<?php

/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 3/10/2019
 * Time: 10:16 PM
 */
class block_grades_chart extends block_base
{
    public function init()
    {
        $this->title = "Biểu đồ phân tích";
    }
    // The PHP tag and the curly bracket for the class definition
    // will only be closed after there is another function added in the next section.
    public function get_content()
    {
        global $CFG, $USER;

        $course = $this->page->course;
        $context = context_course::instance($course->id);
        $canview = has_capability('block/grades_chart:viewpages', $context);

        $roles = get_user_roles($context, $USER->id);

        $isStudent = current(get_user_roles($context, $USER->id))->shortname == 'student' ? 1 : 2;

        if ($this->content !== null) {
            return $this->content;
        }
        $courseId = $_GET['id'];
        $chartURL = new moodle_url('/blocks/grades_chart/gradeschart.php', array('courseId' => $courseId));
        $tableURL = new moodle_url('/blocks/grades_chart/gradestable.php', array('courseId' => $courseId));
        $addInstructionURL = new moodle_url('/blocks/grades_chart/addinstruction.php', array('courseId' => $courseId));
        $accesscontentURL = new moodle_url('/blocks/grades_chart/graphresourcestartup.php', array('courseId' => $courseId));
        $hitURL = new moodle_url('/blocks/grades_chart/hits.php', array('courseId' => $courseId, "legacy" => 0));
        $numberActiveURL = new moodle_url('/blocks/grades_chart/timeaccesses.php', array('courseId' => $courseId, "days" => 7));

        $this->content = new stdClass;

        if ($isStudent == 1) {
            $this->content->text = '<li><a href="' . $tableURL . '" target="_blank">Bảng xem lại khóa học</a></li>';
        } else {
            $this->content->text = '<li><a href="' . $chartURL . '" target="_blank">Biểu đồ năng lực học viên</a></li>';
            $this->content->text .= '<li><a href="' . $accesscontentURL . '" target="_blank">Biểu đồ thống kê số lượt truy cập</a></li>';
            $this->content->text .= '<li><a href="' . $hitURL . '" target="_blank">Biểu đồ phân phối lượt truy cập</a></li>';
            $this->content->text .= '<li><a href="' . $numberActiveURL . '" target="_blank">Biểu đồ hoạt động của học viên</a></li>';
            $this->content->text .= '<li><a href="' . $addInstructionURL . '" target="_blank">Bảng hỗ trợ học viên</a></li>';
        }
        $this->content->footer = '<hr/>';
        return $this->content;
    }
}