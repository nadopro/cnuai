<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$jsonFile = __DIR__ . '/person.json';

function sortKoreanNames(array $names): array
{
    $names = array_values(array_unique($names));
    if (class_exists('Collator')) {
        $collator = new Collator('ko_KR');
        $collator->sort($names);
    } else {
        sort($names, SORT_STRING);
    }
    return $names;
}

function loadPersonJson(string $jsonFile): array
{
    $data = ['persons' => [], 'relations' => []];

    if (!is_file($jsonFile)) {
        return $data;
    }

    $json = file_get_contents($jsonFile);
    if ($json === false || trim($json) === '') {
        return $data;
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return $data;
    }

    foreach (($decoded['nodes'] ?? []) as $node) {
        $name = trim((string)($node['id'] ?? ''));
        if ($name !== '') {
            $data['persons'][] = $name;
        }
    }

    foreach (($decoded['links'] ?? []) as $link) {
        $source = trim((string)($link['source'] ?? ''));
        $target = trim((string)($link['target'] ?? ''));
        $relation = trim((string)($link['relation'] ?? ''));

        if ($source !== '' && $target !== '' && $relation !== '') {
            $data['relations'][] = [
                'source' => $source,
                'target' => $target,
                'relation' => $relation
            ];
        }
    }

    $data['persons'] = sortKoreanNames($data['persons']);
    return $data;
}

if (!isset($_SESSION['autonet_initialized'])) {
    $_SESSION['autonet_data'] = loadPersonJson($jsonFile);
    $_SESSION['autonet_initialized'] = true;
}

if (!isset($_SESSION['autonet_data'])) {
    $_SESSION['autonet_data'] = ['persons' => [], 'relations' => []];
}

$allowedRelations = ['부자', '모자', '형제', '친구', '사제', '장인-사위', '기타'];
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['autonet_action'])) {
    $action = $_POST['autonet_action'];

    if ($action === 'add_person') {
        $personName = trim($_POST['person_name'] ?? '');

        if ($personName === '') {
            $message = '인물명을 입력하세요.';
            $messageType = 'danger';
        } elseif (in_array($personName, $_SESSION['autonet_data']['persons'], true)) {
            $message = '이미 등록된 인물입니다.';
            $messageType = 'warning';
        } else {
            $_SESSION['autonet_data']['persons'][] = $personName;
            $_SESSION['autonet_data']['persons'] = sortKoreanNames($_SESSION['autonet_data']['persons']);
            $message = $personName . ' 인물이 등록되었습니다.';
        }
    }

    elseif ($action === 'add_relation') {
        $person1 = trim($_POST['person1'] ?? '');
        $person2 = trim($_POST['person2'] ?? '');
        $relation = trim($_POST['relation'] ?? '');
        $persons = $_SESSION['autonet_data']['persons'];

        if ($person1 === '' || $person2 === '' || $relation === '') {
            $message = '인물1, 인물2, 관계를 모두 선택하세요.';
            $messageType = 'danger';
        } elseif (!in_array($person1, $persons, true) || !in_array($person2, $persons, true)) {
            $message = '등록되지 않은 인물이 선택되었습니다.';
            $messageType = 'danger';
        } elseif ($person1 === $person2) {
            $message = '인물1과 인물2는 서로 다른 인물을 선택하세요.';
            $messageType = 'warning';
        } elseif (!in_array($relation, $allowedRelations, true)) {
            $message = '올바른 관계를 선택하세요.';
            $messageType = 'danger';
        } else {
            $_SESSION['autonet_data']['relations'][] = [
                'source' => $person1,
                'target' => $person2,
                'relation' => $relation
            ];
            $message = '관계가 등록되었습니다.';
        }
    }

    elseif ($action === 'delete_relation') {
        $index = filter_input(INPUT_POST, 'relation_index', FILTER_VALIDATE_INT);
        if ($index !== false && $index !== null && isset($_SESSION['autonet_data']['relations'][$index])) {
            array_splice($_SESSION['autonet_data']['relations'], $index, 1);
            $message = '관계가 삭제되었습니다.';
        } else {
            $message = '삭제할 관계를 찾을 수 없습니다.';
            $messageType = 'danger';
        }
    }

    elseif ($action === 'make_json') {
        $persons = sortKoreanNames($_SESSION['autonet_data']['persons']);
        $relations = $_SESSION['autonet_data']['relations'];

        $jsonData = ['nodes' => [], 'links' => []];

        foreach ($persons as $person) {
            $jsonData['nodes'][] = ['id' => $person];
        }

        foreach ($relations as $item) {
            $jsonData['links'][] = [
                'source' => $item['source'],
                'target' => $item['target'],
                'relation' => $item['relation']
            ];
        }

        $result = file_put_contents(
            $jsonFile,
            json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );

        if ($result === false) {
            $message = 'person.json 파일을 생성하지 못했습니다. 폴더 쓰기 권한을 확인하세요.';
            $messageType = 'danger';
        } else {
            $message = 'person.json 파일을 새로 생성했습니다.';
        }
    }
}

$persons = sortKoreanNames($_SESSION['autonet_data']['persons']);
$_SESSION['autonet_data']['persons'] = $persons;
$relations = $_SESSION['autonet_data']['relations'];
$personText = implode(',', $persons);
?>

<div class="container py-4">
    <h2 class="mb-4">인물관계망 데이터 관리</h2>

    <?php if ($message !== '') { ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php } ?>

    <div class="card mb-4">
        <div class="card-header">인물 등록</div>
        <div class="card-body">
            <form method="post" action="" class="row g-2 align-items-center">
                <input type="hidden" name="autonet_action" value="add_person">
                <div class="col-auto">
                    <label for="person_name" class="col-form-label">인물명</label>
                </div>
                <div class="col-sm-6 col-md-4">
                    <input type="text" class="form-control" id="person_name" name="person_name" placeholder="예: 홍길동" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">등록</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">등록된 인물 목록</div>
        <div class="card-body">
            <textarea class="form-control" rows="4" readonly><?php echo htmlspecialchars($personText, ENT_QUOTES, 'UTF-8'); ?></textarea>
            <div class="form-text">등록된 인물은 가나다 순으로 표시됩니다.</div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">관계 등록</div>
        <div class="card-body">
            <?php if (count($persons) < 2) { ?>
                <div class="alert alert-warning mb-0">관계를 등록하려면 인물을 2명 이상 등록하세요.</div>
            <?php } else { ?>
                <form method="post" action="" class="row g-2 align-items-end">
                    <input type="hidden" name="autonet_action" value="add_relation">

                    <div class="col-md-3">
                        <label for="person1" class="form-label">인물1</label>
                        <select class="form-select" id="person1" name="person1" required>
                            <option value="">인물1 선택</option>
                            <?php foreach ($persons as $person) { ?>
                                <option value="<?php echo htmlspecialchars($person, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($person, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="person2" class="form-label">인물2</label>
                        <select class="form-select" id="person2" name="person2" required>
                            <option value="">인물2 선택</option>
                            <?php foreach ($persons as $person) { ?>
                                <option value="<?php echo htmlspecialchars($person, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($person, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="relation" class="form-label">관계</label>
                        <select class="form-select" id="relation" name="relation" required>
                            <option value="">관계 선택</option>
                            <?php foreach ($allowedRelations as $item) { ?>
                                <option value="<?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success w-100">등록</button>
                    </div>
                </form>
            <?php } ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">등록된 관계 목록</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th style="width:80px;">순서</th>
                            <th>인물1</th>
                            <th>인물2</th>
                            <th>관계</th>
                            <th style="width:100px;">비고</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($relations) === 0) { ?>
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">등록된 관계가 없습니다.</td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($relations as $index => $item) { ?>
                            <tr>
                                <td class="text-center"><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($item['source'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($item['target'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($item['relation'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-center">
                                    <form method="post" action="" onsubmit="return confirm('이 관계를 삭제하시겠습니까?');">
                                        <input type="hidden" name="autonet_action" value="delete_relation">
                                        <input type="hidden" name="relation_index" value="<?php echo $index; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">삭제</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <form method="post" action="" class="m-0">
            <input type="hidden" name="autonet_action" value="make_json">
            <button type="submit" class="btn btn-primary">JSON으로 변환하기</button>
        </form>

        <button type="button" class="btn btn-success"
                onclick="window.open('autovisual.php','autovisual','width=2000,height=1000,scrollbars=yes,resizable=yes');">
            네트워크 시각화
        </button>
    </div>

    <div class="mt-3 text-secondary small">
        JSON 파일 위치 : <code>person.json</code>
    </div>
</div>
