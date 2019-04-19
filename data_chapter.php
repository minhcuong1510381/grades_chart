<?php
require_once("../../config.php");
require_once($CFG->dirroot . '/lib/moodlelib.php');
global $DB, $USER, $CFG;
require("lib.php");

$courseId = $_POST['courseId'];
//$yearReview = $_POST['yearChapter'];
//$preYear = $yearReview - 1;

$query = "SELECT u.id, u.firstname, u.lastname
          FROM {user} u
          INNER JOIN {role_assignments} ra ON ra.userid = u.id
          INNER JOIN {context} ct ON ct.id = ra.contextid 
          WHERE u.suspended = 0 AND ct.contextlevel = 50 AND ct.instanceid = $courseId
          ORDER BY u.id ASC";

$res = $DB->get_records_sql($query);

$query1 = "SELECT name, q.id,
 			( SELECT COUNT(*) FROM {quiz_grades} qg WHERE qg.quiz = q.id AND qg.grade >= 5) AS grade_greater_than_5
 			FROM {quiz} q INNER JOIN {quiz_grades} qg ON qg.quiz = q.id  
			WHERE q.course = $courseId
 			GROUP BY q.id ";

$quiz = $DB->get_records_sql($query1);

$aQuiz = block_grades_chart_convert_to_array($quiz);

if ($aQuiz) {
    $data = [];

    foreach ($aQuiz as $key => $value) {
        $data[] = ["name" => $value->{'name'}, "quizId" => $value->{'id'} , "per_student_grade_gt_5" => round(($value->{'grade_greater_than_5'} / count($res)) * 100, 2),"count_student_has_grade_gt_5" => $value->{'grade_greater_than_5'}, "count_student_has_grade_lt_5" => (count($res) - $value->{'grade_greater_than_5'})];
    }

    print json_encode($data);
} else {
    $msg = ["response" => "1", "yearReview" => $yearReview];
    print json_encode($msg);
}

?>