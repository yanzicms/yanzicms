<?php
/**
 * Project: Yanzicms
 * Producer: Yanzicms [ http://www.Yanzicms.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.Yanzicms.com All rights reserved.
 */
namespace app\install;
use swuuws\Env;
use swuuws\Request;
use swuuws\Url;
use swuuws\View;
class Yanzicms
{
    public function __construct()
    {
        if(is_file(APP . 'config' . DS . 'yanzicms.php')){
            Url::to('/');
        }
        $domain = Request::root();
        if(Env::get('C_WEB_ROOT') == ''){
            $domain .= 'public/';
        }
        View::assign('domain', $domain);
    }
}