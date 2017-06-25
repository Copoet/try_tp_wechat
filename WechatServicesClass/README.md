##微信公众号接入类
##配置：(也可以使用配置文件加载方式)
 /**
     * 公众帐号appid
     *
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
##调用：
    $Wechat = WechatServices::getinstance();</br>
##接入接口说明：
     http_curl：使用curl请求方式</br>
     checkSignature：签名检测</br>
     getAccessToken：获取全局使用token(此接口可修改token的保存方式)</br>
     getUserWebAuth：网页授权跳转</br>
     getAuthToken：获取网页授权token</br>
     getUserBaseInfo：用户基本信息获取</br>
     getJsapiTicket：获取jsapi票据</br>
     getJsSignature：js_sdk签名生成</br>
     getReceiveMsg：获取推送消息</br>
     getRevFrom：获取消息发送者</br>
     getRevTo：获取消息接收者</br>
     getRevType：获取接收消息类型</br>
     getRevContent：获取消息内容</br>
     reponseMsg：回复消息文本</br>
     getPosition：自动上报用户地理位置</br>
     reportPOsition：获取上报地理位置事件</br>
     createMenu：创建公众号菜单</br>