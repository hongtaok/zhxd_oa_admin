<?php

namespace app\admin\model;

use think\Model;


class ApplyRejectLog extends Model
{

    

    

    // 表名
    protected $name = 'apply_reject_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'reject_time_text'
    ];
    

    



    public function getRejectTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['reject_time']) ? $data['reject_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setRejectTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
