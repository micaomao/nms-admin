<?php

namespace Micaomao\NmsAdmin\Controllers\DevTools;

use Micaomao\NmsAdmin\Admin;
use Illuminate\Http\Request;
use Micaomao\NmsAdmin\Renderers\Form;
use Micaomao\NmsAdmin\Extend\Extension;
use Micaomao\NmsAdmin\Renderers\DialogAction;
use Micaomao\NmsAdmin\Events\ExtensionChanged;
use Micaomao\NmsAdmin\Controllers\AdminController;

class ExtensionController extends AdminController
{
    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function index()
    {
        if ($this->actionOfGetData()) {
            $data = [];
            foreach (Admin::extension()->all() as $extension) {
                $data[] = $this->each($extension);
            }

            return $this->response()->success(['rows' => $data]);
        }

        $page = $this->basePage()->body($this->list());

        return $this->response()->success($page);
    }

    protected function each($extension)
    {
        $property = $extension->composerProperty;

        $name    = $extension->getName();
        $version = $extension->getVersion();

        return [
            'id'          => $name,
            'alias'       => $extension->getAlias(),
            'logo'        => $extension->getLogoBase64(),
            'name'        => $name,
            'version'     => $version,
            'description' => $property->description,
            'authors'     => $property->authors,
            'homepage'    => $property->homepage,
            'enabled'     => $extension->enabled(),
            'extension'   => $extension,
            'doc'         => $extension->getDocs(),
            'has_setting' => $extension->settingForm() instanceof Form,
            'used'        => $extension->used(),
        ];
    }

    public function list()
    {
        return amis()->CRUDTable()
            ->perPage(20)
            ->affixHeader(false)
            ->filterTogglable()
            ->filterDefaultVisible(false)
            ->api($this->getListGetDataPath())
            ->perPageAvailable([10, 20, 30, 50, 100, 200])
            ->footerToolbar(['switch-per-page', 'statistics', 'pagination'])
            ->loadDataOnce()
            ->source('${rows | filter:alias:match:keywords}')
            ->filter(
                $this->baseFilter()->body([
                    amis()->TextControl()
                        ->name('keywords')
                        ->label(__('admin.extensions.form.name'))
                        ->placeholder(__('admin.extensions.filter_placeholder'))
                        ->size('md'),
                ])
            )
            ->headerToolbar([
                $this->moreExtend(),
                $this->localInstall(),
                $this->createExtend(),
                amis('reload')->align('right'),
                amis('filter-toggler')->align('right'),
            ])
            ->columns([
                amis()->TableColumn('alias', __('admin.extensions.form.name'))
                    ->type('tpl')
                    ->tpl('
<div class="flex">
    <div> <img src="${logo}" class="w-10 mr-4"/> </div>
    <div>
        <div><a href="${homepage}" target="_blank">${alias | truncate:30}</a></div>
        <div class="text-gray-400">${name}</div>
    </div>
</div>
'),
                amis()->TableColumn('author', __('admin.extensions.card.author'))
                    ->type('tpl')
                    ->tpl('<div>${authors[0].name}</div> <span class="text-gray-400">${authors[0].email}</span>'),
                $this->rowActions([
                    amis()->DrawerAction()->label(__('admin.show'))->className('p-0')->level('link')->drawer(
                        amis()->Drawer()
                            ->size('lg')
                            ->title('README.md')
                            ->actions([])
                            ->closeOnOutside()
                            ->closeOnEsc()
                            ->body(amis()->Markdown()->name('${doc | raw}')->options([
                                'html'   => true,
                                'breaks' => true,
                            ]))
                    ),
                    amis()->DrawerAction()
                        ->label(__('admin.extensions.setting'))
                        ->level('link')
                        ->visibleOn('${has_setting && enabled}')
                        ->drawer(
                            amis()
                                ->Drawer()
                                ->title(__('admin.extensions.setting'))
                                ->resizable()
                                ->closeOnOutside()
                                ->body(
                                    amis()->Service()
                                        ->schemaApi([
                                            'url'    => admin_url('dev_tools/extensions/config_form'),
                                            'method' => 'post',
                                            'data'   => [
                                                'id' => '${id}',
                                            ],
                                        ])
                                )
                                ->actions([])
                        ),
                    amis()->AjaxAction()
                        ->label('${enabled ? "' . __('admin.extensions.disable') . '" : "' . __('admin.extensions.enable') . '"}')
                        ->level('link')
                        ->className(["text-success" => '${!enabled}', "text-danger" => '${enabled}'])
                        ->api([
                            'url'    => admin_url('dev_tools/extensions/enable'),
                            'method' => 'post',
                            'data'   => [
                                'id'      => '${id}',
                                'enabled' => '${enabled}',
                            ],
                        ])
                        ->confirmText('${enabled ? "' . __('admin.extensions.disable_confirm') . '" : "' . __('admin.extensions.enable_confirm') . '"}'),
                    amis()->AjaxAction()
                        ->label(__('admin.extensions.uninstall'))
                        ->level('link')
                        ->className('text-danger')
                        ->api([
                            'url'    => admin_url('dev_tools/extensions/uninstall'),
                            'method' => 'post',
                            'data'   => ['id' => '${id}'],
                        ])
                        ->visibleOn('${used}')
                        ->confirmText(__('admin.extensions.uninstall_confirm')),
                ]),
            ]);
    }

    /**
     * 创建扩展
     *
     * @return DialogAction
     */
    public function createExtend()
    {
        return amis()->DialogAction()
            ->label(__('admin.extensions.create_extension'))
            ->icon('fa fa-add')
            ->dialog(
                amis()->Dialog()->title(__('admin.extensions.create_extension'))->body(
                    amis()->Form()->mode('normal')->api($this->getStorePath())->body([
                        amis()->Alert()
                            ->level('info')
                            ->showIcon()
                            ->body(__('admin.extensions.create_tips', ['dir' => config('admin.extension.dir')])),
                        amis()->TextControl()
                            ->name('name')
                            ->label(__('admin.extensions.form.name'))
                            ->placeholder('eg: daga/nms-admin')
                            ->required(),
                        amis()->TextControl()
                            ->name('namespace')
                            ->label(__('admin.extensions.form.namespace'))
                            ->placeholder('eg: Micaomao\Notice')
                            ->required(),
                    ])
                )
            );
    }

    public function store(Request $request)
    {
        $extension = Extension::make();

        $extension->createDir($request->name, $request->namespace);

        if ($extension->hasError()) {
            return $this->response()->fail($extension->getError());
        }

        //创建扩展事件
        ExtensionChanged::dispatch($request->name, 'create');

        return $this->response()->successMessage(
            __('admin.successfully_message', ['attribute' => __('admin.extensions.create')])
        );
    }

    /**
     * 本地安装
     *
     * @return DialogAction
     */
    public function localInstall()
    {
        return amis()->DialogAction()
            ->label(__('admin.extensions.local_install'))
            ->icon('fa-solid fa-cloud-arrow-up')
            ->dialog(
                amis()->Dialog()->title(__('admin.extensions.local_install'))->showErrorMsg(false)->body(
                    amis()->Form()->mode('normal')->api('post:' . admin_url('dev_tools/extensions/install'))->body([
                        amis()->FileControl()->name('file')->label()->required()->drag()->accept('.zip'),
                    ])
                )
            );
    }

    /**
     * 更多扩展
     */
    public function moreExtend()
    {
        return amis()->UrlAction()
            ->url('https://nmsadmin.com/ext')
            ->label(__('admin.extensions.more_extensions'))
            ->icon('fa-regular fa-lightbulb')
            ->level('success')
            ->blank();
    }

    /**
     * 安装
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     */
    public function install(Request $request)
    {
        $file = $request->input('file');

        if (!$file) {
            return $this->response()->fail(__('admin.extensions.validation.file'));
        }

        try {
            $path = $this->getFilePath($file);

            $manager = Admin::extension();

            $extensionName = $manager->extract($path, true);

            if (!$extensionName) {
                return $this->response()->fail(__('admin.extensions.validation.invalid_package'));
            }

            //安装扩展事件
            ExtensionChanged::dispatch($extensionName, 'install');

            return $this->response()->successMessage(
                __('admin.successfully_message', ['attribute' => __('admin.extensions.install')])
            );
        } catch (\Throwable $e) {
            return $this->response()->fail($e->getMessage());
        } finally {
            if (!empty($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function getFilePath($file)
    {
        $disk = Admin::config('admin.upload.disk') ?: 'local';

        $root = Admin::config("filesystems.disks.{$disk}.root");

        if (!$root) {
            throw new \Exception(sprintf('Missing \'root\' for disk [%s].', $disk));
        }

        return rtrim($root, '/') . '/' . $file;
    }

    /**
     * 启用/禁用
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function enable(Request $request)
    {
        Admin::extension()->enable($request->id, !$request->enabled);

        //扩展启用禁用事件
        ExtensionChanged::dispatch($request->id, $request->enabled ? 'enable' : 'disable');

        return $this->response()->successMessage(__('admin.action_success'));
    }

    /**
     * 卸载
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function uninstall(Request $request)
    {
        Admin::extension($request->id)->uninstall();

        //扩展卸载事件
        ExtensionChanged::dispatch($request->id, 'uninstall');

        return $this->response()->successMessage(__('admin.action_success'));
    }

    /**
     * 保存扩展设置
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function saveConfig(Request $request)
    {
        $data = collect($request->all())->except(['extension'])->toArray();

        Admin::extension($request->input('extension'))->saveConfig($data);

        return $this->response()->successMessage(__('admin.save_success'));
    }

    /**
     * 获取扩展设置
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getConfig(Request $request)
    {
        $config = Admin::extension($request->input('extension'))->config();

        return $this->response()->success($config);
    }

    /**
     * 获取扩展设置表单
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function configForm(Request $request)
    {
        $form = Admin::extension($request->id)->settingForm();

        return $this->response()->success($form);
    }
}
