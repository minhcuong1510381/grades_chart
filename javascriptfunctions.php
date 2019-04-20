<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();
?>

<script type="text/javascript">

    function parseObjToString(obj) {
        var array = $.map(obj, function (value) {
            return [value];
        });
        return array;
    }

    function showContent() {
        $(".div_nomes").dialog({
            modal: true,
            autoOpen: false,
            width: 'auto'
        });
    }

    function createContent(titulo, alunos) {
        var nomes = "";
        ids = [];
        email = [];
        $.each(alunos, function (ind, val) {
            nomes += val.nome + ", ";
            ids.push(val.userid);
            email.push(val.email);
        });
        var string =
            "<h3>" + titulo + "</h3>" +
            "<p style='font-size:15px'>" + "Sinh viên: "+nomes + "</p>";

        return string;
    }


    function convert_series_to_group(group_id, groups, all_content, chart_id) {

        $(chart_id).highcharts().series[0].setData([0]);
        $(chart_id).highcharts().series[1].setData([0]);

        //comeback to original series
        if (group_id == "-") {
            var nraccess_vet = [];
            var nrntaccess_vet = [];
            $.each(geral, function (index, value) {
                if (value.numberofaccesses > 0) {
                    nraccess_vet.push(value.numberofaccesses);
                } else {
                    nraccess_vet.push([0]);
                }

                if (value.numberofnoaccess > 0) {
                    nrntaccess_vet.push(value.numberofnoaccess);
                } else {
                    nrntaccess_vet.push([0]);
                }
            });

            $(chart_id).highcharts().series[0].setData(nraccess_vet);
            $(chart_id).highcharts().series[1].setData(nrntaccess_vet);
        } else {
            $.each(groups, function (index, group) {
                if (index == group_id) {
                    var access = group.numberofaccesses;
                    var noaccess = group.numberofnoaccess;
                    $(chart_id).highcharts().series[0].setData(access);
                    $(chart_id).highcharts().series[1].setData(noaccess);
                }
            });
        }
    }

</script>