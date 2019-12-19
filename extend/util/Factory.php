<?php
namespace util;

class Factory
{

    /**
     * 制造service logic 和 data对象
     * @param  string $key 要查找的参数key
     * @return 对应参数
     */
    public static function createModel($obj, $refresh=false)
    {
        //参数检测
        if(!isset($obj['name']))
            abort(500,'对象名没有设置');
        $name = $obj['name'];
        if(isset($obj['layer']))
            $lay_name = $obj['layer'];
        else
            $lay_name = 'model';

        //单例实现获取对象实例
        static $objs = [];
        if(!isset($objs[$lay_name]))
            $objs[$lay_name] = [];
        if(!isset($objs[$lay_name][$name]) || true===$refresh)
        {
            try {
                //先判断对应扩展目录是否存在对应文件
                $name = str_replace('/', '.', $name);
                //$file_pth = APP_PATH.'common/'.$lay_name.$name.'.php';
                //if(file_exists($file_pth))

                $mod = model($name, $lay_name);
            } catch (\Exception $e) {
                //未访问到某版本的接口时，访问默认(最新)接口
                /*try {
                    $lay_name = 'service';
                    $mod = model($name, $lay_name);
                } catch (\Exception $e) {
                    throw $e;//不处理异常继续抛
                }*/

                throw $e;//不处理异常继续抛
            }
            if(isset($obj['params']))
                $mod->setParams($obj['params']);
            $objs[$lay_name][$name] = $mod;

        }

        return $objs[$lay_name][$name];
    }


}
