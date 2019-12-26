<?php
namespace org\SocialLogin\sdk;
use org\SocialLogin\SocialOauth;

class QqSDK extends SocialOauth{
    /**
     * 获取requestCode的api接口
     * @var string
     */
    protected $GetRequestCodeURL = 'https://graph.qq.com/oauth2.0/authorize';
    
    /**
     * 获取access_token的api接口
     * @var string
     */
    protected $GetAccessTokenURL = 'https://graph.qq.com/oauth2.0/token';
    
    /**
     * 获取request_code的额外参数,可在配置中修改 URL查询字符串格式
     * @var string
     */
    protected $Authorize = 'scope=get_user_info,add_share';
    
    /**
     * API根路径
     * @var string
     */
    protected $ApiBase = 'https://graph.qq.com/';

    public function getRequestCodeURL()
    {
        //Oauth 标准参数
        $params = array(
            'response_type' => $this->ResponseType,
            'client_id' => $this->AppKey,
            'redirect_uri' => $this->Callback,
            'state' => $this->State
        );
        //获取额外参数
        if($this->Authorize) {
            $params = $this->param($params, $this->Authorize);
        }
        return $this->GetRequestCodeURL.'?'.http_build_query($params);
    }


    /**
     * 组装接口调用参数 并调用接口
     * @param  string $api    微博API
     * @param  string $param  调用API的额外参数
     * @param  string $method HTTP请求方法 默认为GET
     * @return array
     */
    public function call($api, $param = '', $method = 'GET', $multi = false){
        /* 腾讯QQ调用公共参数 */
        $params = array(
            'oauth_consumer_key' => $this->AppKey,
            'access_token'       => $this->Token['access_token'],
            'openid'             => $this->openid(),
            'format'             => 'json'
        );
        $data = $this->http($this->url($api), $this->param($params, $param), $method);

        $data = json_decode($data, true);
        if($data['ret'] == 0) {
            $userInfo = [];
            $userInfo['type'] = 'QQ';
            //$userInfo['name'] = $data['nickname'];
            $userInfo['nickname'] = $data['nickname'];
            $userInfo['logo'] = $data['figureurl_qq_2'] ? $data['figureurl_qq_2'] : $data['figureurl_qq_1'];
            $userInfo['sex'] = '男'==$data['gender'] ? 0 : 1; //性别(男|女转换为0男|1女)
            return $userInfo;
        } else {
            abort(500, '获取QQ用户信息失败');
        }
    }

    /**
     * 获取access_token
     * @param string $code 上一步请求到的code
     * @return mixed
     */
    public function getAccessToken($code, $extend = null)
    {
        //请求参数
        $params = [
            'client_id' => $this->AppKey,
            'client_secret' => $this->AppSecret,
            'grant_type' => $this->GrantType, //固定为authorization_code
            'redirect_uri' => $this->Callback, //回调地址
            'code' => $code, //10分钟过期
        ];
        $data = $this->http($this->GetAccessTokenURL, $params, 'GET');
        $this->Token = $this->parseToken($data, $extend);
        return $this->Token;
    }
    
    /**
     * 
     *  还未完成
     * 
     * 解析access_token方法请求后的返回值 
     * @param string $result 获取access_token的方法的返回值
     * @return array $data  access_token的返回值与openID
     */
    protected function parseToken($result, $extend){
        if (strpos($result, "callback") !== false)
        {
            $lpos = strpos($result, "(");
            $rpos = strrpos($result, ")");
            $result  = substr($result, $lpos + 1, $rpos - $lpos -1);
            $msg = json_decode($result);
            if (isset($msg->error))
            {    
                //获取token信息失败
                echo "获取 token 失败！",'<br>';
                echo "<h3>error:</h3>" . $msg->error;
                echo "<h3>msg  :</h3>" . $msg->error_description;
                die;
                /**
                  这部分未完成 
                 $url = "/mobile.php?s=/Index/index";
                 redirect($url);
                 exit;
                 */    
            }
        }
        parse_str($result, $data);
        if($data['access_token'] && $data['expires_in'] && $data['refresh_token']){
            $this->Token    = $data;
            $data['openid'] = $this->openid();
            return $data;
        }
    }
    
    /**
     * 获取当前授权应用的openid
     * @return string openid
     */
    public function openid(){
        $data = $this->Token;
        if(isset($data['openid'])) {
            return $data['openid'];
        } elseif ($data['access_token']) {
            $data = $this->http($this->url('oauth2.0/me'), array('access_token' => $data['access_token']));            
            $data = json_decode(trim(substr($data, 9), " );\n"), true);            
            if (isset($data['openid'])) {
                return $data['openid'];
            } else {
                throw new Exception("获取用户openid出错：{$data['error_description']}");
            }
        } else {
            throw new Exception('没有获取到openid！');
        }
    }
    
    /**
     * 获取名称
     */
    public function getName(){
        return 'QQ登录';
    }
    /**
     * 获取描述
     */
    public function getMemo(){
        return '绑定腾讯QQ帐号后，您便可使用腾讯QQ帐号登录网站';
    }
    
    /**
     * 获取配置信息
     */
    public function getConfigInfo(){
        return [
            'APP_KEY'=> [
                'name' => '应用标识',
                'type' => 'text',
                'style'=> 'width:300px',
                'desc' => '<= QQ互联平台-网站应用-查看-APP ID',
                'value'=> '',
            ],
            'APP_SECRET'=> [
                'name' => '应用密钥',
                'type' => 'text',
                'style' => 'width:300px',
                'desc' => '<= QQ互联平台-网站应用-查看-APP Key',
                'value'=> '',
            ],
            'STATE'=> [
                'name' => '验证值',
                'type' => 'text',
                'style' => 'width:300px',
                'desc' => '可自定义',
                'value'=> '',
            ],
            'CALLBACK'=> [
                'name' => '回调地址',
                'type' => 'text',
                'style' => 'width:300px',
                'desc' => '本站地址/home/sociallogin/callback/type/qq',
                'value'=> '',
            ],
        ];
    }
}