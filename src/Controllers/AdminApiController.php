<?php

namespace Micaomao\NmsAdmin\Controllers;

use Micaomao\NmsAdmin\Admin;
use Illuminate\Support\Str;
use Micaomao\NmsAdmin\Services\AdminApiService;

/**
 * @property AdminApiService $service
 */
class AdminApiController extends AdminController
{
    public string $serviceName = AdminApiService::class;

    public function index()
    {
        $path = Str::of(request()->path())->replace(Admin::config('admin.route.prefix'), '')->value();
        $api  = $this->service->getApiByPath($path);

        if (!$api) {
            return $this->response()->success();
        }

        return app($api->template)->handle();
    }
}
