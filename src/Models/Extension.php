<?php

namespace Micaomao\NmsAdmin\Models;

class Extension extends BaseModel
{
    protected $fillable = ['name', 'is_enabled', 'options'];

    protected $casts = [
        'options' => 'json',
    ];

    protected $table = 'admin_extensions';
}
