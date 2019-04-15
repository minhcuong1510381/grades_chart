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

$qr = "SELECT q.name, cm.id
        FROM {quiz} q
        INNER JOIN {course_modules} cm ON q.id = cm.instance
        WHERE q.course = $courseId AND cm.module = 16";

$qrArr = block_grades_chart_convert_to_array($DB->get_records_sql($qr));

$quiz1 = [];
$result = [];
foreach ($aQuiz as $key => $value) {
    $t = $value->{'quizid'};
    $query2 = "SELECT q.name, cm.id
                FROM {quiz} q
                INNER JOIN {course_modules} cm ON q.id = cm.instance
                WHERE q.id = $t AND cm.module = 16";

    $quiz1[] = block_grades_chart_convert_to_array($DB->get_records_sql($query2));

    foreach ($qrArr as $k => $r) {
        if ($r->{'id'} == $quiz1[$key][0]->{'id'}) {
            unset($qrArr[$k]);
        }
    }
    $result[] = ["state" => $value->{'state'}, "questionsummary" => $value->{'questionsummary'}, "rightanswer" => $value->{'rightanswer'}, "responsesummary" => $value->{'responsesummary'}, "nameQuiz" => $quiz1[$key][0]->{'name'}, "idQuiz" => $quiz1[$key][0]->{'id'}];
}

foreach ($qrArr as $q) {
    $result[] = ["nameQuiz" => $q->{'name'}, "idQuiz" => $q->{'id'}];
}

$result = groupArray($result, "idQuiz");

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
                        <td><a href="<?php echo $CFG->wwwroot . '/mod/quiz/view.php?id=' . $name; ?>"
                               target="_blank"><?php echo $items[0]['nameQuiz']; ?></a></td>
                    <?php } ?>
                    <?php for ($i = 0; $i < countMaxArray($result); $i++) { ?>
                        <?php if (!is_array($items[0])) { ?>
                            <td>-</td>
                        <?php } else { ?>
                            <?php if ($items[$i]) { ?>
                                <?php if ($items[$i]['state'] == "gradedright") { ?>
                                    <td><a href="javascript:void(0);" data-toggle="tooltip" data-html="true"
                                           data-placement="right"
                                           title="<?php echo '***Câu hỏi là: ' . '&#013;' . htmlentities($items[$i]['questionsummary']) . '&#013;' . '***Câu trả lời của bạn: ' . htmlentities($items[$i]['responsesummary']) . '&#013;' . '***Đáp án: ' . htmlentities($items[$i]['rightanswer']); ?>"><i
                                                    class="fa fa-check"></i></a></td>
                                <?php } else { ?>
                                    <td><a href="javascript:void(0);" data-toggle="tooltip" data-html="true"
                                           data-placement="right"
                                           title="<?php echo '***Câu hỏi là: ' . '&#013;' . htmlentities($items[$i]['questionsummary']) . '&#013;' . '***Câu trả lời của bạn: ' . htmlentities($items[$i]['responsesummary']) . '&#013;' . '***Đáp án: ' . htmlentities($items[$i]['rightanswer']); ?>"><i
                                                    class="fa fa-times"></i></a></td>
                                <?php } ?>
                            <?php } else { ?>
                                <td>-</td>
                            <?php } ?>
                        <?php } ?>
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
