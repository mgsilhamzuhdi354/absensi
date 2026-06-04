<?php

namespace App\Exports;

use App\Models\User;
use App\Services\AttendanceRecapService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class RekapExport implements FromQuery, WithColumnFormatting, WithMapping, WithHeadings,ShouldAutoSize,WithStyles
{
    use Exportable;

    private array $filters;
    private ?array $summaries = null;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function styles(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        //BORDER
        $sheet->getStyle("A1:$highestColumn" . $highestRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // HEADER
        $sheet->getStyle("A1:" . $highestColumn . "1")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // WRAP TEXT
        $sheet->getStyle("A1:$highestColumn" . $highestRow)->getAlignment()->setWrapText(true);

        // ALIGNMENT TEXT
        $sheet->getStyle("A1:$highestColumn" . $highestRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

        //BOLD FIRST ROW
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],
        ];
    }

    public function headings(): array
    {
        return [
            'Nama',
            'Total Cuti',
            'Total Izin Masuk',
            'Total Sakit',
            'Total Izin Telat',
            'Total Izin Pulang Cepat',
            'Total Hadir',
            'Total Dinas Luar',
            'Total Alfa',
            'Total Libur',
            'Total Telat',
            'Total Pulang Cepat',
            'Total Lembur',
            'Persentase Kehadiran',
        ];
    }

    public function map($model): array
    {
        $summary = $this->summaryFor($model->id);

        return [
            $model->name,
            $summary['cuti'] . ' x',
            $summary['izin_masuk'] . ' x',
            $summary['sakit'] . ' x',
            $summary['izin_telat'] . ' x',
            $summary['izin_pulang_cepat'] . ' x',
            $summary['total_hadir'] . ' x',
            $summary['total_dinas_luar'] . ' x',
            $summary['total_alfa'] . ' x',
            $summary['libur'] . ' x',
            $summary['telat_duration']['label'] . "\n" . $summary['jumlah_telat'] . " x",
            $summary['pulang_cepat_duration']['label'] . "\n" . $summary['jumlah_pulang_cepat'] . " x",
            $summary['lembur_duration']['label'],
            number_format($summary['persentase_kehadiran'], 1) . ' %',
        ];
    }

    public function columnFormats(): array
    {
        return [

        ];
    }

    public function query()
    {
        return User::orderBy('name', 'ASC');
    }

    private function summaryFor(int $userId): array
    {
        if ($this->summaries === null) {
            $tanggalMulai = $this->filters['mulai'] ?? request()->input('mulai');
            $tanggalAkhir = $this->filters['akhir'] ?? request()->input('akhir');
            $this->summaries = app(AttendanceRecapService::class)
                ->summariesForUsers(User::pluck('id'), $tanggalMulai, $tanggalAkhir);
        }

        $tanggalMulai = $this->filters['mulai'] ?? request()->input('mulai');
        $tanggalAkhir = $this->filters['akhir'] ?? request()->input('akhir');

        return $this->summaries[$userId] ?? app(AttendanceRecapService::class)->summaryForUser($userId, $tanggalMulai, $tanggalAkhir);
    }
}
