<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\common\controller\Backend;
use fast\Auth;
use think\Db;
use think\Validate;

/**
 * 贷款申请管理
 *
 * @icon fa fa-circle-o
 */
class Apply extends Backend
{
    
    /**
     * Apply模型对象
     * @var \app\admin\model\Apply
     */
    protected $model = null;
//    protected $relationSearch = true;
    protected $searchFields = 'id,name';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Apply;
        $this->view->assign("statusList", $this->model->getStatusList());
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

            // 如果是客户经理登录， 只显示分配到他名下的申请
            $user_info = $this->auth->getUserInfo($this->auth->id);

            if ($user_info['role'] == 3) {
                $where = ['admin_id' => $user_info['id']];
            }

            $total = $this->model
                ->with('user')
                ->with('admin')
                ->with('product')
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with('user')
                ->with('admin')
                ->with('product')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list, 'text' => 'aaa');

            return json($result);
        }
        return $this->view->fetch();
    }


    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }

                    if (!empty($params['admin_id'])) {
                        $admin_info = Admin::find($params['admin_id']);

                        if (!empty($admin_info)) {
                            $params['allot_time'] = datetime(time());
                        }
                    }

                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 上传认证资料
     * @param null $ids
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function upload_evidence($ids = null)
    {
        $row = $this->model->with('product', 'user', 'product.evidences')->find($ids);
        $need_evidences = collection($row->product->evidences)->toArray();
        $need_evidence_nums = array_sum(array_column($need_evidences, 'num'));

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $image_nums = count(explode(',', $params['images']));

            if ($image_nums < $need_evidence_nums) {
                $this->error('上传的资料数量缺失, 请确认上传资料是否完整');
            }

            $params['upload_evidence_time'] = date('Y-m-d H:i:s', time());
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->success('上传成功', '');
            } else {
                $this->error('上传失败');
            }
        }

        $this->view->assign('row', $row);
        $this->view->assign('need_evidences', $need_evidences);
        $this->view->assign('need_evidence_nums', $need_evidence_nums);
        return $this->view->fetch();
    }

    /**
     * 分配客户
     * @param null $ids
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function allot($ids = null)
    {
        $row = $this->model->get($ids);

        $admin_model = new Admin();
        $admin_info = $admin_model->where('id', $this->auth->id)->find();
        $admins = $admin_model->where('department_id', $admin_info->department_id)->select();

        foreach ($admins as $index => $item) {
            unset($item['password'], $item['salt']);
            $admin_list[] = [
                'id' => isset($item['id']) ? $item['id'] : '',
                'username'      => isset($item['username']) ? $item['username'] : '',
            ];
        }

        if ($this->request->isPost()) {
            $params = $this->request->request('row/a');
            $validate = new Validate([
                'admin_id' => 'require|number',
            ], [
                'admin_id.require' => '请选择客户经理',
            ]);

            if (!$validate->check($params)) {
                $this->error($validate->getError());
            }

            $row->admin_id = $params['admin_id'];
            $row->allot_time = date('Y-m-d H:i:s', time());
            $row->save();
            $this->success('分配成功', '');
        }

        $this->view->assign('admin_list', $admin_list);
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 客户经理上传尽调文件 并给出初始额度
     * @param $ids
     * @return string
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function report_check_fund($ids)
    {
        $row = $this->model->get($ids);

        if ($this->request->isPost()) {
            $params = $this->request->request('row/a');

            $validate = new Validate([
                'first_check_fund' => 'require',
                'report' => 'require',
            ], [
                'first_check_fund.require' => '请填写初审额度',
                'report.require' => '请上传尽调报告',
            ]);
            if (!$validate->check($params)) {
                $this->error($validate->getError());
            }

            $row->first_check_fund = $params['first_check_fund'];
            $row->report = $params['report'];
            $row->report_fund_time = date('Y-m-d H:i:s', time());
            $row->save();
            $this->success('操作成功', '');
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    public function reject($ids = null)
    {
        $row = $this->model->get($ids);

        if ($this->request->isPost()) {
            $params = $this->request->request('row/a');

            $validate = new Validate([
                'reject_reason' => 'require',
            ], [
                'reject_reason.require' => '请填写驳回理由',
            ]);
            if (!$validate->check($params)) {
                $this->error($validate->getError());
            }

            Db::startTrans();
            try {
                // apply 修改 status 状态为 2（审核拒绝）
                $row->status = 2;
                $row->reject_admin_id = $this->auth->id;
                $row->reject_reason = $params['reject_reason'];
                $row->reject_time = date('Y-m-d H:i:s', time());
                $row->save();

                // apply_reject_log 表插入记录: id apply_id admin_id reject_reason reject_time
                $apply_reject_log = new \app\admin\model\ApplyRejectLog();
                $apply_reject_log->apply_id = $ids;
                $apply_reject_log->admin_id = $this->auth->id;
                $apply_reject_log->reject_reason = $params['reject_reason'];
                $apply_reject_log->reject_time = date('Y-m-d H:i:s', time());

                $result = $apply_reject_log->save();

            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success();
            } else {
                $this->error(__('No rows were updated'));
            }
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }


}
