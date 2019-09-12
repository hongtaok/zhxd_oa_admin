<?php

namespace app\admin\model;

use think\Model;


class Product extends Model
{

    

    

    // 表名
    protected $name = 'product';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'evidences'
    ];

    public function pcate()
    {
        return $this->belongsTo('Pcate');
    }

    public function getEvidencesAttr()
    {
        return Evidence::where('id', 'in', explode(',', $this->evidence_ids))->select();
//        return implode(',' , Evidence::where('id', 'in', explode(',', $this->evidence_ids))->column('name'));
    }



}
