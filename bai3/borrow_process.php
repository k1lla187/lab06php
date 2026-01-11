<?php
// borrow_process.php
// Xử lý lưu phiếu mượn, cập nhật books.json

$file = __DIR__ . '/../data/borrows.json';
$booksFile = __DIR__ . '/../data/books.json';
$membersFile = __DIR__ . '/../data/members.csv';

// safe load JSON arrays
function loadJsonArray($path) {
    if (!file_exists($path)) return [];
    $c = file_get_contents($path);
    if ($c === false) return [];
    $j = json_decode($c, true);
    return is_array($j) ? $j : [];
}

$borrows = loadJsonArray($file);
$books = loadJsonArray($booksFile);

// request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Phải gửi bằng POST');
}

$memberRaw = trim($_POST['member'] ?? '');
$bookId = trim($_POST['book'] ?? '');
$qty = max(1, intval($_POST['qty'] ?? 1));
$dateRaw = trim($_POST['date'] ?? '');
$days = intval($_POST['days'] ?? 0);

if ($memberRaw === '' || $bookId === '' || $dateRaw === '' || $days < 1 || $days > 30 || $qty < 1) {
    http_response_code(400);
    die('Dữ liệu không hợp lệ');
}

// Normalize date input to Y-m-d (accept dd/mm/yyyy too)
function normalizeInputDate($s) {
    $s = trim($s);
    if ($s === '') return false;
    // Try Y-m-d
    $dt = DateTime::createFromFormat('Y-m-d', $s);
    $err = DateTime::getLastErrors();
    if ($dt !== false && is_array($err) && empty($err['warning_count']) && empty($err['error_count'])) {
        return $dt->format('Y-m-d');
    }
    // Try d/m/Y
    $dt = DateTime::createFromFormat('d/m/Y', $s);
    $err = DateTime::getLastErrors();
    if ($dt !== false && is_array($err) && empty($err['warning_count']) && empty($err['error_count'])) {
        return $dt->format('Y-m-d');
    }
    // Try strtotime fallback
    $ts = strtotime($s);
    if ($ts !== false) {
        return date('Y-m-d', $ts);
    }
    return false;
}

$date = normalizeInputDate($dateRaw);
if ($date === false) die('Ngày mượn không hợp lệ');

// find member in members.csv (match id, email or phone)
// members.csv may have different column orders; we'll search all columns for the provided token
$foundMember = null;
if (file_exists($membersFile)) {
    $lines = file($membersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $f = str_getcsv($line);
        // skip empty rows
        if (!is_array($f) || count(array_filter($f, fn($v)=>strlen(trim($v))>0))===0) continue;
        // first cell as id if present
        $candidateId = $f[0] ?? '';
        // check token among all fields
        foreach ($f as $cell) {
            if (trim($cell) === $memberRaw) {
                $foundMember = [
                    'id' => $candidateId,
                    'raw_row' => $f,
                ];
                break 2;
            }
        }
    }
}
if (!$foundMember) {
    // not found: treat provided memberRaw as id anyway (but still allow)
    // to follow original behavior, require exist — here we enforce exist
    die('Mã thành viên không tồn tại');
}
$memberId = $foundMember['id'] ?: $memberRaw;

// find book
$foundBookIndex = null;
foreach ($books as $i => $b) {
    if (($b['id'] ?? '') === $bookId) {
        $foundBookIndex = $i;
        break;
    }
}
if ($foundBookIndex === null) die('Mã sách không tồn tại');

$available = intval($books[$foundBookIndex]['qty'] ?? 0);
if ($available <= 0) die('Sách hiện không có sẵn');
if ($available < $qty) die('Không đủ số lượng sách (còn ' . $available . ')');

// prevent duplicate active borrow (same member + same book)
foreach ($borrows as $b) {
    if (($b['member'] ?? '') === $memberId && ($b['book'] ?? '') === $bookId && ($b['status'] ?? '') === 'Đang mượn') {
        $existingQty = intval($b['qty'] ?? 1);
        die("Thành viên đã mượn sách này (SL đang mượn: $existingQty)");
    }
}

// compute due date
$bdt = DateTime::createFromFormat('Y-m-d', $date);
$due = clone $bdt;
$due->modify('+' . $days . ' days');
$dueStr = $due->format('Y-m-d');

// create borrow record
$borrowId = uniqid('P');
$borrow = [
    'id' => $borrowId,
    'member' => $memberId,
    'book' => $bookId,
    'qty' => $qty,
    'date' => $date,
    'days' => $days,
    'due' => $dueStr,
    'status' => 'Đang mượn',
];

$borrows[] = $borrow;
// decrement book qty
$books[$foundBookIndex]['qty'] = $available - $qty;

// save
file_put_contents($file, json_encode($borrows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents($booksFile, json_encode($books, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'message' => 'Mượn sách thành công',
    'borrow' => $borrow,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);