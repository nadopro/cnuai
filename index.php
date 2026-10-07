<?php
$sessionPath = __DIR__ . '/sess';

include "db.php";
$conn = connectDB(); 

if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}

session_save_path($sessionPath);
session_start();

$loginError = '';

if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    $_SESSION = [];
    session_destroy();

    header('Location: index.php');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $userId = trim($_POST['user_id'] ?? '');
    $userPw = $_POST['user_pw'] ?? '';

    $sql = "SELECT id, name, pass, level FROM members WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $userId);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($member = $result->fetch_assoc()) {
            if ($member['pass'] === $userPw) {
                $_SESSION['user_id'] = $member['id'];
                $_SESSION['user_name'] = $member['name'];
                $_SESSION['level'] = (int)$member['level'];

                $stmt->close();

                header('Location: index.php');
                exit;
            }
        }

        $stmt->close();
    }

    $loginError = '아이디 또는 비밀번호가 올바르지 않습니다.';
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>충남대학교 한문학과</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/d3@7.9.0/dist/d3.min.js"></script>

    <style>
        html, body {
            height: 100%;
        }

        body {
            display: flex;
            flex-direction: column;
            margin: 0;
        }

        main {
            flex: 1;
        }

        footer {
            background-color: #CCCCCC;
        }
    </style>
</head>

<body>

<!-- 로그인 영역 -->
<div class="bg-light border-bottom py-2">
    <div class="container">
        <?php if (isset($_SESSION['user_name'])) { ?>

            <div class="d-flex justify-content-end align-items-center gap-2">
                <span>
                    <strong><?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?></strong> 님
                </span>

                <form method="post" action="index.php" class="m-0">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        로그아웃
                    </button>
                </form>
            </div>

        <?php } else { ?>

            <form method="post" action="index.php"
                  class="d-flex justify-content-end align-items-center flex-wrap gap-2">

                <input type="hidden" name="action" value="login">

                <label for="user_id" class="form-label mb-0">ID</label>
                <input type="text"
                       class="form-control form-control-sm"
                       id="user_id"
                       name="user_id"
                       style="width:140px;"
                       required>

                <label for="user_pw" class="form-label mb-0">PW</label>
                <input type="password"
                       class="form-control form-control-sm"
                       id="user_pw"
                       name="user_pw"
                       style="width:140px;"
                       required>

                <button type="submit" class="btn btn-sm btn-primary">
                    로그인
                </button>
            </form>

            <?php if ($loginError !== '') { ?>
                <div class="text-danger text-end small mt-1">
                    <?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php } ?>

        <?php } ?>
    </div>
</div>


<!-- 상단 Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">충남대학교</a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavbar"
                aria-controls="mainNavbar"
                aria-expanded="false"
                aria-label="메뉴 열기">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link active" href="index.php">홈</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false">
                        한문 수업
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php?cmd=non">논어</a></li>
                        <li><a class="dropdown-item" href="index.php?cmd=rgb">RGB</a></li>
                        <li><a class="dropdown-item" href="index.php?cmd=bscolor">BS 색상</a></li>
                        <li><a class="dropdown-item" href="index.php?cmd=network">인물관계 시각화</a></li>
                        <li><a class="dropdown-item" href="index.php?cmd=input">입력</a></li>
                        <li><a class="dropdown-item" href="index.php?cmd=autonet">자동인물관계</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false">
                        메뉴2
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">메뉴2-1</a></li>
                        <li><a class="dropdown-item" href="#">메뉴2-2</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false">
                        메뉴3
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">메뉴3-1</a></li>
                        <li><a class="dropdown-item" href="#">메뉴3-2</a></li>
                        <li><a class="dropdown-item" href="#">메뉴3-3</a></li>
                    </ul>
                </li>


                <?php if (isset($_SESSION['level']) && (int)$_SESSION['level'] === 9) { ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false">
                        관리자
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php?cmd=member_admin">회원관리</a></li>
                        <li><a class="dropdown-item" href="index.php?cmd=board_admin">게시판관리</a></li>
                    </ul>
                </li>
                <?php } ?>

            </ul>
        </div>
    </div>
</nav>

<!-- 본문 -->
<main>
    <div class="container py-5">

<?php
    $cmd = $_GET['cmd'] ?? '';

    $allowedPages = ['non', 'autonet', 'rgb', 'bscolor', 'network', 'input', 'member_admin', 'board_admin'];

    if ($cmd && in_array($cmd, $allowedPages, true)) {
        include __DIR__ . '/' . $cmd . '.php';
    } else {
?>
        <div class="text-center">
            <h1 class="mb-3">충남대학교 한문학과</h1>
            <h2 class="mb-3">AI와 문화콘텐츠 실습</h2>
            <p class="fs-5">홈페이지 만들기 첫화면입니다.</p>
        </div>
<?php
    }
?>

    </div>
</main>

<!-- 하단 사이트 정보 -->
<footer class="py-3 mt-auto">
    <div class="container text-center">
        충남대학교 한문학과 Tel. 042-821-0000
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
