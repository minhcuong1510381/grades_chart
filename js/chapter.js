$(document).ready(function () {
    $('#form-chapter').on('submit', function (e) {
        $('#chapterCanvas').remove();
        $('.chart-container').append('<canvas id="chapterCanvas"><canvas>');
        $('.comment').empty();
        e.preventDefault();
        var frm = $('#form-chapter');
        $.ajax({
            type: "POST",
            url: 'data_chapter.php',
            data: frm.serializeArray(),
            success: function (data) {
                var obj = JSON.parse(data);
                console.log(obj);
                if (obj.response == 1) {
                    alert("Năm " + obj.yearReview + " hiện chưa có dữ liệu");
                    return false;
                }
                var vertex = [];
                var data = [];
                var ave = [];
                var color = [];
                var boder = [];

                for (var i in obj) {
                    vertex.push(obj[i].name);
                    data.push(obj[i].per_student_grade_gt_5);
                    ave.push(50);
                    if(obj[i].per_student_grade_gt_5 >= 50){
                        color.push('rgba(75, 192, 192, 0.2)');
                        boder.push('rgb(75, 192, 192)');
                    }
                    else{
                        color.push('rgba(255, 99, 132, 0.2)');
                        boder.push('rgb(255, 99, 132)');
                    }
                }
                console.log(color);

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
                                steps: 10,
                                stepValue: 5,
                                min: 0,
                                suggestedMin: 0,
                                max: 100
                            }
                        }]
                    },
                };
                var chartdata = {
                    labels: vertex,
                    datasets: [
                        {
                            label: "Trung bình",
                            backgroundColor: 'rgba(255, 0, 0, 0.2)',
                            borderColor: 'rgb(255, 0, 0)',
                            borderWidth: '1',
                            type: "line",
                            data: ave,
                            fill: false,
                        },
                        {
                            label: "Phần trăm sinh viên có ĐTB lớn hơn hoặc bằng 5",
                            backgroundColor: color,
                            borderColor: boder,
                            borderWidth: '1',
                            data: data,
                            fill: false,
                        }
                    ]
                };
                $(".chart-container").css("display", "block");
                $(".comment").css("display", "block");
                var ctx = $("#chapterCanvas");

                var barGraph = new Chart(ctx, {
                    type: 'bar',
                    data: chartdata,
                    options: options
                });

                var h5 = document.createElement("h5");
                h5.innerHTML = "Đánh giá mức độ hiệu quả của bài kiểm tra:";
                $(".comment").append(h5);

                var ol = document.createElement("ol");
                $(".comment").append(ol);

                for (var i = 0; i < data.length; i++){
                    var li = document.createElement("li");
                    if(data[i] <= 30){
                        var span = document.createElement("i");
                        span.setAttribute('class', 'fa fa-exclamation-circle');
                        span.setAttribute('style', 'color: #ee5253');
                        li.innerHTML = vertex[i] + ": ";
                        li.append(span);
                    }
                    else if(data[i] > 30 && data[i] <= 50){
                        var span = document.createElement("i");
                        span.setAttribute('class', 'fa fa-exclamation-triangle');
                        span.setAttribute('style', 'color: orange');
                        li.innerHTML = vertex[i] + ": ";
                        li.append(span);
                    }
                    else if(data[i] > 50 && data[i] <= 70){
                        var span = document.createElement("i");
                        span.setAttribute('class', 'em em-slightly_smiling_face');
                        span.setAttribute('style', 'font-size: 12px');
                        li.innerHTML = vertex[i] + ": ";
                        li.append(span);
                    }
                    else if(data[i] >= 70){
                        var span = document.createElement("i");
                        span.setAttribute('class', 'fa fa-thumbs-up');
                        span.setAttribute('style', 'color: #4267b2');
                        li.innerHTML = vertex[i] + ": ";
                        li.append(span);
                    }
                    ol.append(li);
                }
            }
        });
    });
});

