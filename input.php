<!-- input.php : index.php 본문에서 include하여 사용 -->

<div class="container py-4">
    <h2 class="mb-4">HTML Form 입력 요소 예제</h2>

    <form method="post" action="" enctype="multipart/form-data">

        <div class="mb-3">
            <label for="user_name" class="form-label">이름 (text)</label>
            <input type="text" class="form-control" id="user_name" name="user_name"
                   placeholder="이름을 입력하세요">
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">비밀번호 (password)</label>
            <input type="password" class="form-control" id="password" name="password"
                   placeholder="비밀번호를 입력하세요">
        </div>

        <div class="mb-3">
            <label for="memo" class="form-label">자기소개 (textarea)</label>
            <textarea class="form-control" id="memo" name="memo" rows="4"
                      placeholder="여러 줄의 내용을 입력할 수 있습니다."></textarea>
        </div>

        <div class="mb-3">
            <label for="department" class="form-label">학과 선택 (select)</label>
            <select class="form-select" id="department" name="department">
                <option value="">학과를 선택하세요</option>
                <option value="한문학과">한문학과</option>
                <option value="국어국문학과">국어국문학과</option>
                <option value="영어영문학과">영어영문학과</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label d-block">성별 선택 (radio)</label>

            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="gender"
                       id="gender_male" value="남성">
                <label class="form-check-label" for="gender_male">남성</label>
            </div>

            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="gender"
                       id="gender_female" value="여성" checked>
                <label class="form-check-label" for="gender_female">여성</label>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label d-block">취미 선택 (checkbox)</label>

            <div class="form-check">
                <input class="form-check-input" type="checkbox"
                       name="hobby[]" id="hobby_reading" value="독서">
                <label class="form-check-label" for="hobby_reading">독서</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="checkbox"
                       name="hobby[]" id="hobby_music" value="음악">
                <label class="form-check-label" for="hobby_music">음악</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="checkbox"
                       name="hobby[]" id="hobby_sports" value="운동">
                <label class="form-check-label" for="hobby_sports">운동</label>
            </div>
        </div>

        <div class="mb-3">
            <label for="grade" class="form-label">학년 선택 (number)</label>
            <input type="number" class="form-control" id="grade" name="grade"
                   min="1" max="14" step="3" value="1">
        </div>

        <div class="mb-3">
            <label for="today" class="form-label">날짜 선택 (date)</label>
            <input type="date" class="form-control" id="today" name="today">
        </div>

        <div class="mb-3">
            <label for="favorite_color" class="form-label">색상 선택 (color)</label>
            <input type="color" class="form-control form-control-color"
                   id="favorite_color" name="favorite_color" value="#0d6efd">
        </div>

        <div class="mb-3">
            <label for="upload_file" class="form-label">파일 선택 (file)</label>
            <input type="file" class="form-control" id="upload_file" name="upload_file">
        </div>

        <div class="mb-3">
            <label class="form-label d-block">일반 버튼 (button)</label>
            <button type="button" class="btn btn-secondary"
                    onclick="alert('일반 버튼을 클릭했습니다.')">
                버튼
            </button>
        </div>

        <hr>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">전송</button>
            <button type="reset" class="btn btn-outline-secondary">초기화</button>
        </div>

    </form>
</div>
