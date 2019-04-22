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
                Chọn sinh viên để so sánh không phù hợp.
            </div>
            <h5 style="margin: 0 auto; width: 500px;">Chọn sinh viên:</h5>
            <div class="form-group choose-student" style="margin: 0 auto; width: 500px;">
                <select class="form-control selectpicker" id="student" name="studentId" data-live-search="true">
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
                                <div class="modal-content">

                                    <!-- Modal Header -->
                                    <div class="modal-header">
                                        <h4 class="modal-title">Chọn dữ liệu cho tiêu chí <?php echo $i; ?></h4>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>

                                    <!-- Modal body -->
                                    <div class="modal-body">
                                        <?php foreach ($aQuiz as $k => $v) { ?>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input"
                                                       id="choiceData[<?php echo $i; ?>][<?php echo $v->{'id'}; ?>]"
                                                       name="idQuiz[<?php echo $i; ?>][<?php echo $v->{'id'}; ?>]"
                                                       value="<?php echo $v->{'id'}; ?>">
                                                <label class="form-check-label"
                                                       for="choiceData[<?php echo $i; ?>][<?php echo $v->{'id'}; ?>]"><?php echo $v->{'name'}; ?></label>
                                            </div>
                                        <?php } ?>
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

    <div class="chart-container" style="display: none; width: 800px;">
        <canvas id="mycanvas" style="width: 800px"></canvas>
    </div>
</div>

<?php include('inc/footer.php') ?>
<script>
    $(document).ready(function () {
        $('#setting').on('click', function () {
            // $('.choose-topic').css('display',"block");
            // $('.group-setting').css('display',"none");
            var numberTopic = $('#numberTopic').val();
            if (numberTopic < 3) {
                alert("Cần thiết lập ít nhất 3 tiêu chí");
                return false;
            }
            var courseId = $('#courseId').val();
            window.location.href = "gradeschart.php?courseId=" + courseId + "&countTopic=" + numberTopic;
        });
        $('#back').on('click',function () {
            var courseId = $('#courseId').val();
            window.location.href = "gradeschart.php?courseId=" + courseId;
        });
        $('#myform').on('submit', function (e) {
            $('#mycanvas').remove();
            $('.chart-container').append('<canvas id="mycanvas"><canvas>');
            e.preventDefault();
            var frm = $('#myform');
            console.log(frm);
            $.ajaxSetup({
                cache: false
            });
            $.ajax({
                type: "POST",
                url: 'data.php',
                data: frm.serializeArray(),
                success: function (data) {
                    var obj = JSON.parse(data);
                    if (obj.response == 1) {
                        alert(obj.msg);
                        return false;
                    }

                    var vertex = [];
                    var score = [];
                    var ave = [];

                    for (var i in obj) {
                        vertex.push(obj[i].name);
                        score.push(obj[i][0].average);
                        ave.push(5);
                    }
                    console.log(score);

                    var options = {
                        scale: {
                            ticks: {
                                beginAtZero: true,
                                max: 10
                            }
                        }
                    };

                    var chartdata = {
                        labels: vertex,
                        datasets: [
                            {
                                label: obj[0].user,
                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                borderColor: 'rgb(54, 162, 235)',
                                pointBackgroundColor: 'rgb(54, 162, 235)',
                                pointBorderColor: '#fff',
                                pointHoverBackgroundColor: '#fff',
                                pointHoverBorderColor: 'rgb(54, 162, 235)',
                                data: score,
                                fill: true,
                            },
                            {
                                label: "Trung bình",
                                borderColor: 'rgb(255, 0, 0)',
                                backgroundColor: 'rgba(255, 255, 255, 0)',
                                borderWidth: '1',
                                pointStyle: 'cross',
                                data: ave,
                                fill: true,
                            }
                        ]
                    };

                    $(".chart-container").css("display", "block");

                    var ctx = $("#mycanvas");

                    var barGraph = new Chart(ctx, {
                        type: 'radar',
                        data: chartdata,
                        options: options,
                    });

                }
            });
        });
    });
</script>
</body>
</html>
