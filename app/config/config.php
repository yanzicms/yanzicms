<?php
/**
 * Project: swuuws
 * Producer: swuuws [ http://www.swuuws.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.swuuws.com All rights reserved.
 */
return [
    /**
     * Whether to display error details.
     */
    'APP_DEBUG' => false,
    /**
     * Routing method: automatic or manual or both.
     */
    'ROUTE_TYPE' => 'both',
    /**
     * Which country's language is used.
     */
    'APP_LANG' => 'auto',
    /**
     * The language to use when no language pack is found.
     */
    'DEFAULT_LANG' => '',
    /**
     * The name of the get request.
     */
    'LANG_REQUEST' => 'lang',
    /**
     * Whether to display error message.
     */
    'ERROR_REPORT' => true,
    /**
     * Time zone.
     */
    'TIME_ZONE' => 'PRC',
    /**
     * Rewrite.
     */
    'OPENED_REWRITE' => 'auto',
    /**
     * Ways to log errors.
     */
    'LOG_LEVEL' => 'error',
    /**
     * Address suffix.
     */
    'ADDRESS_SUFFIX' => 'html',
    /**
     * Template file suffix.
     */
    'TEMPLATE_SUFFIX' => 'html',
    /**
     * Front border of template label.
     */
    'FRONT_BORDER' => '{',
    /**
     * The back border of the template label.
     */
    'BACK_BORDER' => '}',
    /**
     * Pagination frame.
     */
    'PAGINATE' => 'bootstrap4',
    /**
     * Maximum number of pagination displayed.
     */
    'PAGINATE_MAX_NUM' => 5,
    /**
     * Whether to display the previous page and the next page.
     */
    'PAGINATE_PREV_NEXT' => true,
    /**
     * Whether to always show paging.
     */
    'PAGINATE_ALWAYS_SHOW' => false,
    /**
     * Way of caching.
     */
    'CACHE_TYPE' => 'file',
    /**
     * Directory for storing resource files.
     */
    'DATA_PATH' => 'public/data',
    /**
     * Verification code type.
     */
    'CAPTCHA_TYPE' => 'separate',
    /**
     * Number of captcha text.
     */
    'CAPTCHA_NUMBER' => 5,
    /**
     * Captcha text size.
     */
    'CAPTCHA_FONTSIZE' => 30,
    /**
     * Captcha width.
     */
    'CAPTCHA_WIDTH' => 200,
    /**
     * Captcha height.
     */
    'CAPTCHA_HEIGHT' => 80,
    /**
     * Captcha background color.
     */
    'CAPTCHA_BGCOLOR' => '218,232,237',
    /**
     * Whether to add noise.
     */
    'CAPTCHA_HASNOISE' => true,
    /**
     * Number of noise.
     */
    'CAPTCHA_NOISENUMBER' => 30,
    /**
     * Whether to add interference line.
     */
    'CAPTCHA_HASINTERFERENCE' => true,
    'C_WEB_ROOT' => ''
];