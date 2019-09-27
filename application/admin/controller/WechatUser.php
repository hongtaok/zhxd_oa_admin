<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use EasyWeChat\Foundation\Application;
use think\Db;

/**
 * 微信粉丝管理
 *
 * @icon fa fa-circle-o
 */
class WechatUser extends Backend
{
    
    /**
     * WechatUser模型对象
     * @var \app\admin\model\WechatUser
     */
    protected $model = null;
    protected $searchFields = 'openid,unionid';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\WechatUser;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 初始化服务号粉丝数据（清空 并 全部插入）
     * @throws \Exception
     */
    public function wechat_user_list_init()
    {
        Db::query('truncate table oa_wechat_user');

        $wechat_config = get_addon_config('wechat');
        $app = new Application($wechat_config);
        $user_list = $app->user->lists();

        $wechat_user_model = new \app\admin\model\WechatUser();

        $user_list = array_chunk($user_list['data']['openid'], 100);
        if (!empty($user_list)) {
            foreach ($user_list as $list) {
                $user_info_list = $app->user->batchGet($list);
                $user_info_list = $user_info_list['user_info_list'];
                $wechat_user_model->saveAll($user_info_list);
            }
        }
        $this->success('同步成功');
    }

}
