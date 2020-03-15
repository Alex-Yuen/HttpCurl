# httpCurl for ThinkPHP6

## 安装

> composer require anqin/httpcurl

## 使用
该库暂不支持文件提交
>$http = new HttpCurl();
>
>$resp = $http->get('https://www.baidu.com')->exec();
>
>$resp = $http->post('https://www.baidu.com',['aaa'=>'bbb'])->exec();
>
>$resp->toString(); //返回字符串
>
>$resp->toArray(); //返回数组
>
>$resp->close();//释放内存,防止内存泄漏