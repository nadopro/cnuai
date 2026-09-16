<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>충남대학교 한문학과</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

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
                        <li><a class="dropdown-item" href="#">RGB</a></li>
                        <li><a class="dropdown-item" href="#">BS 색상</a></li>
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

            </ul>
        </div>
    </div>
</nav>

<!-- 본문 -->
<main>
    <div class="container py-5">

<?php
    $cmd = $_GET['cmd'] ?? '';

    if ($cmd == 'non') {
        include 'non.php';
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
