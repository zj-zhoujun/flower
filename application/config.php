<?php
//应用配置文件,这里为所有模块共用配置，只列出与think不同的
return [
    // 应用调试模式
    'app_debug'              => false,
    // 应用Trace
    'app_trace'              => false,
    // 默认模块名
    'default_module'         => 'home',
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter'         => 'remove_xss,trim',
    //模板替换
    'view_replace_str'       => [
        '__PUBLIC__' => '/',
        '__STATIC__' => '/static',
        '__URL__'    => '',//此处在Base.php中重新动态替换了
    ],

    //模板设置
    'template'               => [
        // 模板引擎类型 支持 php think 支持扩展
        'type'         => 'Think',
        // 模板路径
        'view_path'    => '',
        // 模板后缀
        'view_suffix'  => 'html',
        // 模板文件名分隔符
        'view_depr'    => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin'    => '{',
        // 模板引擎普通标签结束标记
        'tpl_end'      => '}',
        // 标签库标签开始标记
        'taglib_begin' => '<',   //后台兼容2.0
        // 标签库标签结束标记
        'taglib_end'   => '>',   //后台兼容2.0
        // 视图根目录
        'view_base'    => APP_PATH.'tpl'.DS,
        //模版缓存
        'tpl_cache' => false
    ],
    //session
    'session'                => [
        'prefix'     => 'user',
        'type'       => '',
        'auto_start' => true,
    ],

    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => 'basic/success',
    'dispatch_error_tmpl'    => 'basic/error',

    // 显示错误信息
    'show_error_msg'         => true,

    //会员登录标识
    'USER_AUTH_NAME' => 'username',
    'USER_AUTH_ID'   => 'userid',

    //验证码设置
    'captcha'  => [
        // 验证码字体大小(px)
        'fontSize' => 30,
        // 是否画混淆曲线
        'useCurve' => false,
        // 验证码图片高度
        'imageH'   => 60,
        // 验证码图片宽度
        'imageW'   => 180,
        // 验证码位数
        'length'   => 3,
        // 验证成功后是否重置
        'reset'    => true,
    ],

    // SSO单点登录配置
    'sso_cache' =>  [
        // 使用复合缓存类型
        'type'  =>  'complex',
        // 默认使用的缓存
        'default'   =>  [
            // 驱动方式
            'type'   => 'File',
            // 缓存保存目录
            'path'   => CACHE_PATH,
        ],
        'sso'   =>  [
            // 驱动方式
            'type'   => 'File',
            // 缓存保存目录
            'path'   => CACHE_PATH.DS.'sso',
        ],
    ],

    'log' => [
        // 日志记录方式，内置 file socket 支持扩展
        'type'  => 'File',
        // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别
        'level' => ['error'],
    ],
];
