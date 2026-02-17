<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison; // <-- Aquí llamamos la nueva regla

// Le aplicamos la regla a la clase principal
class ResurtidoExport implements FromArray, WithHeadings, WithStrictNullComparison
{
    protected $datos;
    protected $cabeceras;

    public function __construct(array $datos, array $cabeceras)
    {
        $this->datos = $datos;
        $this->cabeceras = $cabeceras;
    }

    public function array(): array
    {
        return $this->datos;
    }

    public function headings(): array
    {
        return $this->cabeceras;
    }
}