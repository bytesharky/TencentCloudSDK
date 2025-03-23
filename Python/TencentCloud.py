# -*- coding: utf-8 -*-

import time
import json
import hmac
import hashlib
import requests
from urllib.parse import urlparse, urlencode


class TencentCloud:
    def __init__(self, secret_id, secret_key, timeout=10):
        self.secret_id = secret_id
        self.secret_key = secret_key
        self.timeout = timeout
        self.curl_cmd = ""

    def send_get(self, url, common, param):
        build = self.build(url, common, param, False)
        return self.send_http(build["url"], build["headers"], build["param"], False)

    def send_post(self, url, common, param):
        build = self.build(url, common, param, True)
        return self.send_http(build["url"], build["headers"], build["param"], True)

    def lastCurl(self):
        return self.curl_cmd

    def build(self, url, common, param, is_post):

        if not url.startswith("http://") and not url.startswith("https://"):
            raise ValueError('参数："url" 应该以 https:// 或 http:// 开头')
        
        if isinstance(common, str):
            common = json.loads(common)
        if not isinstance(common, dict):
            raise ValueError('参数："common" 应该是一个字典或JSON字符串')
        
        if isinstance(param, str):
            param = json.loads(param)
        if not isinstance(param, dict):
            raise ValueError('参数："param" 应该是一个字典或JSON字符串')
        
        build_time = int(time.time())
        parsed_url = urlparse(url)
        host = parsed_url.netloc
        service = host.split(".")[0]

        headers = {
            "Host": host,
            "Content-Type": "application/json" if is_post else "application/x-www-form-urlencoded",
            "X-TC-Timestamp": str(build_time)
        }
        
        for key, val in common.items():
            headers[f"X-TC-{key}"] = str(val)
        
        # 过滤掉以 "x-tc-" 开头的 headers，不参与签名计算
        filtered_headers = {k: v for k, v in headers.items() if not k.lower().startswith("x-tc-")}

        canonical_query_string = "" if is_post else urlencode(param, safe='')
        canonical_headers = "\n".join(f"{k.lower()}:{v}" for k, v in sorted(filtered_headers.items())) + "\n"
        signed_headers = ";".join(k.lower() for k in sorted(filtered_headers))
        hashed_payload = hashlib.sha256(json.dumps(param, separators=(",", ":")).encode() if is_post else b'').hexdigest()
        
        canonical_request = f"{('POST' if is_post else 'GET')}\n/\n{canonical_query_string}\n{canonical_headers}\n{signed_headers}\n{hashed_payload}"

        credential_scope = f"{time.strftime('%Y-%m-%d', time.gmtime(build_time))}/{service}/tc3_request"
        string_to_sign = f"TC3-HMAC-SHA256\n{build_time}\n{credential_scope}\n{hashlib.sha256(canonical_request.encode()).hexdigest()}"

        secret_date = hmac.new(("TC3" + self.secret_key).encode(), time.strftime('%Y-%m-%d', time.gmtime(build_time)).encode(), hashlib.sha256).digest()
        secret_service = hmac.new(secret_date, service.encode(), hashlib.sha256).digest()
        secret_signing = hmac.new(secret_service, b"tc3_request", hashlib.sha256).digest()
        signature = hmac.new(secret_signing, string_to_sign.encode(), hashlib.sha256).hexdigest()
        
       
        headers["Authorization"] = (f"TC3-HMAC-SHA256 Credential={self.secret_id}/{credential_scope}, "
                                     f"SignedHeaders={signed_headers}, Signature={signature}")
        
        return {"url": url, "headers": headers, "param": param}

    def send_http(self, url, headers, data, is_post):
        try:
            if (is_post):
                json_data = json.dumps(data, separators=(',', ':'))
                response = requests.post(url, headers=headers, data=json_data, timeout=self.timeout)
                self.curl_cmd = self.requests_to_curl(url, 'POST', headers, data, self.timeout)
            else:
                response = requests.get(url, headers=headers, params=data, timeout=self.timeout)
                self.curl_cmd = self.requests_to_curl(url, 'GET', headers, data, self.timeout)


            response.raise_for_status()
            return response.text
        except requests.exceptions.RequestException as e:
            return json.dumps({"state": "network error", "error": str(e)})
        
    def requests_to_curl(self, url, method, headers=None, data=None, timeout=None):
        # 构建基本的 curl 命令
        curl_command = f"curl -X {method} {url} \\\n"

        # 处理请求头
        if headers:
            for key, value in headers.items():
                curl_command += f' -H "{key}: {value}"  \\\n'

        # 处理请求数据
        if data:
            json_data = json.dumps(data, separators=(',', ':'))
            curl_command += f' -d \'{json_data}\''

        # 处理超时时间
        if timeout:
            curl_command += f' --connect-timeout {timeout} --max-time {timeout}'

        return curl_command