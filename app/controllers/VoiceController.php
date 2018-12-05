<?php
namespace callApi\Controllers;
use callApi\Gsm\parseIniFile;
use callApi\Common\ResultCommon as common;
use callApi\Models\Jxunsys\Notice as notice;
use callApi\Api\task;
/**
 * Display the default index page.
 */
class VoiceController extends ControllerBase
{
	/*
     * 通讯能力接口
     */
    protected $soundpath;
    protected $format;
    protected $callerid;
    public    $noticeModel;

	public function onConstruct()
    {
    	$this->common = new common();
    	$conf=$this->config['common'];
        $this->callerid = '075523426092';
        $this->noticeModel = new notice();//mixcall 外呼任务表
        $this->soundpath = '/var/lib/asterisk/sounds/custom/';
        $this->format = 'wav';
    }

    /**
     * 上传文件
     */
    public function uploadFileAction(){
        $postData  = file_get_contents("php://input");
        $param     = json_decode($postData, true);
        $content   = isset($param['content']) ?$param['content'] : $this->common->returnCore('10000','content');
        $filename  = isset($param['filename'])?$param['filename']: $this->common->returnCore('10000','filename');
        $realpath  = $this->soundpath.$filename.'.'.$this->format;
        if(file_exists($realpath)){
            $this->common->returnCore('40001');
        }
        $res = file_put_contents($realpath, urldecode($filecontent));
        if($res){
            $this->common->returnCore('200');
        }else{
            $this->common->returnCore('40002');
        }
    }

    /**
     * 语音告警-文件
     */
    /**
     * 语音告警
     */
    public function voiceAlarmFileAction(){
        $postData  = file_get_contents("php://input");
        $param     = json_decode($postData, true);
        $phone     = isset($param['phone']) ?$param['phone']  : $this->common->returnCore('10000','phone');
        $params    = isset($param['params'])?$param['params'] : $this->common->returnCore('10000','params');
        $ActionID  = isset($param['ActionID']) ?$param['ActionID']  : '';  //通话标识
        $pt        = isset($param['pt']) && is_numeric($param['pt'])? $param['pt']  : 2;  //播报次数
        $wt        = isset($param['wt']) && is_numeric($param['wt'])? $param['wt']  : 2;  //等待时间（s）
        is_numeric($phone) ? true : $this->common->returnCore('10002', 'phone');
        self::isJson($params) ? $params : $this->common->returnCore('10002', 'params');
        $db = $this->di->getShared('jxunsys');
        $info = array(
            'phone'=>$phone,
            'params'=>$params,
            'ActionID'=>$ActionID,
            'pt'=>$pt,
            'wt'=>$wt,
            'reqtime'=>date('Y-m-d H:i:s'),
	        'status'=>0,
	        'type'=>1
        );
        // $id = $this->noticeModel->insert($info);
        $bool = $db->insert(
		    "notice",
		    array_values($data),  // 顺序对应字段的值数组，不能含空字符串
		    array_keys($data)     // 字段数组
		);
        $info['Context'] = 'voice-alarm-file';
        $info['callerid'] = $this->callerid;
        $info['id'] = $id;
        $task = new task();//mixcall 外呼任务表
        $res = $task->init($info);
        if($res){
            //处理成功
            $this->common->returnCore('200', '', $ActionID);
        }else{
            //处理失败，缺少参数
            $this->common->returnCore('40003');
        }
    }

    /**
     * 语音告警-tts
     */
    public function voiceAlarmTTSAction(){
        $postData  = file_get_contents("php://input");
        $param     = json_decode($postData, true);
        $phone     = isset($param['phone']) ?$param['phone']  : $this->common->returnCore('10000','phone');
        $params    = isset($param['params'])?$param['params'] : $this->common->returnCore('10000','params');
        $ActionID  = isset($param['ActionID']) ?$param['ActionID']  : '';  //通话标识
        $pt        = isset($param['pt']) && is_numeric($param['pt'])? $param['pt']  : 2;  //播报次数
        $wt        = isset($param['wt']) && is_numeric($param['wt'])? $param['wt']  : 2;  //等待时间（s）
        is_numeric($phone) ? true : $this->common->returnCore('10002', 'phone');
        self::isJson($params) ? $params : $this->common->returnCore('10002', 'params');
        $info = array(
            'phone'=>$phone,
            'params'=>$params,
            'ActionID'=>$ActionID,
            'pt'=>$pt,
            'wt'=>$wt,
            'reqtime'=>date('Y-m-d H:i:s')
        );
        // $id = $this->noticeModel->insert($info, true);
        $bool = $db->insert(
		    "notice",
		    array_values($data),  // 顺序对应字段的值数组，不能含空字符串
		    array_keys($data)     // 字段数组
		);
        $info['Context'] = 'mix-notice-tts';
        $info['callerid'] = $this->callerid;
        $info['id'] = $id;
        $task = new task();//mixcall 外呼任务表
        $res = $task->init($info);
        if($res){
            //处理成功
            $this->common->returnCore('200', '', $ActionID);
        }else{
            //处理失败，缺少参数
            $this->common->returnCore('40003');
        }
    }

    /**
     * 语音告警-自定义流程
     */
    public function voiceAlarmDefineAction(){
        $postData  = file_get_contents("php://input");
        $param     = json_decode($postData, true);
        $phone     = isset($param['phone']) ?$param['phone']  : $this->common->returnCore('10000','phone');
        $pt        = isset($param['pt']) && is_numeric($param['pt'])? $param['pt']  : 2;  //播报次数
        $wt        = isset($param['wt']) && is_numeric($param['wt'])? $param['wt']  : 2;  //等待时间（s）
        $ActionID  = isset($param['ActionID']) ?$param['ActionID']  : '';  //通话标识
        is_numeric($phone) ? true : $this->common->returnCore('10002', 'phone');
        $info = array(
            'phone'=>$phone,
            'ActionID'=>$ActionID,
            'pt'  => $pt,
            'wt'  => $wt,
            'reqtime'=>date('Y-m-d H:i:s')
        );
        // $id = $this->noticeModel->insert($info, true);
        $bool = $db->insert(
		    "notice",
		    array_values($data),  // 顺序对应字段的值数组，不能含空字符串
		    array_keys($data)     // 字段数组
		);
        $info['Context'] = 'voice-alarm-define';
        $info['callerid'] = $this->callerid;
        $info['id'] = $id;
        $task = new task();//mixcall 外呼任务表
        $res = $task->init($info);
        if($res){
            //处理成功
            $this->common->returnCore('200', '', $ActionID);
        }else{
            //处理失败，缺少参数
            $this->common->returnCore('40003');
        }
    }

    /**
     * 创建文件目录
     */
    public function mk_dir($dir, $mode = 0755) { 
        if (is_dir($dir) || @mkdir($dir,$mode)) return true; 
        if (!$this->mk_dir(dirname($dir),$mode)) return false; 
        return @mkdir($dir,$mode); 
    } 
}
	
