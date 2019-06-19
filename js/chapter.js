$(document).ready(function () {
    $('#form-chapter').on('submit', function (e) {
        $('.table-container').empty();
        $('#detail').unbind("click");
        $('.table-container').css("display", "none");
        $('.graph').css("display", "none");
        $('#detail').css("display", "none");
        $('#chapterCanvas').remove();
        $('.chart-container').append('<canvas id="chapterCanvas"><canvas>');
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
                // console.log(color);

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
                            label: "Phần trăm học viên có ĐTB lớn hơn hoặc bằng 5",
                            backgroundColor: color,
                            borderColor: boder,
                            borderWidth: '1',
                            data: data,
                            fill: false,
                        }
                    ]
                };
                $(".chart-container").css("display", "block");
                $("#detail").css("display", "block");
                $(".detail").css("display", "block");

                var ctx = $("#chapterCanvas");

                var barGraph = new Chart(ctx, {
                    type: 'bar',
                    data: chartdata,
                    options: options
                });

                $('#detail').click(function (e) {
                    e.preventDefault();
                    $('.chart-container').css("display", "none");
                    $('.table-container').css("display", "block");
                    $('.detail').css("display", "none");
                    $('.graph').css("display", "block");

                    var table = document.createElement("table");
                    table.setAttribute("class", "table table-bordered");
                    $('.table-container').append(table);

                    var thead = document.createElement("thead");
                    table.appendChild(thead);

                    var trHead = document.createElement("tr");
                    thead.appendChild(trHead);

                    var thHead1 = document.createElement("th");
                    var thHead2 = document.createElement("th");
                    thHead1.innerHTML = "Tên bài kiểm tra";
                    thHead2.innerHTML = "Số liệu";

                    trHead.appendChild(thHead1);
                    trHead.appendChild(thHead2);

                    var tbody = document.createElement("tbody");
                    table.appendChild(tbody);

                    for (var i = 0; i < obj.length; i++){
                        var trbody1 = document.createElement("tr");
                        var trbody2 = document.createElement("tr");

                        tbody.appendChild(trbody1);
                        tbody.appendChild(trbody2);

                        var tdbody1 = document.createElement("th");
                        tdbody1.setAttribute("rowspan", "2");
                        var tdbody2 = document.createElement("td");
                        var abody2 = document.createElement("a");
                        var abody3 = document.createElement("a");
                        var tdbody3 = document.createElement("td");

                        tdbody1.innerHTML = obj[i].name;
                        abody2.innerHTML = obj[i].count_student_has_grade_gt_5 + " Học viên TRÊN trung bình";
                        abody3.innerHTML = obj[i].count_student_has_grade_lt_5 + " Học viên DƯỚI trung bình";
                        abody2.setAttribute("href", "#");
                        abody2.setAttribute("data-toggle", "modal");
                        abody2.setAttribute("data-target", "#modalPass["+obj[i].quizId+"]");
                        abody3.setAttribute("href", "#");
                        abody3.setAttribute("data-toggle", "modal");
                        abody3.setAttribute("data-target", "#modalNotPass["+obj[i].quizId+"]");

                        tdbody2.appendChild(abody2);
                        tdbody3.appendChild(abody3);

                        trbody1.appendChild(tdbody1);
                        trbody1.appendChild(tdbody2);
                        trbody2.appendChild(tdbody3);

                    }
                });

                $('#graph').click(function (e) {
                    e.preventDefault();
                    $('.chart-container').css("display", "block");
                    $('.table-container').empty();
                    $('.detail').css("display", "block");
                    $('.graph').css("display", "none");
                });


            }
        });
    });
});

