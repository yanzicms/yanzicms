<?php
/**
 * Project: Yanzicms
 * Producer: Yanzicms [ http://www.Yanzicms.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.Yanzicms.com All rights reserved.
 */
namespace app\install;
use swuuws\Db;
use swuuws\File;
use swuuws\Lang;
use swuuws\M;
use swuuws\Mod;
use swuuws\Model;
use swuuws\Request;
use swuuws\Response;
use swuuws\View;
class Install extends Yanzicms
{
    public function index()
    {
        $folder = APP . 'config';
        if(is_dir($folder) && function_exists('chmod')){
            @chmod($folder, 0777);
        }
        $folder = APP . 'model';
        if(is_dir($folder) && function_exists('chmod')){
            @chmod($folder, 0777);
        }
        $folder = ROOT . 'runtime' . DS . 'cache';
        if(is_dir($folder) && function_exists('chmod')){
            @chmod($folder, 0777);
        }
        $folder = ROOT . 'runtime' . DS . 'log';
        if(is_dir($folder) && function_exists('chmod')){
            @chmod($folder, 0777);
        }
        $folder = ROOT . 'runtime' . DS . 'solve';
        if(is_dir($folder) && function_exists('chmod')){
            @chmod($folder, 0777);
        }
        return View::view();
    }
    public function testing()
    {
        if(Request::isPost()){
            Response::write($this->environment());
        }
    }
    private function environment()
    {
        $result = '';
        if(version_compare(phpversion(), '5.6.0', '<')){
            $result .= '<div>' . Lang::lang('PHP version requires more than 5.6') . '</div>';
        }
        if(!class_exists('pdo')){
            $result .= '<div>' . Lang::lang('Need to enable pdo support') . '</div>';
        }
        if(!extension_loaded('pdo_mysql')){
            $result .= '<div>' . Lang::lang('Pdo_mysql is not enabled') . '</div>';
        }
        if(!function_exists('curl_init')){
            $result .= '<div>' . Lang::lang('Need to enable curl support') . '</div>';
        }
        if(!function_exists('gd_info')){
            $result .= '<div>' . Lang::lang('GD support needs to be turned on') . '</div>';
        }
        if(!function_exists('session_start')){
            $result .= '<div>' . Lang::lang('Need to enable Session support') . '</div>';
        }
        $folders = [
            'app/config',
            'app/model',
            'runtime/cache',
            'runtime/log',
            'runtime/solve'
        ];
        foreach($folders as $val){
            $chkval = str_replace('/', DS, $val);
            $path = ROOT . $chkval;
            File::newFolder($path, true);
            $file = $path . DS . 'test.php';
            $re = file_put_contents($file, 'yanzicms');
            if($re === false){
                $result .= '<div>' . Lang::lang('Directory is not writable') . ': ' . $val . '</div>';
            }
            @unlink($file);
        }
        if(empty($result)){
            $result = 'ok';
        }
        return $result;
    }
    public function chkdb()
    {
        if(Request::isPost()){
            try{
                Db::createDb(Request::getPost('name'), 'mysql', Request::getPost('user'), Request::getPost('password'), Request::getPost('host'), Request::getPost('port'));
                $file = file_get_contents(APP . 'controller' . DS . 'install' . DS . 'dbtemp.php');
                $file = str_replace(['#host#', '#port#', '#database#', '#username#', '#password#', '#prefix#'], [Request::getPost('host'), Request::getPost('port'), Request::getPost('name'), Request::getPost('user'), Request::getPost('password'), Request::getPost('prefix')], $file);
                file_put_contents(APP . 'config' . DS . 'db.php', $file);
                Response::write('ok');
            }
            catch(\Exception $e){
                Response::write(Lang::lang('Database information error'));
            }
        }
    }
    public function creating()
    {
        $this->createModel();
    }
    private function createModel()
    {
        $defaulttime = '1000-01-01 00:00:00';
        Model::ifNoModel('user')->newModel('user')
            ->add('name')->type(M::varchar())->len(50)->notnull()->defaults('')->index('name')
            ->add('password')->type(M::varchar())->len(255)->notnull()->defaults('')
            ->add('nickname')->type(M::varchar())->len(50)->defaults('')
            ->add('email')->type(M::varchar())->len(100)->defaults('')->index('email')
            ->add('url')->type(M::varchar())->len(100)->defaults('')
            ->add('avatar')->type(M::varchar())->len(255)->defaults('')
            ->add('gender')->type(M::tinyint())->len(1)->defaults(0)
            ->add('birthday')->type(M::date())->defaults('1000-01-01')
            ->add('signature')->type(M::varchar())->len(500)->defaults('')
            ->add('creation')->type(M::datetime())->defaults($defaulttime)
            ->add('login')->type(M::datetime())->defaults($defaulttime)
            ->add('ip')->type(M::varchar())->len(255)->defaults('')
            ->add('activation')->type(M::varchar())->len(128)->defaults('')
            ->add('status')->type(M::tinyint())->len(1)->defaults(1)
            ->add('integral')->type(M::int())->len(11)->defaults(0)
            ->add('gold')->type(M::int())->len(11)->defaults(0)
            ->add('usertype')->type(M::smallint())->len(6)->defaults(10)
            ->create();
        Model::ifNoModel('dispose')->newModel('dispose')
            ->add('name')->type(M::varchar())->len(225)->notnull()->index('name')
            ->add('content')->type(M::text())
            ->add('autoload')->type(M::tinyint())->len(1)->defaults(0)
            ->add('control')->type(M::tinyint())->len(1)->defaults(0)
            ->create();
        Model::ifNoModel('attribution')->newModel('attribution')
            ->add('name')->type(M::varchar())->len(200)->notnull()
            ->add('alias')->type(M::varchar())->len(200)->defaults('')->index('alias')
            ->add('keyword')->type(M::varchar())->len(200)->defaults('')
            ->add('description')->type(M::text())
            ->add('parent')->type(M::int())->len(11)->unsigned()->defaults(0)
            ->add('menu')->type(M::tinyint())->len(1)->defaults(1)
            ->add('aslink')->type(M::tinyint())->len(1)->defaults(0)
            ->add('href')->type(M::varchar())->len(500)->defaults('')
            ->add('single')->type(M::varchar())->len(500)->defaults('')
            ->add('image')->type(M::varchar())->len(200)->defaults('')
            ->add('icon')->type(M::varchar())->len(700)->defaults('')
            ->add('template')->type(M::varchar())->len(200)->defaults('')
            ->add('fortemplate')->type(M::tinyint())->len(1)->defaults(0)
            ->add('show')->type(M::smallint())->len(6)->defaults(0)
            ->add('sort')->type(M::int())->len(11)->defaults(0)->index('sort')
            ->create();
        Model::ifNoModel('content')->newModel('content')
            ->add('uid')->type(M::int())->len(11)->unsigned()->defaults(0)->index('uid')
            ->add('aid')->type(M::int())->len(11)->unsigned()->defaults(0)->index('aid')
            ->add('keyword')->type(M::varchar())->len(200)->defaults('')
            ->add('title')->type(M::varchar())->len(500)->defaults('')
            ->add('alias')->type(M::varchar())->len(200)->defaults('')->index('alias')
            ->add('summary')->type(M::varchar())->len(600)->defaults('')
            ->add('content')->type(M::text())
            ->add('creation')->type(M::datetime())->defaults($defaulttime)
            ->add('modify')->type(M::datetime())->defaults($defaulttime)->index('modify')
            ->add('parent')->type(M::int())->len(11)->unsigned()->defaults(0)
            ->add('image')->type(M::varchar())->len(500)->defaults('')
            ->add('template')->type(M::varchar())->len(200)->defaults('')
            ->add('status')->type(M::tinyint())->len(1)->defaults(1)
            ->add('comment')->type(M::tinyint())->len(1)->defaults(1)
            ->add('finalcomment')->type(M::datetime())->defaults($defaulttime)
            ->add('view')->type(M::int())->len(11)->defaults(0)->index('view')
            ->add('praise')->type(M::int())->len(11)->defaults(0)
            ->add('favorites')->type(M::int())->len(11)->defaults(0)
            ->add('comment')->type(M::int())->len(11)->defaults(0)->index('comment')
            ->add('top')->type(M::tinyint())->len(1)->defaults(0)->index('top')
            ->add('recommend')->type(M::tinyint())->len(1)->defaults(0)->index('recommend')
            ->add('model')->type(M::int())->len(11)->unsigned()->defaults(0)
            ->add('single')->type(M::tinyint())->len(1)->defaults(0)->index('single')
            ->add('contribution')->type(M::tinyint())->len(1)->defaults(0)->index('contribution')
            ->create();
        Model::ifNoModel('models')->newModel('models')
            ->add('name')->type(M::varchar())->len(200)->defaults('')
            ->add('alias')->type(M::varchar())->len(200)->defaults('')->index('alias')
            ->add('description')->type(M::varchar())->len(600)->defaults('')
            ->add('contribution')->type(M::tinyint())->len(1)->defaults(0)
            ->add('parts')->type(M::text())
            ->create();
        Model::ifNoModel('comment')->newModel('comment')
            ->add('uid')->type(M::int())->len(11)->unsigned()->defaults(0)->index('uid')
            ->add('cid')->type(M::int())->len(11)->unsigned()->defaults(0)
            ->add('comment')->type(M::text())
            ->add('creation')->type(M::datetime())->defaults($defaulttime)
            ->add('modify')->type(M::datetime())->defaults($defaulttime)->index('modify')
            ->add('parent')->type(M::int())->len(11)->unsigned()->defaults(0)
            ->add('status')->type(M::tinyint())->len(1)->defaults(1)->index('status')
            ->create();
        Model::ifNoModel('slideshow')->newModel('slideshow')
            ->add('sgid')->type(M::int())->len(11)->unsigned()->defaults(0)
            ->add('name')->type(M::varchar())->len(200)->notnull()->defaults('')
            ->add('image')->type(M::varchar())->len(200)->defaults('')
            ->add('link')->type(M::varchar())->len(500)->defaults('')
            ->add('description')->type(M::varchar())->len(600)->defaults('')
            ->add('status')->type(M::tinyint())->len(1)->defaults(1)
            ->add('sort')->type(M::int())->len(11)->defaults(0)
            ->create();
        Model::ifNoModel('slidegroup')->newModel('slidegroup')
            ->add('name')->type(M::varchar())->len(200)->notnull()->defaults('')
            ->add('alias')->type(M::varchar())->len(200)->defaults('')->index('alias')
            ->add('width')->type(M::int())->len(11)->defaults(0)
            ->add('height')->type(M::int())->len(11)->defaults(0)
            ->add('description')->type(M::varchar())->len(600)->defaults('')
            ->create();
        Model::ifNoModel('link')->newModel('link')
            ->add('name')->type(M::varchar())->len(200)->notnull()->defaults('')
            ->add('url')->type(M::varchar())->len(500)->notnull()->defaults('')
            ->add('image')->type(M::varchar())->len(300)->defaults('')
            ->add('description')->type(M::varchar())->len(600)->defaults('')
            ->add('home')->type(M::tinyint())->len(1)->defaults(1)
            ->add('sort')->type(M::int())->len(11)->defaults(0)->index('sort')
            ->add('status')->type(M::tinyint())->len(1)->defaults(1)
            ->create();
        Model::ifNoModel('favorites')->newModel('favorites')
            ->add('uid')->type(M::int())->len(11)->unsigned()->defaults(0)->index('uid')
            ->add('cid')->type(M::int())->len(11)->unsigned()->defaults(0)->index('cid')
            ->add('creation')->type(M::datetime())->defaults($defaulttime)
            ->create();
        Model::ifNoModel('message')->newModel('message')
            ->add('uid')->type(M::int())->len(11)->unsigned()->defaults(0)
            ->add('name')->type(M::varchar())->len(200)->defaults('')
            ->add('phone')->type(M::varchar())->len(200)->defaults('')
            ->add('email')->type(M::varchar())->len(200)->defaults('')
            ->add('other')->type(M::varchar())->len(200)->defaults('')
            ->add('parent')->type(M::int())->len(11)->unsigned()->defaults(0)
            ->add('message')->type(M::text())
            ->add('creation')->type(M::datetime())->defaults($defaulttime)
            ->add('reply')->type(M::tinyint())->len(1)->defaults(0)
            ->create();
        Model::ifNoModel('advertising')->newModel('advertising')
            ->add('name')->type(M::varchar())->len(200)->notnull()->defaults('')
            ->add('alias')->type(M::varchar())->len(200)->defaults('')->index('alias')
            ->add('image')->type(M::varchar())->len(300)->defaults('')
            ->add('url')->type(M::varchar())->len(500)->notnull()->defaults('')
            ->add('description')->type(M::varchar())->len(600)->defaults('')
            ->add('code')->type(M::text())
            ->create();
        if(Request::hasPost('structure') && Request::getPost('structure') == 'on'){
            Model::ifNoModel('news')->newModel('news')
                ->add('cid')->type(M::int())->len(11)->unsigned()->defaults(0)
                ->add('author')->type(M::varchar())->len(1024)->defaults('')
                ->add('source')->type(M::varchar())->len(1024)->defaults('')
                ->add('address')->type(M::varchar())->len(1024)->defaults('')
                ->create();
            Model::ifNoModel('download')->newModel('download')
                ->add('cid')->type(M::int())->len(11)->unsigned()->defaults(0)
                ->add('author')->type(M::varchar())->len(1024)->defaults('')
                ->add('vendor')->type(M::varchar())->len(1024)->defaults('')
                ->add('demo')->type(M::varchar())->len(1024)->defaults('')
                ->add('version')->type(M::varchar())->len(1024)->defaults('')
                ->add('category')->type(M::text())
                ->add('environment')->type(M::text())
                ->add('softlang')->type(M::text())
                ->add('softtype')->type(M::text())
                ->add('authorization')->type(M::text())
                ->add('programlang')->type(M::text())
                ->add('download')->type(M::varchar())->len(1024)->defaults('')
                ->add('upload')->type(M::varchar())->len(500)->defaults('')
                ->create();
            Model::ifNoModel('photoalbum')->newModel('photoalbum')
                ->add('cid')->type(M::int())->len(11)->unsigned()->defaults(0)
                ->add('upload')->type(M::text())
                ->add('source')->type(M::varchar())->len(1024)->defaults('')
                ->add('address')->type(M::varchar())->len(1024)->defaults('')
                ->create();
            Model::ifNoModel('video')->newModel('video')
                ->add('cid')->type(M::int())->len(11)->unsigned()->defaults(0)
                ->add('upload')->type(M::varchar())->len(500)->defaults('')
                ->add('author')->type(M::varchar())->len(1024)->defaults('')
                ->add('source')->type(M::varchar())->len(1024)->defaults('')
                ->add('address')->type(M::varchar())->len(1024)->defaults('')
                ->create();
            Model::ifNoModel('article')->newModel('article')
                ->add('cid')->type(M::int())->len(11)->unsigned()->defaults(0)
                ->add('author')->type(M::varchar())->len(1024)->defaults('')
                ->add('source')->type(M::varchar())->len(1024)->defaults('')
                ->add('address')->type(M::varchar())->len(1024)->defaults('')
                ->create();
            Model::ifNoModel('category')->newModel('category')
                ->add('cid')->type(M::int())->len(11)->unsigned()->defaults(0)
                ->add('location')->type(M::text())
                ->add('phone')->type(M::varchar())->len(1024)->defaults('')
                ->add('email')->type(M::varchar())->len(1024)->defaults('')
                ->add('other')->type(M::varchar())->len(1024)->defaults('')
                ->add('address')->type(M::varchar())->len(1024)->defaults('')
                ->create();
        }
    }
}