<?php

namespace Micaomao\NmsAdmin\Controllers\DevTools;

use Illuminate\Support\Facades\Schema;
use Micaomao\NmsAdmin\Support\Cores\Database;
use Micaomao\NmsAdmin\Models\AdminRelationship;
use Micaomao\NmsAdmin\Controllers\AdminController;
use Micaomao\NmsAdmin\Services\AdminRelationshipService;

/**
 * @property AdminRelationshipService $service
 */
class RelationshipController extends AdminController
{
    protected string $serviceName = AdminRelationshipService::class;

    public function list()
    {
        $crud = $this->baseCRUD()
            ->filterTogglable(false)
            ->headerToolbar([
                $this->createButton(true, 'lg'),
                ...$this->baseHeaderToolBar(),
                $this->modelGenerator(),
            ])
            ->columns([
                amis()->TableColumn('id', 'ID')->sortable(),
                amis()->TableColumn('model', __('admin.relationships.model'))->searchable(),
                amis()->TableColumn('title', __('admin.relationships.title'))->searchable(),
                amis()->TableColumn('remark', __('admin.relationships.remark'))->searchable(),
                $this->rowActions([
                    $this->previewButton(),
                    $this->rowEditButton(true, 'lg'),
                    $this->rowDeleteButton(),
                ]),
            ]);

        return $this->baseList($crud);
    }

    public function modelGenerator()
    {
        return amis()->DrawerAction()->label(__('admin.relationships.generate_model'))->level('success')->drawer(
            amis()->Drawer()
                ->title(__('admin.relationships.generate_model'))
                ->size('lg')
                ->resizable()
                ->closeOnOutside()
                ->closeOnEsc()
                ->actions([
                    amis()->VanillaAction()
                        ->label(__('admin.relationships.generate'))
                        ->actionType('submit')
                        ->level('primary'),
                ])
                ->body([
                    amis()->Form()
                        ->api('/dev_tools/relation/generate_model')
                        ->initApi('/dev_tools/relation/all_models')
                        ->mode('normal')
                        ->body([
                            amis()->TreeControl()
                                ->name('check_tables')
                                ->label()
                                ->multiple()
                                ->heightAuto()
                                ->required()
                                ->source('${all_models}')
                                ->searchable()
                                ->joinValues(false)
                                ->extractValue()
                                ->size('full')
                                ->className('h-full b-none')
                                ->inputClassName('h-full tree-full')
                                ->set('menuTpl', '${label} <span class="text-gray-300 pl-2">${model}</span>'),
                        ]),
                ])
        );
    }

    public function previewButton()
    {
        return amis()->DrawerAction()->label(__('admin.preview'))->level('link')->icon('fa fa-eye')->drawer(
            amis()->Drawer()
                ->position('top')
                ->resizable()
                ->title(__('admin.preview'))
                ->actions([])
                ->showCloseButton(false)
                ->closeOnEsc()
                ->closeOnOutside()
                ->body(
                    amis()->Code()->value('${preview_code | raw}')->language('php')
                )
        );
    }

    public function form()
    {
        $modelSelect = function ($name, $label) {
            return amis()
                ->SelectControl($name, $label)
                ->required()
                ->menuTpl('${label} <span class="text-gray-300 pl-2">${table}</span>')
                ->source('/dev_tools/relation/model_options')
                ->searchable();
        };

        $columnSelect = function ($name, $label, $modelField = "_blank_model", $tableField = '_blank_table') {
            return amis()
                ->TextControl($name, $label)
                ->source('/dev_tools/relation/column_options?model=${' . $modelField . '}&table=${' . $tableField . '}');
        };

        $args = function ($type, $items) {
            return amis()
                ->ComboControl('args', __('admin.relationships.args'))
                ->multiLine()
                ->strictMode(false)
                ->items($items)
                ->visibleOn('${type == "' . $type . '"}');
        };

        return $this->baseForm()->data([
            'tables' => Database::getTables(),
        ])->body([
            amis()->GroupControl()->body([
                amis()->GroupControl()->direction('vertical')->body([
                    $modelSelect('model', __('admin.relationships.model')),
                    amis()->TextControl('title', __('admin.relationships.title'))->required()->placeholder('comments'),
                    amis()->TextControl('remark', __('admin.relationships.remark')),
                    amis()->SelectControl('type', __('admin.relationships.type'))
                        ->required()
                        ->value(AdminRelationship::TYPE_BELONGS_TO)
                        ->menuTpl('${label} <span class="text-gray-300 pl-2">${method}</span>')
                        ->options(AdminRelationship::typeOptions()),
                ]),
                // 一对一
                $args(AdminRelationship::TYPE_HAS_ONE, [
                    // $related, $foreignKey = null, $localKey = null
                    $modelSelect('related', __('admin.relationships.related_model')),
                    $columnSelect('foreignKey', 'foreignKey', 'related'),
                    $columnSelect('localKey', 'localKey', 'model'),
                ]),
                // 一对多
                $args(AdminRelationship::TYPE_HAS_MANY, [
                    // $related, $foreignKey = null, $localKey = null
                    $modelSelect('related', __('admin.relationships.related_model')),
                    $columnSelect('foreignKey', 'foreignKey', 'related'),
                    $columnSelect('localKey', 'localKey', 'model'),
                ]),
                // 一对多(反向)/属于
                $args(AdminRelationship::TYPE_BELONGS_TO, [
                    // $related, $foreignKey = null, $ownerKey = null, $relation = null
                    $modelSelect('related', __('admin.relationships.related_model')),
                    $columnSelect('foreignKey', 'foreignKey', 'model'),
                    $columnSelect('ownerKey', 'ownerKey', 'related'),
                    amis()->TextControl('relation', 'relation'),
                ]),
                // 多对多
                $args(AdminRelationship::TYPE_BELONGS_TO_MANY, [
                    // $related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null, $parentKey = null, $relatedKey = null, $relation = null
                    $modelSelect('related', __('admin.relationships.related_model')),
                    amis()->SelectControl('table', 'table')->source('${tables}')->searchable(),
                    $columnSelect('foreignPivotKey', 'foreignPivotKey', '_blank_model', 'table'),
                    $columnSelect('relatedPivotKey', 'relatedPivotKey', '_blank_model', 'table'),
                    $columnSelect('parentKey', 'parentKey', 'model'),
                    $columnSelect('relatedKey', 'relatedKey', 'related'),
                    amis()->TextControl('relation', 'relation'),
                ]),
                // 远程一对一
                $args(AdminRelationship::TYPE_HAS_ONE_THROUGH, [
                    // $related, $through, $firstKey = null, $secondKey = null, $localKey = null, $secondLocalKey = null
                    $modelSelect('related', __('admin.relationships.related_model')),
                    $modelSelect('through', __('admin.relationships.through_model')),
                    $columnSelect('firstKey', 'firstKey', 'through'),
                    $columnSelect('secondKey', 'secondKey', 'related'),
                    $columnSelect('localKey', 'localKey', 'model'),
                    $columnSelect('secondLocalKey', 'secondLocalKey', 'through'),
                ]),
                // 远程一对多
                $args(AdminRelationship::TYPE_HAS_MANY_THROUGH, [
                    // $related, $through, $firstKey = null, $secondKey = null, $localKey = null, $secondLocalKey = null
                    $modelSelect('related', __('admin.relationships.related_model')),
                    $modelSelect('through', __('admin.relationships.through_model')),
                    $columnSelect('firstKey', 'firstKey', 'through'),
                    $columnSelect('secondKey', 'secondKey', 'related'),
                    $columnSelect('localKey', 'localKey', 'model'),
                    $columnSelect('secondLocalKey', 'secondLocalKey', 'through'),
                ]),
                // 一对一(多态)
                $args(AdminRelationship::TYPE_MORPH_ONE, [
                    // $related, $name, $type = null, $id = null, $localKey = null
                    $modelSelect('related', __('admin.relationships.related_model')),
                    amis()->TextControl('name', 'name')->required(),
                    amis()->TextControl('type', 'type'),
                    amis()->TextControl('id', 'id'),
                    $columnSelect('localKey', 'localKey', 'model'),
                ]),
                // 一对多(多态)
                $args(AdminRelationship::TYPE_MORPH_MANY, [
                    // $related, $name, $type = null, $id = null, $localKey = null
                    $modelSelect('related', __('admin.relationships.related_model')),
                    amis()->TextControl('name', 'name')->required(),
                    amis()->TextControl('type', 'type'),
                    amis()->TextControl('id', 'id'),
                    $columnSelect('localKey', 'localKey', 'model'),
                ]),
                // 多对多(多态)
                $args(AdminRelationship::TYPE_MORPH_TO_MANY, [
                    // $related, $name, $table = null, $foreignPivotKey = null, $relatedPivotKey = null, $parentKey = null, $relatedKey = null, $inverse = false
                    $modelSelect('related', __('admin.relationships.related_model')),
                    amis()->TextControl('name', 'name')->required(),
                    amis()->SelectControl('table', 'table')->source('${tables}')->searchable(),
                    $columnSelect('foreignPivotKey', 'foreignPivotKey', '_blank_model', 'table'),
                    $columnSelect('relatedPivotKey', 'relatedPivotKey', '_blank_model', 'table'),
                    $columnSelect('parentKey', 'parentKey', 'model'),
                    $columnSelect('relatedKey', 'relatedKey', 'related'),
                    amis()->TextControl('inverse', 'inverse'),
                ]),
            ]),
        ]);
    }


    public function detail($id)
    {
        return $this->baseDetail()->body([]);
    }

    /**
     * 获取所有 model
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     */
    public function modelOptions()
    {
        $models = $this->service->allModels()['models'];

        return $this->response()->success($models);
    }

    /**
     * 字段选项
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     */
    public function columnOptions()
    {
        $model = request('model');
        $table = request('table');

        if (blank($model) && blank($table)) {
            return $this->response()->success([]);
        }

        $table = $table ?: app($model)->getTable();

        $columns = Schema::getColumnListing($table);

        return $this->response()->success($columns);
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     */
    public function allModels()
    {
        $all    = $this->service->allModels();
        $tables = $all['tables'];
        $models = collect($all['models'])->keyBy('table');

        $all_models = collect($tables)->map(function ($item) use ($models) {
            $model = data_get($models, $item . '.value');

            return [
                'value'    => $item,
                'label'    => $item,
                'model'    => $model,
                'disabled' => (bool)$model,
            ];
        })->sortBy('disabled')->values();

        return $this->response()->success(compact('all_models'));
    }

    /**
     * 生成模型
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     */
    public function generateModel()
    {
        $tables     = request('check_tables');
        $existsList = collect($this->service->allModels()['models'])->pluck('table')->toArray();
        $exists     = array_intersect($tables, $existsList);

        admin_abort_if(filled($exists), __('admin.relationships.model_exists') . implode(',', $exists));

        try {
            foreach ($tables as $table) {
                $this->service->generateModel($table);
            }
        } catch (\Throwable $e) {
            return $this->response()->fail($e->getMessage());
        }

        return $this->response()->successMessage(__('admin.action_success'));
    }
}
