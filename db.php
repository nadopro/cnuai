<?php
/*
 * db.php
 * MySQL 데이터베이스 연결 함수
 */

function connectDB()
{
    $host = "localhost";
    $dbname = "cnu";
    $dbuser = "cnu";
    $dbpass = "1111";

    $conn = new mysqli($host, $dbuser, $dbpass, $dbname);

    if ($conn->connect_error) {
        die("DB 접속 실패: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

    return $conn;
}

function closeDB($conn)
{
    if ($conn instanceof mysqli) {
        $conn->close();
    }
}
?>
