<?php

namespace Micaomao\NmsAdmin\Models;

use Micaomao\NmsAdmin\Admin;
use Illuminate\Database\Eloquent\Model;
use Micaomao\NmsAdmin\Traits\DatetimeFormatterTrait;

class BaseModel extends Model
{
    use DatetimeFormatterTrait;

    public function __construct(array $attributes = [])
    {
        $this->setConnection(Admin::config('admin.database.connection'));

        parent::__construct($attributes);
    }
}
