<?php
namespace app\common\logic;
use think\Model;
use think\Db;
/**
 *产品模型
 */
class Juhe extends Model
{
    //笑话信息
    public function Joke(){
        $key = 'b723475e7c92668f7f776ef2c6dfa393';
        $url = 'http://v.juhe.cn/joke/content/list.php?key='.$key.'&page=1&pagesize=10&sort=desc&time='.time();
        $list = curl_get($url);
        return $list;
    }
    //历史上的今天
    public function Toh(){
        $key = '3ada0044ecf39215f1f73363a347bf77';
        $url = 'http://api.juheapi.com/japi/toh?key='.$key.'&v=1.0&month='.date('m').'&day='.date('d');
        $list = curl_get($url);
        return $list;
    }

    //获取产品详情
    public function info($id){

    }
}
