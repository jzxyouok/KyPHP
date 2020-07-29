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
 * Script Name: AddonMiniApi.php
 * Create: 2020/7/29 15:50
 * Description: 小程序应用公共接口类
 * Author: fudaoji<fdj@kuryun.cn>
 */
namespace app\common\controller;

use ky\ErrorCode;
use ky\Helper;

class AddonMiniApi extends BaseCtl
{
    //protected $openPlatform;
    protected $miniApp;
    protected $miniInfo;
    protected $miniAddonInfo;
    /**
     * @var \app\common\model\Mini
     */
    protected $miniM;
    protected $miniId;

    /**
     * @var string
     */
    protected $addonName;
    /**
     * @var string
     */
    protected $addonController;
    /**
     * @var string
     */
    protected $addonAction;

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        if(request()->isPost() || 1){
            $this->miniM = model('common/mini');
            $this->setMiniId();
            $this->setMiniInfo();
            $this->setApp();
            $this->setAddonInfo();

        }else{
            abort(ErrorCode::CatchException, '非法请求');
        }
    }

    /**
     * 设置应用信息
     * @author: fudaoji<fdj@kuryun.cn>
     */
    public function setAddonInfo(){
        $this->addonName = strtolower($this->request->param('addon'));
        $this->addonController = strtolower($this->request->param('col'));
        $this->addonAction = strtolower($this->request->param('act'));
        $this->miniAddonInfo = model('common/miniAddon')->getOneByMap([
            'addon' => $this->addonName, 'mini_id' => $this->miniId,
        ]);
        if ($this->miniAddonInfo && $this->miniAddonInfo['infos']) {
            $this->miniAddonInfo['infos'] = json_decode($this->miniAddonInfo['infos'], true);
        }else{
            Helper::error(ErrorCode::BadParam, '请先在电脑端进行应用配置');
        }
    }

    /**
     * 设置小程序id
     * Author: fudaoji<fdj@kuryun.cn>
     */
    protected function setMiniId(){
        $this->miniId = input('mid', 0, 'intval');
        if(empty($this->miniId)){
            abort(ErrorCode::CatchException, '非法请求,mid参数丢失');
        }
    }

    /**
     * 设置小程序信息
     * @author fudaoji<fdj@kuryun.cn>
     */
    protected function setMiniInfo() {
        $this->miniInfo = $this->miniM->getOne($this->miniId);
        if(empty($this->miniInfo)){
            abort(ErrorCode::CatchException, '非法请求,小程序不存在');
        }
    }

    /**
     * 设置小程序应用
     * @author fudaoji<fdj@kuryun.cn>
     */
    protected function setApp() {
        $this->miniApp = controller('mini/mini', 'event')->getApp($this->miniInfo);
        //$this->openPlatform = controller('mp', 'event')->getOpenPlatform();
    }
}