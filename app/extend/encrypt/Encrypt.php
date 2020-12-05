<?php
/**
 * Project: Yanzicms
 * Producer: Yanzicms [ http://www.Yanzicms.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.Yanzicms.com All rights reserved.
 */
namespace extend\encrypt;
class Encrypt
{
    public static function hash($string)
    {
        return password_hash($string, PASSWORD_BCRYPT);
    }
    public static function verify($string, $hash)
    {
        return password_verify($string, $hash);
    }
}