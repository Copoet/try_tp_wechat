<?php
/**
 * Created by PhpStorm.
 * User: copoet
 * Date: 16/11/19
 * Time: 下午4:19
 * 单例模式设计
 * method one:
 * $wechatObj = WechatService::getinstance();
 * $wechatObj->valid();
 * $type = $wechatObj->getRevType();//获取消息类型
 * $msg_content = $wechatObj->getRevContent();//获取推送消息内容
 *
 */
namespace Common\Services;

class WechatServices
{
    /**消息内容类型**/
    const MSGTYPE_TEXT = 'text'; //文本
    const MSGTYPE_IMAGE = 'image'; //图片
    const MSGTYPE_LOCATION = 'location';//地理位置
    const MSGTYPE_LINK = 'link'; //链接
    const MSGTYPE_VOICE = 'voice';//语音
    const MSGTYPE_VIDEO = 'shortvideo';//视频
    /**事件***/
    const MSGTYPE_EVENT = 'event'; // 事件
    const MSGTYPE_EVENT_LOCATION = 'LOCATION';//自动上报地理位置
    const MSGTYPE_EVENT_SUBSCRIBE = 'subscribe';//订阅事件
    const MSGTYPE_EVENT_UNSUBSCRIBE = 'unsubscribe';//取消订阅事件
    const MSGTYPE_EVENT_CLICK = 'CLICK';//点击菜单推事件

    /**访问接口 url**/
    const API_URL_PREFIX = 'https://api.weixin.qq.com/cgi-bin';//前缀
    const AUTH_URL = '/token?grant_type=client_credential&';// 获取token
    /***oauth url **/
    const OAUTH_PREFIX = 'https://open.weixin.qq.com/connect/oauth2';
    const OAUTH_AUTHORIZE_URL = '/authorize?'; //授权url
    const OAUTH_TOKEN_PREFIX = 'https://api.weixin.qq.com/sns/oauth2';
    const OAUTH_TOKEN_URL = '/access_token?'; //获取网页授权token url
    const OAUTH_REFRESH_URL = '/refresh_token?';//刷新网页授权token url
    const OAUTH_USERINFO_URL = 'https://api.weixin.qq.com/sns/userinfo?';//获取用户信息url

    /**
     * 公众帐号appid
     * @var string
     */

    public $appId = 'wx150a19cb41f1b6af';

    /**
     * 公众帐号appsecret
     * @var string
     */
    private $appsecret = '7fb3c79fc614a3dfdf2ec170f4a3772c';
    /**
     *
     * 公众帐号配置token
     * @var string
     */
    private $token = 'weixin';

    static public $instance;//声明一个静态变量（保存在类中唯一的一个实例）

    private function __construct($appId, $appsecret, $token)
    {
        $this->appId = isset($appId) ? $appId : $this->appId;
        $this->appsecret = isset($appsecret) ? $appsecret : $this->appsecret;
        $this->token = isset($token) ? $token : $this->token;
        $echoStr = isset($_GET["echostr"]) ? $_GET["echostr"] : '';//关注公众号返回字符串
        $checkData = $this->checkSignature();//签名校验
        if ($echoStr) {
            if ($checkData) {
                echo $echoStr;
                return true;
            } else {
                return false;
            }
        }
        return $this->checkSignature();
    }

    static public function getinstance(){//声明一个getinstance()静态方法，用于检测是否有实例对象
        if(!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    /** 
     * 微信签名检测
     * @return bool
     *
     */
    private function checkSignature()
    {

        $signature = isset($_GET["signature"]) ? $_GET["signature"] : '';
        $timestamp = isset($_GET["timestamp"]) ? $_GET["timestamp"] : '';
        $nonce = isset($_GET["nonce"]) ? $_GET["nonce"] : '';
        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * curl 请求
     * @param $url
     * @param string $type get/post
     * @param string $res 返回接过
     * @param string $arr 发送的数据包
     * @return mixed
     */
    public function http_curl($url, $type = 'get', $res = 'json', $arr = '')
    {
        //初始化url
        $ch = curl_init();
        //设置url参数
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($type == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);
        }
        // 抓取URL并把它传递给浏览器
        $data = curl_exec($ch);
        // 关闭cURL资源，并且释放系统资源
        curl_close($ch);
        return json_decode($data, true);
    }

    /**
     * 获取AccessToken
     * @return mixed
     *
     */
    public function getAccessToken()
    {

        $confModel=M('WechatGlobalConf');
        $condition['name']=array('eq','access_token');
        $result=$confModel->where($condition)->field('name,value,expires_in')->find();
        if ($result['value'] && $result['expires_in'] >time()) {
            return $result['value'];
        }else{
            $data = self::http_curl(self::API_URL_PREFIX . self::AUTH_URL . '&appid='.$this->appId.'&secret='. $this->appsecret);
            if ($data) {
                $up_data['name']='access_token';
                $up_data['value']=$data['access_token'];
                $up_data['expires_in']=$data['expires_in']+time()-1000;
                $confModel->where($condition)->save($up_data);
            }
            return $data['access_token'];
        }
    }


    /**
     * 网页授权跳转接口
     * @param $callback
     * @param string $state
     * @param string $scope
     * @return mixed
     *
     */
    public function getUserWebAuth($callback, $state = '', $scope = 'snsapi_userinfo')
    {

        return self::OAUTH_PREFIX . self::OAUTH_AUTHORIZE_URL . 'appid=' . $this->appId . '&redirect_uri=' . urlencode($callback) . '&response_type=code&scope=' . $scope . '&state=' . $state . '#wechat_redirect';

    }

    /**
     * 获取网页授权token
     * @return bool|mixed
     *
     */
    public function getAuthToken($code)
    {
        if (!$code) return false;
        $data = self::http_curl(self::OAUTH_TOKEN_PREFIX . self::OAUTH_TOKEN_URL . 'appid=' . $this->appId . '&secret=' . $this->appsecret . '&code=' . $code . '&grant_type=authorization_code', 'get');
        if ($data) {
            return $data;
        }
        return false;
    }

    /***
     * 获取用户基本信息
     * @param $access_token
     * @param $open_id
     * @return bool|mixed
     *
     */
    public function getUserBaseInfo($access_token, $open_id)
    {
        if (!$access_token || !$open_id) return false;
        $result = self::http_curl(self::OAUTH_USERINFO_URL . 'access_token=' . $access_token . '&openid=' . $open_id);
        if ($result) {
            return $result;
        }
        return false;
    }


    /**
     * 获取jsapi票据
     * @return mixed
     *
     */
    public function getJsapiTicket()
    {
        $confModel=M('WechatGlobalConf');
        $condition['name']=array('eq','jsticket');
        $result=$confModel->where($condition)->field('name,value,expires_in')->find();
        if ($result['value'] && $result['expires_in'] > time()) {
            $ticket = $result['value'];
            return $ticket;
        } else {
            $token = self::getAccessToken();
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$token&type=jsapi";
            $data = $this->http_curl($url, 'get');
            if ($data) {
                $up_data['value']=$data['ticket'];
                $up_data['expires_in']=$data['expires_in']+time()-1000;
                $confModel->where($condition)->save($up_data);
            }
            return $data['ticket'];
        }
    }

    /**
     * 获取随机码
     * @param int $length
     * @param int $type
     * @return string
     *
     */
    private function randCode($length = 16, $type = 0)
    {
        $arr = array(1 => "0123456789", 2 => "abcdefghijklmnopqrstuvwxyz", 3 => "ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4 => "~@#$%^&*(){}[]|");
        if ($type == 0) {
            array_pop($arr);
            $string = implode("", $arr);
        } elseif ($type == "-1") {
            $string = implode("", $arr);
        } else {
            $string = $arr[$type];
        }
        $count = strlen($string) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $string[rand(0, $count)];
        }
        return $code;
    }

    /**
     * js_sdk签名生成
     * @return mixed
     *
     */
    public function getJsSignature()
    {

        $timestamp = time(); //时间戳
        $nonceStr = $this->randCode();//随机码
        $ticket = $this->getJsapiTicket();//jsapi-ticket
        //动态获取url
        $protocol = (!empty($_SERVER[HTTPS]) && $_SERVER[HTTPS] !== off || $_SERVER[SERVER_PORT] == 443) ? "https://" : "http://";
        $url = $protocol . $_SERVER[HTTP_HOST] . $_SERVER[REQUEST_URI];
        $string = "jsapi_ticket=" . $ticket . "&noncestr=" . $nonceStr . "&timestamp=" . $timestamp . "&url=" . $url;
        $signature = sha1($string);
        $result['timestamp'] = $timestamp;//签名时间戳
        $result['nonceStr'] = $nonceStr;//随机字符串
        $result['url'] = $url;// url
        $result['signature'] = $signature; //签名
        $result['jsapi_ticket'] = $ticket; //签名
        return $result;

    }


    /**
     * 获取推送消息
     * @return bool|\SimpleXMLElement
     *
     */
    public function getReceiveMsg()
    {
        $postMsg = $GLOBALS["HTTP_RAW_POST_DATA"];
        $msgObj = simplexml_load_string($postMsg);
        if ($msgObj) {
            return $msgObj;
        }
        return false;
    }


    /**
     * 获取消息发送者
     */
    public function getRevFrom()
    {
        $msgObj = $this->getReceiveMsg();
        if ($msgObj) {
            return $msgObj->FromUserName;
        }
        return false;
    }

    /**
     * 获取消息接受者
     */
    public function getRevTo()
    {
        $msgObj = $this->getReceiveMsg();
        if ($msgObj) {
            return $msgObj->ToUserName;
        }
        return false;
    }

    /**
     * 获取接收消息的类型
     */
    public function getRevType()
    {
        $msgObj = $this->getReceiveMsg();
        if ($msgObj) {
            return strtolower($msgObj->MsgType);
        }
        return false;
    }

    /**
     * 获取消息ID
     */
    public function getRevID()
    {
        $msgObj = $this->getReceiveMsg();
        if ($msgObj) {
            return $msgObj->MsgId;
        }
        return false;
    }


    /**
     * 获取消息发送时间
     *
     */
    public function getRevCtime()
    {
        $msgObj = $this->getReceiveMsg();
        if ($msgObj) {
            return $msgObj['CreateTime'];
        }
        return false;
    }

    /**
     * 获取接收消息内容正文
     *
     */
    public function getRevContent()
    {
        $msgObj = $this->getReceiveMsg();
        if ($msgObj) {
            if ($msgObj->Content) {
                return trim($msgObj->Content);//文本内容
            } else if ($msgObj->Recognition) {
                return trim($msgObj->Recognition);//语音识别
            }
        }
        return false;
    }


    /**
     * 回复文本消息
     * @param $content
     */
    public function reponseMsg($content)
    {
        //消息回复模板
        $template = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    </xml>";
        $toUser = $this->getRevFrom();
        $fromUser = $this->getRevTo();
        $time = time();
        $msgType = 'text';
        $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
        echo $info;

    }

    /**
     * 自动上报地理位置
     * @return bool
     */
    public function getPosition()
    {
        $msgObj = $this->getReceiveMsg();

        if ($msgObj) {
            $result=array();
            $result['longitude'] = $msgObj->Longitude;//经度
            $result['latitude'] = $msgObj->Latitude;//纬度
            $result['precision'] = $msgObj->Precision;//地理位置精度
            return $result;
        }
        return false;
    }

    /**
     * 获取接收事件类型
     */
    public function getRevEventType()
    {
        $msgObj = $this->getReceiveMsg();
        if (isset($msgObj->Event)) {
            return $msgObj->Event;
        }
        return false;
    }

    /**
     * 获取上报地理位置事件
     * @return bool
     */
    public function reportPOsition(){
        $msgObj = $this->getReceiveMsg();
        if (strtolower($msgObj->MsgType) == 'location') {
            $result['longitude'] = $msgObj->Location_Y;//经度
            $result['latitude'] = $msgObj->Location_X;//纬度
            $result['label'] = $msgObj->Label;//地理位置精度
            return $result;
        }
        return false;
    }

    /**
     * 微信发送模板消息
     * @param $opendId    open_id
     * @param $templateId 模板ID
     * @param $info_url   查看详情url
     * @param $data   发送的消息数组
     * @return mixed
     *
     * use:
     *  'data'=>array(
     *      'name'=>array('value'=>'雷锋','color'=>'#173177'),
     *      'money'=>array('value'=>'1000.00','color'=>'#173177'),
     *      'number'=>array('value'=>'001232','color'=>'#173177')
     *  )
     */
    public function sendTemplateMessage($opendId,$templateId,$info_url,$data){
        $token = self::getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=$token";
        $array=array(
            'touser'=>$opendId,
            'template_id'=>$templateId,
            'url'=>$info_url,
            'data'=>$data,
        );
        $postData=json_encode($array);
        $result= $this->http_curl($url, 'post','json',$postData);
        return $result;
    }


    /**
     * 创建菜单
     * @param $menuData
     * @return bool
     */
    public function createMenu($menuData){
        $menuArr = urldecode(json_encode($menuData));
        $access_token = $this-> getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=$access_token";
        $result = $this->http_curl($url, 'post', 'json', $menuArr);
        return $result;
    }
}