<?php
/**
 * Project: Yanzicms
 * Producer: Yanzicms [ http://www.Yanzicms.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.Yanzicms.com All rights reserved.
 */
namespace app\user_center;
use extend\branch\Branch;
use extend\plugin\Plugin;
use extend\web_dispose\WebDispose;
use model\Attribution;
use swuuws\Debug;
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
    }
    protected function start($title = '')
    {
        WebDispose::get();
        View::assign('user', Session::get('name'));
        if(!empty($title)){
            View::assign('pagetitle', $title);
        }
        if(Session::get('usertype') < 10){
            View::assign('isAdmin', 1);
        }
        else{
            View::assign('isAdmin', 0);
        }
        $pluginUserMenus = [];
        Plugin::addp('pluginUserMenu', $pluginUserMenus);
        foreach($pluginUserMenus as $key => $val){
            $pluginUserMenus[$key]['href'] = Url::url('user_center/explugin', ['plugin' => $val['plugin'], 'func' => $val['function'], 'name' => urlencode($val['name'])]);
        }
        View::assign('pluginUserMenus', $pluginUserMenus);
        $templateUserMenus = [];
        Plugin::addt('templateUserMenu', $templateUserMenus);
        foreach($templateUserMenus as $key => $val){
            $templateUserMenus[$key]['href'] = Url::url('user_center/extemplate', ['plugin' => $val['plugin'], 'func' => $val['function'], 'name' => urlencode($val['name'])]);
        }
        View::assign('templateUserMenus', $templateUserMenus);
        if(count($pluginUserMenus) > 0 || count($templateUserMenus) > 0){
            View::assign('hasother', 1);
        }
        else{
            View::assign('hasother', 0);
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
}