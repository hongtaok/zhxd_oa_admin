<?php

namespace app\admin\model;

use think\Model;


class Config extends Model
{

    

    

    // 表名
    protected $name = 'config';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







}
