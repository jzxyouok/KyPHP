<?php
// +----------------------------------------------------------------------
// | [KyPHP System] Copyright (c) 2020 http://www.kuryun.com/
// +----------------------------------------------------------------------
// | [KyPHP] 并不是自由软件,你可免费使用,未经许可不能去掉KyPHP相关版权
// +----------------------------------------------------------------------
// | Author: fudaoji <fdj@kuryun.cn>
// +----------------------------------------------------------------------
/**
 * Created by PhpStorm.
 * Script Name: Mp.php
 * Create: 2020/5/28 15:15
 * Description: 微信相关
 * Author: fudaoji<fdj@kuryun.cn>
 */
namespace app\mp\event;

use app\common\event\Base;
use app\common\model\AdminStore;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Messages\Image;
use EasyWeChat\Kernel\Messages\Music;
use EasyWeChat\Kernel\Messages\News;
use EasyWeChat\Kernel\Messages\NewsItem;
use EasyWeChat\Kernel\Messages\Text;
use EasyWeChat\Kernel\Messages\Video;
use EasyWeChat\Kernel\Messages\Voice;
use ky\ErrorCode;
use think\facade\Log;

class Mp extends Base
{
    /**
     * 获取开放平台
     * @return \EasyWeChat\OpenPlatform\Application
     * Author: fudaoji<fdj@kuryun.cn>
     * @throws \Exception
     */
    public function getOpenPlatform(){
        $platform = config('system.weixin.platform');
        if(empty($platform)){
            exception("开放平台参数未配置", ErrorCode::WxCompException);
        }
        $config = [
            'app_id' => $platform['appid'],
            'secret'   => $platform['appsecret'],
            'token'    => $platform['token'],
            'aes_key'  => $platform['aes_key']
        ];
        return Factory::openPlatform($config);
    }

    /**
     * 获取公众号APP
     * @param $mp_info
     * @return \EasyWeChat\OfficialAccount\Application|\EasyWeChat\OpenPlatform\Authorizer\OfficialAccount\Application
     * Author: fudaoji<fdj@kuryun.cn>
     * @throws \Exception
     */
    public function getApp($mp_info){
        $config = [
            'app_id'   => '',
            'secret'   => '',
            'token'    => '',
            'response_type' => 'array',
            'log' => [
                'level' => 'error',
                'file'  => RUNTIME_PATH . 'log/mp-'.$mp_info['id'].'.log',
            ],
        ];
        if($mp_info['appsecret']){
            $config['aes_key'] = $mp_info['encodingaeskey'];
            $config['app_id'] = $mp_info['appid'];
            $config['secret'] = $mp_info['appsecret'];
            $config['token'] = $mp_info['refresh_token'];
            return Factory::officialAccount($config);
        }else{
            return $this->getOpenPlatform()->officialAccount($mp_info['appid'], $mp_info['refresh_token']);
        }    
    }
    
    /**
     * 授权方微信公众号信息入库
     * @param array $auth_info
     * @param int $uid
     * @return mixed
     * @author Jason<dcq@kuryun.cn>
     */
    public function updateAuthInfo($auth_info = [], $uid = 0) {
        $insert_data = [
            'appid'             => $auth_info['authorization_info']['authorizer_appid'],
            'appsecret'         => '', //区分手动接入
            'refresh_token'     => $auth_info['authorization_info']['authorizer_refresh_token'],
            'nick_name'         => $auth_info['authorizer_info']['nick_name'],
            'service_type_info' => $auth_info['authorizer_info']['service_type_info']['id'],
            'verify_type_info'  => $auth_info['authorizer_info']['verify_type_info']['id'],
            'user_name'         => $auth_info['authorizer_info']['user_name'],
            'principal_name'    => $auth_info['authorizer_info']['principal_name'],
            'alias'             => $auth_info['authorizer_info']['alias'],
            'business_info'     => json_encode($auth_info['authorizer_info']['business_info']),
            'qrcode_url'        => $auth_info['authorizer_info']['qrcode_url'],
            'idc'               => $auth_info['authorizer_info']['idc'],
            'signature'         => $auth_info['authorizer_info']['signature'],
            'func_info'         => json_encode($auth_info['authorization_info']['func_info']),
            'is_auth'           => 1
        ];
        //未设置头像时，无该字段
        if(isset($auth_info['authorizer_info']['head_img'])) {
            $insert_data['head_img'] = $auth_info['authorizer_info']['head_img'];
        }
        if($mp = model('mp')->getOneByMap(['appid' => $insert_data['appid']])) {
            $insert_data['id'] = $mp['id'];
            $result = model('mp')->updateOne($insert_data);
        }else {
            $store = model('adminStore')->addOne(['uid' => $uid, 'type' => AdminStore::MP]);
            $insert_data['uid'] = $uid;
            $insert_data['id'] = $store['id'];
            $result = model('mp')->addOne($insert_data);
        }
        return $result;
    }

    /**
     * 授权方小程序信息入库
     * @param array $auth_info
     * @param int $user_id
     * @return mixed
     * @author Jason<dcq@kuryun.cn>
     */
    public function updateMiniInfo($auth_info, $user_id = 1) {
        $insert_data = [
            'appid'             => $auth_info['authorization_info']['authorizer_appid'],
            'refresh_token'     => $auth_info['authorization_info']['authorizer_refresh_token'],
            'nick_name'         => $auth_info['authorizer_info']['nick_name'],
            'service_type_info' => $auth_info['authorizer_info']['service_type_info']['id'],
            'verify_type_info'  => $auth_info['authorizer_info']['verify_type_info']['id'],
            'user_name'         => $auth_info['authorizer_info']['user_name'],
            'principal_name'    => $auth_info['authorizer_info']['principal_name'],
            'alias'             => $auth_info['authorizer_info']['alias'],
            'business_info'     => json_encode($auth_info['authorizer_info']['business_info']),
            'qrcode_url'        => $auth_info['authorizer_info']['qrcode_url'],
            'idc'               => $auth_info['authorizer_info']['idc'],
            'signature'         => $auth_info['authorizer_info']['signature'],
            'func_info'         => json_encode($auth_info['authorization_info']['func_info']),
            'mini_program_info' => json_encode($auth_info['authorizer_info']['MiniProgramInfo']),
            'is_auth'           => 1
        ];
        //未设置头像时，无该字段
        if(isset($auth_info['authorizer_info']['head_img'])) {
            $insert_data['head_img'] = $auth_info['authorizer_info']['head_img'];
        }
        if($mini = model('Mini')->getOneByMap(['appid' => $auth_info['authorization_info']['authorizer_appid']])) {
            $insert_data['id'] = $mini['id'];
            $result = model('Mini')->updateOne($insert_data);
        }else {
            $insert_data['id'] = $user_id;
            $result = model('Mini')->addOne($insert_data);
        }

        return $result;
    }
}