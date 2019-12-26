<?php
namespace org\SocialLogin\sdk;
use org\SocialLogin\SocialOauth;

class DouyinSDK extends SocialOauth{
    /**
     * 获取授权码（code）
     * @var string
     */
    protected $GetRequestCodeURL = 'https://open.douyin.com/platform/oauth/connect';

    /**
     * 获取access_token的api接口
     * @var string
     */
    protected $GetAccessTokenURL = 'https://open.douyin.com/oauth/access_token/';

    /**
     * API根路径 -- 获取用户信息会用该参数进行拼接
     * @var string
     */
    protected $ApiBase = 'https://open.douyin.com/oauth/';

    /**
     * 获取授权码（code） -- 应用授权作用域
     */
    protected $Scope = 'user_info';

    public function getRequestCodeURL()
    {
        //Oauth 标准参数
        $params = array(
            'client_key' => $this->AppKey,
            'response_type' => $this->ResponseType,//值为code
            'scope' => $this->Scope,
            'redirect_uri' => $this->Callback,
            'state' => $this->State,
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
        /* 抖音调用公共参数 */
        $params = array(
            'access_token'       => $this->Token['access_token'],
            'open_id'             => $this->openid(),
        );


        $data = $this->http($this->url($api).'/', $this->param($params, $param), $method);

        $data = json_decode($data, true);

        if( $data['data']['error_code'] == 0) {
            $userInfo = [];
            $userInfo['type']     = 'DOUYIN';
            $userInfo['nickname'] = $data['data']['nickname'];//昵称
            $userInfo['logo']     = $data['data']['avatar'];//头像
            return $userInfo;
        } else {
            abort(500, '获取抖音用户信息失败');
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
            'client_key' => $this->AppKey,
            'client_secret' => $this->AppSecret,
            'code' => $code, //code只能使用一次，用完即失效，需要进行重新授权
            'grant_type' => $this->GrantType, //固定为authorization_code
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

//        $data = (array)json_decode($result);
        $data = json_decode($result, true);

        if(!empty($data['message']) && strtolower($data['message']) != 'success') {
            abort(500,lang('获取AccessToken失败'), ['redirect_url'=>url('/')]);
        }

        $data = $data['data'];

        if($data['access_token'] && $data['expires_in'] && $data['open_id']) {
            $data['openid'] = $data['open_id'];//增加个openid，兼容qq和wechat格式
            $this->Token    = $data;
            return $data;
        } else {
            abort(500, '取 token 出错');
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
        return '抖音登录';
    }
    /**
     * 获取描述
     */
    public function getMemo(){
        return '绑定抖音帐号后，您便可使用抖音帐号登录网站';
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
                'desc' => '<= 抖音开放平台-网站应用-查看-ClientKey',
                'value'=> '',
            ],
            'APP_SECRET'=> [
                'name' => '应用密钥',
                'type' => 'text',
                'style' => 'width:300px',
                'desc' => '<= 抖音开放平台-网站应用-查看-ClientSecret',
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
                'desc' => '本站地址/home/sociallogin/callback/type/douyin',
                'value'=> '',
            ],
        ];
    }


}