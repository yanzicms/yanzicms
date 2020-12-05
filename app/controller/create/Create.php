<?php
/**
 * Project: Yanzicms
 * Producer: Yanzicms [ http://www.Yanzicms.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.Yanzicms.com All rights reserved.
 */
namespace app\create;
use extend\encrypt\Encrypt;
use model\Advertising;
use model\Attribution;
use model\Dispose;
use model\Models;
use model\Slidegroup;
use model\User;
use swuuws\Date;
use swuuws\V;
use swuuws\Validate;
use swuuws\Request;
use swuuws\Response;
use swuuws\Lang;
class Create
{
    public function creating()
    {
        if(Request::isPost()){
            Validate::isPost()->postValue('admin')->rule(V::must())->ifError(Lang::lang('Administrator account cannot be empty'))->rule(V::startLetterNumberHyphenUnder())->ifError(Lang::lang('The user name can only use letters, numbers, underscores and connecting lines, and start with a letter'))
                ->postValue('pwd')->rule(V::must())->ifError(Lang::lang('Password must be filled'))->rule(V::minlen(), 8)->ifError(Lang::lang('Password length cannot be less than 8 digits'))
                ->postValue('repwd')->rule(V::must())->ifError(Lang::lang('Repeat password must be filled'))->rule(V::equal(), V::getValue('pwd'))->ifError(Lang::lang('The password must be equal to the repeated password'))
                ->postValue('email')->rule(V::must())->ifError(Lang::lang('Email must be filled'))->rule(V::email())->ifError(Lang::lang('Email format error'))->success(function($data){
                    $user = new User();
                    $user->name = $data['admin'];
                    $user->nickname = substr(md5($data['admin']), -5);
                    $user->password = Encrypt::hash($data['pwd']);
                    $user->email = $data['email'];
                    $user->creation = Date::now();
                    $user->usertype = 0;
                    $user->insert();
                    $rewrite = Request::isRewrite();
                    $rewrite = $rewrite ? 1 : 0;
                    $dispose = new Dispose();
                    $dispose->insert([
                        ['name' => 'title', 'content' => Request::getPost('title'), 'autoload' => 1, 'control' => 1],
                        ['name' => 'subtitle', 'content' => '', 'autoload' => 1, 'control' => 1],
                        ['name' => 'keyword', 'content' => '', 'autoload' => 1, 'control' => 1],
                        ['name' => 'description', 'content' => '', 'autoload' => 1, 'control' => 1],
                        ['name' => 'template', 'content' => 'yanzi', 'autoload' => 1, 'control' => 0],
                        ['name' => 'record', 'content' => '', 'autoload' => 1, 'control' => 1],
                        ['name' => 'copyright', 'content' => '', 'autoload' => 1, 'control' => 1],
                        ['name' => 'statistics', 'content' => '', 'autoload' => 1, 'control' => 1],
                        ['name' => 'filtername', 'content' => '', 'autoload' => 0, 'control' => 1],
                        ['name' => 'comment', 'content' => 1, 'autoload' => 0, 'control' => 1],
                        ['name' => 'domain', 'content' => Request::root(), 'autoload' => 1, 'control' => 1],
                        ['name' => 'logo', 'content' => '', 'autoload' => 1, 'control' => 1],
                        ['name' => 'captcha', 'content' => 1, 'autoload' => 1, 'control' => 1],
                        ['name' => 'filtercomment', 'content' => '', 'autoload' => 0, 'control' => 1],
                        ['name' => 'rewrite', 'content' => $rewrite, 'autoload' => 1, 'control' => 1],
                        ['name' => 'allowLogin', 'content' => 1, 'autoload' => 1, 'control' => 1],
                        ['name' => 'closeSlide', 'content' => 0, 'autoload' => 0, 'control' => 1],
                        ['name' => 'icon', 'content' => '', 'autoload' => 1, 'control' => 1],
                        ['name' => 'everyPageShows', 'content' => 10, 'autoload' => 1, 'control' => 1],
                        ['name' => 'closeSitemap', 'content' => 0, 'autoload' => 0, 'control' => 1],
                        ['name' => 'closeRSS', 'content' => 0, 'autoload' => 0, 'control' => 1],
                        ['name' => 'closeSite', 'content' => 0, 'autoload' => 0, 'control' => 1],
                        ['name' => 'buildTime', 'content' => Date::now(), 'autoload' => 0, 'control' => 0]
                    ]);
                    $slidegroup = new Slidegroup();
                    $slidegroup->insert([
                        ['name' => '幻灯1组500×310', 'alias' => 'slide1', 'width' => 500, 'height' => 310]
                    ]);
                    $advertising = new Advertising();
                    $advertising->insert([
                        ['name' => '首页横幅', 'alias' => 'homebanner', 'description' => '首页横幅广告']
                    ]);
                    if(Request::hasPost('structure') && Request::getPost('structure') == 'on'){
                        $models = new Models();
                        $models->insert([
                            ['name' => '新闻', 'alias' => 'news', 'contribution' => 0, 'parts' => '[{"partname":"作者","partalias":"author","parttype":"varchar","defaults":""},{"partname":"信息来源","partalias":"source","parttype":"varchar","defaults":""},{"partname":"来源地址","partalias":"address","parttype":"varchar","defaults":""}]'],
                            ['name' => '下载', 'alias' => 'download', 'contribution' => 1, 'parts' => '[{"partname":"软件作者","partalias":"author","parttype":"varchar","defaults":""},{"partname":"厂商主页","partalias":"vendor","parttype":"varchar","defaults":""},{"partname":"演示地址","partalias":"demo","parttype":"varchar","defaults":""},{"partname":"软件版本","partalias":"version","parttype":"varchar","defaults":""},{"partname":"软件分类","partalias":"category","parttype":"singlechoice","defaults":"Web应用，手机app，小程序，服务器类，网络软件，应用软件，系统工具，图形图像，多媒体类，安全相关，其他"},{"partname":"运行环境","partalias":"environment","parttype":"multiplechoice","defaults":"Windows7，Windows10，Windows server，Unix，Linux，MAC OS，其他"},{"partname":"软件语言","partalias":"softlang","parttype":"singlechoice","defaults":"简体中文，繁体中文，英文，多国语言"},{"partname":"软件类型","partalias":"softtype","parttype":"singlechoice","defaults":"国产软件，国外软件，汉化软件"},{"partname":"授权形式","partalias":"authorization","parttype":"singlechoice","defaults":"共享软件，开源软件，免费软件，自由软件，试用软件，演示软件，商业软件"},{"partname":"编程语言","partalias":"programlang","parttype":"multiplechoice","defaults":"Python，Java，JavaScript，C#，PHP，C/C  ，R，Objective-C，Swift，Matlab，TypeScript，Kotlin，Ruby，Go，Scala，Visual Basic，其他语言"},{"partname":"下载地址","partalias":"download","parttype":"varchar","defaults":""},{"partname":"上传软件","partalias":"upload","parttype":"annex","defaults":""}]'],
                            ['name' => '相册', 'alias' => 'photoalbum', 'contribution' => 0, 'parts' => '[{"partname":"上传图集","partalias":"upload","parttype":"multiplepictures","defaults":""},{"partname":"来源","partalias":"source","parttype":"varchar","defaults":""},{"partname":"来源地址","partalias":"address","parttype":"varchar","defaults":""}]'],
                            ['name' => '视频', 'alias' => 'video', 'contribution' => 0, 'parts' => '[{"partname":"上传视频","partalias":"upload","parttype":"video","defaults":""},{"partname":"视频作者","partalias":"author","parttype":"varchar","defaults":""},{"partname":"视频来源","partalias":"source","parttype":"varchar","defaults":""},{"partname":"来源地址","partalias":"address","parttype":"varchar","defaults":""}]'],
                            ['name' => '文章', 'alias' => 'article', 'contribution' => 1, 'parts' => '[{"partname":"作者","partalias":"author","parttype":"varchar","defaults":""},{"partname":"来源","partalias":"source","parttype":"varchar","defaults":""},{"partname":"来源地址","partalias":"address","parttype":"varchar","defaults":""}]'],
                            ['name' => '分类信息', 'alias' => 'category', 'contribution' => 1, 'parts' => '[{"partname":"所在地","partalias":"location","parttype":"singlechoice","defaults":"黄浦区，徐汇区，长宁区，静安区，普陀区，虹口区，杨浦区，宝山区，闵行区，嘉定区，松江区，青浦区，奉贤区，金山区，浦东新区，崇明区，其他"},{"partname":"联系电话","partalias":"phone","parttype":"varchar","defaults":""},{"partname":"联系邮箱","partalias":"email","parttype":"varchar","defaults":""},{"partname":"其他联系方式","partalias":"other","parttype":"varchar","defaults":""},{"partname":"联系地址","partalias":"address","parttype":"varchar","defaults":""}]']
                        ]);
                        $attribution = new Attribution();
                        $attribution->insert([
                            ['name' => '新闻', 'alias' => 'news', 'parent' => 0, 'menu' => 1, 'fortemplate' => 1],
                            ['name' => '下载', 'alias' => 'download', 'parent' => 0, 'menu' => 1, 'fortemplate' => 1],
                            ['name' => '相册', 'alias' => 'photoalbum', 'parent' => 0, 'menu' => 1, 'fortemplate' => 1],
                            ['name' => '视频', 'alias' => 'video', 'parent' => 0, 'menu' => 1, 'fortemplate' => 1],
                            ['name' => '文章', 'alias' => 'article', 'parent' => 0, 'menu' => 1, 'fortemplate' => 1],
                            ['name' => '分类信息', 'alias' => 'category', 'parent' => 0, 'menu' => 1, 'fortemplate' => 1],
                            ['name' => '国内新闻', 'alias' => 'domestic', 'parent' => 1, 'menu' => 1, 'fortemplate' => 1],
                            ['name' => '国际新闻', 'alias' => 'international', 'parent' => 1, 'menu' => 1, 'fortemplate' => 1],
                            ['name' => '娱乐新闻', 'alias' => 'entertainment', 'parent' => 1, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '体育新闻', 'alias' => 'sports', 'parent' => 1, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '系统软件', 'alias' => 'system', 'parent' => 2, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '网络工具', 'alias' => 'network', 'parent' => 2, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '安全相关', 'alias' => 'safety', 'parent' => 2, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '媒体工具', 'alias' => 'media', 'parent' => 2, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '明星风采', 'alias' => 'celebrity', 'parent' => 3, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '自然风景', 'alias' => 'natural', 'parent' => 3, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '动漫图片', 'alias' => 'anime', 'parent' => 3, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '影视', 'alias' => 'film', 'parent' => 4, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '短视频', 'alias' => 'shortvideo', 'parent' => 4, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '小说', 'alias' => 'novel', 'parent' => 5, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '散文', 'alias' => 'prose', 'parent' => 5, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '诗歌', 'alias' => 'poetry', 'parent' => 5, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '房屋信息', 'alias' => 'houses', 'parent' => 6, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '跳蚤市场', 'alias' => 'flea', 'parent' => 6, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '同城生活', 'alias' => 'city', 'parent' => 6, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '求职招聘', 'alias' => 'job', 'parent' => 6, 'menu' => 1, 'fortemplate' => 0],
                            ['name' => '房屋求租', 'alias' => 'demand', 'parent' => 23, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '房屋出租', 'alias' => 'rent', 'parent' => 23, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '房屋求购', 'alias' => 'purchase', 'parent' => 23, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '房屋出售', 'alias' => 'sale', 'parent' => 23, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '办公用房', 'alias' => 'office', 'parent' => 23, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '旺铺门面', 'alias' => 'facade', 'parent' => 23, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '电脑配件', 'alias' => 'accessories', 'parent' => 24, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '电器数码', 'alias' => 'digital', 'parent' => 24, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '通讯产品', 'alias' => 'communication', 'parent' => 24, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '居家用品', 'alias' => 'houseware', 'parent' => 24, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '本地新闻', 'alias' => 'localnews', 'parent' => 25, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '购物打折', 'alias' => 'discount', 'parent' => 25, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '旅游活动', 'alias' => 'tourism', 'parent' => 25, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '便民告示', 'alias' => 'notice', 'parent' => 25, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '工程技术', 'alias' => 'engineering', 'parent' => 26, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '财务会计', 'alias' => 'accounting', 'parent' => 26, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '餐饮行业', 'alias' => 'food', 'parent' => 26, 'menu' => 0, 'fortemplate' => 0],
                            ['name' => '经营管理', 'alias' => 'management', 'parent' => 26, 'menu' => 0, 'fortemplate' => 0]
                        ]);
                    }
                    file_put_contents(APP . 'config' . DS . 'yanzicms.php', 'yanzicms');
                    Response::write('ok');
                })->failure(function($data){
                    Response::write(Validate::errorToString($data));
                });
        }
    }
}