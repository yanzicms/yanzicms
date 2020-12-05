<?php
/**
 * Project: swuuws
 * Producer: swuuws [ http://www.swuuws.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.swuuws.com All rights reserved.
 */
define('SWUUWS_START', microtime(true));
define('DS', DIRECTORY_SEPARATOR);
define('APP', dirname(__DIR__) . DS);
define('ROOT', dirname(APP) . DS);
require ROOT . '/vendor/autoload.php';
swuuws\Application::launch();