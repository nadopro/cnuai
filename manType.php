<?php
/**
 * manType.php
 * Usage: index.php?cmd=manType
 * Database: type_table(idx, title, useflag, memo)
 * Requires an existing mysqli connection in $conn (or $mysqli) from index.php.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('mt_escape')) {
    function mt_escape($value): string {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

$mtBase = 'index.php?cmd=manType';
$mtTab = isset($_GET['tab']) && in_array($_GET['tab'], ['type', 'question', 'test'], true)
    ? $_GET['tab'] : 'type';
$mtError = '';
$mtMessage = '';
$mtEdit = ['idx' => 0, 'title' => '', 'useflag' => 'Y', 'memo' => ''];
$mtRows = [];

if (empty($_SESSION['manType_csrf'])) {
    $_SESSION['manType_csrf'] = bin2hex(random_bytes(32));
}
$mtCsrf = $_SESSION['manType_csrf'];

$mtDb = null;
if (isset($conn) && $conn instanceof mysqli) {
    $mtDb = $conn;
} elseif (isset($mysqli) && $mysqli instanceof mysqli) {
    $mtDb = $mysqli;
}

if (!$mtDb) {
    $mtError = 'DB 연결을 찾을 수 없습니다. index.php에서 mysqli 연결 객체를 $conn 또는 $mysqli 변수로 제공해주세요.';
} else {
    $mtDb->set_charset('utf8mb4');

    // All modifications are handled via POST and prepared statements.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $mtAction = (string) ($_POST['action'] ?? '');
        $mtPostedToken = (string) ($_POST['csrf_token'] ?? '');

        if (!hash_equals($mtCsrf, $mtPostedToken)) {
            $mtError = '요청 인증에 실패했습니다. 페이지를 새로고침한 뒤 다시 시도해주세요.';
        } elseif ($mtAction === 'save') {
            $mtIdx = filter_var($_POST['idx'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            $mtIdx = $mtIdx === false ? -1 : $mtIdx;
            $mtTitle = trim((string) ($_POST['title'] ?? ''));
            $mtUseflag = (string) ($_POST['useflag'] ?? 'Y');
            $mtMemo = trim((string) ($_POST['memo'] ?? ''));
            $mtEdit = ['idx' => $mtIdx, 'title' => $mtTitle, 'useflag' => $mtUseflag, 'memo' => $mtMemo];

            if ($mtIdx < 0 || $mtTitle === '') {
                $mtError = '유형명을 입력해주세요.';
            } elseif (mb_strlen($mtTitle, 'UTF-8') > 100) {
                $mtError = '유형명은 100자 이내로 입력해주세요.';
            } elseif (!in_array($mtUseflag, ['Y', 'N'], true)) {
                $mtError = '사용 여부가 올바르지 않습니다.';
            } else {
                try {
                    if ($mtIdx > 0) {
                        $mtStmt = $mtDb->prepare('UPDATE type_table SET title = ?, useflag = ?, memo = ? WHERE idx = ?');
                        $mtStmt->bind_param('sssi', $mtTitle, $mtUseflag, $mtMemo, $mtIdx);
                    } else {
                        $mtStmt = $mtDb->prepare('INSERT INTO type_table (title, useflag, memo) VALUES (?, ?, ?)');
                        $mtStmt->bind_param('sss', $mtTitle, $mtUseflag, $mtMemo);
                    }
                    $mtStmt->execute();
                    $mtAffected = $mtStmt->affected_rows;
                    $mtStmt->close();
                    if ($mtIdx > 0 && $mtAffected === 0) {
                        // 0 also means that data is identical; verify that target exists.
                        $mtCheck = $mtDb->prepare('SELECT idx FROM type_table WHERE idx = ?');
                        $mtCheck->bind_param('i', $mtIdx);
                        $mtCheck->execute();
                        $mtExists = $mtCheck->get_result()->fetch_assoc();
                        $mtCheck->close();
                        if (!$mtExists) {
                            throw new RuntimeException('수정할 유형을 찾을 수 없습니다.');
                        }
                    }
                    $_SESSION['manType_notice'] = $mtIdx > 0 ? '유형을 수정했습니다.' : '유형을 등록했습니다.';
                    $mtRedirect = $mtBase . '&tab=type';
                    if (!headers_sent()) {
                        header('Location: ' . $mtRedirect, true, 303);
                    } else {
                        echo '<script>location.replace(' . json_encode($mtRedirect) . ');</script>';
                    }
                    exit;
                } catch (Throwable $e) {
                    $mtError = '저장 중 오류가 발생했습니다: ' . $e->getMessage();
                }
            }
        } elseif ($mtAction === 'delete') {
            $mtIdx = filter_var($_POST['idx'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($mtIdx === false || $mtIdx === null) {
                $mtError = '삭제할 유형 번호가 올바르지 않습니다.';
            } else {
                try {
                    $mtStmt = $mtDb->prepare('DELETE FROM type_table WHERE idx = ?');
                    $mtStmt->bind_param('i', $mtIdx);
                    $mtStmt->execute();
                    $mtDeleted = $mtStmt->affected_rows;
                    $mtStmt->close();
                    $_SESSION['manType_notice'] = $mtDeleted ? '유형을 삭제했습니다.' : '이미 삭제되었거나 존재하지 않는 유형입니다.';
                    $mtRedirect = $mtBase . '&tab=type';
                    if (!headers_sent()) {
                        header('Location: ' . $mtRedirect, true, 303);
                    } else {
                        echo '<script>location.replace(' . json_encode($mtRedirect) . ');</script>';
                    }
                    exit;
                } catch (Throwable $e) {
                    $mtError = '삭제할 수 없습니다. 다른 데이터에서 이 유형을 사용 중인지 확인해주세요. (' . $e->getMessage() . ')';
                }
            }
        }
        $mtTab = 'type';
    }

    if ($mtTab === 'type') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['edit'])) {
            $mtEditId = filter_var($_GET['edit'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($mtEditId !== false) {
                $mtStmt = $mtDb->prepare('SELECT idx, title, useflag, memo FROM type_table WHERE idx = ?');
                $mtStmt->bind_param('i', $mtEditId);
                $mtStmt->execute();
                $mtFound = $mtStmt->get_result()->fetch_assoc();
                $mtStmt->close();
                if ($mtFound) {
                    $mtEdit = $mtFound;
                } else {
                    $mtError = '선택한 유형을 찾을 수 없습니다.';
                }
            }
        }
        try {
            $mtResult = $mtDb->query('SELECT idx, title, useflag, memo FROM type_table ORDER BY idx DESC');
            $mtRows = $mtResult->fetch_all(MYSQLI_ASSOC);
            $mtResult->free();
        } catch (Throwable $e) {
            $mtError = '유형 목록을 불러오지 못했습니다: ' . $e->getMessage();
        }
    }
}
if (isset($_SESSION['manType_notice'])) {
    $mtMessage = (string) $_SESSION['manType_notice'];
    unset($_SESSION['manType_notice']);
}
?>
<style>
.mt-wrap{max-width:1100px;margin:20px auto 40px;font-family:Arial,'Malgun Gothic',sans-serif;color:#1e293b;font-size:14px}
.mt-wrap *{box-sizing:border-box}
.mt-tabs{display:flex;gap:4px;border-bottom:2px solid #dbe3ed;margin-bottom:24px}
.mt-tab{display:inline-block;padding:13px 24px;text-decoration:none;color:#475569;font-weight:600;border-radius:8px 8px 0 0}
.mt-tab:hover{background:#f1f5f9}
.mt-tab.active{color:#1358b6;background:#eaf2ff;border-bottom:3px solid #1358b6;margin-bottom:-2px}
.mt-panel{border:1px solid #dce4ed;border-radius:10px;background:#fff;padding:24px;margin-bottom:22px}
.mt-heading{font-size:18px;font-weight:700;margin:0 0 20px}
.mt-field{margin-bottom:16px}
.mt-label{display:block;margin-bottom:7px;font-weight:600}
.mt-input,.mt-select,.mt-textarea{width:100%;border:1px solid #cbd5e1;border-radius:6px;padding:10px 12px;background:#fff;font:inherit}
.mt-textarea{min-height:92px;resize:vertical}
.mt-input:focus,.mt-select:focus,.mt-textarea:focus{outline:2px solid #b9d5ff;border-color:#4384da}
.mt-actions{display:flex;gap:8px;align-items:center;margin-top:16px}
.mt-btn{border:0;border-radius:6px;padding:9px 16px;cursor:pointer;text-decoration:none;display:inline-block;font:inherit;text-align:center}
.mt-primary{background:#175ec0;color:#fff}.mt-neutral{background:#e2e8f0;color:#334155}.mt-danger{background:#dc3545;color:#fff}
.mt-small{padding:6px 11px;font-size:13px}
.mt-notice{padding:12px 15px;border-radius:7px;margin-bottom:18px;background:#eaf7ef;color:#166534}
.mt-error{padding:12px 15px;border-radius:7px;margin-bottom:18px;background:#fff1f2;color:#9f1239}
.mt-tablebox{width:100%;overflow-x:auto}
.mt-table{width:100%;border-collapse:collapse;min-width:650px}
.mt-table th,.mt-table td{padding:12px;border-bottom:1px solid #e2e8f0;text-align:left;vertical-align:middle}
.mt-table th{background:#f3f6fa;font-weight:700;white-space:nowrap}
.mt-table td.mt-center,.mt-table th.mt-center{text-align:center}
.mt-table .mt-memo{white-space:pre-wrap;overflow-wrap:anywhere}
.mt-table .mt-ops{display:flex;gap:6px;justify-content:center;align-items:center}
.mt-table .mt-ops form{margin:0}
.mt-status{display:inline-block;padding:3px 9px;border-radius:20px;font-size:12px;background:#e6f6e9;color:#166534}
.mt-status.off{background:#f1f5f9;color:#64748b}
.mt-empty{text-align:center!important;color:#64748b;padding:25px!important}
@media(max-width:600px){.mt-panel{padding:16px}.mt-tab{flex:1;text-align:center;padding:12px 7px}.mt-wrap{margin:12px auto}}
</style>
<div class="mt-wrap">
    <nav class="mt-tabs" aria-label="관리 메뉴">
        <a class="mt-tab <?= $mtTab === 'type' ? 'active' : '' ?>" href="<?= mt_escape($mtBase) ?>&amp;tab=type">유형관리</a>
        <a class="mt-tab <?= $mtTab === 'question' ? 'active' : '' ?>" href="<?= mt_escape($mtBase) ?>&amp;tab=question">질문관리</a>
        <a class="mt-tab <?= $mtTab === 'test' ? 'active' : '' ?>" href="<?= mt_escape($mtBase) ?>&amp;tab=test">테스트</a>
    </nav>

    <?php if ($mtMessage !== ''): ?><div class="mt-notice" role="status"><?= mt_escape($mtMessage) ?></div><?php endif; ?>
    <?php if ($mtError !== ''): ?><div class="mt-error" role="alert"><?= mt_escape($mtError) ?></div><?php endif; ?>

    <?php if ($mtTab === 'type'): ?>
        <section class="mt-panel">
            <h2 class="mt-heading"><?= (int) $mtEdit['idx'] > 0 ? '유형 수정' : '유형 등록' ?></h2>
            <form method="post" action="<?= mt_escape($mtBase) ?>&amp;tab=type">
                <input type="hidden" name="csrf_token" value="<?= mt_escape($mtCsrf) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="idx" value="<?= (int) $mtEdit['idx'] ?>">
                <div class="mt-field">
                    <label class="mt-label" for="mt-title">유형명 <span style="color:#dc3545">*</span></label>
                    <input class="mt-input" type="text" name="title" id="mt-title" maxlength="100" required value="<?= mt_escape($mtEdit['title']) ?>" placeholder="유형명을 입력하세요">
                </div>
                <div class="mt-field">
                    <label class="mt-label" for="mt-useflag">사용 여부</label>
                    <select class="mt-select" name="useflag" id="mt-useflag">
                        <option value="Y" <?= $mtEdit['useflag'] === 'Y' ? 'selected' : '' ?>>사용 (Y)</option>
                        <option value="N" <?= $mtEdit['useflag'] === 'N' ? 'selected' : '' ?>>미사용 (N)</option>
                    </select>
                </div>
                <div class="mt-field">
                    <label class="mt-label" for="mt-memo">메모</label>
                    <textarea class="mt-textarea" name="memo" id="mt-memo" placeholder="필요한 설명을 입력하세요"><?= mt_escape($mtEdit['memo']) ?></textarea>
                </div>
                <div class="mt-actions">
                    <button class="mt-btn mt-primary" type="submit" <?= !$mtDb ? 'disabled' : '' ?>><?= (int) $mtEdit['idx'] > 0 ? '수정 저장' : '등록' ?></button>
                    <?php if ((int) $mtEdit['idx'] > 0): ?><a class="mt-btn mt-neutral" href="<?= mt_escape($mtBase) ?>&amp;tab=type">취소 / 신규 등록</a><?php endif; ?>
                </div>
            </form>
        </section>
        <section class="mt-panel">
            <h2 class="mt-heading">전체 유형 목록 (<?= count($mtRows) ?>건)</h2>
            <div class="mt-tablebox">
                <table class="mt-table">
                    <thead><tr><th class="mt-center" style="width:80px">번호</th><th>유형명</th><th class="mt-center" style="width:110px">사용 여부</th><th>메모</th><th class="mt-center" style="width:160px">관리</th></tr></thead>
                    <tbody>
                    <?php if (!$mtRows): ?>
                        <tr><td class="mt-empty" colspan="5">등록된 유형이 없습니다.</td></tr>
                    <?php else: foreach ($mtRows as $mtRow): ?>
                        <tr>
                            <td class="mt-center"><?= (int) $mtRow['idx'] ?></td>
                            <td><?= mt_escape($mtRow['title']) ?></td>
                            <td class="mt-center"><span class="mt-status <?= $mtRow['useflag'] === 'Y' ? '' : 'off' ?>"><?= $mtRow['useflag'] === 'Y' ? '사용' : '미사용' ?></span></td>
                            <td class="mt-memo"><?= mt_escape($mtRow['memo'] ?? '') ?></td>
                            <td><div class="mt-ops">
                                <a class="mt-btn mt-primary mt-small" href="<?= mt_escape($mtBase) ?>&amp;tab=type&amp;edit=<?= (int) $mtRow['idx'] ?>">수정</a>
                                <form method="post" action="<?= mt_escape($mtBase) ?>&amp;tab=type" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                    <input type="hidden" name="csrf_token" value="<?= mt_escape($mtCsrf) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="idx" value="<?= (int) $mtRow['idx'] ?>">
                                    <button class="mt-btn mt-danger mt-small" type="submit">삭제</button>
                                </form>
                            </div></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php elseif ($mtTab === 'question'): ?>
        <section class="mt-panel"><h2 class="mt-heading">질문관리</h2><p>질문관리 기능은 추후 연결할 수 있도록 탭을 준비했습니다.</p></section>
    <?php else: ?>
        <section class="mt-panel"><h2 class="mt-heading">테스트</h2><p>테스트 기능은 추후 연결할 수 있도록 탭을 준비했습니다.</p></section>
    <?php endif; ?>
</div>
