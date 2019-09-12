<?php

namespace app\admin\model;

use think\Model;


class Employee extends Model
{

    

    

    // 表名
    protected $name = 'employee';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'sex_text',
        'department_ids',
        'department_names',
    ];
    

    
    public function getSexList()
    {
        return ['1' => __('Sex 1'), '2' => __('Sex 2')];
    }


    public function getSexTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['sex']) ? $data['sex'] : '');
        $list = $this->getSexList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function departments()
    {
        return $this->belongsToMany('Department', 'employee_department');
    }

    public function getDepartmentIdsAttr()
    {
        return implode(',', $this->departments()->column('department_id'));
    }

    public function getDepartmentNamesAttr()
    {
        return implode(',', $this->departments()->column('name'));
    }

}
