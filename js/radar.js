$(document).ready(function () {
    $('#myform').on('submit', function (e) {
        $('.table-container').empty();
        $('#detail').unbind("click");
        $('.table-container').css("display", "none");
        $('.graph').css("display", "none");
        $('#detail').css("display", "none");
        $('#mycanvas').remove();
        $('.chart-container').append('<canvas id="mycanvas"><canvas>');
        e.preventDefault();
        var frm = $('#myform');

        if ($('#compare-student').css("display") == "none") {
            $.ajax({
                type: "POST",
                url: 'data.php',
                data: frm.serializeArray(),
                success: function (data) {
                    var obj = JSON.parse(data);
                    // console.log(obj);
                    // return false;
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
                    $("#detail").css("display", "block");
                    $(".detail").css("display", "block");

                    var ctx = $("#mycanvas");

                    var barGraph = new Chart(ctx, {
                        type: 'radar',
                        data: chartdata,
                        options: options,
                    });

                    $('#detail').click(function (e) {
                        e.preventDefault();
                        $('.chart-container').css("display", "none");
                        $('.table-container').css("display", "block");
                        $('.detail').css("display", "none");
                        $('.graph').css("display", "block");

                        var sum = 0;

                        var table = document.createElement("table");
                        table.setAttribute("class", "table table-bordered");
                        $('.table-container').append(table);

                        var thead = document.createElement("thead");
                        table.appendChild(thead);

                        var trHead = document.createElement("tr");
                        thead.appendChild(trHead);

                        var thHead = document.createElement("th");
                        thHead.setAttribute("colspan", "3");
                        trHead.appendChild(thHead);

                        thHead.innerHTML = obj[0].user;

                        var tbody = document.createElement("tbody");
                        table.appendChild(tbody);

                        var trBody = document.createElement("tr");
                        tbody.appendChild(trBody);

                        for (var i = 1; i <= 3; i++) {
                            var thBody = document.createElement("th");
                            trBody.appendChild(thBody);
                            if (i == 1) {
                                thBody.innerHTML = "Chương";
                            } else if (i == 2) {
                                thBody.innerHTML = "ĐTB chương";
                            } else {
                                thBody.innerHTML = "Đánh giá";
                            }
                        }

                        for (var i = 0; i < obj.length; i++) {
                            var trBody1 = document.createElement("tr");
                            tbody.appendChild(trBody1);

                            var tdBody1 = document.createElement("td");
                            tdBody1.setAttribute("width", "60%");
                            var tdBody2 = document.createElement("td");
                            tdBody2.setAttribute("width", "20%");
                            var tdBody3 = document.createElement("td");
                            tdBody3.setAttribute("width", "20%");

                            trBody1.appendChild(tdBody1);
                            trBody1.appendChild(tdBody2);
                            trBody1.appendChild(tdBody3);

                            tdBody1.innerHTML = obj[i].name;
                            tdBody2.innerHTML = parseFloat(obj[i].average).toFixed(1);
                            if (tdBody2.innerHTML >= 5) {
                                tdBody3.innerHTML = '<i class="fa fa-check"></i>';
                            } else {
                                tdBody3.innerHTML = '<i class="fa fa-times"></i>';
                            }
                            sum += parseFloat(tdBody2.innerHTML);
                        }

                        var trBody2 = document.createElement("tr");
                        tbody.appendChild(trBody2);

                        var thBody2 = document.createElement("th");
                        trBody2.appendChild(thBody2);
                        thBody2.innerHTML = "Trung bình khóa học: ";

                        var tdBody4 = document.createElement("td");
                        trBody2.appendChild(tdBody4);
                        tdBody4.innerHTML = (sum / obj.length).toFixed(1);

                        var tdBody5 = document.createElement("td");
                        trBody2.appendChild(tdBody5);
                        if (tdBody4.innerHTML >= 5) {
                            tdBody5.innerHTML = '<i class="fa fa-check"></i>';
                        } else {
                            tdBody5.innerHTML = '<i class="fa fa-times"></i>';
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
        } else {
            $('#detail').css("display", "none");
            var studentIdCheck = $('#student').val();
            var studentIdCpCheck = $('#choose-compare-student').val();

            if (studentIdCheck == studentIdCpCheck) {
                $('#alert').css("display", "block");

                $('#alert').delay(3000).fadeOut("slow");
            } else {
                $.ajax({
                    type: "POST",
                    url: 'data.php',
                    data: frm.serializeArray(),
                    success: function (data) {

                        var obj = JSON.parse(data);
                        if (obj.response == 1) {
                            alert("Sinh viên " + obj.user + " hiện chưa có điểm");
                            return false;
                        }
                        var vertex = [];
                        var score = [];
                        var ave = [];
                        var vertexCp = [];
                        var scoreCp = [];

                        for (var i in obj[0]) {
                            vertex.push(obj[0][i].name);
                            score.push(obj[0][i].average);
                            ave.push(5);
                        }

                        for (var i in obj[1]) {
                            vertexCp.push(obj[1][i].name);
                            scoreCp.push(obj[1][i].average);
                        }

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
                                    label: obj[0][0].user,
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
                                    label: obj[1][0].user,
                                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                    borderColor: 'rgb(255, 99, 132)',
                                    pointBackgroundColor: 'rgb(255, 99, 132)',
                                    pointBorderColor: '#fff',
                                    pointHoverBackgroundColor: '#fff',
                                    pointHoverBorderColor: 'rgb(255, 99, 132)',
                                    data: scoreCp,
                                    fill: true,
                                },
                                {
                                    label: "Trung bình",
                                    borderColor: 'rgba(255, 0, 0, 0.5)',
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
