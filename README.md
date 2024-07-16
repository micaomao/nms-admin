<div align="center">
    <br/>
    <h1 align="center">
        Micaomao Admin
    </h1>
    <h4 align="center">
        快速且灵活的后台框架
    </h4> 
</div>

<br>

### 项目介绍

基于 `Laravel` 、 `amis` 、 `mysql/pgsql` 开发的后台框架, 快速且灵活~

- 基于 amis 以 json 的方式在后端构建页面，减少前端开发工作量，提升开发效率。
- 在 amis 150多个组件都不满足的情况下, 可自行开发前端。
- 框架为前后端分离 (不用再因为框架而束手束脚~)。

<br>

### 内置功能

- 基础后台功能
    - 后台用户管理
    - 角色管理
    - 权限管理
    - 菜单管理
- **代码生成器**
    - 保存生成记录
    - 导入/导出生成记录
    - 可使用命令清除生成的内容
    - 无需更改代码即可生成完整功能
- `amis` 全组件封装 150+ , 无需前端开发即可完成复杂页面
- 多模块支持
- 图形化扩展管理

<br>


### 安装

> 👉 __注意: `NmsAdmin` 是 `laravel` 的扩展包, 安装前请确保你会使用 `laravel`__

##### 1. 创建 `laravel` 项目

```php
composer create-project laravel/laravel example-app
```

##### 2. 配置数据库信息

```dotenv
# .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nms_admin
DB_USERNAME=root
DB_PASSWORD=
```

> 如果你使用的是 laravel 11 , 还需要执行: `php artisan install:api`

##### 3. 获取 `Micaomao Admin`

```shell
composer require micaomao/nms-admin
```

##### 4. 安装

```shell
# 先发布框架资源
php artisan admin:publish
# 执行安装 (可以在执行安装命令前在 config/admin.php 中修改部分配置)
php artisan admin:install
```

##### 5. 运行项目

启动服务, 访问 `/admin` 路由即可 <br>
_初始账号密码都是 `admin`_


<br>

### 支持项目

你可以通过以下方式支持项目:

- 报告 Bug
- 提交 PR
  - 参见 [贡献文档](https://github.com/micaomao/nms-admin/blob/master/CONTRIBUTING.md)
- 点点 Star 
    - 如果觉得项目不错，或者已经在使用了，希望你可以去 [Github](https://github.com/micaomao/nms-admin)
或者 [Gitee](https://gitee.com/micaomao/nms-admin) 帮我们点个 ⭐ Star，这将是对我们极大的鼓励与支持。

[![Stargazers over time](https://starchart.cc/micaomao/nms-admin.svg?variant=adaptive)](https://github.com/micaomao/nms-admin)

<br>
<br>

感谢 [__JetBrains__](https://jb.gg/OpenSourceSupport) 提供的 `IDE` 支持

