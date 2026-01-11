<?php
session_start();

$csv = __DIR__ . '/../data/members.csv';
$members = [];

function e($s)
{
    return htmlspecialchars($s);
}

if (file_exists($csv)) {
    $lines = file($csv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $fields = str_getcsv($line);
        // support two formats: old (name,email,phone,dob,gender,address)
        // and new (id,name,email,phone,dob,gender,address)
        if (count($fields) === 6) {
            $members[] = [
                'id' => '',
                'name' => $fields[0] ?? '',
                'email' => $fields[1] ?? '',
                'phone' => $fields[2] ?? '',
                'dob' => $fields[3] ?? '',
                'gender' => $fields[4] ?? '',
                'address' => $fields[5] ?? '',
            ];
        } else {
            $members[] = [
                'id' => $fields[0] ?? '',
                'name' => $fields[1] ?? '',
                'email' => $fields[2] ?? '',
                'phone' => $fields[3] ?? '',
                'dob' => $fields[4] ?? '',
                'gender' => $fields[5] ?? '',
                'address' => $fields[6] ?? '',
            ];
        }
    }
}
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Danh sách thành viên</title>
</head>
<body>

<h2>Danh sách thành viên</h2>

<?php if (empty($members)): ?>
    <p>Không có dữ liệu thành viên.</p>
<?php else: ?>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>#</th>
                <th>Mã</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>SĐT</th>
                <th>Ngày sinh</th>
                <th>Giới tính</th>
                <th>Địa chỉ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $i => $m): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($m['id']) ?></td>
                    <td><?= e($m['name']) ?></td>
                    <td><?= e($m['email']) ?></td>
                    <td><?= e($m['phone']) ?></td>
                    <td><?= e($m['dob']) ?></td>
                    <td><?= e($m['gender']) ?></td>
                    <td><?= e($m['address']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if (isset($_SESSION['member'])): ?>
    <?php $sm = $_SESSION['member']; ?>
    <h3>Thành viên vừa đăng ký</h3>
    <ul>
        <li>Mã: <?= e($sm['id'] ?? '') ?></li>
        <li>Họ tên: <?= e($sm['name'] ?? '') ?></li>
        <li>Email: <?= e($sm['email'] ?? '') ?></li>
        <li>SĐT: <?= e($sm['phone'] ?? '') ?></li>
        <li>Ngày sinh: <?= e($sm['dob'] ?? '') ?></li>
        <li>Giới tính: <?= e($sm['gender'] ?? '') ?></li>
        <li>Địa chỉ: <?= e($sm['address'] ?? '') ?></li>
    </ul>
<?php endif; ?>

<p><a href="register_member.php">Đăng ký thêm</a></p>

</body>
</html>

