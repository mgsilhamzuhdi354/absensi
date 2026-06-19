<?php

namespace App\Http\Controllers;

use App\Exports\AtkExport;
use App\Models\Atk;
use App\Models\AtkStockTransaction;
use App\Models\Counter;
use App\Services\AtkQrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AtkController extends Controller
{
    private const PUBLIC_DISK = 'public';

    private $qrService;

    public function __construct(AtkQrService $qrService)
    {
        $this->qrService = $qrService;
    }

    public function index()
    {
        $title = 'ATK';
        $search = request()->input('search');
        $status = request()->input('status');
        $atks = Atk::when($search, function ($query) use ($search) {
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
            ->orderBy('id', 'DESC')
            ->paginate(10)
            ->withQueryString();

        return view('atk.index', compact('title', 'atks'));
    }

    public function tambah()
    {
        $title = 'ATK';
        $kode_atk = $this->previewKodeAtk();

        return view('atk.tambah', compact('title', 'kode_atk'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedAtkData($request);
        $atk = DB::transaction(function () use ($validated) {
            $validated['kode_atk'] = $this->generateKodeAtk();
            $atk = Atk::create($validated);

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
        $deletedStockTransactions = $this->deletedStockTransactions($atk);

        return view('atk.detail', compact('title', 'atk', 'deletedStockTransactions'));
    }

    public function edit($id)
    {
        $title = 'ATK';
        $atk = Atk::find($id);
        if (!$atk) {
            return redirect('/atk')->with('error', 'Data ATK sudah tidak tersedia.');
        }

        return view('atk.edit', compact('title', 'atk'));
    }

    public function update(Request $request, $id)
    {
        $atk = Atk::findOrFail($id);
        $validated = $this->validatedAtkData($request, $atk);
        $atk->update($validated);
        $this->qrService->ensure($atk, true);

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
        $atk->delete();

        return redirect('/atk')->with('success', 'Data Berhasil Dihapus');
    }

    public function stockIn(Request $request, $id)
    {
        $atk = Atk::findOrFail($id);
        $validated = $request->validate([
            'tanggal_transaksi' => 'required|date',
            'jumlah' => 'required|numeric|min:0.01',
            'sumber_barang' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
        ]);

        $this->recordStockTransaction($atk, 'masuk', $validated);

        return redirect('/atk/' . $atk->id . '/detail')->with('success', 'Stok masuk ATK berhasil disimpan');
    }

    public function stockOut(Request $request, $id)
    {
        $atk = Atk::findOrFail($id);
        $validated = $request->validate([
            'tanggal_transaksi' => 'required|date',
            'jumlah' => 'required|numeric|min:0.01',
            'penerima_barang' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
        ]);

        $this->recordStockTransaction($atk, 'keluar', $validated);

        return redirect('/atk/' . $atk->id . '/detail')->with('success', 'Stok keluar ATK berhasil disimpan');
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

    private function generateKodeAtk(): string
    {
        $counter = $this->lockedCounter('ATK', 'ATK');
        $nextCounter = (int) $counter->counter + 1;
        $counter->update(['counter' => $nextCounter]);

        return $this->formatCounterCode($counter->text ?: 'ATK', $nextCounter);
    }

    private function previewKodeAtk(): string
    {
        $counter = Counter::firstOrCreate(
            ['name' => 'ATK'],
            ['text' => 'ATK', 'counter' => 0]
        );

        return $this->formatCounterCode($counter->text ?: 'ATK', (int) $counter->counter + 1);
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
        ]);

        $validated['stok'] = round((float) $validated['stok'], 2);
        $validated['active'] = $request->boolean('active') ? 1 : 0;
        if ($atk) {
            $validated['kode_atk'] = $atk->kode_atk;
        } else {
            unset($validated['kode_atk']);
        }
        unset($validated['foto_barang']);

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
            $lockedAtk = Atk::whereKey($atk->id)->lockForUpdate()->firstOrFail();
            $stockBefore = $this->currentStock($lockedAtk);
            $quantity = round((float) $data['jumlah'], 2);
            $stockAfter = $type === 'masuk'
                ? $stockBefore + $quantity
                : $stockBefore - $quantity;

            if ($type === 'keluar' && $stockAfter < -0.000001) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Jumlah keluar tidak boleh melebihi stok tersedia.',
                ]);
            }

            $stockAfter = round(max(0, $stockAfter), 2);
            $lockedAtk->update([
                'stok' => $stockAfter,
            ]);

            return AtkStockTransaction::create([
                'atk_id' => $lockedAtk->id,
                'jenis_transaksi' => $type,
                'jumlah' => $quantity,
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

    private function currentStock(Atk $atk): float
    {
        return round(max(0, (float) ($atk->stok ?? 0)), 2);
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
