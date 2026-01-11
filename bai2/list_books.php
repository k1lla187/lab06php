<?php
$books = json_decode(file_get_contents('../data/books.json'), true) ?? [];
function e($s)
{
    return htmlspecialchars($s);
} ?>


<table border=1>
    <tr>
        <th>Mã</th>
        <th>Tên</th>
        <th>Tác giả</th>
        <th>Năm</th>
        <th>Ngày xuất bản</th>
        <th>Thể loại</th>
        <th>Giá</th>
        <th>SL</th>
    </tr>
    <?php foreach ($books as $b): ?>
        <tr>
            <td><?= e($b['id'] ?? '') ?></td>
            <td><?= e($b['name'] ?? '') ?></td>
            <td><?= e($b['author'] ?? '') ?></td>
            <td><?= e($b['year'] ?? '') ?></td>
            <td><?= e($b['published'] ?? '') ?></td>
            <td><?= e($b['cat'] ?? '') ?></td>
            <td><?= isset($b['price']) ? e(number_format($b['price'])) : '' ?></td>
            <td><?= e($b['qty'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
</table>