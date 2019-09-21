<?php

namespace app\admin\model;

use fast\Tree;
use think\Model;
use think\Session;

class Admin extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $append = [
        'role_text',
        'department_name',
    ];

    /**
     * 重置用户密码
     * @author baiyouwen
     */
    public function resetPassword($uid, $NewPassword)
    {
        $passwd = $this->encryptPassword($NewPassword);
        $ret = $this->where(['id' => $uid])->update(['password' => $passwd]);
        return $ret;
    }

    // 密码加密
    protected function encryptPassword($password, $salt = '', $encrypt = 'md5')
    {
        return $encrypt($password . $salt);
    }

    public function department()
    {
        return $this->belongsTo('Department');
    }

    public function getRoleList()
    {
        return [
            1 => '市场部专员',
            2 => '客户经理-前端',
            3 => '客户经理-后端',
            4 => '客户部负责人',
            5 => '风控初审',
            6 => '风控中审',
            7 => '风控终审',
            8 => '分公司总经理',
            9 => '运营总监',
            10 => '总经理',
            11 => '系统管理员',
            12 => '超级管理员',
        ];
    }

    public function getRoleTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['role']) ? $data['role'] : '');
        $list = $this->getRoleList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getDepartmentNameAttr($department_id)
    {
        $tree = Tree::instance();
        $department_model = new Department();
        $departments = collection($department_model->select())->toArray();
        $tree->init($departments);

        $department_parents = $tree->getParentsNames($this->department_id, true);
        return $department_parents_name = implode('-', $department_parents);
    }

}
