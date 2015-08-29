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
