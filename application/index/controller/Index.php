<?php
namespace app\index\controller;
use think\Db;
/**
 * 前台首页控制器
 * @package app\index\controller
 */
class Index extends Home
{
    public function index()
    {
        //顶部幻灯
        $slide_list = logic('Advert')->getAdvert('top_slide');
        $this->assign('slide_list',$slide_list);
        //网站logo
        $this->assign('web_site_logo',get_file_path(config('web_site_logo')));
        //幻灯下方广告图
        $flower_speak = logic('Advert')->getAdvert('flower_speak');
        $this->assign('flower_speak',$flower_speak);
        //产品列表
        $product_list = logic('Product')->Lists();
        $this->assign('product_list',$product_list);
        return $this->fetch();
    }
}
