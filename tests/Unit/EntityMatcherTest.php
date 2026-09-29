<?php

namespace Tests\Unit;

use App\Services\EntityMatcher;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class EntityMatcherTest extends TestCase
{
    private EntityMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new EntityMatcher();
    }

    private function candidates(array $names): Collection
    {
        return collect($names)->map(fn ($name, $i) => (object) ['id' => $i + 1, 'name' => $name]);
    }

    public function test_bo_dau_va_ky_tu_dac_biet(): void
    {
        $this->assertSame('set ao len xam beo', $this->matcher->normalize('Sét áo len xám bèo!'));
        $this->assertSame('quan bo 01', $this->matcher->normalize('  Quần   bò  (01) '));
    }

    public function test_khop_khach_du_ten_trong_he_thong_co_hau_to_ord(): void
    {
        $result = $this->matcher->match('Ánh Ngọc', $this->candidates([
            'Ánh Ngọc (ord 14/12)',
            'Ngọc Ánh (ord T11)',
        ]));

        $this->assertSame(1, $result['id']);
        $this->assertSame('exact', $result['match']);
    }

    public function test_khong_tu_chon_khi_nhieu_khach_cung_ten(): void
    {
        $result = $this->matcher->match('Linh', $this->candidates([
            'Linh (mẹ Gấu)',
            'Linh (7/3/26)',
            'Linh(ord 8/7/26)',
        ]));

        $this->assertNull($result['id']);
        $this->assertSame('low', $result['match']);
        $this->assertCount(3, $result['suggestions']);
    }

    public function test_khong_tu_chon_khi_diem_hai_ung_vien_dau_qua_sat_nhau(): void
    {
        $result = $this->matcher->match('Huyền', $this->candidates([
            'Huyền Huyền (ord T11)',
            'Huyền dư (Fb trang)',
            'Thu Huyền (Ord 7/12)',
        ]));

        $this->assertNull($result['id']);
        $this->assertSame('low', $result['match']);
    }

    public function test_tu_chon_khi_chi_dao_thu_tu_tu(): void
    {
        $result = $this->matcher->match('quần bi nâu', $this->candidates([
            'Quần nâu bi',
            'Áo len xám',
        ]));

        $this->assertSame(1, $result['id']);
        $this->assertSame('high', $result['match']);
    }

    public function test_khong_tu_chon_khi_ten_trong_note_co_them_chi_tiet(): void
    {
        // "bèo" là chi tiết khác biệt → phải để người dùng xác nhận, chỉ gợi ý.
        $result = $this->matcher->match('áo len xám bèo', $this->candidates([
            'Áo len xám',
        ]));

        $this->assertNull($result['id']);
        $this->assertSame('low', $result['match']);
        $this->assertSame('Áo len xám', $result['suggestions'][0]['name']);
    }

    public function test_khong_goi_y_san_pham_khong_lien_quan(): void
    {
        $result = $this->matcher->match('hoa tulip', $this->candidates([
            'Dép hoa cúc',
            'Quần bò ống loe',
        ]));

        $this->assertNull($result['id']);
        $this->assertSame('none', $result['match']);
        $this->assertSame([], $result['suggestions']);
    }

    public function test_chuan_hoa_size_theo_whitelist(): void
    {
        $this->assertSame('90', $this->matcher->normalizeSize(' size 90 '));
        $this->assertSame('XXL', $this->matcher->normalizeSize('2xl'));
        $this->assertSame('L', $this->matcher->normalizeSize('l'));
        // Size giày nằm trong khoảng 20-43
        $this->assertSame('32', $this->matcher->normalizeSize('32'));
        $this->assertSame('43', $this->matcher->normalizeSize('size 43'));
        // Ngoài khoảng thì để trống cho người dùng chọn lại
        $this->assertNull($this->matcher->normalizeSize('44'));
        $this->assertNull($this->matcher->normalizeSize('FREE'));
        $this->assertNull($this->matcher->normalizeSize(''));
        $this->assertNull($this->matcher->normalizeSize(null));
    }
}
