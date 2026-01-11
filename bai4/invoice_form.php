<?php
session_start();

$errors = [];
$fieldErrors = [];
$customer = $_POST['customer'] ?? $_SESSION['invoice_customer'] ?? '';
$email = $_POST['email'] ?? $_SESSION['invoice_email'] ?? '';
$phone = $_POST['phone'] ?? $_SESSION['invoice_phone'] ?? '';
$discount = $_POST['discount'] ?? 0;
$vat = $_POST['vat'] ?? 0;
$pay = $_POST['pay'] ?? 'cash';
$names = $_POST['name'] ?? ['', '', ''];
$qtys = $_POST['qty'] ?? ['', '', ''];
$prices = $_POST['price'] ?? ['', '', ''];

$invoiceData = null;

function fmt($n){ return number_format($n) . ' VND'; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // validate customer
    if (trim($phone) === '') { $errors[] = 'Số điện thoại bắt buộc'; $fieldErrors['phone'] = 'Số điện thoại bắt buộc'; }

    // items
    $items = [];
    for ($i = 0; $i < 3; $i++) {
        $n = trim($names[$i] ?? '');
        $q = (int)($qtys[$i] ?? 0);
        $p = (float)($prices[$i] ?? 0);
        if ($n !== '' || $q !== 0 || $p !== 0) {
            if ($n === '' || $q <= 0 || $p <= 0) {
                $errors[] = "Dòng " . ($i+1) . " không hợp lệ";
                $fieldErrors["item_$i"] = 'Tên, SL và Giá phải hợp lệ';
            } else {
                $items[] = ['name'=>$n,'qty'=>$q,'price'=>$p,'line'=>$q*$p];
            }
        }
    }

    if (count($items) === 0) { $errors[] = 'Ít nhất 1 dòng hàng hợp lệ'; }

    // discount & VAT ranges
    $discount = floatval($discount);
    $vat = floatval($vat);
    if ($discount < 0 || $discount > 30) { $errors[] = 'Giảm giá phải 0–30%'; $fieldErrors['discount'] = 'Giảm giá phải 0–30%'; }
    if ($vat < 0 || $vat > 15) { $errors[] = 'VAT phải 0–15%'; $fieldErrors['vat'] = 'VAT phải 0–15%'; }

    if (empty($errors)) {
        // compute totals
        $sub = 0;
        foreach ($items as $it) $sub += $it['line'];
        $discAmount = $sub * ($discount/100);
        $vatAmount = ($sub - $discAmount) * ($vat/100);
        $total = $sub - $discAmount + $vatAmount;

        // invoice payload
        $invoice = [
            'customer' => ['name'=>$customer,'email'=>$email,'phone'=>$phone],
            'items' => $items,
            'sub' => $sub,
            'discount_pct' => $discount,
            'discount' => $discAmount,
            'vat_pct' => $vat,
            'vat' => $vatAmount,
            'total' => $total,
            'pay' => $pay,
            'created_at' => date('c')
        ];

        // append to a single invoices file
        $invoicesFile = __DIR__ . '/../data/invoices.json';
        $all = file_exists($invoicesFile) ? json_decode(file_get_contents($invoicesFile), true) : [];
        if (!is_array($all)) $all = [];
        // add an invoice id
        $invoice['invoice_id'] = 'INV' . time();
        $all[] = $invoice;
        file_put_contents($invoicesFile, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $fn = $invoicesFile; // file path for display

        // store in session for later
        $_SESSION['last_invoice'] = $invoice;
        $_SESSION['invoice_customer'] = $customer;
        $_SESSION['invoice_email'] = $email;
        $_SESSION['invoice_phone'] = $phone;

        $invoiceData = $invoice;
    }
}

function e($s){ return htmlspecialchars($s); }
?>

<!doctype html>
<html>
<body>
<h2>Tạo hóa đơn</h2>

<form method="post">
    Tên KH <input name="customer" value="<?= e($customer) ?>"> <br>
    Email <input name="email" value="<?= e($email) ?>"> <br>
    SĐT <input name="phone" value="<?= e($phone) ?>"> <br>
    <?php if (!empty($fieldErrors['phone'])): ?><div style="color:red"><?= e($fieldErrors['phone']) ?></div><?php endif; ?>
    <br>

    <?php for ($i=0;$i<3;$i++): ?>
        <div style="margin-bottom:6px">
            Tên <input name="name[]" value="<?= e($names[$i] ?? '') ?>"> 
            SL <input name="qty[]" type="number" value="<?= e($qtys[$i] ?? '') ?>"> 
            Giá <input name="price[]" value="<?= e($prices[$i] ?? '') ?>"> 
            <?php if (!empty($fieldErrors["item_$i"])): ?><div style="color:red"><?= e($fieldErrors["item_$i"]) ?></div><?php endif; ?>
        </div>
    <?php endfor; ?>

    Giảm giá % <input name="discount" value="<?= e($discount) ?>"> <?php if (!empty($fieldErrors['discount'])): ?><span style="color:red"><?= e($fieldErrors['discount']) ?></span><?php endif; ?> <br>
    VAT % <input name="vat" value="<?= e($vat) ?>"> <?php if (!empty($fieldErrors['vat'])): ?><span style="color:red"><?= e($fieldErrors['vat']) ?></span><?php endif; ?> <br>

    Thanh toán:
    <input type="radio" name="pay" value="cash" <?= $pay==='cash' ? 'checked' : '' ?>>Tiền mặt
    <input type="radio" name="pay" value="bank" <?= $pay==='bank' ? 'checked' : '' ?>>Chuyển khoản<br>

    <button type="submit">Tạo hóa đơn</button>
</form>

<?php if ($invoiceData): ?>
    <h3>Hóa đơn</h3>
    <p>Khách: <?= e($invoiceData['customer']['name']) ?> — <?= e($invoiceData['customer']['phone']) ?> <?= e($invoiceData['customer']['email']) ? '— '.e($invoiceData['customer']['email']) : '' ?></p>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead><tr><th>Mặt hàng</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead>
        <tbody>
            <?php foreach ($invoiceData['items'] as $it): ?>
                <tr>
                    <td><?= e($it['name']) ?></td>
                    <td><?= e($it['qty']) ?></td>
                    <td><?= e(number_format($it['price'])) ?></td>
                    <td><?= e(number_format($it['line'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="3">Tạm tính</td><td><?= e(number_format($invoiceData['sub'])) ?></td></tr>
            <tr><td colspan="3">Giảm giá (<?= e($invoiceData['discount_pct']) ?>%)</td><td>-<?= e(number_format($invoiceData['discount'])) ?></td></tr>
            <tr><td colspan="3">VAT (<?= e($invoiceData['vat_pct']) ?>%)</td><td><?= e(number_format($invoiceData['vat'])) ?></td></tr>
            <tr><td colspan="3"><strong>Tổng</strong></td><td><strong><?= e(number_format($invoiceData['total'])) ?> VND</strong></td></tr>
        </tfoot>
    </table>
    <p>Phương thức: <?= e($invoiceData['pay']) ?></p>
    <p>Đã lưu: <?= e(basename($fn ?? '')) ?></p>
<?php elseif (!empty($errors)): ?>
    <?php foreach ($errors as $er) echo "<p style='color:red'>".e($er)."</p>"; ?>
<?php endif; ?>

</body>
</html>