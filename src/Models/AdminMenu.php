<?php

namespace Micaomao\NmsAdmin\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminMenu extends BaseModel
{
    protected $guarded = [];

    const TYPE_ROUTE  = 1;
    const TYPE_LINK   = 2;
    const TYPE_IFRAME = 3;
    const TYPE_PAGE   = 4;

    public static function getType(): array
    {
        return [
            self::TYPE_ROUTE  => __('admin.admin_menu.route'),
            self::TYPE_LINK   => __('admin.admin_menu.link'),
            self::TYPE_IFRAME => __('admin.admin_menu.iframe'),
            self::TYPE_PAGE   => __('admin.admin_menu.page'),
        ];
    }

    /**
     * 父级菜单
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function title(): Attribute
    {
        return Attribute::get(function ($value) {
            $transKey  = ($this->extension ? $this->extension . '::' : '') . "menu.{$value}";
            $translate = __($transKey);

            return $translate == $transKey ? $value : $translate;
        });
    }
}
