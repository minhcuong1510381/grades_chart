<?php
require_once("../../config.php");
require_once($CFG->dirroot . '/lib/moodlelib.php');
global $DB, $USER;
require("lib.php");
// include('data.php');

$courseId = $_GET['courseId'];

require_login($courseId);

$context = context_course::instance($courseId);

$roles = get_user_roles($context, $USER->id);

$isStudent = current(get_user_roles($context, $USER->id))->shortname == 'student' ? 1 : 2;

if ($isStudent == 1) {
    return false;
}

$students = block_grades_chart_get_students($courseId);
$numberofstudents = count($students);
if ($numberofstudents == 0) {
    echo("Không có sinh viên trong khóa học");
    exit;
}

$query = "SELECT name, id
        FROM {quiz}
        WHERE course = $courseId
        ORDER BY id";

$aQuiz = block_grades_chart_convert_to_array($DB->get_records_sql($query));

//echo "<pre>";
//print_r($aQuiz);die;

?>
<?php include('inc/header.php') ?>
<div class="container" style="height: 700px;">
    <div class="header">
        <div class="title-gradeschart" style="margin: 0 auto; width: 500px; text-align: center">
            <h3>Biểu đồ đánh giá chi tiết năng lực của sinh viên</h3>
        </div>
        <?php if ($_GET["countTopic"]) { ?>
            <form id="myform" style="margin-top: 20px;">
                <input type="hidden" id="courseId" name="courseId" value="<?php echo $courseId; ?>">
                <div class="alert alert-danger" id="alert" role="alert"
                     style="margin: 0 auto; width: 500px; display: none;">
                </div>
                <h5 style="margin: 0 auto; width: 500px;">Chọn sinh viên:</h5>
                <div class="input-group choose-student" style="margin: 0 auto; width: 500px;">
                    <select class="form-control selectpicker" id="student" name="studentId" data-live-search="true">
                        <?php foreach ($students as $key => $value) { ?>
                            <option value="<?php echo $value->{'id'}; ?>">
                                <?php echo $value->{'lastname'} . " " . $value->{'firstname'}; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="compare">So sánh</button>
                    </div>
                </div>
                <div class="form-group choose-student-compare" style="margin: 0 auto; width: 500px; display: none;"
                     id="compare-student">
                    <label>Chọn sinh viên để so sánh:</label>
                    <select class="form-control selectpicker" id="choose-compare-student" data-live-search="true">
                        <?php foreach ($students as $key => $value) { ?>
                            <option value="<?php echo $value->{'id'}; ?>">
                                <?php echo $value->{'lastname'} . " " . $value->{'firstname'}; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="choose-topic">
                    <h5 style="margin: 0 auto; width: 500px;">Chọn tiêu chí:</h5>
                    <?php for ($i = 1; $i <= $_GET["countTopic"]; $i++) { ?>
                        <p style="margin: 0 auto; width: 500px;">Tiêu chí <?php echo $i; ?>:</p>
                        <div class="input-group" style="margin: 0 auto; width: 500px;">
                            <input type="text" class="form-control" name="topic[<?php echo $i; ?>]"
                                   placeholder="Tên tiêu chí <?php echo $i; ?>...">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" data-toggle="modal"
                                        data-target="#myModal[<?php echo $i; ?>]" id="choice-data">Chọn dữ liệu
                                </button>
                            </div>
                        </div>

                        <!-- The Modal -->
                        <div class="modal" id="myModal[<?php echo $i; ?>]">
                            <div class="modal-dialog">
                                <div class="modal-content" style="width: 600px;">

                                    <!-- Modal Header -->
                                    <div class="modal-header">
                                        <h4 class="modal-title">Chọn dữ liệu cho tiêu chí <?php echo $i; ?></h4>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>

                                    <!-- Modal body -->
                                    <div class="modal-body data-box">
                                        <ul class="ks-cboxtags">
                                            <?php foreach ($aQuiz as $k => $v) { ?>
                                                <li><input type="checkbox"
                                                           id="choiceData[<?php echo $i; ?>][<?php echo $v->{'id'}; ?>]"
                                                           name="idQuiz[<?php echo $i; ?>][<?php echo $v->{'id'}; ?>]"
                                                           value="<?php echo $v->{'id'}; ?>"><label
                                                            for="choiceData[<?php echo $i; ?>][<?php echo $v->{'id'}; ?>]"><?php echo $v->{'name'}; ?></label>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </div>

                                    <!-- Modal footer -->
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger" data-dismiss="modal">Xác nhận
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="button-group" style="margin-left: 40%; width: 500px; margin-top: 10px; ">
                        <button id="back" type="button" class="btn btn-primary">Quay về
                        </button>
                        <button id="submit" type="submit" class="btn btn-danger">Xác nhận
                        </button>
                    </div>

                </div>

            </form>
            <br>
        <?php } ?>
        <?php if (!$_GET["countTopic"]) { ?>
            <div class="alert alert-danger" id="alert" role="alert"
                 style="margin: 0 auto; width: 500px; display: none;">
            </div>
            <div class="form-inputTopic" style="margin: 0 auto; width: 500px;">
                <input type="hidden" id="courseId" name="courseId" value="<?php echo $courseId; ?>">
                <h5>Điền vào số tiêu chí cần thiết lập:</h5>
                <div class="input-group group-setting">
                    <input type="number" class="form-control" id="numberTopic" name="numberTopic"
                           placeholder="Nhập số tiêu chí cần thiết lập...(Ít nhất 3 tiêu chí)">
                    <div class="input-group-append">
                        <button id="setting" type="button" class="btn btn-default">Thiết lập
                        </button>
                    </div>
                </div>
            </div>
        <?php } ?>

    </div>

    <div id="chart1" style="min-width: 360px; max-width: 600px; height: 400px; margin: 0 auto;"></div>
</div>

<?php include('inc/footer.php') ?>
<script src="externalref/highcharts.js"></script>
<script src="externalref/highcharts-more.js"></script>
<script>
    $(document).ready(function () {
        $('#setting').on('click', function () {
            // $('.choose-topic').css('display',"block");
            // $('.group-setting').css('display',"none");
            var numberTopic = $('#numberTopic').val();
            if (numberTopic < 3) {
                $('#alert').html("Cần thiết lập ít nhất 3 tiêu chí");

                $('#alert').css("display", "block");

                $('#alert').delay(3000).fadeOut("slow");
                return false;
            }
            var courseId = $('#courseId').val();
            window.location.href = "gradeschart.php?courseId=" + courseId + "&countTopic=" + numberTopic;
        });
        $('#back').on('click', function () {
            var courseId = $('#courseId').val();
            window.location.href = "gradeschart.php?courseId=" + courseId;
        });
        $('#myform').on('submit', function (e) {
            e.preventDefault();
            var frm = $('#myform');
            $.ajaxSetup({
                cache: false
            });
            if ($('#compare-student').css("display") == "none") {
                // alert("abc");return false;
                $.ajax({
                    type: "POST",
                    url: 'data.php',
                    data: frm.serializeArray(),
                    success: function (data) {
                        var obj = JSON.parse(data);
                        if (obj.response == 1) {
                            // alert(obj.msg);
                            $('#alert').html(obj.msg);

                            $('#alert').css("display", "block");

                            $('#alert').delay(3000).fadeOut("slow");
                            return false;
                        }

                        var vertex = [];
                        var score = [];
                        var ave = [];

                        for (var i in obj[0]) {
                            vertex.push(obj[0][i].name);
                            score.push(parseFloat(obj[0][i][0].average));
                            ave.push(parseFloat(obj[1][i][0].aver));
                        }
                        var chartype = {
                            polar: true,
                            type: 'line'
                        }
                        var chartitle = {
                            text: 'Biểu đồ năng lực sinh viên',
                            x: -80
                        }
                        var chartpane = {
                            size: '80%'
                        }
                        var chartxaxis = {
                            categories: vertex,
                            tickmarkPlacement: 'on',
                            lineWidth: 0
                        }
                        var chartyaxis = {
                            gridLineInterpolation: 'polygon',
                            lineWidth: 0,
                            min: 0,
                            max: 10
                        }
                        var chartooltip = {
                            shared: true,
                            valueDecimals: 1,
                            valueSuffix: ' điểm'
                        }
                        var chartlegend = {
                            align: 'right',
                            verticalAlign: 'top',
                            y: 70,
                            layout: 'vertical'
                        }
                        var chartseries = [{
                            name: obj[0][0].user,
                            data: score,
                            color: '#7cb5ec',
                            pointPlacement: 'on'
                        }, {
                            name: 'Trung bình',
                            data: ave,
                            color: '#FF0000',
                            lineWidth: 1,
                            pointPlacement: 'on'
                        }]
                        $('#chart1').highcharts({
                            chart: chartype,
                            title: chartitle,
                            pane: chartpane,
                            xAxis: chartxaxis,
                            yAxis: chartyaxis,
                            tooltip: chartooltip,
                            legend: chartlegend,
                            series: chartseries
                        });
                    }
                });
            } else {
                var studentIdCheck = $('#student').val();
                var studentIdCpCheck = $('#choose-compare-student').val();

                if (studentIdCheck == studentIdCpCheck) {
                    $('#alert').html("Chọn sinh viên so sánh không phù hợp!");

                    $('#alert').css("display", "block");

                    $('#alert').delay(3000).fadeOut("slow");
                    return false;
                } else {
                    $.ajax({
                        type: "POST",
                        url: 'data.php',
                        data: frm.serializeArray(),
                        success: function (data) {
                            var obj = JSON.parse(data);
                            console.log(obj);

                            if (obj.response == 1) {
                                // alert(obj.msg);
                                $('#alert').html(obj.msg);

                                $('#alert').css("display", "block");

                                $('#alert').delay(3000).fadeOut("slow");
                                return false;
                            }

                            var vertex = [];
                            var score = [];
                            var ave = [];
                            var vertexCp = [];
                            var scoreCp = [];

                            for (var i in obj[0]) {
                                vertex.push(obj[0][i].name);
                                score.push(parseFloat(obj[0][i][0].average));
                                ave.push(parseFloat(obj[2][i][0].aver));
                            }

                            for (var i in obj[1]) {
                                vertexCp.push(obj[1][i].name);
                                scoreCp.push(parseFloat(obj[1][i][0].average));
                            }
                            var chartype = {
                                polar: true,
                                type: 'line'
                            }
                            var chartitle = {
                                text: 'Biểu đồ năng lực sinh viên',
                                x: -80
                            }
                            var chartpane = {
                                size: '80%'
                            }
                            var chartxaxis = {
                                categories: vertex,
                                tickmarkPlacement: 'on',
                                lineWidth: 0
                            }
                            var chartyaxis = {
                                gridLineInterpolation: 'polygon',
                                lineWidth: 0,
                                min: 0,
                                max: 10
                            }
                            var chartooltip = {
                                shared: true,
                                valueDecimals: 1,
                                valueSuffix: ' điểm'
                            }
                            var chartlegend = {
                                align: 'right',
                                verticalAlign: 'top',
                                y: 70,
                                layout: 'vertical'
                            }
                            var chartseries = [{
                                name: obj[0][0].user,
                                data: score,
                                color: '#7cb5ec',
                                pointPlacement: 'on'
                            }, {
                                name: obj[1][0].user,
                                data: scoreCp,
                                color: '#00FF00',
                                pointPlacement: 'on'
                            }, {
                                name: 'Trung bình',
                                data: ave,
                                color: '#FF0000',
                                lineWidth: 1,
                                pointPlacement: 'on'
                            }]
                            $('#chart1').highcharts({
                                chart: chartype,
                                title: chartitle,
                                pane: chartpane,
                                xAxis: chartxaxis,
                                yAxis: chartyaxis,
                                tooltip: chartooltip,
                                legend: chartlegend,
                                series: chartseries
                            });

                        }
                    });
                }
            }

        });

        $('#compare').click(function (e) {
            if ($('#compare-student').css("display") == "none") {
                $('#compare-student').css("display", "block");
                $('#choose-compare-student').attr('name', 'compareStudentId');
            } else {
                $('#compare-student').css("display", "none");
                $('#choose-compare-student').removeAttr('name');
            }
        });
    });
</script>
</body>
</html>
