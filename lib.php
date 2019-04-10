<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 3/11/2019
 * Time: 10:34 AM
 */
defined('MOODLE_INTERNAL') || die();

function block_grades_chart_convert_to_array($user){
    $result = array();
    if($user){
        foreach ($user as $key => $value){
            $result[] = $value;
        }
    }

    return $result;
}

function groupArray($arr, $group, $preserveGroupKey = false, $preserveSubArrays = false)
{
    $temp = array();
    foreach ($arr as $key => $value) {
        $groupValue = $value[$group];
        if (!$preserveGroupKey) {
            unset($arr[$key][$group]);
        }
        if (!array_key_exists($groupValue, $temp)) {
            $temp[$groupValue] = array();
        }

        if (!$preserveSubArrays) {
            $data = count($arr[$key]) == 1 ? array_pop($arr[$key]) : $arr[$key];
        } else {
            $data = $arr[$key];
        }
        $temp[$groupValue][] = $data;
    }
    return $temp;
}

function countMaxArray($arr){
	$max = 0;
	foreach ($arr as $key => $value) {
	    if($max == 0){
	        $max = count($value);
        }
	    else {
            if (count($value) > $max) {
                $max = count($value);
            }
        }
	}
	return $max;
}