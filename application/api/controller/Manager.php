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
use app\admin\model\Config;
use app\admin\model\Department;
use app\admin\model\Employee;
use app\common\controller\Api;
use Encore\Admin\Form\Field\Time;
use fast\Tree;
use think\Db;
use think\Log;
use think\Validate;
use fast\Random;
use app\admin\model\User;
use QRcode;


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

        Log::write(['user_info' => $user_info]);

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


        Log::write(['params' => $params]);

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
            $data['unionid'] = $auth_data['unionid'];

            if (empty($user_info['is_bind']) && empty($user_info['openid']) && empty($user_info['session_key'])) {
                $user_info->openid = $data['openid'];
                $user_info->session_key = $data['session_key'];
                $user_info->nickname = $params['nickname'];
                $user_info->avatar = $params['avatar'];

                if (!empty($data['unionid'])) {
                    $user_info->unionid = $data['unionid'];
                }

                $user_info->is_bind = 1;
                $user_info->save();

                Log::write(['user_info' => $user_info]);

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

        if (empty($admin_info['promote_code'])) {
            $data = $this->request->domain() . '?admin_id=' . $admin_info['id'];
            $outfile = ROOT_PATH . 'public' . '/qrcode/admin_' . $admin_info['id'] . '_' .  time() . '.jpg';

            $level = 'L';
            $size = 4;
            $QRcode = new QRcode();
            ob_start();
            $QRcode->png($data, $outfile, $level, $size, 2);

            $path_info = pathinfo($outfile);
            $qrcode_url = $this->request->domain() . '/qrcode/' . $path_info['basename'];

            $admin_info->promote_code = $qrcode_url;
            $admin_info->save();
        }

        $admin_info['department_full_names'] = $this->getDepartmentParentNames($admin_info->department_id);

        if (in_array($admin_info['role'], [1,2,3])) {
            $admin_info['is_manager'] = 0;
        } elseif (in_array($admin_info['role'], [4,5,6,7,8,9,10,11,12])) {
            $admin_info['is_manager'] = 1;
        }

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

        $apply_model = new Apply();
        $query = $apply_model
            ->field('id,product_id,user_id,admin_id,status,apply_time,city,first_check_fund,final_check_fund')
            ->with(['product' => function ($query) {
                $query->field('id,name,evidence_ids');
            }])
            ->with(['user' => function ($query) {
                $query->field('id,username,mobile,prevtime,logintime,jointime');
            }])
            ->where('admin_id', '=', $admin_id)
            ->where('status', '=', $status);

        $start_time = $this->request->request('start_time');
        $end_time = $this->request->request('end_time');

        if (!empty($start_time) && !empty($end_time)) {
            $start_time = $start_time . ' 00:00:00';
            $end_time = $end_time . ' 24:00:00';
            $query->where('apply_time', '>', $start_time);
            $query->where('apply_time', '<', $end_time);
        }

        $applies = $query->select();
        $this->success('', ['applies' => $applies]);
    }

    /**
     * 我的团队
     * @return \think\response\Json
     */
    public function team()
    {
        $admin_id = $this->request->request('admin_id');
        $user_model = new User();
        $team = $user_model->team($admin_id);
        $this->success('', $team);
    }

    /**
     * 我的业绩
     * @return \think\response\Json
     */
    public function team_applies()
    {
        $admin_id = $this->request->request('admin_id');
        $user_model = new User();
        $team = $user_model->team($admin_id);

        $config_model = new Config();
        $scale_one = $config_model->where('name', '=', 'scale_one')->find();
        $scale_two = $config_model->where('name', '=', 'scale_two')->find();

        $users = array_merge($team['first'], $team['second']);

        $applies['list'] = [];
        if (!empty($users)) {
            foreach ($users as $key => &$val) {
                $user_applies = Db::table('oa_apply')->field('id, product_id, user_id, admin_id, first_check_fund, final_check_fund, final_check_time, status')->where('user_id', $val['id'])->where('status', 3)->select();
                if (!empty($user_applies)) {
                    foreach ($user_applies as &$user_apply) {
                        if ($val['tier'] == 1) {
                            $user_apply['apply_income'] = $user_apply['final_check_fund'] * $scale_one->value;
                        }
                        if ($val['tier'] == 2) {
                            $user_apply['apply_income'] = $user_apply['final_check_fund'] * $scale_two->value;
                        }
                        $user_apply['product_name'] = Db::table('oa_product')->where('id', $user_apply['product_id'])->column('name')[0];
                        $user_apply['admin_username'] = Db::table('oa_admin')->where('id', $user_apply['admin_id'])->column('username')[0];
                        $user_apply['user_info'] = $val;
                        $applies['list'][] = $user_apply;
                    }
                }
            }
        }

        $applies['total_apply_income'] = array_sum(array_column($applies['list'], 'apply_income'));
        $applies['total_user_team'] = count($users);

        $this->success('', $applies);
    }

    /**
     * 贷款申请详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function apply_info()
    {
        $id = $this->request->request('id');
        if (empty($id)) {
            $this->error('参数错误');
        }

        $apply_model = new Apply();
        $apply_info = $apply_model
            ->field('id,product_id,user_id,admin_id,status,apply_time,city,first_check_fund,first_check_time,middle_check_time,final_check_time,reject_time')
            ->with(['product' => function ($query) {
                $query->field('id, name');
            }])
            ->with(['user' => function ($query) {
                $query->field('id,username,mobile,id_number,prevtime,logintime,jointime');
            }])
            ->where('id', '=', $id)
            ->find();

        if (empty($apply_info)) {
            $this->error('数据不存在');
        }

        $this->success('', ['apply_info' => $apply_info]);
    }

    /**
     * 员工端 - 我的客户
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function admin_users()
    {
        $admin_id = $this->request->request('admin_id');

        if (empty($admin_id)) {
            $this->error('参数错误');
        }

        $admin_model = new Admin();
        $admin_info = $admin_model->where('id', '=', $admin_id)->find();
        if (empty($admin_info)) {
            $this->error('员工信息错误');
        }

        $team_total = 0;
        $users = $admin_model->users;
        if (!empty($users)) {
            foreach ($users as &$user) {
                $user['team'] = $user->team();
                $team_total += $user['team']['total'];
            }
        }

        $user_team_total = count($users) + $team_total;
        $this->success('', ['list' => $users, 'user_team_total' => $user_team_total]);
    }

    /**
     * 我的员工
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function admins()
    {
        $admin_id = $this->request->request('admin_id');
        if (empty($admin_id)) {
            $this->error('参数错误');
        }

        $admin_model = new Admin();
        $admin_info = $admin_model->where('id', '=', $admin_id)->find();

        $admin_roles = $admin_model->getRoleList();

        if (!in_array($admin_info->id, array_keys($admin_roles))) {
            $this->error('管理员角色信息错误');
        }

        // 普通员工（客户经理）
        if (in_array($admin_info->id, [1,2,3])) {

        }

        // 客户部负责人
        if ($admin_info->id == 4) {
            $users = $admin_model->where('department_id', '=', $admin_info->department_id)->where('role', '=', 3)->select();
        }

        $this->success('', $users);
    }

    /**
     * 订单统计
     */
    public function stat()
    {
        $apply_model = new Apply();
        $type = $this->request->request('type');

        if (in_array($type, array('week', 'last_week'))) {
            $where_time = $type == 'week' ? 'week' : 'last week';
            $field = ['count(*) as count', 'DAYOFWEEK(apply_time) as date'];
        } elseif (in_array($type, array('month', 'last_month'))) {
            $where_time = $type == 'month' ? 'month' : 'last month';
            $field = ['count(*) as count','DAYOFMONTH(apply_time) as date', 'apply_time'];
        } else {
            $this->error('参数类型错误');
        }

        $list = $apply_model->field($field)->whereTime('apply_time', $where_time)->group('date')->select();
        $data['x'] = [];
        $data['y'] = [];
        if (!empty($list)) {
            foreach ($list as $key => &$val) {
                $data['y'][] = $val['count'];
                if (in_array($type, array('week', 'last_week'))) {
                    $data['x'][] = $this->get_week_day($val['date']);
                } elseif (in_array($type, array('month', 'last_month'))) {
                    $data['x'][] = "'" . $val['date'] . "'";
                }
            }
        }

        if (empty($data['x'])) {
            $data['y'] = [0,0,0,0,0,0,0];
            if (in_array($type, array('week', 'last_week'))) {
                $data['x'] = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
            } elseif (in_array($type, array('month', 'last_month'))) {
                $data['x'] = ['1','2','3','4','5','6','7'];
            }
        }

        $this->success('', $data);
    }

    /**
     * 审批统计
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function check_stat()
    {
        $apply_model = new Apply();
        $admin_id = $this->request->request('admin_id');
        $type = $this->request->request('type');
        if (empty($type) || !in_array($type, ['week', 'last_week', 'month', 'last_month'])) {
            $this->error('参数错误');
        }

        if (in_array($type, array('week', 'last_week'))) {
            $where_time = $type == 'week' ? 'week' : 'last week';
        } elseif (in_array($type, array('month', 'last_month'))) {
            $where_time = $type == 'month' ? 'month' : 'last month';
        }

        $admin_model = new Admin();
        $admin_info = $admin_model->where('id', '=', $admin_id)->find();

        if (!empty($admin_info) && $admin_info->role == 3) {
            $list = $apply_model->field('apply_time, status')->where('admin_id', '=', $admin_id)->whereTime('apply_time', $where_time)->select();
        } else {
            $list = $apply_model->field('apply_time, status')->whereTime('apply_time', $where_time)->select();
        }

        $data = [];
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                $data[$val['status']][] = $val;
            }
        }

        $res['x'] = ['未审核', '审核中', '已拒绝', '已通过'];

        $res['y'][] = !empty($data[0]) ? count($data[0]) : 0;
        $res['y'][] = !empty($data[1]) ? count($data[1]) : 0;
        $res['y'][] = !empty($data[2]) ? count($data[2]) : 0;
        $res['y'][] = !empty($data[3]) ? count($data[3]) : 0;

        $this->success('', $res);
    }


    public function user_stat()
    {
        $user_model = new User();
        $type = $this->request->request('type');

        if (in_array($type, array('week', 'last_week'))) {
            $where_time = $type == 'week' ? 'week' : 'last week';
            $field = ['count(*) as count', 'DAYOFWEEK(created_time) as date', 'created_time'];
        } elseif (in_array($type, array('month', 'last_month'))) {
            $where_time = $type == 'month' ? 'month' : 'last month';
            $field = ['count(*) as count','DAYOFMONTH(created_time) as date'];
        } else {
            $this->error('参数类型错误');
        }

        $list = $user_model->field($field)->whereTime('created_time', $where_time)->group('date')->select();


        $data['x'] = [];
        $data['y'] = [];
        if (!empty($list)) {
            foreach ($list as $key => &$val) {
                $data['y'][] = $val['count'];
                if (in_array($type, array('week', 'last_week'))) {
                    $data['x'][] = $this->get_week_day($val['date']);
                } elseif (in_array($type, array('month', 'last_month'))) {
                    $data['x'][] = "'" . $val['date'] . "'";
                }
            }
        }

        if (empty($data['x'])) {
            $data['y'] = [0,0,0,0,0,0,0];
            if (in_array($type, array('week', 'last_week'))) {
                $data['x'] = ['周一', '周二', '周三', '周四', '周五', '周六', '周日'];
            } elseif (in_array($type, array('month', 'last_month'))) {
                $data['x'] = ['1','2','3','4','5','6','7'];
            }
        }

        $this->success('', $data);
    }



    public function get_time_type_name($type)
    {
        if (in_array($type, array('week', 'last_week'))) {
            return 'week';
        } elseif (in_array($type, array('month', 'last_month'))) {
            return 'month';
        }
    }


    public function get_week_day($key)
    {
        $week = [
            1 => '周日',
            2 => '周一',
            3 => '周二',
            4 => '周三',
            5 => '周四',
            6 => '周五',
            7 => '周六',
        ];
        return $week[$key];
    }

}
































