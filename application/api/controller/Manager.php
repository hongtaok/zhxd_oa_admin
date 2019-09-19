<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/9
 * Time: 14:43
 */

namespace app\api\controller;


use app\admin\model\Admin;
use app\admin\model\Apply;
use app\admin\model\Department;
use app\admin\model\Employee;
use app\common\controller\Api;
use fast\Tree;
use think\Validate;
use fast\Random;

class Manager extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = [];

    /**
     * 登录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login()
    {
        $admin_model = new Admin();

        $params['username'] = $this->request->request('username');
        $params['password'] = $this->request->request('password');

        $validate = new Validate([
            'username' => 'require',
            'password' => 'require',
        ]);
        if (!$validate->check($params)) {
            $this->error($validate->getError());
        }

        $user_info = $admin_model->where('username', $params['username'])->find();

        if (empty($user_info)) {
            $this->error('没有记录， 请联系管理员添加员工');
        } else {
            if ($user_info['password'] == md5(md5($params['password']) . $user_info['salt'])) {
                $this->success('', ['user_info' => $user_info]);
            } else {
                $this->error('账号密码错误');
            }
        }
    }

    /**
     * 授权并绑定信息
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function bind()
    {
        $admin_model = new Admin();

        $params['code'] = $this->request->request('code');
        $params['avatar'] = $this->request->request('avatar');
        $params['nickname'] = $this->request->request('nickname');
        $params['admin_id'] = $this->request->request('admin_id');

        $validate = new Validate([
            'code' => 'require',
            'avatar' => 'require',
            'nickname' => 'require',
            'admin_id' => 'require',
        ]);
        if (!$validate->check($params)) {
            $this->error($validate->getError());
        }

        $user_info = $admin_model->where('id', $params['admin_id'])->find();

        if (empty($user_info)) {
            $this->error('没有记录， 请联系管理员添加员工');
        } else {
            $auth_data = webapp_auth($params['code'], true);
            $data['openid'] = $auth_data['openid'];
            $data['session_key'] = $auth_data['session_key'];

            if (empty($user_info['is_bind']) && empty($user_info['openid']) && empty($user_info['session_key'])) {
                $user_info->openid = $data['openid'];
                $user_info->session_key = $data['session_key'];
                $user_info->nickname = $params['nickname'];
                $user_info->avatar = $params['avatar'];
                $user_info->is_bind = 1;
                $user_info->save();
                $this->success('授权绑定成功', $user_info);
            }

            $this->success('授权成功', $user_info);
        }
    }

    /**
     * 员工信息
     * @throws \think\db\exception\DataNotF\oundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function admin_info()
    {
        $admin_model = new Admin();
        $admin_id = $this->request->request('admin_id');

        if (empty($admin_id)) {
            $this->error('员工id不能为空');
        }

        $admin_info = $admin_model->where('id', $admin_id)->find();
        $admin_info['department_full_names'] = $this->getDepartmentParentNames($admin_info->department_id);
        $this->success('', $admin_info);
    }

    /**
     * 获取部门名称（包含父级）
     * @param $department_id
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDepartmentParentNames($department_id)
    {
        $tree = Tree::instance();
        $department_model = new Department();
        $departments = collection($department_model->select())->toArray();
        $tree->init($departments);

        $department_parents = $tree->getParentsNames($department_id, true);
        return $department_parents_name = implode('-', $department_parents);
    }

    /**
     * 获取密码加密后的字符串
     * @param string $password 密码
     * @param string $salt     密码盐
     * @return string
     */
    public function getEncryptPassword($password, $salt = '')
    {
        return md5(md5($password) . $salt);
    }

    /**
     * 该员工下的 贷款申请
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function applies()
    {
        $admin_id = $this->request->request('admin_id');
        $status = $this->request->request('status');
        $start_time = $this->request->request('start_time') . ' 00:00:00';
        $end_time = $this->request->request('end_time') . ' 24:00:00';

        $apply_model = new Apply();
        $applies = $apply_model
            ->field('id,product_id,user_id,admin_id,status,apply_time,city')
            ->with(['product' => function ($query) {
                $query->field('id,name,evidence_ids');
            }])
            ->with(['user' => function ($query) {
                $query->field('id,username,mobile,prevtime,logintime,jointime');
            }])
            ->where('admin_id', '=', $admin_id)
            ->where('status', '=', $status)
            ->where('apply_time', '>', $start_time)
            ->where('apply_time', '<', $end_time)
            ->select();

        $this->success('', ['applies' => $applies]);

    }

    public function apply_info()
    {
        $id = $this->request->request('id');

        if (empty($id)) {
            $this->error('参数错误');
        }

        $apply_model = new Apply();
        $apply_info = $apply_model
            ->field('id,product_id,user_id,admin_id,status,apply_time,city')
            ->with(['product' => function ($query) {
                $query->field('id, name');
            }])
            ->with(['user' => function ($query) {
                $query->field('id,username,mobile,prevtime,logintime,jointime');
            }])
            ->where('id', '=', $id)
            ->find();

        if (empty($apply_info)) {
            $this->error('数据不存在');
        }

        $this->success('', ['apply_info' => $apply_info]);
    }

























}