<?php
// borrow_form.php
// Form mượn sách (đơn giản). JS sẽ chuẩn hóa ngày trước khi gửi.
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Phiếu mượn</title>
</head>
<body>
<h2>Phiếu mượn sách</h2>

<form id="borrowForm" method="post" action="borrow_process.php">
    <label>Mã thành viên <input name="member" required></label><br>
    <label>Mã sách <input name="book" required></label><br>
    <label>Số lượng <input name="qty" type="number" min="1" value="1" required></label><br>
    <label>Ngày mượn <input id="borrowDate" type="date" name="date" required></label><br>
    <label>Số ngày <input name="days" type="number" min="1" max="30" value="7" required></label><br>
    <button type="submit">Mượn</button>
</form>

<script>
// Ensure the date input sends YYYY-MM-DD (some browsers/locales can differ)
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('borrowDate');
    // default to today
    const today = new Date();
    dateInput.value = today.toISOString().split('T')[0];

    document.getElementById('borrowForm').addEventListener('submit', function (e) {
        // Normalise date input (if user typed dd/mm/yyyy, try to reformat)
        const val = dateInput.value.trim();
        if (!val) return; // required will handle

        // If contains slash (dd/mm/yyyy), convert to YYYY-MM-DD
        if (val.indexOf('/') !== -1) {
            const parts = val.split('/');
            if (parts.length === 3) {
                // assume dd/mm/yyyy
                const d = parts[0].padStart(2, '0');
                const m = parts[1].padStart(2, '0');
                const y = parts[2];
                dateInput.value = `${y}-${m}-${d}`;
            }
        } else if (val.indexOf('-') !== -1) {
            // already likely YYYY-MM-DD or other; if it's DD-MM-YYYY we can try detect
            const parts = val.split('-');
            if (parts[0].length === 2 && parts[2].length === 4) {
                // dd-mm-yyyy => convert
                const d = parts[0].padStart(2, '0');
                const m = parts[1].padStart(2, '0');
                const y = parts[2];
                dateInput.value = `${y}-${m}-${d}`;
            }
        }
        // let form submit normally
    });
});
</script>
</body>
</html>