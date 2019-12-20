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

        //幻灯下方广告图
        $flower_speak = logic('Advert')->getAdvert('flower_speak');
        $this->assign('flower_speak',$flower_speak);
        //产品列表
        $product_list = logic('Product')->Lists();
        $this->assign('product_list',$product_list->all());
        //幻灯下方广告图
        $product_down = logic('Advert')->getAdvert('product_down');
        $this->assign('product_down',$product_down);
        return $this->fetch();
    }

    public function shop(){
        //产品列表
        $page = input('page')?:1;
        $product_list = logic('Product')->Lists('',1,$page);
        $this->assign('product_list',$product_list);
        return $this->fetch();
    }
    //产品详情
    public function detail(){
        $id = input('id');
        $info = Db::name('cms_product')->where('id',$id)->find();
        if(!$info){
            $this->error('参数错误！');
        }
        $info['image'] = get_files_path($info['image']);
        $info['thumb_image'] = get_thumb($info['image']);
        dump($info);exit;
        $this->assign('info',$info);
        return $this->fetch();
    }
}
