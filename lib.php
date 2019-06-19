<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 3/11/2019
 * Time: 10:34 AM
 */
defined('MOODLE_INTERNAL') || die();

function block_grades_chart_convert_to_array($user){
    $result = array();
    if($user){
        foreach ($user as $key => $value){
            $result[] = $value;
        }
    }

    return $result;
}

function groupArray($arr, $group, $preserveGroupKey = false, $preserveSubArrays = false)
{
    $temp = array();
    foreach ($arr as $key => $value) {
        $groupValue = $value[$group];
        if (!$preserveGroupKey) {
            unset($arr[$key][$group]);
        }
        if (!array_key_exists($groupValue, $temp)) {
            $temp[$groupValue] = array();
        }

        if (!$preserveSubArrays) {
            $data = count($arr[$key]) == 1 ? array_pop($arr[$key]) : $arr[$key];
        } else {
            $data = $arr[$key];
        }
        $temp[$groupValue][] = $data;
    }
    return $temp;
}

function countMaxArray($arr){
	$max = 0;
	foreach ($arr as $key => $value) {
	    if($max == 0){
	        $max = count($value);
        }
	    else {
            if (count($value) > $max) {
                $max = count($value);
            }
        }
	}
	return $max;
}
function block_grades_chart_get_course_used_modules($courseid){
    global $DB;

    $sql = "SELECT cm.module, md.name
            FROM {course_modules} cm
            LEFT JOIN {modules} md ON cm.module = md.id
            WHERE cm.course = ?
            GROUP BY cm.module, md.name";
    $params = array($courseid);
    $result = $DB->get_records_sql($sql, $params);

    return $result;
}
function block_grades_chart_generate_graph_startup_module_entry ($iconhtml, $name, $value, $title) {

    return      "<div style='height: 24px;line-height: 24px;text-align: left;border: 1px solid lightgrey;" .
        "margin-bottom: 2px; margin-top: 8px'>" .
        "<div style='display: table;'>" .
        $iconhtml .
        "<div style='display: table-cell; vertical-align: middle;'>" .
        "<input type='checkbox' id='selectable' name='" . $name . "' value='" . $value . "'>" . $title . "</div>" .
        "</div></div>";
}
function block_grades_chart_get_students($course) {
    global $DB;
    $students = array();
    $context = context_course::instance($course);
    $allstudents = get_enrolled_users($context, 'block/grades_chart:bemonitored', 0,
        'u.id, u.firstname, u.lastname, u.email, u.suspended', 'firstname, lastname');
    foreach ($allstudents as $student) {
        if ($student->suspended == 0) {
            if (groups_user_groups_visible($DB->get_record('course', array('id' =>  $course), '*', MUST_EXIST), $student->id)) {
                $students[] = $student;
            }
        }
    }
    return($students);
}

function block_grades_chart_get_resource_url_access($course, $estudantes, $requestedtypes, $startdate, $hidden) {
    global $COURSE;
    global $DB;
    foreach ($estudantes as $tupla) {
        $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);

    $requestedmodules = array($course); // first parameter is courseid, later are modulesids to display

    foreach ($requestedtypes as $module) { // making params for the table
        $temp = $resource = $DB->get_record('modules', array('name' => $module), 'id');
        array_push($requestedmodules, $temp->id);
    }

    // $startdate = $COURSE->startdate;

    /* Temp table to order */
    $params = array($course);
    $sql = "SELECT id, section, sequence
            FROM {course_sections}
            WHERE course  = ? AND sequence <> ''
            ORDER BY section";
    $result = $DB->get_records_sql($sql, $params);

    $dbman = $DB->get_manager();
    $table = new xmldb_table('tmp_analytics_graphs');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('section', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_field('module', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_field('sequence', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $dbman->create_temp_table($table);
    $sequence = 0;
    foreach ($result as $tuple) {
        $modules = explode(',', $tuple->sequence);
        foreach ($modules as $module) {
            $record = new stdClass();
            $record->section = $tuple->section;
            $record->module = $module;
            $record->sequence = $sequence++;
            $DB->insert_record('tmp_analytics_graphs', $record, false);
        }
    }

    $params = array_merge(array($startdate), $inparams, $requestedmodules);

    $sqla = "SELECT temp.id+(COALESCE(temp.userid,1)*1000000)as id, temp.id as ident, tag.section, m.name as tipo, ";
    $sqlb = "temp.userid, usr.firstname, usr.lastname, usr.email, temp.acessos, tag.sequence
                    FROM (
                        SELECT cm.id, log.userid, count(*) as acessos
                        FROM {course_modules} cm
                        LEFT JOIN {logstore_standard_log} log ON log.timecreated >= ?
                            AND log.userid $insql AND (action = 'viewed' OR action = 'submission') AND cm.id=log.contextinstanceid
                        WHERE cm.course = ?";
    if ($hidden) {
        $sqlb .= " AND (";
    } else {
        $sqlb .= " AND cm.visible=1 AND (";
    }

    $sqlc = "cm.module=?";

    if (count($requestedmodules) >= 2) {
        for ($i = 2; $i < count($requestedmodules); $i++) {
            $sqlc .= " OR cm.module=?";
        }
    }

    $sqld = ")
                        GROUP BY cm.id, log.userid
                        ) as temp
                    LEFT JOIN {course_modules} cm ON temp.id = cm.id
                    LEFT JOIN {modules} m ON cm.module = m.id
                    ";
    $sqle = "   LEFT JOIN {user} usr ON usr.id = temp.userid
                    LEFT JOIN {tmp_analytics_graphs} tag ON tag.module = cm.id
                    ORDER BY tag.sequence";

    foreach ($requestedtypes as $type) {
        switch ($type) { // probably unnecessary, but here it is fine I think, at least for readability
            case "assign" :
                $sqla .= "asn.name as assign, ";
                $sqld .= "LEFT JOIN {assign} asn ON cm.instance = asn.id
        ";
                break;
            case "chat" :
                $sqla .= "cht.name as chat, ";
                $sqld .= "LEFT JOIN {chat} cht ON cm.instance = cht.id
        ";
                break;
            case "choice" :
                $sqla .= "chc.name as choice, ";
                $sqld .= "LEFT JOIN {choice} chc ON cm.instance = chc.id
        ";
                break;
            case "feedback" :
                $sqla .= "fdb.name as feedback, ";
                $sqld .= "LEFT JOIN {feedback} fdb ON cm.instance = fdb.id
        ";
                break;
            case "forum" :
                $sqla .= "frm.name as forum, ";
                $sqld .= "LEFT JOIN {forum} frm ON cm.instance = frm.id
        ";
                break;
            case "lesson" :
                $sqla .= "lss.name as lesson, ";
                $sqld .= "LEFT JOIN {lesson} lss ON cm.instance = lss.id
        ";
                break;
            case "quiz" :
                $sqla .= "qz.name as quiz, ";
                $sqld .= "LEFT JOIN {quiz} qz ON cm.instance = qz.id
        ";
                break;
            case "scorm" :
                $sqla .= "scr.name as scorm, ";
                $sqld .= "LEFT JOIN {scorm} scr ON cm.instance = scr.id
        ";
                break;
            case "survey" :
                $sqla .= "srv.name as survey, ";
                $sqld .= "LEFT JOIN {survey} srv ON cm.instance = srv.id
        ";
                break;
            case "wiki" :
                $sqla .= "wk.name as wiki, ";
                $sqld .= "LEFT JOIN {wiki} wk ON cm.instance = wk.id
        ";
                break;
            case "workshop" :
                $sqla .= "wrk.name as workshop, ";
                $sqld .= "LEFT JOIN {workshop} wrk ON cm.instance = wrk.id
        ";
                break;
            case "book" :
                $sqla .= "bk.name as book, ";
                $sqld .= "LEFT JOIN {book} bk ON cm.instance = bk.id
        ";
                break;
            case "resource" :
                $sqla .= "rsr.name as resource, ";
                $sqld .= "LEFT JOIN {resource} rsr ON cm.instance = rsr.id
        ";
                break;
            case "folder" :
                $sqla .= "fld.name as folder, ";
                $sqld .= "LEFT JOIN {folder} fld ON cm.instance = fld.id
        ";
                break;
            case "page" :
                $sqla .= "pg.name as page, ";
                $sqld .= "LEFT JOIN {page} pg ON cm.instance = pg.id
        ";
                break;
            case "url" :
                $sqla .= "rl.name as url, ";
                $sqld .= "LEFT JOIN {url} rl ON cm.instance = rl.id
        ";
                break;
        }
    }

    $sql = $sqla . $sqlb . $sqlc . $sqld . $sqle;

    $resultado = $DB->get_records_sql($sql, $params);
    $dbman->drop_table($table);
    return($resultado);
}

function block_grades_chart_subtract_student_arrays($estudantes, $acessaram) {
    $resultado = array();
    foreach ($estudantes as $estudante) {
        $encontrou = false;
        foreach ($acessaram as $acessou) {
            if ($estudante['userid'] == $acessou ['userid']) {
                $encontrou = true;
                break;
            }
        }
        if (!$encontrou) {
            $resultado[] = $estudante;
        }
    }
    return $resultado;
}
function block_grades_chart_get_course_group_members($course) {
    global $DB;
    $groupmembers = array();
    $groups = groups_get_all_groups($course);
    foreach ($groups as $group) {
        if (groups_group_visible($group->id, $DB->get_record('course', array('id' =>  $course), '*', MUST_EXIST))) {
            $members = groups_get_members($group->id);
            if (!empty($members)) {
                $groupmembers[$group->id]['name'] = $group->name;
                $numberofmembers = 0;
                foreach ($members as $member) {
                    $groupmembers[$group->id]['members'][] = $member->id;
                    $numberofmembers++;
                }
                $groupmembers[$group->id]['numberofmembers']  = $numberofmembers;
            }
        }
    }
    return($groupmembers);
}
function block_grades_chart_get_course_grouping_members($course) {
    global $DB;
    $groupingmembers = array();
    $groupings = groups_get_all_groupings($course);
    foreach ($groupings as $grouping) {
        if (groups_group_visible($group->id, $DB->get_record('course', array('id' =>  $course), '*', MUST_EXIST))) {
            $members = groups_get_grouping_members($grouping->id);
            if (!empty($members)) {
                $groupingmembers[$grouping->id]['name'] = $grouping->name;
                $numberofmembers = 0;
                foreach ($members as $member) {
                    $groupingmembers[$grouping->id]['members'][] = $member->id;
                    $numberofmembers++;
                }
                $groupingmembers[$grouping->id]['numberofmembers']  = $numberofmembers;
            }
        }
    }
    return($groupingmembers);
}
function block_grades_chart_get_course_name($course) {
    global $DB;
    $sql = "SELECT
              a.fullname
            FROM
              {course} a
            WHERE
              a.id = " . $course;
    $result = $DB->get_records_sql($sql);

    $resultname = "";

    foreach ($result as $item) {
        if (!empty($item)) {
            $resultname = $item->fullname;
        }
    }

    return $resultname;
}
function block_grades_chart_get_number_of_days_access_by_week($course, $estudantes, $startdate, $legacy=0) {
    global $DB;
    $timezone = new DateTimeZone(core_date::get_server_timezone());
    $timezoneadjust   = $timezone->getOffset(new DateTime);
    foreach ($estudantes as $tupla) {
        $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($timezoneadjust, $timezoneadjust, $startdate, $course, $startdate), $inparams);
    if (!$legacy) {
        $sql = "SELECT temp2.userid+(week*1000000) as id, temp2.userid, firstname, lastname, email, week,
                number, numberofpageviews
                FROM (
                    SELECT temp.userid, week, COUNT(*) as number, SUM(numberofpageviews) as numberofpageviews
                    FROM (
                        SELECT MIN(log.id) as id, log.userid,
                            FLOOR((log.timecreated + ?)/ 86400)   as day,
                            FLOOR( (((log.timecreated  + ?) / 86400) - (?/86400))/7) as week,
                            COUNT(*) as numberofpageviews
                        FROM {logstore_standard_log} log
                        WHERE courseid = ? AND action = 'viewed' AND target = 'course'
                            AND log.timecreated >= ? AND log.userid $insql
                        GROUP BY userid, day, week
                    ) as temp
                    GROUP BY week, temp.userid
                ) as temp2
                LEFT JOIN {user} usr ON usr.id = temp2.userid
                ORDER BY LOWER(firstname), LOWER(lastname),userid, week";
    } else {
        $sql = "SELECT temp2.userid+(week*1000000) as id, temp2.userid, firstname, lastname, email, week,
                number, numberofpageviews
                FROM (
                    SELECT temp.userid, week, COUNT(*) as number, SUM(numberofpageviews) as numberofpageviews
                    FROM (
                        SELECT MIN(log.id) as id, log.userid,
                            FLOOR((log.time + ?)/ 86400)   as day,
                            FLOOR( (((log.time  + ?) / 86400) - (?/86400))/7) as week,
                            COUNT(*) as numberofpageviews
                        FROM {log} log
                        WHERE course = ? AND action = 'view' AND module = 'course'
                            AND log.time >= ? AND log.userid $insql
                        GROUP BY userid, day, week
                    ) as temp
                    GROUP BY week, temp.userid
                ) as temp2
                LEFT JOIN {user} usr ON usr.id = temp2.userid
                ORDER BY LOWER(firstname), LOWER(lastname),userid, week";
    }
    $resultado = $DB->get_records_sql($sql, $params);
    return($resultado);
}
function block_grades_chart_get_number_of_modules_access_by_week($course, $estudantes, $startdate, $legacy=0) {
    global $DB;
    $timezone = new DateTimeZone(core_date::get_server_timezone());
    $timezoneadjust   = $timezone->getOffset(new DateTime);
    foreach ($estudantes as $tupla) {
        $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($timezoneadjust, $startdate, $course, $startdate), $inparams);
    if (!$legacy) {
        $sql = "SELECT userid+(week*1000000), userid, firstname, lastname, email, week, number
                FROM (
                    SELECT  userid, week, COUNT(*) as number
                    FROM (
                        SELECT log.userid, objecttable, objectid,
                        FLOOR((((log.timecreated + ?) / 86400) - (?/86400))/7) as week
                        FROM {logstore_standard_log} log
                        WHERE courseid = ? AND action = 'viewed' AND target = 'course_module'
                        AND log.timecreated >= ? AND log.userid $insql
                        GROUP BY userid, week, objecttable, objectid
                    ) as temp
                    GROUP BY userid, week
                ) as temp2
                LEFT JOIN {user} usr ON usr.id = temp2.userid
                ORDER by LOWER(firstname), LOWER(lastname), userid, week";
    } else {
        $sql = "SELECT userid+(week*1000000), userid, firstname, lastname, email, week, number
                FROM (
                    SELECT  userid, week, COUNT(*) as number
                    FROM (
                        SELECT log.userid, module, cmid,
                        FLOOR((((log.time + ?) / 86400) - (?/86400))/7) as week
                        FROM {log} log
                        WHERE course = ? AND (action = 'view' OR action = action = 'view forum')
                            AND module <> 'assign' AND cmid <> 0 AND time >= ? AND log.userid $insql
                        GROUP BY userid, week, module, cmid
                    ) as temp
                    GROUP BY userid, week
                ) as temp2
                LEFT JOIN {user} usr ON usr.id = temp2.userid
                ORDER by LOWER(firstname), LOWER(lastname), userid, week";
    }
    $resultado = $DB->get_records_sql($sql, $params);
    return($resultado);
}
function block_grades_chart_get_number_of_modules_accessed($course, $estudantes, $startdate, $legacy=0) {
    global $DB;
    foreach ($estudantes as $tupla) {
        $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($course, $startdate), $inparams);
    if (!$legacy) {
        $sql = "SELECT userid, COUNT(*) as number
            FROM (
                SELECT log.userid, objecttable, objectid
                FROM {logstore_standard_log} log
                LEFT JOIN {user} usr ON usr.id = log.userid
                WHERE courseid = ? AND action = 'viewed' AND target = 'course_module'
                    AND log.timecreated >= ? AND log.userid $insql
                GROUP BY log.userid, objecttable, objectid
            ) as temp
            GROUP BY userid
            ORDER by userid";
    } else {
        $sql = "SELECT userid, COUNT(*) as number
            FROM (
                SELECT log.userid, module, cmid
                FROM {log} log
                LEFT JOIN {user} usr ON usr.id = log.userid
                WHERE course = ? AND (action = 'view' OR action = 'view forum')
                    AND module <> 'assign' AND cmid <> 0  AND log.time >= ? AND log.userid $insql
                GROUP BY log.userid, module, cmid
            ) as temp
            GROUP BY userid
            ORDER by userid";
    }
    $resultado = $DB->get_records_sql($sql, $params);
    return($resultado);
}
function block_grades_chart_check_student_has_grades($studentId){
    global $DB;
    $sql = "SELECT SUM(grade) as grades
            FROM {quiz_grades}
            WHERE userid = $studentId";

    $res = $DB->get_record_sql($sql);

    if($res->{'grades'} != null){
        return true;
    }
    else{
        return false;
    }
}

function block_grades_chart_get_instruction($questionid)
{
    global $DB;
    $query = "SELECT bgc.questionid, bgc.instruction
        FROM {block_grades_chart} bgc";
    $aInstruction = block_grades_chart_convert_to_array($DB->get_records_sql($query));

    foreach ($aInstruction as $key => $value) {
        if ($aInstruction[$key]->{'questionid'} == $questionid) {
            return $aInstruction[$key]->{'instruction'};
        }
    }
    return null;
}

function block_grades_chart_get_check_id($questionid) {
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
function block_grades_chart_get_user_resource_url_page_access($course, $student, $legacy=0) {
    global $COURSE;
    global $DB;

    $requestedmodules = block_grades_chart_get_course_used_modules($course);

    // $resource = $DB->get_record('modules', array('name' => 'resource'), 'id');
    // $url = $DB->get_record('modules', array('name' => 'url'), 'id');
    // $page = $DB->get_record('modules', array('name' => 'page'), 'id');
    // $wiki = $DB->get_record('modules', array('name' => 'wiki'), 'id');
    $startdate = $COURSE->startdate;

    $paramsdefault = array($startdate, $student, $course);
    // array($startdate, $student, $course, $resource->id, $url->id, $page->id, $wiki->id);
    $paramsids = array();
    $sqla = "SELECT temp.id, m.name as tipo, ";
    $sqlb = "COALESCE(temp.userid,0) as userid,  temp.acessos
                    FROM (
                        SELECT cm.id, log.userid, count(*) as acessos
                        FROM {course_modules} cm
                        LEFT JOIN {logstore_standard_log} log ON log.timecreated >= ?
                            AND log.userid = ? AND action = 'viewed' AND cm.id=log.contextinstanceid
                        WHERE cm.course = ? AND (";

    $sqlc = "cm.module=?";

    if (count ($requestedmodules) >= 2) {
        for ($i = 2; $i <= count($requestedmodules); $i++) {
            $sqlc .= " OR cm.module=?";
        }
    }

    $sqld = ") AND cm.visible = 1
                        GROUP BY cm.id, log.userid
                        ) as temp
                    LEFT JOIN {course_modules} cm ON temp.id = cm.id
                    LEFT JOIN {modules} m ON cm.module = m.id
                    ";
    $sqle = "ORDER BY m.name";

    foreach ($requestedmodules as $module) {
        $temp = $DB->get_record('modules', array('name' => $module->name), 'id');
        array_push($paramsdefault, $temp->id);
        switch ($module->name) {
            case "activequiz" :
                $sqla .= "avq.name as activequiz, ";
                $sqld .= "LEFT JOIN {activequiz} avq ON cm.instance = avq.id
            ";
                break;
            case "assign" :
                $sqla .= "asn.name as assign, ";
                $sqld .= "LEFT JOIN {assign} asn ON cm.instance = asn.id
            ";
                break;
            case "attendance" :
                $sqla .= "att.name as attendance, ";
                $sqld .= "LEFT JOIN {attendance} att ON cm.instance = att.id
            ";
                break;
            case "bigbluebuttonbn" :
                $sqla .= "bbn.name as bigbluebuttonbn, ";
                $sqld .= "LEFT JOIN {bigbluebuttonbn} bbn ON cm.instance = bbn.id
            ";
                break;
            case "booking" :
                $sqla .= "bkn.name as booking, ";
                $sqld .= "LEFT JOIN {booking} bkn ON cm.instance = bkn.id
            ";
                break;
            case "certificate" :
                $sqla .= "cft.name as certificate, ";
                $sqld .= "LEFT JOIN {certificate} cft ON cm.instance = cft.id
            ";
                break;
            case "chat" :
                $sqla .= "cht.name as chat, ";
                $sqld .= "LEFT JOIN {chat} cht ON cm.instance = cht.id
            ";
                break;
            case "checklist" :
                $sqla .= "ckl.name as checklist, ";
                $sqld .= "LEFT JOIN {checklist} ckl ON cm.instance = ckl.id
            ";
                break;
            case "choice" :
                $sqla .= "chc.name as choice, ";
                $sqld .= "LEFT JOIN {choice} chc ON cm.instance = chc.id
            ";
                break;
            case "icontent" :
                $sqla .= "ict.name as icontent, ";
                $sqld .= "LEFT JOIN {icontent} ict ON cm.instance = ict.id
            ";
                break;
            case "customcert" :
                $sqla .= "ctc.name as customcert, ";
                $sqld .= "LEFT JOIN {customcert} ctc ON cm.instance = ctc.id
            ";
                break;
            case "data" :
                $sqla .= "dt.name as data, ";
                $sqld .= "LEFT JOIN {data} dt ON cm.instance = dt.id
            ";
                break;
            case "dataform" :
                $sqla .= "dfm.name as dataform, ";
                $sqld .= "LEFT JOIN {dataform} dfm ON cm.instance = dfm.id
            ";
                break;
            case "lti" :
                $sqla .= "lt.name as lti, ";
                $sqld .= "LEFT JOIN {lti} lt ON cm.instance = lt.id
            ";
                break;
            case "feedback" :
                $sqla .= "fdb.name as feedback, ";
                $sqld .= "LEFT JOIN {feedback} fdb ON cm.instance = fdb.id
            ";
                break;
            case "forum" :
                $sqla .= "frm.name as forum, ";
                $sqld .= "LEFT JOIN {forum} frm ON cm.instance = frm.id
            ";
                break;
            case "game" :
                $sqla .= "gme.name as game, ";
                $sqld .= "LEFT JOIN {game} gme ON cm.instance = gme.id
            ";
                break;
            case "glossary" :
                $sqla .= "gls.name as glossary, ";
                $sqld .= "LEFT JOIN {glossary} gls ON cm.instance = gls.id
            ";
                break;
            case "choicegroup" :
                $sqla .= "cgr.name as choicegroup, ";
                $sqld .= "LEFT JOIN {choicegroup} cgr ON cm.instance = cgr.id
            ";
                break;
            case "groupselect" :
                $sqla .= "grs.name as groupselect, ";
                $sqld .= "LEFT JOIN {groupselect} grs ON cm.instance = grs.id
            ";
                break;
            case "hotpot" :
                $sqla .= "htp.name as hotpot, ";
                $sqld .= "LEFT JOIN {hotpot} htp ON cm.instance = htp.id
            ";
                break;
            case "hvp" :
                $sqla .= "hvp.name as hvp, ";
                $sqld .= "LEFT JOIN {hvp} hvp ON cm.instance = hvp.id
            ";
                break;
            case "lesson" :
                $sqla .= "lss.name as lesson, ";
                $sqld .= "LEFT JOIN {lesson} lss ON cm.instance = lss.id
            ";
                break;
            case "openmeetings" :
                $sqla .= "opm.name as openmeetings, ";
                $sqld .= "LEFT JOIN {openmeetings} opm ON cm.instance = opm.id
            ";
                break;
            case "questionnaire" :
                $sqla .= "qst.name as questionnaire, ";
                $sqld .= "LEFT JOIN {questionnaire} qst ON cm.instance = qst.id
            ";
                break;
            case "turnitintooltwo" :
                $sqla.= "tii.name as turnitintooltwo, ";
                $sqld.= "LEFT JOIN {turnitintooltwo} tii ON cm.instance = tii.id
            ";
                break;
            case "quiz" :
                $sqla .= "qz.name as quiz, ";
                $sqld .= "LEFT JOIN {quiz} qz ON cm.instance = qz.id
            ";
                break;
            case "quizgame" :
                $sqla .= "qzg.name as quizgame, ";
                $sqld .= "LEFT JOIN {quizgame} qzg ON cm.instance = qzg.id
            ";
                break;
            case "scheduler" :
                $sqla .= "sdr.name as scheduler, ";
                $sqld .= "LEFT JOIN {scheduler} sdr ON cm.instance = sdr.id
            ";
                break;
            case "scorm" :
                $sqla .= "scr.name as scorm, ";
                $sqld .= "LEFT JOIN {scorm} scr ON cm.instance = scr.id
            ";
                break;
            case "subcourse" :
                $sqla .= "sbc.name as subcourse, ";
                $sqld .= "LEFT JOIN {subcourse} sbc ON cm.instance = sbc.id
            ";
                break;
            case "survey" :
                $sqla .= "srv.name as survey, ";
                $sqld .= "LEFT JOIN {survey} srv ON cm.instance = srv.id
            ";
                break;
            case "vpl" :
                $sqla .= "vpl.name as vpl, ";
                $sqld .= "LEFT JOIN {vpl} vpl ON cm.instance = vpl.id
            ";
                break;
            case "wiki" :
                $sqla .= "wk.name as wiki, ";
                $sqld .= "LEFT JOIN {wiki} wk ON cm.instance = wk.id
            ";
                break;
            case "workshop" :
                $sqla .= "wrk.name as workshop, ";
                $sqld .= "LEFT JOIN {workshop} wrk ON cm.instance = wrk.id
            ";
                break;
            case "book" :
                $sqla .= "bk.name as book, ";
                $sqld .= "LEFT JOIN {book} bk ON cm.instance = bk.id
            ";
                break;
            case "resource" :
                $sqla .= "rsr.name as resource, ";
                $sqld .= "LEFT JOIN {resource} rsr ON cm.instance = rsr.id
            ";
                break;
            case "folder" :
                $sqla .= "fld.name as folder, ";
                $sqld .= "LEFT JOIN {folder} fld ON cm.instance = fld.id
            ";
                break;
            case "imscp" :
                $sqla .= "msc.name as imscp, ";
                $sqld .= "LEFT JOIN {imscp} msc ON cm.instance = msc.id
            ";
                break;
            case "label" :
                $sqla .= "lbl.name as label, ";
                $sqld .= "LEFT JOIN {label} lbl ON cm.instance = lbl.id
            ";
                break;
            case "lightboxgallery" :
                $sqla .= "lbg.name as lightboxgallery, ";
                $sqld .= "LEFT JOIN {lightboxgallery} lbg ON cm.instance = lbg.id
            ";
                break;
            case "page" :
                $sqla .= "pg.name as page, ";
                $sqld .= "LEFT JOIN {page} pg ON cm.instance = pg.id
            ";
                break;
            case "poster" :
                $sqla .= "pst.name as poster, ";
                $sqld .= "LEFT JOIN {poster} pst ON cm.instance = pst.id
            ";
                break;
            case "recordingsbn" :
                $sqla .= "rbn.name as recordingsbn, ";
                $sqld .= "LEFT JOIN {recordingsbn} rbn ON cm.instance = rbn.id
            ";
                break;
            case "url" :
                $sqla .= "rl.name as url, ";
                $sqld .= "LEFT JOIN {url} rl ON cm.instance = rl.id
            ";
                break;
        }
    }

    // $params = "SETHERE";
    $sql = $sqla . $sqlb . $sqlc . $sqld . $sqle;
    $params = array_merge($paramsdefault, $paramsids);
    // return $paramsdefault;
    $result = $DB->get_records_sql($sql, $paramsdefault);

//    echo "<pre>";
//    print_r($result);die;

    return($result);

}

function block_grades_chart_get_logstore_loglife() {
    global $DB;
    $sql = "SELECT  a.id, a.plugin, a.name, a.value
                FROM {config_plugins} a
                WHERE a.name = 'loglifetime' AND a.plugin = 'logstore_standard'
                ORDER BY name";
    $result = $DB->get_records_sql($sql);
    return reset($result)->value;
}

function block_grades_chart_get_course_days_since_startdate($course) {
    global $DB;
    $sql = "SELECT  a.id, a.startdate
                FROM {course} a
                WHERE a.id = " . $course;
    $result = $DB->get_records_sql($sql);
    $startdate = reset($result)->startdate;
    $currentdate = time();
    return floor(($currentdate - $startdate) / (60 * 60 * 24));
}

function block_grades_chart_get_accesses_last_days($course, $estudantes, $daystoget) {
    global $DB;
    $date = strtotime(date('Y-m-d', strtotime('-'. $daystoget .' days')));
    $sql = "SELECT s.id, s.action, s.target, s.userid, s.courseid, s.timecreated, usr.firstname, usr.lastname
            FROM {logstore_standard_log} s
            LEFT JOIN {user} usr ON s.userid = usr.id
            WHERE s.courseid = " . $course . " AND s.timecreated >= " . $date . "
            AND (";
    $iterator = 0;
    foreach ($estudantes as $item) {
        if ($iterator == 0) {
            $sql .= " s.userid = " . $item->id;
        } else {
            $sql .= " OR s.userid = " . $item->id;
        }
        $iterator++;
    }
    $sql .= " )
             ORDER BY s.timecreated";
    $resultado = $DB->get_records_sql($sql);

    foreach ($resultado as $item) {
        $item->timecreated = date("His", $item->timecreated);
    }

    return($resultado);
}