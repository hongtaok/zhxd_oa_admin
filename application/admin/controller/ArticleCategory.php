<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use fast\Tree;

class ArticleCategory extends Backend
{

    protected $model = null;
    protected $category_list = [];
    protected $searchFields = ['name'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\ArticleCategory;

        $tree = Tree::instance();
        $data = collection($this->model->select())->toArray();
        $tree->init($data, 'pid');
        $this->category_list = $tree->getTreeList($tree->getTreeArray(0), 'name');

        $category_data = [0 => ['name' => __('None')]];
        foreach ($this->category_list as $k => $v) {
            $category_data[$v['id']] = $v;
        }
        $this->view->assign('parentList', $category_data);

    }


    

}
