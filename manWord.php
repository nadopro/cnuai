<?php
/**
 * manWord.php
 * index.php?cmd=manWord 로 호출되며 index.php에서 DB 접속이 이미 이루어진 것을 전제로 합니다.
 * DB 연결 변수: $conn, $mysqli, $db 중 mysqli 객체를 자동 감지합니다.
 */

$wordDb = null;
foreach (['conn', 'mysqli', 'db'] as $connectionName) {
    if (isset($$connectionName) && $$connectionName instanceof mysqli) {
        $wordDb = $$connectionName;
        break;
    }
}
if (!$wordDb) {
    echo '<div class="word-error">MySQLi 연결을 찾을 수 없습니다. index.php에서 $conn (또는 $mysqli, $db)을 설정해 주세요.</div>';
    return;
}
$wordDb->set_charset('utf8mb4');

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (empty($_SESSION['manWord_csrf'])) {
    $_SESSION['manWord_csrf'] = bin2hex(random_bytes(32));
}
$wordCsrf = $_SESSION['manWord_csrf'];

function wordEsc($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function wordGo(string $message): void {
    $target = 'index.php?cmd=manWord&tab=word&msg=' . rawurlencode($message);
    if (!headers_sent()) {
        header('Location: ' . $target);
        exit;
    }
    echo '<script>location.replace(' . json_encode($target, JSON_UNESCAPED_UNICODE) . ');</script>';
    echo '<a href="' . wordEsc($target) . '">목록으로 돌아가기</a>';
    exit;
}

$wordTab = (string)($_GET['tab'] ?? 'word');
if (!in_array($wordTab, ['word', 'stats', 'test'], true)) $wordTab = 'word';
$wordError = '';
$wordEdit = null;
$wordInput = [
    'idx' => 0, 'words' => '', 'kwords' => '', 'sounds' => '',
    'same' => '', 'memo' => '', 'level' => 1, 'useflag' => 'Y'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['word_action'])) {
    $action = (string)$_POST['word_action'];
    $tokenOk = isset($_POST['csrf_token']) && hash_equals($wordCsrf, (string)$_POST['csrf_token']);
    if (!$tokenOk) {
        $wordError = '요청이 유효하지 않습니다. 화면을 새로고침한 뒤 다시 시도해 주세요.';
    } elseif ($action === 'delete') {
        $idx = filter_var($_POST['idx'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($idx === false || $idx === null) {
            $wordError = '삭제할 항목이 올바르지 않습니다.';
        } else {
            $stmt = $wordDb->prepare('DELETE FROM word_table WHERE idx = ?');
            $stmt->bind_param('i', $idx);
            if ($stmt->execute()) wordGo('삭제되었습니다.');
            $wordError = '삭제하지 못했습니다: ' . $stmt->error;
            $stmt->close();
        }
    } elseif ($action === 'save') {
        $idx = filter_var($_POST['idx'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $wordInput = [
            'idx' => ($idx === false ? 0 : (int)$idx),
            'words' => trim((string)($_POST['words'] ?? '')),
            'kwords' => trim((string)($_POST['kwords'] ?? '')),
            'sounds' => trim((string)($_POST['sounds'] ?? '')),
            'same' => trim((string)($_POST['same'] ?? '')),
            'memo' => trim((string)($_POST['memo'] ?? '')),
            'level' => (int)($_POST['level'] ?? 1),
            'useflag' => (string)($_POST['useflag'] ?? 'Y')
        ];
        if ($wordInput['words'] === '' || $wordInput['kwords'] === '') {
            $wordError = '고사성어 한자와 한글 독음을 입력해 주세요.';
        } elseif (mb_strlen($wordInput['words']) > 50 || mb_strlen($wordInput['kwords']) > 100 || mb_strlen($wordInput['sounds']) > 255) {
            $wordError = '입력 가능한 글자 수를 초과했습니다.';
        } elseif ($wordInput['level'] < 1 || $wordInput['level'] > 5 || !in_array($wordInput['useflag'], ['Y', 'N'], true)) {
            $wordError = '난이도 또는 사용 여부 값이 올바르지 않습니다.';
        } else {
            $w = $wordInput['words'];
            $k = $wordInput['kwords'];
            $s = $wordInput['sounds'];
            $same = $wordInput['same'];
            $memo = $wordInput['memo'];
            $level = $wordInput['level'];
            $flag = $wordInput['useflag'];
            if ($wordInput['idx'] > 0) {
                $id = $wordInput['idx'];
                $stmt = $wordDb->prepare('UPDATE word_table SET words=?, kwords=?, sounds=?, same=?, memo=?, level=?, useflag=? WHERE idx=?');
                $stmt->bind_param('sssssisi', $w, $k, $s, $same, $memo, $level, $flag, $id);
                $success = '수정되었습니다.';
            } else {
                $stmt = $wordDb->prepare('INSERT INTO word_table (words, kwords, sounds, same, memo, level, useflag) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('sssssis', $w, $k, $s, $same, $memo, $level, $flag);
                $success = '등록되었습니다.';
            }
            if ($stmt->execute()) wordGo($success);
            $wordError = '저장하지 못했습니다: ' . $stmt->error;
            $stmt->close();
        }
    }
}

if ($wordTab === 'word' && $_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['edit'])) {
    $editIdx = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($editIdx !== false && $editIdx !== null) {
        $stmt = $wordDb->prepare('SELECT idx, words, kwords, sounds, same, memo, level, useflag FROM word_table WHERE idx=?');
        $stmt->bind_param('i', $editIdx);
        $stmt->execute();
        $wordEdit = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($wordEdit) $wordInput = $wordEdit;
        else $wordError = '수정할 어휘를 찾을 수 없습니다.';
    }
}

$wordRows = [];
if ($wordTab === 'word') {
    $rs = $wordDb->query('SELECT idx, words, kwords, useflag, level FROM word_table ORDER BY level asc, kwords asc');
    if ($rs) {
        while ($row = $rs->fetch_assoc()) $wordRows[] = $row;
        $rs->free();
    } else {
        $wordError = '목록 조회 중 오류가 발생했습니다: ' . $wordDb->error;
    }
}
?>
<style>
.word-admin {max-width:1100px; margin:24px auto; font-family:Arial,'Malgun Gothic',sans-serif; color:#253044;}
.word-admin * {box-sizing:border-box;}
.word-admin h2 {font-size:23px;margin:0 0 20px;}
.word-tabs {display:flex;gap:0;border-bottom:2px solid #d5dce7;margin-bottom:22px;}
.word-tabs a {display:block;padding:13px 25px;text-decoration:none;color:#526176;font-weight:600;}
.word-tabs a.active {color:#225ed8;border-bottom:3px solid #225ed8;margin-bottom:-2px;}
.word-panel {border:1px solid #dce2ec;border-radius:9px;padding:22px;margin-bottom:25px;background:#fff;}
.word-panel h3 {margin:0 0 18px;font-size:18px;}
.word-grid {display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:17px 22px;}
.word-field label {display:block;font-size:14px;font-weight:600;margin-bottom:6px;}
.word-field input,.word-field select,.word-field textarea {width:100%;padding:10px 11px;border:1px solid #cbd3df;border-radius:5px;font:inherit;background:#fff;}
.word-field textarea {min-height:86px;resize:vertical;}
.word-wide {grid-column:1/-1;}
.word-help {display:block;color:#778295;font-size:12px;margin-top:5px;}
.word-actions {display:flex;gap:9px;margin-top:20px;}
.word-btn {display:inline-block;border:0;border-radius:5px;padding:9px 16px;font-size:13px;cursor:pointer;text-decoration:none;background:#235ed4;color:#fff;}
.word-btn.secondary {background:#728096;}
.word-btn.danger {background:#cc4050;}
.word-btn.small {padding:7px 11px;font-size:12px;}
.word-table-wrap {width:100%;overflow-x:auto;}
.word-table {width:100%;border-collapse:collapse;min-width:550px;}
.word-table th,.word-table td {padding:12px 10px;border-bottom:1px solid #e5e9ef;text-align:center;}
.word-table th {background:#f2f5f9;font-weight:600;}
.word-table td.words {font-size:18px;font-weight:600;}
.word-row-actions {display:flex;gap:6px;justify-content:center;align-items:center;}
.word-row-actions form {margin:0;}
.word-error {background:#fff0f2;color:#ac2233;padding:12px 14px;border-radius:5px;margin-bottom:15px;}
.word-success {background:#eff7f1;color:#24703e;padding:12px 14px;border-radius:5px;margin-bottom:15px;}
.word-empty {text-align:center;color:#778295;padding:35px 0;}
@media(max-width:640px){.word-grid{grid-template-columns:1fr}.word-tabs a{flex:1;text-align:center;padding:12px 6px}.word-panel{padding:15px}}
</style>

<section class="word-admin">
    <h2>고사성어 관리</h2>
    <nav class="word-tabs" aria-label="고사성어 관리 메뉴">
        <a href="index.php?cmd=manWord&amp;tab=word" class="<?= $wordTab === 'word' ? 'active' : '' ?>">1. 어휘관리</a>
        <a href="index.php?cmd=manWord&amp;tab=stats" class="<?= $wordTab === 'stats' ? 'active' : '' ?>">2. 통계</a>
        <a href="index.php?cmd=manWord&amp;tab=test" class="<?= $wordTab === 'test' ? 'active' : '' ?>">3. 테스트</a>
    </nav>

    <?php if ($wordError !== ''): ?><div class="word-error"><?= wordEsc($wordError) ?></div><?php endif; ?>
    <?php if (isset($_GET['msg']) && is_string($_GET['msg'])): ?><div class="word-success"><?= wordEsc($_GET['msg']) ?></div><?php endif; ?>

    <?php if ($wordTab === 'word'): ?>
    <div class="word-panel" id="word-form">
        <h3><?= (int)$wordInput['idx'] > 0 ? '고사성어 수정' : '고사성어 등록' ?></h3>
        <form action="index.php?cmd=manWord&amp;tab=word" method="post">
            <input type="hidden" name="word_action" value="save">
            <input type="hidden" name="csrf_token" value="<?= wordEsc($wordCsrf) ?>">
            <input type="hidden" name="idx" value="<?= (int)$wordInput['idx'] ?>">
            <div class="word-grid">
                <div class="word-field">
                    <label for="word-words">고사성어 한자 *</label>
                    <input id="word-words" name="words" maxlength="50" required placeholder="竹馬故友" value="<?= wordEsc($wordInput['words']) ?>">
                </div>
                <div class="word-field">
                    <label for="word-kwords">한글 독음 *</label>
                    <input id="word-kwords" name="kwords" maxlength="100" required placeholder="죽마고우" value="<?= wordEsc($wordInput['kwords']) ?>">
                </div>
                <div class="word-field word-wide">
                    <label for="word-sounds">각 한자의 훈과 음</label>
                    <input id="word-sounds" name="sounds" maxlength="255" placeholder="대 죽,말 마,옛 고,벗 우" value="<?= wordEsc($wordInput['sounds']) ?>">
                    <span class="word-help">한자 순서대로 쉼표(,)로 구분해 입력합니다.</span>
                </div>
                <div class="word-field word-wide">
                    <label for="word-same">유사한 고사성어</label>
                    <input id="word-same" name="same" placeholder="관포지교,지음,수어지교" value="<?= wordEsc($wordInput['same']) ?>">
                    <span class="word-help">여러 개를 쉼표(,)로 구분해 입력합니다.</span>
                </div>
                <div class="word-field word-wide">
                    <label for="word-memo">설명</label>
                    <textarea id="word-memo" name="memo" placeholder="고사성어의 뜻과 유래를 입력하세요."><?= wordEsc($wordInput['memo']) ?></textarea>
                </div>
                <div class="word-field">
                    <label for="word-level">난이도</label>
                    <select id="word-level" name="level">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= (int)$wordInput['level'] === $i ? 'selected' : '' ?>><?= $i ?>단계</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="word-field">
                    <label for="word-useflag">사용 여부</label>
                    <select id="word-useflag" name="useflag">
                        <option value="Y" <?= $wordInput['useflag'] === 'Y' ? 'selected' : '' ?>>사용(Y)</option>
                        <option value="N" <?= $wordInput['useflag'] === 'N' ? 'selected' : '' ?>>미사용(N)</option>
                    </select>
                </div>
            </div>
            <div class="word-actions">
                <button class="word-btn" type="submit"><?= (int)$wordInput['idx'] > 0 ? '수정 저장' : '등록' ?></button>
                <?php if ((int)$wordInput['idx'] > 0): ?>
                    <a class="word-btn secondary" href="index.php?cmd=manWord&amp;tab=word">취소 / 신규 등록</a>
                <?php else: ?>
                    <button class="word-btn secondary" type="reset">입력 초기화</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="word-panel">
        <h3>등록된 어휘 목록 (총 <?= count($wordRows) ?>건)</h3>
        <div class="word-table-wrap"><table class="word-table">
            <thead><tr><th>idx</th><th>words</th><th>kwords</th><th>useflag</th><th>level</th><th>비고</th></tr></thead>
            <tbody>
            <?php if (count($wordRows) === 0): ?>
                <tr><td colspan="5" class="word-empty">등록된 고사성어가 없습니다.</td></tr>
            <?php else: foreach ($wordRows as $row): ?>
                <tr>
                    <td><?= (int)$row['idx'] ?></td>
                    <td class="words"><?= wordEsc($row['words']) ?></td>
                    <td><?= wordEsc($row['kwords']) ?></td>
                    <td><?= $row['useflag'] === 'Y' ? '사용' : '미사용' ?></td>
                    <td><?= $row['level'] ?></td>
                    <td><div class="word-row-actions">
                        <a class="word-btn small" href="index.php?cmd=manWord&amp;tab=word&amp;edit=<?= (int)$row['idx'] ?>#word-form">수정</a>
                        <form method="post" action="index.php?cmd=manWord&amp;tab=word" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                            <input type="hidden" name="word_action" value="delete">
                            <input type="hidden" name="csrf_token" value="<?= wordEsc($wordCsrf) ?>">
                            <input type="hidden" name="idx" value="<?= (int)$row['idx'] ?>">
                            <button type="submit" class="word-btn danger small">삭제</button>
                        </form>
                    </div></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table></div>
    </div>
    <?php elseif ($wordTab === 'stats'): ?>
        <div class="word-panel"><h3>통계</h3><p>통계 기능을 추가할 수 있도록 준비된 탭입니다.</p></div>
    <?php else: ?>
        <div class="word-panel"><h3>테스트</h3><p>고사성어 테스트 기능을 추가할 수 있도록 준비된 탭입니다.</p></div>
    <?php endif; ?>
</section>
