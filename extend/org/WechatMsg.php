<?php
namespace org;
use app\index\controller\Wechat;

/**
 * 微信消息
 * Class WechatMsg
 * @package org
 */
class WechatMsg
{
    public $data = [];

    public function __construct()
    {
    }

    public function auth($signature = '', $params = [])
    {
        sort($params, SORT_STRING);
        $sign = sha1(implode($params));

        return $signature === $sign;
    }

    public function request($xml = '')
    {
        if(!$xml) {
            $xml = file_get_contents("php://input");
        }

        $xml = new \SimpleXMLElement($xml);
        if(!$xml) {
            return ['err'=>'xml错误'];
        }
        foreach($xml as $key => $value) {
            $this->data[$key] = strval($value);
        }

        if(empty($this->data)) {
            return ['err'=>'xml解析错误'];
        } else {
            return ['data'=>$this->data];
        }
    }

    /**
     * 处理数据，回复微信
     * @param $content
     * @param string $type
     * @param int $flag
     */
    public function response($content, $type = 'text', $flag = 0)
    {
        $this->data = array(
            'ToUserName' => $this->data['FromUserName'],
            'FromUserName' => $this->data['ToUserName'],
            'CreateTime' => systemTime(),
            'MsgType' => $type
        );
        $this->$type($content);
        $this->data['FuncFlag'] = $flag;

        $xml = new \SimpleXMLElement('<xml></xml>');
        $this->data2xml($xml, $this->data);
        return ['data'=>$xml->asXML()];
    }

    public function text($content)
    {
        if($this->is_utf8($content)) {
            $this->data ['Content'] = $content;
        } else {
            $str = iconv("gb2312", "UTF-8//IGNORE", $content);
            if($str) {
                $this->data ['Content'] = $str;
            } else {
                $this->data ['Content'] = "Welcome to Szchemist!";
            }
        }
    }

    public function is_utf8($str)
    {
        return (bool)preg_match('//u', $str);
    }

    public function music($music)
    {
        list($music['Title'], $music['Description'], $music['MusicUrl'], $music['HQMusicUrl']) = $music;
        $this->data['Music'] = $music;
    }

    public function news($news)
    {
        $articles = array();
        foreach($news as $key => $value) {
            list($articles[$key]['Title'], $articles[$key]['Description'], $articles[$key]['PicUrl'], $articles[$key]['Url']) = $value;
            if($key >= 9) {
                break;
            }
        }
        $this->data['ArticleCount'] = count($articles);
        $this->data['Articles'] = $articles;
    }

    public function data2xml($xml, $data, $item = 'item')
    {
        foreach($data as $key => $value) {
            is_numeric($key) && $key = $item;
            if(is_array($value) || is_object($value)) {
                $child = $xml->addChild($key);
                $this->data2xml($child, $value, $item);
            } else {
                if(is_numeric($value)) {
                    $child = $xml->addChild($key, $value);
                } else {
                    $child = $xml->addChild($key);
                    $node = dom_import_simplexml($child);
                    $node->appendChild($node->ownerDocument->createCDATASection($value));
                }
            }
        }
    }
}

?>
