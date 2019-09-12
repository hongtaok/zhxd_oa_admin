<?php

namespace app\admin\model;

use fast\Tree;
use think\Db;
use think\Model;


class Department extends Model
{
    // 表名
    protected $name = 'department';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'manager_ids',
        'manager_names',
        'full_name',
    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    public function managers()
    {
        return $this->belongsToMany('Admin', 'department_manager');
    }

    public function getManagerIdsAttr()
    {
        return implode(',', $this->managers()->column('admin_id'));
    }

    public function getManagerNamesAttr()
    {
        return implode(',', $this->managers()->column('username'));
    }

    public function getFullNameAttr()
    {
        $tree = Tree::instance();
        $parents = Db::table('oa_department')->field('id, pid, name')->select();
        $tree->init($parents);
        $parent_names = $tree->getParentsNames($this->id, true);
        return $department_parents_name = implode('-', $parent_names);
    }

}
