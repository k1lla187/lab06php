<?php
// return_process.php
// Xử lý trả sách — chấp nhận nhiều định dạng ngày và kiểm tra khoảng trả với ngày mượn + days

$file = __DIR__ . '/../data/borrows.json';
$booksFile = __DIR__ . '/../data/books.json';

// safe loaders
function loadJsonArray($path) {
    if (!file_exists($path)) return [];
    $c = file_get_contents($path);
    if ($c === false) return [];
    $j = json_decode($c, true);
    return is_array($j) ? $j : [];
}

$borrows = loadJsonArray($file);
$books = loadJsonArray($booksFile);

// flexible parse: supports Y-m-d and d/m/Y and tries strtotime
function parseDateFlexible($s) {
    $s = trim((string)$s);
    if ($s === '') return null;
    // Y-m-d
    $dt = DateTime::createFromFormat('Y-m-d', $s);
    $err = DateTime::getLastErrors();
    if ($dt !== false && is_array($err) && empty($err['warning_count']) && empty($err['error_count'])) {
        return $dt;
    }
    // d/m/Y
    $dt = DateTime::createFromFormat('d/m/Y', $s);
    $err = DateTime::getLastErrors();
    if ($dt !== false && is_array($err) && empty($err['warning_count']) && empty($err['error_count'])) {
        return $dt;
    }
    // d-m-Y
    $dt = DateTime::createFromFormat('d-m-Y', $s);
    $err = DateTime::getLastErrors();
    if ($dt !== false && is_array($err) && empty($err['warning_count']) && empty($err['error_count'])) {
        return $dt;
    }
    // try strtotime
    $ts = strtotime($s);
    if ($ts !== false) {
        try {
            $d = new DateTime();
            $d->setTimestamp($ts);
            return $d;
        } catch (Exception $e) {
            // fall through
        }
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Phải gửi bằng POST');
}

$id = trim($_POST['id'] ?? '');
$member = trim($_POST['member'] ?? '');
$bookId = trim($_POST['book'] ?? '');
$returnDateStr = trim($_POST['date'] ?? '');

if ($id === '' && ($member === '' || $bookId === '')) {
    http_response_code(400);
    die('Phải cung cấp mã phiếu hoặc (mã TV + mã sách)');
}

// parse return date (if empty => today)
if ($returnDateStr !== '') {
    $parsed = parseDateFlexible($returnDateStr);
    if ($parsed === false) {
        die('Ngày trả không hợp lệ (chấp nhận YYYY-MM-DD hoặc DD/MM/YYYY)');
    }
    $returnDate = $parsed;
} else {
    $returnDate = new DateTime(); // today
}

// find borrow
$found = false;
$infoIndex = null;
foreach ($borrows as $idx => $br) {
    if ($id !== '') {
        if (($br['id'] ?? '') === $id && ($br['status'] ?? '') === 'Đang mượn') {
            $found = true;
            $infoIndex = $idx;
            break;
        }
    } else {
        if (($br['member'] ?? '') === $member && ($br['book'] ?? '') === $bookId && ($br['status'] ?? '') === 'Đang mượn') {
            $found = true;
            $infoIndex = $idx;
            break;
        }
    }
}

if (!$found) die('Phiếu không hợp lệ hoặc đã trả');

$info = &$borrows[$infoIndex];

// parse borrow date (stored as Y-m-d but handle other possible formats to be robust)
$borrowDateStr = $info['date'] ?? '';
$bdt = parseDateFlexible($borrowDateStr);
if ($bdt === false || $bdt === null) {
    die('Dữ liệu phiếu mượn không hợp lệ (ngày mượn)');
}
$borrowDays = intval($info['days'] ?? 0);
if ($borrowDays < 1 || $borrowDays > 30) {
    die('Dữ liệu phiếu mượn không hợp lệ (số ngày mượn)');
}

// compute due date
$dueDate = clone $bdt;
$dueDate->modify('+' . $borrowDays . ' days');

// check returnDate in range [borrowDate, dueDate]
$returnYmd = $returnDate->format('Y-m-d');
$borrowYmd = $bdt->format('Y-m-d');
$dueYmd = $dueDate->format('Y-m-d');

if ($returnDate < $bdt) {
    die("Ngày trả không hợp lệ: phải lớn hơn hoặc bằng ngày mượn ($borrowYmd)");
}
if ($returnDate > $dueDate) {
    die("Ngày trả không hợp lệ: phải không vượt quá hạn trả ($dueYmd). Khoảng hợp lệ: $borrowYmd — $dueYmd");
}

// mark returned & update books qty
$info['status'] = 'Đã trả';
$returnedQty = intval($info['qty'] ?? 1);

// find book and update qty
$bookFound = false;
foreach ($books as $bi => $bk) {
    if (($bk['id'] ?? '') === ($info['book'] ?? '')) {
        $books[$bi]['qty'] = intval($bk['qty'] ?? 0) + $returnedQty;
        $bookFound = true;
        break;
    }
}
// it's ok if book not found in books.json — but warn? we will proceed

// save files
file_put_contents($file, json_encode($borrows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents($booksFile, json_encode($books, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// response
$response = [
    'message' => 'Trả sách thành công',
    'borrow' => $info,
    'returned_at' => $returnYmd,
    'valid_return_range' => [
        'from' => $borrowYmd,
        'to' => $dueYmd,
    ],
];
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);