<?php

namespace App\Services;

use App\Models\OrderItem;
use Illuminate\Support\Collection;

/**
 * Khớp tên khách hàng / sản phẩm đọc từ take note với dữ liệu đã có trong hệ thống.
 *
 * Điểm khớp được tính trên tên đã bỏ dấu, kết hợp 3 tiêu chí: độ giống chuỗi,
 * độ trùng từ (Dice) và độ chứa từ. Chỉ khớp chắc chắn mới được chọn sẵn,
 * các trường hợp còn lại chỉ đưa ra gợi ý để người dùng bấm chọn.
 */
class EntityMatcher
{
    /** Điểm tối thiểu để tự động chọn sẵn. */
    public const AUTO_SELECT_SCORE = 86;

    /** Điểm tối thiểu để đưa vào danh sách gợi ý. */
    public const SUGGEST_SCORE = 55;

    /**
     * Khoảng cách điểm tối thiểu so với ứng viên thứ hai mới dám chọn sẵn.
     * Nhiều khách trùng tên gọi ("Huyền", "Linh"...) chỉ chênh nhau vài điểm,
     * chọn sẵn trong trường hợp đó gần như chắc chắn gán sai người.
     */
    public const AUTO_SELECT_MARGIN = 10;

    public const MAX_SUGGESTIONS = 5;

    private const VIETNAMESE_MAP = [
        'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
        'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
        'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
        'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
        'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
        'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
        'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
        'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
        'đ' => 'd',
    ];

    /**
     * Bỏ dấu, bỏ ký tự đặc biệt, gộp khoảng trắng.
     */
    public function normalize(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value), 'UTF-8');
        $value = strtr($value, self::VIETNAMESE_MAP);
        $value = preg_replace('/[^a-z0-9\s]+/u', ' ', $value);

        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * Bỏ phần trong ngoặc khỏi tên. Khách hàng trong hệ thống thường được lưu kèm
     * hậu tố kiểu "Khánh Hòa (ord 14/12)" hoặc "Thảo Vân (Fb Trang)", trong khi
     * take note chỉ ghi tên, nên phải so khớp cả phần tên gốc.
     */
    public function stripAnnotations(?string $value): string
    {
        $value = preg_replace('/\([^)]*\)?/u', ' ', (string) $value);
        $value = preg_replace('/\b(ord|order|fb)\b.*$/iu', ' ', $value);

        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * Các cách viết của một tên dùng để so khớp: nguyên bản và bản đã bỏ ngoặc.
     *
     * @return array<int, string>
     */
    private function variants(?string $value): array
    {
        $full = $this->normalize($value);
        $stripped = $this->normalize($this->stripAnnotations($value));

        return array_values(array_unique(array_filter([$full, $stripped])));
    }

    /**
     * Điểm giống nhau giữa 2 tên, thang 0-100. Lấy điểm cao nhất giữa các cách viết.
     */
    public function score(string $needle, string $haystack): float
    {
        $best = 0.0;

        foreach ($this->variants($needle) as $a) {
            foreach ($this->variants($haystack) as $b) {
                $best = max($best, $this->scoreNormalized($a, $b));
            }
        }

        return $best;
    }

    /**
     * Hai tên có trùng khít nhau ở một cách viết nào đó không.
     */
    public function isExact(string $needle, string $haystack): bool
    {
        return array_intersect($this->variants($needle), $this->variants($haystack)) !== [];
    }

    private function scoreNormalized(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0;
        }

        if ($a === $b) {
            return 100;
        }

        similar_text($a, $b, $similarPercent);

        $tokensA = array_unique(explode(' ', $a));
        $tokensB = array_unique(explode(' ', $b));
        $common = count(array_intersect($tokensA, $tokensB));

        $dice = (count($tokensA) + count($tokensB)) > 0
            ? (2 * $common) / (count($tokensA) + count($tokensB))
            : 0;

        // Độ phủ tính theo tên đọc từ note: tên trong note có thêm từ mô tả mà sản phẩm
        // trong hệ thống không có ("áo len xám bèo" vs "Áo len xám") thì phải bị trừ điểm,
        // vì thêm chi tiết thường nghĩa là một mẫu khác.
        $coverage = count($tokensA) > 0 ? $common / count($tokensA) : 0;

        return round(0.45 * $similarPercent + 0.35 * ($dice * 100) + 0.20 * ($coverage * 100), 1);
    }

    /**
     * Tìm bản ghi khớp nhất trong $candidates.
     *
     * @param  Collection  $candidates  Các model có thuộc tính id + name
     * @return array{id: int|null, match: string, suggestions: array<int, array{id: int, name: string, score: float}>}
     */
    public function match(?string $rawName, Collection $candidates): array
    {
        $result = ['id' => null, 'match' => 'none', 'suggestions' => []];

        if (!$rawName || trim($rawName) === '' || $candidates->isEmpty()) {
            return $result;
        }

        $scored = $candidates
            ->map(fn ($candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'score' => $this->score($rawName, $candidate->name),
                'exact' => $this->isExact($rawName, $candidate->name),
            ])
            ->filter(fn ($row) => $row['score'] >= self::SUGGEST_SCORE)
            ->sortByDesc('score')
            ->values();

        if ($scored->isEmpty()) {
            return $result;
        }

        $best = $scored->first();

        $result['suggestions'] = $scored
            ->take(self::MAX_SUGGESTIONS)
            ->map(fn ($row) => [
                'id' => $row['id'],
                'name' => $row['name'],
                'score' => $row['score'],
            ])
            ->all();

        $result['match'] = 'low';

        $exactMatches = $scored->where('exact', true);

        // Trùng khít và chỉ có một bản ghi trùng khít → chọn sẵn.
        if ($exactMatches->count() === 1) {
            $result['id'] = $exactMatches->first()['id'];
            $result['match'] = 'exact';

            return $result;
        }

        // Nhiều bản ghi trùng khít (khách trùng tên) → để người dùng chọn.
        if ($exactMatches->count() > 1) {
            return $result;
        }

        $runnerUp = $scored->get(1);
        $margin = $runnerUp === null
            ? PHP_INT_MAX
            : $best['score'] - $runnerUp['score'];

        if ($best['score'] >= self::AUTO_SELECT_SCORE && $margin >= self::AUTO_SELECT_MARGIN) {
            $result['id'] = $best['id'];
            $result['match'] = 'high';
        }

        return $result;
    }

    /**
     * Chuẩn hoá size theo whitelist của OrderItem. Không khớp thì trả null.
     */
    public function normalizeSize(?string $rawSize): ?string
    {
        if ($rawSize === null || trim($rawSize) === '') {
            return null;
        }

        $sizes = OrderItem::getSizes();

        $value = mb_strtoupper(trim($rawSize), 'UTF-8');
        $value = preg_replace('/\b(SIZE|SZ|CỠ|CO)\b/u', '', $value);
        $value = preg_replace('/[^A-Z0-9]+/u', '', $value);

        if ($value === '') {
            return null;
        }

        $aliases = [
            '2XL' => 'XXL',
            '3XL' => 'XXXL',
            '1XL' => 'XL',
        ];

        $value = $aliases[$value] ?? $value;

        // So sánh không phân biệt chữ hoa/thường với whitelist
        foreach ($sizes as $size) {
            if (mb_strtoupper($size, 'UTF-8') === $value) {
                return $size;
            }
        }

        return null;
    }
}
