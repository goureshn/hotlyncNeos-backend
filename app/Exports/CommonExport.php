<?php

namespace App\Exports;

use App\Models\User;
// use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithDrawings;

class CommonExport implements FromView, WithTitle, ShouldAutoSize, WithStyles, WithEvents, WithDrawings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $view;
    protected $data;
    protected $title;
    protected $style;

    function __construct($view, $data, $title, $style = []) {
        $this->view = $view;
        $this->data = $data;
        $this->title = $title ?? "report";
        $this->style = $style ?? [];
    }

    public function view(): View
    {
        return view($this->view, ['data' => $this->data]);
    }

    public function title(): string
    {
        return $this->title;
    }

    public function styles(Worksheet $sheet)
    {
        return $this->style;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $heigth_arr = $this->data["height"] ?? [];

                foreach ($heigth_arr as $key => $value) {
                    $event->sheet->getDelegate()->getRowDimension($key)->setRowHeight($value);
                }
                // $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(50);
     
            },
        ];
    }

    public function drawings()
    {
        return $this->data["logo"] ?? [];
    }
}
