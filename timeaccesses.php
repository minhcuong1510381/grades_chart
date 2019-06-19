<?php
require('../../config.php');
require('lib.php');
require('javascriptfunctions.php');
$course = required_param('courseId', PARAM_INT);
$days = required_param('days', PARAM_INT);
global $DB;
global $CFG;

$students = block_grades_chart_get_students($course);
$numberofstudents = count($students);
if ($numberofstudents == 0) {
    echo("Không có học viên.");
    exit;
}

$logstorelife = block_grades_chart_get_logstore_loglife();
$coursedayssincestart = block_grades_chart_get_course_days_since_startdate($course);
if ($logstorelife === null || $logstorelife == 0) {
    // 0, false and NULL are threated as null in case logstore setting not found and 0 is "no removal" logs.
    $maximumdays = $coursedayssincestart; // the chart should not break with value more than available
} else if ($logstorelife >= $coursedayssincestart) {
    $maximumdays = $coursedayssincestart;
} else {
    $maximumdays = $logstorelife;
}

if ($days > $maximumdays) { // sanitycheck
    $days = $maximumdays;
} else if ($days < 1) {
    $days = 1;
}

$daysaccess = block_grades_chart_get_accesses_last_days($course, $students, $days);
$daysaccess = json_encode($daysaccess);

?>

<html>
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Biểu đồ mô tả hoạt động của học viên</title>
	<link rel="shortcut icon" href="img/favicon.ico">
    <link rel="stylesheet" href="externalref/jquery-ui-1.12.1/jquery-ui.css">
    <script src="externalref/jquery-1.12.2.js"></script>
    <script src="externalref/jquery-ui-1.12.1/jquery-ui.js"></script>
    <script src="externalref/highstock.js"></script>
    <script src="externalref/no-data-to-display.js"></script>
    <script src="externalref/exporting.js"></script>

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
    </style>

</head>

<div style="width: 300px; min-width: 325px; height: 65px;left:10px; top:5px; border-radius: 0px;padding: 5px;border: 2px solid silver;text-align: center;">
    Số ngày cần tham khảo
    <input style="width: 50px;" id = "days" type="number" name="days" min="1" max="<?php echo $maximumdays; ?>" value="<?php echo $days ?>">
    <br>
    <button style="width: 225px;" id="apply">Xác nhận</button>
    <br>
    <?php echo "Số ngày tối đa: " . "<b>" . $maximumdays . "</b>"; ?>
</div>

<div id="containerA" style="min-width: 300px; height: 400px; margin: 0 auto"></div>
<br>
<hr/>
<br>
<div id="containerB" style="min-width: 300px; height: 400px; margin: 0 auto"></div>

<script>
    var data = <?php echo $daysaccess; ?>;
    var houraccesses = [];
    var houractivities = [];

    for (var i = 0; i < 24; i++)
    {
        var hourbegin = i * 10000;
        var hourend = i * 10000 + 9999;
        var countedIds = [];
        var numActiveStudents = 0;
        var numActivitiesHour = 0;
        var maximumDays = <?php echo $maximumdays; ?>;

        for(var j in data)
        {
            if (data[j].timecreated >= hourbegin && data[j].timecreated <= hourend) {
                if (jQuery.inArray(data[j].userid, countedIds) == -1) {
                    countedIds.push(data[j].userid);
                    numActiveStudents++;
                }
                numActivitiesHour++;
            }
        }

        houraccesses[i] = numActiveStudents;
        houractivities[i] = numActivitiesHour;
    }

    $('#apply').click(function() {
        if (maximumDays < $('#days').val()) {
            window.location.href = '<?php echo $CFG->wwwroot . "/blocks/grades_chart/timeaccesses.php?courseId=" . $course . "&days="; ?>' + maximumDays;
        } else {
            window.location.href = '<?php echo $CFG->wwwroot . "/blocks/grades_chart/timeaccesses.php?courseId=" . $course . "&days="; ?>' + $('#days').val();
        }
        return false;
    });

    var colors = ['#3B97B2', '#67BC42', '#FF56DE', '#E6D605', '#BC36FE', '#000'];

    Highcharts.chart('containerA', {
        chart: {
            type: 'column',
            events: {
                load: function(){
                    this.mytooltip = new Highcharts.Tooltip(this, this.options.tooltip);
                }
            }
        },
        title: {
            text: 'Số lượng học viên hoạt động'
        },
        xAxis: {
            type: 'category',
            labels: {
                rotation: -45,
                style: {
                    fontSize: '13px',
                    fontFamily: 'Verdana, sans-serif'
                }
            }
        },
        yAxis: {
            min: 0,
            title: {
                text: ''
            }
        },
        legend: {
            enabled: false
        },
        tooltip: {
            enabled: false,
            useHTML: true,
            backgroundColor: "rgba(255, 255, 255, 1.0)",
            formatter: function(){
                var hour = this.point.name.replace(":00", "");
                var hourbegin = hour * 10000;
                var hourend = hour * 10000 + 9999;
                var countedIds = [];

                var tooltipStr = "<span style='font-size: 13px'><b>" +
                    this.point.name +
                    "</b></span>:<br>";

                for(var j in data)
                {
                    if (data[j].timecreated >= hourbegin && data[j].timecreated <= hourend) {
                        if (jQuery.inArray(data[j].userid, countedIds) == -1) {
                            countedIds.push(data[j].userid);
                            tooltipStr += data[j].lastname + " " + data[j].firstname + "<br>";
                        }
                    }
                }

                return "<div class='scrollableHighchartsTooltipAddition'>" + tooltipStr + "</div>";
            }
        },
        credits: {
            enabled: false
        },
        plotOptions: {
            series : {
                stickyTracking: false,
                events: {
                    click : function(evt){
                        this.chart.mytooltip.refresh(evt.point, evt);
                    },
                    mouseOut : function(){
                        this.chart.mytooltip.hide();
                    }
                }
            }
        },
        series: [{
            name: 'Time',
            data: [
                {
                    name: '00:00',
                    y: houraccesses[0],
                    color: colors[2]

                },
                {
                    name: '01:00',
                    y: houraccesses[1],
                    color: colors[2]

                },
                {
                    name: '02:00',
                    y: houraccesses[2],
                    color: colors[2]

                },
                {
                    name: '03:00',
                    y: houraccesses[3],
                    color: colors[2]

                },
                {
                    name: '04:00',
                    y: houraccesses[4],
                    color: colors[2]

                },
                {
                    name: '05:00',
                    y: houraccesses[5],
                    color: colors[2]

                },
                {
                    name: '06:00',
                    y: houraccesses[6],
                    color: colors[0]

                },
                {
                    name: '07:00',
                    y: houraccesses[7],
                    color: colors[0]

                },
                {
                    name: '08:00',
                    y: houraccesses[8],
                    color: colors[0]

                },
                {
                    name: '09:00',
                    y: houraccesses[9],
                    color: colors[0]

                },
                {
                    name: '10:00',
                    y: houraccesses[10],
                    color: colors[0]

                },
                {
                    name: '11:00',
                    y: houraccesses[11],
                    color: colors[0]

                },
                {
                    name: '12:00',
                    y: houraccesses[12],
                    color: colors[0]

                },
                {
                    name: '13:00',
                    y: houractivities[13],
                    color: colors[0]

                },
                {
                    name: '14:00',
                    y: houraccesses[14],
                    color: colors[0]

                },
                {
                    name: '15:00',
                    y: houraccesses[15],
                    color: colors[0]

                },
                {
                    name: '16:00',
                    y: houraccesses[16],
                    color: colors[0]

                },
                {
                    name: '17:00',
                    y: houraccesses[17],
                    color: colors[0]

                },
                {
                    name: '18:00',
                    y: houraccesses[18],
                    color: colors[0]

                },
                {
                    name: '19:00',
                    y: houraccesses[19],
                    color: colors[2]

                },
                {
                    name: '20:00',
                    y: houraccesses[20],
                    color: colors[2]

                },
                {
                    name: '21:00',
                    y: houraccesses[21],
                    color: colors[2]

                },
                {
                    name: '22:00',
                    y: houraccesses[22],
                    color: colors[0]

                },
                {
                    name: '23:00',
                    y: houraccesses[23],
                    color: colors[2]

                }
            ],
            dataLabels: {
                enabled: true,
                rotation: -90,
                color: '#FFFFFF',
                align: 'right',
                format: '{point.y:.1f}', // one decimal
                y: 10, // 10 pixels down from the top
                style: {
                    fontSize: '13px',
                    fontFamily: 'Verdana, sans-serif'
                }
            }
        }]
    });

    Highcharts.chart('containerB', {
        chart: {
            type: 'column',
            events: {
                load: function(){
                    this.mytooltip = new Highcharts.Tooltip(this, this.options.tooltip);
                }
            }
        },
        title: {
            text: 'Chi tiết hoạt động của học viên'
        },
        xAxis: {
            type: 'category',
            labels: {
                rotation: -45,
                style: {
                    fontSize: '13px',
                    fontFamily: 'Verdana, sans-serif'
                }
            }
        },
        yAxis: {
            min: 0,
            title: {
                text: ''
            }
        },
        legend: {
            enabled: false
        },
        tooltip: {
            enabled: false,
            useHTML: true,
            backgroundColor: "rgba(255, 255, 255, 1.0)",
            formatter: function(){
                var hour = this.point.name.replace(":00", "");
                var hourbegin = hour * 10000;
                var hourend = hour * 10000 + 9999;

                var tooltipStr = "<span style='font-size: 13px'><b>" +
                    this.point.name +
                    "</b></span>:<br>";

                var previousStr = "";
                var sameStrCount = 0;
                for(var j in data)
                {
                    if (data[j].timecreated >= hourbegin && data[j].timecreated <= hourend) {
                        var tempstr = data[j].lastname + " " + data[j].firstname
                            + "->" + data[j].action + ":" + data[j].target;

                        if (tempstr != previousStr && sameStrCount == 0) {
                            if (previousStr != "") {
                                tooltipStr += previousStr + "<br>";
                            }
                            previousStr = tempstr;
                        } else if (tempstr == previousStr) {
                            sameStrCount++;
                        } else if (tempstr != previousStr && sameStrCount > 0) {
                            tooltipStr += previousStr + ":" + (sameStrCount+1) +"<br>";
                            sameStrCount = 0;
                            previousStr = tempstr;
                        }
                    }
                }

                if (sameStrCount > 0) {
                    tooltipStr += previousStr + ":" + (sameStrCount+1) +"<br>";
                } else {
                    tooltipStr += previousStr + "<br>";
                }

                return "<div class='scrollableHighchartsTooltipAddition'>" + tooltipStr + "</div>";
            }
        },
        credits: {
            enabled: false
        },
        plotOptions: {
            series : {
                stickyTracking: false,
                events: {
                    click : function(evt){
                        this.chart.mytooltip.refresh(evt.point, evt);
                    },
                    mouseOut : function(){
                        this.chart.mytooltip.hide();
                    }
                }
            }
        },
        series: [{
            name: 'Time',
            data: [
                {
                    name: '00:00',
                    y: houractivities[0],
                    color: colors[2]

                },
                {
                    name: '01:00',
                    y: houractivities[1],
                    color: colors[2]

                },
                {
                    name: '02:00',
                    y: houractivities[2],
                    color: colors[2]

                },
                {
                    name: '03:00',
                    y: houractivities[3],
                    color: colors[2]

                },
                {
                    name: '04:00',
                    y: houractivities[4],
                    color: colors[2]

                },
                {
                    name: '05:00',
                    y: houractivities[5],
                    color: colors[2]

                },
                {
                    name: '06:00',
                    y: houractivities[6],
                    color: colors[0]

                },
                {
                    name: '07:00',
                    y: houractivities[7],
                    color: colors[0]

                },
                {
                    name: '08:00',
                    y: houractivities[8],
                    color: colors[0]

                },
                {
                    name: '09:00',
                    y: houractivities[9],
                    color: colors[0]

                },
                {
                    name: '10:00',
                    y: houractivities[10],
                    color: colors[0]

                },
                {
                    name: '11:00',
                    y: houractivities[11],
                    color: colors[0]

                },
                {
                    name: '12:00',
                    y: houractivities[12],
                    color: colors[0]

                },
                {
                    name: '13:00',
                    y: houractivities[13],
                    color: colors[0]

                },
                {
                    name: '14:00',
                    y: houractivities[14],
                    color: colors[0]

                },
                {
                    name: '15:00',
                    y: houractivities[15],
                    color: colors[0]

                },
                {
                    name: '16:00',
                    y: houractivities[16],
                    color: colors[0]

                },
                {
                    name: '17:00',
                    y: houractivities[17],
                    color: colors[0]

                },
                {
                    name: '18:00',
                    y: houractivities[18],
                    color: colors[0]

                },
                {
                    name: '19:00',
                    y: houractivities[19],
                    color: colors[2]

                },
                {
                    name: '20:00',
                    y: houractivities[20],
                    color: colors[2]

                },
                {
                    name: '21:00',
                    y: houractivities[21],
                    color: colors[2]

                },
                {
                    name: '22:00',
                    y: houractivities[22],
                    color: colors[0]

                },
                {
                    name: '23:00',
                    y: houractivities[23],
                    color: colors[2]

                }
            ],
            dataLabels: {
                enabled: true,
                rotation: -90,
                color: '#FFFFFF',
                align: 'right',
                format: '{point.y:.1f}', // one decimal
                y: 10, // 10 pixels down from the top
                style: {
                    fontSize: '13px',
                    fontFamily: 'Verdana, sans-serif'
                }
            }
        }]
    });

</script>

</html>

