<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    public function query()
    {
        return Product::query()->orderBy('id');
    }

    public function title(): string
    {
        return 'Sản phẩm';
    }

    public function headings(): array
    {
        return [
            'Mã sản phẩm',
            'Tên sản phẩm',
            'Giá mặc định (đ)',
            'Ảnh (URL)',
            'Ghi chú',
            'Ngày tạo',
        ];
    }

    public function map($product): array
    {
        return [
            $product->product_code,
            $product->name,
            $product->default_price,
            $product->image_url ?? '',
            $product->note ?? '',
            $product->created_at->format('d/m/Y H:i'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 35,
            'C' => 20,
            'D' => 60,
            'E' => 40,
            'F' => 18,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header row styling
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'name' => 'Arial',
                'size' => 11,
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '4F46E5'], // indigo-600
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
        ]);

        // Data rows
        $sheet->getStyle('A2:F' . ($sheet->getHighestRow()))->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10],
            'alignment' => ['vertical' => 'center'],
        ]);

        // Currency column right-aligned
        $sheet->getStyle('C2:C' . $sheet->getHighestRow())
            ->getNumberFormat()
            ->setFormatCode('#,##0');

        $sheet->getStyle('C2:C' . $sheet->getHighestRow())
            ->getAlignment()->setHorizontal('right');

        // Freeze header row
        $sheet->freezePane('A2');

        // Row height for header
        $sheet->getRowDimension(1)->setRowHeight(22);

        return [];
    }
}
