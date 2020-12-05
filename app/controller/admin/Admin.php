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
use extend\yanzi\Yanzi;
use model\Advertising;
use model\Attribution;
use model\Comment;
use model\Content;
use model\Dispose;
use model\Favorites;
use model\Link;
use model\Message;
use model\Models;
use model\Slidegroup;
use model\Slideshow;
use model\User;
use swuuws\Cache;
use swuuws\Date;
use swuuws\Debug;
use swuuws\Env;
use swuuws\File;
use swuuws\Hook;
use swuuws\Image;
use swuuws\Lang;
use swuuws\M;
use swuuws\Mod;
use swuuws\Model;
use swuuws\Request;
use swuuws\Response;
use swuuws\Session;
use swuuws\Url;
use swuuws\V;
use swuuws\Validate;
use swuuws\View;
class Admin extends Yanzicms
{
    public function index()
    {
        $space = Cache::get('admin_space');
        if($space === false){
            if(function_exists('disk_free_space')){
                $space = round(disk_free_space(ROOT) / (1024 * 1024));
            }
            else{
                $space = Lang::lang('Unknown');
            }
            Cache::set('admin_space', $space);
        }
        $contribution = Cache::get('admin_contribution');
        if($contribution === false){
            $content = new Content();
            $content->id = '';
            $cdata = $content->select('contribution = ? AND status = ?', [1, 2])->limit(1)->getOne();
            if(count($cdata) > 0){
                $contribution = 1;
            }
            else{
                $contribution = 0;
            }
            Cache::set('admin_contribution', $contribution, 600);
        }
        View::assign('build', Info::getInfo('buildTime'))->assign('space', $space . ' M')->assign('sysname', Lang::lang('Yanzi CMS'))->assign('version', Env::get('YANZI_VERSION'))->assign('contribution', $contribution);
        $this->start();
        return View::view();
    }
    public function content()
    {
        $this->start(Lang::lang('Content'));
        $content = new Content();
        $content->id = '';
        $content->title = '';
        $content->alias = '';
        $content->creation = '';
        $content->image = '';
        $content->status = '';
        $content->view = '';
        $content->top = '';
        $content->recommend = '';
        $content->single = '';
        $data = $content->select('contribution = ?', 0)->order('id DESC')->paginate($this->per);
        foreach($data['items'] as $key => $val){
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
    public function newcontent()
    {
        Validate::isPost()
            ->postValue('selectmodel')->rule(V::must())->ifError(Lang::lang('Model must be selected'))
            ->postValue('attribution')->rule(V::must())->ifError(Lang::lang('Attribution must be chosen'))
            ->postValue('title')->rule(V::must())->ifError(Lang::lang('Title must be filled in'))
            ->postValue('content')->rule(V::must())->ifError(Lang::lang('Content must be filled in'))
            ->postValue('alias')->rule(V::startLetterNumberUnder())->ifError(Lang::lang('The alias must start with a letter and can only use letters, numbers and underscores'))->success(function($data){
                if(!empty($data['alias'])){
                    $checkalias = new Content();
                    $checkalias->id = '';
                    $check = $checkalias->select('alias = ?', $data['alias'])->limit(1)->getOne();
                    if(count($check) > 0){
                        Response::writeEnd(Lang::lang('Alias already exists'));
                    }
                }
                $selectmodel = new Models();
                $selectmodel->id = '';
                $selectmodel->alias = '';
                $selectmodel->parts = '';
                $selectmodel->select('alias = ?', $data['selectmodel'])->limit(1)->getOne(function($sdata) use($data){
                    $parts = json_decode($sdata['parts'], true);
                    $now = Date::now();
                    $data['content'] = Yanzi::filter(Request::getPost('content', false));
                    Plugin::adds('write', $data);
                    $re = Mod::transaction(function() use($data, $sdata, $parts, $now){
                        $newcontent = new Content();
                        $newcontent->uid = Session::get('id');
                        $newcontent->aid = $data['attribution'];
                        $newcontent->keyword = str_replace('，', ',', Request::getPost('keyword'));
                        $newcontent->title = $data['title'];
                        $newcontent->alias = $data['alias'];
                        $newcontent->summary = Request::getPost('summary');
                        $newcontent->content = $data['content'];
                        $newcontent->creation = $now;
                        $newcontent->modify = $now;
                        $newcontent->image = Request::getPost('image');
                        $newcontent->template = Request::getPost('template');
                        $newcontent->top = Request::getPost('top');
                        $newcontent->recommend = Request::getPost('recommend');
                        $newcontent->model = $sdata['id'];
                        $newcontent->single = Request::getPost('single');
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
                    Plugin::adds('afterWrite', $cid);
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
        $this->start(Lang::lang('New content'));
        $models = new Models();
        $models->name = '';
        $models->alias = '';
        $models->parts = '';
        View::assign('models', $models->select()->getSet());
        View::assign('templates', $this->getTemplates('content'));
        $this->allAttribution();
        return View::view();
    }
    public function contentmodify()
    {
        Validate::isPost()
            ->postValue('attribution')->rule(V::must())->ifError(Lang::lang('Attribution must be chosen'))
            ->postValue('title')->rule(V::must())->ifError(Lang::lang('Title must be filled in'))
            ->postValue('content')->rule(V::must())->ifError(Lang::lang('Content must be filled in'))
            ->postValue('alias')->rule(V::startLetterNumberUnder())->ifError(Lang::lang('The alias must start with a letter and can only use letters, numbers and underscores'))->success(function($data){
                $cid = Request::getPost('cid');
                if(!empty($data['alias'])){
                    $checkalias = new Content();
                    $checkalias->id = '';
                    $check = $checkalias->select('id <> ? AND alias = ?', [$cid, $data['alias']])->limit(1)->getOne();
                    if(count($check) > 0){
                        Response::writeEnd(Lang::lang('Alias already exists'));
                    }
                }
                $selfcontent = new Content();
                $selfcontent->model = '';
                $selfcontent->select('id = ?', $cid)->limit(1)->getOne(function($sdata) use($data, $cid){
                    $selectmodel = new Models();
                    $selectmodel->id = '';
                    $selectmodel->alias = '';
                    $selectmodel->parts = '';
                    $selectmodel->select('id = ?', $sdata['model'])->limit(1)->getOne(function($mdata) use($data, $cid, $sdata){
                        $parts = json_decode($mdata['parts'], true);
                        $now = Date::now();
                        $data['content'] = Yanzi::filter(Request::getPost('content', false));
                        Plugin::adds('modify', $data);
                        $re = Mod::transaction(function() use($data, $cid, $sdata, $mdata, $parts, $now){
                            $modifycontent = new Content();
                            $modifycontent->aid = $data['attribution'];
                            $modifycontent->keyword = str_replace('，', ',', Request::getPost('keyword'));
                            $modifycontent->title = $data['title'];
                            $modifycontent->alias = $data['alias'];
                            $modifycontent->summary = Request::getPost('summary');
                            $modifycontent->content = $data['content'];
                            $modifycontent->modify = $now;
                            $modifycontent->image = Request::getPost('image');
                            $modifycontent->template = Request::getPost('template');
                            $modifycontent->top = Request::getPost('top');
                            $modifycontent->recommend = Request::getPost('recommend');
                            $modifycontent->single = Request::getPost('single');
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
                        Plugin::adds('afterModify', $cid);
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
        $this->start(Lang::lang('Modify content'));
        $content = new Content();
        $content->id = '';
        $content->aid = '';
        $content->keyword = '';
        $content->title = '';
        $content->alias = '';
        $content->summary = '';
        $content->content = '';
        $content->image = '';
        $content->template = '';
        $content->top = '';
        $content->recommend = '';
        $content->model = '';
        $content->single = '';
        $content->select('id = ?', Request::getGet('id'))->limit(1)->getOne(function($data){
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
            });
        });
        View::assign('templates', $this->getTemplates('content'));
        $this->allAttribution();
        return View::view();
    }
    public function contentstatus()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))
            ->postValue('val')->rule(V::zeroOrOne())->ifError(Lang::lang('Parameter error'))->success(function($data){
                $content = new Content();
                $content->status = $data['val'];
                $content->update('id = ?', $data['id']);
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    public function contenttop()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))
            ->postValue('val')->rule(V::zeroOrOne())->ifError(Lang::lang('Parameter error'))->success(function($data){
                $content = new Content();
                $content->top = $data['val'];
                $content->update('id = ?', $data['id']);
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    public function contentrecommend()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))
            ->postValue('val')->rule(V::zeroOrOne())->ifError(Lang::lang('Parameter error'))->success(function($data){
                $content = new Content();
                $content->recommend = $data['val'];
                $content->update('id = ?', $data['id']);
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    public function contentdel()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $content = new Content();
            $content->id = '';
            $content->content = '';
            $content->image = '';
            $content->model = '';
            $content->select('id = ?', $data['id'])->limit(1)->getOne(function($cdata){
                $model = new Models();
                $model->alias = '';
                $model->parts = '';
                $mdata = $model->select('id = ?', $cdata['model'])->limit(1)->getOne();
                $param = $cdata;
                $param['modelsAlias'] = $mdata['alias'];
                $param['modelsParts'] = $mdata['parts'];
                Plugin::adds('beforeDeletContent', $param);
                $re = Mod::transaction(function() use($cdata, $mdata){
                    $delcontent = new Content();
                    $delcontent->id = $cdata['id'];
                    $delcontent->delete();
                    $aliasclass = 'model\\'.ucfirst($mdata['alias']);
                    $delmodel = new $aliasclass();
                    $delmodel->cid = $cdata['id'];
                    $delmodel->delete();
                    $delcomment = new Comment();
                    $delcomment->cid = $cdata['id'];
                    $delcomment->delete();
                    $delfavorites = new Favorites();
                    $delfavorites->cid = $cdata['id'];
                    $delfavorites->delete();
                });
                if($re === true){
                    Plugin::adds('afterDeletContent', $param);
                    Response::writeEnd('ok');
                }
                else{
                    Response::writeEnd(Lang::lang('The operation failed, please try again later'));
                }
            });
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
    }
    /**
     * Attribution
     */
    public function attribution()
    {
        $this->start(Lang::lang('Attribution'));
        $allAttribution = new Attribution();
        $allAttribution->id = '';
        $allAttribution->name = '';
        $allAttribution->alias = '';
        $allAttribution->parent = '';
        $allAttribution->menu = '';
        $allAttribution->fortemplate = '';
        $allAttribution->sort = '';
        $allAttribution->select()->order('sort ASC, id ASC')->getSet(function($data){
            $data = Branch::line($data);
            foreach($data as $key => $val){
                if(!empty($val['level'])){
                    $data[$key]['level'] = str_repeat('&nbsp;', strlen($val['level']) * 3) . '└─' . '&nbsp;';
                }
            }
            View::assign('data', $data);
        });
        return View::view();
    }
    /**
     * Add attribution
     */
    public function addattribution()
    {
        Validate::isPost()
            ->postValue('name')->rule(V::must())->ifError(Lang::lang('Name must be filled'))
            ->postValue('alias')->rule(V::must())->ifError(Lang::lang('Alias must be filled'))->rule(V::startLetterNumberUnder())->ifError(Lang::lang('The alias must start with a letter and can only use letters, numbers and underscores'))->success(function($data){
                $attribution = new Attribution();
                $attribution->id = '';
                $attribution->select('name = ? OR alias = ?', [$data['name'], $data['alias']])->limit(1)->getOne(function($rdata) use($data){
                    if(count($rdata) > 0){
                        Response::writeEnd(Lang::lang('Name or alias already exists'));
                    }
                    else{
                        $newAttribution = new Attribution();
                        $newAttribution->name = $data['name'];
                        $newAttribution->alias = $data['alias'];
                        $newAttribution->description = Request::getPost('description');
                        $newAttribution->parent = Request::getPost('upperlevel');
                        $newAttribution->menu = Request::getPost('menu');
                        $newAttribution->icon = str_replace('"', '\'', Request::getPost('icon', false));
                        $newAttribution->template = Request::getPost('template');
                        $newAttribution->fortemplate = Request::getPost('fortemplate');
                        $newAttribution->insert();
                        Cache::delete('menu');
                        Response::writeEnd('ok');
                    }
                });
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
        $this->start(Lang::lang('Add attribution'));
        View::assign('templates', $this->getTemplates('column'));
        $this->allAttribution();
        return View::view();
    }
    public function attributionsort()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))
            ->postValue('val')->rule(V::positiveInt())->ifError(Lang::lang('The ordinal number must be a positive integer'))->success(function($data){
                $attribution = new Attribution();
                $attribution->sort = $data['val'];
                $attribution->update('id = ?', $data['id']);
                Response::writeEnd('ok');
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
    }
    /**
     * Menu
     */
    public function attributionmenu()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))
            ->postValue('val')->rule(V::zeroOrOne())->ifError(Lang::lang('Parameter error'))->success(function($data){
                $attribution = new Attribution();
                $attribution->menu = $data['val'];
                $attribution->update('id = ?', $data['id']);
                Cache::delete('menu');
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    public function attributionfortemplate()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))
            ->postValue('val')->rule(V::zeroOrOne())->ifError(Lang::lang('Parameter error'))->success(function($data){
                $attribution = new Attribution();
                $attribution->fortemplate = $data['val'];
                $attribution->update('id = ?', $data['id']);
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    /**
     * Delete attribution
     */
    public function attributiondel()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
                $attribution = new Attribution();
                $attribution->parent = '';
                $self = $attribution->select('id = ?', $data['id'])->limit(1)->getOne();
                $delattribution = new Attribution();
                $delattribution->id = $data['id'];
                $delattribution->delete();
                $updateattribution = new Attribution();
                $updateattribution->parent = $self['parent'];
                $updateattribution->update('parent = ?', $data['id']);
                Cache::delete('menu');
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    /**
     * Modify attribution
     */
    public function attributionmodify()
    {
        Validate::isPost()
            ->postValue('name')->rule(V::must())->ifError(Lang::lang('Name must be filled'))
            ->postValue('alias')->rule(V::must())->ifError(Lang::lang('Alias must be filled'))->rule(V::startLetterNumberUnder())->ifError(Lang::lang('The alias must start with a letter and can only use letters, numbers and underscores'))->success(function($data){
                $attribution = new Attribution();
                $attribution->id = '';
                $attribution->select('id <> ? AND (name = ? OR alias = ?)', [Request::getPost('id'), $data['name'], $data['alias']])->limit(1)->getOne(function($rdata) use($data){
                    if(count($rdata) > 0){
                        Response::writeEnd(Lang::lang('Name or alias already exists'));
                    }
                    else{
                        $modifyAttribution = new Attribution();
                        $modifyAttribution->name = $data['name'];
                        $modifyAttribution->alias = $data['alias'];
                        $modifyAttribution->description = Request::getPost('description');
                        $modifyAttribution->parent = Request::getPost('upperlevel');
                        $modifyAttribution->menu = Request::getPost('menu');
                        $modifyAttribution->icon = str_replace('"', '\'', Request::getPost('icon', false));
                        $modifyAttribution->template = Request::getPost('template');
                        $modifyAttribution->fortemplate = Request::getPost('fortemplate');
                        $modifyAttribution->update('id = ?', Request::getPost('id'));
                        Cache::delete('menu');
                        Response::writeEnd('ok');
                    }
                });
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
        $this->start(Lang::lang('Modify attribution'));
        $attribution = new Attribution();
        $attribution->id = '';
        $attribution->name = '';
        $attribution->alias = '';
        $attribution->description = '';
        $attribution->parent = '';
        $attribution->menu = '';
        $attribution->icon = '';
        $attribution->template = '';
        $attribution->fortemplate = '';
        $attribution->select('id = ?', Request::getGet('id'))->limit(1)->getOne(function($data){
            View::assign('attribution', $data);
        });
        View::assign('templates', $this->getTemplates('column'));
        $this->allAttribution();
        return View::view();
    }
    /**
     * Models
     */
    public function model()
    {
        $this->start(Lang::lang('Model'));
        $model = new Models();
        $model->id = '';
        $model->name = '';
        $model->alias = '';
        $model->description = '';
        $model->contribution = '';
        $model->select()->getSet(function($data){
            View::assign('data', $data);
        });
        return View::view();
    }
    public function modelcontribution()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))
            ->postValue('val')->rule(V::zeroOrOne())->ifError(Lang::lang('Parameter error'))->success(function($data){
                $models = new Models();
                $models->contribution = $data['val'];
                $models->update('id = ?', $data['id']);
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    /**
     * New model
     */
    public function addmodel()
    {
        Validate::isPost()
            ->postValue('name')->rule(V::must())->ifError(Lang::lang('Name must be filled'))
            ->postValue('alias')->rule(V::must())->ifError(Lang::lang('Alias must be filled'))->rule(V::startLetterNumberUnder())->ifError(Lang::lang('The alias must start with a letter and can only use letters, numbers and underscores'))
            ->postValue('parts')->rule(V::must())->ifError(Lang::lang('Model parts must be filled'))->success(function($data){
                $model = new Models();
                $model->id = '';
                $model->select('name = ? OR alias = ?', [$data['name'], $data['alias']])->limit(1)->getOne(function($rdata) use($data){
                    if(count($rdata) > 0){
                        Response::writeEnd(Lang::lang('Name or alias already exists'));
                    }
                    elseif(in_array($data['alias'], $this->disableAlias())){
                        Response::writeEnd(Lang::lang('The alias is not available, please change to another alias'));
                    }
                    else{
                        $data['parts'] = htmlspecialchars_decode($data['parts']);
                        $parts = json_decode($data['parts'], true);
                        $palias = array_column($parts, 'partalias');
                        $paliasun = array_unique($palias);
                        if(count($palias) != count($paliasun)){
                            Response::writeEnd(Lang::lang('Alias cannot be repeated'));
                        }
                        $missing = false;
                        $daterr = false;
                        $datetimerr = false;
                        $singlechoice = false;
                        $multiplechoice = false;
                        foreach($parts as $val){
                            if(empty($val['partname']) || empty($val['partalias']) || empty($val['parttype'])){
                                $missing = true;
                            }
                            elseif($val['parttype'] == 'date' && !empty($val['defaults']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $val['defaults'])){
                                $daterr = true;
                            }
                            elseif($val['parttype'] == 'datetime' && !empty($val['defaults']) && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $val['defaults'])){
                                $datetimerr = true;
                            }
                            elseif($val['parttype'] == 'singlechoice' && empty($val['defaults'])){
                                $singlechoice = true;
                            }
                            elseif($val['parttype'] == 'multiplechoice' && empty($val['defaults'])){
                                $multiplechoice = true;
                            }
                        }
                        if($missing){
                            Response::writeEnd(Lang::lang('Model parts must be completed'));
                        }
                        elseif($daterr){
                            Response::writeEnd(Lang::lang('Date format must be "YYYY-MM-DD"'));
                        }
                        elseif($datetimerr){
                            Response::writeEnd(Lang::lang('The date and time format must be "YYYY-MM-DD HH:MM:SS"'));
                        }
                        elseif($singlechoice){
                            Response::writeEnd(Lang::lang('The options are not filled in, and the options are separated by ","'));
                        }
                        elseif($multiplechoice){
                            Response::writeEnd(Lang::lang('The options are not filled in, and the options are separated by ","'));
                        }
                        else{
                            $re = Mod::transaction(function() use($data, $parts){
                                $newmodel = new Models();
                                $newmodel->name = $data['name'];
                                $newmodel->alias = $data['alias'];
                                $newmodel->description = Request::getPost('description');
                                $newmodel->contribution = Request::getPost('contribution');
                                $newmodel->parts = $data['parts'];
                                $newmodel->insert();
                                $newmd = Model::ifNoModel($data['alias'])->newModel($data['alias'])->add('cid')->type(M::int())->len(11)->unsigned()->defaults(0);
                                foreach($parts as $val){
                                    switch($val['parttype']){
                                        case 'varchar':
                                            $default = empty($val['defaults']) ? '' : $val['defaults'];
                                            $newmd = $newmd->add($val['partalias'])->type(M::varchar())->len(1024)->defaults($default);
                                            break;
                                        case 'text':
                                            $default = empty($val['defaults']) ? '' : $val['defaults'];
                                            $newmd = $newmd->add($val['partalias'])->type(M::text())->defaults($default);
                                            break;
                                        case 'int':
                                            $default = empty($val['defaults']) ? 0 : intval($val['defaults']);
                                            $newmd = $newmd->add($val['partalias'])->type(M::int())->len(11)->defaults($default);
                                            break;
                                        case 'decimal':
                                            $default = empty($val['defaults']) ? 0 : round(floatval($val['defaults']), 2);
                                            $newmd = $newmd->add($val['partalias'])->type(M::decimal())->len('10,2')->defaults($default);
                                            break;
                                        case 'date':
                                            $default = empty($val['defaults']) ? '1000-01-01' : $val['defaults'];
                                            $newmd = $newmd->add($val['partalias'])->type(M::date())->defaults($default);
                                            break;
                                        case 'datetime':
                                            $default = empty($val['defaults']) ? '1000-01-01 00:00:00' : $val['defaults'];
                                            $newmd = $newmd->add($val['partalias'])->type(M::datetime())->defaults($default);
                                            break;
                                        case 'singlechoice':
                                            $default = '';
                                            $newmd = $newmd->add($val['partalias'])->type(M::text())->defaults($default);
                                            break;
                                        case 'multiplechoice':
                                            $default = '';
                                            $newmd = $newmd->add($val['partalias'])->type(M::text())->defaults($default);
                                            break;
                                        case 'multiplepictures':
                                            $default = empty($val['defaults']) ? '' : $val['defaults'];
                                            $newmd = $newmd->add($val['partalias'])->type(M::text())->defaults($default);
                                            break;
                                        case 'video':
                                            $default = empty($val['defaults']) ? '' : $val['defaults'];
                                            $newmd = $newmd->add($val['partalias'])->type(M::varchar())->len(500)->defaults($default);
                                            break;
                                        case 'annex':
                                            $default = empty($val['defaults']) ? '' : $val['defaults'];
                                            $newmd = $newmd->add($val['partalias'])->type(M::varchar())->len(500)->defaults($default);
                                            break;
                                    }
                                }
                                $newmd->create();
                            });
                            if($re === true){
                                Response::writeEnd('ok');
                            }
                            else{
                                Response::writeEnd(Lang::lang('The operation failed, please try again later'));
                            }
                        }
                    }
                });
            })
            ->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
        $this->start(Lang::lang('Add model'));
        return View::view();
    }
    /**
     * Modify the model
     */
    public function modelmodify()
    {
        Validate::isPost()
            ->postValue('name')->rule(V::must())->ifError(Lang::lang('Name must be filled'))
            ->postValue('alias')->rule(V::must())->ifError(Lang::lang('Alias must be filled'))->rule(V::startLetterNumberUnder())->ifError(Lang::lang('The alias must start with a letter and can only use letters, numbers and underscores'))
            ->postValue('parts')->rule(V::must())->ifError(Lang::lang('Model parts must be filled'))->success(function($data){
                $model = new Models();
                $model->id = '';
                $model->select('id <> ? AND (name = ? OR alias = ?)', [Request::getPost('id'), $data['name'], $data['alias']])->limit(1)->getOne(function($rdata) use($data){
                    if(count($rdata) > 0){
                        Response::writeEnd(Lang::lang('Name or alias already exists'));
                    }
                    elseif(in_array($data['alias'], $this->disableAlias())){
                        Response::writeEnd(Lang::lang('The alias is not available, please change to another alias'));
                    }
                    else{
                        $selfmodel = new Models();
                        $selfmodel->id = '';
                        $selfmodel->parts = '';
                        $sdata = $selfmodel->select('id = ?', Request::getPost('id'))->limit(1)->getOne();
                        $data['parts'] = htmlspecialchars_decode($data['parts']);
                        $parts = json_decode($data['parts'], true);
                        $palias = array_column($parts, 'partalias');
                        $paliasun = array_unique($palias);
                        if(count($palias) != count($paliasun)){
                            Response::writeEnd(Lang::lang('Alias cannot be repeated'));
                        }
                        $missing = false;
                        $daterr = false;
                        $datetimerr = false;
                        $singlechoice = false;
                        $multiplechoice = false;
                        $addmodel = [];
                        $originalmodel = [];
                        foreach($parts as $key => $val){
                            if(empty($val['partname']) || empty($val['partalias']) || empty($val['parttype'])){
                                $missing = true;
                            }
                            elseif($val['parttype'] == 'date' && !empty($val['defaults']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $val['defaults'])){
                                $daterr = true;
                            }
                            elseif($val['parttype'] == 'datetime' && !empty($val['defaults']) && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $val['defaults'])){
                                $datetimerr = true;
                            }
                            elseif($val['parttype'] == 'singlechoice' && empty($val['defaults'])){
                                $singlechoice = true;
                            }
                            elseif($val['parttype'] == 'multiplechoice' && empty($val['defaults'])){
                                $multiplechoice = true;
                            }
                            if($val['isnew'] == 1){
                                $addmodel[] = $val;
                            }
                            else{
                                $originalmodel[$val['partalias']] = $val;
                            }
                            unset($parts[$key]['isnew']);
                        }
                        if($missing){
                            Response::writeEnd(Lang::lang('Model parts must be completed'));
                        }
                        elseif($daterr){
                            Response::writeEnd(Lang::lang('Date format must be "YYYY-MM-DD"'));
                        }
                        elseif($datetimerr){
                            Response::writeEnd(Lang::lang('The date and time format must be "YYYY-MM-DD HH:MM:SS"'));
                        }
                        elseif($singlechoice){
                            Response::writeEnd(Lang::lang('The options are not filled in, and the options are separated by ","'));
                        }
                        elseif($multiplechoice){
                            Response::writeEnd(Lang::lang('The options are not filled in, and the options are separated by ","'));
                        }
                        else{
                            $opalias = array_column($originalmodel, 'partalias');
                            $oldparts = json_decode($sdata['parts'], true);
                            $delmodel = [];
                            foreach($oldparts as $key => $val){
                                if(!in_array($val['partalias'], $opalias)){
                                    $delmodel[] = $val;
                                }
                                if(isset($originalmodel[$val['partalias']]) && $this->arrayValuesSame($originalmodel[$val['partalias']], $val, ['partname', 'parttype', 'defaults'])){
                                    unset($originalmodel[$val['partalias']]);
                                }
                            }
                            $re = Mod::transaction(function() use($data, $parts, $addmodel, $originalmodel, $delmodel){
                                $modifymodel = new Models();
                                $modifymodel->name = $data['name'];
                                $modifymodel->alias = $data['alias'];
                                $modifymodel->description = Request::getPost('description');
                                $modifymodel->contribution = Request::getPost('contribution');
                                $modifymodel->parts = json_encode($parts);
                                $modifymodel->update('id = ?', Request::getPost('id'));
                                if(count($delmodel) > 0){
                                    foreach($delmodel as $dval){
                                        Model::modifyModel($data['alias'])->del($dval['partalias']);
                                    }
                                }
                                if(count($originalmodel) > 0){
                                    foreach($originalmodel as $oval){
                                        $change = Model::modifyModel($data['alias'])->change($oval['partalias'], $oval['partalias']);
                                        switch($oval['parttype']){
                                            case 'varchar':
                                                $default = empty($oval['defaults']) ? '' : $oval['defaults'];
                                                $change = $change->type(M::varchar())->len(1024)->defaults($default);
                                                break;
                                            case 'text':
                                                $default = empty($oval['defaults']) ? '' : $oval['defaults'];
                                                $change = $change->type(M::text())->defaults($default);
                                                break;
                                            case 'int':
                                                $default = empty($oval['defaults']) ? 0 : intval($oval['defaults']);
                                                $change = $change->type(M::int())->len(11)->defaults($default);
                                                break;
                                            case 'decimal':
                                                $default = empty($oval['defaults']) ? 0 : round(floatval($oval['defaults']), 2);
                                                $change = $change->type(M::decimal())->len('10,2')->defaults($default);
                                                break;
                                            case 'date':
                                                $default = empty($oval['defaults']) ? '1000-01-01' : $oval['defaults'];
                                                $change = $change->type(M::date())->defaults($default);
                                                break;
                                            case 'datetime':
                                                $default = empty($oval['defaults']) ? '1000-01-01 00:00:00' : $oval['defaults'];
                                                $change = $change->type(M::datetime())->defaults($default);
                                                break;
                                            case 'singlechoice':
                                                $default = '';
                                                $change = $change->type(M::text())->defaults($default);
                                                break;
                                            case 'multiplechoice':
                                                $default = '';
                                                $change = $change->type(M::text())->defaults($default);
                                                break;
                                            case 'multiplepictures':
                                                $default = empty($val['defaults']) ? '' : $val['defaults'];
                                                $change = $change->type(M::text())->defaults($default);
                                                break;
                                            case 'video':
                                                $default = empty($oval['defaults']) ? '' : $oval['defaults'];
                                                $change = $change->type(M::varchar())->len(500)->defaults($default);
                                                break;
                                            case 'annex':
                                                $default = empty($oval['defaults']) ? '' : $oval['defaults'];
                                                $change = $change->type(M::varchar())->len(500)->defaults($default);
                                                break;
                                        }
                                        $change->modify();
                                    }
                                }
                                if(count($addmodel) > 0){
                                    foreach($addmodel as $aval){
                                        $addm = Model::modifyModel($data['alias'])->add($aval['partalias']);
                                        switch($aval['parttype']){
                                            case 'varchar':
                                                $default = empty($aval['defaults']) ? '' : $aval['defaults'];
                                                $addm = $addm->type(M::varchar())->len(1024)->defaults($default);
                                                break;
                                            case 'text':
                                                $default = empty($aval['defaults']) ? '' : $aval['defaults'];
                                                $addm = $addm->type(M::text())->defaults($default);
                                                break;
                                            case 'int':
                                                $default = empty($aval['defaults']) ? 0 : intval($aval['defaults']);
                                                $addm = $addm->type(M::int())->len(11)->defaults($default);
                                                break;
                                            case 'decimal':
                                                $default = empty($aval['defaults']) ? 0 : round(floatval($aval['defaults']), 2);
                                                $addm = $addm->type(M::decimal())->len('10,2')->defaults($default);
                                                break;
                                            case 'date':
                                                $default = empty($aval['defaults']) ? '1000-01-01' : $aval['defaults'];
                                                $addm = $addm->type(M::date())->defaults($default);
                                                break;
                                            case 'datetime':
                                                $default = empty($aval['defaults']) ? '1000-01-01 00:00:00' : $aval['defaults'];
                                                $addm = $addm->type(M::datetime())->defaults($default);
                                                break;
                                            case 'singlechoice':
                                                $default = '';
                                                $addm = $addm->type(M::text())->defaults($default);
                                                break;
                                            case 'multiplechoice':
                                                $default = '';
                                                $addm = $addm->type(M::text())->defaults($default);
                                                break;
                                            case 'multiplepictures':
                                                $default = empty($val['defaults']) ? '' : $val['defaults'];
                                                $addm = $addm->type(M::text())->defaults($default);
                                                break;
                                            case 'video':
                                                $default = empty($aval['defaults']) ? '' : $aval['defaults'];
                                                $addm = $addm->type(M::varchar())->len(500)->defaults($default);
                                                break;
                                            case 'annex':
                                                $default = empty($aval['defaults']) ? '' : $aval['defaults'];
                                                $addm = $addm->type(M::varchar())->len(500)->defaults($default);
                                                break;
                                        }
                                        $addm->modify();
                                    }
                                }
                            });
                            if($re === true){
                                Response::writeEnd('ok');
                            }
                            else{
                                Response::writeEnd(Lang::lang('The operation failed, please try again later'));
                            }
                        }
                    }
                });
            })
            ->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
        $thismodel = new Models();
        $thismodel->id = '';
        $thismodel->name = '';
        $thismodel->alias = '';
        $thismodel->description = '';
        $thismodel->contribution = '';
        $thismodel->parts = '';
        $thismodel->select('id = ?', Request::getGet('id'))->limit(1)->getOne(function($data){
            View::assign('model', $data);
        });
        $this->start(Lang::lang('Modify the model'));
        return View::view();
    }
    /**
     * Delete model
     */
    public function modeldel()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($idata){
            $thismodel = new Models();
            $thismodel->id = '';
            $thismodel->alias = '';
            $thismodel->select('id = ?', $idata['id'])->limit(1)->getOne(function($data){
                $re = Mod::transaction(function() use($data){
                    Model::ifHasModel($data['alias'])->delModel($data['alias']);
                    $delmodel = new Models();
                    $delmodel->id = $data['id'];
                    $delmodel->delete();
                });
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
    }
    public function setup()
    {
        Validate::isPost()->postValue('title')->rule(V::must())->ifError(Lang::lang('Site name must be filled'))->postValue('domain')->rule(V::must())->ifError(Lang::lang('Domain name must be filled'))->rule(V::url())->ifError(Lang::lang('Incorrect domain format'))->success(function(){
            $dataArr = Request::getPost('', false);
            $dataArr['domain'] = rtrim($dataArr['domain'], '/') . '/';
            if(substr($dataArr['domain'], 0, 4) != 'http'){
                $dataArr['domain'] = 'http://' . $dataArr['domain'];
            }
            foreach((array)$dataArr as $key =>$val){
                $disposeupdate = new Dispose();
                $disposeupdate->content = $val;
                $disposeupdate->update('name = ?', $key);
                $disposeupdate = null;
            }
            Response::writeEnd('ok');
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
        $dispose = new Dispose();
        $dispose->name = '';
        $dispose->content = '';
        $dispose->select('control = ?', 1)->getSet(function($data){
            $disposearr = [];
            foreach($data as $key => $val){
                $disposearr[$val['name']] = $val['content'];
            }
            View::assign('dispose', $disposearr);
        });
        $this->start(Lang::lang('Set up'));
        return View::view();
    }
    public function user()
    {
        $search = '';
        if(Request::hasGet('user')){
            $search = trim(Request::getGet('user'));
        }
        $user = new User();
        $user->id = '';
        $user->name = '';
        $user->nickname = '';
        $user->email = '';
        $user->avatar = '';
        $user->creation = '';
        $user->login = '';
        $user->status = '';
        $user->usertype = '';
        if(empty($search)){
            $udata = $user->select('id > ?', 1)->order('id DESC')->paginate($this->per);
        }
        else{
            $udata = $user->select('id > ? AND name LIKE ?', [1, '%'.$search.'%'])->order('id DESC')->paginate($this->per);
        }
        foreach($udata['items'] as $key => $val){
            if($val['login'] == '1000-01-01 00:00:00'){
                $udata['items'][$key]['login'] = '';
            }
        }
        View::assign('data', $udata);
        $this->start(Lang::lang('User'));
        return View::view();
    }
    public function userstatus()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))
            ->postValue('val')->rule(V::zeroOrOne())->ifError(Lang::lang('Parameter error'))->success(function($data){
                $user = new User();
                $user->status = $data['val'];
                $user->update('id = ?', $data['id']);
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    public function userdel()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $selfComment = new Comment();
            $selfComment->id = '';
            $selfComment->select('uid = ?', $data['id'])->getSet(function($sdata) use($data){
                $cids = '';
                foreach($sdata as $key => $val){
                    $cids .= empty($cids) ? $val['id'] : ',' . $val['id'];
                }
                $re = Mod::transaction(function() use($data, $cids){
                    $delUser = new User();
                    $delUser->id = $data['id'];
                    $delUser->delete();
                    $delContent = new Content();
                    $delContent->uid = $data['id'];
                    $delContent->delete();
                    $delComment = new Comment();
                    $delComment->uid = $data['id'];
                    $delComment->delete();
                    $modifyComment = new Comment();
                    $modifyComment->parent = 0;
                    $modifyComment->update('parent in ?', $cids);
                    $delFavorites = new Favorites();
                    $delFavorites->uid = $data['id'];
                    $delFavorites->delete();
                });
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
    }
    public function userchange()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))
            ->postValue('val')->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
                $user = new User();
                $user->usertype = $data['val'];
                $user->update('id = ?', $data['id']);
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    public function slide()
    {
        $slideshow = new Slideshow();
        $slideshow->id = '';
        $slideshow->name = '';
        $slideshow->image = '';
        $slideshow->status = '';
        $slideshow->sort = '';
        $slidegroup = new Slidegroup();
        $slidegroup->name = 'groupname';
        $slidegroup->width = '';
        $slidegroup->height = '';
        $slideshow->select()->order('sgid asc, sort asc')->join(
            $slidegroup->select()->equal('id', 'sgid')
        )->getSet(function($data){
            View::assign('data', $data);
        });
        $this->start(Lang::lang('Slide'));
        return View::view();
    }
    public function addslide()
    {
        Validate::isPost()->postValue('slidegroup')->rule(V::must())->ifError(Lang::lang('Slide group must be selected'))->postValue('slideshowimage')->rule(V::must())->ifError(Lang::lang('Slideshow image must be uploaded'))->success(function($data){
            $addslide = new Slideshow();
            $addslide->sgid = $data['slidegroup'];
            $addslide->name = Request::getPost('name');
            $addslide->image = $data['slideshowimage'];
            $addslide->link = Request::getPost('link');
            $addslide->description = Request::getPost('description');
            $addslide->insert();
            Response::writeEnd('ok');
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
        $slidegroup = new Slidegroup();
        $slidegroup->id = '';
        $slidegroup->name = '';
        $slidegroup->select()->getSet(function($data){
            View::assign('slidegroup', $data);
        });
        $this->start(Lang::lang('Add slideshow'));
        return View::view();
    }
    public function slidemodify()
    {
        Validate::isPost()->postValue('slidegroup')->rule(V::must())->ifError(Lang::lang('Slide group must be selected'))->postValue('slideshowimage')->rule(V::must())->ifError(Lang::lang('Slideshow image must be uploaded'))->success(function($data){
            $modifyslide = new Slideshow();
            $modifyslide->sgid = $data['slidegroup'];
            $modifyslide->name = Request::getPost('name');
            $modifyslide->image = $data['slideshowimage'];
            $modifyslide->link = Request::getPost('link');
            $modifyslide->description = Request::getPost('description');
            $modifyslide->update('id = ?', Request::getPost('id'));
            Response::writeEnd('ok');
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
        $slideshow = new Slideshow();
        $slideshow->id = '';
        $slideshow->sgid = '';
        $slideshow->name = '';
        $slideshow->image = '';
        $slideshow->link = '';
        $slideshow->description = '';
        $slideshow->select('id = ?', Request::getGet('id'))->limit(1)->getOne(function($data){
            View::assign('slide', $data);
        });
        $slidegroup = new Slidegroup();
        $slidegroup->id = '';
        $slidegroup->name = '';
        $slidegroup->select()->getSet(function($data){
            View::assign('slidegroup', $data);
        });
        $this->start(Lang::lang('Edit slide'));
        return View::view();
    }
    public function slidesort()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))
            ->postValue('val')->rule(V::positiveInt())->ifError(Lang::lang('The ordinal number must be a positive integer'))->success(function($data){
                $slideshow = new Slideshow();
                $slideshow->sort = $data['val'];
                $slideshow->update('id = ?', $data['id']);
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    public function slidestatus()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))
            ->postValue('val')->rule(V::zeroOrOne())->ifError(Lang::lang('Parameter error'))->success(function($data){
                $slideshow = new Slideshow();
                $slideshow->status = $data['val'];
                $slideshow->update('id = ?', $data['id']);
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    public function slidedel()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $selfshow = new Slideshow();
            $selfshow->image = '';
            $selfshow->select('id = ?', $data['id'])->limit(1)->getOne(function($sdata) use($data){
                $slideshow = new Slideshow();
                $slideshow->id = $data['id'];
                $slideshow->delete();
                if(!empty($sdata['image'])){
                    $this->delimg($sdata['image']);
                }
                Response::writeEnd('ok');
            });
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
    }
    public function addslidegroup()
    {
        Validate::isPost()
            ->postValue('name')->rule(V::must())->ifError(Lang::lang('Name must be filled'))
            ->postValue('alias')->rule(V::must())->ifError(Lang::lang('Alias must be filled'))->rule(V::startLetterNumberUnder())->ifError(Lang::lang('The alias must start with a letter and can only use letters, numbers and underscores'))
            ->postValue('width')->rule(V::must())->ifError(Lang::lang('Width must be filled'))->rule(V::positiveInt())->ifError(Lang::lang('Width must be a positive integer'))
            ->postValue('height')->rule(V::must())->ifError(Lang::lang('Height must be filled'))->rule(V::positiveInt())->ifError(Lang::lang('Height must be a positive integer'))->success(function($data){
                $slidegroup = new Slidegroup();
                $slidegroup->id = '';
                $slidegroup->select('name = ? OR alias = ?', [$data['name'], $data['alias']])->limit(1)->getOne(function($sdata) use($data){
                    if(count($sdata) > 0){
                        Response::writeEnd(Lang::lang('Name or alias already exists'));
                    }
                    else{
                        $newSlidegroup = new Slidegroup();
                        $newSlidegroup->name = $data['name'];
                        $newSlidegroup->alias = $data['alias'];
                        $newSlidegroup->width = $data['width'];
                        $newSlidegroup->height = $data['height'];
                        $newSlidegroup->description = Request::getPost('description');
                        $newSlidegroup->insert();
                        Response::writeEnd('ok');
                    }
                });
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
        $this->start(Lang::lang('Add slideshow group'));
        return View::view();
    }
    public function slidegroup()
    {
        $slidegroup = new Slidegroup();
        $slidegroup->id = '';
        $slidegroup->name = '';
        $slidegroup->alias = '';
        $slidegroup->width = '';
        $slidegroup->height = '';
        $slidegroup->select()->getSet(function($data){
            View::assign('data', $data);
        });
        $this->start(Lang::lang('Slideshow group'));
        return View::view();
    }
    public function slidegroupmodify()
    {
        Validate::isPost()
            ->postValue('name')->rule(V::must())->ifError(Lang::lang('Name must be filled'))
            ->postValue('alias')->rule(V::must())->ifError(Lang::lang('Alias must be filled'))->rule(V::startLetterNumberUnder())->ifError(Lang::lang('The alias must start with a letter and can only use letters, numbers and underscores'))
            ->postValue('width')->rule(V::must())->ifError(Lang::lang('Width must be filled'))->rule(V::positiveInt())->ifError(Lang::lang('Width must be a positive integer'))
            ->postValue('height')->rule(V::must())->ifError(Lang::lang('Height must be filled'))->rule(V::positiveInt())->ifError(Lang::lang('Height must be a positive integer'))->success(function($data){
                $slidegroup = new Slidegroup();
                $slidegroup->id = '';
                $slidegroup->select('id <> ? AND (name = ? OR alias = ?)', [Request::getPost('id'), $data['name'], $data['alias']])->limit(1)->getOne(function($sdata) use($data){
                    if(count($sdata) > 0){
                        Response::writeEnd(Lang::lang('Name or alias already exists'));
                    }
                    else{
                        $modifySlidegroup = new Slidegroup();
                        $modifySlidegroup->name = $data['name'];
                        $modifySlidegroup->alias = $data['alias'];
                        $modifySlidegroup->width = $data['width'];
                        $modifySlidegroup->height = $data['height'];
                        $modifySlidegroup->description = Request::getPost('description');
                        $modifySlidegroup->update('id = ?', Request::getPost('id'));
                        Response::writeEnd('ok');
                    }
                });
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
        $this->start(Lang::lang('Modify slide group'));
        $slidegroup = new Slidegroup();
        $slidegroup->id = '';
        $slidegroup->name = '';
        $slidegroup->alias = '';
        $slidegroup->width = '';
        $slidegroup->height = '';
        $slidegroup->description = '';
        $slidegroup->select('id = ?', Request::getGet('id'))->limit(1)->getOne(function($data){
            View::assign('slidegroup', $data);
        });
        return View::view();
    }
    public function slidegroupdel()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $re = Mod::transaction(function() use($data){
                $delSlidegroup = new Slidegroup();
                $delSlidegroup->id = $data['id'];
                $delSlidegroup->delete();
                $delSlideshow = new Slideshow();
                $delSlideshow->sgid = $data['id'];
                $delSlideshow->delete();
            });
            if($re === true){
                Response::writeEnd('ok');
            }
            else{
                Response::writeEnd(Lang::lang('The operation failed, please try again later'));
            }
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
    }
    public function links()
    {
        $link = new Link();
        $link->id = '';
        $link->name = '';
        $link->url = '';
        $link->image = '';
        $link->home = '';
        $link->sort = '';
        $link->status = '';
        $link->select()->order('sort asc')->getSet(function($data){
            View::assign('data', $data);
        });
        $this->start(Lang::lang('Links'));
        return View::view();
    }
    public function addlink()
    {
        Validate::isPost()->postValue('name')->rule(V::must())->ifError(Lang::lang('Name must be filled'))->postValue('url')->rule(V::must())->ifError(Lang::lang('The link must be filled in'))->success(function($data){
            $addlink = new Link();
            $addlink->name = $data['name'];
            $addlink->url = $data['url'];
            $addlink->image = Request::getPost('image');
            $addlink->description = Request::getPost('description');
            $addlink->home = Request::getPost('home');
            $addlink->insert();
            Response::writeEnd('ok');
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
        $this->start(Lang::lang('Add a link'));
        return View::view();
    }
    public function linkmodify()
    {
        Validate::isPost()->postValue('name')->rule(V::must())->ifError(Lang::lang('Name must be filled'))->postValue('url')->rule(V::must())->ifError(Lang::lang('The link must be filled in'))->success(function($data){
            $modifylink = new Link();
            $modifylink->name = $data['name'];
            $modifylink->url = $data['url'];
            $modifylink->image = Request::getPost('image');
            $modifylink->description = Request::getPost('description');
            $modifylink->home = Request::getPost('home');
            $modifylink->update('id = ?', Request::getPost('id'));
            Response::writeEnd('ok');
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
        $link = new Link();
        $link->id = '';
        $link->name = '';
        $link->url = '';
        $link->image = '';
        $link->description = '';
        $link->home = '';
        $link->select('id = ?', Request::getGet('id'))->limit(1)->getOne(function($data){
            View::assign('link', $data);
        });
        $this->start(Lang::lang('Modify friendship link'));
        return View::view();
    }
    public function linksort()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))
            ->postValue('val')->rule(V::positiveInt())->ifError(Lang::lang('The ordinal number must be a positive integer'))->success(function($data){
                $link = new Link();
                $link->sort = $data['val'];
                $link->update('id = ?', $data['id']);
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    public function linkhome()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))
            ->postValue('val')->rule(V::zeroOrOne())->ifError(Lang::lang('Parameter error'))->success(function($data){
                $link = new Link();
                $link->home = $data['val'];
                $link->update('id = ?', $data['id']);
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    public function linkstatus()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))
            ->postValue('val')->rule(V::zeroOrOne())->ifError(Lang::lang('Parameter error'))->success(function($data){
                $link = new Link();
                $link->status = $data['val'];
                $link->update('id = ?', $data['id']);
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    public function linkdel()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $selflink = new Link();
            $selflink->image = '';
            $selflink->select('id = ?', $data['id'])->limit(1)->getOne(function($sdata) use($data){
                $link = new Link();
                $link->id = $data['id'];
                $link->delete();
                if(!empty($sdata['image'])){
                    $this->delimg($sdata['image']);
                }
                Response::writeEnd('ok');
            });
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
    }
    public function plugin()
    {
        File::newFolder(APP . 'plugin', true);
        $data = [];
        $openplugins = Info::getInfo('openedplugins');
        if(empty($openplugins)){
            $openplugins = [];
        }
        else{
            $openplugins = unserialize($openplugins);
        }
        $plugins = File::listFolders(APP . 'plugin', true);
        foreach($plugins as $val){
            $onep['plugin'] = $val;
            if(in_array($val, $openplugins)){
                $onep['opened'] = 1;
            }
            else{
                $onep['opened'] = 0;
            }
            $tmp = [
                'name' => '',
                'description' => '',
                'author' => '',
                'version' => '',
            ];
            $infoPath = APP . 'plugin' . DS . $val . DS . 'info.txt';
            if(is_file($infoPath)){
                $info = file($infoPath);
                $name = '';
                $description = '';
                foreach($info as $ival){
                    if(trim($ival) == ''){
                        continue;
                    }
                    $ival = str_replace('：', ': ', $ival);
                    if(strpos($ival, ':') !== false){
                        if(!empty($name)){
                            $onep[$name] = $description;
                            unset($tmp[$name]);
                            $description = '';
                        }
                        $name = trim(strstr($ival, ':', true));
                        $description .= trim(substr(strstr($ival, ':'), 1));
                    }
                    else{
                        $description .= '<br>' . $ival;
                    }
                }
                if(!empty($name)){
                    $onep[$name] = $description;
                    unset($tmp[$name]);
                }
            }
            if(count($tmp) > 0){
                $onep = array_merge($onep, $tmp);
            }
            $data[] = $onep;
        }
        View::assign('data', $data);
        $this->start(Lang::lang('Plugin'));
        return View::view();
    }
    public function pluginopen()
    {
        Validate::isPost()->postValue('plugin')->rule(V::must())->ifError(Lang::lang('Parameter error'))
            ->postValue('val')->rule(V::zeroOrOne())->ifError(Lang::lang('Parameter error'))->success(function($data){
                $openplugins = Info::getInfo('openedplugins');
                if(empty($openplugins)){
                    $openplugins = [];
                }
                else{
                    $openplugins = unserialize($openplugins);
                }
                if($data['val'] == 1){
                    if(!isset($openplugins[$data['plugin']])){
                        $openplugins[] = $data['plugin'];
                        Plugin::add('open', $data['plugin']);
                    }
                }
                else{
                    foreach($openplugins as $key => $val){
                        if($val == $data['plugin']){
                            unset($openplugins[$key]);
                            Plugin::add('close', $data['plugin']);
                        }
                    }
                }
                Info::setInfo('openedplugins', serialize($openplugins));
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    public function uploadplugin()
    {
        $filePath = ROOT . File::get('file')->checkExt(Request::getPost('allow'))->ifSaved()->getUploaded();
        $pluginFile = APP . 'plugin';
        $zip = new \ZipArchive();
        if($zip->open($filePath, \ZipArchive::OVERWRITE || \ZIPARCHIVE::CREATE) === true){
            $zip->extractTo($pluginFile);
            $zip->close();
            @unlink($filePath);
        }
        Response::writeEnd('ok');
    }
    public function plugindel()
    {
        Validate::isPost()->postValue('plugin')->rule(V::must())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $pluginFile = APP . 'plugin' . DS . $data['plugin'];
            File::deleteFolder($pluginFile);
            $openplugins = unserialize(Info::getInfo('openedplugins'));
            foreach($openplugins as $key => $val){
                if($val == $data['plugin']){
                    unset($openplugins[$key]);
                }
            }
            Info::setInfo('openedplugins', serialize($openplugins));
            Response::writeEnd('ok');
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
    }
    public function template()
    {
        $templatePath = ROOT . 'public' . DS . 'template';
        $template = File::listFolders($templatePath, true);
        $opentemplate = Info::getInfo('template');
        $data = [];
        foreach($template as $val){
            $onep['template'] = $val;
            if($opentemplate == $val){
                $onep['opened'] = 1;
            }
            else{
                $onep['opened'] = 0;
            }
            $tmp = [
                'name' => '',
                'description' => '',
                'author' => '',
                'version' => '',
                'screenshot' => ''
            ];
            $screenshot = $templatePath . DS . $val . DS . 'screenshot.jpg';
            if(is_file($screenshot)){
                $tmp['screenshot'] = 'template/'.$val.'/screenshot.jpg';
            }
            $infoPath = $templatePath . DS . $val . DS . 'info.txt';
            if(is_file($infoPath)){
                $info = file($infoPath);
                $name = '';
                $description = '';
                foreach($info as $ival){
                    if(trim($ival) == ''){
                        continue;
                    }
                    $ival = str_replace('：', ': ', $ival);
                    if(strpos($ival, ':') !== false){
                        if(!empty($name)){
                            $onep[$name] = $description;
                            unset($tmp[$name]);
                            $description = '';
                        }
                        $name = trim(strstr($ival, ':', true));
                        $description .= trim(substr(strstr($ival, ':'), 1));
                    }
                    else{
                        $description .= '<br>' . $ival;
                    }
                }
                if(!empty($name)){
                    $onep[$name] = $description;
                    unset($tmp[$name]);
                }
            }
            if(count($tmp) > 0){
                $onep = array_merge($onep, $tmp);
            }
            $data[] = $onep;
        }
        View::assign('data', $data);
        $this->start(Lang::lang('Template'));
        return View::view();
    }
    public function templateopen()
    {
        Validate::isPost()->postValue('template')->rule(V::must())->ifError(Lang::lang('Parameter error'))->success(function($data){
                $oldtemp = Info::getInfo('template');
                if(is_file(ROOT . 'public' . DS . 'template' . DS . $oldtemp . DS  . ucfirst($oldtemp) . '.php')){
                    Plugin::add('close', 'template/' . $oldtemp);
                }
                Info::setInfo('template', $data['template']);
                if(is_file(ROOT . 'public' . DS . 'template' . DS . $data['template'] . DS  . ucfirst($data['template']) . '.php')){
                    Plugin::add('open', 'template/' . $data['template']);
                }
                Cache::delAll();
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    public function templatedel()
    {
        Validate::isPost()->postValue('template')->rule(V::must())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $templatePath = ROOT . 'public' . DS . 'template' . DS . $data['template'];
            File::deleteFolder($templatePath);
            Response::writeEnd('ok');
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
    }
    public function uploadtemplate()
    {
        $filePath = ROOT . File::get('file')->checkExt(Request::getPost('allow'))->ifSaved()->getUploaded();
        $templateFile = ROOT . 'public' . DS . 'template';
        $zip = new \ZipArchive();
        if($zip->open($filePath, \ZipArchive::OVERWRITE || \ZIPARCHIVE::CREATE) === true){
            $zip->extractTo($templateFile);
            $zip->close();
            @unlink($filePath);
        }
        Response::writeEnd('ok');
    }
    public function clearcache()
    {
        Validate::isPost()->success(function(){
            Cache::delAll();
            Response::writeEnd('ok');
        });
        $this->start(Lang::lang('Clear cache'));
        return View::view();
    }
    public function uploadslide()
    {
        $sgid = Request::getPost('slidegroup');
        $slidegroup = new Slidegroup();
        $slidegroup->width = '';
        $slidegroup->height = '';
        $slidegroup->select('id = ?', $sgid)->limit(1)->getOne(function($data){
            $filePath = File::get('file')->checkExt(Request::getPost('allow'))->ifSaved()->getUploaded();
            Image::get(ROOT . str_replace('/', DS, $filePath))->resize($data['width'], $data['height']);
            Response::writeEnd(ltrim($filePath, 'public/'));
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
    public function latestversion()
    {
        if(Request::isPost()){
            $latestVersion = Info::latestVersion();
            if(empty($latestVersion)){
                $latestVersion = Env::get('YANZI_VERSION');
            }
            return $latestVersion;
        }
        return '';
    }
    public function comment()
    {
        $this->start(Lang::lang('Comment'));
        $comment = new Comment();
        $comment->id = '';
        $comment->cid = '';
        $comment->comment = '';
        $comment->creation = '';
        $comment->status = '';
        $user = new User();
        $user->name = '';
        $user->avatar = '';
        $comments = $comment->select()->order('id DESC')->join(
            $user->select()->equal('id', 'uid')
        )->paginate($this->per);
        foreach($comments['items'] as $key => $val){
            $comments['items'][$key]['comment'] = nl2br($val['comment']);
            $comments['items'][$key]['content'] = Url::url('content', ['id' => $val['cid']]);
        }
        View::assign('data', $comments);
        return View::view();
    }
    public function commentstatus()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))
            ->postValue('val')->rule(V::zeroOrOne())->ifError(Lang::lang('Parameter error'))->success(function($data){
                $comment = new Comment();
                $comment->status = $data['val'];
                $comment->update('id = ?', $data['id']);
                Response::writeEnd('ok');
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
    }
    public function commentdel()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $selfcomment = new Comment();
            $selfcomment->cid = '';
            $selfcomment->select('id = ?', $data['id'])->limit(1)->getOne(function($sdata) use($data){
                $comment = new Comment();
                $comment->id = $data['id'];
                $comment->delete();
                $updatecomment = new Comment();
                $updatecomment->parent = 0;
                $updatecomment->update('parent = ?', $data['id']);
                Cache::delGroup('comment_' . $sdata['cid']);
                Response::writeEnd('ok');
            });
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
    }
    public function message()
    {
        $message = new Message();
        $message->id = '';
        $message->name = '';
        $message->phone = '';
        $message->email = '';
        $message->other = '';
        $message->message = '';
        $message->creation = '';
        $messages = $message->select()->order('id DESC')->paginate($this->per);
        foreach($messages['items'] as $key => $val){
            $messages['items'][$key]['message'] = nl2br($val['message']);
        }
        View::assign('data', $messages);
        $this->start(Lang::lang('Message'));
        return View::view();
    }
    public function messagedel()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $message = new Message();
            $message->id = $data['id'];
            $message->delete();
            $pmessage = new Message();
            $pmessage->parent = $data['id'];
            $pmessage->delete();
            Response::writeEnd('ok');
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
    public function contribution()
    {
        $this->start(Lang::lang('Contributions without review'));
        $content = new Content();
        $content->id = '';
        $content->title = '';
        $content->creation = '';
        $content->image = '';
        $content->status = '';
        $content->view = '';
        $content->top = '';
        $content->recommend = '';
        $content->single = '';
        View::assign('data', $content->select('contribution = ? AND status = ?', [1,2])->order('id ASC')->paginate($this->per));
        return View::view();
    }
    public function reviewedcontribution()
    {
        $this->start(Lang::lang('Contributions reviewed'));
        $content = new Content();
        $content->id = '';
        $content->title = '';
        $content->alias = '';
        $content->creation = '';
        $content->image = '';
        $content->status = '';
        $content->view = '';
        $content->top = '';
        $content->recommend = '';
        $content->single = '';
        $data = $content->select('contribution = ? AND status <> ?', [1,2])->order('id ASC')->paginate($this->per);
        foreach($data['items'] as $key => $val){
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
    public function getcontribution()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $content = new Content();
            $content->content = '';
            $content->select('id = ?', $data['id'])->limit(1)->getOne(function($cdata){
                if(empty($cdata)){
                    Response::writeEnd(Lang::lang('Content not found'));
                }
                else{
                    Response::writeEnd($cdata['content']);
                }
            });
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
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
                            $modifycontent->template = Request::getPost('template');
                            $modifycontent->top = Request::getPost('top');
                            $modifycontent->recommend = Request::getPost('recommend');
                            $modifycontent->single = Request::getPost('single');
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
        $content->template = '';
        $content->top = '';
        $content->recommend = '';
        $content->model = '';
        $content->single = '';
        $content->select('id = ?', Request::getGet('id'))->limit(1)->getOne(function($data){
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
        });
        View::assign('templates', $this->getTemplates('content'));
        return View::view();
    }
    public function addad()
    {
        Validate::isPost()->postValue('name')->rule(V::must())->ifError(Lang::lang('Name must be filled'))->postValue('alias')->rule(V::must())->ifError(Lang::lang('Alias must be filled'))->rule(V::startLetterNumberUnder())->ifError(Lang::lang('The alias must start with a letter and can only use letters, numbers and underscores'))->success(function($data){
            $advertising = new Advertising();
            $advertising->id = '';
            $advertising->select('name = ? OR alias = ?', [$data['name'], $data['alias']])->limit(1)->getOne(function($sdata) use($data){
                if(count($sdata) > 0){
                    Response::writeEnd(Lang::lang('Name or alias already exists'));
                }
                else{
                    $image = Request::getPost('image');
                    $code = Request::getPost('code', false);
                    if(empty($image) && empty($code)){
                        Response::writeEnd(Lang::lang('Ad image and ad code must fill in one'));
                    }
                    else{
                        $addad = new Advertising();
                        $addad->name = $data['name'];
                        $addad->alias = $data['alias'];
                        $addad->image = $image;
                        $addad->url = Request::getPost('url');
                        $addad->description = Request::getPost('description');
                        $addad->code = $code;
                        $addad->insert();
                        Response::writeEnd('ok');
                    }
                }
            });
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
        $this->start(Lang::lang('Add ad'));
        return View::view();
    }
    public function allads()
    {
        $ad = new Advertising();
        $ad->id = '';
        $ad->name = '';
        $ad->alias = '';
        $ad->description = '';
        $ad->select()->order('id asc')->getSet(function($data){
            View::assign('data', $data);
        });
        $this->start(Lang::lang('All ads'));
        return View::view();
    }
    public function admodify()
    {
        Validate::isPost()->postValue('name')->rule(V::must())->ifError(Lang::lang('Name must be filled'))->postValue('alias')->rule(V::must())->ifError(Lang::lang('Alias must be filled'))->rule(V::startLetterNumberUnder())->ifError(Lang::lang('The alias must start with a letter and can only use letters, numbers and underscores'))->success(function($data){
            $advertising = new Advertising();
            $advertising->id = '';
            $advertising->select('id <> ? AND (name = ? OR alias = ?)', [Request::getPost('id'), $data['name'], $data['alias']])->limit(1)->getOne(function($sdata) use($data){
                    if(count($sdata) > 0){
                        Response::writeEnd(Lang::lang('Name or alias already exists'));
                    }
                    else{
                        $image = Request::getPost('image');
                        $code = Request::getPost('code', false);
                        if(empty($image) && empty($code)){
                            Response::writeEnd(Lang::lang('Ad image and ad code must fill in one'));
                        }
                        else{
                            $modifyAd = new Advertising();
                            $modifyAd->name = $data['name'];
                            $modifyAd->alias = $data['alias'];
                            $modifyAd->image = $image;
                            $modifyAd->url = Request::getPost('url');
                            $modifyAd->description = Request::getPost('description');
                            $modifyAd->code = $code;
                            $modifyAd->update('id = ?', Request::getPost('id'));
                            Response::writeEnd('ok');
                        }
                    }
                });
            })->failure(function($data){
                Response::writeEnd(Validate::errorToString($data));
            });
        $this->start(Lang::lang('Edit ads'));
        $ad = new Advertising();
        $ad->id = '';
        $ad->name = '';
        $ad->alias = '';
        $ad->image = '';
        $ad->url = '';
        $ad->description = '';
        $ad->code = '';
        $ad->select('id = ?', Request::getGet('id'))->limit(1)->getOne(function($data){
            View::assign('ad', $data);
        });
        return View::view();
    }
    public function addel()
    {
        Validate::isPost()->postValue('id')->rule(V::must())->ifError(Lang::lang('Parameter error'))->rule(V::positiveInt())->ifError(Lang::lang('Parameter error'))->success(function($data){
            $ad = new Advertising();
            $ad->image = '';
            $ad->select('id = ?', $data['id'])->limit(1)->getOne(function($sdata) use($data){
                $delad = new Advertising();
                $delad->id = $data['id'];
                $delad->delete();
                if(!empty($sdata['image'])){
                    $this->delimg($sdata['image']);
                }
                Response::writeEnd('ok');
            });
        })->failure(function($data){
            Response::writeEnd(Validate::errorToString($data));
        });
    }
}