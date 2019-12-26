<?php
namespace org\SocialLogin;

abstract class SocialOauth
{
    /**
     * oauth版本
     * @var string
     */
    protected $Version = '2.0';

    /**
     * 申请应用时分配的app_key
     * @var string
     */
    public $AppKey = '';

    /**
     * 申请应用时分配的 app_secret
     * @var string
     */
    public $AppSecret = '';

    /**
     * 授权类型 response_type 目前只能为code
     * @var string
     */
    protected $ResponseType = 'code';

    /**
     * 回调页面URL  可以通过配置文件配置
     * @var string
     */
    public $Callback = '';

    /**
     * grant_type 目前只能为 authorization_code
     * @var string
     */
    protected $GrantType = 'authorization_code';

    /**
     * 获取request_code新增参数
     * @var string
     */
    protected $State = ''; //最好使用 session +  随机数组合

    /**
     * 获取request_code的额外参数 URL查询字符串格式
     * @var srting
     */
    protected $Authorize = '';

    /**
     * 获取request_code请求的URL
     * @var string
     */
    protected $GetRequestCodeURL = '';

    /**
     * 获取access_token请求的URL
     * @var string
     */
    protected $GetAccessTokenURL = '';

    /**
     * API根路径
     * @var string
     */
    protected $ApiBase = '';

    /**
     * 授权后获取到的TOKEN信息
     * @var array
     */
    protected $Token = null;

    /**
     * 调用接口类型
     * @var string
     */
    protected $Type = '';

    /**
     * 构造方法，配置应用信息
     * @param array $token
     */
    public function __construct($token = null)
    {
        //设置SDK类型
        $class = get_class($this);
        $class = explode('\\', $class)[3];
        $this->Type = strtoupper(substr($class, 0, strlen($class) - 3));

        //获取三方配置
        $config = logic('SocialLogin')->getSocialConfig($this->Type);

        //增加后台登录参数设置过滤
        if((empty($config['APP_KEY']) || empty($config['APP_SECRET'])) && (request()->module()!='admin' && request()->controller()!='SocialLogin')) {
            if(!('qq' == strtolower($this->Type) && session('device') == 'app')) {
                abort(500, $this->Type.' 未配置');
            }
        } else {
            $this->AppKey = $config['APP_KEY'] ?? '';
            $this->AppSecret = $config['APP_SECRET'] ?? '';
            $this->State = $config['STATE'] ?? '';
            $this->Callback = $config['CALLBACK'] ?? '';

            $this->Token = $token; //设置获取到的TOKEN
        }
    }

    /**
     * 取得Oauth实例
     * @param      $type
     * @param null $token
     * @return mixed
     */
    public static function getInstance($type, $token = null)
    {
        $name = 'org\SocialLogin\sdk\\'.ucfirst(strtolower($type)).'SDK';
        if(class_exists($name)) {
            return new $name($token);
        } else {
            abort(500, '类不存在:'.$name);
        }
    }

    /**
     * 返回请求code的URL
     */
    abstract protected function getRequestCodeURL();

    /**
     * 合并默认参数和额外参数
     * @param array $params 默认参数
     * @param       array   /string $param 额外参数
     * @return array:
     */
    protected function param($params, $param)
    {
        if(is_string($param)) {
            parse_str($param, $param);
        }
        if(!is_array($param)) {
            abort(500, 'AUTHORIZE配置不正确！');
        }
        return array_merge($params, $param);
    }

    /**
     * 抽象方法，在SNSSDK中实现
     * 组装接口调用参数 并调用接口
     */
    abstract protected function call($api, $param = '', $method = 'GET', $multi = false);

    /**
     * 抽象方法，在SNSSDK中实现
     * 解析access_token方法请求后的返回值
     */
    abstract protected function parseToken($result, $extend);

    /**
     * 抽象方法，在SNSSDK中实现
     * 获取当前授权用户的SNS标识
     */
    abstract public function openid();

    //__set()方法用来设置私有属性
    public function __set($property_name, $value)
    {
        $this->$property_name = $value;
    }

    //__get()方法用来获取私有属性
    public function __get($property_name)
    {
        if(isset($this->$property_name)) {
            return ($this->$property_name);
        } else {
            return (NULL);
        }
    }

    /**
     * 初始化配置
     */
    //     protected  function config(){
    //         if ($this->Type=='WEIXIN') {
    //             if(isset($_SERVER)&&is_weixin()) //如果在微信内部访问
    //             {
    //                 $config = C("THINK_SDK_{$this->Type}");
    //             }else{
    //                 $config = C("THINK_SDK_PC{$this->Type}");
    //             }
    //         }else{
    //             $config = C("THINK_SDK_{$this->Type}");
    //         }
    //         if(!empty($config['AUTHORIZE']))
    //             $this->Authorize = $config['AUTHORIZE'];
    //         if(!empty($config['CALLBACK']))
    //             $this->Callback = $config['CALLBACK'];
    //         else
    //             throw new Exception('请配置回调页面地址');
    //     }

    /**
     * 获取access_token
     * @param string $code 上一步请求到的code
     * @return array token参数与openID
     */
    public function getAccessToken($code, $extend = null)
    {
        //$this->config();
        /*$params = array(
            'client_id' => $this->AppKey,
            'client_secret' => $this->AppSecret,
            'grant_type' => $this->GrantType,
            'code' => $code,
            'redirect_uri' => $this->Callback,
        );
        $data = $this->http($this->GetAccessTokenURL, $params, 'GET');
        $this->Token = $this->parseToken($data, $extend);
        return $this->Token;*/
    }

    /**
     * 获取指定API请求的URL
     * @param  string $api API名称
     * @param  string $fix api后缀
     * @return string      请求的完整URL
     */
    protected function url($api, $fix = '')
    {
        return $this->ApiBase.$api.$fix;
    }

    /**
     * 发送HTTP请求方法，目前只支持CURL发送请求
     * @param  string $url    请求URL
     * @param  array  $params 请求参数
     * @param  string $method 请求方法GET/POST
     * @return array  $data   响应数据
     */
    protected function http($url, $params, $method = 'GET', $header = array(), $multi = false)
    {
        $opts = array(
            CURLOPT_TIMEOUT => 3,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );
        /* 根据请求类型设置特定参数 */
        switch(strtoupper($method)) {
            case 'GET':
                $opts[CURLOPT_URL] = $url.'?'.http_build_query($params);
                break;
            case 'POST':
                //判断是否传输文件
                $params = $multi ? $params : http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                abort(500,lang('不支持的请求方式'));
        }

        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        if(!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if($error && strtoupper($method) == 'GET') {
            $data = file_get_contents($url.'?'.http_build_query($params));
            return $data;
        }
        if($error) abort(500, '请求发生错误：'.$error);
        return $data;
    }

    /*
     * 重新设置配置信息
     */
    public function setconfig($config)
    {
        //获取三方配置
        $config = $config ?? config("THINK_SDK_{$this->Type}");
        if(empty($config['APP_KEY']) || empty($config['APP_SECRET'])) {
            abort(500, '请配置您申请的APP_KEY和APP_SECRET');
        } else {
            $this->AppKey = $config['APP_KEY'];
            $this->AppSecret = $config['APP_SECRET'];
            //$this->Token = $token; //设置获取到的TOKEN
            $this->Callback = $config['CALLBACK'];
        }
    }

    /**
     * 获取所有第三方登录
     */
    public static function getLoginClassList($type = 'all', $params = [])
    {
        //遍历目录
        $file_path = EXTEND_PATH.'org/SocialLogin/sdk';
        $handle = opendir($file_path);
        $file_list = [];
        //取出有效的登录类
        while(($_file = readdir($handle)) !== false) {
            $_ignore = array('.', '..', '.svn');
            if(in_array($_file, $_ignore)) {
                continue;
            }
            //获取类名
            $_class_name = explode('.', $_file);
            $class_name = $_class_name[0];

            //判断是否开启
            $open = CONF($class_name.'_open');
            if($type == 'open') {//只获取开启的列表
                if($open != 1) continue;
            }
            //获取名称
            $sdk = __NAMESPACE__.'\sdk\\'.$class_name;
            $payment_ob = new $sdk();

            //获取基本信息
            $result = [];
            //开启
            $result['open'] = $open;
            //名称
            $result["name"] = $payment_ob->getName();
            //描述
            $result["memo"] = $payment_ob->getMemo();
            //配置信息
            $result["config"] = $payment_ob->getConfigInfo();
            //配置信息
            $result["request_url"] = $payment_ob->getRequestCodeURL('', '', $params);

            $file_list[$class_name] = $result;
        }
        return $file_list;
    }
}
