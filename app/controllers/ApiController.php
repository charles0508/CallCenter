<?php
namespace callApi\Controllers;
use callApi\Common\ResultCommon as common;
use callApi\Classes\jxuncall;
use callApi\Api\download;
use callApi\Api\main;
use Phalcon\Mvc\View;
use callApi\Api\event;

class ApiController extends ControllerBase
{
	private $call_config;
	private $common;
	private $jxun;
	private $main;
	/*
	 * 接口返回为数组，如：array('result'=>1, 'data'=>'...', error=>0)，
	 * 其中result为1时，表示操作成功，此时可以根据data里面接收结果，error保存错误码。
	 */
	public function onConstruct()
    {
		$this->common = new common();
		$this->jxun = new jxuncall();
		$this->call_config = $this->common->call_config;
		$conf = $this->call_config['common'];
		// print_r($conf['cchost_php'].':'.$conf['ccport']."/n");
		// print_r($conf['ccuser'].':'.$conf['ccpassword']);exit;
		$this->jxun->connect($conf['cchost_php'].':'.$conf['ccport'], $conf['ccuser'], $conf['ccpassword']);
		$this->main = new main($this->jxun);
	}
	/***************下面为事件JS回调接口**********************/
	//加载接口
	public function command(){
		$interfaceurl=urlencode(WEB_URL.'api/?action=command_conf');
		include admin_tpl('command_js');
	}
	//配置信息
	public function command_conf(){
		$this->main->command_conf();
	}
	/***************下面为事件转发接口**********************/
	//事件转发--事件接口入口
	public function eventAction(){
		$event=new event();
		$event->index($_REQUEST);
	}
	/*
	 * 事件转发--事件日志
	 */
	public function event_logAction(){
		$event=new event();
		$event->event_log();
	}
	//弹屏演示
	public function apiPopDemoAction(){
		$extension = isset($_REQUEST['extension'])?$_REQUEST['extension']:'';
		$config=$this->call_config;
		$this->view->setVar('extension', $extension);
	}
	
	
	/***************下面为电话控制接口**********************/
	//加载电话控制接口 LEVEL_MAIN_LAYOUT模板渲染到显示到主控制器
	public function controlAction(){
		$call_conf=$this->call_config;
		$call_conf['jxuncontrol']['ajaxUrl'] = WEB_URL;
		$this->view->setVar('mixcontrol', $call_conf['mixcontrol']);
		$this->view->disableLevel(View::LEVEL_MAIN_LAYOUT);
	}
	//演示页面
	public function apiPanelAction(){

	}
//==================================================常用接口==========================================================

	//查询呼叫时长
	public function GetCallTimeAction(){
		$postData  = file_get_contents("php://input");
		$param     = json_decode($postData, true);
		$ExtenNo   = isset($param['ExtenNo'])?$param['ExtenNo']:$this->common->returnCore('10000','ExtenNo');
		$DstNo     = isset($param['DstNo'])?$param['DstNo']:$this->common->returnCore('10000','DstNo');

		is_numeric($ExtenNo) ? true : $this->common->returnCore('10002', 'ExtenNo');
		is_numeric($DstNo)   ? true : $this->common->returnCore('10002', 'DstNo');

		pc_base::load_app_class('cdr','',0);
		$cdrModel = new cdr();//mixcall 外呼任务表
		$where = 'CallerNo="'.$ExtenNo.'" AND CalledNo="'.$DstNo.'"';
		$data = $cdrModel->get_one($where, 'CallDate,CallerNo,CalledNo,TotalTime,RingTime,PickTime,PickStatus,RecordFileName', '', 'CallDate DESC');
		$this->common->returnCore('200', '', $data);
	}
	//电话呼出
	public function CallOutAction(){
		$postData  = file_get_contents("php://input");
		$param     = json_decode($postData, true);
		$ExtenNo   = isset($param['ExtenNo'])?$param['ExtenNo']:$this->common->returnCore('10000','ExtenNo');
		$DstNo     = isset($param['DstNo'])?$param['DstNo']:$this->common->returnCore('10000','DstNo');
		$ActionID  = isset($param['ActionID'])?$param['ActionID']:'';
		is_numeric($ExtenNo) ? true : $this->common->returnCore('10002', 'ExtenNo');
		is_numeric($DstNo)   ? true : $this->common->returnCore('10002', 'DstNo');

		$channel = trim($ExtenNo);
		$context = 'from-internal';
		$priority = '1';//priority
		$application = null;
		$data = null;
		$timeout = null;
		$callerid = null;
		$variable = null;
		$account = $ActionID;
		$async = 1;
		$actionid = null;
		//将分机转换成分机通道，如6205转换为SIP/6205
		$channel=$this->jxun->getHint($channel); //SIP/8001
		if(!$callerid)$callerid=substr($channel, strpos($channel, '/')+1);//8001
		$result = $this->jxun->Originate($channel,$exten,$context,$priority,$application,$data,$timeout,$callerid,$variable,$account,$async,$actionid);
		// if(isset($result['Response']) && $result['Response'] == 'Error'){
		// 	$this->common->returnCore('10002', $result['Message']);
		// }else{
			
		// }
		$this->common->returnCore('200');
	}

	//电话挂断
	public function HangUpAction(){
		$postData  = file_get_contents("php://input");
		$param     = json_decode($postData, true);
		$ExtenNo   = isset($param['ExtenNo']) ? $param['ExtenNo'] : $this->common->returnCore('10000','ExtenNo');
		is_numeric($ExtenNo) ? true : $this->common->returnCore('10002', 'ExtenNo');
		$channel=$this->jxun->getCalleridDetail($ExtenNo);
		if($channel){
			$this->jxun->Hangup($channel[0]);
			$this->common->returnCore('200');
		}else{
			$this->common->returnCore('10003');
		}
	}
	// 判断分机是否创建
	public function CheckNoCreate($extension){
		$res = $this->jxun->send_request('extensionstate', array('exten'=>$extension, 'context'=>'from-internal'));
		$res = $res['Status'];
		if($res == -1){
			$this->common->returnCore($this->response, '10004', $extension);
			$this->response;
			return true;
		}else{
			return false;
		}
	}
	//电话转接
	public function CallTransferAction(){
		$postData  = file_get_contents("php://input");
		$param     = json_decode($postData, true);
		$ExtenNo   = isset($param['ExtenNo'])?$param['ExtenNo']:$this->common->returnCore('10000','ExtenNo');
		$DstNo     = isset($param['DstNo'])?$param['DstNo']:$this->common->returnCore('10000','DstNo');
		is_numeric($ExtenNo) ? true : $this->common->returnCore('10002', 'ExtenNo');
		is_numeric($DstNo)   ? true : $this->common->returnCore('10002', 'DstNo');

		$extension     = $ExtenNo;
		$context       = 'from-internal';
		$extensionDst  = $DstNo;
		$exten         = $extensionDst;//exten
		$priority      = '1';//priority
		$channel=$this->jxun->getCalleridDetail($extension);
		$channel=isset($channel[0])?$channel[0]:null;	
		if($channel){
			//转接至分机
			$context='from-internal-xfer';
			$this->jxun->send_request('atxfer', array('channel'=>$channel, 'exten'=>$exten, 'context'=>$context, 'priority'=>$priority));
			$this->common->returnCore('200');
		}else{
			$this->common->returnCore('10003');
		}
	}

	//弹窗接口
	public function PopWindowAction(){
		$ExtenNo  = isset($_REQUEST['ExtenNo'])?$_REQUEST['ExtenNo']:$this->common->returnCore('10000','ExtenNo');
		$OpenType = isset($_REQUEST['OpenType'])?$_REQUEST['OpenType']:'2';

		is_numeric($ExtenNo) ? true : $this->common->returnCore('10002', 'ExtenNo');
		$extension = $ExtenNo;
		$config    = $this->config;
		$mode = 'on';
		$this->view->setVar('call_config', $call_config);
		$this->view->setVar('extension', $extension);

		$this->view->disableLevel(View::LEVEL_MAIN_LAYOUT);
	}

	//座席签入/签出
	public function SignInAndOutAction(){
		$postData  = file_get_contents("php://input");
		$param     = json_decode($postData, true);
		$ExtenNo   = isset($param['ExtenNo'])?$param['ExtenNo']:$this->common->returnCore('10000','ExtenNo');
		$SignType  = isset($param['SignType'])?$param['SignType']:$this->common->returnCore('10000','SignType');

		is_numeric($ExtenNo)  ? true : $this->common->returnCore('10002', 'ExtenNo');
		is_numeric($SignType) ? true : $this->common->returnCore('10002', 'SignType');

		$extension = $ExtenNo;
		$dnd       = $SignType;
		$resMsg    = '操作成功';
		if($dnd=='-1'){
			//示闲
			$this->jxun->Command("database del DND $extension");
			$this->jxun->queuepause($extension, '', 'false');
		}else{
			//示忙
			$this->jxun->Command("database put DND $extension $dnd");
			$this->jxun->queuepause($extension, '', 'true');
		}
		$this->jxun->send_request('UserEvent', array('UserEvent'=>'MixDND','DND'=>$dnd, 'Exten'=>$extension));
		$this->common->returnCore('200','',$resMsg);
	}

	//获取签入/签出状态
	public function CheckSignStatusAction(){
		$postData  = file_get_contents("php://input");
		$param     = json_decode($postData, true);
		$ExtenNo   = isset($param['ExtenNo'])?$param['ExtenNo']:$this->common->returnCore('10000','ExtenNo');
		is_numeric($ExtenNo) ? true : $this->common->returnCore('10002', 'ExtenNo');

		$extension  = $ExtenNo;//获取示忙状态的分机号码
		$data=$this->jxun->Command("database get DND $extension");
		if(strpos($data['data'],'Database entry not found')===false){
			$res=trim(substr(strstr($data['data'], 'Value:'), 7));
		}else{
			$res='-1';
		}
		$this->common->returnCore('200', '', $res);
	}

	//获取座席状态
	public function CheckStatusAction(){
		$postData  = file_get_contents("php://input");
		$param     = json_decode($postData, true);
		$ExtenNo   = isset($param['ExtenNo'])?$param['ExtenNo']:$this->common->returnCore('10000','ExtenNo');
		is_numeric($ExtenNo) ? true : $this->common->returnCore('10002', 'ExtenNo');
		$extension=$ExtenNo;
		$res=$this->jxun->send_request('extensionstate', array('exten'=>$extension,'context'=>'from-internal'));
		$res=$res['Status'];
		//data为0表示空闲， -1表示不存在，1表示通话中，2表示忙，4表示未注册，8表示振铃中，16表示保持中
		$retuValue = array();
		switch($res){
			case '0':
				$retuValue['state'] = "0";
				$retuValue['desc'] = "空闲";
				break;
			case '-1':
				$retuValue['state'] = "-1";
				$retuValue['desc'] = "未创建";
				break;
			case '1':
				$retuValue['state'] = "1";
				$retuValue['desc'] = '通话中';
				break;
			case '2':
				$retuValue['state'] = "2";
				$retuValue['desc'] = '正忙';
				break;
			case '4':
				$retuValue['state'] = "4";
				$retuValue['desc'] = '未注册';
				break;
			case '8':
				$retuValue['state'] = "8";
				$retuValue['desc'] = '振铃中';
				break;
			case '16':
				$retuValue['state'] = "16";
				$retuValue['desc'] = '暂停中';
				break;
		}
		$this->common->returnCore('200', '', $retuValue);
	}

	//电话抢接
	public function SnatchAction(){
		$postData    = file_get_contents("php://input");
		$param       = json_decode($postData, true);
		$ExtenNo     = isset($param['ExtenNo'])?$param['ExtenNo']:$this->common->returnCore('10000','ExtenNo');
		$ExtenNoDest = isset($param['ExtenNoDest'])?$param['ExtenNoDest']:$this->common->returnCore('10000','ExtenNoDest');
		
		is_numeric($ExtenNo) ? true : $this->common->returnCore('10002', 'ExtenNo');
		is_numeric($ExtenNoDest) ? true : $this->common->returnCore('10002', 'ExtenNoDest');

		$extension   = $ExtenNo; //抢听座席
		$extensionDst=$ExtenNoDest;//被抢座席

		$hint=$this->jxun->getHint($extension);
		if($hint){
			$dst=$this->jxun->getRingExtenPickUp(array($extensionDst));
			$this->jxun->Originate($hint,null,null,null,'Pickup',$dst,null,$extension);
			$this->common->returnCore('200');
		}else{
			$this->common->returnCore('10004');
		}
	}

	//监听、密语
	public function TapPhoneAction(){
		$postData    = file_get_contents("php://input");
		$param       = json_decode($postData, true);
		$ExtenNo     = isset($param['ExtenNo'])?$param['ExtenNo']:$this->common->returnCore('10000','ExtenNo');
		$DstNo = isset($param['DstNo'])?$param['DstNo']:$this->common->returnCore('10000','DstNo');
		$Action      = isset($param['Action'])?$param['Action']:$this->common->returnCore('10000','Action');

		is_numeric($ExtenNo) ? true : $this->common->returnCore('10002', 'ExtenNo');
		is_numeric($DstNo) ? true : $this->common->returnCore('10002', 'DstNo');
		is_numeric($Action) ? true : $this->common->returnCore('10002', 'Action');
		$opt = $Action == 0 ? 'b' : $Action == 1 ? 'w' :  $this->common->returnCore('10002', 'Action');

		$extension     =  $ExtenNo;  //监听的分机号码
		$extensionDst  =  $DstNo;    //被监听的分机号码
		$option        =  $opt;//b为监控，w为密语
		$hint     =  $this->jxun->getHint($extension);//监听分机通道
		$spyHint  =  $this->jxun->getHint($extensionDst);//被监听分机通道
		if($hint && $spyHint){
			$this->jxun->Originate($hint,null,null,null,'ChanSpy',$spyHint.'-,'.$option,null,$extension);
			$this->common->returnCore('200');
		}else{
			$this->common->returnCore('10004');
		}
	}

	//通话保持/恢复
	public function HoldAndRecoverAction(){
		$postData    = file_get_contents("php://input");
		$param       = json_decode($postData, true);
		$ExtenNo     = isset($param['ExtenNo'])?$param['ExtenNo']:$this->common->returnCore('10000','ExtenNo');

		is_numeric($ExtenNo) ? true : $this->common->returnCore('10002', 'ExtenNo');
		$extension=$ExtenNo;//要进行通话保持/恢复的分机号码

		if(!$extension)return $this->output($res);
		$channel=$this->jxun->getCalleridDetail($extension);
		if(!$channel)return $this->common->returnCore('10003');
		if($channel[12]!='(None)'){
			$DIALSTATUS=$this->jxun->GetVar($channel[0], 'DIALSTATUS');
			if($DIALSTATUS['Value']){
				//$channel[0](分机)为主叫通道
				$data=$this->jxun->GetVar($channel[0], 'CDR(dst)');
				$this->jxun->Command("database put AMPUSER $extension/hold {$channel[12]},{$channel[0]}");
			}else{
				//分机为被叫
				$data=$this->jxun->GetVar($channel[12], 'CDR(src)');
				$this->jxun->Command("database put AMPUSER $extension/hold {$channel[0]},{$channel[12]}");
			}			
			$exten=$data['Value'];
			$command=array(
				'Channel' => $channel[12],
				'ExtraChannel' => $channel[0],
				'Exten' => $extension,
				'ExtraExten' => $exten,
				'Context' => 'interface-musiconhold',
				'ExtraContext' => 'interface-musiconhold',
				'Priority' => '1',
				'ExtraPriority' => '1',
			);			
			$this->jxun->send_request('Redirect', $command);
			$this->common->returnCore('200');
		}elseif($channel[12]=='(None)'){
			$data=$this->jxun->Command("database get AMPUSER $extension/hold");
			$data=$data['data'];
			if(strpos($data, 'Database entry not found')===false){
				$channelArr=explode(',', trim(substr($data, strpos($data, 'Value: ')+7)));
				if(in_array($channel[0], $channelArr)){
					$channel1=$channel2=false;
					$data=$this->jxun->Command("core show channels concise");
					$data=explode("\n", $data['data']);
					foreach($data as $row){
						if(strpos($row, $channelArr[0])!==false && strpos($row, $channelArr[0])===0){
							$channel1=true;
						}
						if(strpos($row, $channelArr[1])!==false && strpos($row, $channelArr[1])===0){
							$channel2=true;
						}
					}
					if($channel1 && $channel2){
						$command=array(
							'Channel1' => $channelArr[0],
							'Channel2' => $channelArr[1],
							'Tone' => 'yes',
						);
						$this->jxun->Command("database del AMPUSER $extension/hold");
						$this->jxun->send_request('Bridge', $command);
						$res=true;
					}elseif($channel1 || $channel2){
						$channel=$channel1?$channelArr[0]:$channelArr[1];
						$command=array(
							'Channel' => $channel,
							'Exten' => 4,
							'Context' => 'interface-musiconhold',
							'Priority' => '4',
						);
						$this->jxun->Command("database del AMPUSER $extension/hold");
						$this->jxun->send_request('Redirect', $command);
						$res=true;				
					}
				}
			}
			$this->common->returnCore('200');
		}
	}
	/*
	 * 录音播放接口
	 */
	public function PlayRecordingAction(){
		$file = isset($_GET['RecordFileName']) && !empty($_GET['RecordFileName'])?$_GET['RecordFileName']:$this->common->returnCore('10000','RecordFileName');
		$arr=explode('_', $file);
                $dirarr=explode('/', $file);
                $filename=substr($arr[0], -8, 4).'/'.substr($arr[0], -4, 4).'/'.$file;
                $dirarr[count($dirarr)-1]=substr($arr[0], -8, 4).'/'.substr($arr[0], -4, 4).'/'.$dirarr[count($dirarr)-1];
                $filename=implode('/', $dirarr);
		$file = 'monitor/'.$filename;
		if(!file_exists($file)){
			$this->common->returnCore('10005');
		}
		$file = 'http://193.112.157.251/callApi/public/voicefile/2018/1129/voiceTest.wav'; //需要返回页面这样格式的数据
		// $file = "/data/www/default/callApi/public/voicefile/2018/1129/voiceTest.wav";
		// if(!file_exists($file)){
		// 	$common->error($this->response, '10005');
		// 	return $this->response;
		// }
		$this->view->setVar('file', $file);
	}
	/*
	 * 录音下载接口
	 */
	public function downRecordingAction(){
		$monitorDir       = isset($this->config['monitorDir']) &&!empty($this->config['monitorDir']) ? $this->config['monitorDir'] : "/var/spool/asterisk/monitor/";
		$this->monitorDir = $this->config['monitorDir'];
		$file             = isset($_GET['RecordFileName']) && !empty($_GET['RecordFileName'])?$_GET['RecordFileName']:$this->common->returnCore('10000','RecordFileName');
		$arr=explode('_', $file);
		$dirarr=explode('/', $file);
		$file=substr($arr[0], -8, 4).'/'.substr($arr[0], -4, 4).'/'.$file;
		$dirarr[count($dirarr)-1]=substr($arr[0], -8, 4).'/'.substr($arr[0], -4, 4).'/'.$dirarr[count($dirarr)-1];
		$file=implode('/', $dirarr);
		$file = $monitorDir.$file;
		if(!file_exists($file)){
			$this->common->returnCore('10005');
		}
		$download=pc_base::load_app_class('download', '', 0);
		new download($file);
	}

//===============================================不常用接口====================================================
	//根据分机号码获取分机所在通道上的通道变量
	public function getvarAction(){
		extract($_REQUEST);
		$extension=isset($extension)?$extension:'';//分机号码
		$variable=isset($variable)?$variable:'';//通道变量名
		$res=$this->jxun->jxunGetVar($extension, $variable);
		$this->output($res);
}
	//设置通道变量
	public function setvarAction(){
		extract($_REQUEST);
		$extension=isset($extension)?$extension:'';//分机号码
		$variable=isset($variable)?$variable:'';//通道变量名及值，如 name=jxuncall
		$option=isset($option)?$option:'';//设置方向，如self分机通道，peer对方通道
		$res=$this->jxun->jxunSetVar($extension, $variable, $option);
		$this->output($res);
	}      
	//根据主叫号码获取通道，返回通道变量DID，弹屏的时候由于jxunproxy返回返回DID，这里面主动获取一下，只有电话呼入的时候才调用。
	public function getdidAction(){
		extract($_REQUEST);
		$num=isset($num)?$num:'';//外线呼入时的主叫号码
		$res=$this->jxun->getdid($num);
		$this->output($res);
	}
	//根据分机号码获取与分机通话的号码
	public function getnumAction(){
		$res=false;
		extract($_REQUEST);
		$extension=isset($extension)?$extension:'';
		if($extension){
			$res=$this->jxun->getNumByExten($extension);
		}
		$this->output($res);
	} 

	//返回JSON数据
	public function output($str=''){
		$res=array('result'=>'1', 'data'=>'', 'error'=>'0');//返回结果标准格式
		if(is_bool($str)){
			//布尔值返回操作结果，true or false
			if($str===true){
					$res['result']='1';
			}elseif($str===false){
					$res['result']='0';
			}
		}elseif(is_array($str)){
			//数组，可以包含操作结果、返回值、错误码
			$res['data']=$str;
		}else{
			//字符串，为操作结果
			$res['data']=$str;
		}
		$res['error']=0;
		$json=json_encode($res);
		$callback=isset($_REQUEST['callback'])?$_REQUEST['callback']:'';
		if($callback){
			echo $callback.'('.$json.')';
		}else{
			echo $json;
		}
	}

	
	//编码转换
	function getTransferEncode($str){
		
		$defaultEncode=$this->call_config['jxuncontrol']['encode'];
		//为utf-8将不需要处理,不为utf-8将进行转换
		if($defaultEncode!='UTF-8'){
			$str=iconv('UTF-8',$defaultEncode,$str);
		}
		return $str;
	}
	
	//移动座席登入
	public function jxunUserLoginAction(){
		extract($_REQUEST);
		$user = isset($user)?$user:'';//分机号码
		$device = isset($device)?$device:'';//设备号码
		$re = array('op'=>'login', 'rev'=>"$user:$device", 'status'=>0, 'content'=>'');
		//验证分机号与设备号有效性
		if( preg_match('/^[0-9]+$/', $user, $mat) && preg_match('/^[0-9]+$/', $device, $mat2)){
			$userres = $this->jxun->Command("database get AMPUSER $user/recording");
			//分机号码不存在
			if(strpos($userres['data'], 'Database entry not found')!==false){
				$re['status'] = 2;
				$re['content'] = '分机号不存在';
				return $this->output($re);
			}
			$deviceres = $this->jxun->Command("database get DEVICE $device/dial");
			//设备号码不存在
			if(strpos($deviceres['data'],'Database entry not found')!==false){
				$re['status'] = 3;
				$re['content'] = '设备号不存在';
				return $this->output($re);
			}
			//设备存在,判断是否已经注册（软电话注册、语音网关注册、IP话机注册）
			$devicestatsres = $this->jxun->Command("sip show peer $device");
			if(!preg_match('/Status(\s)+:(\s)OK/',$devicestatsres['data'],$res) || preg_match('/Unspecified/',$devicestatsres['data'],$res2)){
				$re['status'] = 4;
				$re['content'] = '设备异常';
				return $this->output($re);
			}
			//根据分机找到分机已经登陆的设备
			$usertodevice = $this->jxun->Command("database get AMPUSER $user/device");
			//找到分机所对应的设备
			if(strpos($usertodevice['data'], 'Value: ')!==false){
				$tempdevice = trim(substr($usertodevice['data'], strpos($usertodevice['data'], 'Value: ')+7));
				if($tempdevice==$device){
					//分机与设备已经处于绑定状态，不需要再次登陆
					$re['status'] = 5;
					$re['content'] = '用户已登陆';
					return $this->output($re);
				}else{
					//解除分机与以前的设备绑定状态
					$this->jxun->Command("database put DEVICE  $tempdevice/user none");
				}
			}
			//根据设备找到设备已经登陆的分机
			$devicetouser = $this->jxun->Command("database get DEVICE $device/user");
			//找到设备对应的分机
			if(strpos($devicetouser['data'], 'Value: ')!==false){
				$exten = trim(substr($devicetouser['data'], strpos($devicetouser['data'], 'Value: ')+7));
				if($exten != 'none'){
					//说明设备号已经登陆,退出该设备已经登陆的分机
					$this->jxun->Command("database del AMPUSER $exten/device");					
					$this->jxun->Command("dialplan add extension $exten,hint,&Custom:DND$exten into ext-local replace ");
					$this->jxun->Command("devstate change Custom:DND$exten UNAVAILABLE");
					$this->jxun->send_request('UserEvent', array('UserEvent'=>'UserDeviceRemoved','Data'=>$exten.'|'.$device));
				}
			}
			$this->jxun->Command("database put AMPUSER $user/device $device ");
			$this->jxun->Command("database put DEVICE  $device/user $user ");
			$this->jxun->Command("dialplan add extension $user,hint,SIP/$device&Custom:DND$user into ext-local replace ");
			$this->jxun->Command("devstate change Custom:DND$user NOT_INUSE");
			$this->jxun->send_request('UserEvent', array('UserEvent'=>'UserDeviceAdded','Data'=>$user.'|'.$device));
			$re['status'] = 1;
			$re['content'] = '登陆成功';
			$this->output($re);
		}else{
			$re['status'] = 6;
			$re['content'] = '无效分机或无效设备';
			$this->output($re);
		}
	}
		
	/**
	 * 移动座席用户退出
	 * 
	 * @return
	 */
	public function jxunUserLogoutAction(){
		extract($_REQUEST);
		$user = isset($user)?$user:'';
		$re = array('op'=>'logout','rev'=>"$user",'status'=>0,'content'=>'');
		if(preg_match('/^[0-9]+$/',$user,$mat)){//验证用户号与设备号有效性
			$userres = $this->jxun->Command("database get AMPUSER $user/recording");
			//分机号码不存在
			if(strpos($userres['data'],'Database entry not found')!==false){
				$re['status'] = 2;
				$re['content'] = '分机号不存在';
				return $this->output($re);
			}
			$deviceres = $this->jxun->Command("database get AMPUSER $user/device");
			$device = $deviceres['data'];
			$device =trim(substr($device, strpos($device, 'Value: ')+7));
			if(strpos($deviceres['data'],'Database entry not found')!==false){
				$re['status'] = 3;
				$re['content'] = '用户已退出';
				return $this->output($re);
			}else{
				$this->jxun->Command("database del AMPUSER $user/device");
				$this->jxun->Command("database put DEVICE  $device/user none ");
				$this->jxun->Command("dialplan add extension $user,hint,&Custom:DND$user into ext-local replace ");
				$this->jxun->Command("devstate change Custom:DND$user UNAVAILABLE");
				$this->jxun->send_request('UserEvent', array('UserEvent'=>'UserDeviceRemoved','Data'=>$user.'|'.$device));
				$re['status'] = 1;
				$re['content'] = '退出成功';
				$this->output($re);
			}
		}else{
			$re['status'] = 4;
			$re['content'] = '无效分机或无效设备';
			$this->output($re);
		}
	}	
	
	/**
	支持通过html REQUEST方式上传录音，可以指定上传录音的目的地，仅支持上传wav格式录音
	return 
		11	文件类型错误
		12	上传错误
		13	文件已经存在
		14	文件含有中文字符
		16	上传成功
	**/
	public function uploadRecordAction(){
		$path = "/var/lib/asterisk/sounds/interfaceupload/";
		
		if(!is_dir($path)){
			mkdir($path);
			exec("mycmd -s chmod -R 777 $path");
		}
		
		$arr = $_FILES;
		foreach($arr as $key=>$val){
			if($val['name']) $res[] = $this->uploadFile($val, $path);
		}
		exec("mycmd -s chmod -R 777 ".$path);
		$this->output($res);
	}
	
	private function uploadFile($arr, $path){
		
		$type = "wav";
		$res = "16";
		
		$tmpName 	= $arr["tmp_name"];
		$name 		= $arr["name"];
		$error 		= $arr["error"];
	
		if(substr($name,strrpos($name,'.')+1)!=strtolower($type)){
			$res = '11';
		}else if($error>0){
			$res = '12'.$error;
		}else if(file_exists($path . $name)){
			$res = '13';
		}else if(preg_match("/[\x7f-\xff]/", $name)){
			$res = '14';
		}else{
			move_uploaded_file($tmpName,$path.$name);
		}
		return $res;
	}	
	/******************************app模块中的接口*********************************/
	/*
	 * 控制面板--路由转发
	 */
	public function panelAction(){
		$action=isset($_REQUEST['action']) && $_REQUEST['action']!='panel'?$_REQUEST['action']:'panel_conf';
		if (method_exists($this, $action)) {
			call_user_func(array($this, $action));
		}else{
			exit(json_encode(array("Code"=>404, "Message"=>"Method Not Exist !!!")));	
		}
	}
	//控制面板--初始化配置
	public function panel_confAction() {
		$this->main->panel_conf();
	}
	//获取控制面板信息
	public function panel_newAction(){
		$data=$this->jxun->appList();
		echo $this->output($data);
	}
	/*
	 * 队列监控--路由转发
	 */
	public function queuemonitorAction(){
		$action=isset($_REQUEST['action']) && $_REQUEST['action']!='queuemonitor'?$_REQUEST['action']:'queuemonitor_conf';
		if($action=='queueinfo'){
			$action='queuemonitor_info';
		}		
		if (method_exists($this, $action)) {
			call_user_func(array($this, $action));
		}else{
			exit(json_encode(array("Code"=>404, "Message"=>"Method Not Exist !!!")));	
		}		
	}
	/*
	 * 队列监控--初始化配置
	 */
	public function queuemonitor_confAction(){
		$this->main->queuemonitor_conf();
	}
	/*
	 * 队列监控--队列详细信息
	 */
	public function queuemonitor_infoAction(){
		$this->main->queuemonitor_info();
	}
	/*
	 * 座席监控--路由转发
	 */
	public function agentmonitorAction(){
		$action=isset($_REQUEST['action']) && $_REQUEST['action']!='agentmonitor'?$_REQUEST['action']:'agentmonitor_conf';
		if (method_exists($this, $action)) {
			call_user_func(array($this, $action));
		}else{
			exit(json_encode(array("Code"=>404, "Message"=>"Method Not Exist !!!")));	
		}		
	}
	/**
	坐席监控--带图像功能
	**/
	public function agentmonitor_imgAction(){
		$action=isset($_REQUEST['action']) && $_REQUEST['action']!='agentmonitor_img'?$_REQUEST['action']:'agentmonitor_img_conf';
		if (method_exists($this, $action)) {
			call_user_func(array($this, $action));
		}else{
			exit(json_encode(array("Code"=>404, "Message"=>"Method Not Exist !!!")));	
		}		
	}	
	/*
	 * 座席监控--初始化配置
	 */
	public function agentmonitor_img_confAction(){
		$this->main->agentmonitor_img();
	}	
	/*
	 * 座席监控--初始化配置
	 */
	public function agentmonitor_confAction(){
		$this->main->agentmonitor_conf();
	}
	/*
	 * 座席监控--获取分机列表
	 */
	public function extenlistAction(){
		$this->main->extenlist();
	}
	
	//动态执行类jxuncall及其父类里面的方法
	public function jxuncall_commandAction(){
		//http://localhost/01.interface/api/?action=jxuncall_command&method=Originate&variable=sip/7205,13424246447,from-internal,1,,,,7205
		//http://localhost/01.interface/api/?action=jxuncall_command&method=Originate&variable=SIP/MN_71/13424246447,7205,from-internal,1,,,,7205
		extract($_REQUEST);
		$method=isset($method)?$method:'';
		$variable=isset($variable)?explode(',', $variable):array();
		$var0=isset($variable[0])?$variable[0]:null;
		$var1=isset($variable[1])?$variable[1]:null;
		$var2=isset($variable[2])?$variable[2]:null;
		$var3=isset($variable[3])?$variable[3]:null;
		$var4=isset($variable[4])?$variable[4]:null;
		$var5=isset($variable[5])?$variable[5]:null;
		$var6=isset($variable[6])?$variable[6]:null;
		$var7=isset($variable[7])?$variable[7]:null;
		//$var8=isset($variable[8])?$variable[8]:null;
		//$var9=isset($variable[9])?$variable[9]:null;
		if(isset($_REQUEST['limittime']) && $_REQUEST['limittime']){
                        $calltime = 1000*(int)$_REQUEST['limittime'];
                        $var8 = 'CALLTIME='.$calltime;
                }else{
                        $var8=isset($variable[8])?$variable[8]:null;
                }
                if(isset($variable[9]) && isset($_REQUEST['user_data']) && $_REQUEST['user_data']){
                        $arr = explode('_', $variable[9]);
                        $var9 = $arr[2].'-'.$_REQUEST['user_data'];
                }else{
                        $var9=isset($variable[9])?$variable[9]:null;
                }
	
		$var10=isset($variable[10])?$variable[10]:null;
		if(method_exists($this->jxun, $method)){
			//return $jxun->$method($var0, $var1, $var2, $var3, $var4, $var5, $var6, $var7, $var8, $var9, $var10);
			echo json_encode($this->jxun->$method($var0, $var1, $var2, $var3, $var4, $var5, $var6, $var7, $var8, $var9, $var10));
		}else{
			exit(json_encode(array("Code"=>404, "Message"=>"Method Not Exist !!!")));	
		}
	}

}
