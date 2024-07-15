<?php

namespace Micaomao\NmsAdmin\Extend;

use Micaomao\NmsAdmin\Renderers\ComboControl;

class Sku
{
    public static function make()
    {
        return new self();
    }

    /**
     * SKU 表单
     *
     * @param string $name       字段名
     * @param string $label      字段标签
     * @param array  $skuColumns sku 的字段信息, 数组格式的 amis 表单组件
     *
     * @return ComboControl
     */
    public function form(string $name = 'sku', string $label = 'SKU', array $skuColumns = [], $static = false)
    {
        $key       = 'sku_' . $name;
        $serviceId = $key . '_service';
        $comboId   = $name . '_combo';

        return amis()->ComboControl($name, $label)
            ->id($comboId)
            ->multiLine()
            ->strictMode(false)
            ->noBorder()
            ->required()
            ->items([
                amis()->ComboControl('groups')
                    ->multiple()
                    ->multiLine()
                    ->addButtonText('添加规格组')
                    ->validateOnChange()
                    ->hidden($static)
                    ->items([
                        amis()->TextControl('group_name', '规格名称')->required()->set('unique', true),
                        amis()->ComboControl('specs', '规格值')
                            ->validateOnChange()
                            ->multiple()
                            ->draggable()
                            ->addButtonText('添加规格')
                            ->items([
                                amis()->TextControl('spec')->set('unique', true)->required(),
                            ]),
                    ]),
                amis()->Divider()->visibleOn('${groups}')->hidden($static),
                amis()->VanillaAction()
                    ->level('success')
                    ->label('生成 SKU')
                    ->className('mb-3')
                    ->onEvent(['click' => ['actions' => [['actionType' => 'rebuild', 'componentId' => $serviceId]]]])
                    ->hidden($static)
                    ->visibleOn('${groups}'),
                // 通过 service 在后端生成sku结构
//                amis()->Service()->id($serviceId)->showErrorMsg(false)->schemaApi([
//                    'url'    => '/owl-sku/generate',
//                    'method' => 'post',
//                    'data'   => [
//                        'goods_id'    => '${id}',
//                        'groups'      => '${groups}',
//                        'sku_name'    => $name,
//                        'sku_columns' => $skuColumns,
//                        'static'      => $static,
//                    ],
//                ]),
            ]);
    }


    /**
     * 生成 sku
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     */
    public function generate(Request $request)
    {
        $specGroup = $request->input('groups');

        if (is_null($specGroup)) {
            return $this->response()->success([]);
        }

        admin_abort_if(blank(Arr::flatten($specGroup)), '请填写规格组');

        $groupName = Arr::pluck($specGroup, 'group_name');

        $spec = collect($specGroup)->pluck('specs')->map(function ($item) {
            admin_abort_if(blank($item), '请填写规格值');

            return array_column($item, 'spec');
        })->toArray();

        // 规格交叉组合
        $specCrossJoin = Arr::crossJoin(...$spec);

        $groupNameMd5 = array_map(fn($item) => md5($item), $groupName);
        $value        = array_map(fn($item) => array_combine($groupNameMd5, $item), $specCrossJoin);
        $columns      = array_map(fn($item) => amis()->TableColumn(md5($item), $item), $groupName);

        if ($request->input('sku_columns')) {
            $columns = array_merge($columns, $request->input('sku_columns'));
        } else {
            $columns[] = amis()->NumberControl('price', '售价')
                ->value(0)
                ->min(0)
                ->max(999999999)
                ->precision(0)
                ->step(0.01)
                ->required()
                ->width(240);
            $columns[] = amis()->NumberControl('cost_price', '成本')
                ->value(0)
                ->min(0)
                ->max(999999999)
                ->precision(0)
                ->step(0.01)
                ->required()
                ->width(240);
            $columns[] = amis()->NumberControl('cost_price', '利润')
                ->value(0)
                ->min(0)
                ->max(999999999)
                ->precision(0)
                ->step(0.01)
                ->required()
                ->width(240);
            $columns[] = amis()->NumberControl('stock', '库存')
                ->value(0)
                ->min(0)
                ->max(999999999)
                ->step(1)
                ->required()
                ->width(240);
        }

        if ($request->static) {
            $columns = array_map(function ($item) {
                if ($item instanceof BaseRenderer) {
                    $item->set('static', true);
                } else {
                    $item['static'] = true;
                }
                return $item;
            }, $columns);
        }

        // 回显数据
        $goodsId = $request->input('goods_id');
        if (filled($goodsId)) {
            Sku::make()->mergeExistsData($goodsId, $value);
        }

        // 更改 name 否则 table 数据不会更新
        $schema = amis()->TableControl('skus_' . now()->getTimestampMs())
            ->id($request->sku_name . '_skus')
            ->needConfirm()
            ->className('pt-3')
            ->columns($columns)
            ->value($value);

        return $this->response()->success($schema);
    }




}
