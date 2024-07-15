<?php

namespace Micaomao\NmsAdmin\Support\Cores;

use Micaomao\NmsAdmin\Admin;
use Micaomao\NmsAdmin\Services\AdminRelationshipService;

class Relationships
{
    public static function boot()
    {
        if (!Admin::hasTable('admin_relationships')) {
            return;
        }

        $relationships = AdminRelationshipService::make()->getAll();

        if (blank($relationships)) {
            return;
        }

        foreach ($relationships as $relationship) {
            try {
                $relationship->model::resolveRelationUsing($relationship->title, function ($model) use ($relationship) {
                    $method = $relationship->method;

                    return $model->$method(...array_column($relationship->buildArgs(), 'value'));
                });
            } catch (\Throwable $e) {
            }
        }
    }
}
