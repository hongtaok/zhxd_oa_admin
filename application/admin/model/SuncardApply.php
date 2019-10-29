<?php

namespace app\admin\model;

use think\Model;


class SuncardApply extends Model
{

    

    

    // 表名
    protected $name = 'suncard_apply';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'from_type_text'
    ];

    public function getFromTypeList()
    {
        return ['1' => '中汇鑫德', '2' => '尧商行', '3' => '公众号', '4' => '商户'];
    }

    public function getFromTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['from_type']) ? $data['from_type'] : '');
        $list = $this->getFromTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    







}
