<?php
/**
 * Project: Yanzicms
 * Producer: Yanzicms [ http://www.Yanzicms.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.Yanzicms.com All rights reserved.
 */
namespace app\index;
use extend\encrypt\Encrypt;
use extend\plugin\Plugin;
use model\Attribution;
use model\Comment;
use model\Content;
use model\Favorites;
use model\Message;
use model\Models;
use model\User;
use swuuws\Cache;
use swuuws\Cookie;
use swuuws\Date;
use swuuws\Debug;
use swuuws\Lang;
use swuuws\Mod;
use swuuws\Model;
use swuuws\Request;
use swuuws\Response;
use swuuws\Session;
use swuuws\Url;
use swuuws\V;
use swuuws\Validate;
use swuuws\View;
class Index extends Yanzicms
{
    public function index()
    {
        $this->start();
        $page = 1;
        if(Request::hasGet('page')){
            $page = Request::getGet('page');
        }
        $yanzi = Cache::get('index_' . $page);
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
            $user = new User();
            $user->nickname = '';
            $user->avatar = '';
            $attribution = new Attribution();
            $attribution->name = 'belong';
            $attribution->alias = 'belongalias';
            $yanzi = $content->select('status = ?', 1)->order('id DESC')->join(
                $user->select()->equal('id', 'uid')
            )->join(
                $attribution->select()->equal('id', 'aid')
            )->paginate($this->per);
            $this->modifyList($yanzi['items']);
            Cache::set('index_' . $page, $yanzi)->group('index');
        }
        Plugin::adds('home', $yanzi);
        View::assign('yanzi', $yanzi);
        return $this->view('index');
    }
    public function login()
    {
        if(Session::has('id') && Session::has('usertype')){
            $usertype = Session::get('usertype');
            if($usertype < 10){
                Url::to('admin/index');
            }
            else{
                Url::to('user-center/index');
            }
        }
        Validate::isPost()->postValue('username')->rule(V::must())->ifError(Lang::lang('User name must be filled'))->postValue('pwd')->rule(V::must())->ifError(Lang::lang('Password must be filled'))->hasPostValue('captcha')->rule(V::must())->ifError(Lang::lang('Captcha must be filled'))->rule(V::captcha())->ifError(Lang::lang('Captcha error'))->success(function($data){
            $user = new User();
            $user->id = '';
            $user->name = '';
            $user->password = '';
            $user->status = '';
            $user->usertype = '';
            $user->select('name = ?', [trim($data['username'])])->limit(1)->getOne(function($data){
                if(count($data) < 1){
                    Response::writeEnd(Lang::lang('The user does not exist'));
                }
                elseif($data['status'] != 1){
                    Response::writeEnd(Lang::lang('The user is disabled, please contact the administrator'));
                }
                elseif(!Encrypt::verify(Request::getPost('pwd'), $data['password'])){
                    Response::writeEnd(Lang::lang('Wrong password'));
                }
                else{
                    Plugin::adds('login', $data);
                    Session::set('id', $data['id']);
                    Session::set('name', $data['name']);
                    Session::set('usertype', $data['usertype']);
                    if(Request::hasPost('remember') && strtolower(Request::getPost('remember')) == 'on'){
                        Cookie::set('id', $data['id'], 604800);
                        Cookie::set('name', $data['name'], 604800);
                        Cookie::set('secret', Encrypt::hash($data['name'] . $data['password']), 604800);
                    }
                    $logined = new User();
                    $logined->login = Date::now();
                    $logined->ip = Request::ip();
                    $logined->update('id = ?', $data['id']);
                    Response::writeEnd('ok');
                }
            });
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
        $this->start();
        $view = $this->view('login');
        Plugin::adds('loginPage', $view);
        if($view === false){
            return View::view();
        }
        return $view;
    }
    public function logout()
    {
        Session::delete('id');
        Session::delete('name');
        Session::delete('usertype');
        Cookie::delete('id');
        Cookie::delete('name');
        Cookie::delete('secret');
        Url::to('/');
    }
    public function missed()
    {
        header("HTTP/1.1 404 Not Found");
        $this->start(false);
        $view = $this->view('404');
        if($view === false){
            return View::view();
        }
        return $view;
    }
    public function signup()
    {
        Validate::isPost()->postValue('username')->rule(V::must())->ifError(Lang::lang('User name must be filled'))->rule(V::startLetterNumberHyphenUnder())->ifError(Lang::lang('The user name can only use letters, numbers, underscores and connecting lines, and start with a letter'))
            ->postValue('pwd')->rule(V::must())->ifError(Lang::lang('Password must be filled'))->rule(V::minlen(), 8)->ifError(Lang::lang('Password length cannot be less than 8 digits'))
            ->postValue('repwd')->rule(V::must())->ifError(Lang::lang('Repeat password must be filled'))->rule(V::equal(), V::getValue('pwd'))->ifError(Lang::lang('The password must be equal to the repeated password'))
            ->postValue('email')->rule(V::must())->ifError(Lang::lang('Email must be filled'))->rule(V::email())->ifError(Lang::lang('Email format error'))
            ->success(function($data){
                $data['username'] = trim($data['username']);
                $user = new User();
                $user->id = '';
                $user->select('name = ?', $data['username'])->limit(1)->getOne(function($rdata) use($data){
                    if(count($rdata) > 0){
                        Response::writeEnd(Lang::lang('User already exists, please register with another name'));
                    }
                    else{
                        Plugin::adds('signup', $data);
                        $newUser = new User();
                        $newUser->name = $data['username'];
                        $newUser->nickname = 'yanzi_' . rand();
                        $newUser->password = Encrypt::hash($data['pwd']);
                        $newUser->email = $data['email'];
                        $newUser->creation = Date::now();
                        $newUser->insert();
                        Response::writeEnd('ok');
                    }
                });
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
        $this->start();
        $view = $this->view('signup');
        Plugin::adds('signupPage', $view);
        if($view === false){
            return View::view();
        }
        return $view;
    }
    public function column($id)
    {
        $this->start();
        $page = 1;
        if(Request::hasGet('page')){
            $page = Request::getGet('page');
        }
        $yanzi = Cache::get('column_' . $id . '_' . $page);
        if($yanzi === false){
            if(!is_numeric($id)){
                $column = new Attribution();
                $column->id = '';
                $column->template = '';
                $re = $column->select('alias = ?', $id)->limit(1)->getOne();
                $aid = $re['id'];
                $template = $re['template'];
            }
            else{
                $aid = $id;
                $column = new Attribution();
                $column->template = '';
                $re = $column->select('id = ?', $id)->limit(1)->getOne();
                $template = $re['template'];
            }
            $idarr = $this->terminalAttribution($aid);
            $mark = '';
            foreach($idarr as $val){
                $mark .= empty($mark) ? '?' : ',?';
            }
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
            $user = new User();
            $user->nickname = '';
            $user->avatar = '';
            $yanzi = $content->select('aid IN ('.$mark.') AND status = ?', $idarr)->order('id DESC')->join(
                $user->select()->equal('id', 'uid')
            )->paginate($this->per);
            $this->modifyList($yanzi['items'], false);
            $yanzi['template'] = $template;
            $yanzi['active'] = $aid;
            $yanzi['subcolumn'] = $this->subAttribution($aid);
            $yanzi['recommend'] = $this->columnrecommend($aid, $mark, $idarr);
            $yanzi['latest'] = $this->columnlatest($aid, $mark, $idarr);
            $yanzi['popular'] = $this->columnpopular($aid, $mark, $idarr);
            Cache::set('column_' . $id . '_' . $page, $yanzi)->group('column');
        }
        self::$attribution = $yanzi['active'];
        unset($yanzi['active']);
        if(count($yanzi['subcolumn']) > 0){
            $yanzi['hassubcolumn'] = 1;
        }
        else{
            $yanzi['hassubcolumn'] = 0;
        }
        Plugin::adds('column', $yanzi);
        $template = 'column';
        if(!empty($yanzi['template'])){
            $template = 'column' . DS . $yanzi['template'];
        }
        unset($yanzi['template']);
        View::assign('yanzi', $yanzi);
        return $this->view($template, 'column');
    }
    public function keyword($word)
    {
        $this->start();
        $word = urldecode($word);
        $page = 1;
        if(Request::hasGet('page')){
            $page = Request::getGet('page');
        }
        $yanzi = Cache::get('keyword_' . $word . '_' . $page);
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
            $user = new User();
            $user->nickname = '';
            $user->avatar = '';
            $attribution = new Attribution();
            $attribution->name = 'belong';
            $attribution->alias = 'belongalias';
            $yanzi = $content->select('keyword LIKE ? AND status = ?', ['%'.$word.'%', 1])->order('id DESC')->join(
                $user->select()->equal('id', 'uid')
            )->join(
                $attribution->select()->equal('id', 'aid')
            )->paginate($this->per);
            $this->modifyList($yanzi['items']);
            Cache::set('keyword_' . $word . '_' . $page, $yanzi)->group('keyword');
        }
        Plugin::adds('keyword', $yanzi);
        View::assign('yanzi', $yanzi);
        $bread = [
            'title' => $word,
            'icon' => '',
            'href' => '#!',
        ];
        return $this->view('keyword', '', '', $bread);
    }
    public function content($id)
    {
        $this->start();
        $yanzi = Cache::get('content_' . $id);
        if($yanzi === false){
            if(!is_numeric($id)){
                $content = new Content();
                $content->id = '';
                $re = $content->select('alias = ?', $id)->limit(1)->getOne();
                $cid = $re['id'];
            }
            else{
                $cid = $id;
            }
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
            $content->template = '';
            $content->view = '';
            $content->praise = '';
            $content->favorites = '';
            $content->comment = '';
            $content->model = '';
            $user = new User();
            $user->nickname = '';
            $user->avatar = '';
            $attribution = new Attribution();
            $attribution->name = 'belong';
            $attribution->alias = 'belongalias';
            $data = $content->select('id = ? AND status = ?', [$cid, 1])->limit(1)->join(
                $user->select()->equal('id', 'uid')
            )->join(
                $attribution->select()->equal('id', 'aid')
            )->getOne();
            if(!empty($data)){
                $models = new Models();
                $models->alias = '';
                $models->parts = '';
                $model = $models->select('id = ?', $data['model'])->limit(1)->getOne();
                $parts = json_decode($model['parts'], true);
                $palias = array_column($parts, 'partalias');
                $parttype = array_column($parts, 'parttype');
                $mclass = 'model\\'.ucfirst($model['alias']);
                $mobj = new $mclass();
                foreach($palias as $key => $val){
                    $mobj->$val = '';
                }
                $mre = $mobj->select('cid = ?', $cid)->limit(1)->getOne();
                foreach($mre as $key => $val){
                    $type = array_shift($parttype);
                    $data[$key] = $this->conversion($val, $type);
                }
                $this->modifyContent($data);
                $yanzi['template'] = $model['alias'];
            }
            else{
                $data = '';
            }
            $yanzi['content'] = $data;
            $prevcontent = new Content();
            $prevcontent->id = '';
            $prevcontent->aid = '';
            $prevcontent->keyword = '';
            $prevcontent->title = '';
            $prevcontent->alias = '';
            $prevcontent->summary = '';
            $prevcontent->content = '';
            $prevcontent->creation = '';
            $prevcontent->image = '';
            $prevcontent->view = '';
            $prevcontent->praise = '';
            $pdata = $prevcontent->select('id < ?', $cid)->order('id DESC')->limit(1)->getOne();
            if(!empty($pdata)){
                $this->modifyContent($pdata, false);
            }
            else{
                $pdata = '';
            }
            $yanzi['prev'] = $pdata;
            $nextcontent = new Content();
            $nextcontent->id = '';
            $nextcontent->aid = '';
            $nextcontent->keyword = '';
            $nextcontent->title = '';
            $nextcontent->alias = '';
            $nextcontent->summary = '';
            $nextcontent->content = '';
            $nextcontent->creation = '';
            $nextcontent->image = '';
            $nextcontent->view = '';
            $nextcontent->praise = '';
            $ndata = $nextcontent->select('id > ?', $cid)->order('id ASC')->limit(1)->getOne();
            if(!empty($ndata)){
                $this->modifyContent($ndata, false);
            }
            else{
                $ndata = '';
            }
            $yanzi['next'] = $ndata;
            Cache::set('content_' . $id, $yanzi)->group('content');
        }
        $bread = '';
        if(!empty($yanzi['content'])){
            $viewContent = new Content();
            $viewContent->view = ['+', 1];
            $viewContent->update('id = ?', $yanzi['content']['id']);
            $page = 1;
            if(Request::hasGet('page')){
                $page = Request::getGet('page');
            }
            $comments = Cache::get('comment_' . $id . '_' . $page);
            if($comments === false){
                $comment = new Comment();
                $comment->id = '';
                $comment->comment = '';
                $comment->creation = '';
                $comment->parent = '';
                $cuser = new User();
                $cuser->nickname = '';
                $cuser->avatar = '';
                $comments = $comment->select('cid = ? AND status = ?', [$yanzi['content']['id'], 1])->join(
                    $cuser->select()->equal('id', 'uid')
                )->paginate($this->per);
                $parent = '';
                foreach($comments['items'] as $key => $val){
                    if($val['parent'] > 0){
                        $parent .= empty($parent) ? $val['parent'] : ',' . $val['parent'];
                    }
                    $comments['items'][$key]['creation'] = $this->getTime($val['creation']);
                    $comments['items'][$key]['comment'] = nl2br($val['comment']);
                }
                $parr = [];
                if(!empty($parent)){
                    $pcomment = new Comment();
                    $pcomment->id = '';
                    $pcomment->comment = '';
                    $pcomment->creation = '';
                    $puser = new User();
                    $puser->nickname = '';
                    $puser->avatar = '';
                    $in = '';
                    $parentArr = explode(',', $parent);
                    foreach($parentArr as $val){
                        $in .= empty($in) ? '?' : ',?';
                    }
                    $parentArr[] = 1;
                    $pcomments = $pcomment->select('id IN ('.$in.') AND status = ?', $parentArr)->join(
                        $puser->select()->equal('id', 'uid')
                    )->getSet();
                    foreach($pcomments as $pkey => $pval){
                        $parr[$pval['id']] = [
                            'id' => $pval['id'],
                            'comment' => nl2br($pval['comment']),
                            'creation' => $this->getTime($pval['creation']),
                            'nickname' => $pval['nickname'],
                            'avatar' => $pval['avatar'],
                        ];
                    }
                }
                foreach($comments['items'] as $key => $val){
                    if($val['parent'] > 0 && isset($parr[$val['parent']])){
                        $comments['items'][$key]['parent'] = $parr[$val['parent']];
                    }
                    else{
                        $comments['items'][$key]['parent'] = '';
                    }
                }
                Cache::set('comment_' . $id . '_' . $page, $comments)->group('comment_' . $yanzi['content']['id']);
            }
            $yanzi['comment'] = $comments;
            self::$attribution = $yanzi['content']['aid'];
            unset($yanzi['content']['aid']);
            self::$pageid = $yanzi['content']['id'];
            self::$pagealias = $yanzi['content']['alias'];
            $bread = [
                'title' => $yanzi['content']['title'],
                'icon' => '',
                'href' => $yanzi['content']['href'],
            ];
        }
        $yanzi['recommend'] = $this->contentrecommend();
        $yanzi['latest'] = $this->contentlatest();
        $yanzi['popular'] = $this->contentpopular();
        Plugin::adds('content', $yanzi);
        if(isset($yanzi['template'])){
            $modeltemp = 'model' . DS . $yanzi['template'];
            unset($yanzi['template']);
        }
        else{
            $modeltemp = '';
        }
        $template = 'content';
        if(isset($yanzi['content']['template'])){
            if(!empty($yanzi['content']['template'])){
                $template = 'content' . DS . $yanzi['content']['template'];
            }
            unset($yanzi['content']['template']);
        }
        View::assign('yanzi', $yanzi);
        return $this->view($template, 'content', $modeltemp, $bread);
    }
    public function comment()
    {
        Validate::isPost()->postValue('comment')->rule(V::must())->ifError(Lang::lang('Comment content cannot be empty'))
            ->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
                if(!Session::has('id')){
                    Response::writeEnd(Lang::lang('You must log in to comment'));
                }
                $reply = 0;
                if(Request::hasPost('reply')){
                    $reply = intval(Request::getPost('reply'));
                }
                Plugin::adds('comment', $data);
                $now = Date::now();
                $mycomment = new Comment();
                $mycomment->uid = Session::get('id');
                $mycomment->cid = $data['id'];
                $mycomment->comment = $data['comment'];
                $mycomment->creation = $now;
                $mycomment->modify = $now;
                $mycomment->parent = $reply;
                $mycomment->insert();
                $content = new Content();
                $content->comment = ['+', 1];
                $content->update('id = ?', $data['id']);
                Cache::delGroup('comment_' . $data['id']);
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    public function message()
    {
        Validate::isPost()->postValue('message')->rule(V::must())->ifError(Lang::lang('Message cannot be empty'))->postValue('email')->rule(V::email())->ifError(Lang::lang('Incorrect email format'))->success(function($data){
            $uid = 0;
            if(Session::has('id')){
                $uid = Session::get('id');
            }
            Plugin::adds('message', $data);
            $addmessage = new Message();
            $addmessage->uid = $uid;
            $addmessage->name = Request::getPost('name');
            $addmessage->phone = Request::getPost('phone');
            $addmessage->email = $data['email'];
            $addmessage->other = Request::getPost('other');
            $addmessage->message = $data['message'];
            $addmessage->creation = Date::now();
            $addmessage->insert();
            Response::writeEnd('ok');
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
        $page = 1;
        if(Request::hasGet('page')){
            $page = Request::getGet('page');
        }
        $messages = Cache::get('message_' . $page);
        if($messages === false){
            $message = new Message();
            $message->name = '';
            $message->message = '';
            $message->creation = '';
            $messages = $message->select()->order('id DESC')->paginate($this->per);
            foreach($messages['items'] as $key => $val){
                $messages['items'][$key]['name'] = substr($val['name'], 0, 1) . '***';
                $messages['items'][$key]['message'] = nl2br($val['message']);
                $messages['items'][$key]['creation'] = $this->getTime($val['creation']);
            }
            Cache::set('message_' . $page, $messages)->group('message');
        }
        View::assign('message', $messages);
        $this->start();
        $bread = [
            'title' => Lang::lang('Message'),
            'icon' => '',
            'href' => '#!',
        ];
        return $this->view('message', '', '', $bread);
    }
    public function favorites()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $alfavorites = new Favorites();
            $alfavorites->id = '';
            $alfavorites->select('cid = ? AND uid = ?', [$data['id'], Session::get('id')])->limit(1)->getOne(function($adata) use ($data){
                if(count($adata) == 0){
                    $re = Mod::transaction(function() use($data){
                        $favorites = new Favorites();
                        $favorites->uid = Session::get('id');
                        $favorites->cid = $data['id'];
                        $favorites->creation = Date::now();
                        $favorites->insert();
                        $content = new Content();
                        $content->favorites = ['+', 1];
                        $content->update('id = ?', $data['id']);
                    });
                    if($re === true){
                        Response::writeEnd(Lang::lang('Already favorited'));
                    }
                    else{
                        Response::writeEnd(Lang::lang('The operation failed, please try again later'));
                    }
                }
            });
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
    }
    public function likes()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $ckcontent = new Content();
            $ckcontent->id = '';
            $ckcontent->uid = '';
            $ckcontent->select('id = ?', $data['id'])->limit(1)->getOne(function($adata) use ($data){
                if($adata['uid'] == Session::get('id')){
                    Response::writeEnd(Lang::lang('You cannot like your content'));
                }
                else{
                    $content = new Content();
                    $content->praise = ['+', 1];
                    $content->update('id = ?', $data['id']);
                    Response::writeEnd(Lang::lang('Already liked'));
                }
            });
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
    }
    public function search()
    {
        $this->start();
        $word = urldecode(Request::getGet('word'));
        $page = 1;
        if(Request::hasGet('page')){
            $page = Request::getGet('page');
        }
        $yanzi = Cache::get('search_' . $word . '_' . $page);
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
            $user = new User();
            $user->nickname = '';
            $user->avatar = '';
            $attribution = new Attribution();
            $attribution->name = 'belong';
            $attribution->alias = 'belongalias';
            $yanzi = $content->select('(title LIKE ? OR summary LIKE ?) AND status = ?', ['%'.$word.'%', '%'.$word.'%', 1])->order('id DESC')->join(
                $user->select()->equal('id', 'uid')
            )->join(
                $attribution->select()->equal('id', 'aid')
            )->paginate($this->per);
            $this->modifyList($yanzi['items']);
            Cache::set('search_' . $word . '_' . $page, $yanzi)->group('search');
        }
        Plugin::adds('search', $yanzi);
        View::assign('yanzi', $yanzi);
        $bread = [
            'title' => Lang::lang('Search'),
            'icon' => '',
            'href' => '#!',
        ];
        return $this->view('search', '', '', $bread);
    }
}