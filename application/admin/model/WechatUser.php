<?php

namespace app\admin\model;

use think\Model;


class WechatUser extends Model
{

    

    

    // 表名
    protected $name = 'wechat_user';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'subscribe_time_text'
    ];
    

    



    public function getSubscribeTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['subscribe_time']) ? $data['subscribe_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setSubscribeTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
