<?php

namespace app\admin\model;

use think\Model;


class Apply extends Model
{

    // 表名
    protected $name = 'apply';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text'
    ];

    public function getStatusList()
    {
        return ['0' => '未审核', '1' => '审核中', '2' => '审核拒绝', '3' => '审核通过'];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function user()
    {
        return $this->belongsTo('User')->setEagerlyType(0);
    }

    public function admin()
    {
        return $this->belongsTo('Admin')->setEagerlyType(0);
    }

    public function product()
    {
        return $this->belongsTo('Product')->setEagerlyType(0);
    }

}
