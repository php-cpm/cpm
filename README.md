# cpm
php Composer(or Chinese) Package manager. Installs, publishes and manages node programs.
First Usage
Search Package in Console
```
cpm search monolog
```
you got things like this

 **monolog/monolog Sends** your logs to files, sockets, inboxes, databases and various web services
 
 **symfony/monolog-bundle** Symfony MonologBundle 
 
 **symfony/monolog-bridge** Symfony Monolog Bridge
 
 **flynsarmy/slim-monolog** Monolog logging support Slim Framework
 
......
Thinking of using cpm with composer
```
composer install monolog/monolog
```


国内的行情让我思考

我决定做一套能满足80%的开发者的精简版composer服务

是的，从packagist.org上获取1.8W个项目，重新生成packages.json和providers.json

提供本地搜索功能

提供开发者喜欢的开源项目推荐和介绍文章（项目名、用途、tags、Github地址、docs地址）

提供包的分类查看服务

提供其他能帮助php开发者的服务

全部免费，并且提供镜像备份功能，一旦网站运营不下去了，开发者仍可以通过github获取网站备份自行搭建

通过1周紧张的开发，实现了获取7700余个命名空间，1.8W个项目信息文件的下载自动化

实际生成文件大小约为215MB

相比原来全量下载8W项目包共600MB体积极大的减少，并且测试获取常见、优质项目资源稳定高效


HOW TO USE

在现在的开发环境中迁出一份代码

从项目目录执行命令

```
bin/cpm search monolog
```
可以查询带有monolog相关字眼的项目，以下载和收藏量做排序
```
bin/cpm-server build
```
可以在本地重建一套静态composer库，配置文件尚未独立出来，有些url地址还是需要手工修改

