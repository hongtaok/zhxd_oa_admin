<?php

namespace app\admin\controller\user;

use app\admin\model\WithdrawRecord;
use app\common\controller\Backend;
use fast\Date;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{

    protected $relationSearch = true;

    protected $noNeedRight = ['index'];
    protected $searchFields = ['username', 'nickname'];
    /**
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('User');
    }

    /**
     * 查看
     */
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
                ->with('group')
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with(['group', 'withdrawRecords'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach ($list as $k => $v) {
                $v['team'] = $v->team();
                $v->hidden(['password', 'salt']);
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));
        return parent::edit($ids);
    }

    public function withdraw($ids)
    {
        $row = $this->model->get($ids);
        $row->team = $row->team();
        if ($this->request->isPost()) {
            $amount = $this->request->request('amount');

            // 提现金额不能为空
            if (empty($amount) || $amount <= 0) {
                $this->error('请输入提现金额');
            }

            // 提现金额不能大于总业绩
            if ($amount > $row->income_total) {
                $this->error('提现金额不能大于总业绩');
            }

            // 总业绩不能小于0
            if ($row->income_total <= 0) {
                $this->error('用户业绩小于0, 无法提现');
            }

            // 剩余可提现金额不能小于0
            if ($row->withdraw_balance <= 0) {
                $this->error('用户剩余可提现金额小于0，无法提现');
            }

            // 提现金额不能大于剩余可提现金额
            if ($amount > $row->withdraw_balance) {
                $this->error('提现金额不可大于用户的剩余可提现金额');
            }

            $record_data = [
                'amount' => $amount,
                'admin_id' => $this->auth->id,
                'user_id' => $row->id,
                'before_amount' => $row->withdraw_balance,
                'after_amount' => $row->withdraw_balance - $amount,
                'record_time' => datetime(time()),
            ];

            $withdraw_model = new WithdrawRecord();
            $res = $withdraw_model->save($record_data);
            if ($res) {
                $this->success('提现成功');
            } else {
                $this->error('提现失败');
            }
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

}
