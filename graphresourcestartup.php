<?php
require_once("../../config.php");
require_once($CFG->dirroot . '/lib/moodlelib.php');
global $DB;
require("lib.php");

$course = required_param('courseId', PARAM_INT);

require_login($course);
$context = context_course::instance($course);
require_capability('block/grades_chart:viewpages', $context);
$courseparams = get_course($course);
$startdate = date("Y-m-d", $courseparams->startdate);

$availablemodules = array();
foreach (block_grades_chart_get_course_used_modules($course) as $result) {
    array_push($availablemodules, $result->name);
}
//echo "<pre>";
//print_r($availablemodules);die;

$legacypixurlbefore = "<img style='display: table-cell; vertical-align: middle;' src='";
$legacypixurlafter = "'width='24' height='24'>";
?>
<script>
    function checkUncheck(setTo) {
        var c = document.getElementsByTagName('input');
        for (var i = 0; i < c.length; i++) {
            if (c[i].type == 'checkbox') {
                c[i].checked = setTo;
            }
        }
    }
</script>

<html style="background-color: #f4f4f4;">
<div style="width: 250px;height: 80%;position:absolute;left:0; right:0;top:0; bottom:0;margin:auto;max-width:100%;max-height:100%;
overflow:auto;background-color: white;border-radius: 0px;padding: 20px;border: 2px solid darkgray;text-align: center;">
    <?php
    echo "<input type=\"hidden\" name=\"id\" value=\"$course\">";

    echo "<h1>" . "Đồ thị số lượt truy cập" . "</h1>";
    echo "<h3>" . "Chọn mục cần tham khảo" . ":</h3>";
    ?>
    <div style="text-align: left">
        <form action="graphresourceurl.php" method="get">
            <?php
            $num = 1;
            echo "<h4 style='margin-bottom: 3px'>" . "Hoạt động:" . "</h4>";
            if (in_array("assign", $availablemodules)) {
                // from here used to check if specific module is available, otherwise it is not displayed
                echo block_grades_chart_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_assign", "mod_assign", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "assign", "Bài tập lớn");
                $num++;
            }
            if (in_array("chat", $availablemodules)) {
                echo block_grades_chart_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_chat", "mod_chat", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "chat", "Trò chuyện");
                $num++;
            }
            if (in_array("choice", $availablemodules)) {
                echo block_grades_chart_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_choice", "mod_choice", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "choice", "Lựa chọn");
                $num++;
            }
            if (in_array("feedback", $availablemodules)) {
                echo block_grades_chart_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_feedback", "mod_feedback", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "feedback", "Phản hồi");
                $num++;
            }
            if (in_array("forum", $availablemodules)) {
                echo block_grades_chart_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_forum", "mod_forum", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "forum", "Diễn đàn");
                $num++;
            }
            if (in_array("lesson", $availablemodules)) {
                echo block_grades_chart_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_lesson", "mod_lesson", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "lesson", "Bài giảng");
                $num++;
            }
            if (in_array("quiz", $availablemodules)) {
                echo block_grades_chart_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_quiz", "mod_quiz", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "quiz", "Câu hỏi");
                $num++;
            }
            if (in_array("scorm", $availablemodules)) {
                echo block_grades_chart_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_scorm", "mod_scorm", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "scorm", "SCORM");
                $num++;
            }
            if (in_array("survey", $availablemodules)) {
                echo block_grades_chart_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_survey", "mod_survey", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "survey", "Khảo sát");
                $num++;
            }
            if (in_array("wiki", $availablemodules)) {
                echo block_grades_chart_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_wiki", "mod_wiki", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "wiki", "Wiki");
                $num++;
            }
            if (in_array("workshop", $availablemodules)) {
                echo block_grades_chart_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_workshop", "mod_workshop", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "workshop", "Hội thảo");
                $num++;
            }

            echo "<h4 style='margin-bottom: 3px'>" . "Tài nguyên:" . "</h4>";

            if (in_array("book", $availablemodules)) {
                echo block_grades_chart_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_book", "mod_book", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "book", "Sách");
                $num++;
            }
            if (in_array("resource", $availablemodules)) {
                echo block_grades_chart_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_resource", "mod_resource", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "resource", "Tài nguyên");
                $num++;
            }
            if (in_array("folder", $availablemodules)) {
                echo block_grades_chart_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_folder", "mod_folder", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "folder", "Tập tin");
                $num++;
            }
            if (in_array("page", $availablemodules)) {
                echo block_grades_chart_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_page", "mod_page", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "page", "Page");
                $num++;
            }
            if (in_array("url", $availablemodules)) {
                echo block_grades_chart_generate_graph_startup_module_entry($OUTPUT->pix_icon("icon", "mod_url", "mod_url", array(
                    'width' => 24,
                    'height' => 24,
                    'title' => ''
                )), "mod" . $num, "url", "URL");
                $num++;
            }

            echo "<input type=\"hidden\" name=\"id\" value=\"$course\">";

            echo "<h4 style='margin-bottom: 3px'>" . "Thiết lập:" . "</h4>";

            echo "Bắt đầu từ:" . ": <input type=\"date\" name=\"from\" value=\"$startdate\"><br>";

            echo "<input type=\"checkbox\" name=\"hidden\" value=\"true\">" . "Hiển thị các mục ẩn";

            ?>
    </div>
    <?php
    echo "<input type='button' value='" . "Chọn tất cả" . "' onclick='checkUncheck(true);'>";
    echo "<input type='button' value='" . "Xóa tất cả" . "' onclick='checkUncheck(false);'>";
    echo "<input type='submit' value='" . "Xem biểu đồ" . "''>";
    ?>
    </form>
</div>
