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
 * Script Name: Qrcode.php
 * Create: 2020/6/17 17:04
 * Description: 二维码
 * @link https://developers.weixin.qq.com/doc/offiaccount/Account_Management/Generating_a_Parametric_QR_Code.html 微信官方文档
 * @link https://www.easywechat.com/docs/4.1/basic-services/qrcode  easywechat文档
 * Author: fudaoji<fdj@kuryun.cn>
 */

namespace app\mp\controller;

use app\admin\controller\FormBuilder;
use think\Db;
use think\facade\Log;

class Qrcode extends Base
{
    /**
     * @var \app\common\model\MpQrcode
     */
    private $qrM;
    /**
     * @var \app\common\model\MpQrcodeLog
     */
    private $qrLogM;
    /**
     * @var array
     */
    private $tabList;

    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub

        if(! ($this->mpInfo['verify_type_info'] >= 0 && $this->mpInfo['service_type_info'] == 2)){
            $this->error("当前公众号未获得生成带参数二维码的接口");
        }

        $this->qrM = model('mpQrcode');
        $this->qrLogM = model('mpQrcodeLog');
        $this->tabList = [
            'index' => [
                'title' => '列表',
                'href' => url('index', ['type' => 'index'])
            ],
            'log' => [
                'title' => '扫描统计',
                'href' => url('index', ['type' => 'log'])
            ]
        ];
    }

    /**
     * 二维码扫码统计
     * @return mixed
     * Author: fudaoji<fdj@kuryun.cn>
     * @throws \think\exception\DbException
     */
    public function log(){
        $id = input('qrcode_id', 0, 'intval');
        $type = input('type', -1);
        $where = ['log_mpid' => $this->mpId, 'qrcode_id' => $id];
        $type != -1 && $where['type'] = $type;

        $search_key = input('search_key', '');
        $search_key && $where['f.nickname'] = ['like', '%'.$search_key.'%'];
        $data_list = $this->qrLogM->pageJoin([
            'alias' => 'l',
            'join' => [
                [model('mpFollow')->getTrueTable(['mpid' => $this->mpId]) . ' f', 'f.openid=l.openid']
            ],
            'page_size' => $this->pageSize,
            'where' => $where,
            'order' => ['l.id' => 'desc'],
            'refresh' => 1,
            'field' => 'l.*, f.nickname, f.headimgurl'
        ]);

        $page = $data_list->appends(['search_key' => $search_key, 'type' => $type])->render();
        $assign = [
            'type' => $type,
            'data_list' => $data_list,
            'page' => $page,
            'search_key' => $search_key
        ];
        return $this->show($assign);
    }

    /**
     * 删除
     * Author: fudaoji<fdj@kuryun.cn>
     */
    public function delPost(){
        if(request()->isPost()){
            $id = input('post.id', 0);
            if(! $data = $this->qrM->getOne($id)){
                $this->error('数据不存在', '', ['token' => request()->token()]);
            }
            $this->qrM->delOne($id);
            $this->success('删除成功');
        }
    }

    /**
     * 二维码列表
     * @return mixed
     * Author: fudaoji<fdj@kuryun.cn>
     * @throws \think\exception\DbException
     */
    public function index(){
        $type = input('type', 'index');
        $search_key = input('search_key', '');
        $where = ['mpid' => $this->mpId];
        $search_key && $where['title|keyword'] = ['like', '%'.$search_key.'%'];
        $data_list = $this->qrM->page($this->pageSize, $where, ['id' => 'desc'], true, 1);
        $page = $data_list->appends(['search_key' => $search_key, 'type' => $type])->render();
        $assign = [
            'tab_list' => $this->tabList,
            'type' => $type,
            'data_list' => $data_list,
            'page' => $page,
            'search_key' => $search_key
        ];
        return $this->show($assign);
    }

    /**
     * 增加二维码
     * @return mixed
     * @author: fudaoji<fdj@kuryun.cn>
     */
    public function add(){
        if(request()->isPost()){
            $post_data = input('post.');
            $post_data['mpid'] = $this->mpId;
            $res = $this->validate($post_data,'MpQrcode');
            if($res !== true){
                $this->error($res, '', ['token' => request()->token()]);
            }
            $data = [
                'mpid' => $this->mpId,
                'title' => $post_data['title'],
                'keyword' => $post_data['keyword'],
                'type' => $post_data['type'],
                'scene_str' => $post_data['scene_str'],
                'expire' => intval($post_data['expire'])
            ];

            try{
                if($post_data['type'] > 0){
                    $res = $this->mpApp->qrcode->temporary($data['scene_str'], $data['expire']);
                }else{
                    $res = $this->mpApp->qrcode->forever($data['scene_str']);
                }

                if(!empty($res['url'])){
                    $data['url'] = $res['url'];
                    $data['ticket'] = $res['ticket'];
                    $data['qrcode_url'] = $this->mpApp->qrcode->url($data['ticket']);
                    $short_url = $this->mpApp->url->shorten($data['qrcode_url']);
                    if(isset($short_url) && $short_url['errcode'] == 0){
                        $data['short_url'] = $short_url['short_url'];
                    }
                    $res = $this->qrM->addOne($data);
                }else{
                    $res = false;
                }
            }catch (\Exception $e){
                Log::write("错误：".$e->getMessage());
                $res = false;
            }
            if($res){
                $this->success('保存成功');
            }else{
                $this->error('保存失败，请刷新重试', '', ['token' => request()->token()]);
            }
        }
        $builder = new FormBuilder();
        $builder->setTemplate('common/form')
            ->addFormItem('title', 'text', '场景名称', '不超过25字', [], 'required maxlength=50')
            ->addFormItem('keyword', 'text', '关键词', '扫描此二维码的同时触发此关键词，不超过40字', [], 'required maxlength=40')
            ->addFormItem('scene_str', 'text', '场景值', '场景值ID（字符串形式的ID），字符串类型，长度限制为1到64', [], 'required maxlength=64')
            ->addFormItem('type', 'toggle_radio', '类型', '类型', [0 => '永久', 1 => '临时'], 'required')
            ->addFormItem('expire', 'number', '有效时间/秒', '临时二维码过期时间, 最大为30天（2592000秒）', [], 'required max=2592000 min=1', 'type-item type-1 hide')
            ->setFormData(['type' => 0]);
        return $builder->show();
    }
}