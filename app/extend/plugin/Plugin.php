<?php
/**
 * Project: Yanzicms
 * Producer: Yanzicms [ http://www.Yanzicms.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.Yanzicms.com All rights reserved.
 */
namespace extend\plugin;
use extend\info\Info;
use swuuws\Debug;
use swuuws\Hook;
use swuuws\Lang;
class Plugin
{
    public static function add($name, $plugin, &$param = null)
    {
        Hook::add($name, $plugin);
        return Hook::listen($name, $param);
    }
    public static function adds($name, &$param = null)
    {
        $hashook = false;
        $plugins = Info::getInfo('openedplugins');
        if(!empty($plugins)){
            $plugins = unserialize($plugins);
            if(count($plugins) > 0){
                foreach($plugins as $val){
                    Lang::loadPack(APP . 'plugin' . DS . $val . DS . 'lang', true);
                }
                Hook::add($name, $plugins);
                $hashook = true;
            }
        }
        $template = Info::getInfo('template');
        if(is_file(ROOT . 'public' . DS . 'template' . DS . $template . DS  . ucfirst($template) . '.php')){
            Lang::loadPack(ROOT . 'public' . DS . 'template' . DS . $template . DS . 'lang', true);
            Hook::add($name, 'template/' . $template);
            $hashook = true;
        }
        if($hashook){
            return Hook::listen($name, $param);
        }
        return false;
    }
    public static function addp($name, &$param = null)
    {
        $plugins = Info::getInfo('openedplugins');
        if(!empty($plugins)){
            $plugins = unserialize($plugins);
            if(count($plugins) > 0){
                foreach($plugins as $val){
                    Lang::loadPack(APP . 'plugin' . DS . $val . DS . 'lang', true);
                }
                Hook::add($name, $plugins);
                return Hook::listen($name, $param);
            }
        }
        return false;
    }
    public static function addt($name, &$param = null)
    {
        $template = Info::getInfo('template');
        if(is_file(ROOT . 'public' . DS . 'template' . DS . $template . DS  . ucfirst($template) . '.php')){
            Lang::loadPack(ROOT . 'public' . DS . 'template' . DS . $template . DS . 'lang', true);
            Hook::add($name, 'template/' . $template);
            return Hook::listen($name, $param);
        }
        return false;
    }
}