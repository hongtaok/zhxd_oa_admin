<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Db;
use think\Validate;

class Index extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function index()
    {
        $this->redirect('index/apply');
    }

    public function news()
    {
        $this->redirect('index/apply');
        $newslist = [];
        return jsonp(['newslist' => $newslist, 'new' => count($newslist), 'url' => 'https://www.fastadmin.net?ref=news']);
    }

    public function apply()
    {



        if ($this->request->isPost()) {

            $data['username'] = $this->request->request('username');
            $data['phone'] = $this->request->request('phone');
//            $data['id_number'] = $this->request->request('id_number');
            $data['address'] = $this->request->request('address');
            $data['captcha'] = $this->request->request('captcha');
            $agree = $this->request->request('agree');

            if ($agree != 'on') {
                $this->error('请仔细阅读服务协议!', 'index/apply');
            }

            $rules = [
                'username' => 'require',
                'phone' => 'require|length:11|number',
                'captcha|验证码'=>'require|captcha'
            ];

            $msg = [
                'name.require' => '姓名不能为空',
                'phone.require' => '手机号不能为空',
                'phone.length' => '手机号应为11位',
            ];

            $validate = new Validate($rules, $msg);
            if (!$validate->check($data)) {
                $this->error($validate->getError(), 'index/apply');
            }
            unset($data['captcha']);
            Db::table('oa_suncard_apply')->insert($data);

            $this->redirect('index/message');
        }

        return $this->view->fetch();
    }

    public function message()
    {
        return $this->view->fetch();
    }
}
