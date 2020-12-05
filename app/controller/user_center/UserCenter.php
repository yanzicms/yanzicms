<?php
/**
 * Project: Yanzicms
 * Producer: Yanzicms [ http://www.Yanzicms.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.Yanzicms.com All rights reserved.
 */
namespace app\user_center;
use extend\croppic\Croppic;
use extend\encrypt\Encrypt;
use extend\info\Info;
use extend\plugin\Plugin;
use extend\yanzi\Yanzi;
use model\Comment;
use model\Content;
use model\Favorites;
use model\Message;
use model\Models;
use model\User;
use swuuws\Cache;
use swuuws\Date;
use swuuws\Debug;
use swuuws\Env;
use swuuws\File;
use swuuws\Image;
use swuuws\Lang;
use swuuws\Mod;
use swuuws\Request;
use swuuws\Response;
use swuuws\Session;
use swuuws\Url;
use swuuws\V;
use swuuws\Validate;
use swuuws\View;
class UserCenter extends Yanzicms
{
    public function index()
    {
        $this->start();
        return View::view();
    }
    public function personalInformation()
    {
        Validate::isPost()->postValue('nickname')->rule(V::must())->ifError(Lang::lang('Nickname must be filled in'))->success(function($data){
            $modifyUser = new User();
            $modifyUser->nickname = $data['nickname'];
            $modifyUser->email = Request::getPost('email');
            $modifyUser->url = Request::getPost('url');
            $modifyUser->gender = Request::getPost('gender');
            $modifyUser->birthday = Request::getPost('birthday');
            $modifyUser->signature = Request::getPost('signature');
            $modifyUser->update('id = ?', Session::get('id'));
            Response::writeEnd('ok');
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
        $this->start(Lang::lang('Personal information'));
        $user = new User();
        $user->nickname = '';
        $user->email = '';
        $user->url = '';
        $user->avatar = '';
        $user->gender = '';
        $user->birthday = '';
        $user->signature = '';
        $user->select('id = ?', Session::get('id'))->limit(1)->getOne(function($data){
            if($data['birthday'] == '1000-01-01'){
                $data['birthday'] = '';
            }
            View::assign('yanzi', $data);
        });
        View::assign('uploadavatar', Url::url('user_center/uploadavatar'));
        return View::view();
    }
    public function uploadavatar()
    {
        $imagePath = 'public' . DS . 'avatar' . DS . substr(md5(Session::get('id')), 0, 3);
        File::newFolder($imagePath);
        $croppic = new Croppic();
        $re = $croppic->crop($imagePath . DS . 'avatar_' . rand() . '.jpg');
        if($re['status'] == 'success'){
            $user = new User();
            $user->avatar = '';
            $avatar = ltrim($re['url'], 'public/');
            $user->select('id = ?', Session::get('id'))->limit(1)->getOne(function($data) use($avatar){
                $modifyUser = new User();
                $modifyUser->avatar = $avatar;
                $modifyUser->update('id = ?', Session::get('id'));
                $data['avatar'] = str_replace('..', '', $data['avatar']);
                if(!empty($data['avatar']) && substr($data['avatar'], 0, 7) == 'avatar/' && basename($avatar) != basename($data['avatar'])){
                    @unlink(ROOT . 'public' . DS . str_replace(['\\', '/'], DS, $data['avatar']));
                }
            });
            if(Env::get('C_WEB_ROOT') != ''){
                $re['url'] = ltrim($re['url'], 'public/');
            }
            $re['url'] = Info::getInfo('domain') . $re['url'];
        }
        Response::writeJson($re);
    }
    public function avatardel()
    {
        $user = new User();
        $user->avatar = '';
        $user->select('id = ?', Session::get('id'))->limit(1)->getOne(function($data){
            $modifyUser = new User();
            $modifyUser->avatar = '';
            $modifyUser->update('id = ?', Session::get('id'));
            $data['avatar'] = str_replace('..', '', $data['avatar']);
            if(!empty($data['avatar']) && substr($data['avatar'], 0, 7) == 'avatar/'){
                @unlink(ROOT . 'public' . DS . str_replace(['\\', '/'], DS, $data['avatar']));
            }
        });
        Response::writeEnd('ok');
    }
    public function changePassword()
    {
        Validate::isPost()->postValue('originalpassword')->rule(V::must())->ifError(Lang::lang('The original password must be filled in'))
            ->postValue('newpassword')->rule(V::must())->ifError(Lang::lang('The new password must be filled in'))->rule(V::minlen(), 8)->ifError(Lang::lang('The length of the new password must be at least 8 characters'))
            ->postValue('confirmpassword')->rule(V::must())->ifError(Lang::lang('The confirm password must be filled in'))->rule(V::equal(), Request::getPost('newpassword'))->ifError(Lang::lang('Confirm password must be the same as the new password'))->success(function($data){
            $user = new User();
            $user->password = '';
            $user->select('id = ?', Session::get('id'))->limit(1)->getOne(function($udata) use($data){
                if(!Encrypt::verify($data['originalpassword'], $udata['password'])){
                    Response::writeEnd(Lang::lang('The original password is wrong'));
                }
                else{
                    $modifyuser = new User();
                    $modifyuser->password = Encrypt::hash($data['newpassword']);
                    $modifyuser->update('id = ?', Session::get('id'));
                    Response::writeEnd('ok');
                }
            });
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
        $this->start(Lang::lang('Change password'));
        return View::view();
    }
    public function mycomment()
    {
        $this->start(Lang::lang('My comment'));
        $comment = new Comment();
        $comment->id = '';
        $comment->cid = '';
        $comment->comment = '';
        $comment->creation = '';
        $comment->status = '';
        $comments = $comment->select('uid = ?', Session::get('id'))->order('id DESC')->paginate($this->per);
        foreach($comments['items'] as $key => $val){
            $comments['items'][$key]['comment'] = nl2br($val['comment']);
            $comments['items'][$key]['content'] = Url::url('content', ['id' => $val['cid']]);
        }
        View::assign('data', $comments);
        return View::view();
    }
    public function mycommentdel()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $selfcomment = new Comment();
            $selfcomment->uid = '';
            $selfcomment->cid = '';
            $selfcomment->select('id = ?', $data['id'])->limit(1)->getOne(function($sdata) use($data){
                if($sdata['uid'] != Session::get('id')){
                    Response::writeEnd(Lang::lang('Illegal operation'));
                }
                else{
                    $comment = new Comment();
                    $comment->id = $data['id'];
                    $comment->delete();
                    $updatecomment = new Comment();
                    $updatecomment->parent = 0;
                    $updatecomment->update('parent = ?', $data['id']);
                    Cache::delGroup('comment_' . $sdata['cid']);
                    Response::writeEnd('ok');
                }
            });
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
    }
    public function mymessage()
    {
        $this->start(Lang::lang('My message'));
        $message = new Message();
        $message->id = '';
        $message->name = '';
        $message->message = '';
        $message->creation = '';
        $message->phone = '';
        $message->email = '';
        $message->other = '';
        $messages = $message->select('uid = ?', Session::get('id'))->order('id DESC')->paginate($this->per);
        foreach($messages['items'] as $key => $val){
            $messages['items'][$key]['message'] = nl2br($val['message']);
        }
        View::assign('data', $messages);
        return View::view();
    }
    public function mymessagedel()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $selfMessage = new Message();
            $selfMessage->uid = '';
            $selfMessage->select('id = ?', $data['id'])->limit(1)->getOne(function($sdata) use($data){
                if($sdata['uid'] != Session::get('id')){
                    Response::writeEnd(Lang::lang('Illegal operation'));
                }
                else{
                    $delMessage = new Message();
                    $delMessage->id = $data['id'];
                    $delMessage->delete();
                    $pmessage = new Message();
                    $pmessage->parent = $data['id'];
                    $pmessage->delete();
                    Response::writeEnd('ok');
                }
            });
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
    }
    public function explugin($plugin, $func, $name)
    {
        $this->start(urldecode($name));
        $param = [];
        Plugin::add($func, $plugin, $param);
        $view = '';
        if(isset($param['view'])){
            $view = $param['view'];
        }
        View::assign('pluginview', $view);
        return View::view();
    }
    public function extemplate($plugin, $func, $name)
    {
        $this->start(urldecode($name));
        $param = [];
        Plugin::add($func, 'template/' . $plugin, $param);
        $view = '';
        if(isset($param['view'])){
            $view = $param['view'];
        }
        View::assign('templateview', $view);
        return View::view();
    }
    public function mycollection()
    {
        $this->start(Lang::lang('My collection'));
        $favorites = new Favorites();
        $favorites->id = '';
        $favorites->cid = '';
        $favorites->creation = '';
        $content = new Content();
        $content->title = '';
        $content->alias = '';
        $data = $favorites->select('uid = ?', Session::get('id'))->order('id DESC')->join(
            $content->select()->equal('id', 'cid')
        )->paginate($this->per);
        foreach($data['items'] as $key => $val){
            if(!empty($val['alias'])){
                $data['items'][$key]['href'] = Url::url('content', ['id' => $val['alias']]);
            }
            else{
                $data['items'][$key]['href'] = Url::url('content', ['id' => $val['cid']]);
            }
        }
        View::assign('data', $data);
        return View::view();
    }
    public function mycollectiondel()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $selffavorites = new Favorites();
            $selffavorites->uid = '';
            $selffavorites->cid = '';
            $selffavorites->select('id = ?', $data['id'])->limit(1)->getOne(function($sdata) use($data){
                if($sdata['uid'] != Session::get('id')){
                    Response::writeEnd(Lang::lang('Illegal operation'));
                }
                else{
                    $re = Mod::transaction(function() use($data, $sdata){
                        $favorites = new Favorites();
                        $favorites->id = $data['id'];
                        $favorites->delete();
                        $content = new Content();
                        $content->favorites = ['-', 1];
                        $content->update('id = ?', $sdata['cid']);
                    });
                    if($re === true){
                        Response::writeEnd('ok');
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
    public function contribution()
    {
        Validate::isPost()
            ->postValue('selectmodel')->rule(V::must())->ifError(Lang::lang('The submission area must be selected'))
            ->postValue('attribution')->rule(V::must())->ifError(Lang::lang('Contribution column must be selected'))
            ->postValue('title')->rule(V::must())->ifError(Lang::lang('Title must be filled in'))
            ->postValue('content')->rule(V::must())->ifError(Lang::lang('Content must be filled in'))->success(function($data){
                $selectmodel = new Models();
                $selectmodel->id = '';
                $selectmodel->alias = '';
                $selectmodel->parts = '';
                $selectmodel->select('alias = ?', $data['selectmodel'])->limit(1)->getOne(function($sdata) use($data){
                    $parts = json_decode($sdata['parts'], true);
                    $now = Date::now();
                    $data['content'] = Yanzi::filter(Request::getPost('content', false));
                    Plugin::adds('contribution', $data);
                    $re = Mod::transaction(function() use($data, $sdata, $parts, $now){
                        $newcontent = new Content();
                        $newcontent->uid = Session::get('id');
                        $newcontent->aid = $data['attribution'];
                        $newcontent->keyword = str_replace('，', ',', Request::getPost('keyword'));
                        $newcontent->title = $data['title'];
                        $newcontent->summary = Request::getPost('summary');
                        $newcontent->content = $data['content'];
                        $newcontent->creation = $now;
                        $newcontent->modify = $now;
                        $newcontent->image = Request::getPost('image');
                        $newcontent->status = 2;
                        $newcontent->model = $sdata['id'];
                        $newcontent->contribution = 1;
                        $cid = $newcontent->insert();
                        $aliasclass = 'model\\'.ucfirst($sdata['alias']);
                        $newmodel = new $aliasclass();
                        $newmodel->cid = $cid;
                        foreach($parts as $key => $val){
                            if($val['parttype'] == 'int'){
                                $newmodel->{$val['partalias']} = intval(Request::getPost($val['partalias']));
                            }
                            elseif($val['parttype'] == 'decimal'){
                                $newmodel->{$val['partalias']} = round(floatval(Request::getPost($val['partalias'])), 2);
                            }
                            elseif($val['parttype'] == 'date'){
                                $pdate = Request::getPost($val['partalias']);
                                if(empty($pdate)){
                                    $pdate = '1000-01-01';
                                }
                                $newmodel->{$val['partalias']} = $pdate;
                            }
                            elseif($val['parttype'] == 'datetime'){
                                $pdatetime = Request::getPost($val['partalias']);
                                if(empty($pdatetime)){
                                    $pdatetime = '1000-01-01 00:00:00';
                                }
                                $newmodel->{$val['partalias']} = $pdatetime;
                            }
                            elseif($val['parttype'] == 'multiplechoice'){
                                $partalias = Request::getPost($val['partalias']);
                                if(is_array($partalias) && count($partalias) > 0){
                                    $newmodel->{$val['partalias']} = implode(',', $partalias);
                                }
                                else{
                                    if(empty($partalias)){
                                        $partalias = '';
                                    }
                                    $newmodel->{$val['partalias']} = $partalias;
                                }
                            }
                            else{
                                $newmodel->{$val['partalias']} = Request::getPost($val['partalias']);
                            }
                        }
                        $newmodel->insert();
                    });
                    Plugin::adds('afterContribution', $cid);
                    if($re === true){
                        Response::writeEnd('ok');
                    }
                    else{
                        Response::writeEnd(Lang::lang('The operation failed, please try again later'));
                    }
                });
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
        $this->start(Lang::lang('Contribution'));
        $models = new Models();
        $models->name = '';
        $models->alias = '';
        $models->parts = '';
        View::assign('models', $models->select('contribution = ?', 1)->getSet());
        $this->getAllAttribution();
        return View::view();
    }
    public function contributionmodify()
    {
        Validate::isPost()
            ->postValue('attribution')->rule(V::must())->ifError(Lang::lang('Contribution column must be selected'))
            ->postValue('title')->rule(V::must())->ifError(Lang::lang('Title must be filled in'))
            ->postValue('content')->rule(V::must())->ifError(Lang::lang('Content must be filled in'))->success(function($data){
                $cid = Request::getPost('cid');
                $selfcontent = new Content();
                $selfcontent->uid = '';
                $selfcontent->model = '';
                $selfcontent->select('id = ?', $cid)->limit(1)->getOne(function($sdata) use($data, $cid){
                    if($sdata['uid'] != Session::get('id')){
                        Response::writeEnd(Lang::lang('Illegal operation'));
                    }
                    $selectmodel = new Models();
                    $selectmodel->id = '';
                    $selectmodel->alias = '';
                    $selectmodel->parts = '';
                    $selectmodel->select('id = ?', $sdata['model'])->limit(1)->getOne(function($mdata) use($data, $cid, $sdata){
                        $parts = json_decode($mdata['parts'], true);
                        $now = Date::now();
                        $data['content'] = Yanzi::filter(Request::getPost('content', false));
                        Plugin::adds('modifyContribution', $data);
                        $re = Mod::transaction(function() use($data, $cid, $sdata, $mdata, $parts, $now){
                            $modifycontent = new Content();
                            $modifycontent->aid = $data['attribution'];
                            $modifycontent->keyword = str_replace('，', ',', Request::getPost('keyword'));
                            $modifycontent->title = $data['title'];
                            $modifycontent->summary = Request::getPost('summary');
                            $modifycontent->content = $data['content'];
                            $modifycontent->modify = $now;
                            $modifycontent->image = Request::getPost('image');
                            $modifycontent->status = 2;
                            $modifycontent->update('id = ?', $cid);
                            $modifyaliasclass = 'model\\'.ucfirst($mdata['alias']);
                            $newmodel = new $modifyaliasclass();
                            foreach($parts as $key => $val){
                                if($val['parttype'] == 'int'){
                                    $newmodel->{$val['partalias']} = intval(Request::getPost($val['partalias']));
                                }
                                elseif($val['parttype'] == 'decimal'){
                                    $newmodel->{$val['partalias']} = round(floatval(Request::getPost($val['partalias'])), 2);
                                }
                                elseif($val['parttype'] == 'date'){
                                    $pdate = Request::getPost($val['partalias']);
                                    if(empty($pdate)){
                                        $pdate = '1000-01-01';
                                    }
                                    $newmodel->{$val['partalias']} = $pdate;
                                }
                                elseif($val['parttype'] == 'datetime'){
                                    $pdatetime = Request::getPost($val['partalias']);
                                    if(empty($pdatetime)){
                                        $pdatetime = '1000-01-01 00:00:00';
                                    }
                                    $newmodel->{$val['partalias']} = $pdatetime;
                                }
                                elseif($val['parttype'] == 'multiplechoice'){
                                    $partalias = Request::getPost($val['partalias']);
                                    if(is_array($partalias) && count($partalias) > 0){
                                        $newmodel->{$val['partalias']} = implode(',', $partalias);
                                    }
                                    else{
                                        if(empty($partalias)){
                                            $partalias = '';
                                        }
                                        $newmodel->{$val['partalias']} = $partalias;
                                    }
                                }
                                else{
                                    $newmodel->{$val['partalias']} = Request::getPost($val['partalias']);
                                }
                            }
                            $newmodel->update('cid = ?', $cid);
                        });
                        Plugin::adds('afterModifyContribution', $cid);
                        if($re === true){
                            Response::writeEnd('ok');
                        }
                        else{
                            Response::writeEnd(Lang::lang('The operation failed, please try again later'));
                        }
                    });
                });
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
        $this->start(Lang::lang('Edit submission'));
        $content = new Content();
        $content->id = '';
        $content->uid = '';
        $content->aid = '';
        $content->keyword = '';
        $content->title = '';
        $content->summary = '';
        $content->content = '';
        $content->image = '';
        $content->model = '';
        $content->select('id = ?', Request::getGet('id'))->limit(1)->getOne(function($data){
            if(!isset($data['uid']) || $data['uid'] != Session::get('id')){
                View::assign('errorinfo', Lang::lang('Illegal operation'));
            }
            else{
                View::assign('errorinfo', '');
                $models = new Models();
                $models->alias = '';
                $models->parts = '';
                $models->select('id = ?', $data['model'])->limit(1)->getOne(function($mdata) use($data){
                    $data['parts'] = $mdata['parts'];
                    View::assign('data', $data);
                    $parts = json_decode($mdata['parts'], true);
                    $palias = array_column($parts, 'partalias');
                    $aliasclass = 'model\\'.ucfirst($mdata['alias']);
                    $cmodel = new $aliasclass();
                    foreach($palias as $key => $val){
                        $cmodel->$val = '';
                    }
                    $cmodel->select('cid = ?', $data['id'])->limit(1)->getOne(function($cdata){
                        $cmdata = [];
                        foreach($cdata as $key => $val){
                            $cmdata[] = [
                                'name' => $key,
                                'content' => $val
                            ];
                        }
                        View::assign('modelc', $cmdata);
                    });
                    $this->getAllAttribution($mdata['alias']);
                });
            }
        });
        return View::view();
    }
    public function mysubmissionbox()
    {
        $this->start(Lang::lang('My submission box'));
        $content = new Content();
        $content->id = '';
        $content->title = '';
        $content->alias = '';
        $content->creation = '';
        $content->image = '';
        $content->status = '';
        $content->view = '';
        $data = $content->select('uid = ? AND contribution = ?', [Session::get('id'), 1])->order('id DESC')->paginate($this->per);
        foreach($data['items'] as $key => $val){
            if($val['status'] == 0){
                $data['items'][$key]['statedescription'] = Lang::lang('Did not pass');
            }
            elseif($val['status'] == 1){
                $data['items'][$key]['statedescription'] = Lang::lang('Normal');
            }
            else{
                $data['items'][$key]['statedescription'] = Lang::lang('Under review');
            }
            if(!empty($val['alias'])){
                $data['items'][$key]['href'] = Url::url('content', ['id' => $val['alias']]);
            }
            else{
                $data['items'][$key]['href'] = Url::url('content', ['id' => $val['id']]);
            }
        }
        View::assign('data', $data);
        return View::view();
    }
    public function mysubmissionboxdel()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $selfcontent = new Content();
            $selfcontent->uid = '';
            $selfcontent->content = '';
            $selfcontent->image = '';
            $selfcontent->model = '';
            $selfcontent->select('id = ?', $data['id'])->limit(1)->getOne(function($sdata) use($data){
                if($sdata['uid'] != Session::get('id')){
                    Response::writeEnd(Lang::lang('Illegal operation'));
                }
                else{
                    $model = new Models();
                    $model->alias = '';
                    $model->parts = '';
                    $mdata = $model->select('id = ?', $sdata['model'])->limit(1)->getOne();
                    $param = $sdata;
                    $param['modelsAlias'] = $mdata['alias'];
                    $param['modelsParts'] = $mdata['parts'];
                    Plugin::adds('beforeDeletContribution', $param);
                    $re = Mod::transaction(function() use($data, $sdata, $mdata){
                        $delcontent = new Content();
                        $delcontent->id = $data['id'];
                        $delcontent->delete();
                        $aliasclass = 'model\\'.ucfirst($mdata['alias']);
                        $delmodel = new $aliasclass();
                        $delmodel->cid = $data['id'];
                        $delmodel->delete();
                        $delcomment = new Comment();
                        $delcomment->cid = $data['id'];
                        $delcomment->delete();
                        $delfavorites = new Favorites();
                        $delfavorites->cid = $data['id'];
                        $delfavorites->delete();
                    });
                    if($re === true){
                        Plugin::adds('afterDeletContribution', $param);
                        Response::writeEnd('ok');
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
    public function uploadfile()
    {
        if(Request::hasPost('fullpath')){
            $fullpath = Request::getPost('fullpath');
        }
        $filePath = File::get('file')->checkExt(Request::getPost('allow'))->ifSaved()->getUploaded();
        $param = [
            'file' => $filePath
        ];
        Plugin::adds('uploadFile', $param);
        if(isset($fullpath) && $fullpath == 1){
            if(Env::get('C_WEB_ROOT') != ''){
                $filePath = ltrim($filePath, 'public/');
            }
            Response::writeEnd(Info::getInfo('domain') . $filePath);
        }
        else{
            Response::writeEnd(ltrim($filePath, 'public/'));
        }
    }
    public function uploadfiledel()
    {
        $fileName = str_replace('..', '', Request::getPost('filename'));
        if(substr($fileName, 0, 5) == 'data/'){
            @unlink(ROOT . 'public' . DS . str_replace('/', DS, $fileName));
            Response::writeEnd('ok');
        }
        else{
            Response::writeEnd(Lang::lang('The operation failed, please try again later'));
        }
    }
}