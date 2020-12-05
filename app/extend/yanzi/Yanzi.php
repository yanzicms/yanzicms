<?php
/**
 * Project: Yanzicms
 * Producer: Yanzicms [ http://www.Yanzicms.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.Yanzicms.com All rights reserved.
 */
namespace extend\yanzi;
use swuuws\Url;
use swuuws\View;
class Yanzi
{
    public static function jsonEncode($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
    public static function jsonDecode($value)
    {
        return json_decode($value, true);
    }
    public static function curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:82.0) Gecko/20100101 Firefox/82.0');
        curl_setopt($ch , CURLOPT_URL , $url);
        if(substr($url, 0, 8) == 'https://'){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
    public static function addMenu(&$param, $name, $func = '')
    {
        $calltrace = debug_backtrace();
        $plugin = basename(dirname($calltrace[0]['file']));
        if(is_array($name)){
            foreach($name as $key => $val){
                $param[] = [
                    'plugin' => $plugin,
                    'name' => $val['name'],
                    'function' => $val['function']
                ];
            }
        }
        else{
            $param[] = [
                'plugin' => $plugin,
                'name' => $name,
                'function' => $func
            ];
        }
    }
    public static function getTemplate($name = '')
    {
        $calltrace = debug_backtrace();
        $filePath = dirname($calltrace[0]['file']);
        $plugin = basename($filePath);
        if(empty($name)){
            $name = $plugin;
        }
        return View::view($filePath . DS . 'template' . DS . $name);
    }
    public static function getCss($path = '')
    {
        $calltrace = debug_backtrace();
        $filePath = dirname($calltrace[0]['file']);
        $plugin = basename($filePath);
        $recss = '';
        if(empty($path)){
            $path = 'css' . DS . $plugin . '.css';
            $recss .= file_get_contents($filePath . DS . $path);
        }
        else{
            if(is_array($path)){
                foreach($path as $key => $val){
                    if(substr($val, -4) != '.css'){
                        $val .= '.css';
                    }
                    $val = ltrim(str_replace(['/', '\\'], DS, $val), DS);
                    $recss .= file_get_contents($filePath . DS . $val);
                }
            }
            else{
                if(substr($path, -4) != '.css'){
                    $path .= '.css';
                }
                $path = ltrim(str_replace(['/', '\\'], DS, $path), DS);
                $recss .= file_get_contents($filePath . DS . $path);
            }
        }
        return '<style>' . $recss . '</style>';
    }
    public static function getJs($path = '')
    {
        $calltrace = debug_backtrace();
        $filePath = dirname($calltrace[0]['file']);
        $plugin = basename($filePath);
        $rejs = '';
        if(empty($path)){
            $path = 'js' . DS . $plugin . '.js';
            $rejs .= file_get_contents($filePath . DS . $path);
        }
        else{
            if(is_array($path)){
                foreach($path as $key => $val){
                    if(substr($val, -3) != '.js'){
                        $val .= '.js';
                    }
                    $val = ltrim(str_replace(['/', '\\'], DS, $val), DS);
                    $rejs .= file_get_contents($filePath . DS . $val);
                }
            }
            else{
                if(substr($path, -3) != '.js'){
                    $path .= '.js';
                }
                $path = ltrim(str_replace(['/', '\\'], DS, $path), DS);
                $rejs .= file_get_contents($filePath . DS . $path);
            }
        }
        return '<script>' . $rejs . '</script>';
    }
    public static function pluginUrl($func, $name = 'yanzi')
    {
        $calltrace = debug_backtrace();
        $plugin = basename(dirname($calltrace[0]['file']));
        return Url::url('admin/explugin', ['plugin' => $plugin, 'func' => $func, 'name' => urlencode($name)]);
    }
    public static function templateUrl($func, $name = 'yanzi')
    {
        $calltrace = debug_backtrace();
        $plugin = basename(dirname($calltrace[0]['file']));
        return Url::url('admin/extemplate', ['plugin' => $plugin, 'func' => $func, 'name' => urlencode($name)]);
    }
    public static function pluginUserUrl($func, $name = 'yanzi')
    {
        $calltrace = debug_backtrace();
        $plugin = basename(dirname($calltrace[0]['file']));
        return Url::url('user_center/explugin', ['plugin' => $plugin, 'func' => $func, 'name' => urlencode($name)]);
    }
    public static function templateUserUrl($func, $name = 'yanzi')
    {
        $calltrace = debug_backtrace();
        $plugin = basename(dirname($calltrace[0]['file']));
        return Url::url('user_center/extemplate', ['plugin' => $plugin, 'func' => $func, 'name' => urlencode($name)]);
    }
    public static function filter($str, $keep = 'p,u,s,strike,i,b,big,small,strong,font,h1,h2,h3,h4,h5,h6,span,blockquote,a,pre,sub,sup,ol,ul,li,br,hr,img,div,table,thead,tr,th,tbody,td,label,video,audio,source')
    {
        if(!is_array($keep)){
            $keep = explode(',', $keep);
        }
        $keep = array_map(function($v){
            $v = trim($v);
            if(substr($v,0, 1) != '<'){
                $v = '<' . $v . '>';
            }
            return $v;
        }, $keep);
        $keeps = implode(' ', $keep);
        $str = strip_tags($str, $keeps);
        $str = preg_replace("/(<[A-Za-z]+ [^>]*on)([^>]*>)/", '$1 $2', $str);
        return $str;
    }
}