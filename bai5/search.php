<?php
$books = json_decode(file_get_contents('../data/books.json'), true) ?? [];
$kw = trim($_GET['kw'] ?? '');
$category = $_GET['category'] ?? 'all';
$year_from = trim($_GET['year_from'] ?? '');
$year_to = trim($_GET['year_to'] ?? '');
$price_from = trim($_GET['price_from'] ?? '');
$price_to = trim($_GET['price_to'] ?? '');

$res = [];
foreach ($books as $b) {
    // keyword
    if ($kw && stripos($b['name'] ?? '', $kw) === false) continue;
    // category
    if ($category != 'all' && ($b['cat'] ?? '') != $category) continue;
    // year range
    if ($year_from !== '') {
        if (!isset($b['year']) || intval($b['year']) < intval($year_from)) continue;
    }
    if ($year_to !== '') {
        if (!isset($b['year']) || intval($b['year']) > intval($year_to)) continue;
    }
    // price range
    if ($price_from !== '') {
        if (!isset($b['price']) || floatval($b['price']) < floatval($price_from)) continue;
    }
    if ($price_to !== '') {
        if (!isset($b['price']) || floatval($b['price']) > floatval($price_to)) continue;
    }

    $res[] = $b;
}

function e($s)
{
    return htmlspecialchars($s);
} ?>


<form method="get">
    Từ khóa <input name="kw" value="<?= e($kw) ?>">

    Thể loại
    <select name="category">
        <option value="all" <?= $category==='all' ? 'selected' : '' ?>>Tất cả</option>
        <option <?= $category==='Giáo trình' ? 'selected' : '' ?>>Giáo trình</option>
        <option <?= $category==='Kỹ năng' ? 'selected' : '' ?>>Kỹ năng</option>
        <option <?= $category==='Văn học' ? 'selected' : '' ?>>Văn học</option>
        <option <?= $category==='Khoa học' ? 'selected' : '' ?>>Khoa học</option>
        <option <?= $category==='Khác' ? 'selected' : '' ?>>Khác</option>
    </select>

    Năm từ <input name="year_from" size="4" value="<?= e($year_from) ?>"> đến <input name="year_to" size="4" value="<?= e($year_to) ?>">

    Giá từ <input name="price_from" size="6" value="<?= e($price_from) ?>"> đến <input name="price_to" size="6" value="<?= e($price_to) ?>">

    <button>Tìm</button>
</form>


<?php if (empty($res) && ($_GET)): ?>
    <p>Không có kết quả</p>
<?php endif; ?>

<?php if (!empty($res)): ?>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên</th>
                <th>Tác giả</th>
                <th>Thể loại</th>
                <th>Năm</th>
                <th>Giá</th>
                <th>Số lượng</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($res as $b): ?>
                <tr>
                    <td><?= e($b['id'] ?? '') ?></td>
                    <td><?= e($b['name'] ?? '') ?></td>
                    <td><?= e($b['author'] ?? '') ?></td>
                    <td><?= e($b['cat'] ?? '') ?></td>
                    <td><?= e($b['year'] ?? '') ?></td>
                    <td><?= isset($b['price']) ? e(number_format($b['price'])) : '' ?></td>
                    <td><?= e($b['qty'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>