<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class CommonImportExcel implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $heading_row = 1;

    function __construct($heading_row = 1) {
        $this->heading_row = $heading_row;
    }

    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        //
    }

    public function headingRow(): int
    {
        return $this->heading_row;
    }
}
