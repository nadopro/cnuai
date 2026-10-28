<?php
/**
 * manPerson.php
 * index.php?cmd=manPerson 에서 include하여 사용
 * 전제: index.php에서 MySQLi 연결 객체 $conn (또는 $mysqli)을 생성해 둠
 * person: person_id, name, gender, description, photo, created_at
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// index.php에서 전달한 DB 연결을 사용합니다.
if (!isset($conn) && isset($mysqli) && $mysqli instanceof mysqli) {
    $conn = $mysqli;
}
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    exit('DB 연결이 없습니다. index.php에서 MySQLi 연결 객체 $conn을 생성해주세요.');
}
$conn->set_charset('utf8mb4');

date_default_timezone_set('Asia/Seoul');

// 이 파일은 index.php와 같은 디렉터리에 두는 것을 기준으로 합니다.
$photoDir = __DIR__ . '/data/photo';
$photoUrlDir = 'data/photo';
$actionUrl = 'index.php?cmd=manPerson';

if (empty($_SESSION['man_person_csrf'])) {
    $_SESSION['man_person_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['man_person_csrf'];

function mp_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function mp_photo_url(?string $filename, string $base): string {
    if (!$filename) return '';
    // 파일명만 저장하고, 폴더 이동을 차단합니다.
    return $base . '/' . rawurlencode(basename($filename));
}
function mp_remove_photo(?string $filename, string $dir): void {
    if (!$filename) return;
    $name = basename($filename);
    $path = $dir . '/' . $name;
    if (is_file($path)) {
        @unlink($path);
    }
}
function mp_upload_photo(array $file, string $dir): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($file['error'] ?? -1) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new RuntimeException('사진 업로드에 실패했습니다.');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('사진은 5MB 이하만 등록할 수 있습니다.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('JPG, PNG, WEBP, GIF 사진만 등록할 수 있습니다.');
    }
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('사진 저장 폴더를 만들 수 없습니다.');
    }
    // 예: 20261028123456.jpg / 같은 초에 겹치면 20261028123456_1.jpg
    $stamp = date('YmdHis');
    $extension = $extensions[$mime];
    $filename = $stamp . '.' . $extension;
    $n = 1;
    while (file_exists($dir . '/' . $filename)) {
        $filename = $stamp . '_' . $n++ . '.' . $extension;
    }
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
        throw new RuntimeException('사진을 저장하지 못했습니다.');
    }
    return $filename;
}
function mp_find_person(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare('SELECT person_id, name, gender, description, photo FROM person WHERE person_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
}

$error = '';
$editing = null;
$form = ['person_id' => 0, 'name' => '', 'gender' => '', 'description' => '', 'photo' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operation = (string)($_POST['operation'] ?? '');
    $id = filter_var($_POST['person_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
    $newPhoto = '';
    try {
        if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('요청 검증에 실패했습니다. 페이지를 새로고침해 주세요.');
        }
        if ($operation === 'delete') {
            if ($id <= 0 || !($old = mp_find_person($conn, $id))) {
                throw new RuntimeException('삭제할 인물을 찾을 수 없습니다.');
            }
            $stmt = $conn->prepare('DELETE FROM person WHERE person_id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            mp_remove_photo($old['photo'], $photoDir);
            header('Location: ' . $actionUrl . '&result=deleted');
            exit;
        }
        if ($operation !== 'save') {
            throw new RuntimeException('올바르지 않은 요청입니다.');
        }
        $name = trim((string)($_POST['name'] ?? ''));
        $gender = (string)($_POST['gender'] ?? '');
        $description = trim((string)($_POST['description'] ?? ''));
        $form = ['person_id' => $id, 'name' => $name, 'gender' => $gender, 'description' => $description, 'photo' => ''];
        if ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
            throw new RuntimeException('이름은 1~100자로 입력해 주세요.');
        }
        if (!in_array($gender, ['M', 'F'], true)) {
            throw new RuntimeException('성별을 선택해 주세요.');
        }
        $old = null;
        if ($id > 0) {
            $old = mp_find_person($conn, $id);
            if (!$old) throw new RuntimeException('수정할 인물을 찾을 수 없습니다.');
            $form['photo'] = $old['photo'];
        }
        $newPhoto = mp_upload_photo($_FILES['photo'] ?? [], $photoDir);
        $photo = $newPhoto !== '' ? $newPhoto : ($old['photo'] ?? null);
        if ($id > 0) {
            $stmt = $conn->prepare('UPDATE person SET name = ?, gender = ?, description = ?, photo = ? WHERE person_id = ?');
            $stmt->bind_param('ssssi', $name, $gender, $description, $photo, $id);
        } else {
            $stmt = $conn->prepare('INSERT INTO person (name, gender, description, photo) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $name, $gender, $description, $photo);
        }
        $stmt->execute();
        $stmt->close();
        if ($newPhoto !== '' && $old && $old['photo']) {
            mp_remove_photo($old['photo'], $photoDir);
        }
        header('Location: ' . $actionUrl . '&result=' . ($id > 0 ? 'updated' : 'created'));
        exit;
    } catch (Throwable $e) {
        if ($newPhoto !== '') mp_remove_photo($newPhoto, $photoDir);
        $error = $e->getMessage();
    }
} elseif (isset($_GET['edit'])) {
    $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT) ?: 0;
    if ($id > 0) {
        $editing = mp_find_person($conn, $id);
        if ($editing) $form = $editing;
        else $error = '수정할 인물을 찾을 수 없습니다.';
    }
}
if ((int)$form['person_id'] > 0) $editing = $form;

$people = [];
$result = $conn->query('SELECT person_id, name, gender, description, photo, created_at FROM person ORDER BY person_id DESC');
while ($row = $result->fetch_assoc()) $people[] = $row;
$result->free();

$message = ['created' => '인물이 등록되었습니다.', 'updated' => '인물 정보가 수정되었습니다.', 'deleted' => '인물이 삭제되었습니다.'][(string)($_GET['result'] ?? '')] ?? '';
?>
<style>
.mp-wrap{max-width:1100px;margin:20px auto;padding:0 14px;font-family:Arial,'Malgun Gothic',sans-serif;color:#222}
.mp-wrap *{box-sizing:border-box}.mp-wrap h2{font-size:22px;margin:18px 0}.mp-panel{padding:22px;border:1px solid #ddd;border-radius:8px;background:#fff}
.mp-field{margin-bottom:14px}.mp-field label{display:block;font-weight:600;margin-bottom:7px}.mp-field input[type=text],.mp-field select,.mp-field textarea{width:100%;padding:10px;border:1px solid #bbb;border-radius:5px;font:inherit}
.mp-field textarea{min-height:100px;resize:vertical}.mp-actions{display:flex;gap:8px;align-items:center}.mp-btn{display:inline-block;padding:8px 13px;border:0;border-radius:5px;background:#245ab6;color:#fff;text-decoration:none;cursor:pointer;font-size:14px}
.mp-btn.secondary{background:#68717c}.mp-btn.danger{background:#c33838}.mp-alert{padding:11px;margin-bottom:16px;border-radius:5px;background:#e9f5ed;color:#175b2a}.mp-alert.error{background:#fdeaea;color:#a02a2a}
.mp-table-scroll{overflow-x:auto}.mp-table{border-collapse:collapse;width:100%;min-width:760px}.mp-table th,.mp-table td{border-bottom:1px solid #ddd;padding:10px;vertical-align:middle;text-align:left}.mp-table th{background:#f3f5f8}.mp-thumb{width:64px;height:64px;object-fit:cover;border-radius:5px}.mp-old-photo{max-width:140px;max-height:140px;object-fit:contain;display:block;margin:6px 0}.mp-muted{color:#777;font-size:13px}.mp-desc{max-width:340px;white-space:pre-wrap;overflow-wrap:anywhere}.mp-inline{display:inline}.mp-nowrap{white-space:nowrap}
</style>
<div class="mp-wrap">
    <h2>인물 <?= $editing ? '수정' : '등록' ?></h2>
    <?php if ($error): ?><div class="mp-alert error"><?= mp_h($error) ?></div><?php endif; ?>
    <?php if ($message): ?><div class="mp-alert"><?= mp_h($message) ?></div><?php endif; ?>
    <div class="mp-panel">
        <form method="post" action="<?= mp_h($actionUrl) ?>" enctype="multipart/form-data">
            <input type="hidden" name="operation" value="save">
            <input type="hidden" name="csrf" value="<?= mp_h($csrf) ?>">
            <input type="hidden" name="person_id" value="<?= (int)$form['person_id'] ?>">
            <div class="mp-field"><label for="mp-name">이름 *</label><input id="mp-name" name="name" type="text" maxlength="100" required value="<?= mp_h($form['name']) ?>"></div>
            <div class="mp-field"><label for="mp-gender">성별 *</label><select id="mp-gender" name="gender" required>
                <option value="">선택</option><option value="M" <?= $form['gender'] === 'M' ? 'selected' : '' ?>>남성</option><option value="F" <?= $form['gender'] === 'F' ? 'selected' : '' ?>>여성</option>
            </select></div>
            <div class="mp-field"><label for="mp-photo">사진 <?= $editing ? '(변경할 경우에만 선택)' : '' ?></label>
                <?php if (!empty($form['photo'])): ?><img class="mp-old-photo" src="<?= mp_h(mp_photo_url($form['photo'], $photoUrlDir)) ?>" alt="현재 사진"><?php endif; ?>
                <input id="mp-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif"><div class="mp-muted">JPG, PNG, WEBP, GIF / 최대 5MB</div></div>
            <div class="mp-field"><label for="mp-description">설명</label><textarea id="mp-description" name="description"><?= mp_h($form['description']) ?></textarea></div>
            <div class="mp-actions"><button type="submit" class="mp-btn"><?= $editing ? '수정 저장' : '등록' ?></button><?php if ($editing): ?><a class="mp-btn secondary" href="<?= mp_h($actionUrl) ?>">취소</a><?php endif; ?></div>
        </form>
    </div>

    <h2>인물 목록 (<?= count($people) ?>명)</h2>
    <div class="mp-table-scroll"><table class="mp-table">
        <thead><tr><th>번호</th><th>사진</th><th>이름</th><th>성별</th><th>설명</th><th>등록일</th><th>관리</th></tr></thead>
        <tbody>
        <?php foreach ($people as $person): ?>
            <tr>
                <td><?= (int)$person['person_id'] ?></td>
                <td><?php if ($person['photo']): ?><img class="mp-thumb" src="<?= mp_h(mp_photo_url($person['photo'], $photoUrlDir)) ?>" alt="<?= mp_h($person['name']) ?> 사진"><?php else: ?><span class="mp-muted">없음</span><?php endif; ?></td>
                <td><?= mp_h($person['name']) ?></td><td><?= $person['gender'] === 'M' ? '남성' : ($person['gender'] === 'F' ? '여성' : '-') ?></td>
                <td class="mp-desc"><?= mp_h($person['description'] ?? '') ?></td><td class="mp-nowrap"><?= mp_h($person['created_at']) ?></td>
                <td class="mp-nowrap"><a class="mp-btn secondary" href="<?= mp_h($actionUrl) ?>&amp;edit=<?= (int)$person['person_id'] ?>">수정</a>
                    <form class="mp-inline" method="post" action="<?= mp_h($actionUrl) ?>" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                        <input type="hidden" name="operation" value="delete"><input type="hidden" name="csrf" value="<?= mp_h($csrf) ?>"><input type="hidden" name="person_id" value="<?= (int)$person['person_id'] ?>">
                        <button class="mp-btn danger" type="submit">삭제</button>
                    </form></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$people): ?><tr><td colspan="7" style="text-align:center">등록된 인물이 없습니다.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
