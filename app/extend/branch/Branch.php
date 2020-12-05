<?php
/**
 * Project: Yanzicms
 * Producer: Yanzicms [ http://www.Yanzicms.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.Yanzicms.com All rights reserved.
 */
namespace extend\branch;
class Branch
{
    private static $list = [];
    public static function line($array, $level = '--', $pname = 'parent', $lname = 'level')
    {
        self::$list = [];
        $re = self::linefun($array, $pname, $lname);
        foreach($re as $key => $value){
            $re[$key][$lname] = str_repeat($level, $value[$lname]);
        }
        return $re;
    }
    public static function grade($array, $pname = 'parent', $son = 'son')
    {
        $re = [];
        $arr = [];
        foreach($array as $value){
            $arr[$value['id']] = $value;
        }
        foreach($arr as $key => $value){
            if($value[$pname] > 0){
                $arr[$value[$pname]][$son][] = &$arr[$key];
            }else{
                $re[] = &$arr[$key];
            }
        }
        return $re;
    }
    private static function linefun($array, $pname = 'parent', $lname = 'level', $pid =0, $level = 0)
    {
        foreach ($array as $key => $value){
            if($value[$pname] == $pid){
                $value[$lname] = $level;
                self::$list[] = $value;
                unset($array[$key]);
                self::linefun($array, $pname, $lname, $value['id'], $level+1);
            }
        }
        return self::$list;
    }
}