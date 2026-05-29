<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\AbsenSheet;
use App\Exports\Sheets\DinasLuarSheet;

class AbsenExport implements WithMultipleSheets
{
    use Exportable;

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            new AbsenSheet($this->filters),
            new DinasLuarSheet(),
        ];
    }
}
