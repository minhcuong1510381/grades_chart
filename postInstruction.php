<?php
require_once("../../config.php");
require_once($CFG->dirroot . '/lib/moodlelib.php');
global $DB, $USER, $CFG;
require("lib.php");

/*echo "<pre>";
print_r($a);
die;*/

 function checkId($questionid) {
 	global $DB;
 	$query = "SELECT bgc.questionid, bgc.instruction
        FROM {block_grades_chart} bgc";
	$aInstruction = block_grades_chart_convert_to_array($DB->get_records_sql($query));

    foreach ($aInstruction as $key => $value) {
        if ($aInstruction[$key]->{'questionid'} == $questionid) {
         	return 0;
        }
    }
    return 1;
}

?>
<?php
	if(isset($_POST['btn-submit'])) {

		$questionId = $_POST['questionId'];

		global $DB;

		foreach ($questionId as $key => $value) {
			if (checkId($key) == 1) {
				if ($questionId[$key] != '') {
					$record = new stdClass();
					$record->questionid = $key;
					$record->instruction = $value;
					$DB->insert_record('block_grades_chart', $record);
				}
			}
			else {
				$record = $DB->get_record('block_grades_chart', array('questionid' => $key));
				$record->instruction = $value;
				$DB->update_record('block_grades_chart', $record);
			}
		}
	}
 ?>