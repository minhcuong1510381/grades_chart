<?php
require_once("../../config.php");
require_once($CFG->dirroot . '/lib/moodlelib.php');
global $DB, $USER, $CFG;
require("lib.php");

$courseId = $_GET['courseId'];

require_login($courseId);

$context = context_course::instance($courseId);

$studentId = $USER->id;

$roles = get_user_roles($context, $studentId);

$isStudent = current(get_user_roles($context, $USER->id))->shortname == 'student' ? 1 : 2;

if ($isStudent == 1) {
    return false;
}

$students = block_grades_chart_get_students($courseId);
$numberofstudents = count($students);
if ($numberofstudents == 0) {
    echo("Không có sinh viên trong khóa học");
    exit;
}

$query = "SELECT question.questiontext, question.id as questionid, quiz.name, quiz.id as quizid
            FROM {question} question
            INNER JOIN {quiz_slots} qs ON qs.questionid = question.id
            INNER JOIN {quiz} quiz ON qs.quizid = quiz.id
            WHERE quiz.course = $courseId";

$quiz = $DB->get_records_sql($query);

$aQuiz = json_decode(json_encode(block_grades_chart_convert_to_array($quiz)), True);

$result = groupArray($aQuiz, "quizid");


?>
<?php include('inc/header.php') ?>
<div class="container">
    <div class="header">
        <div class="title-gradeschart" style="margin: 0 auto; width: 500px; text-align: center">
            <h3>Bảng hỗ trợ sinh viên</h3>
        </div>
    </div>
    <div class="content" style="margin-top: 30px;">
        <?php if($_GET['response'] == 1) { ?>
        <div class="alert alert-success" id="alert" role="alert"
                    style="margin: 0 auto; width: 700px; display: none;">
            Chỉnh sửa tài liệu tham khảo thành công
        </div>
        <?php } ?>
        <?php if($_GET['response'] == 0) { ?>
        <div class="alert alert-success" id="alert" role="alert"
                    style="margin: 0 auto; width: 700px; display: none;">
            Thêm tài liệu tham khảo thành công
        </div>
        <?php } ?>
        <br>
        <table class="table table-bordered" style="width: 700px; margin: 0 auto;">
            <thead>
            <tr>
                <th>Tên bài kiểm tra</th>
                <th>Hành động</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($result as $key => $row) { ?>
                <tr>
                    <td><?php echo $row[0]['name']; ?></td>
                    <td>
                        <button type="button" class="btn btn-info" data-toggle="modal"
                                data-target="#myModal[<?php echo $key; ?>]">Thêm hướng dẫn
                        </button>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <form action="postInstruction.php" method="post">
            <input type="hidden" name="courseId" value="<?php echo $courseId; ?>">
            <?php foreach ($result as $key => $row) { ?>
                <div id="myModal[<?php echo $key; ?>]" class="modal fade" role="dialog">
                    <div class="modal-dialog">
                        <!-- Modal content-->
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4>Thêm chi tiết hướng dẫn các câu hỏi</h4>
                            </div>
                            <?php foreach ($row as $k => $v) { ?>
                                <div class="modal-body">
                                    <a data-toggle="collapse" data-html="true" data-placement="right"
                                       href="#cauhoi[<?php echo $k; ?>]" role="button" aria-expanded="false"
                                       title="<?php echo htmlentities($v['questiontext']); ?>">
                                        Câu hỏi thứ <?php echo $k + 1; ?>
                                    </a>
                                    <textarea class="collapse" name="questionId[<?php echo($row[$k]['questionid']) ?>]"
                                              style="width: 100%;"
                                              id="cauhoi[<?php echo $k; ?>]"><?php echo block_grades_chart_get_instruction($row[$k]['questionid']); ?></textarea>
                                </div>
                            <?php } ?>
                            <div class="modal-footer">
                                <button type="submit" name="btn-submit" class="btn btn-danger">Xác nhận</button>
                                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </form>
        <div class="redirect-course" style="margin: 0 auto; width: 500px; text-align: center; margin-top: 20px;">
            <a href="<?php echo $CFG->wwwroot . '/course/view.php?id=' . $courseId; ?>">
                <button type="button" class="btn btn-primary">Trở về khóa học</button>
            </a>
        </div>
        <br>
    </div>
</div>

<?php include('inc/footer.php') ?>
<script>
    $(document).ready(function () {
        $('[data-toggle="collapse"]').tooltip();
        if(<?php echo $_GET['response']; ?> == 1 || <?php echo $_GET['response']; ?> == 0){
            $('#alert').css("display", "block");

            $('#alert').delay(3000).fadeOut("slow");
        }
    });
</script>
</body>
</html>
