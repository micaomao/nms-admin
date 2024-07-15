<?php

namespace Micaomao\NmsAdmin\Renderers;

/**
 * AutoFillHeight
 *
 * @author  daga
 * @version 6.4.1
 */
class AutoFillHeight extends BaseRenderer
{
    public function __construct()
    {


    }

    /**
     *
     */
    public function height($value = '')
    {
        return $this->set('height', $value);
    }

    /**
     *
     */
    public function maxHeight($value = '')
    {
        return $this->set('maxHeight', $value);
    }


}
