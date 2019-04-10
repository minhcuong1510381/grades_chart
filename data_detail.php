<?php
require_once("../../config.php");
require_once($CFG->dirroot . '/lib/moodlelib.php');
global $DB;
require("lib.php");

$userId = $_GET['userId'];
$courseId = $_GET['courseId'];

$sqlUser = "SELECT firstname, lastname, id
			FROM {user}
			WHERE id = $userId";
$user = $DB->get_record_sql($sqlUser);

$sqlCourseSection = "SELECT name, sequence
						FROM {course_sections}
						WHERE name IS NOT NULL 
						ORDER BY id";

$courseSections = $DB->get_records_sql($sqlCourseSection);

$courseSections = block_grades_chart_convert_to_array($courseSections);

$arrQuiz = [];

foreach ($courseSections as $key => $value) {
    $temp = $courseSections[$key]->{'sequence'};

    $sqlQuiz = "SELECT cm.id, cm.instance, qg.grade
			FROM {course_modules} cm
			LEFT JOIN {quiz_grades} qg ON qg.quiz = cm.instance
			WHERE cm.id IN ($temp) AND cm.module = 16 AND cm.course = $courseId AND qg.userid = $userId";

    $a = $DB->get_records_sql($sqlQuiz);

    if ($a) {
        $arrQuiz[] = block_grades_chart_convert_to_array($a) + ["name" => $value->{'name'}];
    }
}

if ($arrQuiz) {
    $arrRes = [];

    foreach ($arrQuiz as $row) {
        $sum = 0;
        $ave = 0;
        for ($i = 0; $i < count($row) - 1; $i++) {
            $sum += $row[$i]->{'grade'};
        }
        $ave = round($sum / (count($row) - 1), 1);

        $arrRes[] = ["average" => $ave, "name" => $row['name'], "user" => $user->{'firstname'} . " " . $user->{'lastname'}, "userId" => $user->{'id'}];
    }

    print json_encode($arrRes);
}
else{
    $msg = ["user" => $user->{'firstname'} . " " . $user->{'lastname'}, "response" => 1];
    print json_encode($msg);
}

//echo "<pre>";
//print_r($arrQuiz);
//die;