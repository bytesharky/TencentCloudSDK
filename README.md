## 一个超级简单的腾讯云SDK

这是精简到7KB的一个腾讯云SDK，虽然短小，但却精悍，他几乎支持所有腾讯云的API v3，而且它同时支持GET和POST请求

### 你该如果使用它

它只有一个文件，你可以直接使用require引入到你的php代码中。

```php
require('./TencentCloud.php')
```

然后你只需用你的SecretId、SecretKey初始化TencentCloud的一个实例。

```php
$SecretId = "******";
$SecretKey = "******";

$txCloud = new TencentCloud($SecretId, $SecretKey);
```

到这里你就可以按照API文档说明，来发起一个请求，下面我们拿自然语言理解（NLP）下面的休闲聊天来测试一下。

```php
//这里我们定义一个变量，用来存放API接口的地址，必须是https://或者http://开头
$url = "https://nlp.tencentcloudapi.com";

//接下来我们需要继续查阅API文档，以设置必要的请求参数

//这里我们定义一个数组，存放的是公共参数
$common["Version"] ='2019-04-08';        //Version   是    本接口取值：2019-04-08。
$common["Region"]  = 'ap-guangzhou';     //Region    是    地域列表。
//$header["Timestamp"] = time();         //Timestamp 是    当前 UNIX 时间戳，鄙人的SDK会在计算签名时添加。
$common["Action"] = 'ChatBot';           //Action    是    本接口取值：ChatBot。

//这里我们定义一个数组，存放的是公共参数
$param["Query"] = "你好";                //Query     是    用户请求的query
//["OpenId"]                             //OpenId    否    服务的id, 主要用于儿童闲聊接口，比如手Q的openid。
//["Flag"]                               //Flag      否    0: 通用闲聊, 1:儿童闲聊, 默认是通用闲聊

```

至此我们完成了全部设置，下面就可以发起请求了

我们先试试POST请求

```php
//我们可以用SendPost发送一个POST请求
$complete = $txCloud->SendPost($url, $common, $param);

//这里用来获取最近一次请求的curl命令，可在linux上直接运行，以方便调试
$curl = $txCloud->CurlCmd;

echo("POST:\n\n$complete\n\n$curl\n\n");


//运行后得到如下结果:
{"Response":{"Reply":"我挺好的呀","Confidence":0.877128,"RequestId":"99547497-0077-4269-8662-562fac6880dd"}}

curl -X POST https://nlp.tencentcloudapi.com \
-H "Content-Type: application/json" \
-H "Host: nlp.tencentcloudapi.com" \
-H "X-TC-Action: ChatBot" \
-H "X-TC-Region: ap-guangzhou" \
-H "X-TC-Timestamp: 1675888731" \
-H "X-TC-Version: 2019-04-08" \
-H "Authorization: TC3-HMAC-SHA256 Credential=AKIDLcq19D5i9NPH1Xoz0pSr43rfLvJUSDtc/2023-02-08/nlp/tc3_request, SignedHeaders=content-type;host, Signature=dbf5247183f99c1dec0c3991d2698c4634fd08a1a7bbfe8c5e117c5dc1d286a4" \
-d '{"Query":"\u4f60\u597d"}'
```

我们再来试试GET请求

```PHP
//我们可以用SendGet发送一个GET请求
$complete = $txCloud->SendGet($url, $common, $param);

//返回最近一次请求的curl命令
$curl = $txCloud->CurlCmd;

echo("GET:\n\n$complete\n\n$curl\n\n");

//运行后得到如下结果:
{"Response":{"Reply":"我挺好的呀","Confidence":0.877128,"RequestId":"9c12369f-3be3-4e71-99cb-0a838db17681"}}

curl -X GET https://nlp.tencentcloudapi.com?Query=%E4%BD%A0%E5%A5%BD \
-H "Content-Type: application/x-www-form-urlencoded" \
-H "Host: nlp.tencentcloudapi.com" \
-H "X-TC-Action: ChatBot" \
-H "X-TC-Region: ap-guangzhou" \
-H "X-TC-Timestamp: 1675888731" \
-H "X-TC-Version: 2019-04-08" \
-H "Authorization: TC3-HMAC-SHA256 Credential=AKIDLcq19D5i9NPH1Xoz0pSr43rfLvJUSDtc/2023-02-08/nlp/tc3_request, SignedHeaders=content-type;host, Signature=dc451ec5bf24566848673db955aaa672d111851133b17246f318ceaffa44d652" \
```

最后总结一下：

腾讯的NLP闲聊可真的差劲，不支持上下文关联，回答大多都是答非所问。相比最近大火的CahtGPT完全不是一个级别。

