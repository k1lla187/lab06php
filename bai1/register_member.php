<?php
$errors = [];
$data = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'dob' => '',
    'gender' => 'Nam',
    'address' => ''
];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $k => $v) {
        $data[$k] = trim($_POST[$k] ?? '');
    }

    // field-level errors for inline display
    $fieldErrors = [];

    if ($data['name'] === '') {
        $errors[] = 'Họ tên bắt buộc';
        $fieldErrors['name'] = 'Họ tên bắt buộc';
    }

    if ($data['email'] === '') {
        $errors[] = 'Email bắt buộc';
        $fieldErrors['email'] = 'Email bắt buộc';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ';
        $fieldErrors['email'] = 'Email không hợp lệ';
    }

    if ($data['phone'] === '') {
        $errors[] = 'SĐT bắt buộc';
        $fieldErrors['phone'] = 'SĐT bắt buộc';
    } elseif (!preg_match('/^[0-9]{9,11}$/', $data['phone'])) {
        $errors[] = 'SĐT phải 9–11 chữ số';
        $fieldErrors['phone'] = 'SĐT phải 9–11 chữ số';
    }

    if ($data['dob'] === '') {
        $errors[] = 'Ngày sinh bắt buộc';
        $fieldErrors['dob'] = 'Ngày sinh bắt buộc';
    }


    if (!$errors) {
        $csvFile = __DIR__ . '/../data/members.csv';
        $count = file_exists($csvFile) ? count(file($csvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : 0;
        $id = 'M' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        $line = $id . ',' . implode(',', array_map(fn($v) => str_replace(',', ' ', $v), $data)) . "\n";
        file_put_contents($csvFile, $line, FILE_APPEND);
        session_start();
        $_SESSION['member'] = array_merge(['id' => $id], $data);
        header('Location: member_result.php');
        exit;
    }
}
function e($s)
{
    return htmlspecialchars($s);
}

$csv = __DIR__ . '/../data/members.csv';
$member_count = 0;
if (file_exists($csv)) {
    $member_count = count(file($csv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
}
?>


<!doctype html>
<html>

<body>
    <h2>Đăng ký thẻ thư viện</h2>

    <p><a href="member_result.php">Xem danh sách thành viên (<?= $member_count ?>)</a></p>


    <?php if ($errors): ?>
        <ul style="color:red">
            <?php foreach ($errors as $e) echo "<li>$e</li>"; ?>
        </ul>
    <?php endif; ?>


    <form method="post">
        Họ tên: <input name="name" value="<?= e($data['name']) ?>"><br>
        <?php if (!empty($fieldErrors['name'])): ?>
            <div style="color:red"><?= e($fieldErrors['name']) ?></div>
        <?php endif; ?>

        Email: <input name="email" value="<?= e($data['email']) ?>"><br>
        <?php if (!empty($fieldErrors['email'])): ?>
            <div style="color:red"><?= e($fieldErrors['email']) ?></div>
        <?php endif; ?>

        SĐT: <input name="phone" value="<?= e($data['phone']) ?>"><br>
        <?php if (!empty($fieldErrors['phone'])): ?>
            <div style="color:red"><?= e($fieldErrors['phone']) ?></div>
        <?php endif; ?>

        Ngày sinh: <input type="date" name="dob" value="<?= e($data['dob']) ?>"><br>
        <?php if (!empty($fieldErrors['dob'])): ?>
            <div style="color:red"><?= e($fieldErrors['dob']) ?></div>
        <?php endif; ?>

        Giới tính:
        <input type="radio" name="gender" value="Nam" <?= $data['gender'] === 'Nam' ? 'checked' : '' ?>>Nam
        <input type="radio" name="gender" value="Nữ" <?= $data['gender'] === 'Nữ' ? 'checked' : '' ?>>Nữ
        <input type="radio" name="gender" value="Khác" <?= $data['gender'] === 'Khác' ? 'checked' : '' ?>>Khác
        <br>
        Địa chỉ:<br>
        <textarea name="address"><?= e($data['address']) ?></textarea><br>


        <button>Submit</button>
        <button type="reset">Reset</button>
    </form>
</body>

</html>