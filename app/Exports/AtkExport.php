<?php

namespace App\Exports;

use App\Models\Atk;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AtkExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithStyles
{
    use Exportable;

    private $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function styles(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle("A1:$highestColumn" . $highestRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A1:" . $highestColumn . "1")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:$highestColumn" . $highestRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle("A1:$highestColumn" . $highestRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function headings(): array
    {
        return [
            'Kode ATK',
            'Nama ATK',
            'Kategori',
            'Stok',
            'Satuan',
            'Lokasi',
            'Status',
            'Keterangan',
            'Tanggal Dibuat',
            'Tanggal Update',
        ];
    }

    public function map($atk): array
    {
        return [
            $atk->kode_atk ?? '-',
            $atk->nama_atk ?? '-',
            $atk->kategori ?? '-',
            $atk->formatted_stock,
            $atk->satuan ?? '-',
            $atk->lokasi ?? '-',
            $atk->active == 1 ? 'Aktif' : 'Non-Aktif',
            $atk->keterangan ?? '-',
            $this->formatDateTime($atk->created_at),
            $this->formatDateTime($atk->updated_at),
        ];
    }

    public function query()
    {
        $search = $this->filters['search'] ?? null;
        $status = $this->filters['status'] ?? null;

        return Atk::when($search, function ($query) use ($search) {
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
            ->orderBy('id', 'DESC');
    }

    private function formatDateTime($value): string
    {
        if (!$value) {
            return '-';
        }

        Carbon::setLocale('id');

        return Carbon::parse($value)->translatedFormat('d F Y H:i');
    }
}
