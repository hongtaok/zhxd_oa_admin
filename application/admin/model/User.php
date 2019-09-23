<?php

namespace app\admin\model;

use app\common\model\MoneyLog;
use think\Db;
use think\Model;

class User extends Model
{

    // 表名
    protected $name = 'user';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
//        'prevtime_text',
//        'logintime_text',
//        'jointime_text',
    ];

    public function getOriginData()
    {
        return $this->origin;
    }

    protected static function init()
    {
        self::beforeUpdate(function ($row) {
            $changed = $row->getChangedData();
            //如果有修改密码
            if (isset($changed['password'])) {
                if ($changed['password']) {
                    $salt = \fast\Random::alnum();
                    $row->password = \app\common\library\Auth::instance()->getEncryptPassword($changed['password'], $salt);
                    $row->salt = $salt;
                } else {
                    unset($row->password);
                }
            }
        });


        self::beforeUpdate(function ($row) {
            $changedata = $row->getChangedData();
            if (isset($changedata['money'])) {
                $origin = $row->getOriginData();
                MoneyLog::create(['user_id' => $row['id'], 'money' => $changedata['money'] - $origin['money'], 'before' => $origin['money'], 'after' => $changedata['money'], 'memo' => '管理员变更金额']);
            }
        });
    }

    public function getGenderList()
    {
        return ['1' => __('Male'), '0' => __('Female')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }

    public function getPrevtimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['prevtime'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getLogintimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['logintime'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getJointimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['jointime'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPrevtimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setLogintimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setJointimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    public function group()
    {
        return $this->belongsTo('UserGroup', 'group_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function applies()
    {
        return $this->hasMany('Apply');
    }

    public function team($admin_id = 0)
    {
        $this->field('id, admin_id, username, mobile, nickname, avatar, pid');
        if (empty($admin_id)) {
            $this->where('pid', '=', $this->id);
        } else {
            $this->where('admin_id', '=', $admin_id);
        }

        $user_team_first = $this->select();
        $config_model = new Config();

        if (!empty($user_team_first)) {
            $scale_one = $config_model->where('name', '=', 'scale_one')->find();
            foreach ($user_team_first as $user_own_key => &$user_own_val) {
                $user_own_val['tier'] = 1;
                $user_own_val['apply_total'] = Db::table('oa_apply')->where('user_id', '=', $user_own_val['id'])->where('status', '=', 3)->sum('final_check_fund');
                $user_own_val['income'] = $user_own_val['apply_total'] * $scale_one->value;
                $user_team_second_ids[] = $user_own_val['id'];
            }
        }

        $user_model = new User();
        if (!empty($user_team_second_ids)) {
            $scale_two = $config_model->where('name', '=', 'scale_two')->find();
            $query = $user_model->field('id, admin_id, username, mobile, nickname, avatar, pid')->whereIn('pid', $user_team_second_ids);
            if (!empty($admin_id)) {
                $query->where('admin_id', '<>', $admin_id);
            }

            $user_team_second = $user_model->select();
            if (!empty($user_team_second)) {
                foreach ($user_team_second as $user_own_key1 => &$user_own_val1) {
                    $user_own_val1['tier'] = 2;
                    $user_own_val1['apply_total'] = Db::table('oa_apply')->where('user_id', '=', $user_own_val1['id'])->where('status', '=', 3)->sum('final_check_fund');
                    $user_own_val1['income'] = $user_own_val1['apply_total'] * $scale_two->value;
                }
            }
        } else {
            $user_team_second = [];
        }
        $first_total = count($user_team_first);
        $second_total = count($user_team_second);
        $user_team_total = $first_total + $second_total;

        $first_income = array_sum(array_column($user_team_first, 'income'));
        $second_income = array_sum(array_column($user_team_second, 'income'));

        $data = ['total' => $user_team_total, 'income_total' => $first_income + $second_income, 'first_total' => $first_total, 'second_total' => $second_total, 'first' => $user_team_first, 'second' => $user_team_second];
        return $data;
    }

}
