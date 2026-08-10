<?php

namespace App\Http\Controllers;

use App\Exports\AtkExport;
use App\Models\AssetTransfer;
use App\Models\Atk;
use App\Models\AtkStockTransaction;
use App\Models\AtkStockVariant;
use App\Models\Company;
use App\Models\Counter;
use App\Services\AtkQrService;
use App\Services\CompanyContext;
use App\Services\StockAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AtkController extends Controller
{
    private const PUBLIC_DISK = 'public';

    private $qrService;
    private $stockAlertService;

    public function __construct(AtkQrService $qrService, StockAlertService $stockAlertService)
    {
        $this->qrService = $qrService;
        $this->stockAlertService = $stockAlertService;
    }

    public function index()
    {
        $title = 'ATK';
        $search = request()->input('search');
        $status = request()->input('status');
        $atks = Atk::with('company')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('kode_atk', 'LIKE', '%' . $search . '%')
                        ->orWhere('nama_atk', 'LIKE', '%' . $search . '%')
                        ->orWhere('kategori', 'LIKE', '%' . $search . '%')
                        ->orWhere('lokasi', 'LIKE', '%' . $search . '%');
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('active', (int) $status);
            })
            ->when(Schema::hasTable('atk_stock_variants'), function ($query) {
                $query->with('stockVariants');
            })
            ->orderBy('id', 'DESC')
            ->paginate(10)
            ->withQueryString();

        return view('atk.index', compact('title', 'atks'));
    }

    public function tambah()
    {
        $title = 'ATK';
        $companyId = app(CompanyContext::class)->currentCompanyId();
        $companies = Company::active()->orderBy('name')->get();
        $kode_atk = $this->previewKodeAtk($companyId ?: app(CompanyContext::class)->defaultCompanyId());
        $companyCodePreviews = $companies->mapWithKeys(function ($company) {
            return [$company->id => $this->previewKodeAtk($company->id)];
        });

        return view('atk.tambah', compact('title', 'kode_atk', 'companies', 'companyId', 'companyCodePreviews'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedAtkData($request);
        $initialColor = $this->normalizeColor($request->input('warna_barang'));
        $atk = DB::transaction(function () use ($validated, $initialColor) {
            $validated['kode_atk'] = $this->generateKodeAtk($validated['company_id']);
            $atk = Atk::create($validated);
            $this->syncAtkVariantTotal($atk, $initialColor);

            try {
                return $this->qrService->ensure($atk, true);
            } catch (\Throwable $e) {
                \Log::error('ATK QR generation failed during store', [
                    'atk_id' => $atk->id,
                    'message' => $e->getMessage(),
                ]);

                return $atk->fresh();
            }
        });

        $this->stockAlertService->checkAtk(Atk::withoutGlobalScope('company')->find($atk->id));
        app(CompanyContext::class)->setActiveCompany($atk->company_id);

        return redirect('/atk/' . $atk->id . '/detail')->with('success', 'Data Berhasil Disimpan');
    }

    public function detail($id)
    {
        $title = 'Detail ATK';
        $atk = Atk::find($id);
        if (!$atk) {
            return redirect('/atk')->with('error', 'Data ATK sudah tidak tersedia.');
        }

        $atk = $this->qrService->ensure($atk);
        $atk->load(['stockTransactions.processedBy']);
        if (Schema::hasTable('atk_stock_variants')) {
            $atk->load('stockVariants');
        }
        $deletedStockTransactions = $this->deletedStockTransactions($atk);
        $transferCompanies = Company::active()
            ->where('id', '!=', $atk->company_id)
            ->orderBy('name')
            ->get();
        $assetTransfers = AssetTransfer::withoutGlobalScope('company')
            ->with(['sourceCompany', 'destinationCompany', 'processedBy'])
            ->where('transferable_type', Atk::class)
            ->where(function ($query) use ($atk) {
                $query->where('transferable_id', $atk->id)
                    ->orWhere('target_transferable_id', $atk->id);
            })
            ->latest('tanggal_transfer')
            ->latest('id')
            ->get();

        return view('atk.detail', compact('title', 'atk', 'deletedStockTransactions', 'transferCompanies', 'assetTransfers'));
    }

    public function edit($id)
    {
        $title = 'ATK';
        $companyId = app(CompanyContext::class)->currentCompanyId();
        $companies = Company::active()->orderBy('name')->get();
        $companyCodePreviews = collect();
        $atk = Atk::find($id);
        if (!$atk) {
            return redirect('/atk')->with('error', 'Data ATK sudah tidak tersedia.');
        }
        if (Schema::hasTable('atk_stock_variants')) {
            $atk->load('stockVariants');
        }

        return view('atk.edit', compact('title', 'atk', 'companies', 'companyId', 'companyCodePreviews'));
    }

    public function update(Request $request, $id)
    {
        $atk = Atk::findOrFail($id);
        $validated = $this->validatedAtkData($request, $atk);
        $atk->update($validated);
        $this->syncAtkVariantTotal($atk->fresh(), $request->input('warna_barang'));
        $this->qrService->ensure($atk, true);
        $this->stockAlertService->checkAtk($atk->fresh());

        return redirect('/atk/' . $atk->id . '/detail')->with('success', 'Data Berhasil Diupdate');
    }

    public function delete($id)
    {
        $atk = Atk::find($id);
        if (!$atk) {
            return redirect('/atk')->with('success', 'Data ATK sudah terhapus.');
        }

        if ($atk->qr_code_image) {
            Storage::disk(self::PUBLIC_DISK)->delete($atk->qr_code_image);
        }
        if ($atk->foto_barang) {
            Storage::disk(self::PUBLIC_DISK)->delete($atk->foto_barang);
        }
        $this->stockAlertService->resolve($atk);
        $atk->delete();

        return redirect('/atk')->with('success', 'Data Berhasil Dihapus');
    }

    public function stockIn(Request $request, $id)
    {
        $atk = Atk::findOrFail($id);
        $validated = $request->validate([
            'tanggal_transaksi' => 'required|date',
            'jumlah' => 'required|numeric|min:0.01',
            'warna_barang' => 'nullable|string|max:80',
            'sumber_barang' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
        ]);

        $this->recordStockTransaction($atk, 'masuk', $validated);
        $this->stockAlertService->checkAtk($atk->fresh());

        return redirect('/atk/' . $atk->id . '/detail')->with('success', 'Stok masuk ATK berhasil disimpan');
    }

    public function stockOut(Request $request, $id)
    {
        $atk = Atk::findOrFail($id);
        $validated = $request->validate([
            'tanggal_transaksi' => 'required|date',
            'jumlah' => 'required|numeric|min:0.01',
            'warna_barang' => 'nullable|string|max:80',
            'penerima_barang' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
        ]);

        $this->recordStockTransaction($atk, 'keluar', $validated);
        $this->stockAlertService->checkAtk($atk->fresh());

        return redirect('/atk/' . $atk->id . '/detail')->with('success', 'Stok keluar ATK berhasil disimpan');
    }

    public function transferCompany(Request $request, $id)
    {
        $atk = Atk::findOrFail($id);
        $validated = $request->validate([
            'destination_company_id' => [
                'required',
                Rule::exists('companies', 'id')->where(fn ($query) => $query->where('active', 1)),
            ],
            'tanggal_transfer' => 'required|date',
            'jumlah' => 'required|numeric|min:0.01',
            'warna_barang' => 'nullable|string|max:80',
            'catatan' => 'nullable|string',
        ]);

        if ((int) $validated['destination_company_id'] === (int) $atk->company_id) {
            throw ValidationException::withMessages([
                'destination_company_id' => 'Perusahaan tujuan harus berbeda dari perusahaan asal.',
            ]);
        }

        $targetAtkId = null;
        $transfer = DB::transaction(function () use ($atk, $validated, &$targetAtkId) {
            $source = Atk::withoutGlobalScope('company')->findOrFail($atk->id);
            $destinationCompany = Company::findOrFail($validated['destination_company_id']);
            $sourceCompany = Company::find($source->company_id);
            $quantity = round((float) $validated['jumlah'], 2);
            $color = $this->normalizeColor($validated['warna_barang'] ?? null);
            $note = $validated['catatan'] ?? null;

            $outgoing = $this->recordStockTransaction($source, 'keluar', [
                'tanggal_transaksi' => $validated['tanggal_transfer'],
                'jumlah' => $quantity,
                'warna_barang' => $color,
                'penerima_barang' => $destinationCompany->name,
                'catatan' => trim('Transfer ke ' . $destinationCompany->name . ($note ? "\n" . $note : '')),
            ]);

            $target = $this->cloneAtkForTransfer($source, (int) $destinationCompany->id);
            $incoming = $this->recordStockTransaction($target, 'masuk', [
                'tanggal_transaksi' => $validated['tanggal_transfer'],
                'jumlah' => $quantity,
                'warna_barang' => $color,
                'sumber_barang' => $sourceCompany->name ?? 'Perusahaan asal',
                'catatan' => trim('Transfer dari ' . ($sourceCompany->name ?? 'Perusahaan asal') . ($note ? "\n" . $note : '')),
            ]);

            $target = $this->qrService->ensure($target, true);
            $targetAtkId = $target->id;

            return AssetTransfer::create([
                'company_id' => $source->company_id,
                'source_company_id' => $source->company_id,
                'destination_company_id' => $destinationCompany->id,
                'transferable_type' => Atk::class,
                'transferable_id' => $source->id,
                'target_transferable_id' => $target->id,
                'outgoing_transaction_id' => $outgoing->id,
                'incoming_transaction_id' => $incoming->id,
                'jumlah' => $quantity,
                'warna_barang' => $color,
                'tanggal_transfer' => $validated['tanggal_transfer'],
                'catatan' => $note,
                'diproses_oleh' => auth()->id(),
            ]);
        });

        $this->stockAlertService->checkAtk(Atk::withoutGlobalScope('company')->find($atk->id));
        $this->stockAlertService->checkAtk(Atk::withoutGlobalScope('company')->find($targetAtkId));
        app(CompanyContext::class)->setActiveCompany((int) $validated['destination_company_id']);

        return redirect('/atk/' . $targetAtkId . '/detail')
            ->with('success', 'Transfer ATK antar perusahaan berhasil disimpan. Nomor transfer #' . $transfer->id);
    }

    public function updateStockAlert(Request $request, $id)
    {
        $atk = Atk::findOrFail($id);
        $request->validate([
            'stock_alert_enabled' => 'nullable|boolean',
        ]);

        $atk->forceFill([
            'stock_alert_enabled' => $request->boolean('stock_alert_enabled') ? 1 : 0,
        ])->save();

        $this->stockAlertService->checkAtk($atk->fresh());

        return back()->with(
            'success',
            $atk->stock_alert_enabled
                ? 'Notifikasi stok ATK berhasil diaktifkan.'
                : 'Notifikasi stok ATK berhasil dinonaktifkan.'
        );
    }

    public function deleteStockTransaction($id)
    {
        $transaction = AtkStockTransaction::with('atk')->findOrFail($id);
        $atkId = $transaction->atk_id;

        DB::transaction(function () use ($transaction) {
            $lockedAtk = Atk::whereKey($transaction->atk_id)->lockForUpdate()->firstOrFail();
            $currentStock = $this->currentStock($lockedAtk);
            $quantity = (float) ($transaction->jumlah ?? 0);

            $newStock = $transaction->jenis_transaksi === 'keluar'
                ? $currentStock + $quantity
                : $currentStock - $quantity;

            $this->reverseAtkVariantStock($lockedAtk, $transaction, $quantity);

            if ($newStock < -0.000001) {
                throw ValidationException::withMessages([
                    'transaction' => 'Transaksi stok masuk ini belum bisa dihapus karena stok saat ini tidak cukup untuk dibalik.',
                ]);
            }

            $lockedAtk->update([
                'stok' => round(max(0, $newStock), 2),
            ]);

            $transaction->forceFill([
                'deleted_by' => auth()->id(),
            ])->save();
            $transaction->delete();
        });

        if ($atk = Atk::find($atkId)) {
            $this->stockAlertService->checkAtk($atk);
        }

        return redirect('/atk/' . $atkId . '/detail')
            ->with('success', 'Riwayat stok ATK berhasil dihapus dan stok sudah disesuaikan.');
    }

    public function scan()
    {
        $title = 'Scan ATK';

        return view('atk.scan', compact('title'));
    }

    public function scanLookup(Request $request)
    {
        $code = $request->query('code', $request->input('code', ''));
        if (is_array($code)) {
            $code = reset($code);
        }
        $code = trim((string) $code);
        $atk = $this->qrService->findByInput($code);

        if (!$atk) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ATK tidak ditemukan.',
                ], 404);
            }

            return redirect('/atk/scan')->with('error', 'ATK tidak ditemukan.');
        }

        $url = url('/atk/' . $atk->id . '/detail');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);
        }

        return redirect($url);
    }

    public function printQr($id)
    {
        $atk = Atk::find($id);
        if (!$atk) {
            return redirect('/atk')->with('error', 'Data ATK sudah tidak tersedia.');
        }

        $atk = $this->qrService->ensure($atk);

        return view('atk.qr_print', compact('atk'));
    }

    public function downloadQr($id)
    {
        $atk = Atk::find($id);
        if (!$atk) {
            return redirect('/atk')->with('error', 'Data ATK sudah tidak tersedia.');
        }

        $atk = $this->qrService->ensure($atk);

        if (!$atk->qr_code_image || !Storage::disk(self::PUBLIC_DISK)->exists($atk->qr_code_image)) {
            abort(404);
        }

        return Storage::disk(self::PUBLIC_DISK)->download(
            $atk->qr_code_image,
            'qr-atk-' . $this->safeFilename($atk->kode_atk ?: $atk->id) . '.png'
        );
    }

    public function export(Request $request)
    {
        return (new AtkExport($request->all()))->download('Report ATK.xlsx');
    }

    private function generateKodeAtk(int $companyId): string
    {
        $counter = $this->lockedCounter('ATK', 'ATK', $companyId);
        $nextCounter = (int) $counter->counter + 1;
        $counter->update(['counter' => $nextCounter]);

        return $this->formatCounterCode($this->companyCode($companyId) . '/' . ($counter->text ?: 'ATK'), $nextCounter);
    }

    private function previewKodeAtk(?int $companyId): string
    {
        $companyId = $companyId ?: app(CompanyContext::class)->defaultCompanyId();
        $counter = Counter::withoutGlobalScope('company')->firstOrCreate(
            ['company_id' => $companyId, 'name' => 'ATK'],
            ['text' => 'ATK', 'counter' => 0]
        );

        return $this->formatCounterCode($this->companyCode($companyId) . '/' . ($counter->text ?: 'ATK'), (int) $counter->counter + 1);
    }

    private function lockedCounter(string $name, string $prefix, int $companyId): Counter
    {
        Counter::withoutGlobalScope('company')->firstOrCreate(
            ['company_id' => $companyId, 'name' => $name],
            ['text' => $prefix, 'counter' => 0]
        );

        return Counter::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('name', $name)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function formatCounterCode(string $prefix, int $counter): string
    {
        return $prefix . '/' . str_pad($counter, 6, '0', STR_PAD_LEFT);
    }

    private function validatedAtkData(Request $request, Atk $atk = null): array
    {
        $validated = $request->validate([
            'kode_atk' => 'nullable|string|max:255',
            'nama_atk' => 'required|string|max:255',
            'kategori' => 'nullable|string|max:255',
            'stok' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
            'foto_barang' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'active' => 'nullable|boolean',
            'stock_alert_enabled' => 'nullable|boolean',
            'warna_barang' => 'nullable|string|max:80',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $validated['stok'] = round((float) $validated['stok'], 2);
        $validated['company_id'] = $atk
            ? $atk->company_id
            : $this->selectedCompanyId($request);
        $validated['active'] = $request->boolean('active') ? 1 : 0;
        $defaultStockAlertEnabled = $atk ? (bool) $atk->stock_alert_enabled : true;
        $validated['stock_alert_enabled'] = $request->has('stock_alert_enabled')
            ? ($request->boolean('stock_alert_enabled') ? 1 : 0)
            : ($defaultStockAlertEnabled ? 1 : 0);
        if ($atk) {
            $validated['kode_atk'] = $atk->kode_atk;
        } else {
            unset($validated['kode_atk']);
        }
        unset($validated['foto_barang']);
        unset($validated['warna_barang']);

        if ($request->hasFile('foto_barang')) {
            if ($atk && $atk->foto_barang) {
                Storage::disk(self::PUBLIC_DISK)->delete($atk->foto_barang);
            }
            $validated['foto_barang'] = $request->file('foto_barang')->store('atk/photos', self::PUBLIC_DISK);
        }

        return $validated;
    }

    private function recordStockTransaction(Atk $atk, string $type, array $data): AtkStockTransaction
    {
        return DB::transaction(function () use ($atk, $type, $data) {
            $lockedAtk = Atk::withoutGlobalScope('company')->whereKey($atk->id)->lockForUpdate()->firstOrFail();
            $stockBefore = $this->currentStock($lockedAtk);
            $quantity = round((float) $data['jumlah'], 2);
            $color = $this->normalizeColor($data['warna_barang'] ?? null);
            $this->ensureAtkVariantsInitialized($lockedAtk);
            $variant = $this->lockOrCreateAtkVariant($lockedAtk, $color);
            $variantStockBefore = round(max(0, (float) ($variant->stok ?? 0)), 2);
            $stockAfter = $type === 'masuk'
                ? $stockBefore + $quantity
                : $stockBefore - $quantity;

            if ($type === 'keluar' && $stockAfter < -0.000001) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Jumlah keluar tidak boleh melebihi stok tersedia.',
                ]);
            }

            if ($type === 'keluar' && ($variantStockBefore - $quantity) < -0.000001) {
                throw ValidationException::withMessages([
                    'warna_barang' => 'Stok warna ' . $color . ' tidak mencukupi. Stok tersedia ' . $this->formatStockValue($variantStockBefore) . ' ' . ($lockedAtk->satuan ?: 'Pcs') . '.',
                ]);
            }

            $stockAfter = round(max(0, $stockAfter), 2);
            $variant->update([
                'stok' => $type === 'masuk'
                    ? round($variantStockBefore + $quantity, 2)
                    : round(max(0, $variantStockBefore - $quantity), 2),
            ]);
            $lockedAtk->update([
                'stok' => $stockAfter,
            ]);

            return AtkStockTransaction::create([
                'company_id' => $lockedAtk->company_id,
                'atk_id' => $lockedAtk->id,
                'jenis_transaksi' => $type,
                'jumlah' => $quantity,
                'warna_barang' => $color,
                'stok_sebelum' => $stockBefore,
                'stok_sesudah' => $stockAfter,
                'tanggal_transaksi' => $data['tanggal_transaksi'],
                'sumber_barang' => $data['sumber_barang'] ?? null,
                'penerima_barang' => $data['penerima_barang'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'diproses_oleh' => auth()->id(),
            ]);
        });
    }

    private function cloneAtkForTransfer(Atk $source, int $destinationCompanyId): Atk
    {
        return Atk::create([
            'company_id' => $destinationCompanyId,
            'kode_atk' => $this->generateKodeAtk($destinationCompanyId),
            'nama_atk' => $source->nama_atk,
            'kategori' => $source->kategori,
            'stok' => 0,
            'satuan' => $source->satuan,
            'lokasi' => null,
            'keterangan' => trim(($source->keterangan ?: '') . "\n\nTransfer dari " . ($source->company->name ?? 'perusahaan asal')),
            'active' => $source->active,
            'stock_alert_enabled' => $source->stock_alert_enabled,
        ]);
    }

    private function syncAtkVariantTotal(Atk $atk, ?string $color): void
    {
        if (!Schema::hasTable('atk_stock_variants')) {
            return;
        }

        DB::transaction(function () use ($atk, $color) {
            $lockedAtk = Atk::withoutGlobalScope('company')->whereKey($atk->id)->lockForUpdate()->firstOrFail();
            $targetStock = $this->currentStock($lockedAtk);
            $variantTotal = round((float) AtkStockVariant::where('atk_id', $lockedAtk->id)->sum('stok'), 2);
            $delta = round($targetStock - $variantTotal, 2);

            $this->applyAtkVariantDelta($lockedAtk, $this->normalizeColor($color), $delta);
        });
    }

    private function reverseAtkVariantStock(Atk $atk, AtkStockTransaction $transaction, float $quantity): void
    {
        if (!Schema::hasTable('atk_stock_variants')) {
            return;
        }

        $color = $this->normalizeColor($transaction->warna_barang ?? null);
        $this->ensureAtkVariantsInitialized($atk);
        $variant = $this->lockOrCreateAtkVariant($atk, $color);
        $variantStockBefore = round(max(0, (float) ($variant->stok ?? 0)), 2);

        if ($transaction->jenis_transaksi === 'keluar') {
            $variant->update([
                'stok' => round($variantStockBefore + $quantity, 2),
            ]);
            return;
        }

        $variantStockAfter = $variantStockBefore - $quantity;
        if ($variantStockAfter < -0.000001) {
            throw ValidationException::withMessages([
                'transaction' => 'Transaksi stok masuk warna ' . $color . ' belum bisa dihapus karena stok warna saat ini tidak cukup untuk dibalik.',
            ]);
        }

        $variant->update([
            'stok' => round(max(0, $variantStockAfter), 2),
        ]);
    }

    private function applyAtkVariantDelta(Atk $atk, string $color, float $delta): void
    {
        if (abs($delta) < 0.000001) {
            return;
        }

        if ($delta > 0) {
            $variant = $this->lockOrCreateAtkVariant($atk, $color);
            $variant->update([
                'stok' => round(max(0, (float) $variant->stok) + $delta, 2),
            ]);
            return;
        }

        $remaining = abs($delta);
        $variants = AtkStockVariant::where('atk_id', $atk->id)
            ->orderByRaw('CASE WHEN warna_barang = ? THEN 0 ELSE 1 END', [$color])
            ->orderByDesc('stok')
            ->lockForUpdate()
            ->get();

        foreach ($variants as $variant) {
            if ($remaining <= 0) {
                break;
            }

            $available = round(max(0, (float) $variant->stok), 2);
            $taken = min($available, $remaining);
            $variant->update([
                'stok' => round($available - $taken, 2),
            ]);
            $remaining = round($remaining - $taken, 2);
        }
    }

    private function lockOrCreateAtkVariant(Atk $atk, string $color): AtkStockVariant
    {
        $variant = AtkStockVariant::where('atk_id', $atk->id)
            ->where('warna_barang', $color)
            ->lockForUpdate()
            ->first();

        if ($variant) {
            return $variant;
        }

        return AtkStockVariant::create([
            'company_id' => $atk->company_id,
            'atk_id' => $atk->id,
            'warna_barang' => $color,
            'stok' => 0,
        ]);
    }

    private function selectedCompanyId(Request $request): int
    {
        $companyId = $request->input('company_id') ?: app(CompanyContext::class)->currentCompanyId();

        return (int) ($companyId ?: app(CompanyContext::class)->defaultCompanyId());
    }

    private function companyCode(int $companyId): string
    {
        return Company::whereKey($companyId)->value('code') ?: 'IOS';
    }

    private function ensureAtkVariantsInitialized(Atk $atk): void
    {
        if (!Schema::hasTable('atk_stock_variants')) {
            return;
        }

        $hasVariant = AtkStockVariant::where('atk_id', $atk->id)->exists();
        if ($hasVariant) {
            return;
        }

        $stock = $this->currentStock($atk);
        if ($stock <= 0) {
            return;
        }

        AtkStockVariant::create([
            'company_id' => $atk->company_id,
            'atk_id' => $atk->id,
            'warna_barang' => 'Umum',
            'stok' => $stock,
        ]);
    }

    private function currentStock(Atk $atk): float
    {
        return round(max(0, (float) ($atk->stok ?? 0)), 2);
    }

    private function normalizeColor($value): string
    {
        $color = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $color !== '' ? mb_substr($color, 0, 80) : 'Umum';
    }

    private function formatStockValue($value): string
    {
        $formatted = number_format((float) ($value ?? 0), 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }

    private function deletedStockTransactions(Atk $atk)
    {
        return AtkStockTransaction::onlyTrashed()
            ->with(['processedBy', 'deletedBy'])
            ->where('atk_id', $atk->id)
            ->latest('deleted_at')
            ->latest('id')
            ->get();
    }

    private function safeFilename($value): string
    {
        return trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $value), '-') ?: 'atk';
    }
}
