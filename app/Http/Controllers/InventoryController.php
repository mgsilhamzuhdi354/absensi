<?php

namespace App\Http\Controllers;

use App\Models\Lokasi;
use App\Models\Counter;
use App\Models\Jabatan;
use App\Models\Inventory;
use App\Models\InventoryBastDocument;
use App\Models\InventoryStockTransaction;
use App\Models\User;
use App\Services\InventoryBastService;
use App\Services\InventoryQrService;
use App\Services\InventoryStockService;
use App\Notifications\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    private $qrService;
    private $stockService;
    private $bastService;

    public function __construct(InventoryQrService $qrService, InventoryStockService $stockService, InventoryBastService $bastService)
    {
        $this->qrService = $qrService;
        $this->stockService = $stockService;
        $this->bastService = $bastService;
    }

    public function index()
    {
        $title = 'Inventory';
        $search = request()->input('search');
        $inventories = Inventory::with(['lokasi', 'jabatan'])
                                ->when($search, function ($query) use ($search) {
                                    $query->where(function ($q) use ($search) {
                                        $q->where('nama_barang', 'LIKE', '%' . $search . '%')
                                            ->orWhere('kode_barang', 'LIKE', '%' . $search . '%')
                                            ->orWhere('serial_number', 'LIKE', '%' . $search . '%')
                                            ->orWhere('merk_tipe', 'LIKE', '%' . $search . '%');
                                    });
                                })
                                ->when(auth()->user()->is_admin !== 'admin', function ($query) {
                                    $query->where('jabatan_id', auth()->user()->jabatan_id);
                                })
                                ->orderBy('id', 'DESC')
                                ->paginate(10)
                                ->withQueryString();

        return view(auth()->user()->is_admin == 'admin' ? 'inventory.index' : 'inventory.indexUser', compact(
            'title',
            'inventories'
        ));
    }

    public function tambah()
    {
        $title = 'Inventory';
        $lokasi = Lokasi::orderBy('nama_lokasi')->get();
        $jabatan = Jabatan::orderBy('nama_jabatan')->get();
        $counter = Counter::firstOrCreate(
            ['name' => 'Inventory'],
            ['text' => 'INV', 'counter' => 0]
        );
        $counter->increment('counter');
        $counter->refresh();
        $next_number = str_pad($counter->counter, 6, '0', STR_PAD_LEFT);
        $kode_barang = $counter->text . '/' . $next_number;

        return view(auth()->user()->is_admin == 'admin' ? 'inventory.tambah' : 'inventory.tambahUser', compact(
            'title',
            'lokasi',
            'jabatan',
            'kode_barang',
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedInventoryData($request);
        $inventory = Inventory::create($validated);
        $this->qrService->ensure($inventory, true);

        return redirect('/inventory/' . $inventory->id . '/detail')->with('success', 'Data Berhasil Disimpan');
    }

    public function edit($id)
    {
        $title = 'Inventory';
        $lokasi = Lokasi::orderBy('nama_lokasi')->get();
        $jabatan = Jabatan::orderBy('nama_jabatan')->get();
        $inventory = Inventory::findOrFail($id);

        return view(auth()->user()->is_admin == 'admin' ? 'inventory.edit' : 'inventory.editUser', compact(
            'title',
            'lokasi',
            'jabatan',
            'inventory',
        ));
    }

    public function update(Request $request, $id)
    {
        $inventory = Inventory::findOrFail($id);
        $oldCondition = $inventory->kondisi;
        $validated = $this->validatedInventoryData($request, $inventory);

        $inventory->update($validated);
        $this->qrService->ensure($inventory, true);
        $inventory->refresh();
        $this->syncInventoryChangesToStockHistory($inventory, $oldCondition);
        $this->bastService->refreshFilesForInventory($inventory);

        return redirect('/inventory/' . $inventory->id . '/detail')->with('success', 'Data Berhasil Diupdate');
    }

    public function delete($id)
    {
        $inventory = Inventory::findOrFail($id);
        if ($inventory->foto_barang) {
            Storage::disk('public')->delete($inventory->foto_barang);
        }
        if ($inventory->qr_code_image) {
            Storage::disk('public')->delete($inventory->qr_code_image);
        }
        $inventory->delete();
        return redirect('/inventory')->with('success', 'Data Berhasil Dihapus');
    }

    public function detail($id)
    {
        $title = 'Detail Inventory';
        $inventory = Inventory::with(['lokasi', 'jabatan'])->findOrFail($id);
        $inventory = $this->qrService->ensure($inventory);
        $inventory->load([
            'lokasi',
            'jabatan',
            'stockTransactions.lokasi',
            'stockTransactions.penerima.Jabatan',
            'stockTransactions.processedBy',
            'stockTransactions.bastDocument.signedBy',
            'stockTransactions.bastDocument.knownBy',
            'stockTransactions.bastDocument.firstParty',
        ]);
        $deletedStockTransactions = InventoryStockTransaction::onlyTrashed()
            ->with(['processedBy', 'deletedBy', 'bastDocument.signedBy', 'bastDocument.knownBy', 'bastDocument.firstParty'])
            ->where('inventory_id', $inventory->id)
            ->latest('deleted_at')
            ->latest('id')
            ->get();
        $latestActiveStockTransaction = InventoryStockTransaction::with(['penerima.Jabatan', 'processedBy'])
            ->where('inventory_id', $inventory->id)
            ->latest('tanggal_transaksi')
            ->latest('id')
            ->first();
        $isCurrentlyHeld = $latestActiveStockTransaction
            && $latestActiveStockTransaction->jenis_transaksi === 'keluar'
            && ($latestActiveStockTransaction->penerima_barang || $latestActiveStockTransaction->penerima_user_id);
        $currentHolderTransaction = $isCurrentlyHeld ? $latestActiveStockTransaction : null;
        $lokasi = Lokasi::orderBy('nama_lokasi')->get();
        $users = User::with('Jabatan')->orderBy('name')->get();

        return view('inventory.detail', compact('title', 'inventory', 'lokasi', 'users', 'deletedStockTransactions', 'currentHolderTransaction'));
    }

    public function scan()
    {
        $title = 'Scan Barang';

        return view('inventory.scan', compact('title'));
    }

    public function scanLookup(Request $request)
    {
        $code = $request->query('code', $request->input('code', ''));
        if (is_array($code)) {
            $code = reset($code);
        }
        $code = trim((string) $code);
        $inventory = $this->findInventoryByQrInput($code);

        if (!$inventory) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Barang tidak ditemukan.',
                ], 404);
            }

            return redirect('/inventory/scan')->with('error', 'Barang tidak ditemukan.');
        }

        $url = url('/inventory/' . $inventory->id . '/detail');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);
        }

        return redirect($url);
    }

    public function stockIn(Request $request, $id)
    {
        $inventory = Inventory::findOrFail($id);
        $validated = $request->validate([
            'tanggal_transaksi' => 'required|date',
            'jumlah' => 'required|numeric|min:0.01',
            'sumber_barang' => 'nullable|string|max:255',
            'kondisi_barang' => 'nullable|string|max:255',
            'lokasi_id' => 'nullable|exists:lokasis,id',
            'catatan' => 'nullable|string',
        ]);

        $this->stockService->stockIn($inventory, $validated, auth()->user());

        return redirect('/inventory/' . $inventory->id . '/detail')->with('success', 'Stok masuk berhasil disimpan');
    }

    public function stockOut(Request $request, $id)
    {
        $inventory = Inventory::findOrFail($id);
        $minimumQuantity = $inventory->usesWholeStock() ? 1 : 0.01;
        $validated = $request->validate([
            'tanggal_transaksi' => 'required|date',
            'jumlah' => 'required|numeric|min:' . $minimumQuantity,
            'penerima_user_id' => 'nullable|exists:users,id',
            'penerima_barang' => 'required_without:penerima_user_id|nullable|string|max:255',
            'jabatan_penerima' => 'nullable|string|max:255',
            'departemen_penerima' => 'nullable|string|max:255',
            'keperluan' => 'nullable|string|max:255',
            'kondisi_barang' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
            'buat_bast_otomatis' => 'nullable|boolean',
            'nama_mengetahui' => 'nullable|string|max:255',
            'known_by_user_id' => 'nullable|exists:users,id',
            'first_party_user_id' => 'nullable|exists:users,id',
        ]);

        if (!empty($validated['penerima_user_id'])) {
            $receiver = User::with('Jabatan')->findOrFail($validated['penerima_user_id']);
            $receiverDivision = $receiver->Jabatan->nama_jabatan ?? null;
            $validated['penerima_barang'] = $receiver->name;
            $validated['jabatan_penerima'] = $receiverDivision;
            $validated['departemen_penerima'] = $receiverDivision;
        }

        $transaction = $this->stockService->stockOut($inventory, $validated, auth()->user());
        $shouldCreateBast = (bool) ($validated['buat_bast_otomatis'] ?? true);
        $hasReceiver = !empty($validated['penerima_barang']) || !empty($validated['penerima_user_id']);
        $bastMessage = null;

        if ($shouldCreateBast && $hasReceiver) {
            try {
                $document = $this->bastService->createForTransaction($transaction, [
                    'tanggal_surat' => $validated['tanggal_transaksi'],
                    'nama_mengetahui' => $validated['nama_mengetahui'] ?? null,
                    'known_by_user_id' => $validated['known_by_user_id'] ?? null,
                    'first_party_user_id' => $validated['first_party_user_id'] ?? null,
                ], auth()->user());
                $this->notifyBastSigners($document, auth()->user());
                $bastMessage = ' dan Surat BAST otomatis dibuat (' . $document->nomor_surat . ')';
            } catch (\Throwable $e) {
                \Log::error('Auto BAST creation failed for transaction ' . $transaction->id . ': ' . $e->getMessage());
                $bastMessage = '. Transaksi tersimpan, namun pembuatan BAST otomatis gagal';
            }
        }

        return redirect('/inventory/' . $inventory->id . '/detail')
            ->with('success', 'Stok keluar / pindah tangan berhasil disimpan' . ($bastMessage ?? ''));
    }

    public function deleteStockTransaction($id)
    {
        $transaction = InventoryStockTransaction::with('inventory')->findOrFail($id);
        $inventoryId = $transaction->inventory_id;

        DB::transaction(function () use ($transaction) {
            $lockedInventory = Inventory::whereKey($transaction->inventory_id)->lockForUpdate()->firstOrFail();
            $currentStock = $lockedInventory->usesWholeStock()
                ? (float) max(0, round((float) ($lockedInventory->stok ?? 0)))
                : round(max(0, (float) ($lockedInventory->stok ?? 0)), 2);
            $quantity = (float) ($transaction->jumlah ?? 0);

            if ($transaction->jenis_transaksi === 'keluar') {
                $newStock = $currentStock + $quantity;
            } else {
                $newStock = $currentStock - $quantity;
                if ($newStock < 0) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Transaksi stok masuk ini belum bisa dihapus karena stok saat ini tidak cukup untuk dibalik.',
                    ]);
                }
            }

            $lockedInventory->update([
                'stok' => max(0, $newStock),
            ]);

            $transaction->forceFill([
                'deleted_by' => auth()->id(),
            ])->save();
            $transaction->delete();
        });

        return redirect('/inventory/' . $inventoryId . '/detail')
            ->with('success', 'Riwayat stok berhasil dihapus dan tercatat siapa yang menghapus.');
    }

    public function printQr($id)
    {
        $inventory = Inventory::with(['lokasi', 'jabatan'])->findOrFail($id);
        $inventory = $this->qrService->ensure($inventory);

        return view('inventory.qr_print', compact('inventory'));
    }

    public function downloadQr($id)
    {
        $inventory = Inventory::findOrFail($id);
        $inventory = $this->qrService->ensure($inventory);

        if (!$inventory->qr_code_image || !Storage::disk('public')->exists($inventory->qr_code_image)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $inventory->qr_code_image,
            'qr-' . $this->safeFilename($inventory->kode_barang ?: $inventory->id) . '.png'
        );
    }

    public function createBast(Request $request, $id)
    {
        $transaction = InventoryStockTransaction::with(['inventory', 'bastDocument'])->findOrFail($id);
        $validated = $request->validate([
            'tanggal_surat' => 'nullable|date',
            'nama_mengetahui' => 'nullable|string|max:255',
            'known_by_user_id' => 'nullable|exists:users,id',
            'first_party_user_id' => 'nullable|exists:users,id',
        ]);

        $document = $this->bastService->createForTransaction($transaction, $validated, auth()->user());
        $this->notifyBastSigners($document, auth()->user());

        return redirect('/inventory/' . $transaction->inventory_id . '/detail')
            ->with('success', 'Surat BAST berhasil dibuat: ' . $document->nomor_surat);
    }

    public function downloadBast($id)
    {
        $document = InventoryBastDocument::with('transaction.inventory')->findOrFail($id);
        $document = $this->bastService->storePdf($document);

        if (!$document->file_pdf || !Storage::disk('public')->exists($document->file_pdf)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $document->file_pdf,
            'bast-' . $this->safeFilename($document->nomor_surat) . '.pdf'
        );
    }

    public function myBastDocuments()
    {
        $title = 'BAST Inventory Saya';
        $documents = $this->myBastDocumentQuery(auth()->id())
            ->latest('inventory_bast_documents.created_at')
            ->paginate(10)
            ->withQueryString();

        return view('inventory.my_bast_index', compact('title', 'documents'));
    }

    public function showMyBastDocument($id)
    {
        $title = 'Detail BAST Inventory';
        $document = $this->myBastDocumentQuery(auth()->id())->findOrFail($id);

        return view('inventory.my_bast_show', compact('title', 'document'));
    }

    public function signMyBastDocument(Request $request, $id, $role = 'receiver')
    {
        $role = (string) $role;
        $roleConfig = InventoryBastDocument::signatureRoles()[$role] ?? null;

        if (!$roleConfig) {
            abort(404);
        }

        $document = $this->myBastDocumentQuery(auth()->id())->findOrFail($id);

        if ($document->transaction && method_exists($document->transaction, 'trashed') && $document->transaction->trashed()) {
            return redirect('/my-inventory-bast/' . $document->id)
                ->with('error', 'BAST ini belum bisa ditandatangani karena transaksi stoknya sudah dihapus.');
        }

        if (!$document->canUserSignRole(auth()->user(), $role)) {
            abort(404);
        }

        $request->validate([
            'agreement' => 'accepted',
            'signature_data' => ['required', 'string', 'regex:/^data:image\/png;base64,/'],
        ], [
            'agreement.accepted' => 'Centang persetujuan sebelum tanda tangan.',
            'signature_data.required' => 'Bubuhkan tanda tangan di kotak tanda tangan.',
            'signature_data.regex' => 'Format tanda tangan tidak valid.',
        ]);

        if (!$document->{$roleConfig['signed_at']}) {
            $signaturePath = $this->storeBastSignatureImage($document, $role, $request->input('signature_data'));
            $document->forceFill([
                $roleConfig['user_id'] => auth()->id(),
                $roleConfig['name'] => auth()->user()->name,
                $roleConfig['image'] => $signaturePath,
                $roleConfig['signed_at'] => now(),
                $roleConfig['ip'] => $request->ip(),
                $roleConfig['user_agent'] => substr((string) $request->userAgent(), 0, 1000),
            ])->save();

            $this->bastService->storePdf($document->fresh());
        }

        return redirect('/my-inventory-bast/' . $document->id)
            ->with('success', $roleConfig['label'] . ' berhasil ditandatangani dan PDF sudah diperbarui.');
    }

    public function downloadMyBastDocument($id)
    {
        $document = $this->myBastDocumentQuery(auth()->id())->findOrFail($id);
        $document = $this->bastService->storePdf($document);

        if (!$document->file_pdf || !Storage::disk('public')->exists($document->file_pdf)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $document->file_pdf,
            'bast-' . $this->safeFilename($document->nomor_surat) . '.pdf'
        );
    }

    private function validatedInventoryData(Request $request, Inventory $inventory = null)
    {
        $validated = $request->validate([
            'kode_barang' => 'required|string|max:255',
            'nama_barang' => 'required|string|max:255',
            'jenis_barang' => 'nullable|string|max:255',
            'merk_tipe' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'spesifikasi' => 'nullable|string',
            'kondisi' => 'nullable|string|max:255',
            'status_barang' => 'nullable|string|max:255',
            'tanggal_masuk' => 'nullable|date',
            'stok' => 'required|numeric|min:0',
            'uom' => ['required', 'string', 'max:255', 'not_regex:/^\s*\d+([,.]\d+)?\s*$/'],
            'desc' => 'nullable|string',
            'lokasi_id' => 'required|exists:lokasis,id',
            'jabatan_id' => 'required|exists:jabatans,id',
            'foto_barang' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'uom.not_regex' => 'UoM harus berupa nama satuan, contoh Unit, Pcs, Set, Box, bukan angka stok.',
        ]);

        if (Inventory::isWholeStockUom($validated['uom']) && !$this->isWholeNumber($validated['stok'])) {
            throw ValidationException::withMessages([
                'stok' => 'Stok untuk satuan ' . $validated['uom'] . ' harus angka bulat. Gunakan satuan seperti Kg/Liter/Meter jika memang perlu stok desimal.',
            ]);
        }

        $validated['stok'] = Inventory::isWholeStockUom($validated['uom'])
            ? (int) round((float) $validated['stok'])
            : round((float) $validated['stok'], 2);

        unset($validated['foto_barang']);

        if ($request->hasFile('foto_barang')) {
            if ($inventory && $inventory->foto_barang) {
                Storage::disk('public')->delete($inventory->foto_barang);
            }
            $validated['foto_barang'] = $request->file('foto_barang')->store('inventory/photos', 'public');
        }

        return $validated;
    }

    private function isWholeNumber($value): bool
    {
        $number = (float) $value;

        return abs($number - round($number)) < 0.000001;
    }

    private function syncInventoryChangesToStockHistory(Inventory $inventory, $oldCondition)
    {
        if ($inventory->kondisi === $oldCondition) {
            return;
        }

        InventoryStockTransaction::withTrashed()
            ->where('inventory_id', $inventory->id)
            ->where(function ($query) use ($oldCondition) {
                $query->whereNull('kondisi_barang');

                if ($oldCondition !== null && $oldCondition !== '') {
                    $query->orWhere('kondisi_barang', $oldCondition);
                }
            })
            ->update([
                'kondisi_barang' => $inventory->kondisi,
            ]);
    }

    private function notifyBastSigners(InventoryBastDocument $document, User $sender)
    {
        $document->loadMissing('transaction.inventory', 'transaction.penerima', 'knownBy', 'firstParty');

        if (!$document->transaction) {
            return;
        }

        $inventoryName = $document->transaction->inventory->nama_barang ?? 'barang inventori';
        $message = 'Surat BAST ' . $document->nomor_surat . ' untuk ' . $inventoryName . ' menunggu tanda tangan Anda.';
        $signers = collect([
            $document->transaction->penerima,
            $document->knownBy,
            $document->firstParty,
        ])->filter()->unique('id');

        foreach ($signers as $signer) {
            $signer->messages = [
                'user_id' => $sender->id,
                'from' => $sender->name,
                'message' => $message,
                'action' => '/my-inventory-bast/' . $document->id,
                'bast_document_id' => $document->id,
            ];
            $signer->notify(new UserNotification);
        }
    }

    private function myBastDocumentQuery($userId)
    {
        return InventoryBastDocument::with([
                'signedBy.Jabatan',
                'knownBy.Jabatan',
                'firstParty.Jabatan',
                'transaction.inventory.lokasi',
                'transaction.inventory.jabatan',
                'transaction.processedBy',
                'transaction.penerima.Jabatan',
            ])
            ->where(function ($query) use ($userId) {
                $query->whereHas('transaction', function ($transactionQuery) use ($userId) {
                    $transactionQuery->where('penerima_user_id', $userId);
                })
                    ->orWhere('known_by_user_id', $userId)
                    ->orWhere('first_party_user_id', $userId);
            });
    }

    private function storeBastSignatureImage(InventoryBastDocument $document, $role, $signatureData)
    {
        $payload = preg_replace('/^data:image\/png;base64,/', '', (string) $signatureData);
        $binary = base64_decode($payload, true);

        if ($binary === false || strlen($binary) < 20) {
            throw ValidationException::withMessages([
                'signature_data' => 'Tanda tangan tidak valid. Silakan hapus dan tanda tangani ulang.',
            ]);
        }

        $path = 'inventory/bast/signatures/' . $document->id . '-' . $this->safeFilename($role) . '-' . time() . '.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    private function findInventoryByQrInput($value)
    {
        $candidates = $this->extractInventoryCandidates($value);
        if (empty($candidates)) {
            return null;
        }

        foreach ($candidates as $candidate) {
            if (preg_match('/^id:([0-9]+)$/', $candidate, $matches)) {
                $byId = Inventory::find((int) $matches[1]);
                if ($byId) {
                    return $byId;
                }
            }

            $inventory = Inventory::where('qr_token', $candidate)
                ->orWhere('qr_code_value', $candidate)
                ->orWhere('kode_barang', $candidate)
                ->first();

            if ($inventory) {
                return $inventory;
            }
        }

        return null;
    }

    private function extractInventoryCandidates($value)
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }

        $raw = preg_replace('/[\x00-\x1F\x7F\x{200B}-\x{200D}\x{FEFF}]/u', '', $raw);
        $candidates = [$raw];
        if (ctype_digit($raw)) {
            $candidates[] = 'id:' . $raw;
        }

        for ($i = 0; $i < 2; $i++) {
            $decoded = urldecode($raw);
            if ($decoded !== $raw) {
                $candidates[] = trim($decoded);
                $raw = $decoded;
                continue;
            }
            break;
        }

        if (filter_var($raw, FILTER_VALIDATE_URL)) {
            $path = parse_url($raw, PHP_URL_PATH);
            $query = parse_url($raw, PHP_URL_QUERY);

            if ($path && preg_match('#/inventory/([0-9]+)/detail#i', $path, $matches)) {
                $candidates[] = 'id:' . $matches[1];
            }

            if ($path && preg_match('~/assets/detail/([^/?#]+)~i', $path, $matches)) {
                $candidates[] = trim(urldecode($matches[1]));
            }

            if ($path) {
                $segments = array_values(array_filter(explode('/', trim($path, '/'))));
                if (!empty($segments)) {
                    $candidates[] = trim(urldecode((string) end($segments)));
                }
            }

            if ($query) {
                parse_str($query, $params);
                foreach (['code', 'token', 'qr', 'asset', 'kode', 'kode_barang', 'id'] as $key) {
                    if (!empty($params[$key])) {
                        $valueParam = trim((string) $params[$key]);
                        $candidates[] = ctype_digit($valueParam) ? 'id:' . $valueParam : $valueParam;
                    }
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12})/i', $candidate, $uuidMatch)) {
                $candidates[] = strtolower($uuidMatch[1]);
            }

            if (preg_match('/^(?:INV-ASSET:|ASSET-|INV-QR:|QR:)\s*(.+)$/i', $candidate, $prefixed)) {
                $candidates[] = trim($prefixed[1]);
            }

            if (preg_match('/^[A-Za-z]+-\d+$/', $candidate)) {
                $candidates[] = str_replace('-', '/', $candidate);
            }
        }

        $normalized = [];
        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }
            $normalized[$candidate] = true;
        }

        return array_keys($normalized);
    }

    private function safeFilename($value)
    {
        return trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $value), '-') ?: 'inventory';
    }
}
