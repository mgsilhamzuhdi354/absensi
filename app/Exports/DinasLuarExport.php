<?php

namespace App\Exports;

use App\Models\dinasLuar;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DinasLuarExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function styles(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow    = $sheet->getHighestRow();

        $sheet->getStyle("A1:$highestColumn$highestRow")
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A1:{$highestColumn}1")
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:$highestColumn$highestRow")
            ->getAlignment()->setWrapText(true);
        $sheet->getStyle("A1:$highestColumn$highestRow")
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

        return [1 => ['font' => ['bold' => true]]];
    }

    public function headings(): array
    {
        return [
            'Nama Pegawai',
            'Shift',
            'Tanggal',
            'Jam Masuk',
            'Jam Pulang',
            'Lokasi',
            'Status Absen',
            'Koordinat Masuk',
            'Koordinat Pulang',
        ];
    }

    public function map($model): array
    {
        $shift = $model->Shift
            ? $model->Shift->nama_shift . ' (' . $model->Shift->jam_masuk . ' - ' . $model->Shift->jam_keluar . ')'
            : '-';

        $koordinat_masuk  = ($model->lat_absen && $model->long_absen)
            ? $model->lat_absen . ', ' . $model->long_absen
            : '-';

        $koordinat_pulang = ($model->lat_pulang && $model->long_pulang)
            ? $model->lat_pulang . ', ' . $model->long_pulang
            : '-';

        return [
            $model->User->name ?? '-',
            $shift,
            $model->tanggal ?? '-',
            $model->jam_absen  ?? '-',
            $model->jam_pulang ?? '-',
            $model->lokasi     ?? '-',
            $model->status_absen ?? '-',
            $koordinat_masuk,
            $koordinat_pulang,
        ];
    }

    public function query()
    {
        $query = dinasLuar::with(['User', 'Shift'])
            ->join('users', 'users.id', '=', 'dinas_luars.user_id')
            ->orderBy('users.name', 'ASC')
            ->orderBy('dinas_luars.tanggal', 'ASC')
            ->select('dinas_luars.*');

        if (request('user_id')) {
            $query->where('dinas_luars.user_id', request('user_id'));
        }
        if (request('mulai') && request('akhir')) {
            $query->whereBetween('dinas_luars.tanggal', [request('mulai'), request('akhir')]);
        }

        return $query;
    }
}
