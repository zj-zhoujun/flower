<?php
namespace org\SocialLogin\sdk;

use org\SocialLogin\SocialOauth;

class WeixinSDK extends SocialOauth
{
    /**
     * 获取requestCode的api接口
     * @var string
     */
    protected $GetRequestCodeURL = 'https://open.weixin.qq.com/connect/oauth2/authorize';

    /**
     * 获取access_token的api接口
     * @var string
     */
    protected $GetAccessTokenURL = 'https://api.weixin.qq.com/sns/oauth2/access_token';

    /**
     * 扫一扫登录
     * @var string
     */
    protected $QrLoGinURL = 'https://open.weixin.qq.com/connect/qrconnect';
    /**
     * API根路径
     * @var string
     */
    protected $ApiBase = 'https://api.weixin.qq.com/sns/';

    /**
     * 回调地址
     * @var string
     */
    public $Callback = '';
    /**
     * 授权作用域
     * @var string
     */
    public $scope = '';

    /**
     * 组装接口调用参数 并调用接口
     * @param  string $api    微博API
     * @param  string $param  调用API的额外参数
     * @param  string $method HTTP请求方法 默认为GET
     * @return array
     */
    public function call($api, $param = '', $method = 'GET', $multi = false)
    {
        /* 腾讯微博调用公共参数 */
        $params = array(
            'access_token' => $this->Token['access_token'],
            'openid' => $this->openid(),
            //'scope'              => 'snsapi_userinfo',
        );
        $vars = $this->param($params, $param);
        $data = $this->http($this->url($api), $vars, $method, array(), $multi);  // 获取用户信息

        $data = json_decode($data, true);
        if(isset($data['openid'])) {
            $userInfo = [];
            $userInfo['type'] = 'WEIXIN';
            $userInfo['nickname'] = $data['nickname'];
            $userInfo['logo'] = $data['headimgurl'];
            $userInfo['sex'] = 2==$data['sex'] ? 1 : 0; //性别(1男|2女|0未知转换为0男|1女)
            $userInfo['unionid'] = $data['unionid'] ?? '';
            return $userInfo;
        } else {
            abort(500,lang('获取微信用户信息失败').$data['errmsg']);
        }
    }

    /**
     * 获取当前授权应用的openid
     * @return string
     */
    public function openid()
    {
        $data = $this->Token;
        if(isset($data['openid']))
            return $data['openid'];
        else
            throw new Exception('没有获取到openid！');
    }

    /**
     * 请求code
     */
    public function getRequestCodeURL($scope = '', $callback = '', $params_arr = [])
    {
        // Oauth 标准参数
        if(is_weixin()) { //wap
            //授权方式(默认主动授权)
            if(!$scope || !in_array($scope, ['snsapi_userinfo', 'snsapi_base'])) {
                $scope = 'snsapi_userinfo';
            };
            //回调地址
            $callback = $callback ?: $this->Callback;
            if($params_arr && is_array($params_arr)) {
                //业务参数(附加到回调地址后)
                foreach($params_arr as $_k => $_v) {
                    $callback .= '/'.$_k.'/'.$_v;
                }
            }
            //请求参数
            $params = array(
                'appid' => $this->AppKey,
                'redirect_uri' => $callback ?: $this->Callback,
                'response_type' => $this->ResponseType,
                'scope' => $scope,
                'state' => $this->State
            );
        } else { //pc
            $params = array(
                'appid' => $this->AppKey,
                'redirect_uri' => $this->Callback,
                'response_type' => $this->ResponseType,
                'scope' => 'snsapi_login',
                'state' => $this->State
            );
            $this->GetRequestCodeURL = $this->QrLoGinURL;
        }

        //获取额外参数
        if($this->Authorize) {
            parse_str($this->Authorize, $_param);
            if(is_array($_param)) {
                $params = array_merge($params, $_param);
            } else {
                abort(500, 'AUTHORIZE配置不正确');
            }
        }

        return $this->GetRequestCodeURL.'?'.http_build_query($params).'#wechat_redirect';
    }

    /**
     * 获取access_token
     * @param string $code 上一步请求到的code
     * @return mixed
     */
    public function getAccessToken($code, $extend = null)
    {
        $params = array(
            'appid' => $this->AppKey,
            'secret' => $this->AppSecret,
            'code' => $code,
            'grant_type' => $this->GrantType,
        );
        $data = $this->http($this->GetAccessTokenURL, $params, 'GET');
        $this->Token = $this->parseToken($data, $extend);

        $this->Token['appid'] = $this->AppKey;
        return $this->Token;
    }

    /**
     * 解析access_token方法请求后的返回值
     * @param string $result 获取access_token的方法的返回值
     * @return array
     */
    protected function parseToken($result, $extend)
    {
        $data = (array)json_decode($result);
        if(!empty($data['errmsg'])) {
            abort(500,lang('请求已失效'), ['redirect_url'=>url('/')]);
            // abort(500, $data['errmsg'], ['redirect_url'=>url('index/index')]);
        }

        if($extend) {
            $data = array_merge($data, $extend);
        }
        if($data['access_token'] && $data['expires_in'] && $data['openid']) {
            return $data;
        } else {
            abort(500, '取 token 出错');
        }
    }

    /**
     * 设置参数
     */
    public function setconfig($config)
    {
        //获取三方配置
        if(empty($config['APP_KEY']) || empty($config['APP_SECRET'])) {
            abort(500, '请配置您申请的APP_KEY和APP_SECRET');
        } else {
            $this->AppKey = $config['APP_KEY'];
            $this->AppSecret = $config['APP_SECRET'];
            if(isset($config['CALLBACK']) && $config['CALLBACK']) {
                $this->Callback = $config['CALLBACK'];
            }
            if(isset($config['scope']) && $config['scope']) {
                $this->scope = $config['scope'];
            }
        }
    }

    /**
     * 获取名称
     */
    public function getName()
    {
        return '微信登录';
    }

    /**
     * 获取描述
     */
    public function getMemo()
    {
        return '绑定微信帐号后，您便可使用微信帐号登录网站';
    }

    /**
     * 获取配置信息
     */
    public function getConfigInfo()
    {
        return [
            'APP_KEY' => [
                'name' => '应用标识',
                'type' => 'text',
                'style' => 'width:300px',
                'desc' => '<= 微信开放平台-网站应用-查看-AppID',
                'value' => '',
            ],
            'APP_SECRET' => [
                'name' => '应用密钥',
                'type' => 'text',
                'style' => 'width:300px',
                'desc' => '<= 微信开放平台-网站应用-查看-AppSecret',
                'value' => '',
            ],
            'STATE' => [
                'name' => '验证值',
                'type' => 'text',
                'style' => 'width:300px',
                'desc' => '可自定义',
                'value' => '',
            ],
            'CALLBACK' => [
                'name' => '回调地址',
                'type' => 'text',
                'style' => 'width:300px',
                'desc' => '本站地址/home/sociallogin/callback/type/weixin',
                'value' => '',
            ],
        ];
    }
}