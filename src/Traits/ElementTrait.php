<?php

namespace Micaomao\NmsAdmin\Traits;

use Micaomao\NmsAdmin\Admin;

trait ElementTrait
{
    /**
     * 基础页面
     *
     * @return \Micaomao\NmsAdmin\Renderers\Page
     */
    protected function basePage()
    {
        return amis()->Page()->className('m:overflow-auto');
    }

    /**
     * 返回列表按钮
     *
     * @return \Micaomao\NmsAdmin\Renderers\OtherAction
     */
    protected function backButton()
    {
        $path   = str_replace(Admin::config('admin.route.prefix'), '', request()->path());
        $script = sprintf('window.$owl.hasOwnProperty(\'closeTabByPath\') && window.$owl.closeTabByPath(\'%s\')', $path);

        return amis()->OtherAction()
            ->label(__('admin.back'))
            ->icon('fa-solid fa-chevron-left')
            ->level('primary')
            ->onClick('window.history.back();' . $script);
    }

    /**
     * 批量删除按钮
     */
    protected function bulkDeleteButton()
    {
        return amis()->DialogAction()
            ->label(__('admin.delete'))
            ->icon('fa-solid fa-trash-can')
            ->dialog(
                amis()->Dialog()
                    ->title(__('admin.delete'))
                    ->className('py-2')
                    ->actions([
                        amis()->Action()->actionType('cancel')->label(__('admin.cancel')),
                        amis()->Action()->actionType('submit')->label(__('admin.delete'))->level('danger'),
                    ])
                    ->body([
                        amis()->Form()->wrapWithPanel(false)->api($this->getBulkDeletePath())->body([
                            amis()->Tpl()->className('py-2')->tpl(__('admin.confirm_delete')),
                        ]),
                    ])
            );
    }

    /**
     * 新增按钮
     *
     * @param bool   $dialog
     * @param string $dialogSize
     *
     * @return \Micaomao\NmsAdmin\Renderers\DialogAction|\Micaomao\NmsAdmin\Renderers\LinkAction
     */
    protected function createButton(bool $dialog = false, string $dialogSize = '')
    {
        if ($dialog) {
            $form = $this->form(false)->canAccessSuperData(false)->api($this->getStorePath())->onEvent([]);

            $button = amis()->DialogAction()->dialog(
                amis()->Dialog()->title(__('admin.create'))->body($form)->size($dialogSize)
            );
        } else {
            $button = amis()->LinkAction()->link($this->getCreatePath());
        }

        return $button->label(__('admin.create'))->icon('fa fa-add')->level('primary');
    }

    /**
     * 行编辑按钮
     *
     * @param bool   $dialog
     * @param string $dialogSize
     *
     * @return \Micaomao\NmsAdmin\Renderers\DialogAction|\Micaomao\NmsAdmin\Renderers\LinkAction
     */
    protected function rowEditButton(bool $dialog = false, string $dialogSize = '')
    {
        if ($dialog) {
            $form = $this->form(true)
                ->api($this->getUpdatePath())
                ->initApi($this->getEditGetDataPath())
                ->redirect('')
                ->onEvent([]);

            $button = amis()->DialogAction()->dialog(
                amis()->Dialog()->title(__('admin.edit'))->body($form)->size($dialogSize)
            );
        } else {
            $button = amis()->LinkAction()->link($this->getEditPath());
        }

        return $button->label(__('admin.edit'))->icon('fa-regular fa-pen-to-square')->level('link');
    }

    /**
     * 行详情按钮
     *
     * @param bool   $dialog
     * @param string $dialogSize
     *
     * @return \Micaomao\NmsAdmin\Renderers\DialogAction|\Micaomao\NmsAdmin\Renderers\LinkAction
     */
    protected function rowShowButton(bool $dialog = false, string $dialogSize = '')
    {
        if ($dialog) {
            $button = amis()->DialogAction()->dialog(
                amis()->Dialog()->title(__('admin.show'))->body($this->detail('$id'))->size($dialogSize)
            );
        } else {
            $button = amis()->LinkAction()->link($this->getShowPath());
        }

        return $button->label(__('admin.show'))->icon('fa-regular fa-eye')->level('link');
    }

    /**
     * 行删除按钮
     *
     */
    protected function rowDeleteButton()
    {
        return amis()->DialogAction()
            ->label(__('admin.delete'))
            ->icon('fa-regular fa-trash-can')
            ->level('link')
            ->dialog(
                amis()->Dialog()
                    ->title()
                    ->className('py-2')
                    ->actions([
                        amis()->Action()->actionType('cancel')->label(__('admin.cancel')),
                        amis()->Action()->actionType('submit')->label(__('admin.delete'))->level('danger'),
                    ])
                    ->body([
                        amis()->Form()->wrapWithPanel(false)->api($this->getDeletePath())->body([
                            amis()->Tpl()->className('py-2')->tpl(__('admin.confirm_delete')),
                        ]),
                    ])
            );
    }

    /**
     * 操作列
     *
     * @param bool   $dialog
     * @param string $dialogSize
     *
     * @return \Micaomao\NmsAdmin\Renderers\Operation
     */
    protected function rowActions(bool|array $dialog = false, string $dialogSize = '')
    {
        if (is_array($dialog)) {
            return amis()->Operation()->label(__('admin.actions'))->buttons($dialog);
        }

        return amis()->Operation()->label(__('admin.actions'))->buttons([
            $this->rowShowButton($dialog, $dialogSize),
            $this->rowEditButton($dialog, $dialogSize),
            $this->rowDeleteButton(),
        ]);
    }

    /**
     * 基础筛选器
     *
     * @return \Micaomao\NmsAdmin\Renderers\Form
     */
    protected function baseFilter()
    {
        return amis()->Form()
            ->panelClassName('base-filter')
            ->title('')
            ->actions([
                amis()->Button()->label(__('admin.reset'))->actionType('clear-and-submit'),
                amis('submit')->label(__('admin.search'))->level('primary'),
            ]);
    }

    /**
     * 基础筛选器 - 条件构造器
     *
     * @return \Micaomao\NmsAdmin\Renderers\ConditionBuilderControl
     */
    protected function baseFilterConditionBuilder()
    {
        return amis()->ConditionBuilderControl('filter_condition_builder');
    }

    /**
     * @return \Micaomao\NmsAdmin\Renderers\CRUDTable
     */
    protected function baseCRUD()
    {
        $crud = amis()->CRUDTable()
            ->perPage(20)
            ->affixHeader(false)
            ->filterTogglable()
            ->filterDefaultVisible(false)
            ->api($this->getListGetDataPath())
            ->quickSaveApi($this->getQuickEditPath())
            ->quickSaveItemApi($this->getQuickEditItemPath())
            ->bulkActions([$this->bulkDeleteButton()])
            ->perPageAvailable([10, 20, 30, 50, 100, 200])
            ->footerToolbar(['switch-per-page', 'statistics', 'pagination'])
            ->headerToolbar([
                $this->createButton(),
                ...$this->baseHeaderToolBar(),
            ]);

        if (isset($this->service)) {
            $crud->set('primaryField', $this->service->primaryKey());
        }

        return $crud;
    }

    protected function baseHeaderToolBar()
    {
        return [
            'bulkActions',
            amis('reload')->align('right'),
            amis('filter-toggler')->align('right'),
        ];
    }

    /**
     * 基础表单
     *
     * @param bool $back
     *
     * @return \Micaomao\NmsAdmin\Renderers\Form
     */
    protected function baseForm(bool $back = true)
    {
        $path = str_replace(Admin::config('admin.route.prefix'), '', request()->path());

        $form = amis()->Form()->panelClassName('px-48 m:px-0')->title(' ')->mode('horizontal')->promptPageLeave();

        if ($back) {
            $form->onEvent([
                'submitSucc' => [
                    'actions' => [
                        ['actionType' => 'custom', 'script' => 'window.history.back()'],
                        [
                            'actionType' => 'custom',
                            'script'     => sprintf('window.$owl.hasOwnProperty(\'closeTabByPath\') && window.$owl.closeTabByPath(\'%s\')', $path),
                        ],
                    ],
                ],
            ]);
        }

        return $form;
    }

    /**
     * @return \Micaomao\NmsAdmin\Renderers\Form
     */
    protected function baseDetail()
    {
        return amis()->Form()
            ->panelClassName('px-48 m:px-0')
            ->title(' ')
            ->mode('horizontal')
            ->actions([])
            ->initApi($this->getShowGetDataPath());
    }

    /**
     * 基础列表
     *
     * @param $crud
     *
     * @return \Micaomao\NmsAdmin\Renderers\Page
     */
    protected function baseList($crud)
    {
        return amis()->Page()->className('m:overflow-auto')->body($crud);
    }

    /**
     * 导出按钮
     *
     * @param bool $disableSelectedItem
     *
     * @return \Micaomao\NmsAdmin\Renderers\Service
     */
    protected function exportAction($disableSelectedItem = false)
    {
        // 获取主键名称
        $primaryKey = $this->service->primaryKey();
        // 下载路径
        $downloadPath = admin_url('_download_export', true);
        // 导出接口地址
        $exportPath = $this->getExportPath();
        // 无数据提示
        $pageNoData = __('admin.export.page_no_data');
        // 选中行无数据提示
        $selectedNoData = __('admin.export.selected_rows_no_data');
        // 按钮点击事件
        $event = fn($script) => ['click' => ['actions' => [['actionType' => 'custom', 'script' => $script]]]];
        // 导出处理动作
        $doAction = "doAction([{actionType:'setValue',componentId:'export-action',args:{value:{showExportLoading:true}}},{actionType:'ajax',args:{api:{url:url.toString(),method:'get'}}},{actionType:'setValue',componentId:'export-action',args:{value:{showExportLoading:false}}},{actionType:'custom',expression:'\${event.data.responseResult.responseStatus===0}',script:'window.open(\'{$downloadPath}?path=\'+event.data.responseResult.responseData.path)'}])";
        // 按钮
        $buttons = [
            // 导出全部
            amis()->VanillaAction()->label(__('admin.export.all'))->onEvent(
                $event("let data=event.data;let params=Object.keys(data).filter(key=>key!=='page' && key!=='__super').reduce((obj,key)=>{obj[key]=data[key];return obj;},{});let url=new URL('{$exportPath}',window.location.origin);Object.keys(params).forEach(key=>url.searchParams.append(key,params[key]));{$doAction}")
            ),
            // 导出本页
            amis()->VanillaAction()->label(__('admin.export.page'))->onEvent(
                $event("let ids=event.data.items.map(item=>item.{$primaryKey});if(ids.length===0){return doAction({actionType:'toast',args:{msgType:'warning',msg:'{$pageNoData}'}})};let url=new URL('{$exportPath}',window.location.origin);url.searchParams.append('_ids',ids.join(','));{$doAction}")
            ),
        ];
        // 导出选中项
        if (!$disableSelectedItem) {
            $buttons[] = amis()->VanillaAction()->label(__('admin.export.selected_rows'))->onEvent(
                $event("let ids=event.data.selectedItems.map(item=>item.{$primaryKey});if(ids.length===0){return doAction({actionType:'toast',args:{msgType:'warning',msg:'{$selectedNoData}'}})};let url=new URL('{$exportPath}',window.location.origin);url.searchParams.append('_ids',ids.join(','));{$doAction}")
            );
        }

        return amis()->Service()
            ->id('export-action')
            ->set('align', 'right')
            ->set('data', ['showExportLoading' => false])
            ->body(
                amis()->Spinner()->set('showOn', '${showExportLoading}')->overlay()->body(
                    amis()->DropdownButton()
                        ->label(__('admin.export.title'))
                        ->set('icon', 'fa-solid fa-download')
                        ->buttons($buttons)
                        ->closeOnClick()
                        ->align('right')
                )
            );
    }
}
