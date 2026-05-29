<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Models\MappingShift;

class AbsenSheet implements FromQuery, WithColumnFormatting, WithMapping, WithHeadings, ShouldAutoSize, WithStyles, WithEvents, WithTitle
{
    use Exportable;

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function title(): string
    {
        return 'Absensi';
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
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function headings(): array
    {
        return [
            'Nama Pegawai',
            'Shift',
            'Tanggal',
            'Jam Masuk',
            'Telat',
            'Keterangan Masuk',
            'Jam Pulang',
            'Pulang Cepat',
            'Keterangan Pulang',
            'Status Absen',
        ];
    }

    public function map($model): array
    {
        $telat = $model->telat;
        $jam = floor($telat / (60 * 60));
        $menit = $telat - ($jam * (60 * 60));
        $menit2 = floor($menit / 60);
        $detik = $telat % 60;
        if ($jam <= 0 && $menit2 <= 0) {
            $late = '-';
        } else {
            $late = $jam . ' Jam ' . $menit2 . ' Menit ' . $detik . ' Detik';
        }

        $pulang_cepat = $model->pulang_cepat;
        $jam_pulang_cepat = floor($pulang_cepat / (60 * 60));
        $menit_pulang_cepat = $pulang_cepat - ($jam_pulang_cepat * (60 * 60));
        $menit_pulang_cepat2 = floor($menit_pulang_cepat / 60);
        $detik_pulang_cepat = $pulang_cepat % 60;

        if ($jam_pulang_cepat <= 0 && $menit_pulang_cepat2 <= 0) {
            $quick_return = '-';
        } else {
            $quick_return = $jam_pulang_cepat . ' Jam ' . $menit_pulang_cepat2 . ' Menit ' . $detik_pulang_cepat . ' Detik';
        }

        if ($model->Shift) {
            $shift_name = $model->Shift->nama_shift . ' ' . $model->Shift->jam_masuk . ' - ' . $model->Shift->jam_keluar;
        } else {
            $shift_name = '-';
        }

        return [
            $model->name,
            $shift_name,
            $model->tanggal ?? '-',
            $model->jam_absen ? $model->jam_absen : '-',
            $late,
            $model->keterangan_masuk,
            $model->jam_pulang ? $model->jam_pulang : '-',
            $quick_return,
            $model->keterangan_pulang,
            $model->status_absen ?? 'Tidak Masuk',
        ];
    }

    public function columnFormats(): array
    {
        return [];
    }

    public function query()
    {
        // Order by name first (grouped), then by date
        return MappingShift::dataAbsen()->reorder('users.name', 'ASC')->orderBy('tanggal', 'ASC');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Alternating colors per employee name group
                $currentName = '';
                $colorIndex = 0;
                $colors = ['92D050', 'FFFFFF']; // Green and White

                for ($row = 2; $row <= $highestRow; $row++) {
                    $name = $sheet->getCell('A' . $row)->getValue();
                    if ($name !== $currentName) {
                        $currentName = $name;
                        $colorIndex = ($colorIndex + 1) % 2;
                    }
                    $sheet->getStyle("A{$row}:{$highestColumn}{$row}")
                        ->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB($colors[$colorIndex]);
                }
            },
        ];
    }
}
