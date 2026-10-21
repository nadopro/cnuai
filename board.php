<?php
if (!isset($conn) || !($conn instanceof mysqli)) {
    die('DB 연결 정보가 없습니다.');
}

$bid = isset($_GET['bid']) ? (int)$_GET['bid'] : 1;
$allowedBids = [1, 2, 3];
if (!in_array($bid, $allowedBids, true)) {
    $bid = 1;
}

$boardNames = [
    1 => '공지사항',
    2 => '자유게시판',
    3 => '학습게시판'
];

$mode = $_GET['mode'] ?? 'list';
$allowedModes = ['list', 'write', 'show', 'edit'];
if (!in_array($mode, $allowedModes, true)) {
    $mode = 'list';
}

$isLogin = isset($_SESSION['user_id']) && $_SESSION['user_id'] !== '';
$loginId = $isLogin ? (string)$_SESSION['user_id'] : '';
$isAdmin = isset($_SESSION['level']) && (int)$_SESSION['level'] === 9;

if (empty($_SESSION['board_csrf'])) {
    $_SESSION['board_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['board_csrf'];

$message = '';
$messageType = 'success';

function boardH($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function boardUrl(array $params = []): string {
    return 'index.php?' . http_build_query(array_merge(['cmd' => 'board'], $params));
}

function boardCheckCsrf(): bool {
    return isset($_POST['csrf_token'], $_SESSION['board_csrf'])
        && hash_equals($_SESSION['board_csrf'], (string)$_POST['csrf_token']);
}

function boardUploadImage(array $file, int $bid): ?string {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('파일 업로드 중 오류가 발생했습니다.');
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('올바른 업로드 파일이 아닙니다.');
    }

    if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
        throw new RuntimeException('첨부 이미지는 10MB 이하만 가능합니다.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    if (!isset($extensions[$mime])) {
        throw new RuntimeException('JPG, PNG, GIF, WEBP 이미지만 첨부할 수 있습니다.');
    }

    $relativeDir = 'data/board/' . $bid;
    $absoluteDir = __DIR__ . '/' . $relativeDir;

    if (!is_dir($absoluteDir)) {
        if (!mkdir($absoluteDir, 0777, true) && !is_dir($absoluteDir)) {
            throw new RuntimeException('첨부파일 저장 폴더를 만들 수 없습니다.');
        }
    }

    $extension = $extensions[$mime];
    $baseName = date('YmdHis');
    $fileName = $baseName . '.' . $extension;
    $absolutePath = $absoluteDir . '/' . $fileName;

    $seq = 1;
    while (file_exists($absolutePath)) {
        $fileName = $baseName . '_' . $seq . '.' . $extension;
        $absolutePath = $absoluteDir . '/' . $fileName;
        $seq++;
    }

    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        throw new RuntimeException('첨부파일 저장에 실패했습니다.');
    }

    return $relativeDir . '/' . $fileName;
}

function boardDeleteAttachment(?string $path): void {
    if (!$path) return;

    $normalized = str_replace('\\', '/', $path);
    if (strpos($normalized, 'data/board/') !== 0) return;

    $absolutePath = __DIR__ . '/' . $normalized;
    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!boardCheckCsrf()) {
        $message = '잘못된 요청입니다. 다시 시도해 주세요.';
        $messageType = 'danger';
    } else {
        $action = $_POST['board_action'] ?? '';

        if ($action === 'insert') {
            if (!$isLogin) {
                $message = '로그인한 회원만 글을 작성할 수 있습니다.';
                $messageType = 'danger';
                $mode = 'list';
            } else {
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                $isNotice = ($isAdmin && isset($_POST['is_notice'])) ? 1 : 0;
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $attachment = null;

                if ($title === '' || $content === '') {
                    $message = '제목과 내용을 입력하세요.';
                    $messageType = 'danger';
                    $mode = 'write';
                } else {
                    try {
                        $attachment = boardUploadImage($_FILES['attachment'] ?? [], $bid);

                        $stmt = $conn->prepare(
                            "INSERT INTO board
                            (bid, title, is_notice, created_at, ip, writer_id, content, attachment, hit)
                            VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, 0)"
                        );
                        $stmt->bind_param(
                            'isissss',
                            $bid, $title, $isNotice, $ip, $loginId, $content, $attachment
                        );
                        $stmt->execute();
                        $newIdx = $stmt->insert_id;
                        $stmt->close();

                        header('Location: ' . boardUrl([
                            'bid' => $bid,
                            'mode' => 'show',
                            'idx' => $newIdx
                        ]));
                        exit;
                    } catch (Throwable $e) {
                        if ($attachment) boardDeleteAttachment($attachment);
                        $message = $e->getMessage();
                        $messageType = 'danger';
                        $mode = 'write';
                    }
                }
            }
        }

        elseif ($action === 'update') {
            $idx = (int)($_POST['idx'] ?? 0);

            $stmt = $conn->prepare(
                "SELECT writer_id, attachment
                 FROM board
                 WHERE idx = ? AND bid = ?
                 LIMIT 1"
            );
            $stmt->bind_param('ii', $idx, $bid);
            $stmt->execute();
            $old = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$old) {
                $message = '수정할 글을 찾을 수 없습니다.';
                $messageType = 'danger';
                $mode = 'list';
            } elseif (!$isLogin || ($loginId !== $old['writer_id'] && !$isAdmin)) {
                $message = '수정 권한이 없습니다.';
                $messageType = 'danger';
                $mode = 'list';
            } else {
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                $isNotice = ($isAdmin && isset($_POST['is_notice'])) ? 1 : 0;

                if ($title === '' || $content === '') {
                    $message = '제목과 내용을 입력하세요.';
                    $messageType = 'danger';
                    $mode = 'edit';
                    $_GET['idx'] = $idx;
                } else {
                    $newAttachment = null;

                    try {
                        $newAttachment = boardUploadImage($_FILES['attachment'] ?? [], $bid);
                        $attachment = $newAttachment ?: $old['attachment'];

                        $stmt = $conn->prepare(
                            "UPDATE board
                             SET title = ?, content = ?, is_notice = ?, attachment = ?
                             WHERE idx = ? AND bid = ?"
                        );
                        $stmt->bind_param(
                            'ssisii',
                            $title, $content, $isNotice, $attachment, $idx, $bid
                        );
                        $stmt->execute();
                        $stmt->close();

                        if ($newAttachment && $old['attachment']) {
                            boardDeleteAttachment($old['attachment']);
                        }

                        header('Location: ' . boardUrl([
                            'bid' => $bid,
                            'mode' => 'show',
                            'idx' => $idx
                        ]));
                        exit;

                    } catch (Throwable $e) {
                        if ($newAttachment) boardDeleteAttachment($newAttachment);
                        $message = $e->getMessage();
                        $messageType = 'danger';
                        $mode = 'edit';
                        $_GET['idx'] = $idx;
                    }
                }
            }
        }

        elseif ($action === 'delete') {
            $idx = (int)($_POST['idx'] ?? 0);

            $stmt = $conn->prepare(
                "SELECT writer_id, attachment
                 FROM board
                 WHERE idx = ? AND bid = ?
                 LIMIT 1"
            );
            $stmt->bind_param('ii', $idx, $bid);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row) {
                $message = '삭제할 글을 찾을 수 없습니다.';
                $messageType = 'danger';
            } elseif (!$isLogin || ($loginId !== $row['writer_id'] && !$isAdmin)) {
                $message = '삭제 권한이 없습니다.';
                $messageType = 'danger';
            } else {
                $stmt = $conn->prepare("DELETE FROM board WHERE idx = ? AND bid = ?");
                $stmt->bind_param('ii', $idx, $bid);
                $stmt->execute();
                $stmt->close();

                boardDeleteAttachment($row['attachment']);

                header('Location: ' . boardUrl(['bid' => $bid]));
                exit;
            }

            $mode = 'list';
        }
    }
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><?php echo boardH($boardNames[$bid]); ?></h2>
    </div>

    <?php if ($message !== '') { ?>
        <div class="alert alert-<?php echo boardH($messageType); ?>">
            <?php echo boardH($message); ?>
        </div>
    <?php } ?>

<?php if ($mode === 'list') {

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 10;

    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM board WHERE bid = ?");
    $stmt->bind_param('i', $bid);
    $stmt->execute();
    $totalRows = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    if ($page > $totalPages) $page = $totalPages;

    $offset = ($page - 1) * $perPage;

    $stmt = $conn->prepare(
        "SELECT
            b.idx,
            b.title,
            b.is_notice,
            b.created_at,
            b.writer_id,
            b.attachment,
            b.hit,
            COALESCE(m.name, b.writer_id) AS writer_name
         FROM board b
         LEFT JOIN members m ON b.writer_id = m.id
         WHERE b.bid = ?
         ORDER BY b.is_notice DESC, b.idx DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param('iii', $bid, $perPage, $offset);
    $stmt->execute();
    $rows = $stmt->get_result();
?>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr class="text-center">
                    <th class="d-none d-md-table-cell" style="width:80px;">글번호</th>
                    <th>제목</th>
                    <th class="d-none d-md-table-cell" style="width:70px;">첨부</th>
                    <th style="width:120px;">작성자</th>
                    <th class="d-none d-md-table-cell" style="width:170px;">작성일</th>
                    <th class="d-none d-md-table-cell" style="width:80px;">읽음</th>
                </tr>
            </thead>
            <tbody>

            <?php if ($rows->num_rows === 0) { ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-secondary">
                        등록된 글이 없습니다.
                    </td>
                </tr>
            <?php } else { ?>

                <?php while ($row = $rows->fetch_assoc()) { ?>
                    <tr>
                        <td class="d-none d-md-table-cell text-center">
                            <?php if ((int)$row['is_notice'] === 1) { ?>
                                <span class="badge bg-danger">공지</span>
                            <?php } else { ?>
                                <?php echo (int)$row['idx']; ?>
                            <?php } ?>
                        </td>

                        <td>
                            <?php if ((int)$row['is_notice'] === 1) { ?>
                                <span class="badge bg-danger me-1 d-md-none">공지</span>
                            <?php } ?>
                            <a href="<?php echo boardH(boardUrl([
                                'bid' => $bid,
                                'mode' => 'show',
                                'idx' => $row['idx'],
                                'page' => $page
                            ])); ?>" class="text-decoration-none">
                                <?php echo boardH($row['title']); ?>
                            </a>
                        </td>

                        <td class="d-none d-md-table-cell text-center">
                            <?php echo $row['attachment'] ? '📎' : ''; ?>
                        </td>

                        <td class="text-center">
                            <?php echo boardH($row['writer_name']); ?>
                        </td>

                        <td class="d-none d-md-table-cell text-center">
                            <?php echo boardH(date('Y-m-d H:i', strtotime($row['created_at']))); ?>
                        </td>

                        <td class="d-none d-md-table-cell text-center">
                            <?php echo (int)$row['hit']; ?>
                        </td>
                    </tr>
                <?php } ?>

            <?php } ?>

            </tbody>
        </table>
    </div>

<?php
    $stmt->close();

    $pageBlock = 5;
    $startPage = (int)(floor(($page - 1) / $pageBlock) * $pageBlock) + 1;
    $endPage = min($startPage + $pageBlock - 1, $totalPages);
?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">

        <div>
            <?php if ($isLogin) { ?>
                <a href="<?php echo boardH(boardUrl([
                    'bid' => $bid,
                    'mode' => 'write'
                ])); ?>" class="btn btn-primary">
                    글쓰기
                </a>
            <?php } else { ?>
                <button class="btn btn-secondary" type="button" disabled>글쓰기</button>
                <span class="small text-secondary ms-2">
                    로그인 후 작성할 수 있습니다.
                </span>
            <?php } ?>
        </div>

        <nav aria-label="게시판 페이지">
            <ul class="pagination mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link"
                       href="<?php echo boardH(boardUrl([
                           'bid' => $bid,
                           'page' => max(1, $page - 1)
                       ])); ?>">
                        이전
                    </a>
                </li>

                <?php for ($p = $startPage; $p <= $endPage; $p++) { ?>
                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                        <a class="page-link"
                           href="<?php echo boardH(boardUrl([
                               'bid' => $bid,
                               'page' => $p
                           ])); ?>">
                            <?php echo $p; ?>
                        </a>
                    </li>
                <?php } ?>

                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link"
                       href="<?php echo boardH(boardUrl([
                           'bid' => $bid,
                           'page' => min($totalPages, $page + 1)
                       ])); ?>">
                        다음
                    </a>
                </li>
            </ul>
        </nav>
    </div>

<?php
}

elseif ($mode === 'write') {

    if (!$isLogin) {
?>
        <div class="alert alert-warning">
            로그인한 회원만 글을 작성할 수 있습니다.
        </div>

        <a href="<?php echo boardH(boardUrl(['bid' => $bid])); ?>"
           class="btn btn-secondary">
            목록보기
        </a>
<?php
    } else {
?>

    <form method="post"
          action="<?php echo boardH(boardUrl(['bid' => $bid, 'mode' => 'write'])); ?>"
          enctype="multipart/form-data">

        <input type="hidden" name="board_action" value="insert">
        <input type="hidden" name="csrf_token" value="<?php echo boardH($csrfToken); ?>">

        <?php if ($isAdmin) { ?>
            <div class="form-check mb-3">
                <input class="form-check-input"
                       type="checkbox"
                       name="is_notice"
                       id="is_notice"
                       value="1">
                <label class="form-check-label" for="is_notice">
                    공지글로 등록
                </label>
            </div>
        <?php } ?>

        <div class="mb-3">
            <label for="title" class="form-label">제목</label>
            <input type="text" class="form-control" id="title"
                   name="title" maxlength="255" required>
        </div>

        <div class="mb-3">
            <label for="content" class="form-label">내용</label>
            <textarea class="form-control" id="content"
                      name="content" rows="12" required></textarea>
        </div>

        <div class="mb-3">
            <label for="attachment" class="form-label">첨부 이미지</label>
            <input type="file"
                   class="form-control"
                   id="attachment"
                   name="attachment"
                   accept="image/jpeg,image/png,image/gif,image/webp">
            <div class="form-text">
                JPG, PNG, GIF, WEBP 이미지만 첨부할 수 있습니다.
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">완료</button>
            <a href="<?php echo boardH(boardUrl(['bid' => $bid])); ?>"
               class="btn btn-outline-secondary">
                목록보기
            </a>
        </div>
    </form>

<?php
    }
}

elseif ($mode === 'show') {

    $idx = max(1, (int)($_GET['idx'] ?? 0));
    $page = max(1, (int)($_GET['page'] ?? 1));

    $stmt = $conn->prepare(
        "UPDATE board SET hit = hit + 1 WHERE idx = ? AND bid = ?"
    );
    $stmt->bind_param('ii', $idx, $bid);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare(
        "SELECT b.*, COALESCE(m.name, b.writer_id) AS writer_name
         FROM board b
         LEFT JOIN members m ON b.writer_id = m.id
         WHERE b.idx = ? AND b.bid = ?
         LIMIT 1"
    );
    $stmt->bind_param('ii', $idx, $bid);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$post) {
?>
        <div class="alert alert-danger">글을 찾을 수 없습니다.</div>
        <a href="<?php echo boardH(boardUrl(['bid' => $bid])); ?>"
           class="btn btn-secondary">목록보기</a>
<?php
    } else {

        $prevIdx = null;
        $stmt = $conn->prepare(
            "SELECT idx FROM board
             WHERE bid = ? AND idx < ?
             ORDER BY idx DESC LIMIT 1"
        );
        $stmt->bind_param('ii', $bid, $idx);
        $stmt->execute();
        $tmp = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($tmp) $prevIdx = (int)$tmp['idx'];

        $nextIdx = null;
        $stmt = $conn->prepare(
            "SELECT idx FROM board
             WHERE bid = ? AND idx > ?
             ORDER BY idx ASC LIMIT 1"
        );
        $stmt->bind_param('ii', $bid, $idx);
        $stmt->execute();
        $tmp = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($tmp) $nextIdx = (int)$tmp['idx'];

        $canManage = $isLogin &&
            ($loginId === $post['writer_id'] || $isAdmin);
?>

    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">
                <?php if ((int)$post['is_notice'] === 1) { ?>
                    <span class="badge bg-danger me-2">공지</span>
                <?php } ?>
                <?php echo boardH($post['title']); ?>
            </h4>
        </div>

        <div class="card-body">

            <div class="row g-2 border-bottom pb-3 mb-4 small">
                <div class="col-md-4">
                    <strong>작성자:</strong>
                    <?php echo boardH($post['writer_name']); ?>
                    <span class="text-secondary">
                        (<?php echo boardH($post['writer_id']); ?>)
                    </span>
                </div>

                <div class="col-md-4">
                    <strong>작성일:</strong>
                    <?php echo boardH($post['created_at']); ?>
                </div>

                <div class="col-md-2">
                    <strong>읽음:</strong>
                    <?php echo (int)$post['hit']; ?>
                </div>

                <div class="col-md-2">
                    <strong>IP:</strong>
                    <?php echo boardH($post['ip'] ?? ''); ?>
                </div>
            </div>

            <div class="mb-4"
                 style="min-height:180px; white-space:pre-wrap;"><?php echo boardH($post['content']); ?></div>

            <div class="border-top pt-3">
                <strong>첨부:</strong>

                <?php if ($post['attachment']) { ?>
                    <a href="<?php echo boardH($post['attachment']); ?>"
                       target="_blank">
                        <?php echo boardH(basename($post['attachment'])); ?>
                    </a>

                    <div class="mt-3">
                        <img src="<?php echo boardH($post['attachment']); ?>"
                             alt="첨부 이미지"
                             class="img-fluid rounded border"
                             style="max-height:600px;">
                    </div>
                <?php } else { ?>
                    <span class="text-secondary">첨부파일 없음</span>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between gap-2 mt-3">

        <div class="d-flex flex-wrap gap-2">

            <?php if ($prevIdx) { ?>
                <a href="<?php echo boardH(boardUrl([
                    'bid' => $bid, 'mode' => 'show',
                    'idx' => $prevIdx, 'page' => $page
                ])); ?>"
                   class="btn btn-outline-secondary">
                    이전글
                </a>
            <?php } else { ?>
                <button type="button"
                        class="btn btn-outline-secondary"
                        disabled>
                    이전글
                </button>
            <?php } ?>

            <?php if ($nextIdx) { ?>
                <a href="<?php echo boardH(boardUrl([
                    'bid' => $bid, 'mode' => 'show',
                    'idx' => $nextIdx, 'page' => $page
                ])); ?>"
                   class="btn btn-outline-secondary">
                    다음글
                </a>
            <?php } else { ?>
                <button type="button"
                        class="btn btn-outline-secondary"
                        disabled>
                    다음글
                </button>
            <?php } ?>

            <a href="<?php echo boardH(boardUrl([
                'bid' => $bid,
                'page' => $page
            ])); ?>"
               class="btn btn-secondary">
                목록보기
            </a>
        </div>

        <?php if ($canManage) { ?>
            <div class="d-flex gap-2">

                <a href="<?php echo boardH(boardUrl([
                    'bid' => $bid,
                    'mode' => 'edit',
                    'idx' => $idx,
                    'page' => $page
                ])); ?>"
                   class="btn btn-warning">
                    수정
                </a>

                <form method="post"
                      action="<?php echo boardH(boardUrl([
                          'bid' => $bid,
                          'page' => $page
                      ])); ?>"
                      class="m-0"
                      onsubmit="return confirm('정말 삭제하시겠습니까?');">

                    <input type="hidden" name="board_action" value="delete">
                    <input type="hidden" name="csrf_token"
                           value="<?php echo boardH($csrfToken); ?>">
                    <input type="hidden" name="idx"
                           value="<?php echo $idx; ?>">

                    <button type="submit" class="btn btn-danger">
                        삭제
                    </button>
                </form>

            </div>
        <?php } ?>

    </div>

<?php
    }
}

elseif ($mode === 'edit') {

    $idx = max(1, (int)($_GET['idx'] ?? 0));
    $page = max(1, (int)($_GET['page'] ?? 1));

    $stmt = $conn->prepare(
        "SELECT * FROM board
         WHERE idx = ? AND bid = ?
         LIMIT 1"
    );
    $stmt->bind_param('ii', $idx, $bid);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$post) {
?>
        <div class="alert alert-danger">글을 찾을 수 없습니다.</div>
<?php
    } elseif (!$isLogin || ($loginId !== $post['writer_id'] && !$isAdmin)) {
?>
        <div class="alert alert-danger">수정 권한이 없습니다.</div>
<?php
    } else {
?>

    <form method="post"
          action="<?php echo boardH(boardUrl([
              'bid' => $bid,
              'mode' => 'edit',
              'idx' => $idx,
              'page' => $page
          ])); ?>"
          enctype="multipart/form-data">

        <input type="hidden" name="board_action" value="update">
        <input type="hidden" name="csrf_token"
               value="<?php echo boardH($csrfToken); ?>">
        <input type="hidden" name="idx" value="<?php echo $idx; ?>">

        <?php if ($isAdmin) { ?>
            <div class="form-check mb-3">
                <input class="form-check-input"
                       type="checkbox"
                       name="is_notice"
                       id="is_notice"
                       value="1"
                       <?php echo (int)$post['is_notice'] === 1 ? 'checked' : ''; ?>>
                <label class="form-check-label" for="is_notice">
                    공지글
                </label>
            </div>
        <?php } ?>

        <div class="mb-3">
            <label for="title" class="form-label">제목</label>
            <input type="text"
                   class="form-control"
                   id="title"
                   name="title"
                   maxlength="255"
                   value="<?php echo boardH($post['title']); ?>"
                   required>
        </div>

        <div class="mb-3">
            <label for="content" class="form-label">내용</label>
            <textarea class="form-control"
                      id="content"
                      name="content"
                      rows="12"
                      required><?php echo boardH($post['content']); ?></textarea>
        </div>

        <?php if ($post['attachment']) { ?>
            <div class="mb-3">
                <strong>현재 첨부:</strong>
                <?php echo boardH(basename($post['attachment'])); ?>
            </div>
        <?php } ?>

        <div class="mb-3">
            <label for="attachment" class="form-label">
                새 첨부 이미지
            </label>
            <input type="file"
                   class="form-control"
                   id="attachment"
                   name="attachment"
                   accept="image/jpeg,image/png,image/gif,image/webp">

            <div class="form-text">
                새 이미지를 선택하면 기존 첨부 이미지는 교체됩니다.
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">완료</button>

            <a href="<?php echo boardH(boardUrl([
                'bid' => $bid,
                'mode' => 'show',
                'idx' => $idx,
                'page' => $page
            ])); ?>"
               class="btn btn-outline-secondary">
                취소
            </a>

            <a href="<?php echo boardH(boardUrl([
                'bid' => $bid,
                'page' => $page
            ])); ?>"
               class="btn btn-secondary">
                목록보기
            </a>
        </div>
    </form>

<?php
    }
}
?>

</div>
