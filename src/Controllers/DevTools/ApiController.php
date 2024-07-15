<?php

namespace Micaomao\NmsAdmin\Controllers\DevTools;

use Micaomao\NmsAdmin\Admin;
use Illuminate\Support\Str;
use Micaomao\NmsAdmin\Support\Cores\Api;
use Micaomao\NmsAdmin\Services\AdminApiService;
use Micaomao\NmsAdmin\Support\Apis\AdminBaseApi;
use Micaomao\NmsAdmin\Controllers\AdminController;

/**
 * @property AdminApiService $service
 */
class ApiController extends AdminController
{
    protected string $serviceName = AdminApiService::class;

    public function list()
    {
        $crud = $this->baseCRUD()
            ->filterTogglable(false)
            ->headerToolbar([
                $this->createButton(true, 'lg'),
                ...$this->baseHeaderToolBar(),
                $this->appTemplateBtn(),
            ])
            ->columns([
                amis()->TableColumn('id', 'ID')->sortable(),
                amis()->TableColumn('title', __('admin.apis.title'))->searchable(),
                amis()->TableColumn('path', __('admin.apis.path'))->searchable(),
                amis()->TableColumn('template_title', __('admin.apis.template')),
                amis()->TableColumn('enabled', __('admin.apis.enabled'))->quickEdit(
                    amis()->SwitchControl()->mode('inline')->saveImmediately(true)
                ),
                amis()->TableColumn('updated_at', __('admin.updated_at'))->type('datetime')->sortable(true),
                $this->rowActions([
                    $this->rowEditButton(true, 'lg'),
                    $this->rowDeleteButton(),
                ]),
            ]);

        return $this->baseList($crud);
    }

    public function appTemplateBtn()
    {
        return amis()
            ->DialogAction()
            ->label(__('admin.apis.add_template'))
            ->level('success')
            ->icon('fa fa-upload')
            ->dialog(
                amis()->Dialog()->title(__('admin.apis.add_template'))->body([
                    amis()->Form()->mode('normal')->api('/dev_tools/api/add_template')->body([
                        amis()->TextareaControl('template')
                            ->required()
                            ->minRows(10)
                            ->description(__('admin.apis.add_template_tips'))
                            ->placeholder(__('admin.apis.paste_template')),
                        amis()->SwitchControl('overlay', __('admin.apis.overlay'))->value(1),
                    ]),
                ])
            );
    }

    /**
     * 添加模板
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     */
    public function addTemplate()
    {
        $template  = request('template');
        $className = Str::between($template, 'class ', ' extends AdminBaseApi');
        if (!$className) {
            $className = Str::between($template, 'class ', ' extends \Micaomao\NmsAdmin\Support\Apis\AdminBaseApi');
        }

        admin_abort_if(!$className, __('admin.apis.template_format_error'));

        $file = Api::path($className . '.php');

        admin_abort_if(is_file($file) && !request('overlay'), __('admin.apis.template_exists'));

        try {
            app('files')->put($file, $template);
        } catch (\Throwable $e) {
            return $this->response()->fail(__('admin.save_failed'));
        }

        return $this->response()->successMessage(__('admin.save_success'));
    }

    public function form()
    {
        return $this->baseForm()->body([
            amis()->TextControl('title', __('admin.apis.title'))->required(),
            amis()->TextControl('path', __('admin.apis.path'))->required(),
            amis()->SwitchControl('enabled', __('admin.apis.enabled'))->value(1),
            amis()->SelectControl('template', __('admin.apis.template'))
                ->required()
                ->searchable()
                ->source('/dev_tools/api/templates'),
            amis()->ComboControl('args', __('admin.apis.args'))
                ->visibleOn('${template}')
                ->multiLine()
                ->strictMode(false)
                ->items([
                    amis()->Service()->initFetch()->schemaApi('get:/dev_tools/api/args_schema?template=${template}'),
                ]),
        ]);
    }


    public function detail($id)
    {
        return $this->baseDetail()->body([]);
    }

    public function template()
    {
        $apis = collect(Admin::context()->apis)
            ->filter(fn($item) => (new \ReflectionClass($item))->isSubclassOf(AdminBaseApi::class))
            ->map(fn($item) => [
                'label' => app($item)->getMethod() . ' - ' . app($item)->getTitle(),
                'value' => $item,
            ]);

        return $this->response()->success($apis);
    }

    public function argsSchema()
    {
        $schema = app(request('template'))->argsSchema();

        if (blank($schema)) {
            $schema = null;
        }

        return $this->response()->success($schema);
    }
}
