微信公众号接入类
配置：(也可以使用配置文件加载方式)
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
调用：
    $Wechat = WechatServices::getinstance();
接入接口说明：
     http_curl：使用curl请求方式
     checkSignature：签名检测
     getAccessToken：获取全局使用token(此接口可修改token的保存方式)
     getUserWebAuth：网页授权跳转
     getAuthToken：获取网页授权token
     getUserBaseInfo：用户基本信息获取
     getJsapiTicket：获取jsapi票据
     getJsSignature：js_sdk签名生成
     getReceiveMsg：获取推送消息
     getRevFrom：获取消息发送者
     getRevTo：获取消息接收者
     getRevType：获取接收消息类型
     getRevContent：获取消息内容
     reponseMsg：回复消息文本
     getPosition：自动上报用户地理位置
     reportPOsition：获取上报地理位置事件
     createMenu：创建公众号菜单