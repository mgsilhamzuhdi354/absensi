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
use App\Services\StockAlertService;
use App\Notifications\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    private const PUBLIC_DISK = 'public';
    private const TRANSAKSI_KELUAR = 'keluar';

    private $qrService;
    private $stockService;
    private $bastService;
    private $stockAlertService;

    public function __construct(InventoryQrService $qrService, InventoryStockService $stockService, InventoryBastService $bastService, StockAlertService $stockAlertService)
    {
        $this->qrService = $qrService;
        $this->stockService = $stockService;
        $this->bastService = $bastService;
        $this->stockAlertService = $stockAlertService;
    }

    public function index()
    {
        $title = 'Aset Kantor';
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
        $title = 'Aset Kantor';
        $lokasi = Lokasi::orderBy('nama_lokasi')->get();
        $jabatan = Jabatan::orderBy('nama_jabatan')->get();
        $kode_barang = $this->previewKodeBarang();

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
        $inventory = DB::transaction(function () use ($validated) {
            $validated['kode_barang'] = $this->generateKodeBarang();
            $inventory = Inventory::create($validated);

            try {
                return $this->qrService->ensure($inventory, true);
            } catch (\Throwable $e) {
                \Log::error('Inventory QR generation failed during store', [
                    'inventory_id' => $inventory->id,
                    'message' => $e->getMessage(),
                ]);

                return $inventory->fresh();
            }
        });

        $this->stockAlertService->checkInventory($inventory->fresh());

        return redirect('/inventory/' . $inventory->id . '/detail')->with('success', 'Data Berhasil Disimpan');
    }

    public function edit($id)
    {
        $title = 'Aset Kantor';
        $lokasi = Lokasi::orderBy('nama_lokasi')->get();
        $jabatan = Jabatan::orderBy('nama_jabatan')->get();
        $inventory = Inventory::find($id);
        if (!$inventory) {
            return redirect('/inventory')->with('error', 'Data inventory sudah tidak tersedia.');
        }

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
        $this->stockAlertService->checkInventory($inventory);

        return redirect('/inventory/' . $inventory->id . '/detail')->with('success', 'Data Berhasil Diupdate');
    }

    public function delete($id)
    {
        $inventory = Inventory::find($id);
        if (!$inventory) {
            return redirect('/inventory')->with('success', 'Data inventory sudah terhapus.');
        }

        if ($inventory->foto_barang) {
            Storage::disk(self::PUBLIC_DISK)->delete($inventory->foto_barang);
        }
        if ($inventory->qr_code_image) {
            Storage::disk(self::PUBLIC_DISK)->delete($inventory->qr_code_image);
        }
        $this->stockAlertService->resolve($inventory);
        $inventory->delete();
        return redirect('/inventory')->with('success', 'Data Berhasil Dihapus');
    }

    public function detail($id)
    {
        $title = 'Detail Aset Kantor';
        $inventory = Inventory::with(['lokasi', 'jabatan'])->find($id);
        if (!$inventory) {
            return redirect('/inventory')->with('error', 'Data inventory sudah tidak tersedia.');
        }

        $inventory = $this->qrService->ensure($inventory);
        $inventoryReturnTablesReady = $this->inventoryReturnTablesReady();
        $inventory->load($this->stockTransactionRelations($inventoryReturnTablesReady));

        $deletedStockTransactions = $this->deletedStockTransactions($inventory, $inventoryReturnTablesReady);
        $currentHolderTransaction = $this->currentHolderTransaction($inventory);
        $lokasi = Lokasi::orderBy('nama_lokasi')->get();
        $users = User::with('Jabatan')->orderBy('name')->get();

        return view('inventory.detail', compact('title', 'inventory', 'lokasi', 'users', 'deletedStockTransactions', 'currentHolderTransaction', 'inventoryReturnTablesReady'));
    }

    public function scan()
    {
        $title = 'Scan Aset Kantor';

        return view('inventory.scan', compact('title'));
    }

    public function scanLookup(Request $request)
    {
        $code = $request->query('code', $request->input('code', ''));
        if (is_array($code)) {
            $code = reset($code);
        }
        $code = trim((string) $code);
        $inventory = $this->qrService->findByInput($code);

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
        $this->stockAlertService->checkInventory($inventory->fresh());

        return redirect('/inventory/' . $inventory->id . '/detail')->with('success', 'Stok masuk berhasil disimpan');
    }

    public function stockOut(Request $request, $id)
    {
        $inventory = Inventory::findOrFail($id);
        $validated = $this->validatedStockOutData($request, $inventory);
        $validated = $this->fillReceiverSnapshot($validated);

        $transaction = $this->stockService->stockOut($inventory, $validated, auth()->user());
        $bastMessage = $this->createAutomaticBastForStockOut($transaction, $validated);
        $this->stockAlertService->checkInventory($inventory->fresh());

        return redirect('/inventory/' . $inventory->id . '/detail')
            ->with('success', 'Stok keluar / pindah tangan berhasil disimpan' . ($bastMessage ?? ''));
    }

    public function deleteStockTransaction($id)
    {
        $inventoryReturnTablesReady = $this->inventoryReturnTablesReady();
        $transaction = InventoryStockTransaction::with($this->stockTransactionDeleteRelations($inventoryReturnTablesReady))->findOrFail($id);
        $inventoryId = $transaction->inventory_id;

        if ($this->isProtectedReturnTransaction($transaction, $inventoryReturnTablesReady)) {
            return redirect('/inventory/' . $inventoryId . '/detail')
                ->with('error', 'Transaksi pengembalian aset pegawai keluar tidak bisa dihapus dari riwayat stok.');
        }

        DB::transaction(function () use ($transaction) {
            $this->reverseStockTransaction($transaction);
        });

        if ($inventory = Inventory::find($inventoryId)) {
            $this->stockAlertService->checkInventory($inventory);
        }

        return redirect('/inventory/' . $inventoryId . '/detail')
            ->with('success', 'Riwayat stok berhasil dihapus dan tercatat siapa yang menghapus.');
    }

    public function printQr($id)
    {
        $inventory = Inventory::with(['lokasi', 'jabatan'])->find($id);
        if (!$inventory) {
            return redirect('/inventory')->with('error', 'Data inventory sudah tidak tersedia.');
        }

        $inventory = $this->qrService->ensure($inventory);

        return view('inventory.qr_print', compact('inventory'));
    }

    public function downloadQr($id)
    {
        $inventory = Inventory::find($id);
        if (!$inventory) {
            return redirect('/inventory')->with('error', 'Data inventory sudah tidak tersedia.');
        }

        $inventory = $this->qrService->ensure($inventory);

        if (!$inventory->qr_code_image || !Storage::disk(self::PUBLIC_DISK)->exists($inventory->qr_code_image)) {
            abort(404);
        }

        return Storage::disk(self::PUBLIC_DISK)->download(
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

    public function updateBast(Request $request, $id)
    {
        $document = InventoryBastDocument::with('transaction.inventory')->findOrFail($id);
        if (!$this->inventoryBastPartyColumnsReady()) {
            return redirect('/inventory/' . optional($document->transaction)->inventory_id . '/detail')
                ->with('error', 'Database BAST belum siap. Jalankan php artisan migrate terlebih dahulu.');
        }

        $validated = $request->validate([
            'tanggal_surat' => 'required|date',
            'nama_penyerah' => 'nullable|string|max:255',
            'jabatan_penyerah' => 'nullable|string|max:255',
            'departemen_penyerah' => 'nullable|string|max:255',
            'nama_penerima' => 'nullable|string|max:255',
            'jabatan_penerima' => 'nullable|string|max:255',
            'departemen_penerima' => 'nullable|string|max:255',
            'nama_mengetahui' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($document, $validated) {
            $document->forceFill(array_merge($validated, [
                'party_details_locked' => true,
            ]))->save();

            if ($document->transaction) {
                $document->transaction->forceFill([
                    'penerima_barang' => $validated['nama_penerima'] ?? $document->transaction->penerima_barang,
                    'jabatan_penerima' => $validated['jabatan_penerima'] ?? $document->transaction->jabatan_penerima,
                    'departemen_penerima' => $validated['departemen_penerima'] ?? $document->transaction->departemen_penerima,
                ])->save();
            }
        });

        $document = $this->bastService->storePdf($document->fresh());

        return redirect('/inventory/' . $document->transaction->inventory_id . '/detail')
            ->with('success', 'Detail BAST berhasil diupdate dan PDF sudah dibuat ulang.');
    }

    public function downloadBast($id)
    {
        $document = InventoryBastDocument::with('transaction.inventory')->findOrFail($id);
        $document = $this->bastService->storePdf($document);

        if (!$document->file_pdf || !Storage::disk(self::PUBLIC_DISK)->exists($document->file_pdf)) {
            abort(404);
        }

        return Storage::disk(self::PUBLIC_DISK)->download(
            $document->file_pdf,
            'bast-' . $this->safeFilename($document->nomor_surat) . '.pdf'
        );
    }

    public function myBastDocuments()
    {
        $title = 'BAST Aset Kantor Saya';
        if (!$this->inventoryBastTablesReady()) {
            $documents = new LengthAwarePaginator([], 0, 10, 1, [
                'path' => request()->url(),
                'pageName' => 'page',
            ]);

            return view('inventory.my_bast_index', compact('title', 'documents'));
        }

        $documents = $this->myBastDocumentQuery(auth()->id())
            ->latest('inventory_bast_documents.created_at')
            ->paginate(10)
            ->withQueryString();

        return view('inventory.my_bast_index', compact('title', 'documents'));
    }

    public function showMyBastDocument($id)
    {
        if (!$this->inventoryBastTablesReady()) {
            abort(404);
        }

        $title = 'Detail BAST Aset Kantor';
        $document = $this->myBastDocumentQuery(auth()->id())->findOrFail($id);

        return view('inventory.my_bast_show', compact('title', 'document'));
    }

    public function signMyBastDocument(Request $request, $id, $role = 'receiver')
    {
        if (!$this->inventoryBastTablesReady()) {
            abort(404);
        }

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

        $request->validate($this->signatureValidationRules(), $this->signatureValidationMessages());

        if (!$document->{$roleConfig['signed_at']}) {
            $this->bastService->storeSignature(
                $document,
                $role,
                $request->input('signature_data'),
                auth()->user(),
                $request->ip(),
                $request->userAgent()
            );
        }

        return redirect('/my-inventory-bast/' . $document->id)
            ->with('success', $roleConfig['label'] . ' berhasil ditandatangani dan PDF sudah diperbarui.');
    }

    public function downloadMyBastDocument($id)
    {
        if (!$this->inventoryBastTablesReady()) {
            abort(404);
        }

        $document = $this->myBastDocumentQuery(auth()->id())->findOrFail($id);
        $document = $this->bastService->storePdf($document);

        if (!$document->file_pdf || !Storage::disk(self::PUBLIC_DISK)->exists($document->file_pdf)) {
            abort(404);
        }

        return Storage::disk(self::PUBLIC_DISK)->download(
            $document->file_pdf,
            'bast-' . $this->safeFilename($document->nomor_surat) . '.pdf'
        );
    }

    private function stockTransactionRelations(bool $includeReturnDocuments): array
    {
        $relations = [
            'lokasi',
            'jabatan',
            'stockTransactions.lokasi',
            'stockTransactions.penerima.Jabatan',
            'stockTransactions.processedBy',
            'stockTransactions.bastDocument.signedBy',
            'stockTransactions.bastDocument.knownBy',
            'stockTransactions.bastDocument.firstParty',
        ];

        if ($includeReturnDocuments) {
            $relations[] = 'stockTransactions.returnDocument';
        }

        return $relations;
    }

    private function deletedStockTransactions(Inventory $inventory, bool $includeReturnDocuments)
    {
        return InventoryStockTransaction::onlyTrashed()
            ->with($this->deletedStockTransactionRelations($includeReturnDocuments))
            ->where('inventory_id', $inventory->id)
            ->latest('deleted_at')
            ->latest('id')
            ->get();
    }

    private function deletedStockTransactionRelations(bool $includeReturnDocuments): array
    {
        $relations = [
            'processedBy',
            'deletedBy',
            'bastDocument.signedBy',
            'bastDocument.knownBy',
            'bastDocument.firstParty',
        ];

        if ($includeReturnDocuments) {
            $relations[] = 'returnDocument';
        }

        return $relations;
    }

    private function currentHolderTransaction(Inventory $inventory)
    {
        $transaction = InventoryStockTransaction::with(['penerima.Jabatan', 'processedBy'])
            ->where('inventory_id', $inventory->id)
            ->latest('tanggal_transaksi')
            ->latest('id')
            ->first();

        if (!$transaction || $transaction->jenis_transaksi !== self::TRANSAKSI_KELUAR) {
            return null;
        }

        return ($transaction->penerima_barang || $transaction->penerima_user_id) ? $transaction : null;
    }

    private function validatedStockOutData(Request $request, Inventory $inventory): array
    {
        $minimumQuantity = $inventory->usesWholeStock() ? 1 : 0.01;

        return $request->validate([
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
    }

    private function fillReceiverSnapshot(array $data): array
    {
        if (empty($data['penerima_user_id'])) {
            return $data;
        }

        $receiver = User::with('Jabatan')->findOrFail($data['penerima_user_id']);
        $receiverDivision = $receiver->Jabatan->nama_jabatan ?? null;

        $data['penerima_barang'] = $receiver->name;
        $data['jabatan_penerima'] = $receiverDivision;
        $data['departemen_penerima'] = $receiverDivision;

        return $data;
    }

    private function createAutomaticBastForStockOut(InventoryStockTransaction $transaction, array $data)
    {
        if (!$this->shouldCreateAutomaticBast($data)) {
            return null;
        }

        try {
            $document = $this->bastService->createForTransaction($transaction, [
                'tanggal_surat' => $data['tanggal_transaksi'],
                'nama_mengetahui' => $data['nama_mengetahui'] ?? null,
                'known_by_user_id' => $data['known_by_user_id'] ?? null,
                'first_party_user_id' => $data['first_party_user_id'] ?? null,
            ], auth()->user());

            $this->notifyBastSigners($document, auth()->user());

            return ' dan Surat BAST otomatis dibuat (' . $document->nomor_surat . ')';
        } catch (\Throwable $e) {
            \Log::error('Auto BAST creation failed for transaction ' . $transaction->id . ': ' . $e->getMessage());

            return '. Transaksi tersimpan, namun pembuatan BAST otomatis gagal';
        }
    }

    private function shouldCreateAutomaticBast(array $data): bool
    {
        $shouldCreateBast = (bool) ($data['buat_bast_otomatis'] ?? true);
        $hasReceiver = !empty($data['penerima_barang']) || !empty($data['penerima_user_id']);

        return $shouldCreateBast && $hasReceiver;
    }

    private function stockTransactionDeleteRelations(bool $includeReturnDocuments): array
    {
        $relations = ['inventory'];

        if ($includeReturnDocuments) {
            $relations[] = 'returnDocument';
        }

        return $relations;
    }

    private function isProtectedReturnTransaction(InventoryStockTransaction $transaction, bool $returnTablesReady): bool
    {
        return $returnTablesReady
            && ($transaction->return_for_transaction_id || $transaction->returnDocument);
    }

    private function reverseStockTransaction(InventoryStockTransaction $transaction): void
    {
        $lockedInventory = Inventory::whereKey($transaction->inventory_id)->lockForUpdate()->firstOrFail();
        $newStock = $this->stockAfterDeletingTransaction(
            $this->currentStock($lockedInventory),
            $transaction
        );

        $lockedInventory->update([
            'stok' => max(0, $newStock),
        ]);

        $transaction->forceFill([
            'deleted_by' => auth()->id(),
        ])->save();
        $transaction->delete();
    }

    private function currentStock(Inventory $inventory): float
    {
        if ($inventory->usesWholeStock()) {
            return (float) max(0, round((float) ($inventory->stok ?? 0)));
        }

        return round(max(0, (float) ($inventory->stok ?? 0)), 2);
    }

    private function stockAfterDeletingTransaction(float $currentStock, InventoryStockTransaction $transaction): float
    {
        $quantity = (float) ($transaction->jumlah ?? 0);

        if ($transaction->jenis_transaksi === self::TRANSAKSI_KELUAR) {
            return $currentStock + $quantity;
        }

        $newStock = $currentStock - $quantity;
        if ($newStock < 0) {
            throw ValidationException::withMessages([
                'transaction' => 'Transaksi stok masuk ini belum bisa dihapus karena stok saat ini tidak cukup untuk dibalik.',
            ]);
        }

        return $newStock;
    }

    private function signatureValidationRules(): array
    {
        return [
            'agreement' => 'accepted',
            'signature_data' => ['required', 'string', 'regex:/^data:image\/png;base64,/'],
        ];
    }

    private function signatureValidationMessages(): array
    {
        return [
            'agreement.accepted' => 'Centang persetujuan sebelum tanda tangan.',
            'signature_data.required' => 'Bubuhkan tanda tangan di kotak tanda tangan.',
            'signature_data.regex' => 'Format tanda tangan tidak valid.',
        ];
    }

    private function validatedInventoryData(Request $request, Inventory $inventory = null)
    {
        $validated = $request->validate([
            'kode_barang' => 'nullable|string|max:255',
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

        if ($inventory) {
            $validated['kode_barang'] = $inventory->kode_barang;
        } else {
            unset($validated['kode_barang']);
        }
        unset($validated['foto_barang']);

        if ($request->hasFile('foto_barang')) {
            if ($inventory && $inventory->foto_barang) {
                Storage::disk(self::PUBLIC_DISK)->delete($inventory->foto_barang);
            }
            $validated['foto_barang'] = $request->file('foto_barang')->store('inventory/photos', self::PUBLIC_DISK);
        }

        return $validated;
    }

    private function generateKodeBarang(): string
    {
        $counter = $this->lockedCounter('Inventory', 'INV');
        $nextCounter = (int) $counter->counter + 1;
        $counter->update(['counter' => $nextCounter]);

        return $this->formatCounterCode($counter->text ?: 'INV', $nextCounter);
    }

    private function previewKodeBarang(): string
    {
        $counter = Counter::firstOrCreate(
            ['name' => 'Inventory'],
            ['text' => 'INV', 'counter' => 0]
        );

        return $this->formatCounterCode($counter->text ?: 'INV', (int) $counter->counter + 1);
    }

    private function lockedCounter(string $name, string $prefix): Counter
    {
        Counter::firstOrCreate(
            ['name' => $name],
            ['text' => $prefix, 'counter' => 0]
        );

        return Counter::where('name', $name)->lockForUpdate()->firstOrFail();
    }

    private function formatCounterCode(string $prefix, int $counter): string
    {
        return $prefix . '/' . str_pad($counter, 6, '0', STR_PAD_LEFT);
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

    private function inventoryBastTablesReady()
    {
        return Schema::hasTable('inventory_bast_documents')
            && Schema::hasTable('inventory_stock_transactions');
    }

    private function inventoryBastPartyColumnsReady()
    {
        return $this->inventoryBastTablesReady()
            && Schema::hasColumn('inventory_bast_documents', 'departemen_penerima')
            && Schema::hasColumn('inventory_bast_documents', 'departemen_penyerah')
            && Schema::hasColumn('inventory_bast_documents', 'party_details_locked');
    }

    private function inventoryReturnTablesReady()
    {
        return Schema::hasTable('inventory_return_documents')
            && Schema::hasTable('pegawai_keluar_asset_clearances')
            && Schema::hasColumn('inventory_stock_transactions', 'return_for_transaction_id')
            && Schema::hasColumn('inventory_stock_transactions', 'pegawai_keluar_id');
    }

    private function safeFilename($value)
    {
        return trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $value), '-') ?: 'inventory';
    }
}
