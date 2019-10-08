<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class SuncardApply extends Backend
{

    protected $model = null;
    protected $searchFields = ['username', 'phone'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\SuncardApply;

    }

    

}
