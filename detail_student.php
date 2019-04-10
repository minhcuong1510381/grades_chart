<?php
require_once("../../config.php");
require_once($CFG->dirroot . '/lib/moodlelib.php');
global $DB, $USER;
require("lib.php");
// include('data.php');

$courseId = $_GET['courseId'];
$userName = $_GET['userName'];
$userId = $_GET['userId'];

require_login($courseId);

$context = context_course::instance($courseId);

$roles = get_user_roles($context, $USER->id);

$isStudent = current(get_user_roles($context, $USER->id))->shortname == 'student' ? 1 : 2;

if ($isStudent == 1) {
    return false;
}

?>
<?php include('inc/header.php') ?>
    <div class="container" style="height: 700px;">
        <div class="header">
            <div class="title-gradeschart" style="margin: 0 auto; width: 400px; text-align: center">
                <h3>Chi tiết năng lực của sinh viên <?php echo $userName; ?></h3>
            </div>
        </div>
        <div class="content">

        </div>
        <div class="chart-container" style="display: block; width: 1000px;">
            <canvas id="detailCanvas" style="width: 1000px"></canvas>
        </div>
    </div>

<?php include('inc/footer.php'); ?>

<script>
    function detailChart() {
        $('#detailCanvas').remove();
        $('.chart-container').append('<canvas id="detailCanvas"><canvas>');

        $.ajax({
            type: "GET",
            url: "data_detail.php?courseId=" + <?php echo $courseId; ?> + "&userId=" + <?php echo $userId; ?>,
            success: function (data) {
                var obj = JSON.parse(data);
                console.log(obj);
                if (obj.response == 1) {
                    alert("Sinh viên " + obj.user + " hiện chưa có điểm");
                    return false;
                }
                var vertex = [];
                var score = [];
                var ave = [];

                for (var i in obj) {
                    vertex.push(obj[i].name);
                    score.push(obj[i].average);
                    ave.push(5);
                }

                function getColor() {
                    var color = ['#E74C3C', '#F4D03F', '#48C9B0', '#5DADE2', '#BB8FCE', '#F39C12', '#138D75' , '#FFA07A'];
                    return color;
                }

                var ctx = $("#detailCanvas");

                var options = {
                    scales: {
                        xAxes: [{
                            display: true,
                            scaleLabel: {
                                display: true,
                            }
                        }],
                        yAxes: [{
                            display: true,
                            ticks: {
                                beginAtZero: true,
                                min: 0,
                                max: 10
                            }
                        }]
                    },
                };

                var barGraph = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: vertex,
                        datasets: [
                            {
                                label: "Điểm trung bình của sinh viên",
                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                borderColor: 'rgb(75, 192, 192)',
                                data: score,
                                lineTension :0.1,
                                fill: true,
                            },
                            {
                                label: "Trung bình",
                                borderColor: 'rgb(255, 0, 0)',
                                borderWidth: '1',
                                data: ave,
                                fill: false,
                            }
                        ]
                    },
                    options: options
                });


            }
        });
    }
    window.onload = detailChart;
</script>

</body>
</html>