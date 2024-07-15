<?php

namespace Micaomao\NmsAdmin\Support\Apis;

use Micaomao\NmsAdmin\Admin;

/**
 * 删除数据
 */
class DataDeleteApi extends AdminBaseApi
{
    public string $method = 'delete';

    public function getTitle()
    {
        return __('admin.api_templates.data_delete');
    }

    public function handle()
    {
        $result = $this->service()->delete(request($this->getArgs('primary_key', 'ids')));

        if ($result) {
            return Admin::response()
                ->successMessage(__('admin.successfully_message', ['attribute' => __('admin.delete')]));
        }

        return Admin::response()->fail(__('admin.failed_message', ['attribute' => __('admin.delete')]));
    }

    public function argsSchema()
    {
        return [
            amis()->SelectControl('model', __('admin.relationships.model'))
                ->required()
                ->menuTpl('${label} <span class="text-gray-300 pl-2">${table}</span>')
                ->source('/dev_tools/relation/model_options')
                ->searchable(),
            amis()->TextControl('primary_id', __('admin.code_generators.primary_key'))->value('ids'),
        ];
    }

    protected function service()
    {
        $service = $this->blankService();

        $service->setModelName($this->getArgs('model'));

        return $service;
    }
}
