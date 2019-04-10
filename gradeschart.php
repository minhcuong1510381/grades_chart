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

$res = $DB->get_records_sql($query);

?>
<?php include('inc/header.php') ?>
    <div class="container" style="height: 700px;">
        <div class="header">
            <div class="title-gradeschart" style="margin: 0 auto; width: 500px; text-align: center">
                <h3>Biểu đồ đánh giá chi tiết năng lực của sinh viên</h3>
            </div>
            <form id="myform" style="margin-top: 20px;">
                <input type="hidden" id="courseId" name="courseId" value="<?php echo $courseId; ?>">
                <div class="alert alert-danger" id="alert" role="alert"
                     style="margin: 0 auto; width: 500px; display: none;">
                    Chọn sinh viên để so sánh không phù hợp.
                </div>
                <p style="margin: 0 auto; width: 500px;">Chọn sinh viên:</p>
                <div class="input-group choose-student" style="margin: 0 auto; width: 500px;">
                    <select class="custom-select" id="student" name="studentId">
                        <?php foreach ($res as $key => $value) { ?>
                            <option value="<?php echo $value->{'id'}; ?>">
                                <?php echo $value->{'firstname'} . " " . $value->{'lastname'}; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="compare">So sánh</button>
                    </div>
                </div>
                <div class="form-group choose-student-compare" style="margin: 0 auto; width: 500px; display: none;"
                     id="compare-student">
                    <label>Chọn sinh viên để so sánh:</label>
                    <select class="form-control" id="choose-compare-student">
                        <?php foreach ($res as $key => $value) { ?>
                            <option value="<?php echo $value->{'id'}; ?>">
                                <?php echo $value->{'firstname'} . " " . $value->{'lastname'}; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <button id="submit" type="submit" class="btn btn-danger"
                        style="margin-left: 40%; width: 200px; margin-top: 10px;">Xác nhận
                </button>
            </form>
        </div>

        <div class="chart-container" style="display: none; width: 800px;">
            <canvas id="mycanvas" style="width: 800px"></canvas>
        </div>
        <div class="table-container" style="display: none; padding-top: 20px; margin: 0 auto; width: 500px;">
        </div>
        <div class="detail" style="display:none; margin: 0 auto; width: 100px; padding-top: 10px; text-align: center">
            <button type="button" id="detail" class="btn btn-primary">Chi tiết</button>
        </div>
        <div class="graph" style="display:none; margin: 0 auto; width: 100px; text-align: center">
            <button type="button" id="graph" class="btn btn-success">Đồ thị</button>
        </div>
    </div>
<?php include('inc/footer.php') ?>
</body>
</html>
