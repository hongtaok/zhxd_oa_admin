<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 贷款申请通知管理
 *
 * @icon fa fa-circle-o
 */
class ApplyNotice extends Backend
{
    
    /**
     * ApplyNotice模型对象
     * @var \app\admin\model\ApplyNotice
     */
    protected $model = null;
    protected $searchFields = ['apply_id', 'admin.username', 'admin.username'];
    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\ApplyNotice;

    }

    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();


            $total = $this->model
                ->with(['admin', 'user'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['admin', 'user'], 'left')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

}
