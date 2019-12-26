<?php
/**
 * Desc: 微信消息
 * Date: 2017/2/10
 */
namespace app\index\controller;

use org\Pscws\PSCWS4;
use org\WechatMsg;
use think\Db;

class Wechat extends Index
{
    private $token;
    private $signature;
    private $timestamp;
    private $nonce;

    //数据
    private $data = [];

    //日志
    private $log_id = 0;

    //关注回复配置
    public $areply;

    /**
     * 初始化
     */
    public function _initialize()
    {
        //读取关注回复相关配置
        $this->areply = Db::name('wechat_reply_subscribe')->column('name,text');

        //接收参数
        $this->token = input('token', '');
        $this->signature = input('signature', '');
        $this->timestamp = input('timestamp', '');
        $this->nonce = input('nonce', '');

        //日志
        if(1) { //关闭日志 改这里
            $_log = [];
            $_log['token'] = $this->token;
            $_log['signature'] = $this->signature;
            $_log['timestamp'] = $this->timestamp;
            $_log['nonce'] = $this->nonce;
            $_log['w_time'] = time();
            $this->log_id = Db::name('wechat_msg_log')->insertGetId($_log);
        }
    }

    /**
     * 平台微信通知入口
     */
    public function index()
    {
        //实例化
        $msg = new WechatMsg();

        //验签
        $rs = $msg->auth($this->signature, ['token'=>$this->token,'timestamp'=>$this->timestamp,'nonce'=>$this->nonce]);
        if(true !== $rs) {
            $this->log('验签失败');
            exit;
        }
        //GET返回 微信后台配置时
        if(IS_GET) {
            echo input('get.echostr');
            exit;
        }

        //获取数据
        $rs = $msg->request();
        if(!empty($rs['err'])) {
            $this->log($rs['err']);
            exit;
        }
        $this->data = $rs['data'];

        //处理消息
        list($content, $type) = $this->msgProcess($this->data);
        if(empty($content) || empty($type)) {
            $this->log('未匹配');
            exit;
        } else {
            //响应数据
            $rs = $msg->response($content, $type);
            if(!empty($rs['err'])) {
                $this->log($rs['err']);
                exit;
            }

            //响应微信
            $this->log($rs['data']);
            exit($rs['data']);
        }
    }

    /**
     * 消息处理
     * @param array $data 消息数组
     * @return array
     */
    private function msgProcess($data = [])
    {
        if(empty($data['MsgType'])) {
            $this->log('MsgType 错误');
            exit;
        }

        if('event' == $data['MsgType']) {
            /*事件消息*/
            if('CLICK' == $data['Event']) {
                //点击菜单拉取消息时的事件推送
                //$data['Content'] = $data['EventKey'];
                //$this->data['Content'] = $data['Content'];
                //进行关键字匹配
                return $this->keyword(trim($data['EventKey']));
            } elseif('SCAN' == $data['Event']) {
                /*已关注后扫码的事件*/
                //$this->scanlog($data['EventKey']);  //保存扫描二维码日志
            } elseif('subscribe' == $data['Event']) {
                /*关注事件*/
                //更新互联信息
                $sub = ['subscribe' => 1, 'sub_time' => systemTime()];
                Db::name('social')->where(['type' => 'weixin', 'openid' => $data['FromUserName']])->update($sub);

                //若是扫码关注的
                /*if(isset($data['EventKey'])) { //若是扫码
                    if(strtolower(substr($data['EventKey'], 0, 8)) == 'qrscene_') { //若是关注
                        $this->scanlog($data['EventKey']);
                    }
                }*/

                if(!empty($this->areply['weishang_gz_keyword'])) {
                    //启用关注关键字回复
                    $rs = $this->keyword($this->areply['weishang_gz_keyword']);
                    if($rs) {
                        return $rs;
                    }
                }

                //返回默认关注回复
                return array($this->areply['weishang_gz_huifu']??'', 'text');
            } elseif('unsubscribe' == $data['Event']) {
                /*取消关注事件*/
                //更新互联信息
                $unsub = array('subscribe' => 0, 'unsub_time' => systemTime());
                Db::name('social')->where(['type' => 'weixin', 'openid' => $data['FromUserName']])->update($unsub);
                //$this->setFans(array('openid' => $data['FromUserName']), 'del');
                return array('取消关注', 'text');
            } elseif($data['Event'] == 'LOCATION') {
                $openid = $data['FromUserName'];
                $latitude = $data['Latitude'];
                $longitude = $data['Longitude'];
                Db::name('wechat_user')->insert(array('openid' => $openid, 'latitude' => $latitude, 'longitude' => $longitude));
                $dis = $this->getdistance($latitude, $longitude, 35.115428, 118.353432);
                return array($dis, 'text');
                //地理位置
                // return array('location','text');
            } else {
                $this->log('不处理: '.($data['Event']??''));
                exit();
            }
        } else {
            $sendToDmBrain = false;
            /*普通消息*/
            if('voice' == $data['MsgType']) {
                //接收语音
                //$data['Content'] = $data['Recognition'];
                //$this -> data['Content'] = $data['Content'];
            } else {
                $_data = [];
                $_data['ToUserName'] = $data['ToUserName'];
                $_data['FromUserName'] = $data['FromUserName'];
                $_data['CreateTime'] = $data['CreateTime'];
                $_data['MsgType'] = $data['MsgType'];
                $_data['Content'] = $data['Content'] ?? '';
                $_data['MsgId'] = $data['MsgId'];
                Db::name('wechat_user_message')->insert($_data); //将文字信息存储为提问
                if (!empty($data['Content'])) {
                    try {
                        $braiker_url = conf('braiker_url');
                        if ($braiker_url) {
                            $msg = json_encode([
                                'openid'    => $data['FromUserName'],
                                'content'   => $data['Content'] ?? '',
                                'app_id'    => conf('weishang_appid'),
                                'send_time' => $data['CreateTime'],
                                'msg_id'    => $data['MsgId']
                            ]);
                            $rs = curl_request($braiker_url, ['msg'=>$msg]);
                            if(!empty($rs['err'])) {
                                trace("exec err: ".$rs['err']);
                            } else {
                                $sendToDmBrain = true;
                            }
                        }
                    } catch (\Error $e) {
                        trace("send to braiker err: ".$e->getMessage());
                    } catch (\Exception $e) {
                        trace("send to braiker exc: ".$e->getMessage());
                    }
                }
            }

            //微信多客服
            $dkf_key_word = CONF('ADDON_WXCS_KEYWORD') ?? 'wxdkf';
            if($data['MsgType'] == $dkf_key_word) {
                $this->wxduokefu();
                return array("您已进入多客服对话。", 'text');
            }

            //关键字转换
            if(strtolower(substr($data['MsgType'], 0, 3)) == 'yyy') {
                $key = '摇一摇';
                //$yyyphone = substr($data['MsgType'], 3, 11);
            } elseif(substr($data['MsgType'], 0, 2) == '##') {
                $key = '微信墙';
                //$wallmessage = substr_replace($data['MsgType'], '', 0, 2);
            } else {
                $key = $data['Content'] ?? '';
            }

            //分解 暂时不需要
            /*$Pin = new GetPin();
            $function_list=explode(',',$this->areply['weishang_auto_function_list']);
            $tags = $this->get_tags($key);
            $back = explode(',', $tags);
            foreach ($back as $keydata => $data){
                $string = $Pin->Pinyin($data);
                if(in_array($string, $datafun) && $string){
                    //$check = $this->user('connectnum');
                    if ($string == 'fujin') {
                       // $this->recordLastRequest($key);
                    }
                    unset($back[$keydata]);
                    eval('$return= $this->' . $string . '($back);'); //执行不同的函数
                    continue;
                }
            }*/

            //进行关键字匹配
            return $this->keyword(trim($key), $sendToDmBrain);
        }
    }

    /**
     * 匹配关键字回复
     * @param string $key 关键字
     * @return array
     */
    public function keyword($key = '', $sendToDmBrain = false)
    {
        //特殊关键字处理
        if(in_array($key, ['首页', '主页', 'home' ])) {
            return $this->home($key);
        } elseif('签到' == $key) {
            /*$x = 118.353432;
            $y = 35.115428;
            $gps = bdmap2gps($x,$y);
            $dis = getdistance('118.343109,35.110172',$gps);
            return array($dis,'text');*/
            $result = $this->signin($this->data['FromUserName']);
            if(!$result['status']) {
                // return array($result['msg'],'text');
                return array(htmlspecialchars_decode(str_replace(array('{token}', '{wechat_id}'), array($this->token, $this->data['FromUserName']), $result['msg'])), 'text');
            }
        } else {
            //根据关键字规则去匹配
            $map = [];
            $map['keyword'] = ['like', '%'.$key.'%'];
            $rs = Db::name('wechat_reply_keyword')->where($map)->order('id desc')->find();
            if($rs) { //匹配到规则
                switch($rs['type']) {
                    case 'image': //需回复图文
                        $img_db = Db::name('wechat_reply_keyword_image');
                        $ext = $img_db->field('id,info,image,url,title')->limit(9)->order('id DESC')->where($map)->select();
                        $idsWhere = 'id in (';
                        $comma = '';
                        $return = [];
                        if($ext) {
                            foreach($ext as  $infot) {
                                $idsWhere .= $comma.$infot['id'];
                                $comma = ',';
                                if($infot['url']) { //有url
                                    //判断连接是否带http
                                    if(strpos($infot['url'], 'http') !== FALSE) {
                                        $url = html_entity_decode($infot['url']);
                                        $url = str_replace('{token}', $this->token, $url);
                                        if(isset($this->data['wecha_id'])) {
                                            $url = str_replace('{wecha_id}', $this->data['wecha_id'], $url);
                                        }
                                    } else {
                                        $url = $this->getFuncLink($infot['url']);
                                    }
                                } else { //无url
                                    //文章详细信息
                                    $url = request()->domain().'/wap/#wechatreply?token='.$this->token.'&id='.$infot['id'].'&wecha_id='.$this->data['FromUserName'];
                                }
                                //判断上传的地址是否正确  此处不直接用oss是否开启，是防止之前还有些图片是本地上传的
                                if($infot['image']){
                                    if(!preg_match('/(http|https):\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is',$infot['image'])){
                                        $infot['image'] = request()->domain().'/'.$infot['image'];
                                    }
                                }
                                $return[] = array($infot['title'], $infot['info'], $infot['image'], $url);
                            }
                            $idsWhere .= ')';
                            $img_db->where($idsWhere)->setInc('num');
                        }
                        return array($return, 'news');
                        break;
                    case 'text': //需回复文本
                        $db = Db::name('wechat_reply_keyword_text');
                        $info = $db->order('id desc')->find($rs['ext_id']);
                        $db->where('id', $rs['ext_id'])->setInc('num');
                        return array(htmlspecialchars_decode(str_replace(array('{token}', '{wechat_id}'), array($this->token, $this->data['FromUserName']), $info['info'])), 'text');
                        break;
                    case 'Product': //商品
                        $infos = Db::name('product')->limit(9)->order('id desc')->where($map)->select();
                        $return = [];
                        if($infos) {
                            $return = array();
                            foreach($infos as $info) {
                                $return[] = array($info['name'], strip_tags(htmlspecialchars_decode($info['keyword'])), rtrim(CONF('weishang_url'), '/').str_replace('./', '/', $info['image']), rtrim(CONF('weishang_url'), '/').'/wap.php?g=Home&m=Product&a=details&token='.$this->token.'&wecha_id='.$this->data['FromUserName'].'&id='.$info['id'].'&sgssz=mp.weixin.qq.com');
                            }
                        }
                        return array($return, 'news');
                        break;
                    default:
                        if ($sendToDmBrain === true) {
                            exit();
                        }
                        if(!empty($this->areply['weishang_wuren_huifu'])) {
                            return array($this->areply['weishang_wuren_huifu'], 'text');
                        } else {
                            return array('回复帮助，可了解所有功能', 'text');
                        }
                }
            } else { //未匹配到规则
                if ($sendToDmBrain === true) {
                    exit();
                }
                if(1) {
                    if(!empty($this->areply['weishang_wuren_huifu'])) {
                        return array($this->areply['weishang_wuren_huifu'], 'text');
                    } else {
                        return array('回复帮助，可了解所有功能', 'text');
                    }
                } else {
                    return [];
                }
                /*//再匹配图文回复的标题
                $map['title'] = ['like', '%'.$key.'%'];
                $back = Db::name('wechat_reply_keyword_image')->field('id,info,image,url,title,keyword')->order('id desc')->where($map)->select();
                if($back) {
                    $return = [];
                    foreach($back as $keya => $infot) {
                        if($infot['url'] != false) {
                            //判断连接是否带http
                            if(!(strpos($infot['url'], 'http') === FALSE)) {
                                $url = html_entity_decode($infot['url']);
                                $url = str_replace('{token}', $this->token, $url);
                                $url = str_replace('{wecha_id}', $this->data['wecha_id'], $url);
                            } else {
                                $url = $this->getFuncLink($infot['url']);
                            }
                        } else {
                            //文章详细信息
                            $url = request()->domain().'/wap/#wechatreply?token='.$this->token.'&id='.$infot['id'].'&wecha_id='.$this->data['FromUserName'];
                        }
                        $return[] = array($infot['title'], $infot['info'], $infot['image'], $url);
                    }
                    return array($return, 'news');
                } else {
                    //再匹配文本回复的内容
                    $text_key['title'] = array('like', '%'.$key.'%');
                    $back = Db::name('wechat_reply_keyword_text')->field('id,info,title')->order('id desc')->where($text_key)->select();
                    if($back) {
                        $return = [];
                        foreach($back as $keya => $infot) {
                            if($infot['url'] != false) {
                                //判断连接是否带http
                                if(!(strpos($infot['url'], 'http') === FALSE)) {
                                    $url = html_entity_decode($infot['url']);
                                    $url = str_replace('{token}', $this->token, $url);
                                    $url = str_replace('{wecha_id}', $this->data['wecha_id'], $url);
                                } else {
                                    $url = $this->getFuncLink($infot['url']);
                                }
                            } else {
                                //文章详细信息
                                $url = request()->domain().'/wap/#wechatreply?token='.$this->token.'&id='.$infot['id'].'&wecha_id='.$this->data['FromUserName'];
                            }
                            $return[] = array($infot['title'], $infot['info'], $url);
                        }
                        return array($return, 'news');
                    } else {
                        if($this->areply['weishang_wuren_huifu']) {
                            return array($this->areply['weishang_wuren_huifu'], 'text');
                        } else {
                            return array('回复帮助，可了解所有功能', 'text');
                        }
                    }
                }*/
            }
        }
    }

    /**
     * 匹配商城首页
     * @return array
     */
    public function home($key = '')
    {
        if(empty($this->areply['weishang_home_huifu_title'])) {
            return array('商家未做首页配置，请稍后再试', 'text');
        } else {
            //默认跳转首页
            $url = request()->domain().'/wap/';

            //图片地址
            $imgurl = $this->areply['weishang_wuren_huifu_image']??'';
            if(!preg_match('/(http|https):\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is',$imgurl)){
                $imgurl = request()->domain().$this->areply['weishang_wuren_huifu_image']??'';
            }
            //$url = HTTP_TYPE.$url;  //修改首页重复拼接http或https的bug，上边的request()->domain()已经含有了，这里不能在拼接了
            return [
                [
                    [
                        $this->areply['weishang_home_huifu_title']??'',
                        $this->areply['weishang_home_huifu_info']??'',
                        $imgurl,
                        $url
                    ]
                ],
                'news'
            ];
        }
    }

    /**
     * 匹配签到
     * @param string $weixin_openid openid
     * @return array
     */
    public function signin($weixin_openid = '')
    {
        $result = array('status' => false, 'msg' => '');
        $social_info = Db::name('social')->where(['type'=>'weixin', 'openid'=>$weixin_openid])->order('id desc')->find();

        if(!empty($social_info)) {
            $sign = Db::name('wechat_sign');
            $where = array();
            $where['uid'] = $social_info['user_id'];
            $where['signdate'] = array('egt', strtotime(date('Ymd')));
            $hasresult = $sign->where($where)->find();

            if(!$hasresult) {
                $username = Db::name('user')->where(array('id' => $social_info['user_id']))->value('nickname');
                $data = array();
                $data['uid'] = $social_info['user_id'];
                $data['username'] = $username;
                $data['signdate'] = systemTime();
                $data['wxopenid'] = $weixin_openid;
                $sign->insert($data);
                $result['status'] = true;
                return $result;
            } else {
                $text = '今天已经签到了，请明天再来。。。';
            }
        } else {
            $text = '您还没有加入我们，赶快注册吧';
        }
        $result['msg'] = $text;
        return $result;
    }

    //////////todo//////////

    //店铺微信通知
    /*public function shop_notify()
    {
        $this->siteUrl = CONF('weishang_url');
        $this->token = input('token');

        $weixin = new WechatShop($this->token);
        $this->data = $weixin->request();
        if($this->data) {
            list($content, $type) = $weixin->reply($this->data);
            $weixin->response($content, $type);
        }
    }*/

    //增加用户
    public function adddUserInfo()
    {
        $access_token = logic('Wechat')->getWechatBasicAccesstoken();

        $url2 = 'https://api.weixin.qq.com/cgi-bin/user/info?openid='.$this->data['FromUserName'].'&access_token='.$access_token;
        $classData = json_decode($this->curlGet($url2));
        $u_subscribe = $classData->subscribe ? $classData->subscribe : '0';
        $udata['wxapi_wecha_id'] = $classData->openid;
        $udata['subscribe_time'] = $classData->subscribe_time;
        $udata['subscribe'] = $u_subscribe;
        $udata['add_time'] = systemTime();
        $udata['role_array'] = '["User"]';
        $udata['status'] = 1;
        $info_data['nickname'] = str_replace("'", '', $classData->nickname);
        $info_data['sex'] = $classData->sex;
        $info_data['city'] = $classData->city;
        $info_data['province'] = $classData->province;
        $info_data['img'] = $classData->headimgurl;
        //插入前判断下
        $user_open = Db::name('user')->field('id')->where(array('wxapi_wecha_id' => $classData->openid))->find();
        if(!$user_open['id']) {
            //不存在 需要生成一个临时的账号
            $udata['username'] = $this->getCheackName('6');
            $userid = Db::name('user')->insert($udata);
            if($userid) {
                $info_data['uid'] = $userid;
                Db::name('user_info')->insert($info_data);
            }
        } else {
            //存在
            if($user_open['subscribe'] != 1 && $u_subscribe == 1) {
                Db::name('user')->where(array('wxapi_wecha_id' => $classData->openid))->update(array('subscribe' => 1, 'subscribe_time' => $classData->subscribe_time));
            }
        }
    }

    //生成一个随机账号
    public function getCheackName($length)
    {
        $str = null;
        $strPol = "0123456789abcdefghjkmnpqrstuvwxyz";
        $max = strlen($strPol) - 1;
        for($i = 0; $i < $length; $i++) {
            $str .= $strPol[rand(0, $max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }
        $name = "wx_".$str;
        $info = Db::name('user')->field('id')->where(array('username' => $name))->find();
        if($info['id'] == '') {
            return $name;
        } else {
            return $this->getCheackName($length);
        }
    }

    //组合获取连接地址
    public function getFuncLink($u)
    {
        $urlInfos = explode(' ', $u);
        switch($urlInfos[0]) {
            default:
                $url = str_replace('{wechat_id}', $this->data['FromUserName'], $urlInfos[0]);
                break;
            /*case '刮刮卡':
                $Lottery = Db::name('Lottery')->where(array( 'token' => $this->token, 'type' => 2,'status' => 1))->order('id DESC')->find();
                $url     = config('site_url') . url('Wap/Guajiang/index', array('token' => $this->token,'wecha_id' => $this->data['FromUserName'],'id' => $Lottery['id']));
                break;
            case '大转盘':
                $Lottery = Db::name('Lottery')->where(array('token' => $this->token,'type' => 1, 'status' => 1 ))->order('id DESC')->find();
                $url     = config('site_url') . url('Wap/Lottery/index', array('token' => $this->token,'wecha_id' => $this->data['FromUserName'],'id' => $Lottery['id']));
                break;*/
            case '首页':
                $url = rtrim(CONF('weishang_url'), '/').'/wap.php?s=/Index/index/token='.$this->token.'/wecha_id='.$this->data['FromUserName'];
                break;
            case '商城':
                $url = rtrim(CONF('weishang_url'), '/').'/wap.php?s=/Index/index/token='.$this->token.'/wecha_id='.$this->data['FromUserName'];
                break;
        }
        return $url;
    }

    //微信更新会员结束时间
    public function updateMemberEndTime($openid)
    {
        $mysql = Db::name('wechat_member_enddate');
        $id = $mysql->field('id')->where(array('openid' => $openid))->find();
        $data['enddate'] = systemTime();
        $data['openid'] = $openid;
        $data['token'] = $this->token;
        if($id == false) {
            $mysql->insert($data);
        } else {
            $data['id'] = $id['id'];
            $mysql->update($data);
        }
    }

    //关注提醒邀请码
    public function noticeTuijianren($openid)
    {
        //获取
        $pid = Db::name('user')->field('tjruserid')->where(array('wxapi_wecha_id' => $openid))->find();
        if($pid) {
            $puser = Db::name('user')->field('wxapi_wecha_id')->where(array('id' => $pid['tjruserid']))->find();
            if($puser['wxapi_wecha_id']) $this->pushpost($this->token, $puser['wxapi_wecha_id'], '您有邀请的新朋友加入!');
        }
    }

    //消息推送
    public function pushpost($token, $openid, $msgtxt)
    {
        $access_token = logic('Wechat')->getWechatBasicAccesstoken();

        $data = '{"touser":"'.$openid.'","msgtype":"text","text":{"content":"'.$msgtxt.'"}}';
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
        $this->api_notice_increment($url, $data);
    }

    //微信多客服
    public function wxduokefu()
    {
        $fromUsername = $this->data['FromUserName'];
        $toUsername = $this->data['ToUserName'];
        $time = systemTime();
        $customerTpl = "<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[transfer_customer_service]]></MsgType>
                </xml>";
        $resultStr = sprintf($customerTpl, $fromUsername, $toUsername, $time);
        echo $resultStr;
    }

    //标签
    public function get_tags($title, $num = 10)
    {
        $pscws = new PSCWS4();
        $pscws->set_dict(APP_PATH.'Lib/ORG/etc/dict.utf8.xdb');
        $pscws->set_rule(APP_PATH.'Lib/ORG/etc/rules.utf8.ini');
        $pscws->set_ignore(true);
        $pscws->send_text($title);
        $words = $pscws->get_tops($num);
        $pscws->close();
        $tags = array();
        foreach($words as $val) {
            $tags[] = $val['word'];
        }
        return implode(',', $tags);
    }

    //远程获取数据
    public function curlGet($url, $method = 'get', $data = '')
    {
        $ch = curl_init();
        //$header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $temp = curl_exec($ch);
        return $temp;
    }

    //提交插入数据
    public function api_notice_increment($url, $data)
    {
        $ch = curl_init();
        //$header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //$tmpInfo = curl_exec($ch);
        if(curl_errno($ch)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 日志
     */
    public function log($str = '')
    {
        if($this->log_id) {
            $_log = [];
            $_log['result'] = $str;
            $_log['data'] = json_encode($this->data, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_NUMERIC_CHECK);
            Db::name('wechat_msg_log')->where(['id'=>$this->log_id])->update($_log);
        }
    }

    /**
     * @Author     :      liuxing
     * @DateTime   :    2016-02-15 14:43:01
     * @Description: 计算两点之间距离
     */
    public function getdistance($lat1, $lng1, $lat2, $lng2)
    {
        $radLat1 = deg2rad($lat1);//deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137;
        return sprintf("%.2f", $s);
    }
}
