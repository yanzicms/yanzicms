<?php
/**
 * Project: Yanzicms
 * Producer: Yanzicms [ http://www.Yanzicms.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.Yanzicms.com All rights reserved.
 */
namespace extend\info;
use extend\yanzi\Yanzi;
use model\Dispose;
use swuuws\Cache;
class Info
{
    public static function getInfo($name)
    {
        $btime = Cache::get('extend_info_buildtime_' . $name);
        if($btime === false){
            $getInfo = new Dispose();
            $getInfo->content = '';
            $getInfo->select('name = ?', $name)->getOne(function($data) use (&$btime, $name){
                if(count($data) > 0){
                    $btime = $data['content'];
                }
                else{
                    $btime = '';
                }
                Cache::set('extend_info_buildtime_' . $name, $btime);
            });
            $getInfo = null;
        }
        return $btime;
    }
    public static function setInfo($name, $value)
    {
        $selfInfo = new Dispose();
        $selfInfo->id = '';
        $selfInfo->select('name = ?', $name)->limit(1)->getOne(function($data) use($name, $value){
            if(count($data) > 0){
                $setInfo = new Dispose();
                $setInfo->content = $value;
                $setInfo->update('id = ?', $data['id']);
            }
            else{
                $setInfo = new Dispose();
                $setInfo->name = $name;
                $setInfo->content = $value;
                $setInfo->insert();
            }
            $setInfo = null;
        });
        Cache::delete('extend_info_buildtime_' . $name);
    }
    public static function latestVersion()
    {
        $yanzi = Cache::get('yanzilatestversion');
        if($yanzi === false){
            $yanzi = Yanzi::curl('http://www.yanzicms.com/version/index.html?dm=' . urlencode(self::getInfo('domain')) . '&ttl=' . urlencode(self::getInfo('title')));
            if($yanzi === false){
                $yanzi = '';
            }
            Cache::set('yanzilatestversion', $yanzi, '86400');
        }
        return $yanzi;
    }
}