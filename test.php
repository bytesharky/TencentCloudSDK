<?php
ini_set("display_errors", "On"); 
error_reporting(E_ALL); //显示所有错误信息

require('./TencentCloud.php');


$SecretId = "******";
$SecretKey = "******";

$txCloud = new TencentCloud($SecretId, $SecretKey);

$url = "https://nlp.tencentcloudapi.com";

//公共参数
$common["Version"] ='2019-04-08';        //Version   是    String    本接口取值：2019-04-08。
$common["Region"]  = 'ap-guangzhou';     //Region    是    String    地域列表。
//$header["Timestamp"] = time();         //Timestamp 是    String    当前 UNIX 时间戳，由鄙人的SDK添加。
$common["Action"] = 'ChatBot';           //Action    是    String    本接口取值：ChatBot。

$param["Query"] = "你好";                //Query     是    String    用户请求的query
//["OpenId"]                             //OpenId    否    String    服务的id, 主要用于儿童闲聊接口，比如手Q的openid。
//["Flag"]                               //Flag      否    Integer    0: 通用闲聊, 1:儿童闲聊, 默认是通用闲聊

//发送一个POST请求
$complete = $txCloud->SendPost($url, $common, $param);

//返回最近一次请求的curl命令
$curl = $txCloud->CurlCmd;

echo("POST:\n\n$complete\n\n$curl\n\n");

//发送一个GET请求
$complete = $txCloud->SendGet($url, $common, $param);

//返回最近一次请求的curl命令
$curl = $txCloud->CurlCmd;

echo("GET:\n\n$complete\n\n$curl\n\n");
?>