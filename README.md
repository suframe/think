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
- todo:Swoole Tracker集成（未完成）

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













