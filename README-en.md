README: [English](https://github.com/Double-Jin/jin-chat/blob/master/README-en.md) | [中文](https://github.com/Double-Jin/jin-chat/blob/master/README.md)

# statement

> Jin-chat is a complete PC chat system based on EasySwoole and PHP
> 
> Approved by https://github.com/woann/chat, the author use EasySwoole V3 all reconstructed the server-side code, on the basis of the original function increase the Token authentication, Redis/mysql coroutines connection pool, Task asynchronous tasks such as new features
> 
-   This project is based on EasySwoole V3 as a service. EasySwoole is a high-performance asynchronous framework that encapsulates swoole's expansion while retaining swoole's original features, and aims to provide PHP developers with an efficient, fast, and elegant framework.
So before this, you must be familiar with swoole, EasySwoole, and combine their ` EasySwoole ` < https://www.easyswoole.com >
-   H5 part is layui, hereby solemnly state that the im part 'layim' in layui is not open source, only for communication and learning, do not use layim in this project for commercial purposes.
-  This demo is helpful to understand the introduction of EasySwoole and the application of websocket in the business. The code has not been formally tested and encapsulated, and basically only achieves the function, and the logic of the service side cannot be used in the production environment.

# Basic operating environment

-   Ensure **PHP** version is greater than or equal to **7.1**
-   Ensure **Swoole** expanded version is greater than or equal to **4.3.0**
-   Any version of ** PCNTL ** expansion is required
-   Use **Linux** / **FreeBSD** / **MacOS** three operating systems
-   Use **Composer** as a dependency management tool

# Feature list

* Token authentication
* Mysql coroutine connection pool
* Redis coroutine connection pool
* Task asynchronous task
* Login, registration
* Find - add friends
* Find - join group
* Create a group
* The message box
* Individuality signature
* One-on-one chat, group chat
* The historical record
* Offline message
* In the skin

## installing

-   Perform the install command ` git clone https://github.com/Double-Jin/jin-chat.git ` will clone to a local project
-   `composer install` 
-   Import SQL, there is a 'chat.sql' file in the project root directory, import the SQL file into the database
-   Modify 'dev.php' file, configure mysql/redis and other parameters
-   Configure the nginx agent
```
server {
    root /data/wwwroot/chat;
    server_name es-chat.cc;
    location / {
        proxy_http_version 1.1;
        proxy_set_header Connection "keep-alive";
        proxy_set_header X-Real-IP $remote_addr;
        if (!-f $request_filename) {
             proxy_pass http://127.0.0.1:9501;
        }
    }
}
```
-   change`App\HttpController\index.php的index`action`$hostName`Variable is the current domain ws address
-   run EasySwoole ` php easyswoole server start`
-   At this point, visit 'es-chat.cc' to enter the login page
-   Test account 'test1' - 'test2' password is' 123456 ', of course, you can also register.

## img


![图片](https://cdn.learnku.com/uploads/images/201907/01/36324/hDVIioONoy.jpeg!large)

![图片](https://cdn.learnku.com/uploads/images/201907/01/36324/6SFf5jVpYs.jpeg!large)
![chat](https://cdn.learnku.com/uploads/images/201907/01/36324/7fkqjRARXh.jpeg!large)
![图片](https://cdn.learnku.com/uploads/images/201907/01/36324/HimqXRDLRm.jpeg!large)

![图片](https://cdn.learnku.com/uploads/images/201907/01/36324/vThT4zh5Fy.jpeg!large)
