<?php

class TencentCloud
{

    private $SecretId;
    private $SecretKey;
    private $timeout;
    private $CurlCmd = "";

    public function __construct($SecretId, $SecretKey, $timeout = 10)
    {
        $this->SecretId = $SecretId;
        $this->SecretKey = $SecretKey;
        $this->timeout = $timeout;
        $this->CurlCmd = "";
    }

    public function SendGet($url, $common, $param){

        $build = $this->Build($url, $common, $param, false);

        $complete = $this->SendHttp($build["url"], $build["header"], $build["param"], false);

        $this->CurlCmd = $complete["curl"];
        if($complete['state']=='success'){
            return $complete["body"];
        }
        return '{"state":"network error"}';
    }

    public function SendPost($url, $common, $param){

        $build = $this->Build($url, $common, $param, true);

        $complete = $this->SendHttp($build["url"], $build["header"], $build["param"], true);
        
        $this->CurlCmd = $complete["curl"];
        if($complete['state']=='success'){
            return $complete["body"];
        }
        return '{"state":"network error"}';
    }

    public function lastCurl(){
        return $this->CurlCmd;
    }

    private function Build($url, $common, $param, $post){
        
        //打包时间
        $buildTime = time();

        if(!preg_match('/^http[s]?:\/\//',$url))
            throw new Exception('参数："$url"应该以https://或http://开头的网址');

        if(is_string($common))
            $common = json_decode($common, true);

        if(!is_array($common) || count($common) != count($common,1))
            throw new Exception('参数："$common"应该是一个一维数组或者json字符串');

        if(is_string($param))
            $param = json_decode($param, true);

        if(!is_array($param) || count($param) != count($param,1))
            throw new Exception('参数："$param"应该是一个一维数组或者json字符串');

        //必要的请求头
        $header["Host"] = parse_url($url)["host"];
        $header["Content-Type"] = $post?"application/json":"application/x-www-form-urlencoded";

        //公共参数写请求头中
        //Key需要加前缀X-TC-
        foreach($common as $key=>$val)
            $header["X-TC-$key"] = "$val";

        //公共参数添加时间戳
        $header["X-TC-Timestamp"] = $buildTime;

        //计算签名 Ver:3
        //Step0
        ksort($header);
        $keystr = $this->arr2str(array_change_key_case($header));
        date_default_timezone_set('UTC');
        $Service = explode('.',$header["Host"],2)[0];
        $Signing = "tc3_request";

        //Step1
        $CanonicalRequest =
        ($post?"POST":"GET")."\n/\n".
        ($post?"":http_build_query($param,'',null,PHP_QUERY_RFC3986))."\n".
        $keystr['arr']."\n".
        $keystr['key']."\n".
        hash('sha256',($post?(empty($param)?"{}":json_encode($param)):""));

        //Step2
        $Algorithm = "TC3-HMAC-SHA256";
        $CredentialScope = date('Y-m-d',$buildTime)."/".$Service."/".$Signing;
        $StringToSign = $Algorithm."\n".
        "$buildTime\n".
        $CredentialScope."\n".
        hash('sha256',$CanonicalRequest);

        //Step3
        $SecretDate = hash_hmac('sha256',date('Y-m-d'), "TC3".$this->SecretKey, true);
        $SecretService = hash_hmac('sha256', $Service , $SecretDate, true);
        $SecretSigning = hash_hmac('sha256',$Signing, $SecretService, true);
        $Signature = hash_hmac("SHA256", $StringToSign, $SecretSigning);

        //Step4
        $Authorization = $Algorithm.' '.
            'Credential=' . $this->SecretId . '/' . $CredentialScope . ', ' .
            'SignedHeaders=' . $keystr['key'] . ', ' .
            'Signature=' . $Signature;

        $header["Authorization"] = $Authorization;

        return ["url"=>$url, "header"=>$header, "param"=>$param, "post"=>$post];
    }

    private function arr2str($arr){
        $vals = "";
        $keys = [];
        foreach($arr as $k=>$v)
        {
            if(strtolower(substr($k,0, 5)) == "x-tc-") continue;
            $vals .= "$k:$v\n";
            $keys[] = "$k";
        }

        return ['arr'=>$vals,'key'=>implode(";",$keys)];
    }

    private function SendHttp($url, $header, $data, $IsPost, $cookie = false){

        //标准化请求头格式
        $headers = array(); 
        foreach($header as $n => $v) {
            if (is_numeric($n))
                $headers[] = $v;
            else
                $headers[] = $n.':'.$v;
        }
        

        //处理请求数据
        if (!$IsPost){
            if(!empty($data))
                $url = $url."?".(is_array($data)?http_build_query($data):$data);
            }else{
                if(!empty($data))
                    $data = is_array($data)?json_encode($data):$data;
                else
                    $data = "{}";
        }

        //curl请求命令
        $curlcmd = "curl -X ".($IsPost?"POST":"GET")." $url \\\n";
        foreach($headers as $k=>$v)
        {
            $curlcmd .= "-H \"$v\" \\\n";
        }
        if($IsPost)
            $curlcmd .= "-d "."'$data'\n";

        $http = curl_init ($url);                                                          //初始化一个CUR类
        curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);               //是否验证证书由授信CA颁发
        curl_setopt($http, CURLOPT_SSL_VERIFYHOST, false);               //是否验证域名与证书一致
        curl_setopt($http, CURLOPT_ENCODING, 'UTF-8');                   //解析压缩格式
        curl_setopt($http, CURLOPT_HTTPHEADER, $headers);                //构造请求头
        curl_setopt($http, CURLOPT_POST, $IsPost);                       //是POST或GET
        curl_setopt($http, CURLOPT_HEADER, 1);                           //取得http头
        curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);                   //结果保存到变量
        curl_setopt($http, CURLOPT_CONNECTTIMEOUT,$this->timeout);       //连接前N秒未响应超时
        curl_setopt($http, CURLOPT_TIMEOUT,$this->timeout);              //连接在N秒后超时
        
        if ($IsPost)
            curl_setopt($http, CURLOPT_POSTFIELDS, $data);               //发送的数据

        $Response = curl_exec ($http);                                                  //执行并取得返回值
        
        if (curl_errno($http)>0){
            $error = curl_error($http);
            curl_close ($http);                                                          //关闭CURL连接资源
            return array('state'=> $error, 'curl'=>$curlcmd);
        }else{
            $hSize = curl_getinfo($http, CURLINFO_HEADER_SIZE);                  //取得响应头大小
            $headers = substr($Response, 0, $hSize);                     //取出 Header
            $Body = substr($Response, $hSize);                                   //取出 Body
            curl_close ($http);                                                          //关闭CURL连接资源
            return array('state'=>'success','header'=>$headers,'body'=>$Body, 'curl'=>$curlcmd) ;
        }
    }
}
