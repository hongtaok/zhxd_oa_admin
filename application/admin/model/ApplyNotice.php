<?php

namespace app\admin\model;

use think\Model;


class ApplyNotice extends Model
{

    

    

    // 表名
    protected $name = 'apply_notice';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    
    public function admin()
    {
        return $this->belongsTo('Admin');
    }


    public function user()
    {
        return $this->belongsTo('User');
    }







}
