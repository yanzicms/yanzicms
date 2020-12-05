<?php
/**
 * Project: Yanzicms
 * Producer: Yanzicms [ http://www.Yanzicms.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.Yanzicms.com All rights reserved.
 */
namespace app\index;
use extend\branch\Branch;
use extend\plugin\Plugin;
use extend\web_dispose\WebDispose;
use model\Advertising;
use model\Attribution;
use model\Content;
use model\Link;
use model\Slidegroup;
use model\Slideshow;
use model\User;
use swuuws\Cache;
use swuuws\Cookie;
use swuuws\Date;
use swuuws\Env;
use swuuws\Lang;
use swuuws\Request;
use swuuws\Session;
use swuuws\Url;
use swuuws\View;
class Yanzicms
{
    protected $template = 'yanzi';
    protected $per = 20;
    protected static $attribution = 0;
    protected static $pageid = 0;
    protected static $pagealias = '';
    public function __construct()
    {
        if(!is_file(APP . 'config' . DS . 'yanzicms.php')){
            Url::to('install/index');
        }
    }
    protected function start($autologin = true)
    {
        WebDispose::get();
        $this->template = WebDispose::getTemplate();
        if($autologin){
            if(!Session::has('id') && Cookie::has('id') && Cookie::has('name') && Cookie::has('secret')){
                $user = new User();
                $user->name = '';
                $user->password = '';
                $user->status = '';
                $user->usertype = '';
                $user->select('id = ?', [trim(Cookie::get('id'))])->limit(1)->getOne(function($data){
                    if($data['status'] == 1 && $data['name'] == Cookie::get('name') && password_verify($data['name'] . $data['password'], Cookie::get('secret'))){
                        Session::set('id', $data['id']);
                        Session::set('name', $data['name']);
                        Session::set('usertype', $data['usertype']);
                    }
                });
            }
        }
    }
    protected function view($name, $spare = '', $model = '', $cbread = '')
    {
        $bread = $this->menu();
        if(!empty($cbread) && is_array($cbread)){
            $bread[] = $cbread;
        }
        $this->slide();
        $this->links();
        $this->latest();
        $this->recommend();
        $this->single();
        $this->popular();
        $this->hot();
        $this->fortemplate();
        $this->advertising();
        View::assign('active', $name);
        View::assign('breadcrumb', $bread);
        View::assign('isMobile', Request::isMobile() ? 1 : 0);
        View::assign('logged', Session::has('id') ? 1 : 0);
        $path = ROOT . 'public' . DS . 'template' . DS . $this->template;
        Lang::loadPack($path . DS . 'lang', true);
        if(Request::isMobile()){
            if(!empty($model)){
                $vfile = $path . DS . 'mobile' . DS . $model . '.' . Env::get('TEMPLATE_SUFFIX');
                if(is_file($vfile)){
                    return View::view($vfile);
                }
            }
            $vfile = $path . DS . 'mobile' . DS . $name . '.' . Env::get('TEMPLATE_SUFFIX');
            if(is_file($vfile)){
                return View::view($vfile);
            }
            if(!empty($spare) && $spare != $name){
                $vfile = $path . DS . 'mobile' . DS . $spare . '.' . Env::get('TEMPLATE_SUFFIX');
                if(is_file($vfile)){
                    return View::view($vfile);
                }
            }
        }
        if(!empty($model)){
            $vfile = $path . DS . $model . '.' . Env::get('TEMPLATE_SUFFIX');
            if(is_file($vfile)){
                return View::view($vfile);
            }
        }
        $vfile = $path . DS . $name . '.' . Env::get('TEMPLATE_SUFFIX');
        if(is_file($vfile)){
            return View::view($vfile);
        }
        if(!empty($spare) && $spare != $name){
            $vfile = $path . DS . $spare . '.' . Env::get('TEMPLATE_SUFFIX');
            if(is_file($vfile)){
                return View::view($vfile);
            }
        }
        if(in_array($name, ['404', 'signup', 'login'])){
            return false;
        }
        return View::view($path . DS . 'index');
    }
    protected function menu()
    {
        $data = Cache::get('menu');
        if($data === false){
            $allAttribution = new Attribution();
            $allAttribution->id = '';
            $allAttribution->name = '';
            $allAttribution->alias = '';
            $allAttribution->parent = '';
            $allAttribution->menu = '';
            $allAttribution->icon = '';
            $data = $allAttribution->select()->order('sort ASC, id ASC')->getSet();
            $data = Branch::line($data);
            Cache::set('menu', $data)->group('menu');
        }
        $parent = [];
        $levelen = [];
        $ismenu = 1;
        $sllen = -1;
        $highlight = [];
        $bdata = [];
        $bread = [];
        foreach($data as $key => $val){
            $bdata[$val['id']] = [
                'title' => $val['name'],
                'alias' => $val['alias'],
                'parent' => $val['parent'],
                'icon' => $val['icon']
            ];
            while(count($parent) > 0 && strlen($val['level']) <= end($levelen)){
                array_pop($parent);
                array_pop($levelen);
            }
            if($val['menu'] == 0){
                if($ismenu == 1){
                    array_push($parent, $val['parent']);
                    array_push($levelen, strlen($val['level']));
                    $ismenu = 0;
                }
                $sllen = -1;
                unset($data[$key]);
            }
            else{
                $ismenu = 1;
                if(count($parent) > 0 && ($sllen == -1 || ($sllen != -1 && strlen($val['level']) <= $sllen))){
                    $data[$key]['parent'] = end($parent);
                    if($sllen == -1){
                        $sllen = strlen($val['level']);
                    }
                }
                if($val['alias'] != ''){
                    $data[$key]['href'] = Url::url('column', ['id' => $val['alias']]);
                }
                else{
                    $data[$key]['href'] = Url::url('column', ['id' => $val['id']]);
                }
                if($val['id'] == self::$attribution){
                    $data[$key]['active'] = 1;
                    $highlight = [
                        'id' => $val['id'],
                        'parent' => $val['parent'],
                        'level' => $val['level']
                    ];
                }
                else{
                    $data[$key]['active'] = 0;
                }
                unset($data[$key]['alias']);
                unset($data[$key]['menu']);
            }
        }
        $highArr = [];
        if(count($highlight) > 0){
            foreach($data as $hkey => $hval){
                if($hval['level'] == $highlight['level'] && $hval['parent'] == $highlight['parent']){
                    unset($hval['level']);
                    unset($hval['parent']);
                    $highArr[] = $hval;
                }
                unset($data[$hkey]['level']);
            }
        }
        View::assign('sibling', $highArr);
        if(self::$attribution > 0){
            $startid = self::$attribution;
            do{
                array_unshift($bread,$bdata[$startid]);
                $startid = $bdata[$startid]['parent'];
            }while($startid > 0 && $bdata[$startid]['parent'] > 0);
            if($startid > 0){
                array_unshift($bread,$bdata[$startid]);
            }
            foreach($bread as $key => $val){
                if($val['alias'] != ''){
                    $bread[$key]['href'] = Url::url('column', ['id' => $val['alias']]);
                }
                else{
                    $bread[$key]['href'] = Url::url('column', ['id' => $val['id']]);
                }
                unset($bread[$key]['alias']);
                unset($bread[$key]['parent']);
            }
        }
        $data = Branch::grade($data);
        Plugin::adds('menu', $data);
        View::assign('menu', $data);
        return $bread;
    }
    protected function getKeyword($keyword)
    {
        if(empty($keyword)){
            return '';
        }
        $reArr = [];
        $keys = explode(',', $keyword);
        foreach($keys as $key => $val){
            $val = trim($val);
            $reArr[] = [
                'name' => $val,
                'href' => Url::url('keyword', ['word' => urlencode($val)])
            ];
        }
        return $reArr;
    }
    protected function getTime($time)
    {
        return [
            'year' => Date::year($time),
            'month' => Date::month($time),
            'day' => Date::day($time),
            'hour' => Date::hour($time),
            'minute' => Date::minute($time),
            'second' => Date::second($time),
        ];
    }
    protected function getDate($date)
    {
        return [
            'year' => Date::year($date),
            'month' => Date::month($date),
            'day' => Date::day($date)
        ];
    }
    protected function getBelong($belong, $aid, $belongalias)
    {
        if(!empty($belongalias)){
            $href = Url::url('column', ['id' => $belongalias]);
        }
        else{
            $href = Url::url('column', ['id' => $aid]);
        }
        return [
            'name' => $belong,
            'href' => $href
        ];
    }
    protected function modifyList(&$list, $hasbelong = true)
    {
        foreach($list as $key => $val){
            $list[$key]['keyword'] = $this->getKeyword($val['keyword']);
            $list[$key]['creation'] = $this->getTime($val['creation']);
            if(!empty($val['alias'])){
                $list[$key]['href'] = Url::url('content', ['id' => $val['alias']]);
            }
            else{
                $list[$key]['href'] = Url::url('content', ['id' => $val['id']]);
            }
            if($hasbelong){
                $list[$key]['belong'] = $this->getBelong($val['belong'], $val['aid'], $val['belongalias']);
            }
            unset($list[$key]['aid']);
            unset($list[$key]['alias']);
        }
    }
    protected function modifyContent(&$arr, $hasbelong = true)
    {
        $arr['keyword'] = $this->getKeyword($arr['keyword']);
        $arr['creation'] = $this->getTime($arr['creation']);
        if(!empty($arr['alias'])){
            $arr['href'] = Url::url('content', ['id' => $arr['alias']]);
        }
        else{
            $arr['href'] = Url::url('content', ['id' => $arr['id']]);
        }
        if($hasbelong){
            $arr['belong'] = $this->getBelong($arr['belong'], $arr['aid'], $arr['belongalias']);
        }
        unset($arr['alias']);
    }
    protected function slide()
    {
        $slide = Cache::get('slide');
        if($slide === false){
            $slideshow = new Slideshow();
            $slideshow->name = '';
            $slideshow->image = '';
            $slideshow->link = '';
            $slideshow->description = '';
            $slidegroup = new Slidegroup();
            $slidegroup->alias = '';
            $data = $slideshow->select('status = ?', 1)->order('sgid asc, sort asc')->join(
                $slidegroup->select()->equal('id', 'sgid')
            )->getSet();
            $slide = [];
            foreach($data as $key => $val){
                $alias = $val['alias'];
                unset($val['alias']);
                $slide[$alias][] = $val;
            }
            Cache::set('slide', $slide);
        }
        Plugin::adds('slide', $slide);
        View::assign('slide', $slide);
    }
    protected function links()
    {
        $links = Cache::get('links');
        if($links === false){
            $link = new Link();
            $link->name = '';
            $link->url = 'href';
            $link->image = '';
            $link->home = '';
            $data = $link->select('status = ?', 1)->order('sort asc')->getSet();
            $links = [];
            foreach($data as $key => $val){
                $home = $val['home'];
                unset($val['home']);
                if($home == 1){
                    $links['home'][] = $val;
                }
                else{
                    $links['nothome'][] = $val;
                }
                $links['all'][] = $val;
            }
            Cache::set('links', $links);
        }
        Plugin::adds('links', $links);
        View::assign('links', $links);
    }
    protected function latest()
    {
        $yanzi = Cache::get('latest');
        if($yanzi === false){
            $content = new Content();
            $content->id = '';
            $content->aid = '';
            $content->keyword = '';
            $content->title = '';
            $content->alias = '';
            $content->summary = '';
            $content->content = '';
            $content->creation = '';
            $content->image = '';
            $content->view = '';
            $content->praise = '';
            $yanzi = $content->select('status = ?', 1)->order('id DESC')->limit($this->per)->getSet();
            $this->modifyList($yanzi, false);
            Cache::set('latest', $yanzi);
        }
        View::assign('latest', $yanzi);
    }
    public function recommend()
    {
        $yanzi = Cache::get('recommend');
        if($yanzi === false){
            $content = new Content();
            $content->id = '';
            $content->aid = '';
            $content->keyword = '';
            $content->title = '';
            $content->alias = '';
            $content->summary = '';
            $content->content = '';
            $content->creation = '';
            $content->image = '';
            $content->view = '';
            $content->praise = '';
            $yanzi = $content->select('recommend = ? AND status = ?', [1,1])->order('id DESC')->limit($this->per)->getSet();
            $this->modifyList($yanzi, false);
            $comma = '';
            $idArr = [];
            foreach($yanzi as $key => $val){
                $comma .= empty($comma) ? '?' : ',?';
                $idArr[] = $val['id'];
            }
            $count = count($yanzi);
            if($count < $this->per){
                $fillContent = new Content();
                $fillContent->id = '';
                $fillContent->aid = '';
                $fillContent->keyword = '';
                $fillContent->title = '';
                $fillContent->alias = '';
                $fillContent->summary = '';
                $fillContent->content = '';
                $fillContent->creation = '';
                $fillContent->image = '';
                $fillContent->view = '';
                $fillContent->praise = '';
                if(empty($comma)){
                    $fillyanzi = $fillContent->select('status = ?', 1)->order('view DESC')->limit($this->per - $count)->getSet();
                }
                else{
                    $idArr[] = 1;
                    $fillyanzi = $fillContent->select('id NOT IN (' . $comma . ') AND status = ?', $idArr)->order('view DESC')->limit($this->per - $count)->getSet();
                }
                $this->modifyList($fillyanzi, false);
                foreach($fillyanzi as $val){
                    $yanzi[] = $val;
                }
                unset($fillyanzi);
            }
            Cache::set('recommend', $yanzi);
        }
        View::assign('recommend', $yanzi);
    }
    protected function single()
    {
        $yanzi = Cache::get('single');
        if($yanzi === false){
            $content = new Content();
            $content->id = '';
            $content->aid = '';
            $content->keyword = '';
            $content->title = '';
            $content->alias = '';
            $content->summary = '';
            $content->content = '';
            $content->creation = '';
            $content->image = '';
            $content->view = '';
            $content->praise = '';
            $yanzi = $content->select('single = ? AND status = ?', [1,1])->order('id DESC')->limit($this->per)->getSet();
            $this->modifyList($yanzi, false);
            if(empty($yanzi)){
                $fillContent = new Content();
                $fillContent->id = '';
                $fillContent->aid = '';
                $fillContent->keyword = '';
                $fillContent->title = '';
                $fillContent->alias = '';
                $fillContent->summary = '';
                $fillContent->content = '';
                $fillContent->creation = '';
                $fillContent->image = '';
                $fillContent->view = '';
                $fillContent->praise = '';
                $fillyanzi = $fillContent->select('status = ?', 1)->order('id DESC')->limit(3)->getSet();
                $this->modifyList($fillyanzi, false);
                foreach($fillyanzi as $val){
                    $yanzi[] = $val;
                }
                unset($fillyanzi);
            }
            Cache::set('single', $yanzi);
        }
        View::assign('single', $yanzi);
        if(isset($yanzi[0])){
            View::assign('firstSingle', $yanzi[0]);
        }
    }
    protected function conversion($val, $type)
    {
        if($type == 'text'){
            return nl2br($val);
        }
        elseif($type == 'date'){
            if($val == '1000-01-01'){
                return '';
            }
            else{
                return $this->getDate($val);
            }
        }
        elseif($type == 'datetime'){
            if($val == '1000-01-01 00:00:00'){
                return '';
            }
            else{
                return $this->getTime($val);
            }
        }
        elseif($type == 'multiplechoice'){
            return explode(',', $val);
        }
        elseif($type == 'multiplepictures'){
            return explode(',', $val);
        }
        else{
            return $val;
        }
    }
    protected function getAllAttribution()
    {
        $data = Cache::get('allAttribution');
        if($data === false){
            $allAttribution = new Attribution();
            $allAttribution->id = '';
            $allAttribution->name = '';
            $allAttribution->alias = '';
            $allAttribution->parent = '';
            $allAttribution->icon = '';
            $data = $allAttribution->select()->order('id ASC')->getSet();
            $data = Branch::line($data);
            Cache::set('allAttribution', $data);
        }
        return $data;
    }
    protected function terminalAttribution($id)
    {
        return $this->getTerminalAttribution($id, $this->getAllAttribution());
    }
    protected function getTerminalAttribution($id, $data)
    {
        $parentArr = array_unique(array_column($data, 'parent'));
        $start = false;
        $idArr = [];
        $startlen = 0;
        foreach($data as $key => $val){
            if(!$start){
                if($val['id'] != $id){
                    continue;
                }
                else{
                    $start = true;
                    if(!in_array($val['id'], $parentArr)){
                        $idArr[] = $val['id'];
                    }
                    $startlen = strlen($val['level']);
                }
            }
            else{
                if(strlen($val['level']) <= $startlen){
                    break;
                }
                else{
                    if(!in_array($val['id'], $parentArr)){
                        $idArr[] = $val['id'];
                    }
                }
            }
        }
        return $idArr;
    }
    protected function popular()
    {
        $yanzi = Cache::get('popular');
        if($yanzi === false){
            $content = new Content();
            $content->id = '';
            $content->aid = '';
            $content->keyword = '';
            $content->title = '';
            $content->alias = '';
            $content->summary = '';
            $content->content = '';
            $content->creation = '';
            $content->image = '';
            $content->view = '';
            $content->praise = '';
            $yanzi = $content->select('status = ?', 1)->order('view DESC')->limit($this->per)->getSet();
            $this->modifyList($yanzi, false);
            Cache::set('popular', $yanzi);
        }
        View::assign('popular', $yanzi);
    }
    protected function hot()
    {
        $yanzi = Cache::get('hot');
        if($yanzi === false){
            $content = new Content();
            $content->id = '';
            $content->aid = '';
            $content->keyword = '';
            $content->title = '';
            $content->alias = '';
            $content->summary = '';
            $content->content = '';
            $content->creation = '';
            $content->image = '';
            $content->view = '';
            $content->praise = '';
            $yanzi = $content->select('status = ?', 1)->order('comment DESC')->limit($this->per)->getSet();
            $this->modifyList($yanzi, false);
            Cache::set('hot', $yanzi);
        }
        View::assign('hot', $yanzi);
    }
    protected function fortemplate()
    {
        $yanzi = Cache::get('fortemplate');
        if($yanzi === false){
            $allAttribution = new Attribution();
            $allAttribution->id = '';
            $allAttribution->name = '';
            $allAttribution->alias = '';
            $allAttribution->parent = '';
            $allAttribution->fortemplate = '';
            $data = $allAttribution->select()->order('id ASC')->getSet();
            $data = Branch::line($data);
            $yanzi = [];
            $fortemplate = [];
            foreach($data as $val){
                if($val['fortemplate'] == 1){
                    $fortemplate[] = [
                        'id' => $val['id'],
                        'alias' => $val['alias']
                    ];
                }
            }
            if(count($fortemplate) > 0){
                $terminal = [];
                foreach($fortemplate as $val){
                    $terminal[$val['alias']] = $this->getTerminalAttribution($val['id'], $data);
                }
                foreach($terminal as $key => $val){
                    $mark = '';
                    foreach($val as $sval){
                        $mark .= empty($mark) ? '?' : ',?';
                    }
                    $val[] = 1;
                    $content = new Content();
                    $content->id = '';
                    $content->aid = '';
                    $content->keyword = '';
                    $content->title = '';
                    $content->alias = '';
                    $content->summary = '';
                    $content->content = '';
                    $content->creation = '';
                    $content->image = '';
                    $content->view = '';
                    $content->praise = '';
                    $yanzi[$key] = $content->select('aid IN ('.$mark.') AND status = ?', $val)->order('id DESC')->limit($this->per)->getSet();
                    $this->modifyList($yanzi[$key], false);
                }
            }
            Cache::set('fortemplate', $yanzi);
        }
        View::assign('yanzicms', $yanzi);
    }
    protected function advertising()
    {
        $yanzi = Cache::get('advertising');
        if($yanzi === false){
            $advertising = new Advertising();
            $advertising->alias = '';
            $advertising->image = '';
            $advertising->url = '';
            $advertising->code = '';
            $ad = $advertising->select()->getSet();
            $yanzi = [];
            foreach($ad as $key => $val){
                $yanzi[$val['alias']] = [
                    'image' => $val['image'],
                    'href' => $val['url'],
                    'code' => $val['code']
                ];
            }
            Cache::set('advertising', $yanzi);
        }
        View::assign('ad', $yanzi);
    }
    protected function subAttribution($id)
    {
        $data = $this->getAllAttribution();
        $subArr = [];
        foreach($data as $key => $val){
            if($val['parent'] == $id){
                if($val['alias'] != ''){
                    $href = Url::url('column', ['id' => $val['alias']]);
                }
                else{
                    $href = Url::url('column', ['id' => $val['id']]);
                }
                $subArr[] = [
                    'title' => $val['name'],
                    'href' => $href,
                    'items' => $val['id']
                ];
            }
        }
        foreach($subArr as $key => $val){
            $varr = $this->getTerminalAttribution($val['items'], $data);
            $mark = '';
            foreach($varr as $mval){
                $mark .= empty($mark) ? '?' : ',?';
            }
            $varr[] = 1;
            $content = new Content();
            $content->id = '';
            $content->aid = '';
            $content->keyword = '';
            $content->title = '';
            $content->alias = '';
            $content->summary = '';
            $content->content = '';
            $content->creation = '';
            $content->image = '';
            $content->view = '';
            $content->praise = '';
            $yanzi = $content->select('aid IN ('.$mark.') AND status = ?', $varr)->order('id DESC')->limit($this->per)->getSet();
            $this->modifyList($yanzi, false);
            $subArr[$key]['items'] = $yanzi;
            $content = null;
        }
        return $subArr;
    }
    public function columnrecommend($id, $mark, $idarr)
    {
        $yanzi = Cache::get('columnrecommend_' . $id);
        if($yanzi === false){
            $idarr[] = 1;
            $content = new Content();
            $content->id = '';
            $content->aid = '';
            $content->keyword = '';
            $content->title = '';
            $content->alias = '';
            $content->summary = '';
            $content->content = '';
            $content->creation = '';
            $content->image = '';
            $content->view = '';
            $content->praise = '';
            $yanzi = $content->select('aid IN ('.$mark.') AND status = ? AND recommend = ?', $idarr)->order('id DESC')->limit($this->per)->getSet();
            $this->modifyList($yanzi, false);
            $count = count($yanzi);
            if($count < $this->per){
                $fillContent = new Content();
                $fillContent->id = '';
                $fillContent->aid = '';
                $fillContent->keyword = '';
                $fillContent->title = '';
                $fillContent->alias = '';
                $fillContent->summary = '';
                $fillContent->content = '';
                $fillContent->creation = '';
                $fillContent->image = '';
                $fillContent->view = '';
                $fillContent->praise = '';
                $fillyanzi = $fillContent->select('aid IN ('.$mark.') AND status = ? AND recommend <> ?', $idarr)->order('view DESC')->limit($this->per - $count)->getSet();
                $this->modifyList($fillyanzi, false);
                foreach($fillyanzi as $val){
                    $yanzi[] = $val;
                }
                unset($fillyanzi);
            }
            Cache::set('columnrecommend_' . $id, $yanzi);
        }
        return $yanzi;
    }
    protected function columnlatest($id, $mark, $idarr)
    {
        $yanzi = Cache::get('columnlatest_' . $id);
        if($yanzi === false){
            $content = new Content();
            $content->id = '';
            $content->aid = '';
            $content->keyword = '';
            $content->title = '';
            $content->alias = '';
            $content->summary = '';
            $content->content = '';
            $content->creation = '';
            $content->image = '';
            $content->view = '';
            $content->praise = '';
            $yanzi = $content->select('aid IN ('.$mark.') AND status = ?', $idarr)->order('id DESC')->limit($this->per)->getSet();
            $this->modifyList($yanzi, false);
            Cache::set('columnlatest_' . $id, $yanzi);
        }
        return $yanzi;
    }
    protected function columnpopular($id, $mark, $idarr)
    {
        $yanzi = Cache::get('columnpopular_' . $id);
        if($yanzi === false){
            $content = new Content();
            $content->id = '';
            $content->aid = '';
            $content->keyword = '';
            $content->title = '';
            $content->alias = '';
            $content->summary = '';
            $content->content = '';
            $content->creation = '';
            $content->image = '';
            $content->view = '';
            $content->praise = '';
            $yanzi = $content->select('aid IN ('.$mark.') AND status = ?', $idarr)->order('view DESC')->limit($this->per)->getSet();
            $this->modifyList($yanzi, false);
            Cache::set('columnpopular_' . $id, $yanzi);
        }
        return $yanzi;
    }
    public function contentrecommend()
    {
        if(self::$attribution > 0){
            $yanzi = Cache::get('contentrecommend_' . self::$attribution);
            if($yanzi === false){
                $content = new Content();
                $content->id = '';
                $content->aid = '';
                $content->keyword = '';
                $content->title = '';
                $content->alias = '';
                $content->summary = '';
                $content->content = '';
                $content->creation = '';
                $content->image = '';
                $content->view = '';
                $content->praise = '';
                $yanzi = $content->select('aid = ? AND status = ? AND recommend = ?', [self::$attribution, 1, 1])->order('id DESC')->limit($this->per)->getSet();
                $this->modifyList($yanzi, false);
                $count = count($yanzi);
                if($count < $this->per){
                    $fillContent = new Content();
                    $fillContent->id = '';
                    $fillContent->aid = '';
                    $fillContent->keyword = '';
                    $fillContent->title = '';
                    $fillContent->alias = '';
                    $fillContent->summary = '';
                    $fillContent->content = '';
                    $fillContent->creation = '';
                    $fillContent->image = '';
                    $fillContent->view = '';
                    $fillContent->praise = '';
                    $fillyanzi = $fillContent->select('aid = ? AND status = ? AND recommend <> ?', [self::$attribution, 1, 1])->order('view DESC')->limit($this->per - $count)->getSet();
                    $this->modifyList($fillyanzi, false);
                    foreach($fillyanzi as $val){
                        $yanzi[] = $val;
                    }
                    unset($fillyanzi);
                }
                Cache::set('contentrecommend_' . self::$attribution, $yanzi);
            }
            return $yanzi;
        }
        else{
            return '';
        }
    }
    protected function contentlatest()
    {
        if(self::$attribution > 0){
            $yanzi = Cache::get('contentlatest_' . self::$attribution);
            if($yanzi === false){
                $content = new Content();
                $content->id = '';
                $content->aid = '';
                $content->keyword = '';
                $content->title = '';
                $content->alias = '';
                $content->summary = '';
                $content->content = '';
                $content->creation = '';
                $content->image = '';
                $content->view = '';
                $content->praise = '';
                $yanzi = $content->select('aid = ? AND status = ?', [self::$attribution, 1])->order('id DESC')->limit($this->per)->getSet();
                $this->modifyList($yanzi, false);
                Cache::set('contentlatest_' . self::$attribution, $yanzi);
            }
            return $yanzi;
        }
        else{
            return '';
        }
    }
    protected function contentpopular()
    {
        if(self::$attribution > 0){
            $yanzi = Cache::get('contentpopular_' . self::$attribution);
            if($yanzi === false){
                $content = new Content();
                $content->id = '';
                $content->aid = '';
                $content->keyword = '';
                $content->title = '';
                $content->alias = '';
                $content->summary = '';
                $content->content = '';
                $content->creation = '';
                $content->image = '';
                $content->view = '';
                $content->praise = '';
                $yanzi = $content->select('aid = ? AND status = ?', [self::$attribution, 1])->order('view DESC')->limit($this->per)->getSet();
                $this->modifyList($yanzi, false);
                Cache::set('contentpopular_' . self::$attribution, $yanzi);
            }
            return $yanzi;
        }
        else{
            return '';
        }
    }
    private function partMenu($alias, $self = true)
    {
        $menu = $this->getAllAttribution();
        $levelen = 0;
        $start = false;
        $sub = [];
        $parent = 0;
        foreach($menu as $key => $val){
            if($start == true){
                if(strlen($val['level']) > $levelen){
                    if($self === false || $self == 'false'){
                        if($val['parent'] == $parent){
                            $val['parent'] = 0;
                        }
                    }
                    if($val['alias'] != ''){
                        $val['href'] = Url::url('column', ['id' => $val['alias']]);
                    }
                    else{
                        $val['href'] = Url::url('column', ['id' => $val['id']]);
                    }
                    if($val['id'] == self::$attribution){
                        $val['active'] = 1;
                    }
                    else{
                        $val['active'] = 0;
                    }
                    unset($val['level']);
                    $sub[] = $val;
                    continue;
                }
                else{
                    break;
                }
            }
            if($val['alias'] == $alias){
                $levelen = strlen($val['level']);
                $start = true;
                if($self === true || $self == 'true'){
                    $val['parent'] = 0;
                    unset($val['level']);
                    if($val['alias'] != ''){
                        $val['href'] = Url::url('column', ['id' => $val['alias']]);
                    }
                    else{
                        $val['href'] = Url::url('column', ['id' => $val['id']]);
                    }
                    if($val['id'] == self::$attribution){
                        $val['active'] = 1;
                    }
                    else{
                        $val['active'] = 0;
                    }
                    $sub[] = $val;
                }
                else{
                    $parent = $val['id'];
                }
            }
        }
        $sub = Branch::grade($sub);
        return $sub;
    }
}