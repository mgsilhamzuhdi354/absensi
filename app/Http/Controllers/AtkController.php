<?php

namespace App\Http\Controllers;

use App\Exports\AtkExport;
use App\Models\Atk;
use App\Models\Counter;
use App\Services\AtkQrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AtkController extends Controller
{
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
        $kode_atk = $this->generateKodeAtk();

        return view('atk.tambah', compact('title', 'kode_atk'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedAtkData($request);
        $atk = DB::transaction(function () use ($validated) {
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

        return view('atk.detail', compact('title', 'atk'));
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
        $validated = $this->validatedAtkData($request);
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
            Storage::disk('public')->delete($atk->qr_code_image);
        }
        $atk->delete();

        return redirect('/atk')->with('success', 'Data Berhasil Dihapus');
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
        $atk = $this->findAtkByQrInput($code);

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

        if (!$atk->qr_code_image || !Storage::disk('public')->exists($atk->qr_code_image)) {
            abort(404);
        }

        return Storage::disk('public')->download(
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
        $counter = Counter::firstOrCreate(
            ['name' => 'ATK'],
            ['text' => 'ATK', 'counter' => 0]
        );
        $counter->increment('counter');
        $counter->refresh();

        return $counter->text . '/' . str_pad($counter->counter, 6, '0', STR_PAD_LEFT);
    }

    private function validatedAtkData(Request $request): array
    {
        $validated = $request->validate([
            'kode_atk' => 'required|string|max:255',
            'nama_atk' => 'required|string|max:255',
            'kategori' => 'nullable|string|max:255',
            'stok' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
            'active' => 'nullable|boolean',
        ]);

        $validated['stok'] = round((float) $validated['stok'], 2);
        $validated['active'] = $request->boolean('active') ? 1 : 0;

        return $validated;
    }

    private function findAtkByQrInput($value)
    {
        $candidates = $this->extractAtkCandidates($value);
        if (empty($candidates)) {
            return null;
        }

        foreach ($candidates as $candidate) {
            if (preg_match('/^id:([0-9]+)$/', $candidate, $matches)) {
                $byId = Atk::find((int) $matches[1]);
                if ($byId) {
                    return $byId;
                }
            }

            $atk = Atk::where('qr_token', $candidate)
                ->orWhere('qr_code_value', $candidate)
                ->orWhere('kode_atk', $candidate)
                ->first();

            if ($atk) {
                return $atk;
            }
        }

        return null;
    }

    private function extractAtkCandidates($value): array
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

            if ($path && preg_match('#/atk/([0-9]+)/detail#i', $path, $matches)) {
                $candidates[] = 'id:' . $matches[1];
            }

            if ($path) {
                $segments = array_values(array_filter(explode('/', trim($path, '/'))));
                if (!empty($segments)) {
                    $candidates[] = trim(urldecode((string) end($segments)));
                }
            }

            if ($query) {
                parse_str($query, $params);
                foreach (['code', 'token', 'qr', 'atk', 'kode', 'kode_atk', 'id'] as $key) {
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

            if (preg_match('/^(?:ATK-QR:|QR:)\s*(.+)$/i', $candidate, $prefixed)) {
                $candidates[] = trim($prefixed[1]);
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

    private function safeFilename($value): string
    {
        return trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $value), '-') ?: 'atk';
    }
}
