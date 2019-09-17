<?php

namespace app\admin\controller;

use app\admin\model\User;
use app\admin\model\Apply;
use app\admin\model\Product;
use app\common\controller\Backend;
use app\common\model\Attachment;
use think\Config;

/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
        $seventtime = \fast\Date::unixtime('day', -7);
        $paylist = $createlist = [];
        for ($i = 0; $i < 7; $i++)
        {
            $day = date("Y-m-d", $seventtime + ($i * 86400));
            $createlist[$day] = mt_rand(20, 200);
            $paylist[$day] = mt_rand(1, mt_rand(1, $createlist[$day]));
        }
        $hooks = config('addons.hooks');
        $uploadmode = isset($hooks['upload_config_init']) && $hooks['upload_config_init'] ? implode(',', $hooks['upload_config_init']) : 'local';
        $addonComposerCfg = ROOT_PATH . '/vendor/karsonzhang/fastadmin-addons/composer.json';
        Config::parse($addonComposerCfg, "json", "composer");
        $config = Config::get("composer");
        $addonVersion = isset($config['version']) ? $config['version'] : __('Unknown');

        $user_model = new User();
        $totaluser = $user_model->count();# 总会员数

        $apply_model = new Apply();
        $total_apply = $apply_model->count();# 总申请数
        $total_reject_apply = $apply_model->where('status', '=', 2)->count();# 总驳回数
        $total_apply_amount= $apply_model->where('status', '=', 3)->sum('final_check_fund');# 总放款金额

        // 附件统计
        $attachment_model = new Attachment();
        $total_attachment = $attachment_model->count();

        $product_model = new Product();
        $total_product = $product_model->count();# 产品总数

        $pcate_model = new \app\admin\model\Pcate();
        $total_pcate = $pcate_model->count();# 产品分类数量

        $article_model = new \app\admin\model\Article();
        $total_article = $article_model->count();# 文章总数

        $article_category_model = new \app\admin\model\ArticleCategory();
        $total_article_category = $article_category_model->count();# 文章分类总数
        $article_list = $article_model->order('id', 'desc')->limit(8)->select();

        $message_model = new \app\admin\model\Message();
        $total_message = $message_model->count();# 留言总数
        $total_message_user = $message_model->group('user_id')->count();# 留言人数
        $message_list = $message_model->with('user')->order('id', 'desc')->limit(8)->select();

        $this->view->assign([
            'totaluser'        => $totaluser,
            'total_apply'      => $total_apply,
            'total_reject_apply'      => $total_reject_apply,
            'total_apply_amount'      => $total_apply_amount,
            'total_attachment'      => $total_attachment,
            'total_product'      => $total_product,
            'total_pcate'      => $total_pcate,
            'total_article'      => $total_article,
            'total_article_category'      => $total_article_category,
            'total_message'      => $total_message,
            'total_message_user'      => $total_message_user,

            'article_list'      => $article_list,
            'message_list'      => $message_list,

            'paylist'          => $paylist,
            'createlist'       => $createlist,
            'addonversion'       => $addonVersion,
            'uploadmode'       => $uploadmode
        ]);

        return $this->view->fetch();
    }

}
