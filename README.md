# Lab06 — Hệ thống thư viện & hóa đơn (PHP, file-based)

Bản này mô tả cấu trúc, cách chạy và các ghi chú kiểm tra cho bài tập Lab06 — một hệ thống minh họa quản lý thư viện (mượn/trả sách) và hóa đơn dùng lưu trữ bằng file (JSON / CSV), không dùng cơ sở dữ liệu.

## Tổng quan
Ứng dụng gồm nhiều bài (bai1..bai5). Mỗi bài là một chức năng độc lập, dùng các file trong thư mục `data/` để lưu trữ:
- members.csv — danh sách thành viên (CSV)
- books.json — danh sách sách (JSON)
- borrows.json — phiếu mượn (JSON)
- invoices.json — hóa đơn (JSON)

Mục tiêu:
- Thực hành xử lý form với PHP, đọc/ghi file JSON/CSV.
- Thực hiện validation server-side (và một số client-side) để tránh lỗi.
- Quản lý số lượng sách khi mượn / trả.

## Cấu trúc chính
- `bai1/` — Đăng ký và danh sách thành viên  
  Files: `register_member.php`, `member_result.php`  
  Dữ liệu: `data/members.csv`
- `bai2/` — Quản lý sách  
  Files: `add_book.php`, `list_books.php`  
  Dữ liệu: `data/books.json`
- `bai3/` — Mượn / trả sách  
  Files: `borrow_form.php`, `borrow_process.php`, `return_form.php`, `return_process.php`  
  Dữ liệu: `data/borrows.json`, cập nhật `data/books.json`
- `bai4/` — Tạo hóa đơn  
  File: `invoice_form.php`  
  Dữ liệu: `data/invoices.json`
- `bai5/` — Tìm kiếm sách  
  File: `search.php`

Dữ liệu mẫu đã kèm: `data/books.json`, `data/members.csv`, `data/borrows.json` (ví dụ).

## Yêu cầu & môi trường
- PHP 7.4+ (hoặc PHP 8).  
- Web server: Apache (XAMPP) hoặc tương đương.  
- Đảm bảo thư mục `data/` có quyền ghi cho PHP (file_put_contents sẽ cần quyền ghi).

## Cách chạy (Quick start)
1. Bật Apache/PHP (ví dụ: khởi động XAMPP).
2. Truy cập ứng dụng qua trình duyệt:
   - `http://localhost/lab06/bai1/register_member.php` — đăng ký thành viên
   - `http://localhost/lab06/bai2/add_book.php` — thêm sách
   - `http://localhost/lab06/bai2/list_books.php` — danh sách sách
   - `http://localhost/lab06/bai3/borrow_form.php` — mượn sách
   - `http://localhost/lab06/bai3/return_form.php` — trả sách
   - `http://localhost/lab06/bai4/invoice_form.php` — lập hóa đơn
   - `http://localhost/lab06/bai5/search.php` — tìm sách

## Luồng chính & kiểm tra nhanh

1. Mượn sách (bai3)
   - Form: mã thành viên, mã sách, số lượng, ngày mượn, số ngày mượn (1–30).
   - Validation:
     - Mã thành viên phải tồn tại trong `members.csv` (so khớp theo id / email / phone tùy cấu trúc).
     - Mã sách phải tồn tại trong `books.json`.
     - Số lượng >= 1 và không vượt quá `books.json` hiện có.
     - Ngày mượn được chuẩn hóa và lưu ở định dạng `YYYY-MM-DD`.
   - Nếu hợp lệ:
     - Thêm một mục vào `data/borrows.json`:
       - các trường: id (mã phiếu), member, book, qty, date (ngày mượn), days, due (hạn trả), status (Đang mượn).
     - Giảm số lượng sách tương ứng trong `data/books.json`.

2. Trả sách (bai3)
   - Form: nhập `Mã phiếu` (ưu tiên) hoặc `Mã TV + Mã sách`, và `Ngày trả`.
   - Validation:
     - Phiếu phải tồn tại và đang ở trạng thái `Đang mượn`.
     - Ngày trả hợp lệ và nằm trong khoảng [ngày mượn, hạn trả].
     - Hệ thống chấp nhận nhiều định dạng ngày đầu vào (server-side) và client cố gắng gửi `YYYY-MM-DD`.
   - Nếu hợp lệ:
     - Cập nhật trạng thái phiếu = `Đã trả`.
     - Tăng lại số lượng sách trong `data/books.json` theo `qty` trong phiếu.
     - Trả về JSON tóm tắt phiếu.

3. Hóa đơn (bai4)
   - Form nhập các dòng hàng, kiểm tra hợp lệ, lưu mảng vào `data/invoices.json`.

4. Tìm sách (bai5)
   - Hỗ trợ query params: `kw`, `category`, `year_from`, `year_to`, `price_from`, `price_to`.

## Định dạng ngày và lưu ý
- Mọi ngày lưu trong JSON đều chuẩn hóa về `YYYY-MM-DD`.
- Client JS cố gắng chuẩn hóa input ngày sang `YYYY-MM-DD` trước khi gửi để tránh lỗi do locale (ví dụ DD/MM/YYYY).
- Server chấp nhận một vài định dạng dễ gặp (YYYY-MM-DD, DD/MM/YYYY, dd-mm-yyyy, v.v.) nhưng tốt nhất là gửi `YYYY-MM-DD`.

## Xử lý lỗi & debug
- Nếu gặp warnings như "Trying to access array offset on value of type bool":
  - Kiểm tra file tồn tại trước khi json_decode.
  - Kiểm tra `json_decode()` trả về array trước khi dùng.
- Nếu gặp lỗi "Dữ liệu phiếu mượn không hợp lệ (ngày mượn)":
  - Kiểm tra bản ghi trong `data/borrows.json` — trường `date` phải ở dạng `YYYY-MM-DD` hoặc dạng mà server có thể parse.
  - Mở DevTools → Network → kiểm tra giá trị `date` được gửi từ form (Form Data / Request Payload).
- Quyền: đảm bảo user Apache/PHP có quyền đọc/ghi file trong `lab06/data/`.
- Để reset dữ liệu thử nghiệm, sao lưu hoặc khởi tạo lại các file JSON/CSV.

## Gợi ý kiểm thử (test cases)
- Mượn thành công:
  - Chọn member hợp lệ, sách có qty >= số lượng muốn mượn, days = 7 → kiểm tra `borrows.json` và `books.json`.
- Mượn lỗi:
  - Mã TV không tồn tại → server trả lỗi.
  - Số lượng > tồn kho → server trả lỗi.
- Trả thành công:
  - Dùng đúng mã phiếu hoặc (mã TV + mã sách), nhập ngày trong khoảng hợp lệ → trạng thái cập nhật `Đã trả`, `books.json` tăng qty.
- Trả lỗi:
  - Trả 2 lần cùng phiếu → server trả lỗi "Phiếu không hợp lệ hoặc đã trả".
  - Ngày trả trước ngày mượn hoặc sau hạn trả → server trả thông báo ngày không hợp lệ.

## Gợi ý cải tiến / mở rộng (nếu muốn)
- Thêm giao diện quản trị (admin) để xem, chỉnh sửa borrows.
- Thêm logging cho mọi thao tác (thêm file log).
- Thêm locking (file lock) khi ghi nhiều tác vụ đồng thời để tránh race condition.
- Chuyển dần sang DB (SQLite/MySQL) nếu cần khả năng mở rộng.


