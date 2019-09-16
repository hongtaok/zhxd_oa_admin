<?php


function webapp_auth($code, $is_manager = false)
{
    $url = "https://api.weixin.qq.com/sns/jscode2session";
    if (!$is_manager) {
        // c 端小程序
        $data['appid']= 'wxdc118d9357f80c27';
        $data['secret']= 'eb36607ba0eb00fc11328cc0b1d5609b';
    } else {
        // b 端小程序
        $data['appid'] = 'wx5ad2bf99caa41e5a';
        $data['secret'] = 'fb3e89cffbe6136570dc3a3f3e71ada6';
    }

    $data['js_code']= $code;
    $data['grant_type']= 'authorization_code';

    // 微信api返回的session_key和openid
    $arr = httpWurl($url, $data, 'POST');
    $arr = json_decode($arr,true);
    return $arr;
    // 判断是否成功
    if(isset($arr['errcode']) && !empty($arr['errcode'])){
        throw new \think\Exception($arr['errmsg'], $arr['errcode']);
    }

    $data['openid'] = $arr['openid'];
    $data['session_key'] = $arr['session_key'];

    return $arr;
}


function httpWurl($url, $params, $method = 'GET', $header = array(), $multi = false){
    date_default_timezone_set('PRC');
    $opts = array(
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => $header,
        CURLOPT_COOKIESESSION  => true,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_COOKIE         =>session_name().'='.session_id(),
    );
    /* 根据请求类型设置特定参数 */
    switch(strtoupper($method)){
        case 'GET':
            // $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
            // 链接后拼接参数  &  非？
            $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
            break;
        case 'POST':
            //判断是否传输文件
            $params = $multi ? $params : http_build_query($params);
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
        default:
            throw new Exception('不支持的请求方式！');
    }
    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data  = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if($error) throw new Exception('请求发生错误：' . $error);
    return  $data;
}



