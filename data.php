<?php
require_once("../../config.php");
require_once($CFG->dirroot . '/lib/moodlelib.php');
global $DB;
require("lib.php");

$courseId = $_POST['courseId'];
$studentId = $_POST['studentId'];
$studentIdCompare = $_POST['compareStudentId'];

$sqlUser = "SELECT firstname, lastname, id
			FROM {user}
			WHERE id = $studentId";
$user = $DB->get_record_sql($sqlUser);

if ($studentIdCompare) {
    $sqlUserCp = "SELECT firstname, lastname, id
			FROM {user}
			WHERE id = $studentIdCompare";
    $userCp = $DB->get_record_sql($sqlUserCp);

    $arrQuizCp = [];
}

$sqlCourseSection = "SELECT name, sequence
						FROM {course_sections}
						WHERE name IS NOT NULL  AND course = $courseId
						ORDER BY id";

$courseSections = $DB->get_records_sql($sqlCourseSection);

$courseSections = block_grades_chart_convert_to_array($courseSections);

$arrQuiz = [];
$count = 0;
$countCp = 0;
foreach ($courseSections as $key => $value) {

    $temp = $courseSections[$key]->{'sequence'};

    $sqlQuiz = "SELECT cm.id, cm.instance, IFNULL(qg.grade, 0) AS grade
			FROM {course_modules} cm
			LEFT JOIN {quiz_grades} qg ON qg.quiz = cm.instance
			WHERE cm.id IN ($temp) AND cm.module = 16 AND cm.course = $courseId AND qg.userid = $studentId";

    $sqlQuizt = "SELECT cm.id, cm.instance, 0 AS grade
            FROM {course_modules} cm
            WHERE cm.id IN ($temp) AND cm.module = 16 AND cm.course = $courseId ";

    $a = $DB->get_records_sql($sqlQuizt);

    $b1 = $DB->get_records_sql($sqlQuiz);

    if ($a) {
        $arrQuiz[] = block_grades_chart_convert_to_array($b1) + ["name" => $value->{'name'}];

        foreach ($arrQuiz as $key => $value) {
            if (!array_key_exists("grade", $value[0])) {
                array_push($arrQuiz[$key], ["grade" => 0]);
                $count += 1;
            }
        }

        if ($studentIdCompare) {
            $sqlQuizCp = "SELECT cm.id, cm.instance, qg.grade
				FROM {course_modules} cm
				LEFT JOIN {quiz_grades} qg ON qg.quiz = cm.instance
				WHERE cm.id IN ($temp) AND cm.module = 16 AND cm.course = $courseId AND qg.userid = $studentIdCompare";

            $sqlQuizCpt = "SELECT cm.id, cm.instance, 0 AS grade
            FROM {course_modules} cm
            WHERE cm.id IN ($temp) AND cm.module = 16 AND cm.course = $courseId ";

            $b = $DB->get_records_sql($sqlQuizCpt);

            $a1 = $DB->get_records_sql($sqlQuizCp);

            if ($b) {
                $arrQuizCp[] = block_grades_chart_convert_to_array($a1) + ["name" => $value->{'name'}];

                foreach ($arrQuizCp as $key => $value) {
                    if (!array_key_exists("grade", $value[0])) {
                        array_push($arrQuizCp[$key], ["grade" => 0]);
                        $countCp += 1;
                    }
                }
            }
        }
    }
}

if ($count < count($arrQuiz)) {
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

    if ($studentIdCompare) {
        if ($countCp < count($arrQuizCp)) {
            $arrResCp = [];

            foreach ($arrQuizCp as $row) {
                $sum = 0;
                $ave = 0;
                for ($i = 0; $i < count($row) - 1; $i++) {
                    $sum += $row[$i]->{'grade'};
                }
                $ave = round($sum / (count($row) - 1), 1);

                $arrResCp[] = ["average" => $ave, "name" => $row['name'], "user" => $userCp->{'firstname'} . " " . $userCp->{'lastname'}, "userId" => $userCp->{'id'}];
            }
            print json_encode(array($arrRes, $arrResCp));
        } else {
            $msg = ["user" => $userCp->{'firstname'} . " " . $userCp->{'lastname'}, "response" => 1];
            print json_encode($msg);
        }
    } else {
        print json_encode($arrRes);
    }
} else {
    $msg = ["user" => $user->{'firstname'} . " " . $user->{'lastname'}, "response" => 1];
    print json_encode($msg);
}
?>