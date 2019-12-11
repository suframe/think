# suframe-think
使用thinkphp6作为suframe服务后端的扩展库，让thinkphp6有轻量级微服务功能    
本库基于https://github.com/top-think/think-swoole **(v3.0.5以上版本)**上进行的扩展，基本保持原有用法不变。

#  设计
- 通过composer create-project topthink/think创建的为一个独立项目
- 一个项目为一个服务提供者
- 一个服务提供者可以提供多个接口
- 自动注册接口到注册中心
- 有新服务注册，自动更新client列表
- 

