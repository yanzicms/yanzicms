<?php
/**
 * Project: Yanzicms
 * Producer: Yanzicms [ http://www.Yanzicms.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.Yanzicms.com All rights reserved.
 */
namespace app\admin;
use extend\branch\Branch;
use extend\info\Info;
use extend\plugin\Plugin;
use extend\web_dispose\WebDispose;
use model\Attribution;
use model\Dispose;
use swuuws\Cache;
use swuuws\Debug;
use swuuws\Env;
use swuuws\Lang;
use swuuws\Session;
use swuuws\Url;
use swuuws\View;
class Yanzicms
{
    protected $per = 20;
    public function __construct()
    {
        if(!Session::has('id')){
            Url::to('/');
        }
        elseif(Session::get('usertype') >= 10){
            Url::to('user-center/index');
        }
    }
    protected function start($title = '')
    {
        WebDispose::get();
        View::assign('user', Session::get('name'));
        if(!empty($title)){
            View::assign('pagetitle', $title);
        }
        $pluginMenus = [];
        Plugin::addp('pluginMenu', $pluginMenus);
        foreach($pluginMenus as $key => $val){
            $pluginMenus[$key]['href'] = Url::url('admin/explugin', ['plugin' => $val['plugin'], 'func' => $val['function'], 'name' => urlencode($val['name'])]);
        }
        View::assign('pluginMenus', $pluginMenus);
        $templateMenus = [];
        Plugin::addt('templateMenu', $templateMenus);
        foreach($templateMenus as $key => $val){
            $templateMenus[$key]['href'] = Url::url('admin/extemplate', ['plugin' => $val['plugin'], 'func' => $val['function'], 'name' => urlencode($val['name'])]);
        }
        View::assign('templateMenus', $templateMenus);
    }
    protected function allAttribution()
    {
        $allAttribution = new Attribution();
        $allAttribution->id = '';
        $allAttribution->name = '';
        $allAttribution->parent = '';
        $allAttribution->select()->order('sort ASC, id ASC')->getSet(function($data){
            $data = Branch::line($data);
            foreach($data as $key => $val){
                if(isset($data[$key + 1]) && strlen($data[$key + 1]['level']) > strlen($val['level'])){
                    $val['disabled'] = 1;
                }
                else{
                    $val['disabled'] = 0;
                }
                if(!empty($val['level'])){
                    $val['level'] = str_repeat('&nbsp;', strlen($val['level']) * 2) . '└─' . '&nbsp;';
                }
                $data[$key] = $val;
            }
            View::assign('allAttribution', $data);
        });
    }
    protected function disableAlias()
    {
        return ['user', 'dispose', 'attribution', 'content', 'models', 'model', 'attributionlink', 'comment', 'slideshow', 'slidegroup', 'link', 'favorites', 'message', 'id', 'uid', 'keyword', 'title', 'alias', 'summary', 'creation', 'modify', 'parent', 'thumbnail', 'template', 'status', 'comment', 'finalcomment', 'view', 'like', 'top', 'recommend', 'alone', 'order', 'sort', 'group', 'having', 'create'];
    }
    protected function arrayValuesSame($arr1, $arr2, $field = [])
    {
        $str1 = '';
        $str2 = '';
        foreach($field as $val){
            $str1 .= $arr1[$val];
            $str2 .= $arr2[$val];
        }
        if(md5($str1) == md5($str2)){
            return true;
        }
        else{
            return false;
        }
    }
    protected function getTemplates($folder)
    {
        $template = Info::getInfo('template');
        $path = ROOT.'public'.DS.'template'.DS.$template.DS.$folder;
        if(is_dir($path)){
            $re = glob($path.DS.'*.html');
            foreach($re as $key => $val) {
                $tmpdir = basename($val);
                $re[$key] = $tmpdir;
            }
            return $re;
        }
        else{
            return [];
        }
    }
    protected function getAllAttribution($malias = '')
    {
        $allAttribution = new Attribution();
        $allAttribution->id = '';
        $allAttribution->name = '';
        $allAttribution->alias = '';
        $allAttribution->parent = '';
        $allAttribution->select()->order('sort ASC, id ASC')->getSet(function($data) use ($malias){
            $data = Branch::line($data);
            $allList = [];
            $oneList = [];
            $alias = '';
            foreach($data as $key => $val){
                if(isset($data[$key + 1]) && strlen($data[$key + 1]['level']) > strlen($val['level'])){
                    $val['disabled'] = 1;
                }
                else{
                    $val['disabled'] = 0;
                }
                if(!empty($val['level'])){
                    $val['level'] = str_repeat('&nbsp;', strlen($val['level']) * 2) . '└─' . '&nbsp;';
                    $data[$key] = $val;
                    $oneList[] = $val;
                }
                else{
                    $data[$key] = $val;
                    if(count($oneList) > 0){
                        $allList[] = [
                            'alias' => $alias,
                            'list' => $oneList
                        ];
                        $oneList = [];
                    }
                    $oneList[] = $val;
                    $alias = $val['alias'];
                }
            }
            if(count($oneList) > 0){
                $allList[] = [
                    'alias' => $alias,
                    'list' => $oneList
                ];
            }
            if(!empty($malias)){
                $adata = null;
                foreach($allList as $key => $val){
                    if($val['alias'] == $malias){
                        $adata = $val['list'];
                        break;
                    }
                }
                if($adata == null){
                    $adata = $data;
                }
                View::assign('allAttribution', $adata);
            }
            else{
                View::assign('listAttribution', $allList);
                View::assign('allAttribution', $data);
            }
        });
    }
    protected function delimg($path)
    {
        $path = str_replace('..', '', $path);
        if(substr($path, 0, 5) == 'data/'){
            @unlink(ROOT . 'public' . DS . str_replace('/', DS, $path));
        }
    }
}