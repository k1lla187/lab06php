<?php
$file = '../data/books.json';
$books = [];
if (file_exists($file)) {
    $books = json_decode(file_get_contents($file), true);
    if (!is_array($books)) $books = [];
}
$err = [];
$success = '';
// initialize form values so they persist after submit
$id = '';
$name = '';
$author = '';
$published = '';
$cat = 'Giáo trình';
$qty = '';
$price = '';
function e($s){ return htmlspecialchars($s); }


if ($_POST) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $author = $_POST['author'];
    $published = trim($_POST['published'] ?? '');
    $year = null;
    $cat = $_POST['category'];
    $qty = (int)$_POST['qty'];
    $price = floatval($_POST['price'] ?? 0);


    foreach ($books as $b) if ($b['id'] === $id) $err[] = 'Trùng mã sách';


    // Validate published date (YYYY-MM-DD)
    if ($published === '') {
        $err[] = 'Ngày xuất bản bắt buộc';
    } else {
        $dt = DateTime::createFromFormat('Y-m-d', $published);
        $dt_errors = DateTime::getLastErrors();
        if (!$dt || $dt_errors['warning_count'] || $dt_errors['error_count']) {
            $err[] = 'Ngày xuất bản không hợp lệ';
        } else {
            $year = (int)$dt->format('Y');
            $today = new DateTime('today');
            if ($year < 1900 || $dt > $today) $err[] = 'Năm không hợp lệ';
        }
    }

    if ($qty < 0) $err[] = 'Số lượng phải >=0';
    if ($price <= 0) $err[] = 'Giá phải lớn hơn 0';


    if (!$err) {
        $new = compact('id', 'name', 'author', 'year', 'cat', 'qty', 'price');
        $new['published'] = $published;
        $books[] = $new;
        file_put_contents($file, json_encode($books, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $success = 'Thêm thành công';
    }
}
?>


<form method="post">
    Mã sách <input name="id" value="<?= e($id) ?>"><br>
    Tên <input name="name" value="<?= e($name) ?>"><br>
    Tác giả <input name="author" value="<?= e($author) ?>"><br>
    Ngày xuất bản <input name="published" type="date" value="<?= e($published) ?>"><br>
    Thể loại <select name="category">
        <option <?= $cat === 'Giáo trình' ? 'selected' : '' ?>>Giáo trình</option>
        <option <?= $cat === 'Kỹ năng' ? 'selected' : '' ?>>Kỹ năng</option>
        <option <?= $cat === 'Văn học' ? 'selected' : '' ?>>Văn học</option>
        <option <?= $cat === 'Khoa học' ? 'selected' : '' ?>>Khoa học</option>
        <option <?= $cat === 'Khác' ? 'selected' : '' ?>>Khác</option>
    </select><br>
    Số lượng <input name="qty" type="number" value="<?= e($qty) ?>"><br>
    Giá <input name="price" type="number" step="0.01" value="<?= e($price) ?>"><br>
    <button type="submit">Lưu</button>
    <button type="button" id="clearBtn">Reset</button>
</form>

<?php if ($success) echo "<p style='color:green'>$success</p>"; ?>
<?php foreach ($err as $e) echo "<p style='color:red'>$e</p>"; ?>

<script>
(function(){
    var btn = document.getElementById('clearBtn');
    if (!btn) return;
    btn.addEventListener('click', function(){
        var form = this.form || document.querySelector('form');
        if (!form) return;
        var elems = form.querySelectorAll('input, textarea, select');
        elems.forEach(function(el){
            var tag = el.tagName.toLowerCase();
            var type = el.type;
            if (type === 'radio' || type === 'checkbox') {
                el.checked = false;
            } else if (tag === 'select') {
                el.selectedIndex = 0;
            } else {
                el.value = '';
            }
        });
        var first = form.querySelector('input[name="id"]');
        if (first) first.focus();
    });
})();
</script>