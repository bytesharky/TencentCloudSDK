# -*- coding: utf-8 -*-

import TencentCloud

SecretId = "******"
SecretKey = "******"

tencentapi = TencentCloud.TencentCloud(SecretId, SecretKey)

url = "https://nlp.tencentcloudapi.com"

common = {
    "Action" : "ChatBot",
    "Version" : "2019-04-08",
    "Region" : "ap-guangzhou"
}

param = {
    "Query" : "Hollo Word"
}


response = tencentapi.send_post(url, common, param)

print(response)
