<?php
require_once("../../config.php");
require_once($CFG->dirroot . '/lib/moodlelib.php');
global $DB;
require("lib.php");
require('javascriptfunctions.php');
$course = required_param('courseId', PARAM_INT);
$legacy = required_param('legacy', PARAM_INT);
$startdate = optional_param('from', '***', PARAM_TEXT);
global $DB;

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
    echo("Không có sinh viên trong khóa học");
    exit;
}
foreach ($students as $tuple) {
    $arrayofstudents[] = array('userid' => $tuple->id ,
        'nome' => $tuple->firstname.' '.$tuple->lastname,
        'email' => $tuple->email);
}

$resultado = block_grades_chart_get_number_of_days_access_by_week($course, $students, $startdate, $legacy);

//echo "<pre>";
//print_r($resultado);die;

$maxnumberofweeks = 0;
foreach ($resultado as $tuple) {
    $arrayofaccess[] = array('userid' => $tuple->userid ,
        'nome' => $tuple->firstname.' '.$tuple->lastname,
        'email' => $tuple->email);
    if ($tuple->week > $maxnumberofweeks) {
        $maxnumberofweeks = $tuple->week;
    }
}

if ($maxnumberofweeks) {
    $studentswithnoaccess = block_grades_chart_subtract_student_arrays($arrayofstudents, $arrayofaccess);
} else {
    $studentswithnoaccess = $arrayofstudents;
}


$accessresults = block_grades_chart_get_number_of_modules_access_by_week($course, $students, $startdate, $legacy); // B

$maxnumberofresources = 0;
foreach ($accessresults as $tuple) {
    if ( $tuple->number > $maxnumberofresources) {
        $maxnumberofresources = $tuple->number;
    }
}

$groupmembers = block_grades_chart_get_course_group_members($course);
$groupingmembers = block_grades_chart_get_course_grouping_members($course);
$groupmembers = array_merge($groupmembers,$groupingmembers);
$groupmembersjson = json_encode($groupmembers);

$numberofresourcesresult = block_grades_chart_get_number_of_modules_accessed($course, $students, $startdate, $legacy);

$resultado = json_encode($resultado);
$studentswithnoaccess = json_encode($studentswithnoaccess);
$accessresults = json_encode($accessresults);
$numberofresourcesresult = json_encode($numberofresourcesresult);

$event = \block_analytics_graphs\event\block_analytics_graphs_event_view_graph::create(array(
    'objectid' => $course,
    'context' => $context,
    'other' => "hits.php",
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
    <title>Biểu đồ phân phối truy cập</title>
    <link rel="stylesheet" href="externalref/jquery-ui-1.12.1/jquery-ui.css">
    <script src="externalref/jquery-1.12.2.js"></script>
    <script src="externalref/jquery-ui-1.12.1/jquery-ui.js"></script>
    <script src="externalref/highstock.js"></script>
    <script src="externalref/no-data-to-display.js"></script>

    <style>
        div.res_query {
            display:table;
            margin-right:auto;
            margin-left:auto;
        }
        .chart {
            float: left;
            display: block;
            margin: auto;
        }
        .ui-dialog {
            position: fixed;
        }
        #result {
            text-align: right;
            color: gray;
            min-height: 2em;
        }
        #table-sparkline {
            margin: 0 auto;
            border-collapse: collapse;
        }
        div.student_panel{
            font-size: 0.85em;
            min-height: 450px;
            margin-left: auto;
            margin-right: auto;
        }
        a.contentaccess, a.submassign, a.msgs, a.mail, a.quizchart, a.forumchart{
            font-size: 0.85em;
        }
        table.res_query {
            font-size: 0.85em;
        }
        .image-exclamation {
            width: 25px;
            height: 20px;
            vertical-align: middle;
            visibility: hidden;
        }
        .warnings {
            float: right;
            align: right;
            margin-left: 10px;
            display: inline-flex;
            flex-direction: row;
            justify-content: space-around;
            width: 55px;
        }
        .warning1, .warning2 {
            width: 25px;
        }
        .warning1 {
            order: 1;
            margin-right: 5px;
        }
        .warning2 {
            order: 2;
        }
        th {
            font-weight: bold;
            text-align: left;
        }
        td, th {
            padding: 5px;
            border-top: 1px solid silver;
            border-bottom: 1px solid silver;
            border-right: 1px solid silver;
            height: 60px;
        }
        thead th {
            border-top: 2px solid gray;
            border-bottom: 2px solid gray;
        }
        .highcharts-container {
            overflow: visible !important;
        }
        .highcharts-tooltip {
            pointer-events: all !important;
        }
        .highcharts-tooltip>span {
            background: white;
            border: 1px solid silver;
            border-radius: 3px;
            box-shadow: 1px 1px 2px #888;
            padding: 8px;
            max-height: 250px;
            width: auto;
            overflow: auto;
        }
        .scrollableHighchartsTooltipAddition {
            position: relative;
            z-index: 50;
            border: 2px solid rgb(0, 108, 169);
            border-radius: 5px;
            background-color: #ffffff;
            padding: 5px;
            font-size: 9pt;
            overflow: auto;
            height: 200px;
        }
        .totalgraph {
            width: 55%;
            display: block;
            margin-left: auto;
            margin-right: auto;
            margin-top: 50px;
            border-radius: 0px;
            padding: 10px;
            border-top: 1px solid silver;
            border-bottom: 1px solid silver;
            border-right: 1px solid silver;
        }
    </style>
    <script type="text/javascript">
        var courseid = <?php echo json_encode($course); ?>;
        var coursename = <?php echo json_encode($coursename); ?>;
        var geral = <?php echo $resultado; ?>;
        var moduleaccess = <?php echo $accessresults; ?>;
        var numberofresources = <?php echo $numberofresourcesresult; ?>;
        var studentswithnoaccess = <?php echo $studentswithnoaccess; ?>;
        var groups = <?php echo $groupmembersjson; ?>;
        var legacy = <?php echo json_encode($legacy); ?>;
        var weekBeginningOffset = 1; //added to each loop making charts start from WEEK#1 instead of WEEK#0
        var nomes = [];
        var totalResourceAccessData = [];
        var totalWeekDaysAccessData = [];
        $.each(geral, function(ind, val){
            var nome = val.firstname+" "+val.lastname;
            if (nomes.indexOf(nome) === -1)
                nomes.push(nome);

        });

        nomes.sort();
        console.log(geral);
        var students = [];

        $.each(geral, function(ind, val){
            if (students[val.userid]){
                var student = students[val.userid];
                student.semanas[val.week] = Number(val.week);
                student.acessos[val.week] = Number(val.number);
                student.totalofaccesses += Number(val.number);
                student.pageViews += Number(val.numberofpageviews);
                students[val.userid] = student;
            }else{
                var student = {};
                student.userid = Number(val.userid);
                student.nome = val.firstname+" "+val.lastname;
                student.email = val.email;
                student.semanas = [];
                student.semanas[val.week] = Number(val.week);
                student.acessos = [];
                student.acessos[val.week] = Number(val.number);
                student.totalofaccesses = Number(val.number);
                student.pageViews = Number(val.numberofpageviews);
                if (numberofresources[val.userid])
                    student.totalofresources = numberofresources[val.userid].number ;
                else
                    student.totalofresources = 0;
                students[val.userid] = student;
            }
        });

        $.each(moduleaccess, function(index, value){
            if (students[value.userid]){
                var student = students[value.userid];
                if (student.semanasModulos === undefined)
                    student.semanasModulos = [];
                student.semanasModulos[value.week] = Number(value.week);
                if (student.acessosModulos === undefined)
                    student.acessosModulos = [];
                student.acessosModulos[value.week] = (value.number>0 ? Number(value.number) : 0 );
                students[value.userid] = student;
            }
        });

        for (i = 0; i <= <?php echo $maxnumberofweeks; ?>; i++) {
            totalResourceAccessData[i] = 0;
            $.each(students, function(index, item) {
                if (item !== undefined && item.acessosModulos !== undefined && item.acessosModulos[i] != undefined) {
                    totalResourceAccessData[i] += item.acessosModulos[i];
                }
            });
            totalWeekDaysAccessData[i] = 0;
            $.each(students, function(index, item) {
                if (item !== undefined && item.acessos !== undefined && item.acessos[i] != undefined) {
                    totalWeekDaysAccessData[i] += item.acessos[i];
                }
            });
        }
        totalResourceAccessData = pan_array_to_max_number_of_weeks(totalResourceAccessData);
        totalWeekDaysAccessData = pan_array_to_max_number_of_weeks(totalWeekDaysAccessData);

        function trata_array(array){
            var novo = [];
            $.each(array, function(ind, value){
                if (!value)
                    novo[ind] = 0;
                else
                    novo[ind] = value;
            });
            if (novo.length <= <?php echo $maxnumberofweeks; ?>) {
                novo = pan_array_to_max_number_of_weeks(novo);
            }
            return novo;
        }

        function pan_array_to_max_number_of_weeks(array) {
            for (i = array.length; i <= (<?php echo $maxnumberofweeks; ?>); i++ ) {
                if (array[i] === undefined)
                    array[i] = 0;
            }
            return array;
        }

        function gerar_grafico_modulos(student){
            if (student.acessosModulos !== undefined){
                $("#modulos-"+student.userid).highcharts({

                    chart: {
                        borderWidth: 0,
                        type: 'area',
                        margin: [0, 0, 0, 0],
                        spacingBottom: 0,
                        width: 250,
                        height: 60,
                        style: {
                            overflow: 'visible'
                        },
                        skipClone: true,
                    },

                    xAxis: {
                        labels: {
                            enabled: false
                        },
                        title: {
                            text: null
                        },
                        startOnTick: false,
                        endOnTick: false,
                        tickPositions: [],
                        tickInterval: 1,
                        minTickInterval: 24,
                        min: (<?php echo $maxnumberofweeks; ?> + weekBeginningOffset) - 15,
                        max: <?php echo $maxnumberofweeks; ?> + weekBeginningOffset
                    },

                    navigator: {
                        enabled: false,
                        margin: 5
                    },

                    scrollbar: {
                        enabled: true,
                        height: 10
                    },

                    yAxis: {
                        minorTickInterval: 5,
                        endOnTick: false,
                        startOnTick: false,
                        labels: {
                            enabled: false
                        },
                        title: {
                            text: null
                        },
                        tickPositions: [0],
                        max: <?php echo $maxnumberofresources;?>,
                        tickInterval: 5
                    },

                    title: {
                        text: null
                    },

                    credits: {
                        enabled: false
                    },

                    legend: {
                        enabled: false
                    },

                    tooltip: {
                        backgroundColor: null,
                        borderWidth: 0,
                        shadow: false,
                        useHTML: true,
                        hideDelay: 0,
                        shared: true,
                        padding: 0,
                        headerFormat: '',
                        pointFormat: <?php echo "'"."Tuần thứ".": '"; ?> +
                                '{point.x}<br>' +
                            <?php echo "'"."Số lượng tài nguyên truy cập".": '"; ?> +
                                '{point.y}',
                        positioner: function (w, h, point) { return { x: point.plotX - w / 2, y: point.plotY - h};}
                    },
                    plotOptions: {
                        series: {
                            animation:  {
                                duration: 4000
                            },
                            lineWidth: 1,
                            shadow: false,
                            states: {
                                hover: {
                                    lineWidth: 1
                                }
                            },
                            marker: {
                                radius: 2,
                                states: {
                                    hover: {
                                        radius: 4
                                    }
                                }                                        },
                            fillOpacity: 0.25
                        },
                    },
                    series: [{
                        pointStart: weekBeginningOffset,
                        data: trata_array(student.acessosModulos)
                    }],


                    exporting: {
                        enabled: false
                    },

                });
                last_week = <?php echo $maxnumberofweeks; ?>;
                if(!(last_week in student.acessosModulos)){
                    $("#" + student.userid + "-1-img").css("visibility", "visible");
                }
            }else{
                $("#" + student.userid + "-2-img").css("visibility", "visible");
                // $("#modulos-"+student.userid).text("Este usuário não acessou nenhum material ainda.");
                // $("#modulos-"+student.userid).text(":(");
            }
        }

        function gerar_grafico(student){
            $("#acessos-"+student.userid).highcharts({

                chart: {
                    borderWidth: 0,
                    type: 'area',
                    margin: [0, 0, 0, 0],
                    spacingBottom: 0,
                    width: 250,
                    height: 60,
                    style: {
                        overflow: 'visible'
                    },
                    skipClone: true,
                },


                xAxis: {
                    labels: {
                        enabled: false
                    },
                    title: {
                        text: null
                    },
                    startOnTick: false,
                    endOnTick: false,
                    tickPositions: [],
                    tickInterval: 1,
                    minTickInterval: 24,
                    min: (<?php echo $maxnumberofweeks; ?> + weekBeginningOffset) - 15,
                    max: <?php echo $maxnumberofweeks; ?>  + weekBeginningOffset
                },

                navigator: {
                    enabled: false,
                    margin: 5
                },

                scrollbar: {
                    enabled: true,
                    height: 10
                },

                yAxis: {
                    minorTickInterval: 1,
                    endOnTick: false,
                    startOnTick: false,
                    labels: {
                        enabled: false
                    },
                    title: {
                        text: null
                    },
                    tickPositions: [0],
                    max: 7,
                    tickInterval: 1
                },


                title: {
                    text: null
                },


                credits: {
                    enabled: false
                },


                legend: {
                    enabled: false
                },


                tooltip: {
                    backgroundColor: null,
                    borderWidth: 0,
                    shadow: false,
                    useHTML: true,
                    hideDelay: 0,
                    shared: true,
                    padding: 0,
                    headerFormat: '',
                    pointFormat: <?php echo "'"."Tuần thứ".": '"; ?> +
                            '{point.x}<br>' +
                        <?php echo "'"."Số ngày truy cập".": '"; ?> +
                            '{point.y}',
                    positioner: function (w, h, point) { return { x: point.plotX - w / 2, y: point.plotY - h}; }
                },


                plotOptions: {
                    series: {
                        animation: {
                            duration: 2000
                        },
                        lineWidth: 1,
                        shadow: false,
                        states: {
                            hover: {
                                lineWidth: 1
                            }
                        },
                        marker: {
                            radius: 2,
                            states: {
                                hover: {
                                    radius: 4                                                        }
                            }
                        },
                        fillOpacity: 0.25
                    },
                },


                series: [{
                    pointStart: weekBeginningOffset,
                    data: trata_array(student.acessos)
                }],


                exporting: {
                    enabled: false
                },

            });
        }


        function createRow(array, nomes){
            var red_excl = "images/warning-attention-road-sign-exclamation-mark.png";
            var yellow_excl = "images/exclamation_sign.png";
            var red_tooltip = <?php echo json_encode("Tuần trước không truy cập mô-đun"); ?>;
            var yellow_tooltip = <?php echo json_encode("Không truy cập mô-đun đến bây giờ"); ?>;
            $.each(nomes, function(ind,val){
                var nome = val;
                $.each(array, function(index, value){
                    if (value){
                        if (nome === value.nome){
                            var linha = "<tr id='tr-student-"+value.userid+
                                "'><th><span class='nome_student' style='cursor:hand'\
                             id='linha-"+value.userid+"'>"+value.nome+"</span>"+
                                "<div class='warnings'>\
                                    <div class='warning1' id='"+value.userid+"_1'>\
                                                    <img\
                                                        src='" + red_excl + "'\
                                                        title='" + red_tooltip + "'\
                                                        class='image-exclamation'\
                                                        id='" + value.userid + "-1-img'\
                                                    >\
                                                </div>\
                                                <div class='warning2' id='"+value.userid+"_2'>\
                                                    <img\
                                                        src='" + yellow_excl + "'\
                                                        title='" + yellow_tooltip +"'\
                                                        class='image-exclamation'\
                                                        id='" + value.userid + "-2-img'\
                                                    >\
                                                </div>\
                                            </div></th>" +
                                "<td>"+
                                value.pageViews+
                                "</td>"+
                                "<td>"+
                                value.totalofaccesses+
                                "</td>"+
                                "<td width='250' id='acessos-"+value.userid+"'>"+
                                "</td>"+
                                "<td>"+
                                //(value.totalModulos>0? value.totalModulos : 0)+
                                (numberofresources[value.userid]? numberofresources[value.userid].number : 0)+
                                "</td>"+
                                "<td id='modulos-"+value.userid+"'>"+
                                "</td>"+
                                "</tr>";
                            $("table").append(linha);
                            gerar_grafico(value);
                            gerar_grafico_modulos(value);
                        }
                    }
                });
            });
        }

    </script>
</head>
<body>
<?php if (count($groupmembers) > 0) { ?>
    <div style="margin: 20px;">
        <select id="group_select">
            <option value="-"><?php  echo json_encode(get_string('all_groups', 'block_analytics_graphs'));?></option>
            <?php    foreach ($groupmembers as $key => $value) { ?>
                <option value="<?php echo $key; ?>"><?php echo $value["name"]; ?></option>
                <?php
            }
            ?>
        </select>
    </div>
    <?php
}
?>
<center>
    <H2><?php  echo   "Biểu đồ phân phối lượt truy cập của sinh viên";?></H2>
    <H3><?php  echo $coursename;?> </H3>
    <H3><?php  echo   "Bắt đầu từ" . ": "
            . userdate($startdate, get_string('strftimerecentfull'));?> </H3>
</center>
<table id="table-sparkline" >
    <thead>
    <tr>
        <th><?php  echo "Họ tên sinh viên";?></th>
        <th width=50><?php echo "Số lượt truy cập khóa học";?></th>
        <th width=50><?php echo "Số ngày truy cập";?></th>
        <th><center><?php echo "Số ngày truy cập theo tuần";
                echo "<br><i>(". "Số tuần"
                    . ": " . ($maxnumberofweeks + 1).")</i>";?></center></th>
        <th width=50><?php  echo  "Số tài nguyên truy cập";?></th>
        <th><center><?php echo "Số lượng tài nguyên được truy cập theo tuần ";?></center></th>
    </tr>
    </thead>
    <tbody  id='tbody-sparklines'>
    <script type="text/javascript">
        createRow(students, nomes);
    </script>
    </tbody>
</table>

<script type="text/javascript">
    var studentwithaccess = [];
    $.each(students, function(ind, val) {
        var div = "";
        if (val !== undefined){
            var title = coursename +
                "</h3><p style='font-size:small'>" +
                <?php  echo json_encode("Số lượt truy cập khóa học");?> + ": "+
                val.pageViews +
                ", "+ <?php  echo json_encode("Số ngày truy cập");?> + ": "+
                val.totalofaccesses +
                ", "+ <?php  echo json_encode("Số tài nguyên truy cập");?> + ": "+
                val.totalofresources ;
            studentwithaccess[0] = val;
        }
    });
</script>

</body>
</html>
