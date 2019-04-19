<?php
require_once("../../config.php");
require_once($CFG->dirroot . '/lib/moodlelib.php');
global $DB, $USER;
require("lib.php");
// include('data.php');

$courseId = $_GET['courseId'];

require_login($courseId);

$context = context_course::instance($courseId);

$roles = get_user_roles($context, $USER->id);

$isStudent = current(get_user_roles($context, $USER->id))->shortname == 'student' ? 1 : 2;

if ($isStudent == 1) {
    return false;
}

$query1 = "SELECT name, course, id
            FROM {quiz}
            WHERE course = $courseId";

$aQuiz = block_grades_chart_convert_to_array($DB->get_records_sql($query1));

$aUserGt = [];
$aUserLt = [];

foreach ($aQuiz as $key => $value) {
    $idQuiz = $value->{'id'};
    $q = "SELECT u.id, u.firstname, u.lastname, u.email
            FROM {user} u
            INNER JOIN {quiz_grades} qg ON qg.userid = u.id
            INNER JOIN {role_assignments} ra ON ra.userid = u.id
            INNER JOIN {context} ct ON ct.id = ra.contextid 
            WHERE qg.quiz = $idQuiz AND qg.grade >= 5 AND u.suspended = 0 AND ct.contextlevel = 50 AND ct.instanceid = $courseId
            ORDER BY u.id ASC";

    $aUserGt[] = block_grades_chart_convert_to_array($DB->get_records_sql($q)) + ["quizId" => $idQuiz];
}
$aId = [];
foreach ($aUserGt as $h => $r) {
    for ($i = 0; $i < count($r) - 1; $i++) {
        $userId = $r[$i]->{'id'};
        $aId[] = ["userId" => $userId, "quizId" => $r['quizId']];
    }
}

$aId = groupArray($aId, "quizId");

$temp = [];

foreach ($aId as $t => $a) {
    $temp[] = ["id" => implode(",", $a), "quizId" => $t];
}

foreach ($temp as $t) {
    $aId = $t['id'];
    $query = "SELECT u.id, u.firstname, u.lastname, u.email
          FROM {user} u
          INNER JOIN {role_assignments} ra ON ra.userid = u.id
          INNER JOIN {context} ct ON ct.id = ra.contextid 
          WHERE u.suspended = 0 AND ct.contextlevel = 50 AND ct.instanceid = $courseId AND u.id NOT IN ($aId)
          ORDER BY u.id ASC";

    $aUserLt[] = block_grades_chart_convert_to_array($DB->get_records_sql($query)) + ["quizId" => $t['quizId']];
}

//echo "<pre>";
//print_r($aUserLt);die;

?>
<?php include('inc/header.php') ?>
<div class="container" style="height: 1100px;">
    <div class="header">
        <div class="title-gradeschart" style="margin: 0 auto; width: 500px; text-align: center">
            <h3>Biểu đồ đánh giá mức độ hiểu quả của từng bài kiểm tra trong khóa học</h3>
        </div>
    </div>
    <div class="content">
        <form id="form-chapter" style="margin-top: 20px;">
            <input type="hidden" id="courseId" name="courseId" value="<?php echo $courseId; ?>">
            <button id="submit" type="submit" class="btn btn-danger"
                    style="margin-left: 40%; width: 200px; margin-top: 10px;">Xem biểu đồ
            </button>
        </form>
    </div>
    <div class="chart-container" style="display: none; width: 800px;">
        <canvas id="chapterCanvas" style="width: 800px"></canvas>
    </div>
    <div class="table-container" style="display: none; padding-top: 20px; margin: 0 auto; width: 500px;">
    </div>
    <div class="detail" style="display:none; margin: 0 auto; width: 100px; text-align: center">
        <button type="button" id="detail" class="btn btn-primary">Chi tiết</button>
    </div>
    <div class="graph" style="display:none; margin: 0 auto; width: 100px; text-align: center">
        <button type="button" id="graph" class="btn btn-success">Đồ thị</button>
    </div>
    <?php foreach ($aUserGt as $key => $value) { ?>
        <div class="modal" id="modalPass[<?php echo $value['quizId']; ?>]">
            <div class="modal-dialog">
                <div class="modal-content">

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">Những sinh viên trên điểm trung bình</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

                    <!-- Modal body -->
                    <div class="modal-body">
                        <?php for ($i = 0; $i < count($value) - 1; $i++) { ?>
                            <a href="javascript:void(0);" onclick="getEmail('<?php echo $value[$i]->{'email'}; ?>');" style="font-size: 14px;"><?php echo $value[$i]->{'lastname'} . " " . $value[$i]->{'firstname'} . ","; ?></a>
                        <?php } ?>
                        <hr>
                        <div class="send-mail">
                            <h5>Gửi mail cho những sinh viên trên</h5>
                            <form action="">
                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label" for="inputTo">Tới: </label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="inputTo" aria-describedby="toEmail"
                                               name="inputTo">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label" for="inputSubject">Chủ đề: </label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="inputSubject"
                                               aria-describedby="subjectEmail" name="inputSubject">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <textarea name="inputContent" id="inputContent" cols="30" rows="10" style="width: 100%"></textarea>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Gửi</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php foreach ($aUserLt as $k => $v) { ?>
        <div class="modal" id="modalNotPass[<?php echo $v['quizId']; ?>]">
            <div class="modal-dialog">
                <div class="modal-content">

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">Những sinh viên dưới điểm trung bình</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

                    <!-- Modal body -->
                    <div class="modal-body">
                        <?php for ($i = 0; $i < count($v) - 1; $i++) { ?>
                            <span style="font-size: 14px;"><?php echo $v[$i]->{'lastname'} . " " . $v[$i]->{'firstname'} . ","; ?></span>
                        <?php } ?>
                        <hr>
                        <div class="send-mail">
                            <h5>Gửi mail cho những sinh viên trên</h5>
                            <form action="">
                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label" for="inputTo">Tới: </label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="inputTo" aria-describedby="toEmail"
                                               name="inputTo">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label" for="inputSubject">Chủ đề: </label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="inputSubject"
                                               aria-describedby="subjectEmail" name="inputSubject">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <textarea name="inputContent" id="inputContent" cols="30" rows="10" style="width: 100%"></textarea>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Gửi</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                    </div>

                </div>
            </div>
        </div>
    <?php } ?>
</div>

<?php include('inc/footer.php') ?>
<script>
    function getEmail(data) {
        // alert(data);
    }
</script>
</body>
</html>
