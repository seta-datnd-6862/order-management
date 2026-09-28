<?php

namespace App\Services;

use App\Models\OrderItem;

/**
 * Sinh prompt để người dùng copy sang AI (ChatGPT / Claude) cùng với take note,
 * nhận lại JSON đúng schema mà màn hình "Thêm hàng loạt" import được.
 */
class CampaignPromptBuilder
{
    public static function prompt(): string
    {
        $template = <<<'TEXT'
Bạn là trợ lý bóc tách đơn hàng cho một shop quần áo trẻ em. Đọc take note viết tay bên dưới (tiếng Việt, viết tắt nhiều) và trả về DUY NHẤT một khối JSON đúng schema. Không giải thích, không bọc trong dấu ``` .

## Cấu trúc take note
- Dòng dạng `- [ ] 7/9/26` hoặc `- [x] 11/9/26` là TIÊU ĐỀ NGÀY. Mọi đơn bên dưới lấy ngày đó làm ngày đặt mặc định, cho tới tiêu đề ngày tiếp theo.
- Mỗi dòng còn lại bắt đầu bằng tên khách là MỘT ĐƠN HÀNG.
- Dòng chỉ có ký hiệu (`✅✅✅`, `-`, dòng trống) thì bỏ qua, không tạo đơn.
- Phần sau dấu `:` là danh sách sản phẩm, ngăn cách bởi `,` hoặc `.`.

## Quy tắc bóc tách
1. NGÀY: ngày trong ngoặc sau tên khách (`(ord 7/9/26)`, `( 8/7/26)`) là ngày đặt của đơn đó và ưu tiên hơn tiêu đề ngày. Gốc là dd/mm/yy → xuất `YYYY-MM-DD` (yy hiểu là 20yy). Không suy ra được thì `null`.
2. SỐ LƯỢNG: số đứng ngay trước tên sản phẩm. `2 sét áo xanh` → 2. `1 đôi dép` → 1. Không có số → 1.
3. SIZE: số đứng CUỐI cụm sản phẩm hầu như luôn là size, KHÔNG phải giá. `áo len kẻ be 150` → size "150". Giày/dép dùng 20-32, quần áo dùng 73-180, hoặc size chữ (S, M, L, XL...). Xuất `size` NGUYÊN VĂN như trong note, không tự đổi số. Không có size → `null` và thêm vào `warnings`.
4. GIÁ (`price`): CHỈ điền khi note ghi rõ đơn vị tiền (`199k`, `120 nghìn`, `250.000đ`). Xuất ra VNĐ đầy đủ: `199k` → `199000`. Không có đơn vị tiền → `null` (hệ thống sẽ lấy giá mặc định của sản phẩm). TUYỆT ĐỐI không coi số size là giá.
5. CỌC / GIẢM GIÁ: `(cọc 100)` → `deposit_amount: 100000`. Giảm giá ghi vào `discount_amount`. Không có → `0`.
6. TÊN SẢN PHẨM (`product_name`): bỏ số lượng và size, giữ mô tả để tìm kiếm (loại + màu + chi tiết). `1 sét áo len xám bèo + quần 100` → `sét áo len xám bèo + quần`. GIỮ NGUYÊN từ viết tắt của shop (sét, cdg, ct, cv, NB, t&Q, gile...) vì tên sản phẩm trong hệ thống cũng viết như vậy.
7. `sét` / `set` là MỘT sản phẩm, không tách thành nhiều item dù mô tả nhiều bộ phận.
8. `is_marked_done`: `true` nếu dòng của khách đó có 👌 hoặc ✅.
9. `warnings`: ghi lại mọi chỗ cần người kiểm tra: `chưa đặt`, `thiếu Q`, `🚨 ghi note nhầm size`, thiếu size, thiếu số lượng, cụm chữ không hiểu...
10. `customer_note`: thông tin phụ về khách trong ngoặc mà không phải ngày (`fb trang`, `khách cũ`, `13kg`, đơn cũ đã đặt...).
11. `raw_line`: nguyên văn cả dòng gốc. `raw_text`: nguyên văn cụm sản phẩm. Dùng để đối chiếu, không được bỏ trống.
12. KHÔNG bỏ sót đơn nào, KHÔNG bịa thêm đơn hay sản phẩm. Không chắc thì vẫn xuất ra và ghi lý do vào `warnings`.
13. `name`: đặt tên đợt nhập theo khoảng ngày trong note, ví dụ `Đơn 7/9 - 12/9/26`.

Size hệ thống đang dùng (để nhận biết đâu là size): {SIZES}

## Schema JSON
{
  "version": 1,
  "name": "string",
  "orders": [
    {
      "order_date": "YYYY-MM-DD hoặc null",
      "customer_name": "string",
      "customer_note": "string hoặc null",
      "is_marked_done": true,
      "deposit_amount": 0,
      "discount_amount": 0,
      "note": "string hoặc null",
      "warnings": ["string"],
      "raw_line": "string",
      "items": [
        {
          "product_name": "string",
          "size": "string hoặc null",
          "quantity": 1,
          "price": null,
          "note": "string hoặc null",
          "raw_text": "string"
        }
      ]
    }
  ]
}

## Ví dụ
Take note:
- [ ] 7/9/26
- ánh ngọc ord 7/9/26: 1 sét quần loe size 150 áo len kẻ be 150👌
- Huệ (ord 18/12dạ vàng 80) :1 sét 3 ct dạ xám 199k 90 👌

JSON:
{
  "version": 1,
  "name": "Đơn 7/9/26",
  "orders": [
    {
      "order_date": "2026-09-07",
      "customer_name": "Ánh Ngọc",
      "customer_note": null,
      "is_marked_done": true,
      "deposit_amount": 0,
      "discount_amount": 0,
      "note": null,
      "warnings": [],
      "raw_line": "- ánh ngọc ord 7/9/26: 1 sét quần loe size 150 áo len kẻ be 150👌",
      "items": [
        {
          "product_name": "sét quần loe + áo len kẻ be",
          "size": "150",
          "quantity": 1,
          "price": null,
          "note": null,
          "raw_text": "1 sét quần loe size 150 áo len kẻ be 150"
        }
      ]
    },
    {
      "order_date": "2026-09-07",
      "customer_name": "Huệ",
      "customer_note": "ord 18/12 dạ vàng 80",
      "is_marked_done": true,
      "deposit_amount": 0,
      "discount_amount": 0,
      "note": null,
      "warnings": [],
      "raw_line": "- Huệ (ord 18/12dạ vàng 80) :1 sét 3 ct dạ xám 199k 90 👌",
      "items": [
        {
          "product_name": "sét 3 ct dạ xám",
          "size": "90",
          "quantity": 1,
          "price": 199000,
          "note": null,
          "raw_text": "1 sét 3 ct dạ xám 199k 90"
        }
      ]
    }
  ]
}

## Take note cần bóc tách
<<< DÁN TAKE NOTE VÀO ĐÂY >>>
TEXT;

        return strtr($template, [
            '{SIZES}' => implode(', ', OrderItem::getSizes()),
        ]);
    }
}
