<?php
namespace org;

class WechatShop extends WechatMsg
{
    //数据处理
    public function reply($data)
    {
        if('CLICK' == $data['Event']) {
            //点击菜单拉取消息时的事件推送
            $data['Content'] = $data['EventKey'];
            $this->data['Content'] = $data['Content'];
        }
        //关键字转换
        if(strtolower(substr($data['Content'], 0, 3)) == 'yyy') {
            $key = '摇一摇';
          // $yyyphone = substr($data['Content'], 3, 11);
        } elseif(substr($data['Content'], 0, 2) == '##') {
            $key = '微信墙';
           // $wallmessage = substr_replace($data['Content'], '', 0, 2);
        } else {
            $key = $data['Content'];
        }

        switch($key) {
            case '首页':
            case 'home':
            case '主页':
                return $this->shouye();
                break;
            default:
                //默认返回
                return $this->shouye();
        }
        //
    }

    //首页
    public function shouye()
    {
        $imgurl = HTTP_TYPE . CONF('web_address') . '/Public/Images/store.jpg';
        $url = request()->domain().'/wap/';

        return array(
            array(
                array(
                    '欢迎您的到来',
                    '竭诚为您服务',
                    $imgurl,
                    $url
                )
            ), 'news'
        );
    }
}

?>
