<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/4
 * Time: 17:16
 */

namespace app\api\controller;


use app\admin\model\Apply;
use app\common\controller\Api;
use think\Db;
use think\Exception;
use think\Session;
use think\Validate;
class Order extends Api
{

    protected $noNeedLogin = ['apply'];

    /**
     * 申请贷款
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function apply()
    {
        $user_model = model('user');
        $apply_model = new Apply();

        $params['code'] = $this->request->request('code');
        $params['product_id'] = $this->request->request('product_id');
        $params['city'] = $this->request->request('city');
        $params['username'] = $this->request->request('username');
        $params['mobile'] = $this->request->request('mobile');
        $params['id_number'] = $this->request->request('id_number');

        $validate = new Validate([
            'code' => 'require',
            'product_id' => 'require',
            'city' => 'require',
            'username' => 'require',
            'mobile' => 'require',
            'id_number' => 'require',
        ]);
        if (!$validate->check($params)) {
            $this->error($validate->getError());
        }

        // 根据 code 获取 openid 和 session_key
        $webapp_data = webapp_auth($params['code']);
        $openid = $webapp_data['openid'];
        $session_key = $webapp_data['session_key'];

        $user_info = $user_model->where('openid', '=', $openid)->find();
        if (empty($user_info)) {
            // 如果用户不存在， 则新增用户并新增贷款申请
            $user_model->openid = $openid;
            $user_model->session_key = $session_key;
            $user_model->username = $params['username'];
            $user_model->mobile = $params['mobile'];
            $user_model->id_number = $params['id_number'];
            $user_model->save();

            $apply_model->product_id = $params['product_id'];
            $apply_model->user_id = $user_model->getLastInsID();
            $apply_model->apply_time = datetime(time(), 'Y-m-d H:i:s');
            $apply_model->save();
        } else {
            // 用户提交申请信息后， 补充用户信息
            if (!empty($user_info->mobile) || !empty($user_info->id_number || !empty($user_info['username']))) {
                // 如果用户已经授权过了， 而且已经绑定过基本信息， 这里就不更改信息了
                if ($user_info->mobile != $params['mobile']) {
                    $this->error('您提交的手机信息与之前提交的不一致，请联系管理员解决');
                }

                if ($user_info->id_number != $params['id_number']) {
                    $this->error('您提交的身份证信息与之前提交的不一致，请联系管理员解决');
                }

                if ($user_info->username != $params['username']) {
                    $this->error('您提交的姓名与之前提交的不一致，请联系管理员解决');
                }
            } else {
                $user_model->save([
                    'username' => $params['username'],
                    'mobile' => $params['mobile'],
                    'id_number' => $params['id_number'],
                ], ['id' => $user_info->id]);
            }

            // 如果用户存在， 则只新增贷款申请
            $apply_exists = $apply_model->where('product_id', $params['product_id'])->where('user_id', $user_info->id)->select();
            if (!empty($apply_exists)) {
                $this->error('您已申请过该类型的贷款产品');
            }

            $apply_model->product_id = $params['product_id'];
            $apply_model->user_id = $user_info->id;
            $apply_model->apply_time = datetime(time(), 'Y-m-d H:i:s');
            $apply_model->save();
        }

        $this->success('', ['session_key' => $session_key]);
    }




}