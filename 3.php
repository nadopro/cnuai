<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=3.0">
    <title>첫번째 홈페이지</title>
</head>
<body>

<?php
for ($i = 1; $i <= 100; $i++) {
    echo "<p>";
    echo $i . ". ";
    echo "<strong>충남대학교</strong> 인문대학 ";
    echo "<span style='color:red;'>한문학과</span><br>";
    echo "홍길동";
    echo "</p>";
}
?>

</body>
</html>