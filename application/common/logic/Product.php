<?php
namespace app\common\logic;
use think\Model;
use think\Db;
/**
 *产品模型
 */
class Product extends Model
{
    //根据类型获取广告列表
    public function Lists($type='',$limit=10){
        if($type){
            $list = Db::name('cms_product')
                ->alias('a')
                ->where(['a.typeid'=>$type])
                ->field('a.*')
                ->limit($limit)
                ->select();
        }else{
            $list = Db::name('cms_product')
                ->alias('a')
                ->join('admin_attachment at','a.image=at.id')
                ->field('a.*')
                ->limit($limit)
                ->select();
        }

        return $list;
    }
}
