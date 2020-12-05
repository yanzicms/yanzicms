<?php
/**
 * Project: swuuws
 * Producer: swuuws [ http://www.swuuws.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.swuuws.com All rights reserved.
 */
use swuuws\Route;
Route::get('/', 'index/index')->alias('index');
Route::get('column/,id', 'index/column')->alias('column')->where('id', '\w+');
Route::get('content/,id', 'index/content')->alias('content')->where('id', '\w+');
Route::get('keyword/,word', 'index/keyword')->alias('keyword')->where('word', '\S+');
Route::get('admin', 'admin/index')->alias('admin');
Route::get('user_center', 'user_center/index')->alias('user_center');