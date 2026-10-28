1. 프로그램 다운로드

    1. download xampp  --> 3번째 버튼
    2. download visual code

2. 강의 자료의 다운로드

    https://github.com/nadopro/cnuai

3. DB 접속

    localhost/phpmyadmin

Q1:
HTML문서를 하나 만들고 싶어.
문서는 100%를 기본 시작값으로 하고, 확대는 최대 300%까지 하고 싶어.
문서의 제목은 "첫번째 홈페이지"
내용에는 다음과 같이 출력해 줘.
이때 "충남대학교"는 두껍게 처리해주고, "한문학과"는 빨간색으로 보여줘.
이 파일의 이름을 1.html로 만들어줘.

충남대학교 인문대학 한문학과
홍길동


localhost/1.html
Q2. 
이번에는 출력된 내용을 100회 반복하고 싶어. 프로그래밍을 이용해서 2.php 파일을 만들어줘. 그런데 정말 
100회 반복되는 것을 확인하기 위해 맨 앞에 숫자를 같이 남겨줘.

localhost/2.php

Q3.

이번 4.html을 만들고 싶어.

2026년 9월의 달력을 만들고 싶어.
9월 1일은 화요일이야.
표 형태로 출력하고 싶은데,
일요일은 빨간색,
평일은 검정색
토요일은 파란색으로 텍스트를 출력해.
스타일시트를 사용하지 말고, 각각 태그로 출력해.

이번에는 스타일시트를 사용하는 방법으로 만들어줘。
일요일은 빨강색、 노랑 배경、
토요일은 파란색 글씨로 출력

w3schools.com


Bootstrap5를 이용해서 반응형 홈페이지를 만들고 싶어.
상단에는 Navbar를 이용해서 메뉴를 구성하고 싶어.
메뉴에는 "홈", "메뉴1", "메뉴2", "메뉴3"이 있어.
홈을 제외한 나머지는 드랍다운메뉴로 구성할거야.

메뉴1에는 메뉴1-1, 메뉴1-2, 메뉴1-3
메뉴2에는 메뉴2-1, 메뉴2-2
메뉴3에는 메뉴3-1, 메뉴3-2, 메뉴3-3 이 있어.

바디부분에는 다음과 같이 출력해 줘.

----------------------------------------
충남대학교 한문학과
AI와 문화콘텐츠 실습
홈페이지 만들기 첫화면입니다.
----------------------------------------

화면 하단에는 사이트정보가 다음과 같이 출력되도록 해줘.
배경색을 #CCCCCC
-----------------------------------------
충남대학교 한문학과 Tel. 042-821-0000
-----------------------------------------

만약에 내용이 너무 적어도 사이트정보는 반드시 화면의 맨 아래에
배치되도록 화면을 구성해 줘.
이 결과를 index.php로 만들어 줘.


첨부한 것과 같이 index.php 파일의 메뉴를 조금 바꿨어.
cmd=non이라고 되어 있으면,
non.php 파일을 몸체에 포함시키고 싶어(include)
모든 메뉴에는 cmd값으로 화면에 배치되는 값을 결정할거야.
만약에 cmd값이 없으면 현재처럼 현재의 텍스트를 출력하도록 수정해 줘.

rgb.php 파일을 만들고 싶어.
현재 index.php파일에서 rgb.php를 include할거야.
index.php는 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
이렇게 부트스트랩5가 설정되어 있어.

모든 rgb 색상을 배경으로 표시하고 싶어.
그런데 너무 많으니까, 끝이 0, 8인 값만 대상으로 하고 싶어.
예를 들어. 
#000000, #000008, #000010, #000018, .... #FFFFFF 까지
표 형태로 만들고,
<td>#000000</td><td>배경색 #000000</td>
이런 형태로 표시하는데, 한 라인에 3가지 색상만 보여줘.


같은 방법으로 bootstrap에 있는 색상을 표 형태로 출력하고 싶어.
예를 들어 primary, danger 등을
왼쪽에는 텍스트, 오른쪽에는 배경색으로 사용하도록 만들어 줘.
rgb만들때 처럼 표 형태로 만들어줘.

JSON

{} 객체 , 대상
[] 나열
key:value

1. 한 사람을 표시

{
    "소속":"한문학과",
    "이름":"홍길동"
}


2. 객체 속에 객체

{
    "소속":{
        "학과":"한문학과",
        "위치":"인문대 201호"
    },
    "학생": {
        "이름":"홍길동",
        "학번":20260001
    }
}

3. 나열을 표시하고
{
    "소속":{
        "학과":"한문학과",
        "위치":"인문대 201호"
    },
    "학생": [
        {
            "이름":"홍길동",
            "학번":20260001
        }, 
        {
            "이름":"이순신",
            "학번":20260002
        }
    ]
}

인물관계망
{
    "사람" : [ {}, {}, {}, {}, {}],
    "관계" : [ {}, {}, {}]
}


{
    "nodes" : [ {}, {}, {}, {}, {}],
    "links" : [ {}, {}, {}]
}


HTML5의 <head>에 메타데이터를 하나 추가하고 싶어.
d3js 최신 버전을 추가하는 예제를 아래에 추가해 줘.

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>충남대학교 한문학과</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>



D3JS를 이용해서 네트워크 다이어그램을 위한 JSON 데이터를 만들어줘.
node에는 홍길동, 홍대감, 이순신, 이이, 
        사임당, 유성룡, 황진이, 서경덕 이 있어
link에는 다음 정보를 포함해 줘.

홍대감의 아들 = 홍길동,
홍길동의 친구 = 이순신
이순신의 친구 = 유성룡
유성룡의 스승 = 홍대감
사임당의 아들 = 이이
황진이의 스승 = 서경덕
서경덕의 장인 = 이순신
서경덕의 스승 = 이이


결과

{
  "nodes": [
    { "id": "홍길동" },
    { "id": "홍대감" },
    { "id": "이순신" },
    { "id": "이이" },
    { "id": "사임당" },
    { "id": "유성룡" },
    { "id": "황진이" },
    { "id": "서경덕" }
  ],

  "links": [
    {
      "source": "홍대감",
      "target": "홍길동",
      "relation": "아들"
    },
    {
      "source": "홍길동",
      "target": "이순신",
      "relation": "친구"
    },
    {
      "source": "이순신",
      "target": "유성룡",
      "relation": "친구"
    },
    {
      "source": "홍대감",
      "target": "유성룡",
      "relation": "스승"
    },
    {
      "source": "사임당",
      "target": "이이",
      "relation": "아들"
    },
    {
      "source": "서경덕",
      "target": "황진이",
      "relation": "스승"
    },
    {
      "source": "이순신",
      "target": "서경덕",
      "relation": "장인"
    },
    {
      "source": "이이",
      "target": "서경덕",
      "relation": "스승"
    }
  ]
}


이번에는 network.php 파일을 만들거야.
index.php에서 이 파일을 포함하기 때문에
이미 bootstrap5와 d3js를 추가되었어.
지금 만들어진 JSON 데이터를 이용해서 인물 관계망을 
D3JS의 network diagram으로 그리고 싶어.

노드는 원은 없이 텍스트로만 출력해.
관계를 나타내는 링크는 색상으로 관계를 구분하고 싶어.
부자 또는 모자 관계는 빨간색 실선
친구 관계는 파란색 점선
사제 관계는 녹색 실선으로 그려줘.
그외 관계는 #FF00FF 로 그려줘.

마우스로 휠로 올리면 확대, 내리면 축소시켜줘.
링크나 노드가 아닌 영역을 클릭후 이동할수 있도록 해 줘.



input.php를 같은 방법으로 만들고 싶어.
즉 index.php에서 input.php 를 포함하겠다는 말이야.

<form>을 통해 전달된 사용자 입력하는 모든 형태를 예로 만들고 싶어.
예를들어, text(1줄 텍스트입력), password, 
여러줄 입력(textarea),
select(여러개 중 하나 선택),
라디오버튼 (성별 선택)
여러개 선택하는 checkbox(예를 들어 취미 선택)
숫자 선택(예: 학년 선택)
날짜 선택(예: 오늘 날짜 선택)
색상 선택, 파일선택, 버튼을 하나씩 다 예로 만들고 싶어.


첨부한 index.php 파일을 수정하고 싶어.
navbar(메뉴) 위에 로그인 기능을 만들고 싶어.
로그인 관리는 sess 폴더에 저장해서 관리하고 싶어.
만약에 로그인이 된 경우에는 다음처럼 오른쪽 정렬로 보여줘.
    홍길동 님 <로그아웃 버튼>
로그인이 안된 경우에는
  ID <TEXT 입력> PW <비밀번호 입력> <로그인버튼>

회원가입은 아직 기능이 없기 때문에
id : test, pass : 1111 이면 "테스트"가 로그인
id : admin, pass : 1111 이면 "관리자"가 로그인
그외에는 로그인을 하지 못하도록 처리해 줘.


앞에서와 같이 autonet.php파일을 index.php파일에서 include하고 싶어
하고 싶은 작업은 autonet.php 파일을 만드는거야.
인물관계망을 D3.js의 네트워크 다이어그램으로 그리기 위한 
데이터를 관리하기 위한 파일이야.

상단에는
  인물명 <텍스트로 입력> <등록버튼>
등록이 수행되면
하단에 
"등록된 인물 목록"을 만들고,
<textarea>에 콤마단위로 등록된 인물을 나열해 줘.
예를 들어 "홍길동,홍대감,이이" 이런 형태야.

그 아래에는 관계 등록을 위한 영역이야.
<인물1을 SELECT로 선택> <인물2를 SELECT로 선택> <둘 사이의 관계 SELECT><등록버튼>
인물1, 인물2는 등록된 인물들 중 선택하는데, 가나다 순으로 정렬해서 보여줘
이때 둘 사이의 관계는 다음중 하나야.
부자,모자,형제,친구,사제,장인-사위,기타

아래에는 등록된 관계 목록을 표형태로 보여줘.
열의 순서는 다음과 같아.
순서, 인물1, 인물2, 관계, 비고
비고란에는 등록된 관계를 삭제만 할 수 있어.

관계목록 아래에는 다음 버튼이 있어.
<JSON으로 변환하기> <네트워크 시각화>

<JSON으로 변환하기>버튼을 누르면, 등록된 인물과 관계를 이용해
person.json 파일로 만들어줘.
만약 이 파일 존재하는 경우에는 삭제하고 다시 만들어줘.

<네트워크 시각화>버튼을 클릭하면 팝업창으로 autovisual.php를 띄워줘.

만약에 처음 이 파일이 시작되면 person.json 파일이 있는지 검사를 해줘.
먄약 이 파일이 있으면, 이 파일을 기초로해서 등록된 인물목록과 관계목록을 만들어줘.




autovisual.php 파일을 만들고 싶은데, 이 파일은 팝업으로 뜨는 창이야.
D3js의 최신버전을 이용해 인물관계망을 시각화하고 싶어.
첨부하는 파일은 이전에 만든 network.php 파일인데 동작 원리는 같아.
차이는 데이터만 person.json 파일을 이용하는 차이만 있어.



MySQL 테이블을 하나 만들고 싶어.
std_table : 학생 관리 테이블을
학번, 이름, 학과만 관리하고 싶어.
테이블 구조(스키마, schema)를 제시해 줘.

CREATE TABLE std_table (
    student_id VARCHAR(20) NOT NULL,
    student_name VARCHAR(50) NOT NULL,
    department VARCHAR(100) NOT NULL,

    PRIMARY KEY (student_id)
);


INSERT INTO std_table
(student_id, student_name, department)
VALUES
('20240001', '세종', '한문학과'),
('20250002', '이순신', '영문학과'),
('20260003', '이황', '독어독문과'),
('20240004', '이이', '한문학과'),
('20260005', '신사임당', '무용학과'),
('20250006', '유성룡', '영문학과'),
('20240007', '허균', '독어독문과'),
('20260008', '허난설헌', '한문학과'),
('20250009', '정약용', '무용학과'),
('20240010', '김홍도', '영문학과'),
('20260011', '신윤복', '독어독문과'),
('20250012', '조광조', '한문학과'),
('20240013', '김종직', '무용학과'),
('20260014', '서거정', '영문학과'),
('20250015', '송시열', '독어독문과'),
('20240016', '김장생', '한문학과'),
('20260017', '김상헌', '영문학과'),
('20250018', '이항복', '무용학과'),
('20240019', '정철', '독어독문과'),
('20260020', '윤선도', '한문학과'),
('20250021', '박지원', '영문학과'),
('20240022', '홍대용', '무용학과'),
('20260023', '김정희', '독어독문과'),
('20250024', '서경덕', '한문학과'),
('20240025', '김인후', '영문학과'),
('20260026', '조헌', '무용학과'),
('20250027', '박세채', '독어독문과'),
('20240028', '김육', '한문학과'),
('20260029', '송준길', '영문학과'),
('20250030', '임경업', '무용학과');

만들어진 테이블에 데이터 30개를 넣고 싶어.
학번은 "년도"+"순서" 형태.
순서는 0001 처럼 4자리 수로 만들어줘.
년도는 2024~2026 중에 무작위로 선택해. 
학번은 중복될 수 없어.
이름은 조선시대 역사 인물들을 검색해서 무작위로 채워줘.

학과는 한문학과, 영문학과, 무용학과, 독어독문과 중 무작위 선택
질의(Query)를 만들어 줘.


한글깨질 때,

ALTER DATABASE <database_name> CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
ALTER TABLE <table_name> CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


ALTER DATABASE cnu CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
ALTER TABLE std_table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


이 테이블에서 김씨만 찾고 싶어.
만약에 여러개의 데이터라면 이름의 가나다순으로 정렬하고 싶어.


이번에는 회원가입과 로그인을 위한 members라는 테이블을 만들고 싶어.
idx, id, name, pass, level
이 정보가 필요하고, 비밀번호는 암호화하지 않고,
공부하는 용도록 텍스트로 저장할거야.
이때 아이디는 중복될 수 없어.
idx는 숫자로 자동 증가하는 키 값으로 쓰고 싶어.
level은 회원 등급인데
1 : 일반회원
2 : 우수회원
...(중간 예비영역으로 생략)
9 : 관리자
이런 용도로 사용하고 싶어.


CREATE TABLE members (
    idx INT NOT NULL AUTO_INCREMENT,
    id VARCHAR(50) NOT NULL,
    name VARCHAR(50) NOT NULL,
    pass VARCHAR(100) NOT NULL,
    level TINYINT NOT NULL DEFAULT 1,

    PRIMARY KEY (idx),
    UNIQUE KEY (id)
);

테이블은 잘 만들어졌어.
데이터를 입력할 건데,
id: test, name : 테스트, pass : 1111, level : 1
id: admin, name : 관리자, pass : 1111, level : 9
이 외에 데이터 10개를 추가해 줘.
비밀번호는 모두 1111, level= 1이야.



INSERT INTO members
(id, name, pass, level)
VALUES
('test', '테스트', '1111', 1),
('admin', '관리자', '1111', 9),
('hong', '홍길동', '1111', 1),
('lee', '이순신', '1111', 1),
('yi', '이이', '1111', 1),
('saimdang', '신사임당', '1111', 1),
('jeong', '정약용', '1111', 1),
('heo', '허균', '1111', 1),
('yun', '윤선도', '1111', 1),
('park', '박지원', '1111', 1),
('kim', '김홍도', '1111', 1),
('seo', '서경덕', '1111', 1);


index.php파일의 맨 앞에 다음과 같이 추가했어.

<?php
$sessionPath = __DIR__ . '/sess';

include "db.php";

즉 모든 파일이 db.php파일을 포함하도록 구성되어 있어.
db.php 파일을 만들거야.

connectDB() 함수를 하나 만들고,
이 함수는 
DB name : cnu
db user : cnu
db pass : 1111
이 정보를 이용해 접속을 시도하고,
접속 정보인 $conn를 반환해.

closeDB($conn) 함수는 접속을 끊는 기능이야.
이 두 함수를 포함하는 db.php 파일을 만들어줘.



첨부한 index.php 파일의 기능을 변경할거야.
DB접속은 끝났으니까, 
members 테이블의 id, pass를 비교하는 코드로 변경해 줘.
이때 회원등급 정보도 session에 포함시켜줘.

또 관리자로 로그인 한 경우, 메뉴의 마지막에
관리자 메뉴를 하나 추가해 줘.
관리자 메뉴에는 "회원관리", "게시판관리"를 추가해 줘.

ALTER USER 'cnu'@'localhost'
IDENTIFIED BY '1111';

GRANT ALL PRIVILEGES ON cnu.* TO 'cnu'@'localhost';

FLUSH PRIVILEGES;


게시판을 위한 데이터베이스 스키마를 하나 만들고 싶어.
table name : board

idx : integer auto increment, primary key
bid : 게시판의 아이디, 1: 공지사항, 2: 자유게시판 3:학습게시판
제목, 공지여부, 작성시간, ip, 작성자id, 내용, 첨부파일

이 정도가 필요해. 
최적의 스키마를 제시해 줘.


CREATE TABLE board (
    idx INT NOT NULL AUTO_INCREMENT,
    bid TINYINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    is_notice TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip VARCHAR(45) DEFAULT NULL,
    writer_id VARCHAR(50) NOT NULL,
    content TEXT NOT NULL,
    attachment VARCHAR(255) DEFAULT NULL,
    hit integer default '0',

    PRIMARY KEY (idx),
    INDEX idx_bid (bid),
    INDEX idx_writer_id (writer_id),
    INDEX idx_created_at (created_at)
);

bid가 1,2,3번에 대해서 글을 작성해 줘.
작성자는 모두 test라는 아이디로 작성되었고,
제목과 내용은 무작위로 작성해 줘.


INSERT INTO board
(
    bid,
    title,
    is_notice,
    ip,
    writer_id,
    content,
    attachment
)
VALUES
(
    1,
    '학과 홈페이지 이용 안내',
    1,
    '127.0.0.1',
    'test',
    '학과 홈페이지 이용 방법과 게시판 사용 시 주의사항을 안내합니다. 게시글 작성 시 서로를 배려하는 표현을 사용해 주세요.',
    NULL
),
(
    2,
    '요즘 읽고 있는 책을 추천합니다',
    0,
    '127.0.0.1',
    'test',
    '최근에 역사 관련 책을 읽고 있는데 생각보다 흥미로운 내용이 많았습니다. 다른 분들도 재미있게 읽은 책이 있다면 추천해 주세요.',
    NULL
),
(
    3,
    '한문 원문 공부 방법에 대한 질문',
    0,
    '127.0.0.1',
    'test',
    '한문 원문을 읽을 때 문장 구조를 먼저 파악하는 것이 좋은지, 한자 뜻을 하나씩 확인하는 것이 좋은지 궁금합니다. 효과적인 공부 방법을 공유해 주세요.',
    NULL
);


이를 토대로 해서 board.php 파일을 만들거야.
index.php?cmd=board&bid=1과 같이 호출하고 있고,
모든 파일은 index.php에서 board.php를 호출하는 형태야.
이미 DB연결은 끝이난 상태야.

게시판의 기능은 목록보기, 글쓰기, 내용보기, 수정, 삭제하기 등의 기능이 있어.
이를 구분하기 위해 mode라는 값을 사용할거야.
만약에 mode가 없으면 목록보기야.

$mode가 없으면 $mode = "list"야.
목록보기는
글번호, 제목, 첨부, 작성자, 작성일, 읽음
모바일인 경우에는 제목과 작성자만 보여줘.
제목을 클릭하면 내용보기($mode = "show")로 이동해서 작성 내용을 보여줘.
목록은 한 화면에 10개씩만 보여주고,
하단에 페이지네이션으로 페이지별 보기를 만들어줘.
페이지네이션 왼쪽에는 "글쓰기"버튼을 만들어줘.
글쓰기 버튼을 클릭하면 $mode = "write"로 해줘.
글목록은 공지글이 최우선이고, 그다음 최신글 순서로 나열해줘.
목록보기에서 작성자는 아이디만 저장되어 있기 때문에
members 테이블의 id, name을 보고 이름으로 출력해.

글쓰기 기능($mode = "write")
이 기능은 회원 로그인한 경우에만 사용 가능해.
공지여부를 체크하는 것은 관리자만 가능해.
첨부파일은 이미지만 가능하도록 해줘.
제목, 내용, 첨부를 작성할 수 있어야해.
하단에는 <완료>와 <목록보기>버튼이 있어.

글내용보기 (mode="show")
읽음 카운트가 증가해야 하고,
제목, 작성자, 작성일, 읽음수, IP, 내용, 첨부 내용을 모두 표시해 줘.
이미지가 첨부되면 내용 아래에 이미지를 보여줘.
하단에는 <이전글><다음글><목록보기> 버튼이 있고,
현재 로그인한 아이디가 작성자이거나 관리자이면
<수정><삭제>버튼을 만들어줘.

삭제할 때는 "정말 삭제하시겠습니까?"하고 꼭 물어봐 줘.

첨부파일은 data/board/게시판번호/폴더에 저장할거야.
만약에 a.jpg파일이 업로드 되면 20251020123456.jpg처럼
현재 년월일시분초로 변환해서 중복되지 않도록 해 줘.


사진이 있는 인물 관계망을 관리하고 싶어.
이중에서 인물을 DB로 저장하려고 해.
person 테이블을 만들어 줘.


CREATE TABLE person (
    person_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    gender CHAR(1),
    description TEXT,
    photo VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;



  manPerson.php파일을 만들어줘.
  인물 등록을 위해 이름, 성별, 사진, 설명을 입력하고,
  등록 버튼을 누르면 조금 전 만든 테이블에 저장하고 싶어.

  하단에는 이 목록을 표형태로 표시해줘.
  맨 오른쪽에는 수정과 삭제 버튼을 만들어줘.
  수정을 누르면 상단에 있는 입력창을 그대로 활용해 주고,
  삭제할 때는 "정말 삭제하시겠습니까?"라고 묻고 삭제해줘.
  사진은 data/photo 폴더에 저장하고,
  현재날자와 시간정보를 활용해서
  20261028123456.jpg처럼 파일 이름을 변경해서 중복되지 않도록 해줘.


  4조.

  유형을 DB테이블로 만들고 싶어.
  테이블명은 type_table
  idx, title, useflag, memo 정도가 있으면 돼.
  테이블을 만들어줘.


  CREATE TABLE type_table (
    idx INT NOT NULL AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    useflag CHAR(1) NOT NULL DEFAULT 'Y',
    memo TEXT DEFAULT NULL,
    PRIMARY KEY (idx)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


manType.php 파일을 만들거야.
이 메뉴는 탭메뉴로 구성할 건데,
유형관리, 질문관리, 테스트 세개의 메뉴가 있어.
이중 유형관리는 유형을 등록하는 탭메뉴야.
조금 전 만든 테이블(type_table)을 등록하는 입력창을 만들어줘.

하단에는 등록된 전체 유형을 표형태로 나열해 줘.
맨 오른쪽에는 수정, 삭제 버튼을 만들어줘.
수정버튼을 클릭하면 앞에서 등록화면을 활용해 줘.
삭제할 때는 "정말 삭제하시겠습니까?"라고 꼭 물어줘.


3조.
워드관리가 필요한데, DB로 word_table을 만들어줘.
고사성어를 등록하고, 각 한자의 음과 훈을 저장
유사한 고사성어, 설명이 필요해.
유사한 고사성어는 여러개가 있을 수 있지만
쉼표단위로 구분해서 저장할거야.
예를 들어 (지믕,관포지교,죽마고우)

즉, 
idx, words, kwords(사자성어의 한글 독음),
sounds(음과 훈을 콤마단위로 나열할거야)
same (유사한 사자성어 목록, 콤마단위),
level (난이도 1~5)
useflag (사용여부)


CREATE TABLE word_table (
    idx INT AUTO_INCREMENT PRIMARY KEY,
    words VARCHAR(50) NOT NULL COMMENT '고사성어 한자',
    kwords VARCHAR(100) NOT NULL COMMENT '고사성어 한글 독음',
    sounds VARCHAR(255) DEFAULT NULL COMMENT '각 한자의 음과 훈(쉼표 구분)',
    same TEXT DEFAULT NULL COMMENT '유사한 고사성어(쉼표 구분)',
    memo TEXT DEFAULT NULL COMMENT '고사성어 뜻 및 설명',
    level TINYINT NOT NULL DEFAULT 1 COMMENT '난이도(1~5)',
    useflag CHAR(1) NOT NULL DEFAULT 'Y' COMMENT '사용여부(Y/N)',

    CONSTRAINT chk_word_level CHECK (level BETWEEN 1 AND 5),
    CONSTRAINT chk_word_useflag CHECK (useflag IN ('Y', 'N'))
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci
COMMENT='고사성어 관리 테이블';


앞에서 만든 테이블을 이용해서 관리하는 manWord.php파일을 만들거야.
탭메뉴로 구성하는데, 
1. 어휘관리
2. 통계
3. 테스트

어휘관리는 앞의 테이블을 등록하고 관리하는 탭메뉴야.
상단에는 예를 들어 "죽마고우"라는 단어가 있을 때, 이를 등록하는 등록화면이
있어.
하단에는 등록된 어휘(사자성어)목록을 표 형태로 보여줘.
idx, words, kwords, useflag, 비고 만 배치해줘.
비고에는 수정, 삭제 버튼을 만들어주고
수정을 할 때는 상단의 입력부분을 활용해줘.
삭제할 때는 "정말 삭제하시겠습니까?"라고 물어줘.




1조. 여러개의 사진 등록하기
우선 dance 테이블을 만들고 싶어.
idx, title, photo1~10개, useflag
테이블 스키마를 만들어줘.


CREATE TABLE dance (
    idx INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    org    text default null,
    kor     text default null, 
    photo1 VARCHAR(255) DEFAULT NULL,
    photo2 VARCHAR(255) DEFAULT NULL,
    photo3 VARCHAR(255) DEFAULT NULL,
    photo4 VARCHAR(255) DEFAULT NULL,
    photo5 VARCHAR(255) DEFAULT NULL,
    photo6 VARCHAR(255) DEFAULT NULL,
    photo7 VARCHAR(255) DEFAULT NULL,
    photo8 VARCHAR(255) DEFAULT NULL,
    photo9 VARCHAR(255) DEFAULT NULL,
    photo10 VARCHAR(255) DEFAULT NULL,
    useflag CHAR(1) NOT NULL DEFAULT 'Y'
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;



manDance.php파일을 수정해서 다시 만들고 싶어.
다음과 같이 스키마를 변경했어.

CREATE TABLE dance (
    idx INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    org    text default null,
    kor     text default null, 
    photo1 VARCHAR(255) DEFAULT NULL,
    photo2 VARCHAR(255) DEFAULT NULL,
    photo3 VARCHAR(255) DEFAULT NULL,
    photo4 VARCHAR(255) DEFAULT NULL,
    photo5 VARCHAR(255) DEFAULT NULL,
    photo6 VARCHAR(255) DEFAULT NULL,
    photo7 VARCHAR(255) DEFAULT NULL,
    photo8 VARCHAR(255) DEFAULT NULL,
    photo9 VARCHAR(255) DEFAULT NULL,
    photo10 VARCHAR(255) DEFAULT NULL,
    useflag CHAR(1) NOT NULL DEFAULT 'Y'
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


사용자 입력하는 창을 만들고 싶어.
idx, title, org(한문 원문), kor(한글 번역), photos 형태가 있어.
이때 사진은 10개를 Drag and Drop 하도록 해 줘.
조금 전에 만든 dance테이블에 저장하도록 해줘.
하단에는 목록을 표 형태로 만들어줘.
마지막에는 수정과 삭제버튼을 만들고, 수정은 등록 화면을 이용해 줘.
data/dance/인덱스번호 폴더에 저장해.
삭제할 때는 "정말 삭제하시겠습니까?"라고 물어줘.
입력하는 파일은 gif파일 이어서, 상하좌우 회전 기능을 없애도 돼

