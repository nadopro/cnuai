<?php
/**
 * manDance.php - index.php?cmd=manDance 에서 include
 * 필요: $conn (mysqli 연결), dance 테이블, data/dance 쓰기 권한
 * GIF 원본 그대로 보존하며 회전/재인코딩하지 않습니다.
 */
if (!isset($conn) || !($conn instanceof mysqli)) {
    echo '<p>DB 연결 변수 $conn (mysqli)이 필요합니다.</p>';
    return;
}
$conn->set_charset('utf8mb4');
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['dance_csrf'])) $_SESSION['dance_csrf'] = bin2hex(random_bytes(32));
$danceCsrf = $_SESSION['dance_csrf'];
$danceUrl = 'index.php?cmd=manDance';
$danceRoot = __DIR__ . '/data/dance';
$danceError = '';
$danceNotice = '';
const DANCE_MAX_BYTES = 10485760; // GIF 1개당 10MB

if (!function_exists('danceEsc')) {
    function danceEsc($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    function danceFetch(mysqli $conn, int $idx): ?array {
        $stmt = $conn->prepare('SELECT * FROM dance WHERE idx = ?');
        $stmt->bind_param('i', $idx);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $data ?: null;
    }
    function danceFolder(string $root, int $idx): string { return $root . '/' . $idx; }
    function danceFileUrl(int $idx, string $name): string {
        return 'data/dance/' . $idx . '/' . rawurlencode($name);
    }
    function danceRemoveFolder(string $dir): void {
        if (!is_dir($dir)) return;
        foreach (new DirectoryIterator($dir) as $entry) {
            if ($entry->isFile() && !$entry->isLink()) @unlink($entry->getPathname());
        }
        @rmdir($dir);
    }
    function danceGo(string $message): void {
        $url = 'index.php?cmd=manDance&msg=' . rawurlencode($message);
        if (!headers_sent()) { header('Location: ' . $url); exit; }
        echo '<script>location.replace(' . json_encode($url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ');</script>';
        exit;
    }
    function danceUploadList(): array {
        if (empty($_FILES['photos']['name']) || !is_array($_FILES['photos']['name'])) return [];
        $list = [];
        foreach ($_FILES['photos']['name'] as $key => $name) {
            $err = (int)($_FILES['photos']['error'][$key] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_NO_FILE) continue;
            if ($err !== UPLOAD_ERR_OK) throw new RuntimeException('GIF 업로드 오류 (코드 ' . $err . '). 서버의 upload_max_filesize 및 post_max_size를 확인하세요.');
            $tmp = (string)($_FILES['photos']['tmp_name'][$key] ?? '');
            $size = (int)($_FILES['photos']['size'][$key] ?? 0);
            if (!is_uploaded_file($tmp) || $size <= 0 || $size > DANCE_MAX_BYTES) throw new RuntimeException('GIF 한 장은 10MB 이하여야 합니다.');
            $imageInfo = @getimagesize($tmp);
            if (!$imageInfo || ($imageInfo[2] ?? 0) !== IMAGETYPE_GIF) throw new RuntimeException('GIF 파일만 업로드할 수 있습니다.');
            $list[] = ['tmp' => $tmp];
        }
        if (count($list) > 10) throw new RuntimeException('GIF는 최대 10장까지 등록할 수 있습니다.');
        return $list;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        if (!hash_equals($danceCsrf, (string)($_POST['dance_csrf'] ?? ''))) throw new RuntimeException('유효하지 않은 요청입니다. 페이지를 새로고침하세요.');
        $action = (string)($_POST['dance_action'] ?? '');
        $idx = max(0, (int)($_POST['idx'] ?? 0));
        $existing = $idx > 0 ? danceFetch($conn, $idx) : null;
        if ($action === 'save') {
            if ($idx > 0 && !$existing) throw new RuntimeException('수정할 항목이 존재하지 않습니다.');
            $title = trim((string)($_POST['title'] ?? ''));
            $org = trim((string)($_POST['org'] ?? ''));
            $kor = trim((string)($_POST['kor'] ?? ''));
            $useflag = (($_POST['useflag'] ?? 'Y') === 'N') ? 'N' : 'Y';
            if ($title === '' || mb_strlen($title, 'UTF-8') > 200) throw new RuntimeException('제목을 1~200자로 입력하세요.');
            $uploads = danceUploadList();
            $remove = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['remove_photos'] ?? [])), fn($x) => $x >= 1 && $x <= 10)));
            $slots = [];
            for ($i = 1; $i <= 10; $i++) {
                if (!$existing || empty($existing['photo' . $i]) || in_array($i, $remove, true)) $slots[] = $i;
            }
            if (count($uploads) > count($slots)) throw new RuntimeException('최대 10장입니다. 기존 사진을 삭제 대상으로 선택하거나 새 파일을 줄여주세요.');
            $new = !$existing;
            if ($new) {
                $stmt = $conn->prepare('INSERT INTO dance (title, org, kor, useflag) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssss', $title, $org, $kor, $useflag);
                if (!$stmt->execute()) throw new RuntimeException('등록하지 못했습니다.');
                $idx = (int)$conn->insert_id;
                $stmt->close();
            } else {
                $stmt = $conn->prepare('UPDATE dance SET title=?, org=?, kor=?, useflag=? WHERE idx=?');
                $stmt->bind_param('ssssi', $title, $org, $kor, $useflag, $idx);
                if (!$stmt->execute()) throw new RuntimeException('수정하지 못했습니다.');
                $stmt->close();
            }
            $dir = danceFolder($danceRoot, $idx);
            if ($uploads && !is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('data/dance/' . $idx . ' 폴더 생성에 실패했습니다.');
            if ($existing) {
                foreach ($remove as $slot) {
                    $column = 'photo' . $slot;
                    $old = (string)($existing[$column] ?? '');
                    $stmt = $conn->prepare("UPDATE dance SET `$column` = NULL WHERE idx = ?");
                    $stmt->bind_param('i', $idx);
                    $stmt->execute();
                    $stmt->close();
                    if ($old !== '' && basename($old) === $old) @unlink($dir . '/' . $old);
                }
            }
            foreach ($uploads as $k => $file) {
                $slot = $slots[$k];
                $filename = 'photo' . $slot . '_' . bin2hex(random_bytes(8)) . '.gif';
                if (!move_uploaded_file($file['tmp'], $dir . '/' . $filename)) throw new RuntimeException('사진 저장에 실패했습니다.');
                $column = 'photo' . $slot;
                $stmt = $conn->prepare("UPDATE dance SET `$column`=? WHERE idx=?");
                $stmt->bind_param('si', $filename, $idx);
                if (!$stmt->execute()) throw new RuntimeException('사진 DB 저장에 실패했습니다.');
                $stmt->close();
            }
            danceGo($new ? '등록되었습니다.' : '수정되었습니다.');
        } elseif ($action === 'delete') {
            if (!$existing) throw new RuntimeException('삭제할 항목이 존재하지 않습니다.');
            $stmt = $conn->prepare('DELETE FROM dance WHERE idx=?');
            $stmt->bind_param('i', $idx);
            if (!$stmt->execute()) throw new RuntimeException('삭제하지 못했습니다.');
            $stmt->close();
            danceRemoveFolder(danceFolder($danceRoot, $idx));
            danceGo('삭제되었습니다.');
        } else {
            throw new RuntimeException('알 수 없는 요청입니다.');
        }
    } catch (Throwable $e) {
        $danceError = $e->getMessage();
    }
}

$editIdx = max(0, (int)($_GET['edit'] ?? 0));
$edit = $editIdx ? danceFetch($conn, $editIdx) : null;
if ($editIdx && !$edit) $danceError = '수정할 항목이 없습니다.';
if (isset($_GET['msg'])) $danceNotice = (string)$_GET['msg'];
$rows = $conn->query('SELECT * FROM dance ORDER BY idx DESC');
?>
<style>
.dance-admin{max-width:1200px;margin:20px auto;font-family:inherit;color:#222}
.dance-admin *{box-sizing:border-box}.dance-admin h2{font-size:22px;margin:26px 0 16px}
.dance-admin .panel{border:1px solid #dce1e7;background:#fff;padding:22px;border-radius:10px}
.dance-admin label.field{display:block;font-weight:600;margin:14px 0 7px}
.dance-admin input[type=text],.dance-admin textarea,.dance-admin select{width:100%;border:1px solid #cdd3dc;border-radius:6px;padding:11px;font:inherit}
.dance-admin textarea{min-height:135px;resize:vertical;line-height:1.6}
.dance-admin .dropzone{padding:28px 16px;border:2px dashed #8ea7c5;border-radius:10px;text-align:center;background:#f7faff;cursor:pointer}
.dance-admin .dropzone.dragover{border-color:#2364b0;background:#e8f2ff}
.dance-admin .hint{color:#64748b;font-size:13px;margin:8px 0}
.dance-admin .preview,.dance-admin .thumbs{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
.dance-admin .preview-item,.dance-admin .existing-item{width:125px;border:1px solid #e2e8f0;padding:7px;border-radius:7px;text-align:center;overflow:hidden}
.dance-admin .preview img,.dance-admin .thumbs img{width:106px;height:90px;object-fit:contain;background:#f1f5f9}
.dance-admin .preview-item span{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:12px}
.dance-admin .actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:18px}
.dance-admin .btn{border:0;background:#2563eb;color:#fff;padding:9px 15px;border-radius:6px;cursor:pointer;text-decoration:none;display:inline-block;font-size:14px}
.dance-admin .btn.muted{background:#64748b}.dance-admin .btn.danger{background:#dc2626}
.dance-admin .table-scroll{overflow-x:auto}.dance-admin table{width:100%;border-collapse:collapse;font-size:14px}
.dance-admin th,.dance-admin td{padding:11px 9px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}
.dance-admin th{background:#f1f5f9;white-space:nowrap}.dance-admin .textcell{max-width:240px;white-space:pre-wrap;overflow-wrap:anywhere}
.dance-admin .mini{width:65px;height:60px;object-fit:contain;border:1px solid #e5e7eb;margin:2px;background:#f8fafc}
.dance-admin .alert{padding:12px;border-radius:6px;margin:12px 0;background:#fff2f2;color:#a11919}
.dance-admin .ok{background:#ecfdf5;color:#047857}.dance-admin .inline-form{display:inline}
</style>
<div class="dance-admin">
<h2><?= $edit ? '댄스 수정' : '댄스 등록' ?></h2>
<?php if ($danceError): ?><div class="alert"><?= danceEsc($danceError) ?></div><?php endif; ?>
<?php if ($danceNotice): ?><div class="alert ok"><?= danceEsc($danceNotice) ?></div><?php endif; ?>
<div class="panel">
<form id="danceForm" method="post" enctype="multipart/form-data" action="<?= danceEsc($danceUrl) ?>">
<input type="hidden" name="dance_csrf" value="<?= danceEsc($danceCsrf) ?>">
<input type="hidden" name="dance_action" value="save">
<input type="hidden" name="idx" value="<?= (int)($edit['idx'] ?? 0) ?>">
<label class="field">idx</label><input type="text" readonly value="<?= $edit ? (int)$edit['idx'] : '자동 생성' ?>">
<label class="field" for="danceTitle">제목 (title)</label>
<input id="danceTitle" name="title" type="text" maxlength="200" required value="<?= danceEsc($edit['title'] ?? '') ?>">
<label class="field" for="danceOrg">한문 원문 (org)</label>
<textarea id="danceOrg" name="org"><?= danceEsc($edit['org'] ?? '') ?></textarea>
<label class="field" for="danceKor">한글 번역 (kor)</label>
<textarea id="danceKor" name="kor"><?= danceEsc($edit['kor'] ?? '') ?></textarea>
<label class="field" for="danceUseflag">사용 여부 (useflag)</label>
<select id="danceUseflag" name="useflag"><option value="Y" <?= ($edit['useflag'] ?? 'Y') === 'Y' ? 'selected' : '' ?>>사용 (Y)</option><option value="N" <?= ($edit['useflag'] ?? 'Y') === 'N' ? 'selected' : '' ?>>미사용 (N)</option></select>
<?php
$existingCount = 0;
if ($edit):
    for ($i=1; $i<=10; $i++) if (!empty($edit['photo'.$i])) $existingCount++;
?>
<label class="field">등록된 GIF (삭제할 사진 선택)</label>
<div class="thumbs">
<?php for ($i=1; $i<=10; $i++): $n=(string)($edit['photo'.$i] ?? ''); if ($n === '' || basename($n) !== $n) continue; ?>
<label class="existing-item"><img alt="사진 <?= $i ?>" src="<?= danceEsc(danceFileUrl((int)$edit['idx'], $n)) ?>"><span style="display:block"><input type="checkbox" name="remove_photos[]" value="<?= $i ?>" class="dance-remove"> 삭제 (<?= $i ?>번)</span></label>
<?php endfor; ?>
</div>
<?php endif; ?>
<label class="field">GIF 업로드 (최대 10장)</label>
<div id="danceDropzone" class="dropzone" role="button" tabindex="0" aria-label="GIF 파일 드래그 앤 드롭 또는 선택">
<strong>여기에 GIF 파일을 드래그 앤 드롭하세요.</strong><div>또는 클릭하여 파일 선택</div>
<p class="hint">GIF만 가능 · 사진당 최대 10MB · 총 10장 (기존 사진 포함)</p>
</div>
<input id="dancePhotos" type="file" name="photos[]" accept=".gif,image/gif" multiple style="display:none">
<div id="dancePreview" class="preview" aria-live="polite"></div>
<p id="danceFileInfo" class="hint"></p>
<div class="actions"><button type="submit" class="btn"><?= $edit ? '수정 저장' : '등록' ?></button><?php if ($edit): ?><a class="btn muted" href="<?= danceEsc($danceUrl) ?>">취소 / 새로 등록</a><?php endif; ?></div>
</form>
</div>
<h2>댄스 전체 목록</h2>
<div class="table-scroll"><table>
<thead><tr><th>idx</th><th>제목</th><th>한문 원문</th><th>한글 번역</th><th>사진</th><th>사용</th><th>관리</th></tr></thead><tbody>
<?php if ($rows && $rows->num_rows): while ($r=$rows->fetch_assoc()): ?>
<tr><td><?= (int)$r['idx'] ?></td><td><?= danceEsc($r['title']) ?></td><td class="textcell"><?= danceEsc($r['org']) ?></td><td class="textcell"><?= danceEsc($r['kor']) ?></td>
<td><div style="min-width:90px;max-width:250px"><?php for ($i=1;$i<=10;$i++): $n=(string)($r['photo'.$i] ?? ''); if ($n === '' || basename($n) !== $n) continue; ?><a href="<?= danceEsc(danceFileUrl((int)$r['idx'], $n)) ?>" target="_blank" rel="noopener"><img class="mini" alt="GIF <?= $i ?>" src="<?= danceEsc(danceFileUrl((int)$r['idx'], $n)) ?>"></a><?php endfor; ?></div></td>
<td><?= danceEsc($r['useflag']) ?></td>
<td style="white-space:nowrap"><a class="btn" href="<?= danceEsc($danceUrl) ?>&amp;edit=<?= (int)$r['idx'] ?>">수정</a>
<form class="inline-form" method="post" action="<?= danceEsc($danceUrl) ?>" onsubmit="return confirm('정말 삭제하시겠습니까?');"><input type="hidden" name="dance_csrf" value="<?= danceEsc($danceCsrf) ?>"><input type="hidden" name="dance_action" value="delete"><input type="hidden" name="idx" value="<?= (int)$r['idx'] ?>"><button class="btn danger" type="submit">삭제</button></form></td></tr>
<?php endwhile; else: ?><tr><td colspan="7" style="text-align:center">등록된 데이터가 없습니다.</td></tr><?php endif; ?>
</tbody></table></div>
</div>
<script>
(function(){
  const zone=document.getElementById('danceDropzone'),input=document.getElementById('dancePhotos');
  const preview=document.getElementById('dancePreview'),info=document.getElementById('danceFileInfo');
  const form=document.getElementById('danceForm'),removed=[...form.querySelectorAll('.dance-remove')];
  const existing=<?= (int)$existingCount ?>;
  let selected=[];
  function maxAllowed(){return 10-existing+removed.filter(c=>c.checked).length;}
  function sync(){
    const dt=new DataTransfer(); selected.forEach(f=>dt.items.add(f)); input.files=dt.files;
    preview.innerHTML='';
    selected.forEach((f,i)=>{
      const div=document.createElement('div');div.className='preview-item';
      const img=document.createElement('img');img.alt=f.name;const url=URL.createObjectURL(f);img.src=url;img.onload=()=>URL.revokeObjectURL(url);
      const name=document.createElement('span');name.textContent=f.name;
      const btn=document.createElement('button');btn.type='button';btn.textContent='제외';btn.className='btn muted';btn.style.padding='4px 10px';
      btn.onclick=()=>{selected.splice(i,1);sync();};div.append(img,name,btn);preview.append(div);
    });
    info.textContent='추가할 GIF: '+selected.length+'장 / 추가 가능: '+maxAllowed()+'장';
  }
  function add(files){
    let invalid=0,oversize=0,overflow=0;
    for(const f of files){
      if(!(f.type==='image/gif'||/\.gif$/i.test(f.name))){invalid++;continue;}
      if(f.size>10485760){oversize++;continue;}
      if(selected.length>=maxAllowed()){overflow++;continue;}
      selected.push(f);
    }
    sync();
    if(invalid||oversize||overflow) alert([invalid&&'GIF가 아닌 파일 '+invalid+'개 제외',oversize&&'10MB 초과 '+oversize+'개 제외',overflow&&'최대 10장 초과 '+overflow+'개 제외'].filter(Boolean).join('\n'));
  }
  zone.onclick=()=>input.click();zone.onkeydown=e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();input.click();}};
  input.onchange=e=>{const files=[...e.target.files];add(files);};
  for(const ev of ['dragenter','dragover']) zone.addEventListener(ev,e=>{e.preventDefault();zone.classList.add('dragover');});
  for(const ev of ['dragleave','drop']) zone.addEventListener(ev,e=>{e.preventDefault();zone.classList.remove('dragover');});
  zone.addEventListener('drop',e=>add([...e.dataTransfer.files]));
  removed.forEach(c=>c.onchange=()=>{while(selected.length>maxAllowed())selected.pop();sync();});
  form.addEventListener('submit',e=>{if(selected.length>maxAllowed()){e.preventDefault();alert('사진은 최대 10장입니다.');}});
  sync();
})();
</script>
