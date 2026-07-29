# 04. Duyệt Đánh giá sản phẩm (Reviews)

> Đọc `00-phan-quyen.md` trước nếu chưa đọc.

---

## Vì sao cần duyệt?

Khi khách hàng gửi đánh giá (review) cho 1 sản phẩm, đánh giá đó **không tự động hiển thị công khai**, phải được nhân viên **duyệt (Approve)** trước.

## Nơi duyệt đánh giá

Vào mục **Reviews** trong menu (nhóm Catalog). Trên menu bên trái có **số đếm (badge)** hiển thị số lượng đánh giá **đang chờ duyệt**, dùng số này để biết có việc cần xử lý hay không.

## Các thao tác

| Thao tác | Ý nghĩa |
|---|---|
| **Approve (Duyệt)** | Đánh giá chuyển sang trạng thái đã duyệt (`is_approved = true`) → hiển thị công khai trên trang sản phẩm |
| **Reject (Từ chối)** | Đánh giá không được duyệt → khách hàng khác sẽ không thấy |
| **Duyệt hàng loạt (Bulk Approve)** | Tick chọn nhiều đánh giá cùng lúc rồi duyệt 1 lần, tiện khi có nhiều đánh giá tồn đọng |

## Lưu ý khi duyệt

- Đánh giá **chưa duyệt hoàn toàn không hiển thị** trên trang sản phẩm: khách truy cập bình thường sẽ không thấy sự tồn tại của đánh giá đó, kể cả điểm sao trung bình sẽ không tính đánh giá chưa duyệt.
- Nên kiểm tra nội dung đánh giá trước khi duyệt: tránh spam, nội dung phản cảm, hoặc đánh giá không liên quan đến sản phẩm.
- Việc duyệt/từ chối **có thể đổi lại** bất cứ lúc nào (không phải thao tác một chiều). Nếu duyệt nhầm, mở lại đánh giá và chuyển về trạng thái chưa duyệt.

---

*Tiếp theo: `05-thao-tac-mcp.md` nếu bạn có điều khiển AI/Claude thao tác qua MCP. Xem lại `00-phan-quyen.md` nếu cần tra cứu khái niệm chung.*
