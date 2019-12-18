<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\cms\validate;

use think\Validate;

/**
 * 广告验证器
 * @package app\cms\validate
 * @author 蔡伟明 <314013107@qq.com>
 */
class Advert extends Validate
{
    // 定义验证规则
    protected $rule = [
        'typeid|广告分类'   => 'require',
        'name|广告位名称'    => 'require|unique:cms_advert',
        'title'         => 'requireIf:ad_type,1',
        'size'          => 'integer',
        'width'         => 'integer',
        'height'        => 'integer',
        'image'           => 'require',
    ];

    // 定义验证提示
    protected $message = [
        'tagname.regex' => '广告位标识由小写字母、数字或下划线组成，不能以数字开头',
        'image'           => '请上传图片',
        'title'         => '文字内容不能为空',
        'size'          => '文字大小只能填写数字',
        'width'         => '宽度只能填写数字',
        'height'        => '高度只能填写数字',
    ];

    // 定义验证场景
    protected $scene = [
        'name' => ['name']
    ];
}
