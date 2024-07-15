<?php

namespace Micaomao\NmsAdmin;

use Laravel\Sanctum\Sanctum;
use Micaomao\Support\SqlRecord;
use Illuminate\Support\Facades\Auth;
use Micaomao\NmsAdmin\Extend\Manager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Micaomao\NmsAdmin\Traits\AssetsTrait;
use Micaomao\NmsAdmin\Extend\ServiceProvider;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Micaomao\NmsAdmin\Models\PersonalAccessToken;
use Micaomao\NmsAdmin\Services\AdminSettingService;
use Micaomao\NmsAdmin\Models\{AdminMenu, AdminRole, AdminUser, AdminPermission};
use Micaomao\NmsAdmin\Support\{Context,
    Composer,
    Cores\Api,
    Cores\Route,
    Cores\Module,
    Cores\Permission,
    Cores\JsonResponse,
    Cores\Relationships
};

class Admin
{
    use AssetsTrait;

    public static function make(): static
    {
        return new static();
    }

    public static function boot()
    {
        Relationships::boot();
        Api::boot();

        if (class_exists(Sanctum::class)) {
            Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        }

        if (class_exists('\Laravel\Octane\Events\RequestReceived')) {
            Event::listen(\Laravel\Octane\Events\RequestReceived::class, function ($event) {
                SqlRecord::$sql = [];
            });
        }
    }

    public static function response()
    {
        return new JsonResponse();
    }

    /**
     * @return \Micaomao\NmsAdmin\Support\Cores\Menu;
     */
    public static function menu()
    {
        return app('admin.menu');
    }

    /**
     * @return Permission
     */
    public static function permission()
    {
        return new Permission;
    }

    public static function guard()
    {
        return Auth::guard(self::config('admin.auth.guard') ?: 'admin');
    }

    /**
     * @return \App\Models\User|\Illuminate\Contracts\Auth\Authenticatable|null
     */
    public static function user()
    {
        return static::guard()->user();
    }

    public static function bootstrap()
    {
        $file = self::config('admin.bootstrap');

        if (is_file($file)) {
            require self::config('admin.bootstrap');
        }
    }

    /**
     * 加载框架路由
     *
     * @return void
     */
    public static function loadBaseRoute()
    {
        Route::baseLoad();
    }

    /**
     * @param string|null $name
     *
     * @return Manager|ServiceProvider|null
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function extension(?string $name = '')
    {
        if ($name) {
            return app('admin.extend')->get($name);
        }

        return app('admin.extend');
    }

    public static function classLoader()
    {
        return Composer::loader();
    }

    /**
     * 上下文管理.
     *
     * @return Context
     */
    public static function context()
    {
        return app('admin.context');
    }

    /**
     * @return AdminSettingService
     */
    public static function setting()
    {
        return app('admin.setting');
    }

    /**
     * 往分组插入中间件.
     *
     * @param array $mix
     */
    public static function mixMiddlewareGroup(array $mix = [])
    {
        Route::mixMiddlewareGroup($mix);
    }

    /**
     * @return AdminMenu
     */
    public static function adminMenuModel()
    {
        return self::config('admin.models.admin_menu', AdminMenu::class);
    }

    /**
     * @return AdminPermission
     */
    public static function adminPermissionModel()
    {
        return self::config('admin.models.admin_permission', AdminPermission::class);
    }

    /**
     * @return AdminRole
     */
    public static function adminRoleModel()
    {
        return self::config('admin.models.admin_role', AdminRole::class);
    }

    /**
     * @return AdminUser
     */
    public static function adminUserModel()
    {
        return self::config('admin.models.admin_user', AdminUser::class);
    }

    /**
     * @return Module
     */
    public static function module()
    {
        return app('admin.module');
    }

    /**
     * 当前模块
     *
     * @param bool $lower
     *
     * @return mixed|string|null
     */
    public static function currentModule(bool $lower = false)
    {
        return Module::current($lower);
    }

    public static function config($key, $default = '')
    {
        if ($module = self::currentModule(true)) {
            return config($module . '.' . $key, $default);
        }

        return config($key, $default);
    }

    public static function view($apiPrefix = '')
    {
        if (!$apiPrefix) {
            $apiPrefix = self::config('admin.route.prefix');
        }

        if (is_file(public_path('admin-assets/index.html'))) {
            $view = file_get_contents(public_path('admin-assets/index.html'));
        } else {
            $view = file_get_contents(base_path('vendor/daga/nms-admin/admin-views/dist/index.html'));
        }

        $script = '<script>window.$adminApiPrefix = "/' . $apiPrefix . '"</script>';

        return preg_replace('/<script>window.*?<\/script>/is', $script, $view);
    }

    public static function hasTable($table)
    {
        $key = 'admin_has_table_' . $table;
        if (cache()->has($key)) {
            return true;
        }

        $has = Schema::hasTable($table);

        if ($has) {
            cache()->forever($key, true);
        }

        return $has;
    }
}
