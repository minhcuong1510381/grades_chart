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

$query = "SELECT u.id, u.firstname, u.lastname
          FROM {user} u
          INNER JOIN {role_assignments} ra ON ra.userid = u.id
          INNER JOIN {context} ct ON ct.id = ra.contextid 
          WHERE u.suspended = 0 AND ct.contextlevel = 50 AND ct.instanceid = $courseId
          ORDER BY u.id ASC";

$aUser = block_grades_chart_convert_to_array($DB->get_records_sql($query));


$sqlCourseSection = "SELECT name, sequence
						FROM {course_sections}
						WHERE name IS NOT NULL AND course = $courseId
						ORDER BY id";

$courseSections = $DB->get_records_sql($sqlCourseSection);

$courseSections = block_grades_chart_convert_to_array($courseSections);

$arrQuiz = [];

foreach ($aUser as $k => $user) {
    $userId = $user->{'id'};
    foreach ($courseSections as $key => $value) {
        $temp = $courseSections[$key]->{'sequence'};

        $sqlQuiz = "SELECT cm.id, cm.instance, qg.grade
			FROM {course_modules} cm
			LEFT JOIN {quiz_grades} qg ON qg.quiz = cm.instance
			WHERE cm.id IN ($temp) AND cm.module = 16 AND cm.course = $courseId AND qg.userid = $userId";

        $sqlQuizt = "SELECT cm.id, cm.instance, 0 AS grade
            FROM {course_modules} cm
            WHERE cm.id IN ($temp) AND cm.module = 16 AND cm.course = $courseId ";

        $a = $DB->get_records_sql($sqlQuizt);

        $b1 = $DB->get_records_sql($sqlQuiz);

        if ($a) {
            $arrQuiz[] = block_grades_chart_convert_to_array($b1) + ["name" => $value->{'name'}, "user" => $user->{'id'}, "firstname" => $user->{'firstname'}, "lastname" => $user->{'lastname'}];

            foreach ($arrQuiz as $key => $value) {
                if (!array_key_exists("grade", $value[0])) {
                    array_push($arrQuiz[$key], ["grade" => 0]);
                }
            }
        }
    }
}

$arrQuiz = groupArray($arrQuiz, "user");

$res = [];

foreach ($arrQuiz as $h => $rows) {
    $sumTemp = 0;
    foreach ($rows as $row) {
        $sumTemp1 = 0;
        foreach ($row as $v) {
            $sumTemp1 += $v->{'grade'};
        }
        $sumTemp += round($sumTemp1 / (count($row) - 3), 1);
    }
    $res[] = ["ave" => round($sumTemp / (count($rows)), 1), "id" => $h, "firstname" => $rows[0]['firstname'], "lastname" => $rows[0]['lastname']];
}

$sumPass = 0;

$sumNotPass = 0;

$url = new moodle_url('/blocks/grades_chart/detail_student.php', array('courseId' => $courseId));

?>
<?php include('inc/header.php') ?>
<div class="container" style="height: 700px;">
    <div class="header">
        <div class="title-gradeschart" style="margin: 0 auto; width: 500px; text-align: center">
            <h3>Tổng kết điểm trung bình của sinh viên trong khóa học</h3>
        </div>
    </div>
    <div class="content" style="margin: 0 auto; width: 700px; text-align: center; padding-top: 30px;">
        <table class="table table-bordered ave-table">
            <thead>
            <tr>
                <th>Họ tên sinh viên</th>
                <th>Điểm trung bình khóa học</th>
                <th>Đánh giá</th>
                <th>Chi tiết</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($res as $key => $value) { ?>
                <tr>
                    <td><?php echo $value['firstname'] . " " . $value['lastname'] ?></td>
                    <td><?php echo number_format($value['ave'], 1, '.', '') ?></td>
                    <td><?php if ($value['ave'] >= 5) {
                            $sumPass++; ?><i class="fa fa-check"></i><?php } else {
                            $sumNotPass++; ?>
                            <i class="fa fa-times"></i><?php } ?></td>
                    <td>
                        <a href="<?php echo $url . "&userName=" . $value['firstname'] . " " . $value['lastname'] . "&userId=" . $value['id']; ?>"
                           target="_blank">Xem chi tiết</a></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <table class="table table-bordered">
            <tr>
                <th rowspan="2" style="vertical-align : middle;text-align:center;">Tổng kết</th>
                <td>Số lượng sinh viên đạt</td>
                <td><?php echo $sumPass; ?></td>
            </tr>
            <tr>
                <td>Số lượng sinh viên không đạt</td>
                <td><?php echo $sumNotPass; ?></td>
            </tr>
        </table>
    </div>
</div>

<?php include('inc/footer.php') ?>
</body>
</html>
