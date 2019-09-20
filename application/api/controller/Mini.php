<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/3
 * Time: 15:31
 */

namespace app\api\controller;


use app\admin\model\Apply;
use app\admin\model\Article;
use app\admin\model\ArticleCategory;
use app\admin\model\Config;
use app\admin\model\Message;
use app\admin\model\WechatUser;
use app\common\controller\Api;
use EasyWeChat;
use fast\Tree;
use Illuminate\Log\Logger;
use think\Db;
use fast\Random;
use think\Log;
use think\Validate;
use QRcode;
use EasyWeChat\Foundation;
use EasyWeChat\Foundation\Application;

class Mini extends Api
{
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['check_auth', 'user_info', 'user_team', 'user_team_applies', 'apply', 'user_applies', 'user_apply', 'promote', 'articles', 'company_info', 'message', 'auth', 'login', 'wechat_test', 'wechat_user_list_init'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = [];

    /**
     * 检测是否授权入库
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function check_auth()
    {
        $user_model = model('user');

        $user_id = $this->request->request('user_id');
        if (empty($user_id)) {
            $this->error('授权信息错误');
        }

        $user_info = $user_model->where('id', '=', $user_id)->find();
        if (empty($user_info)) {
            $this->error('授权信息错误');
        }
        return $user_info;
    }

    /**
     * 获取用户信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_info()
    {
        $user_info = $this->check_auth();
        $this->success($user_info);
    }

    /**
     * 我的团队
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_team($in = false)
    {
        $user_info = $this->check_auth();
        $user_team_first = Db::table('oa_user')->field('id, username, mobile, nickname, avatar, pid')->where('pid', '=', $user_info->id)->select();

        $config_model = new Config();

        if (!empty($user_team_first)) {
            $scale_one = $config_model->where('name', '=', 'scale_one')->find();
            foreach ($user_team_first as $user_own_key => &$user_own_val) {
                $user_own_val['tier'] = 1;
                $user_own_val['apply_total'] = Db::table('oa_apply')->where('user_id', '=', $user_own_val['id'])->where('status', '=', 3)->sum('final_check_fund');
                $user_own_val['income'] = $user_own_val['apply_total'] * $scale_one->value;
                $user_team_second_ids[] = $user_own_val['id'];
            }
        }

        if (!empty($user_team_second_ids)) {
            $scale_two = $config_model->where('name', '=', 'scale_two')->find();
            $user_team_second = Db::table('oa_user')->field('id, username, mobile, nickname, avatar, pid')->whereIn('pid', $user_team_second_ids)->select();
            if (!empty($user_team_second)) {
                foreach ($user_team_second as $user_own_key1 => &$user_own_val1) {
                    $user_own_val1['tier'] = 2;
                    $user_own_val1['apply_total'] = Db::table('oa_apply')->where('user_id', '=', $user_own_val1['id'])->where('status', '=', 3)->sum('final_check_fund');
                    $user_own_val1['income'] = $user_own_val1['apply_total'] * $scale_two->value;
                }
            }
        } else {
            $user_team_second = [];
        }

        $user_team_total = count($user_team_first) + count($user_team_second);

        $data = ['total' => $user_team_total, 'first' => $user_team_first, 'second' => $user_team_second];
        if (!$in) {
            $this->success('', $data);
        }
        return $data;
    }

    /**
     * 我的业绩（下级-客户申请）
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_team_applies()
    {
        $config_model = new Config();
        $scale_one = $config_model->where('name', '=', 'scale_one')->find();
        $scale_two = $config_model->where('name', '=', 'scale_two')->find();

        $user_teams = $this->user_team(true);
        $users = array_merge($user_teams['first'], $user_teams['second']);

        $applies['list'] = [];
        if (!empty($users)) {
            foreach ($users as $key => &$val) {
                $user_applies = Db::table('oa_apply')->field('id, product_id, user_id, admin_id, first_check_fund, final_check_fund, final_check_time, status')->where('user_id', $val['id'])->where('status', 3)->select();
                if (!empty($user_applies)) {
                    foreach ($user_applies as &$user_apply) {
                        if ($val['tier'] == 1) {
                            $user_apply['apply_income'] = $user_apply['final_check_fund'] * $scale_one->value;
                        }
                        if ($val['tier'] == 2) {
                            $user_apply['apply_income'] = $user_apply['final_check_fund'] * $scale_two->value;
                        }
                        $user_apply['product_name'] = Db::table('oa_product')->where('id', $user_apply['product_id'])->column('name')[0];
                        $user_apply['user_info'] = $val;
                        $applies['list'][] = $user_apply;
                    }
                }
            }
        }

        $applies['total_apply_income'] = array_sum(array_column($applies['list'], 'apply_income'));

        $this->success('', $applies);
    }


    /**
     * 用户的贷款申请列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_applies()
    {
        $user_info = $this->check_auth();

        $status = $this->request->request('status') ?? 0;
        $apply_model = new Apply();
        $applies = $apply_model->with('product')->where('user_id', '=', $user_info->id)->where('status', '=', $status)->select();
        $this->success('', $applies);
    }

    /**
     * 用户的贷款申请详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_apply()
    {
        $user_info = $this->check_auth();

        $apply_id = $this->request->request('apply_id');
        $apply_model = new Apply();
        $apply = $apply_model->with('product')->where('user_id', '=', $user_info->id)->where('id', '=', $apply_id)->find();
        $this->success('', $apply);
    }

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

        $params['product_id'] = $this->request->request('product_id');
        $params['city'] = $this->request->request('city');
        $params['username'] = $this->request->request('username');
        $params['mobile'] = $this->request->request('mobile');
        $params['id_number'] = $this->request->request('id_number');
        $params['user_id'] = $this->request->request('user_id');

        $validate = new Validate([
            'product_id' => 'require',
            'city' => 'require',
            'username' => 'require',
            'mobile' => 'require',
            'id_number' => 'require',
            'user_id' => 'require'
        ], [
            'product_id.require' => '产品不能为空',
            'id_number.require' => '身份证号不能为空',
            'user_id.require' => '授权信息不能为空',
        ]);
        if (!$validate->check($params)) {
            $this->error($validate->getError());
        }

        $user_info = $this->check_auth();
        if (!empty($user_info)) {
            if ($user_info->id != $params['user_id']) {
                $this->error('授权信息错误');
            }

            if (!empty($user_info->mobile) && $user_info->mobile != $params['mobile']) {
                $this->error('您提交的手机信息与之前提交的不一致，请联系管理员解决');
            } else {
                $update_data['mobile'] = $params['mobile'];
            }


            if (!empty($user_info->id_number) && $user_info->id_number != $params['id_number']) {
                $this->error('您提交的身份证信息与之前提交的不一致，请联系管理员解决');
            } else {
                $update_data['id_number'] = $params['id_number'];
            }


            if (!empty($user_info->username) && $user_info->username != $params['username']) {
                $this->error('您提交的姓名与之前提交的不一致，请联系管理员解决');
            } else {
                $update_data['username'] = $params['username'];
            }

            $user_model->save($update_data, ['id' => $user_info->id]);

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

        $this->success('申请成功!');
    }

    /**
     * 推广 (授权过的用户才可以推广)
     * @return int
     */
    public function promote()
    {
        $params['code'] = $this->request->request('code');

        $params['username'] = $this->request->request('username');
        $params['mobile'] = $this->request->request('mobile');
        $params['bank_card'] = $this->request->request('bank_card');
        $params['bank_name'] = $this->request->request('bank_name');
        $params['bank_front_image'] = $this->request->request('bank_front_image');
        $params['bank_back_image'] = $this->request->request('bank_back_image');

        $validate = new Validate([
            'username' => 'require',
            'mobile' => 'require|number',
            'bank_card' => 'require',
            'bank_name' => 'require',
            'bank_front_image' => 'require',
            'bank_back_image' => 'require',
        ], [
            'username.require' => '用户姓名不能为空',
            'mobile.require' => '手机号码不能为空',
            'bank_card.require' => '银行卡号不能为空',
            'bank_name.require' => '开户行不能为空',
            'bank_front_image.require' => '银行卡正面照不能为空',
            'bank_back_image.require' => '银行卡背面照不能为空',
        ]);
        if (!$validate->check($params)) {
            $this->error($validate->getError());
        }

        $user_info = $this->check_auth();

        $data = $this->request->domain() . '?pid=' . $user_info->id;

        $outfile = ROOT_PATH . 'public' . '/qrcode/' . $user_info->id . '_' .  time() . '.jpg';

        $level = 'L';
        $size = 4;
        $QRcode = new QRcode();
        ob_start();
        $QRcode->png($data, $outfile, $level, $size, 2);

        $path_info = pathinfo($outfile);
        $qrcode_url = $this->request->domain() . DS . 'qrcode' . DS . $path_info['basename'];

        // 保存个人的推广二维码
        $user_info->promote_code = $qrcode_url;
        $user_info->username = $params['username'];
        $user_info->mobile = $params['mobile'];
        $user_info->bank_card = $params['bank_card'];
        $user_info->bank_name = $params['bank_name'];
        $user_info->bank_front_image = $params['bank_front_image'];
        $user_info->bank_back_image = $params['bank_back_image'];
        $user_info->save();

        $this->success('', ['user_info' => $user_info]);
    }

    /**
     * 公司动态（文章）
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function articles()
    {
        $article_id = $this->request->request('article_id');

        $article_category_model = new ArticleCategory();
        $article_model = new Article();

        if (!empty($article_id)) {
            $article_info = $article_model->where('id', '=', $article_id)->find();
            $this->success('', ['article_info' => $article_info]);
        }

        $categorys = $article_category_model->select();

        if (!empty($categorys)) {
            foreach ($categorys as $key => $val) {
                $articles = $article_model->where('article_category_id', $val['id'])->select();
                $val['articles'] = $articles;
            }
        }

        $this->success('', ['list' => $categorys]);
    }

    /**
     * 公司信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function company_info()
    {
        $config_model = new Config();

        $list = $config_model->where('group', '=', 'company')->field('id, name, value, title, tip')->select();
        $list = collection($list)->toArray();

        $data = [];
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                $data[$val['name']] = $val;
            }
        }

        $this->success('', ['list' => $data]);
    }

    /**
     * 提交留言
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function message()
    {
        $message_model = new Message();
        $code = $this->request->request('code');
        $content = $this->request->request('content');

        $user_model = new \app\admin\model\User();
        // 根据 code 获取 openid 和 session_key
        $webapp_data = webapp_auth($code);
        $openid = $webapp_data['openid'];
        $session_key = $webapp_data['session_key'];

        $user_info = $user_model->where('openid', '=', $openid)->find();

        if (!empty($user_info)) {
            $message_model->user_id = $user_info->id;
            $message_model->content = $content;
            $message_model->save();
        } else {
            $this->error('未授权');
        }

        $this->success('', ['user_info' => $user_info]);
    }

    /***
     * 授权
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function auth()
    {
        $user_model = model('user');

        $data['code'] = $this->request->request('code');
        $data['nickname'] = $this->request->request('nickname');
        $data['avatar'] = $this->request->request('avatar');
        $pid = $this->request->request('pid');

//        $data['pid'] = $pid;
//        Log::write($data);
//        exit;

        $validate = new Validate([
            'code' => 'require',
            'nickname' => 'require',
            'avatar' => 'require',
        ]);
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $auth_data = webapp_auth($data['code']);

        $openid = $auth_data['openid'];
        $session_key = $auth_data['session_key'];
        if (!empty($auth_data['unionid'])) {
            $unionid = $auth_data['unionid'];
        }

        $user_info = $user_model->where('openid', '=', $openid)->find();
        if (empty($user_info)) {
            $user_model->nickname = $data['nickname'];
            $user_model->avatar = $data['avatar'];
            $user_model->openid = $openid;
            $user_model->session_key = $session_key;
            $user_model->unionid = $unionid;

            if (!empty($pid)) {
                $user_model->pid = $pid;
            }

            $user_model->save();
            $user_id = $user_model->getLastInsID();
        } else {
            $user_id = $user_info->id;

            if (empty($user_info->unionid) && !empty($unionid)) {
                $user_info->unionid = $unionid;
                $user_info->save();
            }
        }

        // 返回 user_id  和  session_key 前端保存
        $this->success('ok', ['user_id' => $user_id, 'session_key' => $session_key, 'auth_data' => $auth_data]);
    }

    public function login()
    {
        $user_model = model('user');
        $mobile = $this->request->request('mobile');
        $password = $this->request->request('password');

        $user_info = $user_model->where('mobile', $mobile)->find();

        if (empty($user_info)) {
            $data['mobile'] = $mobile;
            $data['salt'] = Random::alnum(6);
            $data['password'] = md5(md5($password) . $data['salt']);

            $user_model->save($data);
        } else {
            if ($user_info['password'] == md5(md5($password) . $user_info['salt'])) {
                $user_info->openid_time = time();
                $update = $user_info->save();
                if ($update) {
                    $this->success('登录成功');
                }
            }
        }
        $this->success('登录成功');
    }

    /**
     * 发送微信模板通知（测试）
     * @return \think\response\Json
     * @throws EasyWeChat\Core\Exceptions\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wechat_test()
    {
        $user_info = $this->check_auth();

        $wechat_user_model = new WechatUser();
        $wechat_user_info = $wechat_user_model->where('unionid', '=', $user_info['unionid'])->find();

        $wechat_config = get_addon_config('wechat');
        $app = new Application($wechat_config);

        if (!empty($wechat_user_info)) {
            $data['template_id'] = "2Gschngiyq7tfIcfkUjjSL7eexjdEd9Txl_k8DlGHrA";
            $data['touser'] = $wechat_user_info->openid;
            $data['data'] = [
                "first" => [
                    "value" => "您的审核已经通过！",
                    "color" => "#173177"
                ],
                "keyword1" => [
                    "value" => "风控初审",
                    "color" => "#173177"
                ],
                "keyword2" => [
                    "value" => "10000",
                    "color" => "#173177"
                ],
                "keyword3" => [
                    "value" => "2014年9月22日",
                    "color" => "#173177"
                ],
                "remark" => [
                    "value" => "中汇鑫德",
                    'color' => '#173177'
                ]
            ];

            $res = $app->notice->send($data);
        }

        return json($res);
    }

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

        $wechat_user_model = new WechatUser();

        $user_list = array_chunk($user_list['data']['openid'], 100);
        if (!empty($user_list)) {
            foreach ($user_list as $list) {
                $user_info_list = $app->user->batchGet($list);
                $user_info_list = $user_info_list['user_info_list'];
                $wechat_user_model->saveAll($user_info_list);
            }
        }
    }

}