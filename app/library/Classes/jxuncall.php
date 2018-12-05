<?php
namespace callApi\Classes;
/*
 * mixcall.class.php	电话控制类 
 *
 * @copyright			2007-2012
 * @lastmodify			2012-09-07
 */
include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpagi-asmanager.php');
class jxuncall extends AGI_AsteriskManager{
	//*****************************************核心方法********************************************************
	//socket连接
	function connect($server=NULL, $username=NULL, $secret=NULL){
		// get port from server if specified
		if(strpos($server, ':') !== false){
			$c = explode(':', $server);
			$this->server = $c[0];
			$this->port = $c[1];
		}else{
			$this->server = $server;
			$this->port = 6048;
		}
		// connect the socket
		$errno = $errstr = NULL;
		$this->port = 6048;
		$this->socket = @fsockopen($this->server, $this->port, $errno, $errstr);
		if($this->socket == false){
			$this->log("Unable to connect to manager {$this->server}:{$this->port} ($errno): $errstr");
			//socket 连接失败，程序自动终止
			exit("Unable to connect to manager {$this->server}:{$this->port} ($errno): ".iconv("gb2312", "UTF-8", $errstr));
			return false;
		}
		// read the header
		$str = fgets($this->socket);
		if($str == false){
			// a problem.
			$this->log("Asterisk Manager header not received.");
			return false;
		}else{
			// note: don't $this->log($str) until someone looks to see why it mangles the logging
		}
		// login
		if($this->port==6048){
			//$res = $this->send_request('login', array('Username'=>$username, 'Secret'=>$secret));
			$res = $this->send_request('login', array('Username'=>$username, 'Secret'=>$secret, 'Events'=>'off'));
			if(!isset($res['Response']) || $res['Response'] != 'Success'){
				$this->_logged_in = FALSE;
				$this->log("Failed to login.");
				$this->disconnect();
				exit("Authentication refused");
				return false;
			}			
		}
		$this->_logged_in = TRUE;
		return true;
    }
	
	/*
	 * 根据分机号码获取分机所在通道的详细信息
	 * 
	 * $extension	分机号码
	 * return		array
	 * 返回当前分机通道详细信息的数组，当extension=6205，6205与7255通话时，返回结果如下
	 * 
		Array
		(
            [0] => SIP/6205-00001c2a
            [1] => from-internal
            [2] => 
            [3] => 1
            [4] => Up
            [5] => AppDial
            [6] => (Outgoing Line)
            [7] => 6205
            [8] => 
            [9] => 
            [10] => 3
            [11] => 308
            [12] => SIP/7205-00001c29
            [13] => 1383095359.8099
		)
	 */
	function getCalleridDetail($extension){
		$channelArr=array();//当前分机通道，函数返回值
		$hint=$this->getHint($extension);//分机的hint，如：SIP/7205
		$res=$this->Command("core show channels concise");
		$resArr=explode("\n", $res['data']);
		foreach($resArr as $row){
			if(strpos($row, $hint.'-')!==false && strpos($row, $hint.'-')===0){
				$channelArr=explode('!', trim($row));
				break;
			}
		}
		return $channelArr;
	}
	
	/*
	 * 根据分机号码获取与之通话的另一通道详细信息
	 * 
	 * $extension	分机号码
	 * return		array
	 * 返回与当前分机通话的另一通道详细信息的数组，当extension=6205，6205与7255通话时，返回结果如下
	 * 
		Array
		(
            [0] => SIP/7205-00001c29
            [1] => macro-dial-one
            [2] => s
            [3] => 37
            [4] => Up
            [5] => Dial
            [6] => SIP/6205,"",tTrwW
            [7] => 7205
            [8] => 
            [9] => 
            [10] => 3
            [11] => 308
            [12] => SIP/6205-00001c2a
            [13] => 1383095359.8098
		)
	 */
	function getCalleeidDetail($extension){
		$lastChannel=array();
		$allChannelArr=$this->getAllChannel($extension);
		if(count($allChannelArr)>1){
			$lastChannel=array_pop($allChannelArr);	
		}
		return $lastChannel;
	}
	
	/*
	 * 获取分机原始通道
	 * 
	 * $extension	分机号码
	 * return		string
	 * 返回分机的设备类型，如：SIP/6205
	 */
	function getHint($extension){
		/*
		$res=$this->Command("database get AMPUSER $extension/device");
		$res=$res['data'];
		if(strpos($res, 'Database entry not found')===false){
			$device=trim(substr($res, strrpos($res, ":")+1));
			$res=$this->Command("database get DEVICE $device/dial");
			$res=$res['data'];
			if(strpos($res, 'Database entry not found')===false){
				$hint=trim(substr($res, strrpos($res, ":")+1));
			}
		}
		*/
		/*
		Response: Success
		Message: Extension Status
		Exten: 9005
		Context: from-internal
		Hint: SIP/1005
		Status: 0
		*/
		$res=$this->send_request('extensionstate', array('exten'=>$extension,'context'=>'from-internal'));
		$hintArr=explode('&', $res['Hint']);
		$hint=$hintArr[0];
		return $hint;	
	}
	
	/**
	* xml转数组方法
	* 
	* @param xml $xml xml格式字符串
	*/
	function XML2Array($xml){
		$newArray = array () ;
		if ( !is_array($xml) )$xml = simplexml_load_string ( $xml );
		foreach ( $xml as $key => $value ){
			$value = ( array ) $value ;
			if ( isset ( $value [ 0 ] ) && ! is_object ( $value [ 0 ] ) ){
				$newArray [ $key ] = trim ( $value [ 0 ] ) ;
			}else{
				$newArray [ $key ] = $this->XML2Array($value) ;
			}
		}
		return $newArray ;
	}
	
	//获取单个分机状态
	function extenStatus($exten=''){
		$res=$this->Command("database get DND $exten");
		$res=$res['data'];
		if(strpos($res, 'Database entry not found')===false){
			$DND=$this->getStr($res, 'Value: ');
		}else{
			$DND='-1';
		}
		//查询分机通话状
		$res=$this->send_request('extensionstate', array('exten'=>$exten,'context'=>'from-internal'));
		return array('exten'=>$exten, 'status'=>$res['Status'], 'dnd'=>$DND, 'hint'=>$res['Hint']);
	}
	
	/*
	 * 获取所有内部振铃分机，不包含外呼振铃
	 * 
	 * return array
	 */
	function getRingExten(){
		$ringExten=array();
		$res=$this->Command("core show channels concise");
		$channelArr=explode("\n", $res['data']);
		foreach($channelArr as $row){
			if(strpos($row, '!Ringing!')!==false && substr($row, 0, 6)!='Local/'){
				$tmpArr=explode('!', $row);
				$ringExten[]=$tmpArr[2];
			}
		}
		//过滤外线振铃号码
		foreach($ringExten as $k=>$v){
			$res=$this->Command("database get AMPUSER $v/cidnum");
			$res=$res['data'];
			if(strpos($res, 'Database entry not found')!==false){
				unset($ringExten[$k]);
			}
		}
		return $ringExten;
	}

	/*
	 * 将振铃分机转换成抢接字符串
	 */
	function getRingExtenPickUp($arr=array()){
		if($arr){
			$ringExten=$arr;
		}else{
			$ringExten=$this->getRingExten();
		}
		foreach($ringExten as $k=>$v){
			$ringExten[$k]=$v;
			$ringExten[$k].='&'.$v.'@from-pstn';//解决sip中继对接通过from-pstn打到分机上无法抢接的问题
			$ringExten[$k].='&'.$v.'@ext-local';
			$ringExten[$k].='&'.$v.'@from-internal';
			$ringExten[$k].='&'.$v.'@from-internal-xfer';
			$ringExten[$k].='&'.$v.'@from-did-direct';
			$ringExten[$k].='&LC-'.$v.'@from-internal';
			$ringExten[$k].='&LC-'.$v.'@from-internal-xfer';
		}
		return implode('&', $ringExten);
	}
	
	/*
	 * 获取放音、IVR、队列列表
	 * 
	 * return	array
	 */
	function appList($file="interface_additional.conf"){
		$arr=$this->getConfig($file);
		$ivr=array();
		if(isset($arr['ivr'])){
			foreach($arr['ivr'] as $k=>$v){
				$ivr[]=array('name'=>$v, 'context'=>'ivr-'.$k, 'exten'=>'s', 'priority'=>'1');
			}
		}
		$ann=array();
		if(isset($arr['announcement'])){
			foreach($arr['announcement'] as $k=>$v){
				$ann[]=array('name'=>$v, 'context'=>'app-announcement-'.$k, 'exten'=>'s', 'priority'=>'1');
			}
		}
		$queue=array();
		if(isset($arr['queue'])){
			foreach($arr['queue'] as $k=>$v){
				$queue[]=array('name'=>$v, 'context'=>'ext-queues', 'exten'=>$k, 'priority'=>'1');
			}
		}
		return array('ivr'=>$ivr, 'ann'=>$ann, 'queue'=>$queue);
	}
	
	/*
	 * 查找分机所在通道上面的通道变量
	 * 
	 * $extension	分机号码
	 * $variable	通道变量名称
	 * return	string	通道变量值
	 */
	function mixGetVar($extension='', $variable=''){
		$channelArr=$this->getCalleridDetail($extension);
		$channel=isset($channelArr[0])?$channelArr[0]:'';
		if($channel){
			$res=$this->GetVar($channel, $variable);
			return isset($res['Value']) && $res['Value']?$res['Value']:'';
		}else{
			return '';
		}
	}
	
	/*
	 * 根据分机在分机通道或对方通道上面设置通道变量
	 * 
	 * $extension	分机号码
	 * $variable	通道变量名称值对，如：name=singhead
	 * $option		self在分机通道设置变量，peer在对方通道设置变量
	 * return	boolean
	 */
	function mixSetVar($extension='', $variable='', $option='self'){
		list($name, $value)=explode('=', $variable);
		if(is_numeric($extension)){
			$channelArr=$this->getCalleridDetail($extension);
			if($option=='self'){
				$channel=isset($channelArr[0])?$channelArr[0]:'';
			}else{
				$channel=isset($channelArr[12])?$channelArr[12]:'';
				if($channel=='(None)'){
					$res=$this->GetVar($channelArr[0], 'BRIDGEPEER');
					$channel=isset($res['Value']) && $res['Value']?$res['Value']:'';
				}
			}
		}else{
			list($family, $key)=explode('/', $extension);
			$channel=$this->Command("database get $family $key");
			$channel=$this->getStr($channel['data'], 'Value:');
		}
		if($channel){
			return $this->SetVar($channel, $name, $value);	
		}else{
			return false;	
		}		
	}	
	/*
	* @desc 获取INI格式文件内容
	* @param string $filename conf文件绝对路径
	* @param string $section INI节名，如：ivr,queue,announcement，如果为空则返回整个INI文件内容
	* @return 返回一数组 
	*/
	function getConfig($filename='', $section=''){
		$result=$this->send_request('getconfig', array('filename'=>$filename));//通过AMI命令获取INI格式文件内容
		unset($result['Response']);
		unset($result['ActionID']);
		unset($result['Server']);
		$myresult=array();//将AMI获取的数据转换成二维数组
		foreach($result as $key=>$value){
			if(substr($key,0,8)=='Category'){
				$myresult[$value]=array();
			}else{
				$mykey=array_keys($myresult);
				$mykey=$mykey[count($myresult)-1];//获取数组中最后一个键名
				$value=explode('=', $value);
				$value[1]=str_replace('"', '', str_replace("'", '', $value[1]));//去掉INI格式文件值的引号（单引号和双引号）
				$myresult[$mykey][$value[0]]=$value[1];
			}
		}
		if($section){
			return isset($myresult[$section])?$myresult[$section]:array();
		}else{
			return $myresult;
		}
	}
	
	/*
	 * 根据首尾字符串获取中间字符串
	 * 
	 * $str		原始字符串
	 * $startStr开始字符串
	 * $endStr	结束字符串
	 * return	string
	 * 返回原始字符串中开始字符串与结束字符串中间的内容
	 */
	function getStr($str, $startStr=" ", $endStr=""){		
		$startIndex=strpos($str, $startStr);
		$endIndex=$endStr?strpos($str, $endStr, $startIndex+1):strlen($str);
		return trim(substr($str, $startIndex+strlen($startStr), $endIndex-$startIndex-strlen($startStr)));
	}
	
	//*****************************************辅助方法********************************************************
	/*
	 * 获取队列信息
	 * 
	 * $queue		队列号码
	 */
	function queueinfo($queue){
		$res=$this->getConfig('interface_additional.conf', 'queue');
		$data=$this->Command('queue show');
		$data=explode("\n", $data['data']);
		$queueinfo=array();
		//$ringType=array('ringall'=>'全部振铃', 'roundrobin'=>'循环振铃', 'leastrecent'=>'最久未呼叫', 'fewestcalls'=>'最少呼叫', 'random'=>'随机振铃', 'rrmemory'=>'记忆振铃');
		foreach($data as $v){
			//printr($v);echo '<hr>';
			if(trim($v) && $v{0}!=' ' && is_numeric($v{0})){
				//第一行数据，如：97           has 2 calls (max unlimited) in 'fewestcalls' strategy (0s holdtime), W:0, C:0, A:0, SL:0.0% within 15s
				$curqueue=trim(substr($v,0,strpos($v,'has')));//队列号码
				if((is_array($queue) && in_array($curqueue, $queue)) || !is_array($queue)){
					$queueinfo[$curqueue]['queue']=$curqueue;//队列号码
					$queueinfo[$curqueue]['name']=$res[$curqueue];//队列名称		
					$queueinfo[$curqueue]['ringtype']=$this->getStr($v, "in '", "' strategy");//振铃策略
					$queueinfo[$curqueue]['max']=$this->getStr($v, "(max", ") in");//队列最大等待人数
					$queueinfo[$curqueue]['wait']=$this->getStr($v, "has", "calls");//当前等待人数
					$queueinfo[$curqueue]['maxwaittime']='00:00';//当前最大等待时间
					$queueinfo[$curqueue]['maxwaittimetag']='0';//初始化没有人在等待
					$queueinfo[$curqueue]['holdtime']=$this->getStr($v, "strategy (", "s holdtime");//平均等待时间
					$queueinfo[$curqueue]['weight']=$this->getStr($v, "W:", ", C:");//队列优先级
					$queueinfo[$curqueue]['abandoned']=$this->getStr($v, "A:", ", SL:");//未接来电
					$queueinfo[$curqueue]['completed']=$this->getStr($v, "C:", ", A:");//已接来电
					$queueinfo[$curqueue]['servicelevel']=$this->getStr($v, "within", "s");//服务级别时间
					$queueinfo[$curqueue]['servicelevelperf']=$this->getStr($v, "SL:", "within");//服务级别时间内接通率
					$queueinfo[$curqueue]['agents']=array();
				}
			}elseif(strpos($v,'Local/')!==false && strpos($v,'@from-queue')!==false){
				//如：6205 (Local/6205@from-queue/n) (In use) has taken no calls yet
				if(isset($queueinfo[$curqueue])){
					$extension=$this->getStr($v, 'Local/', '@from-queue');//队列成员
					$paused='unpaused';
					if(strpos($v,'Local/'.$extension.'@from-queue')!==false && strpos($v,'(paused)')!==false){
						$paused='paused';
					}					
					$extensioninfo=$this->extenStatus($extension);
					$extenStatus=array('exten'=>$extensioninfo['exten'], 'dnd'=>$extensioninfo['dnd'], 'status'=>$extensioninfo['status'], 'paused'=>$paused);
					$queueinfo[$curqueue]['agents'][$extension]=$extenStatus;
				}
			}elseif(strpos($v,'(wait:')!==false && strpos($v,', prio')!==false){
				//如：1. SIP/6205-00000036 (wait: 4:41, prio: 0)
				if(isset($queueinfo[$curqueue])){
					$queueinfo[$curqueue]['maxwaittimetag']='1';
					$waittime=$this->getStr($v, "(wait:", ", prio");
					if(intval(str_replace(":", "", $waittime))>intval(str_replace(":", "", $queueinfo[$curqueue]['maxwaittime']))){
						$queueinfo[$curqueue]['maxwaittime']=strlen($waittime)==5?$waittime:'0'.$waittime;
					}
				}
			}
		}
		ksort($queueinfo);
		return $queueinfo;	
	}	
	/*
	 * 发送DTMF信号
	 * 
	 * $extension	分机号码
	 * $digits		要发送的数字
	 * $interval	发送时间间隔
	 */
	function senddtmf($extension, $digits, $interval=500){
		if(!$extension || !$digits)return false;
		$return=true;
		$dtmfChannel=$this->getCalleridDetail($extension);//根据分机找到与该分机通话的另一个通道
		$dtmfChannel=isset($dtmfChannel[12]) && $dtmfChannel[12]!='(None)'?$dtmfChannel[12]:'';
		if(!$dtmfChannel)return false;
		for($i=0; $i<strlen($digits); $i++){
			$digit=$digits{$i};
			$msg=$this->send_request('playdtmf', array('channel'=>$dtmfChannel,'digit'=>$digit));
			if(!(isset($msg['Response']) && $msg['Response']=='Success')){
				$return=false;
			}
			usleep($interval*1000);
		}
		return $return;
	}
	/*
	 * 多方通话
	 * 
	 * $extension	分机号码
	 * $number		外线号码，接口实现时不分内外线号码，此处分开主要是让对接方更易理解
	 * $room		会议房间号，如果没有传递该参数，则系统自动创建
	 * $context		会议的context，需要在拨号方案文件中增加如下信息： 
	  				[interface-meetme]
					exten => _.,1,Noop(${EXTEN})
					exten => _.,n,set(room=${EXTEN})
					exten => _.,n,meetme(${room},pdMX)
					exten => _.,n,hangup
	 */
	function conference($extension, $number, $room='', $context='interface-meetme'){
		if(!$extension)return false;
		$channel=$this->getCalleridDetail($extension);
		if($channel){			
			if($channel[5]=='MeetMe' && $channel[1]==$context){
				$room=$channel[6];
			}else{
				$caller_channel=isset($channel[12]) && $channel[12]!='(None)'?$channel[12]:'';
				$callee_channel=isset($channel[0])?$channel[0]:'';
				if($caller_channel && $callee_channel){
					$this->Redirect($caller_channel,$callee_channel,$room,$context,'1');
				}	
			}
		}else{
			$number[]=$extension;
		}
		foreach($number as $exten){
			$this->Originate('local/'.$exten.'@from-internal',$room,$context,'1',null,null,null,$exten,null,null,true);
		}
		preg_match_all("/\d+/",$room,$mat);				//有的时候发现返回的房间号码是20140915001,pdMX
		if(!empty($mat)) $room=$mat[0][0];
		
		return $room;		
	}
	/**
	踢出参会成员
	$extension 参数为all时，结束会议
	**/
	function kickConference($room,$extension){
		$result = false;
		if(!$room) return false;
		if($extension=='all'){
			$res=$this->Command("meetme kick $room all");
			return true;
		}
		$res=$this->Command("meetme list $room concise");
		$res =$res['data'];
		$res = explode("\r\n",$res);
		$userid=-1;

		foreach($res as $key=>$val){
			$arr = array();
			$arr = explode("!",$val);
			
			if(count($arr)<10) continue;
			if($arr[1]==$extension){
				$userid=$arr[0];
				$this->Command("meetme kick $room $userid");		
				$result=true;
				break;
			} 
		}
		
		return $result;
	}
	/*
	 * 根据号码获取通道，返回DID
	 * 用于外线呼入时，获取主叫通道上面的DID通道变量
	 * 
	 * $num		主叫号码
	 */
	function getdid($num){
		if(!$num)return false;
		$did='';
		$res=$this->Command("core show channels concise");
		$channelArr=explode("\n", $res['data']);
		foreach($channelArr as $row){
			if(strpos($row, "!$num!")!==false && substr($row, 0, 6)!='Local/'){//排除 不存在$num且还有Local/(队列通道)
				if(strpos($row, "!macro-dialout-trunk!")===false){
					$tmpArr=explode('!', $row);
					if(strpos($tmpArr[0], "@from-")===false){//进一步排除通道可能性通道
						$channel=$tmpArr[0];
					}
				}
			}
		}
		if(isset($channel) && $channel){ //不写入循环中，不排除可能查找到多个通道而进行循环获取DID
			$res=$this->GetVar($channel,"DID");
			$did = isset($res['Value']) && $res['Value']?$res['Value']:'';
		}
		return $did;
	}
	/*
	 * 队列示忙/示闲
	 * 
	 * $extension	分机号码
	 * $queue		队列号码
	 * $paused		false示闲，true示忙
	 */
	function queuepause($extension, $queue, $paused='false'){
		if(!$extension)return false;
		$this->send_request("queuepause", array('queue'=>$queue, 'interface'=>'Local/'.$extension.'@from-queue/n', 'paused'=>$paused));
		return true;
	}
	/**
	 * 根据分机号码获取与分机通话的号码
	 * @param $exten
	 * @return unknown_type
	 */
	function getNumByExten($exten){
		$allChannelArr=$this->getAllChannel($exten);
		$lastChannel=array_pop($allChannelArr);
		$number=(isset($lastChannel[7]) && $lastChannel[7]!='(None)' && $lastChannel[7]!=$exten)?$lastChannel[7]:'';
		return $number;		
	}
	/**
	* 根据分机号码获致与分机相关的所有通道
	* $exten	分机号码
	*/
	function getAllChannel($exten){
		$allChannelArr=array();//与分机相关的所有通道，从起始通道到最终通道
		$hint=$this->getHint($exten);//分机的hint，如：SIP/7205
		if($hint){
			$channelArr=array();
			$res=$this->Command("core show channels concise");
			$resArr=explode("\n", $res['data']);//通道数组
			foreach($resArr as $row){
				if(strpos($row, $hint.'-')!==false && strpos($row, $hint.'-')===0){
					$channelArr=explode('!', trim($row));
					break;
				}
			}
			$channel=isset($channelArr[0])?$channelArr[0]:'';
			$allChannelArr=$this->getLinkChannel($resArr, $channel);
		}
		return $allChannelArr;
	}
	/**
	 * 根据起始通道查询所有通道
	 * @param $resArr			通道数据数组
	 * @param $channel			当前通道
	 * @param $allChannel		所有通道
	 * @return unknown_type
	 * Array
	(
	    [SIP/1006-00000044] => Array
	        (
	            [0] => SIP/1006-00000044
	            [1] => from-internal
	            [2] => 
	            [3] => 1
	            [4] => Up
	            [5] => AppDial
	            [6] => (Outgoing Line)
	            [7] => 9006
	            [8] => 
	            [9] => 
	            [10] => 3
	            [11] => 312
	            [12] => SIP/trunk6-00000043
	            [13] => 1381993514.381
	        )	
	    [SIP/trunk6-00000043] => Array
	        (
	            [0] => SIP/trunk6-00000043
	            [1] => macro-dial-one
	            [2] => s
	            [3] => 37
	            [4] => Up
	            [5] => Dial
	            [6] => SIP/1006,"",tTrwW
	            [7] => 2006112231
	            [8] => 
	            [9] => 
	            [10] => 3
	            [11] => 312
	            [12] => SIP/1006-00000044
	            [13] => 1381993514.380
	        )	
	)
	 */
	function getLinkChannel($resArr, $channel, $allChannel=array()){
		if($channel=='')return $allChannel;
		//如果通道不在通道数组中
		if(!array_key_exists($channel, $allChannel)){
			$channelArr=array();//通道详细信息
			foreach($resArr as $row){
				if(strpos($row, $channel)!==false && strpos($row, $channel)===0){
					$channelArr=explode('!', $row);
					break;						
				}
			}
			$allChannel[$channel]=$channelArr;			
			$extChannel=$channelArr[12];//桥接通道
			//如果桥接通道不为空
			if($extChannel && $extChannel!='(None)'){
				$allChannel=$this->getLinkChannel($resArr, $extChannel, $allChannel);	
			}
		}else{
			//出现环路，特殊处理（如SIP/1006-00000044桥接通道为Local/9005@from-internal-xfer-0000009a;1，
			//则需要将Local/9005@from-internal-xfer-0000009a;1修改为Local/9005@from-internal-xfer-0000009a;2
			//继续查找桥接通道）
			$channelArr=$allChannel[$channel];
			$extChannel=$channelArr[12];
			if(strpos($extChannel, ';')!==false){				
				$arr=explode(';', $extChannel);
				if($arr[1]=='1'){
					$arr[1]='2';
				}else{
					$arr[1]='1';
				}	
				$extChannel=implode(';', $arr);				
				$allChannel=$this->getLinkChannel($resArr, $extChannel, $allChannel);
			}			
		}
		return $allChannel;
	}
}
