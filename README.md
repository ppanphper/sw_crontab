SWC(Swoole-Crontab)分布式定时任务系统
==============
1.概述
--------------
+ 本系统参考[osgochina/Donkey](https://github.com/osgochina/Donkey)代码实现的.
+ 基于swoole的定时器程序，支持秒级处理.
+ 异步多进程处理.
+ 完全兼容crontab语法，且支持秒的配置.
+ 去中心-多客户端模式，能够横向扩展.
+ web界面管理，增删改查任务，完整的权限控制.
+ 依赖：需要安装pcre，pcre-devel(安装完成后，已经安装swoole扩展的需要重新编译swoole).

2.架构图
--------------
![架构图](https://raw.github.com/ppanphper/sw_crontab/master/%E6%9E%B6%E6%9E%84%E5%9B%BE.png)

3.流程图
--------------
![流程图](https://raw.github.com/ppanphper/sw_crontab/master/%E6%B5%81%E7%A8%8B%E5%9B%BE.png)

4.Crontab配置
--------------
介绍一下时间配置

    0   1   2   3   4   5
    |   |   |   |   |   |
    |   |   |   |   |   +------ day of week (0 - 6) (Sunday=0)
    |   |   |   |   +------ month (1 - 12)
    |   |   |   +-------- day of month (1 - 31)
    |   |   +---------- hour (0 - 23)
    |   +------------ min (0 - 59)
    +-------------- sec (0-59)[可省略，如果没有0位，则最小时间粒度是分钟]
    
5.环境要求
--------------
| Requirements                  | 1.0.*                         |
|-------------------------------|-------------------------------|
| [PHP](https://php.net)        | 5.6+                          |
| [Mysql](https://dev.mysql.com/downloads/)| 5.6+               |
| [Swoole](http://pecl.php.net/package/swoole)| 1.10.0+         |
| [Redis](http://pecl.php.net/package/redis)| 2.2.8+            |
| [PDO_Mysql](http://pecl.php.net/package/pdo_mysql)| 1.0.2+    |
| [Openssl](https://www.openssl.org/)| 1.0+                     |
| [Pcre](http://www.pcre.org/)| 8.0+                            |
| [Pcre-devel](https://pkgs.org/download/pcre-devel)| 7.8+      |
| Install with Composer...      | ~1.4                          |

6.开始使用
--------------
6.1 使用Docker运行
--------------
1.修改环境配置文件

    admin/web/env.php
    agent/env.php

2.管理后台的配置文件修改

    /path/to/admin/config/mailer.php 修改邮件配置
    /path/to/admin/config/params.php 修改其他参数配置
    
3.Agent配置文件修改

    /path/to/agent/Config/email.php 修改邮件配置
    
4.启动

    需要先启动docker引擎
    cd /path/to/dnmp 目录
    docker-compose up -d
    
6.2 普通方式运行
--------------
1.安装环境依赖

    yum install -y pcre pcre-devel
    编译安装swoole、openssl、redis等扩展

2.安装代码依赖包:

    1、curl -sS https://getcomposer.org/installer | /path/to/php
    2、mv composer.phar /usr/local/bin/composer
    3、分别进入前后端项目目录: cd /path/to/admin 和 cd /path/to/agent
    4、执行安装依赖的类库: /usr/local/bin/php /usr/local/bin/composer install
      (期间可能需要提供GitHub的token，自己创建一个[https://github.com/settings/tokens/new?scopes=repo&description=Composer+on+localhost.localdomain+2018-05-18+1719])
      
3.管理后台的配置文件修改

    /path/to/admin/config/dev/db.php 修改数据库配置
    /path/to/admin/config/dev/redis.php 修改Redis配置
    /path/to/admin/config/mailer.php 修改邮件配置
    /path/to/admin/config/params.php 修改其他参数配置
    
4.Agent配置文件修改

    /path/to/agent/Config/dev/db.php 修改数据库配置
    /path/to/agent/Config/dev/redis.php 修改Redis配置
    /path/to/agent/Config/email.php 修改邮件配置
    
5.启动agent节点

    1、使用Supervisord守护进程启动，把agent目录下的swcAgent.conf配置文件放到Supervisord的include目录下，修改执行路径
        然后使用命令启动: supervisorctl start swcAgent
    2、不使用Supervisord启动，直接使用命令: /usr/local/bin/php /path/to/agent/agent.php start; 启动supervisor会自动运行这个command
    
6.启动监控节点脚本(监控所有节点是否正常，如果没有上报，会有报警邮件通知管理人员)

    1、使用Supervisord守护进程启动，把admin目录下的swcMonitor.conf配置文件放到Supervisord的include目录下，修改执行路径
        然后使用命令启动: supervisorctl start swcMonitor
    2、不使用Supervisord启动，直接使用命令: /usr/local/bin/php /path/to/admin/yii monitor; 启动supervisor会自动运行这个command
    
7.帮助信息
--------------
```
* Usage: /path/to/agent/agent.php start|restart|stop|reload|stats
```

8.TODO
--------------
- [ ] 任务工作流: 后面的任务依赖前一个任务的执行结果(任务执行顺序?)。
- [ ] 灾难转移: 如果任务允许多台服务器执行，本次在这台服务器执行失败，可以转到其他机器再次尝试执行
- [ ] 管理后台手动指定机器运行任务
