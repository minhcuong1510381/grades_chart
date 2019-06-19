<?php
require_once("../../config.php");
require_once($CFG->dirroot . '/lib/moodlelib.php');
global $DB;
require("lib.php");
require('javascriptfunctions.php');
$course = htmlspecialchars(required_param('id', PARAM_INT));
$startdate = optional_param('from', '***', PARAM_TEXT);
$hidden = optional_param('hidden', false, PARAM_TEXT);
require_login($course);
$context = context_course::instance($course);
require_capability('block/grades_chart:viewpages', $context);
$courseparams = get_course($course);
if ($startdate === '***') {
    $startdate = $courseparams->startdate;
} else {
    $datetoarray = explode('-', $startdate);
    $starttime = new DateTime("now", core_date::get_server_timezone_object());
    $starttime->setDate((int)$datetoarray[0], (int)$datetoarray[1], (int)$datetoarray[2]);
    $starttime->setTime(0, 0, 0);
    $startdate = $starttime->getTimestamp();
}
$coursename = "Khóa học" . ": " . $courseparams->fullname;
$students = block_grades_chart_get_students($course);
$numberofstudents = count($students);
if ($numberofstudents == 0) {
    echo("Không có học viên");
    exit;
}
foreach ($students as $tuple) {
    $arrayofstudents[] = array('userid' => $tuple->id, 'nome' => $tuple->lastname . ' ' . $tuple->firstname, 'email' => $tuple->email);
}

$requestedtypes = array();
foreach ($_GET as $querystringvariable => $value) {
    if (substr($querystringvariable, 0, strlen("mod")) !== "mod") {
        continue;
    }
    $temp = $value;
    if (!in_array($temp, $requestedtypes)) {
        switch ($temp) { // not very necessary, left for readability and a little security
            case "assign" :
                array_push($requestedtypes, $temp);
                break;
            case "chat" :
                array_push($requestedtypes, $temp);
                break;
            case "choice" :
                array_push($requestedtypes, $temp);
                break;
            case "feedback" :
                array_push($requestedtypes, $temp);
                break;
            case "forum" :
                array_push($requestedtypes, $temp);
                break;
            case "lesson" :
                array_push($requestedtypes, $temp);
                break;
            case "quiz" :
                array_push($requestedtypes, $temp);
                break;
            case "scorm" :
                array_push($requestedtypes, $temp);
                break;
            case "survey" :
                array_push($requestedtypes, $temp);
                break;
            case "wiki" :
                array_push($requestedtypes, $temp);
                break;
            case "workshop" :
                array_push($requestedtypes, $temp);
                break;
            case "book" :
                array_push($requestedtypes, $temp);
                break;
            case "resource" :
                array_push($requestedtypes, $temp);
                break;
            case "folder" :
                array_push($requestedtypes, $temp);
                break;
            case "page" :
                array_push($requestedtypes, $temp);
                break;
            case "url" :
                array_push($requestedtypes, $temp);
                break;
        }
    }
}

if (count($requestedtypes) < 1) {
    echo "<html style=\"background-color: #f4f4f4;\">";
    echo "<div style=\"width: 200px;height: 100px;position:absolute;left:0; right:0;top:0; bottom:0;margin:auto;max-width:100%;max-height:100%;
overflow:auto;background-color: white;border-radius: 25px;padding: 20px;border: 2px solid darkgray;text-align: center;\">";
    echo "<h3>" . "Hãy chọn mục cần tham khảo!" . "</h3>";
    echo "</div>";
    echo "</html>";
    exit;
}

$result = block_grades_chart_get_resource_url_access($course, $students, $requestedtypes, $startdate, $hidden);
$numberofresources = count($result);
if ($numberofresources == 0) {
    echo "<html style=\"background-color: #f4f4f4;\">";
    echo "<div style=\"width: 200px;height: 100px;position:absolute;left:0; right:0;top:0; bottom:0;margin:auto;max-width:100%;max-height:100%;
overflow:auto;background-color: white;border-radius: 25px;padding: 20px;border: 2px solid darkgray;text-align: center;\">";
    echo "<h3>" . "Không có số liệu" . "</h3>";
    echo "</div>";
    echo "</html>";
    exit;
}
$counter = 0;
$numberofaccesses = 0;
$numberofresourcesintopic = 0;
$resourceid = 0;
$numberofresourcesintopic = array();


foreach ($result as $tuple) {
    if ($resourceid == 0) { /* First time in loop -> get topic and content name */
        $numberofresourcesintopic[$tuple->section] = 1;
        $statistics[$counter]['topico'] = $tuple->section;
        $statistics[$counter]['tipo'] = $tuple->tipo;
        if ($tuple->tipo == 'assign') {
            $statistics[$counter]['material'] = $tuple->assign;
        } else if ($tuple->tipo == 'chat') {
            $statistics[$counter]['material'] = $tuple->chat;
        } else if ($tuple->tipo == 'choice') {
            $statistics[$counter]['material'] = $tuple->choice;
        } else if ($tuple->tipo == 'feedback') {
            $statistics[$counter]['material'] = $tuple->feedback;
        } else if ($tuple->tipo == 'forum') {
            $statistics[$counter]['material'] = $tuple->forum;
        } else if ($tuple->tipo == 'lesson') {
            $statistics[$counter]['material'] = $tuple->lesson;
        } else if ($tuple->tipo == 'quiz') {
            $statistics[$counter]['material'] = $tuple->quiz;
        } else if ($tuple->tipo == 'scorm') {
            $statistics[$counter]['material'] = $tuple->scorm;
        } else if ($tuple->tipo == 'survey') {
            $statistics[$counter]['material'] = $tuple->survey;
        } else if ($tuple->tipo == 'wiki') {
            $statistics[$counter]['material'] = $tuple->wiki;
        } else if ($tuple->tipo == 'workshop') {
            $statistics[$counter]['material'] = $tuple->workshop;
        } else if ($tuple->tipo == 'book') {
            $statistics[$counter]['material'] = $tuple->book;
        } else if ($tuple->tipo == 'resource') {
            $statistics[$counter]['material'] = $tuple->resource;
        } else if ($tuple->tipo == 'folder') {
            $statistics[$counter]['material'] = $tuple->folder;
        } else if ($tuple->tipo == 'page') {
            $statistics[$counter]['material'] = $tuple->page;
        } else if ($tuple->tipo == 'url') {
            $statistics[$counter]['material'] = $tuple->url;
        }

        if ($tuple->userid) { /* If a user accessed -> get name */
            $statistics[$counter]['studentswithaccess'][] = array('userid' => $tuple->userid,
                'nome' => $tuple->lastname . " " . $tuple->firstname, 'email' => $tuple->email);
            $numberofaccesses++;
        }
        $resourceid = $tuple->ident;
    } else {
        if ($resourceid == $tuple->ident and $tuple->userid) {
            // If same resource and someone accessed, add student.
            $statistics[$counter]['studentswithaccess'][] = array('userid' => $tuple->userid,
                'nome' => $tuple->lastname . " " . $tuple->firstname, 'email' => $tuple->email);
            $numberofaccesses++;
        }
        if ($resourceid != $tuple->ident) {
            // If new resource, finish previous and create new.
            if ($statistics[$counter]['topico'] == $tuple->section) {
                $numberofresourcesintopic[$tuple->section]++;
            } else {
                $numberofresourcesintopic[$tuple->section] = 1;
            }
            $statistics[$counter]['numberofaccesses'] = $numberofaccesses;
            $statistics[$counter]['numberofnoaccess'] = $numberofstudents - $numberofaccesses;
            if ($numberofaccesses == 0) {
                $statistics[$counter]['studentswithnoaccess'] = $arrayofstudents;
            } else if ($statistics[$counter]['numberofnoaccess'] > 0) {
                $statistics[$counter]['studentswithnoaccess'] = block_grades_chart_subtract_student_arrays($arrayofstudents,
                    $statistics[$counter]['studentswithaccess']);
            }
            $counter++;
            $statistics[$counter]['topico'] = $tuple->section;
            $statistics[$counter]['tipo'] = $tuple->tipo;
            $resourceid = $tuple->ident;

            if ($tuple->tipo == 'assign') {
                $statistics[$counter]['material'] = $tuple->assign;
            } else if ($tuple->tipo == 'chat') {
                $statistics[$counter]['material'] = $tuple->chat;
            } else if ($tuple->tipo == 'choice') {
                $statistics[$counter]['material'] = $tuple->choice;
            } else if ($tuple->tipo == 'feedback') {
                $statistics[$counter]['material'] = $tuple->feedback;
            } else if ($tuple->tipo == 'forum') {
                $statistics[$counter]['material'] = $tuple->forum;
            } else if ($tuple->tipo == 'lesson') {
                $statistics[$counter]['material'] = $tuple->lesson;
            } else if ($tuple->tipo == 'quiz') {
                $statistics[$counter]['material'] = $tuple->quiz;
            } else if ($tuple->tipo == 'scorm') {
                $statistics[$counter]['material'] = $tuple->scorm;
            } else if ($tuple->tipo == 'survey') {
                $statistics[$counter]['material'] = $tuple->survey;
            } else if ($tuple->tipo == 'wiki') {
                $statistics[$counter]['material'] = $tuple->wiki;
            } else if ($tuple->tipo == 'workshop') {
                $statistics[$counter]['material'] = $tuple->workshop;
            } else if ($tuple->tipo == 'book') {
                $statistics[$counter]['material'] = $tuple->book;
            } else if ($tuple->tipo == 'resource') {
                $statistics[$counter]['material'] = $tuple->resource;
            } else if ($tuple->tipo == 'folder') {
                $statistics[$counter]['material'] = $tuple->folder;
            } else if ($tuple->tipo == 'page') {
                $statistics[$counter]['material'] = $tuple->page;
            } else if ($tuple->tipo == 'url') {
                $statistics[$counter]['material'] = $tuple->url;
            }

            if ($tuple->userid) {
                $statistics[$counter]['studentswithaccess'][] = array('userid' => $tuple->userid,
                    'nome' => $tuple->lastname . " " . $tuple->firstname, 'email' => $tuple->email);
                $numberofaccesses = 1;
            } else {
                $numberofaccesses = 0;
            }
        }
    }
}

$statistics[$counter]['numberofaccesses'] = $numberofaccesses;
$statistics[$counter]['numberofnoaccess'] = $numberofstudents - $numberofaccesses;
if ($numberofaccesses == 0) {
    $statistics[$counter]['studentswithnoaccess'] = $arrayofstudents;
} else if ($statistics[$counter]['numberofnoaccess'] > 0) {
    $statistics[$counter]['studentswithnoaccess'] = block_grades_chart_subtract_student_arrays($arrayofstudents,
        $statistics[$counter]['studentswithaccess']);
}

$groupmembers = block_grades_chart_get_course_group_members($course);
$groupingmembers = block_grades_chart_get_course_grouping_members($course);
$groupmembers = array_merge($groupmembers, $groupingmembers);
$groupmembersjson = json_encode($groupmembers);
$statistics = json_encode($statistics);

$event = \block_grades_chart\event\block_analytics_graphs_event_view_graph::create(array(
    'objectid' => $course,
    'context' => $context,
    'other' => "graphresourceurl.php",
));
$event->trigger();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Biểu đồ số lượt truy cập</title>
    <link rel="shortcut icon" href="img/favicon.ico">
    <link rel="stylesheet" href="externalref/jquery-ui-1.12.1/jquery-ui.css">
    <script src="externalref/jquery-1.12.2.js"></script>
    <script src="externalref/jquery-ui-1.12.1/jquery-ui.js"></script>
    <script src="externalref/highcharts.js"></script>
    <script src="externalref/no-data-to-display.js"></script>
    <script src="externalref/exporting.js"></script>

    <style>
        .ui-dialog {
            position: fixed;
        }
    </style>

    <script type="text/javascript">
        var groups = <?php echo $groupmembersjson; ?>;
        var courseid = <?php echo json_encode($course); ?>;
        var coursename = <?php echo json_encode($coursename); ?>;
        var geral = <?php echo $statistics; ?>;
        var geral = parseObjToString(geral);
        var nome = "";
        var arrayofcontents = [];
        var nraccess_vet = [];
        var nrntaccess_vet = [];
        $.each(groups, function (index, group) {
            group.numberofaccesses = [];
            group.numberofnoaccess = [];
            group.studentswithaccess = [];
            group.studentswithnoaccess = [];
            group.material = [];
        });
        $.each(geral, function (index, value) {
            arrayofcontents.push(value.material);
            //default series value
            nraccess_vet.push(value.numberofaccesses);
            nrntaccess_vet.push(value.numberofnoaccess);
            $.each(groups, function (ind, group) {
                if (group.material[index] === undefined)
                    group.material[index] = value.material;
                if (value.numberofaccesses > 0) {
                    $.each(value.studentswithaccess, function (i, student) {
                        if (group.studentswithaccess[index] === undefined)
                            group.studentswithaccess[index] = [];
                        if (group.numberofaccesses[index] === undefined)
                            group.numberofaccesses[index] = 0;
                        if (group.members.indexOf(student.userid) != -1) {
                            group.numberofaccesses[index] += 1;
                            group.studentswithaccess[index].push(value.studentswithaccess[i]);
                        }
                    });

                } else {
                    if (group.studentswithaccess[index] === undefined)
                        group.studentswithaccess[index] = [];
                    if (group.numberofaccesses[index] === undefined)
                        group.numberofaccesses[index] = 0;
                }
                if (value.numberofnoaccess > 0) {
                    $.each(value.studentswithnoaccess, function (j, student) {
                        if (group.studentswithnoaccess[index] === undefined)
                            group.studentswithnoaccess[index] = [];
                        if (group.numberofnoaccess[index] === undefined)
                            group.numberofnoaccess[index] = 0;
                        if (group.members.indexOf(student.userid) != -1) {
                            group.numberofnoaccess[index] += 1;
                            group.studentswithnoaccess[index].push(value.studentswithnoaccess[j]);
                        }
                    });
                } else {
                    if (group.studentswithnoaccess[index] === undefined)
                        group.studentswithnoaccess[index] = [];
                    if (group.numberofnoaccess[index] === undefined)
                        group.numberofnoaccess[index] = 0;
                }
            });
        });

        function parseObjToString(obj) {
            var array = $.map(obj, function (value) {
                return [value];
            });
            return array;
        }

        $(function () {
            $('#container').highcharts({
                chart: {
                    type: 'bar',
                    zoomType: 'x',
                    panning: true,
                    panKey: 'shift'
                },
                title: {
                    text: ' <?php echo "Biểu đồ số lượt truy cập"; ?>'
                },
                subtitle: {
                    text: ' <?php echo "Khóa học" . ": "
                        . $courseparams->fullname . "<br>" .
                        "Bắt đầu từ" . ": "
                        . userdate($startdate); ?>'
                },
                xAxis: {
                    minRange: 1,
                    categories: arrayofcontents,
                    title: {
                        text: '<?php echo "Nội dung"; ?>'
                    },

                    plotBands: [
                            <?php
                            $inicio = -0.5;
                            $par = 2;
                            foreach ($numberofresourcesintopic as $topico => $numberoftopics) {
                            $fim = $inicio + $numberoftopics;
                            ?>{
                            color: ' <?php echo($par % 2 ? 'rgba(0, 0, 0, 0)' : 'rgba(68, 170, 213, 0.1)'); ?>',
                            label: {
                                align: 'right',
                                x: -10,
                                verticalAlign: 'middle',
                                text: '<?php echo "Chủ đề" . " " . $topico; ?>',
                                style: {
                                    fontStyle: 'italic',
                                }
                            },
                            from: '<?php echo $inicio;?>', // Start of the plot band
                            to: '<?php echo $fim;?>', // End of the plot band
                        },
                        <?php
                        $inicio = $fim;
                        $par++;
                        }
                        ?>
                    ]
                }, yAxis: {
                    min: 0,
                    maxPadding: 0.1,
                    minTickInterval: 1,
                    title: {
                        text: '<?php echo "Số lượng học viên"; ?>',
                        align: 'high'
                    },
                    labels: {
                        overflow: 'justify'
                    }
                },

                tooltip: {
                    valueSuffix: ' <?php echo "Học viên"; ?>'
                },

                plotOptions: {
                    series: {
                        cursor: 'pointer',
                        point: {
                            events: {
                                click: function() {
                                    var nome_conteudo = this.x + "-" + this.series.name.charAt(0);
                                    $(".div_nomes").dialog("close");
                                    var group_id = $( "#group_select" ).val();
                                    if(group_id !== undefined && group_id != "-"){//algum grupo foi selecionado
                                        $("#" + nome_conteudo + "-group-"+group_id).dialog("open");
                                        $("#" + nome_conteudo + "-group-"+group_id).dialog("option", "position", {
                                            my:"center top",
                                            at:"center top+" + 10,
                                            of:window
                                        });
                                    }else{
                                        $("#" + nome_conteudo).dialog("open");
                                        $("#" + nome_conteudo).dialog("option", "position", {
                                            my:"center top",
                                            at:"center top+" + 10,
                                            of:window
                                        });
                                        $("#" + nome_conteudo).dialog("open");
                                    }

                                }
                            }
                        }
                    },

                    bar: {
                        dataLabels: {
                            useHTML: this,
                            enabled: true
                        }
                    }
                },
                legend: {
                    layout: 'vertical',
                    align: 'right',
                    verticalAlign: 'top',
                    x: -40,
                    y: 5,
                    floating: true,
                    borderWidth: 1,
                    backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor || '#FFFFFF'),
                    shadow: true
                },
                credits: {
                    enabled: false
                },
                series: [{
                    name: '<?php echo "Truy cập"; ?>',
                    data: nraccess_vet,
                    color: 'rgb(124, 181, 236)'
                }, {
                    name: '<?php echo "Không truy cập"; ?>',
                    data: nrntaccess_vet,
                    color: 'rgb(67, 67, 72)'
                }]
            });
        });
    </script>
</head>
<body>
<?php if (count($groupmembers) > 0) { ?>
    <div style="margin: 20px;">
        <select id="group_select">
            <option value="-"><?php  echo "Hiện tất cả nhóm";?></option>
            <?php
            foreach ($groupmembers as $key => $value) {
                ?>
                <option value="<?php echo $key; ?>"><?php echo $value["name"]; ?></option>
                <?php
            }
            ?>
        </select>
    </div>
    <?php
}
?>
<div id="container" style="min-width: 800px; height:<?php echo ($counter + 1) * 50 + 180;?>; margin: 0 auto"></div>
<script>
    $.each(geral, function(index, value) {
        var nome = value.material;
        div = "";
        if (typeof value.studentswithaccess != 'undefined')
        {
            var titulo = coursename + "</h3>" +
                <?php  echo json_encode("Truy cập"); ?> + " - "+
                nome;
            div += "<div class='div_nomes' id='" + index + "-" +
                "<?php echo substr("Truy cập", 0, 1); ?>" +
                "'>" + createContent(titulo, value.studentswithaccess) + "</div>";
        }
        if (typeof value.studentswithnoaccess != 'undefined')
        {
            var titulo = coursename + "</h3>" +
                <?php  echo json_encode("Không truy cập"); ?> + " - "+
                nome;
            div += "<div class='div_nomes' id='" + index + "-" +
                "<?php echo substr("Không truy cập", 0, 1); ?>" +
                "'>" + createContent(titulo, value.studentswithnoaccess) + "</div>";
        }
        document.write(div);
    });
    $.each(groups, function(index, value) {
        div = "";
        if (typeof value.studentswithaccess != 'undefined')
        {
            $.each(value.studentswithaccess, function(ind, student){
                var titulo = coursename + "</h3>" +
                    <?php  echo json_encode("Truy cập"); ?> + " - "+
                    value.material[ind];

                if(student !== undefined)
                    div += "<div class='div_nomes' id='" + ind + "-" +
                        "<?php echo substr("Truy cập", 0, 1); ?>" +
                        "-group-"+index+"'>" + createContent(titulo, student) + "</div>";
            });
        }
        if (typeof value.studentswithnoaccess != 'undefined')
        {
            $.each(value.studentswithnoaccess, function(ind, student){
                var titulo = coursename + "</h3>" +
                    <?php  echo json_encode("Không truy cập"); ?> + " - "+
                    value.material[ind];

                if(student !== undefined)
                    div += "<div class='div_nomes' id='" + ind + "-" +
                        "<?php echo substr("Không truy cập", 0, 1); ?>" +
                        "-group-"+index+"'>" + createContent(titulo, student) + "</div>";
            });
        }
        document.write(div);
    });
    showContent();
    $( "#group_select" ).change(function() {
        console.log($(this).val());
        convert_series_to_group($(this).val(), groups, geral, '#container');
    });
</script>
</body>
</html>
