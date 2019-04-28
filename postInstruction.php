<?php
require_once("../../config.php");
require_once($CFG->dirroot . '/lib/moodlelib.php');
global $DB, $USER, $CFG;
require("lib.php");

if (isset($_POST['btn-submit'])) {

    $questionId = $_POST['questionId'];
    $courseId = $_POST['courseId'];

    global $DB;

    foreach ($questionId as $key => $value) {
        if (block_grades_chart_get_check_id($key) == 1) {
            if ($questionId[$key] != '') {
                $record = new stdClass();
                $record->questionid = $key;
                $record->instruction = $value;
                $DB->insert_record('block_grades_chart', $record);
                header("Location: addinstruction.php?courseId=".$courseId);
            }
        } else {
            $record = $DB->get_record('block_grades_chart', array('questionid' => $key));
            $record->instruction = $value;
            $DB->update_record('block_grades_chart', $record);
            header("Location: addinstruction.php?courseId=".$courseId);
        }
    }
}
?>