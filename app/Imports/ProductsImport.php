<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;

class ProductsImport implements ToCollection, WithHeadingRow, SkipsOnFailure
{
    use SkipsFailures;

    public int $importedCount = 0;
    public int $updatedCount = 0;
    public array $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 vì có header row và 0-indexed

            // Bỏ qua dòng trống
            if (empty(trim($row['ten_san_pham'] ?? ''))) {
                continue;
            }

            // Validate từng dòng thủ công
            $validator = Validator::make($row->toArray(), [
                'ten_san_pham'    => 'required|string|max:255',
                'gia_mac_dinh_d'  => 'nullable|numeric|min:0',
                'anh_url'         => 'nullable|url|max:2048',
                'ghi_chu'         => 'nullable|string|max:1000',
            ], [
                'ten_san_pham.required' => "Dòng $rowNumber: Tên sản phẩm là bắt buộc",
                'gia_mac_dinh_d.numeric' => "Dòng $rowNumber: Giá phải là số",
                'anh_url.url'           => "Dòng $rowNumber: Đường dẫn ảnh không hợp lệ",
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->errors[] = $error;
                }
                continue;
            }

            // Xác định update hay insert dựa vào mã sản phẩm
            $productId = null;
            $maCode = trim($row['ma_san_pham'] ?? '');
            if ($maCode && preg_match('/^KBC0*(\d+)$/', $maCode, $matches)) {
                $productId = (int) $matches[1];
            }

            // Xử lý ảnh từ URL
            $imagePath = null;
            $imageUrl = trim($row['anh_url'] ?? '');

            if ($imageUrl) {
                try {
                    $imageContents = @file_get_contents($imageUrl);
                    if ($imageContents !== false) {
                        $extension = $this->getExtensionFromUrl($imageUrl);
                        $fileName = 'products/' . uniqid() . '.' . $extension;
                        Storage::disk('public')->put($fileName, $imageContents);
                        $imagePath = $fileName;
                    } else {
                        $this->errors[] = "Dòng $rowNumber: Không thể tải ảnh từ URL '$imageUrl'";
                    }
                } catch (\Exception $e) {
                    $this->errors[] = "Dòng $rowNumber: Lỗi tải ảnh - " . $e->getMessage();
                }
            }

            $data = [
                'name'          => trim($row['ten_san_pham']),
                'default_price' => is_numeric($row['gia_mac_dinh_d'] ?? null)
                    ? (float) $row['gia_mac_dinh_d']
                    : null,
                'note'          => trim($row['ghi_chu'] ?? '') ?: null,
            ];

            if ($imagePath !== null) {
                $data['image'] = $imagePath;
            }

            // Update nếu tồn tại, ngược lại insert
            if ($productId) {
                $product = Product::find($productId);
                if ($product) {
                    // Xóa ảnh cũ nếu có ảnh mới
                    if ($imagePath && $product->image) {
                        Storage::disk('public')->delete($product->image);
                    }
                    $product->update($data);
                    $this->updatedCount++;
                    continue;
                }
            }

            Product::create($data);
            $this->importedCount++;
        }
    }

    private function getExtensionFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        return in_array($ext, $allowed) ? ($ext === 'jpeg' ? 'jpg' : $ext) : 'jpg';
    }
}
