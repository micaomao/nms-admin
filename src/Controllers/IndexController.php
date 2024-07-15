<?php

namespace Micaomao\NmsAdmin\Controllers;

use Micaomao\NmsAdmin\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Micaomao\NmsAdmin\Models\Extension;
use Micaomao\NmsAdmin\Services\AdminPageService;
use Illuminate\Http\Resources\Json\JsonResource;

class IndexController extends AdminController
{
    public function menus(): JsonResponse|JsonResource
    {
        return $this->response()->success(Admin::menu()->all());
    }

    public function noContentResponse(): JsonResponse|JsonResource
    {
        return $this->response()->successMessage();
    }

    public function settings(): JsonResponse|JsonResource
    {
        $module = Admin::currentModule(true);
        $prefix = $module ? $module . '_' : '';

        return $this->response()->success([
            'nav'      => Admin::getNav(),
            'assets'   => Admin::getAssets(),
            'app_name' => Admin::config('admin.name'),
            'locale'   => config('app.locale'),
            'layout'   => Admin::config('admin.layout'),
            'logo'     => url(Admin::config('admin.logo')),

            'login_captcha'          => Admin::config('admin.auth.login_captcha'),
            'show_development_tools' => Admin::config('admin.show_development_tools'),
            'system_theme_setting'   => Admin::setting()->get($prefix . 'system_theme_setting'),
            'enabled_extensions'     => Extension::query()->where('is_enabled', 1)->pluck('name')?->toArray(),
        ]);
    }

    /**
     * 保存设置项
     *
     * @param Request $request
     *
     * @return JsonResponse|JsonResource
     */
    public function saveSettings(Request $request)
    {
        $data          = $request->all();
        $currentModule = Admin::currentModule(true);

        foreach ($data as $key => $value) {
            if ($key == 'system_theme_setting' && $currentModule) {
                $data[$currentModule . '_' . $key] = $value;
                unset($data[$key]);
            }
        }

        Admin::setting()->setMany($data);

        return $this->response()->successMessage();
    }

    /**
     * 下载导出文件
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadExport(Request $request)
    {
        return response()->download(storage_path('app/' . $request->input('path')))->deleteFileAfterSend();
    }

    /**
     * 图标搜索
     *
     * @return JsonResponse|JsonResource
     */
    public function iconifySearch()
    {
        $query = request('query', 'home');

        $icons = file_get_contents(owl_admin_path('/Support/iconify.json'));
        $icons = json_decode($icons, true);

        $items = [];
        foreach ($icons as $item) {
            if (str_contains($item, $query)) {
                $items[] = ['icon' => $item];
            }
            if (count($items) > 999) {
                break;
            }
        }

        $total = count($items);

        return $this->response()->success(compact('items', 'total'));
    }

    /**
     * 获取页面结构
     *
     * @return JsonResponse|JsonResource
     */
    public function pageSchema()
    {
        return $this->response()->success(AdminPageService::make()->get(request('sign')));
    }
}
