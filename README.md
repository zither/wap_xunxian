QQ：792774502，Lin.仅供分享学习使用！

环境配置（请严格按照此环境配置，尤其是数据库版本）：
nginx1.25(可选)
mysql5.5(必选，低版本会出现未知错误，高版本部分数据库语句会不支持)
php7.4(必选。低版本不支持部分函数，高版本部分函数弃用直接报错！)

建议开启：opcache插件，这将极大提升游戏体验和服务器效率！

使用前必须导入数据库：xunxian.sql，
数据库的配置如下
表名：xunxian
用户名：xunxian
密码：123456

数据库地址：127.0.0.1(测试在小皮环境中localhost会极度卡顿，服务端无视，若有类似问题可搜索修改)


管理员账号：123456
管理员密码：123456


大厅设计文档有教程。
频繁回退容易产生bug。


服务器上运行性能和效率优于本地环境。

补丁集合适用于老版本，新版本无视，将里面文件寻找到对应位置覆盖即可，一般是首页，class文件夹，以及module_all文件夹。
