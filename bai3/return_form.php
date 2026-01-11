<?php
// return_form.php
// Form trả sách — client will POST via fetch and try to normalize date to YYYY-MM-DD
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Trả sách</title>
</head>
<body>
<h2>Trả sách</h2>

<form id="returnForm" method="post" action="return_process.php">
    <p>Nhập <strong>Mã phiếu</strong> (ưu tiên) hoặc <strong>Mã TV + Mã sách</strong>:</p>
    <label>Mã phiếu <input name="id"></label><br>
    <p>Hoặc - Mã TV <input name="member"></p>
    <label>Mã sách <input name="book"></label><br>
    <label>Ngày trả <input id="returnDate" type="date" name="date"></label><br>
    <button type="submit">Trả sách</button>
</form>

<div id="returnMsg" role="status" aria-live="polite" style="margin-top:12px"></div>

<script>
const form = document.getElementById('returnForm');
const msg = document.getElementById('returnMsg');

function normalizeDateInputToISO(dateStr) {
    if (!dateStr) return '';
    // if already ISO-like YYYY-MM-DD
    if (/^\d{4}-\d{1,2}-\d{1,2}$/.test(dateStr)) return dateStr;
    // dd/mm/yyyy
    if (/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(dateStr)) {
        const parts = dateStr.split('/');
        const d = parts[0].padStart(2,'0');
        const m = parts[1].padStart(2,'0');
        const y = parts[2];
        return `${y}-${m}-${d}`;
    }
    // dd-mm-yyyy
    if (/^\d{1,2}-\d{1,2}-\d{4}$/.test(dateStr)) {
        const parts = dateStr.split('-');
        const d = parts[0].padStart(2,'0');
        const m = parts[1].padStart(2,'0');
        const y = parts[2];
        return `${y}-${m}-${d}`;
    }
    // fallback: try Date.parse
    const ts = Date.parse(dateStr);
    if (!isNaN(ts)) {
        const d = new Date(ts);
        return d.toISOString().split('T')[0];
    }
    return dateStr;
}

form.addEventListener('submit', async function(e){
    e.preventDefault();
    msg.style.color = 'black';
    msg.textContent = 'Đang xử lý...';

    const fd = new FormData(form);
    // normalize date
    const rawDate = fd.get('date') || '';
    const iso = normalizeDateInputToISO(rawDate);
    if (iso) fd.set('date', iso);

    try {
        const res = await fetch(form.action, { method: 'POST', body: fd });
        const ct = res.headers.get('content-type') || '';
        if (res.ok) {
            if (ct.includes('application/json')) {
                const data = await res.json();
                msg.style.color = 'green';
                msg.innerHTML = '<strong>' + (data.message || 'Trả sách thành công') + '</strong>' +
                    '<pre style="white-space:pre-wrap;">' + (JSON.stringify(data.borrow || {}, null, 2)) + '</pre>';
            } else {
                const text = await res.text();
                msg.style.color = 'green';
                msg.textContent = text || 'Trả sách thành công';
            }
        } else {
            const text = await res.text();
            msg.style.color = 'red';
            msg.textContent = text || ('Lỗi: ' + res.status);
        }
    } catch (err) {
        msg.style.color = 'red';
        msg.textContent = 'Lỗi kết nối: ' + err;
    }
});
</script>
</body>
</html>