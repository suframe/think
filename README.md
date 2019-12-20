# suframe-think
开发交流QQ群：647344518   [立即加群](http://shang.qq.com/wpa/qunwpa?idkey=83a58116f995c9f83af6dc2b4ea372e38397349c8f1973d8c9827e4ae4d9f50e)

使用thinkphp6作为suframe服务后端的扩展库，让thinkphp6有轻量级微服务功能    
本库基于https://github.com/top-think/think-swoole **(v3.0.5以上版本)**上进行的扩展，基本保持原有用法不变。   
**本系统只针对小型业务系统，大中规模的微服务架构是需要很多工具和系统支撑的，不在此项目服务覆盖内，所以别杠啥鉴权限流降级等**


有兴趣的朋友可以看看这篇新手引导文章：https://www.zacms.com/index.php/archives/566/

# 设计
- 通过composer create-project topthink/think创建的为一个独立项目
- 一个项目为一个服务提供者
- 一个服务提供者可以提供多个接口
- 自动注册接口到注册中心
- 有新服务注册，自动更新client列表
- api网关代理
- Swoole Tracker集成
- todo:日志及链路追踪

# 安装使用
```
composer create-project topthink/think server1
cd server1
composer require suframe/think
```
修改config/swoole.php
```
rpc.server.enable => true,
rpc.server.port => 自定义端口,
rpc.client => include(__DIR__ . '/suframeRpcClient.php');
```

修改config/suframeProxy.php
```
path => '/admin' 为你想注册的api网关根路径，例如/admin, 
name => 'admin' 为你服务的名称（只能是英文字母）
registerServer => [
    'ip' => '127.0.0.1' //选一个应用作为服务注册的应用，这里就选第一个
    'port' => 8091
]
```


# 快速体验

[https://github.com/suframe/think-demo](https://github.com/suframe/think-demo)

有兴趣的可以去看怎么从零搭建自己的服务：[https://www.zacms.com/index.php/archives/566/](https://www.zacms.com/index.php/archives/566/)

# api网关
服务拆分后，api分布比较散，需要统一对外暴露地址
打开app/middleware.php文件，增加
```
return [
    \suframe\think\middlewares\Gateway::class
    ...
]
```
访问：(网关端口和地址目前是你用于注册rpc的地址)
http://127.0.0.1:8090/apis/goods/hello/my
http://127.0.0.1:8090/apis/goods/hello/my

# 服务监控
使用swoole_tracker http://base.swoole-cloud.com/1214079,免费的
安装比较简单，无侵入。照着手册撸即可   
需要注意的是swoole_tracker免费版的额度比较低，我一个压测就没有额度了。有钱的大佬可以买私有化部署，1万起

# 分布式日志
微服务化开发，没有统一日志进行链路追踪是很痛苦的事情，不过就是那么巧，think-swoole扩展性还有待提高，目前还没有办法
把一个请求的request_id一层层传下去并生成统一日志。 

这个我只能想折中的方法了，只是这样业务强绑定了，开发体验不是很好.

1. 在前端网关转发的是，后端服务controller可从header中获取--request_id-- 作为链路请求标识。
2. rpc接口约定好，定义接口的时候，最后一个参数定义$ext = [], 用于扩展 
3. 在controller或者其他业务代码中调用rcp接口的时候， $rpc->method([业务参数], $this->getRpcExtParams()), 其中$this->getRpcExtParams()就是系统默认增加的额外参数，当然前提是你控制器要use suframe\think\traits\ControllerHelper;
4. 日志，tp6的日志比较灵活，意思就是要自己写，包括请求日志，等你自己想获取的

已经反馈社区看如何处理














