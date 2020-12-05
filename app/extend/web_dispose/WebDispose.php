<?php
/**
 * Project: Yanzicms
 * Producer: Yanzicms [ http://www.Yanzicms.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.Yanzicms.com All rights reserved.
 */
namespace extend\web_dispose;
use model\Dispose;
use swuuws\Cache;
use swuuws\Debug;
use swuuws\Env;
use swuuws\View;
class WebDispose
{
    private static $template = 'yanzi';
    public static function get()
    {
        $result = Cache::get('dispose');
        if($result === false){
            $dispose = new Dispose();
            $dispose->name = '';
            $dispose->content = '';
            $result = $dispose->select('autoload = ?', [1])->getSet();
            Cache::set('dispose', $result);
        }
        foreach($result as $item){
            if($item['name'] == 'domain' && Env::get('C_WEB_ROOT') == ''){
                $item['content'] .= 'public/';
            }
            if($item['name'] == 'rewrite' && $item['content'] == 1){
                Env::set('OPENED_REWRITE', 'on');
            }
            if($item['name'] == 'template'){
                self::$template = trim($item['content']);
            }
            View::assign($item['name'], $item['content']);
        }
        return $result;
    }
    public static function getTemplate()
    {
        return self::$template;
    }
}