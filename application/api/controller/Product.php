<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/4
 * Time: 14:54
 */

namespace app\api\controller;


use app\admin\model\Pcate;
use app\common\controller\Api;


class Product extends Api
{
    protected $noNeedLogin = ['categorys', 'products_by_category'];

    public function categorys()
    {
        $pcate = new Pcate();

        $list = $pcate->where('pid', 0)->select();

        $this->success('', ['list' => $list]);
    }

    public function products_by_category()
    {
        $pcate_id = $this->request->param('pcate_id');

        $product_model = new \app\admin\model\Product();
        $pcate_model = new Pcate();

        $allot = $pcate_model->where('id', '=', $pcate_id)->column('allot');
        $list = $product_model->where('pcate_id', '=', $pcate_id)->select();

        $this->success('', ['allot' => $allot, 'list' => $list]);
    }

}