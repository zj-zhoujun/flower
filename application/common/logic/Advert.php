<?php
namespace app\common\logic;
use think\Model;
use think\Db;
/**
 *广告模型
 */
class Advert extends Model
{
    //根据类型获取广告列表
    public function getAdvert($type){
        $list = Db::name('cms_advert')
            ->alias('a')
            ->join('admin_attachment at','a.image=at.id')
            ->where(['a.typeid'=>$type])
            ->field('a.*,at.path as image')
            ->select();
        return $list;
    }
}
