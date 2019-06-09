<head>
    <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.7.0/css/all.css' integrity='sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ' crossorigin='anonymous'>
</head>
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

if ($isStudent == 2) {
    return false;
}

$query = "SELECT u.id, u.firstname, u.lastname
          FROM {user} u
          INNER JOIN {role_assignments} ra ON ra.userid = u.id
          INNER JOIN {context} ct ON ct.id = ra.contextid 
          WHERE u.suspended = 0 AND ct.contextlevel = 50 AND ct.instanceid = $courseId
          ORDER BY u.id ASC";

$res = $DB->get_records_sql($query);

$query1 = "SELECT *
FROM {question_attempt_steps} qas
INNER JOIN {question_attempts} qa ON qa.id = qas.questionattemptid
INNER JOIN {question} q ON qa.questionid = q.id
INNER JOIN {quiz_slots} qs ON qa.questionid = qs.questionid
WHERE qas.state <> 'todo' AND qas.state <> 'complete' AND qas.userid = $studentId
ORDER BY qas.id";
$quiz = $DB->get_records_sql($query1);

$aQuiz = block_grades_chart_convert_to_array($quiz);

$qr = "SELECT q.name, cm.id, qg.grade
        FROM {quiz} q
        INNER JOIN {course_modules} cm ON q.id = cm.instance
        INNER JOIN {quiz_grades} qg ON qg.quiz = q.id
        WHERE q.course = $courseId AND cm.module = 16 AND qg.userid = $studentId";

$qrArr = block_grades_chart_convert_to_array($DB->get_records_sql($qr));

//echo "<pre>";
//print_r($qrArr);die;

$quiz1 = [];
$result = [];
foreach ($aQuiz as $key => $value) {
    $t = $value->{'quizid'};
    $query2 = "SELECT q.name, cm.id, qg.grade
                FROM {quiz} q
                INNER JOIN {course_modules} cm ON q.id = cm.instance
                INNER JOIN {quiz_grades} qg ON qg.quiz = q.id
                WHERE q.id = $t AND cm.module = 16 AND qg.userid = $studentId";

    $quiz1[] = block_grades_chart_convert_to_array($DB->get_records_sql($query2));

    foreach ($qrArr as $k => $r) {
        if ($r->{'id'} == $quiz1[$key][0]->{'id'}) {
            unset($qrArr[$k]);
        }
    }
    $result[] = ["questionId" => $value->{'questionid'}, "state" => $value->{'state'}, "questionsummary" => $value->{'questionsummary'}, "rightanswer" => $value->{'rightanswer'}, "responsesummary" => $value->{'responsesummary'}, "nameQuiz" => $quiz1[$key][0]->{'name'}, "idQuiz" => $quiz1[$key][0]->{'id'}, "grade" => $quiz1[$key][0]->{'grade'}];
}


foreach ($qrArr as $q) {
    $result[] = ["nameQuiz" => $q->{'name'}, "idQuiz" => $q->{'id'}];
}

$result = groupArray($result, "idQuiz");

foreach ($result as $key => $value) {
    if ($key == '') {
        unset($result[$key]);
    }
}

if($result == null){
    echo("Không có dữ liệu cho sinh viên này.");
    exit;
}

$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";

?>
<?php include('inc/header.php') ?>
<div class="container">
    <div class="header">
        <div class="title-gradeschart" style="margin: 0 auto; width: 500px; text-align: center">
            <h3>Đánh giá chi tiết môn học</h3>
        </div>
    </div>
    <div class="content" style="margin-top: 30px;">
        <table class="table table-bordered">
            <thead>
            <tr>
                <th></th>
                <?php for ($i = 1; $i <= countMaxArray($result); $i++) { ?>
                    <th>Câu <?php echo $i; ?></th>
                <?php } ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($result as $name => $items) { ?>
                <tr>
                    <?php if (!is_array($items[0])) { ?>
                        <td><a href="<?php echo $CFG->wwwroot . '/mod/quiz/view.php?id=' . $name; ?>"
                               target="_blank"><span><?php echo $items[0]; ?></span><i
                                        class="fa fa-exclamation-triangle" style="color: orange"></i></a></td>
                    <?php } else { ?>
                        <td><a title="<?php echo $items[0]['grade'].'/'.'10.00000'; ?>" href="<?php echo $CFG->wwwroot . '/mod/quiz/view.php?id=' . $name; ?>"
                               target="_blank"><?php echo $items[0]['nameQuiz']; ?></a>
                            <?php if ($items[0]['grade'] >= 8.0) { ?>
                                <i class="far fa-grin-hearts" style="color: orange"></i>
                            <?php } else if ($items[0]['grade'] >= 7.0 && $items[0]['grade'] < 8.0) { ?>
                                <i class="far fa-grin-beam" style="color: green"></i>
                            <?php } else if ($items[0]['grade'] >= 5.0 && $items[0]['grade'] < 7.0) { ?>
                                <i class="far fa-frown" style="color: yellow"></i>
                            <?php } else { ?>
                                <i class="far fa-sad-cry" style="color: red"></i>
                            <?php } ?>
                        </td>
                    <?php } ?>
                    <?php for ($i = 0; $i < countMaxArray($result); $i++) { ?>
                        <?php if (!is_array($items[0])) { ?>
                            <td>-</td>
                        <?php } else { ?>
                            <?php if ($items[$i]) { ?>
                                <?php if ($items[$i]['state'] == "gradedright") { ?>
                                    <td><a href="javascript:void(0);" data-toggle="modal"
                                           data-target="#myModal-[<?php echo $items[$i]['questionId'] ?>]"><i
                                                    class="fa fa-check"></i></a></td>
                                <?php } else if ($items[$i]['state'] == "gradedwrong" || $items[$i]['state'] == "gaveup") { ?>
                                    <td><a href="javascript:void(0);" data-toggle="modal"
                                           data-target="#myModal-[<?php echo $items[$i]['questionId'] ?>]"><i
                                                    class="fa fa-times"></i></a></td>
                                <?php } else {  ?>
                                    <td><a href="javascript:void(0);" data-toggle="modal"
                                           data-target="#myModal-[<?php echo $items[$i]['questionId'] ?>]"><i
                                                    class="fa fa-circle" style="color: yellow"></i></a></td>
                                <?php } ?>
                            <?php } else { ?>
                                <td>-</td>
                            <?php } ?>
                        <?php } ?>
                        <div class="modal" id="myModal-[<?php echo $items[$i]['questionId'] ?>]">
                            <div class="modal-dialog">
                                <div class="modal-content">

                                    <!-- Modal Header -->
                                    <div class="modal-header">
                                        <h4 class="modal-title">Hướng dẫn</h4>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>

                                    <!-- Modal body -->
                                    <div class="modal-body">
                                        <b>Câu hỏi là:</b>
                                        <p><?php echo nl2br($items[$i]['questionsummary']); ?></p>
                                        <b>Câu trả lời của bạn là:</b>
                                        <p><?php echo nl2br($items[$i]['responsesummary']); ?></p>
                                        <b>Đáp án là:</b>
                                        <p><?php echo nl2br($items[$i]['rightanswer']); ?></p>
                                        <b>Tham khảo</b>
                                        <?php if(preg_match($reg_exUrl,block_grades_chart_get_instruction($items[$i]['questionId']))){ ?>
                                        <p><?php echo '<a target="_blank" href="'.block_grades_chart_get_instruction($items[$i]['questionId']).'">'.block_grades_chart_get_instruction($items[$i]['questionId']).'</a>' ?></p>
                                        <?php } else { ?>
                                        <p><?php echo block_grades_chart_get_instruction($items[$i]['questionId']); ?></p>
                                        <?php } ?>
                                    </div>
                                    <!-- Modal footer -->
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                                    </div>

                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </tr>

            <?php } ?>
            </tbody>
        </table>
        <div class="redirect-course" style="margin: 0 auto; width: 500px; text-align: center">
            <a href="<?php echo $CFG->wwwroot . '/course/view.php?id=' . $courseId; ?>">
                <button type="button" class="btn btn-primary">Trở về khóa học</button>
            </a>
        </div>
    </div>
</div>
<?php include('inc/footer.php') ?>
</body>
</html>
