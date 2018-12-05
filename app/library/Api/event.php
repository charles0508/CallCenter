<?php
namespace callApi\Api;
/**
 *
 //接收事件信息并进行转发
 //Cdr数据
 Array
 (
 [Event] => Cdr
 [Privilege] => call,all
 [AccountCode] => autocall-1
 [Source] => 13800138000
 [Destination] => 6205
 [DestinationContext] => from-did-direct
 [CallerID] => "autocall-1" <13800138000>
 [Channel] => SIP/888-089adb78
 [DestinationChannel] => SIP/6205-089037f8
 [LastApplication] => Dial
 [LastData] => SIP/6205//tTrwW
 [StartTime] => 2011-04-06 20:18:15
 [AnswerTime] =>
 [EndTime] => 2011-04-06 20:18:35
 [Duration] => 20
 [BillableSeconds] => 0
 [Disposition] => NO ANSWER
 [AMAFlags] => DOCUMENTATION
 [UniqueID] => 1302092295.624
 [UserField] => IN6205-13800138000-20110406-201835-1302092295.624.WAV
 )
 */
class event {
	const TIMEOUT_LIMIT = 6;//超时时间，秒
	const DURATION_FILTER = 2;//指定无效记录的过虑时长，秒
	public $configFile;
	public $logFile;
	//mixevent配置
	public $conf = "";
	//comm通用配置
	public $commconf = "";
	public function __construct(){
		$this->configFile=CONFIG_PATH.'config.php';
		$this->logFile=MODULE_PATH.'include/event_log.txt';
	}

	/**
	 * 通过POST方式发送数据给多个接口
	 * @return void
	 */
	public function index($data){
		$data=new_stripslashes($data);
		$conf=include($this->configFile);
		$this->conf = $conf['mixevent'];
		$this->commconf = $conf['common'];
		$islog=$conf['mixevent']['islog'];
		$mixeventurl=explode('|', $conf['mixevent']['mixeventurl']);
		$autocallurl=trim($conf['mixevent']['autocallurl']);
		if(isset($data['Event'])){
			if($data['Event']=='Cdr'){
				//处理Cdr数据
				if($autocallurl)$this->_asynExecUrl(explode('|', $autocallurl), $data, $conf['mixevent']['method']);
				$data = $this->getCdrCallType($data);//处理呼叫类型
				$data = $this->filterInvalidData($data, 'ALL');//数据处理
				$data = $this->preModifyData($data);//数据处理
				$data = $this->advModifyData($data);//数据处理	
			}
			$this->_asynExecUrl($mixeventurl, $data, $conf['mixevent']['method']);//转发数据
		}
		if($islog)$this->log($data);//写日志
	}

	//打印数组
	function printr($arr){
		echo '<pre>';
		print_r($arr);
		echo '</pre>';
	}

	/**
	 * 写日志
	 * @param mixed $data 日志信息
	 * @return void
	 */
	public function log($data){
		exec("mycmd -s chmod 777 {$this->logFile}",$out,$status);
		$type = "a+";
		$updateTime = filemtime($this->logFile);
		if(date('Y-m-d',time())!=date('Y-m-d',$updateTime)){
			$type = "w";
		}
		$handle = fopen($this->logFile, $type);
		flock($handle, LOCK_EX);
		fwrite($handle, "[".date("Y-m-d H:i:s")."]\n".var_export($data,true)."\n\n");
		flock($handle, LOCK_UN);
		fclose($handle);
	}

	/**
	 * 异步执行多个接口
	 * @param array $urlArray 接口地址
	 * @param array $data 接口参数信息
	 * @param boolean $debug 是否打印返回信息
	 * @return void
	 */
	private function _asynExecUrl($urlArray, $data, $method = 'post'){
		//过滤空地址
		foreach ($urlArray AS $k=>$v){
			if(!$v)unset($urlArray[$k]);
		}
		$urlArray=array_values($urlArray);
		set_time_limit(90);
		$node_count = count($urlArray);
		$curl_arr = array();
		$master = curl_multi_init();
		//要发送的数据
		$str = "";
		$tempdata = $data; //保存data数组
		foreach ($data AS $k=>$v){
			$str .= $k."=".urlencode($v)."&";
		}
		$data = substr($str, 0, -1);
		//循环接口地址数组
		for($i = 0; $i < $node_count; $i++){
			$url = $urlArray[$i];
			//			$querystr = parse_url($url,PHP_URL_QUERY); //获取url中查询之后串
			//			parse_str($querystr);//将查询字符串赋值，m=agent&c=api&a=vmail&event=cdr , $event = 'cdr'
			//			if(isset($eventname) && strtolower($tempdata['Event']) != 'userevent' && stripos($eventname,$tempdata['Event']) === false || isset($eventname)&& isset($usereventname) && strtolower($tempdata['Event']) == 'userevent' && isset($tempdata['UserEvent']) && stripos($usereventname, $tempdata['UserEvent']) === false){
			//				break;
			//			}
			// $urlarr = parse_url($url); //获取url中查询之后串
			$urlarr = explode('?',$url);
			$temp_url_arr = array();
			if(isset($urlarr[1])){
				$queryarr = explode('&', $urlarr[1]);
				foreach ($queryarr as $value){
					$v_arr = explode('=', $value);
					$k = $v_arr[0];
					$v = isset($v_arr[1])?$v_arr[1]:'';
					$temp_url_arr[$k] = $v;
				}
			}
			if(isset($temp_url_arr['event']) && strtolower($tempdata['Event']) != 'userevent' && stripos($temp_url_arr['event'],$tempdata['Event']) === false ||
			isset($temp_url_arr['event']) && stripos($temp_url_arr['event'],$tempdata['Event']) === false ||
			isset($temp_url_arr['event'])&& isset($temp_url_arr['userevent']) && strtolower($tempdata['Event']) == 'userevent' && isset($tempdata['UserEvent']) && stripos($temp_url_arr['userevent'], $tempdata['UserEvent']) === false){
				continue;
			}
			unset($temp_url_arr['event']);
			unset($temp_url_arr['userevent']);
			$query_str = '';
			foreach ($temp_url_arr as $key => $value){
				$query_str .= "$key=$value&";
			}
			$query_str = substr($query_str, 0,-1);
			$url = '';
			$url .= $urlarr[0];
			$url .= isset($urlarr['1'])?"?".$urlarr[1]:'';
			if($method=='post'){
				$curl_arr[$i] = curl_init($url);
				curl_setopt($curl_arr[$i], CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl_arr[$i],CURLOPT_TIMEOUT,self::TIMEOUT_LIMIT);//超时
				curl_setopt($curl_arr[$i],CURLOPT_POST,1);
				curl_setopt($curl_arr[$i],CURLOPT_POSTFIELDS,$data);
				curl_multi_add_handle($master, $curl_arr[$i]);
			}else{
				$url.=strpos($url, '?')===false?'?':'&';
				$url.=$data;
				$curl_arr[$i] = curl_init($url);
				curl_setopt($curl_arr[$i], CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl_arr[$i],CURLOPT_TIMEOUT,self::TIMEOUT_LIMIT);//超时
				curl_multi_add_handle($master, $curl_arr[$i]);
			}
		}
		do{
			curl_multi_exec($master,$running);
		}while($running > 0);
		/*
		if(0){
			for($i = 0; $i < $node_count; $i++){
				$results = curl_multi_getcontent  ( $curl_arr[$i]  );
				echo( "result" . $i .": ". $results . "<br/>");
			}
		}
		*/
	}

	/**
	 * @param array $data CDR 请求数据
	 * @return array
	 * @author myp
	 */
	public function getCdrCallType($data){
		if(strpos($data['UserField'], '_')!==false){
			$tmpArr = explode('_', $data['UserField']);
			if(isset($tmpArr[0]) && strtolower($tmpArr[0])=='internal'){
				//内部通话
				$data['CallType']='0';
			}elseif(isset($tmpArr[0]) && strtolower($tmpArr[0])=='in'){
				//外线呼入
				$data['CallType']='1';
			}elseif(isset($tmpArr[0]) && strtolower($tmpArr[0])=='out'){
				//内线呼出
				$data['CallType']='2';
			}else{
				//其它
				$data['CallType']='3';
			}
			$data['UserField']=isset($tmpArr[1])?$tmpArr[1]:'';
		}else{
			$data['CallType']='3';
		}
		return $data;
	}

	/**
	 * 过滤无效数据
	 * 过滤 IVR 、 队列   与  转接、挂断 、停靠等数据 
	 * @param array $data CDR 请求数据 
	 * @param string $mode 过滤选项  PRIMARY | ADVANCED | ALL
	 * @return void | array
	 * @author jhj
	 */
	public function filterInvalidData($data,$mode = ''){
		if($mode == 'PRIMARY' || $mode == 'ALL'){  //初步过滤 ，考虑到后面保留原始数据问题  TODO 如果数据过滤正确考虑删
		if((($data['Disposition']=='NO ANSWER' OR $data['Disposition']=='BUSY') && $data['Duration']<=self::DURATION_FILTER && strstr($data['Channel'],'from-queue'))
		OR ($data['Source']=='' && $data['Destination']=='s' && !strstr($data['DestinationContext'],'ivr'))){
			die("PRIMARY Filter data");
		}
		if($data['Source'] == '' && $data['CallerID'] == '' && $data['DestinationChannel'] == ''){ //该数据占用很大一部分而且无效
			die("PRIMARY Filter data");
		}
		}
		//AppDial、Queue、Park、ParkedCall 过滤队列 、保持、抢接
		//ivr- 、app-announcement- IVR 流程数据
		// Pickup 过滤拦截失败
		if($mode == 'ADVANCED'  || $mode == 'ALL'){
			if(($data['LastApplication'] == 'AppDial' OR $data['LastApplication'] == 'Queue' OR $data['LastApplication'] == 'Park' OR $data['LastApplication'] == 'ParkedCall') || ($data['Destination'] == 'hangup' OR strstr($data['Destination'],'SIP'))
			|| (strstr($data['DestinationContext'],'ivr-') OR strstr($data['DestinationContext'],'app-announcement-')) || ($data['LastApplication'] == 'Pickup' && $data['Disposition'] == 'NO ANSWER' && $data['Destination'] == 's') ){
				die(" ADVANCED Filter data");
			}
			if(strpos($data['Destination'],'*') === 0){//过滤*78 *79 *0 等  第一个字符中含有*号字符
				die("ADVANCED Filter data , Filter *");
			}
			if($data['Destination'] == 's' && $data['DestinationChannel'] == ''){//过滤Destination为s，DestinationChannel为空数据 TODO lastapp=Transferred Call 数据会受一定影响(该数据本版本过滤，6万数据中16条)
			die("ADVANCED Filter data , Filter Destination=s  DestinationChannel=null");
			}
		}
		return $data;
	}

	/**
	 * 预处理
	 * 判断是否为呼出，如果呼出设置出局号码  将截取主叫通道中的分机号码替换 Source ， 与获取座席名称
	 * @param array $data CDR 请求数据 
	 * @return Array
	 * @author jhj
	 */
	public function preModifyData($data){
		$arr = $this->conf;
		if($arr['ampextensions'] == 'deviceanduser'){//为移动座席
			$commarr = $this->commconf;
			$astman = new AGI_AsteriskManager;
			if($commarr['runmode']=='local'){
				$amphost='localhost';
			}else{
				$amphost = isset($commarr['cchost']) && $commarr['cchost']? $commarr['cchost']:'127.0.0.1';
			}
			if (!$astman->connect($amphost, $commarr['ccuser'] , $commarr['ccpassword'])) { //TODO 这里需要配置用户名、密码、服务器地址
				if($arr['islog']) $this->log("[error]: can't connect Mixcore");
			}
			//替换主叫通道里面的设备号码为分机号码
			$data['Channel'] = $this->chanConvert($data['Channel'], $astman);
			//替换被叫通道里面的设备号码为分机号码
			$data['DestinationChannel'] = $this->chanConvert($data['DestinationChannel'], $astman);
			//替换source设备号码为分机号码
			$exten=$this->getExtenByDevice($data['Source'], $astman);
			$data['Source']=$exten?$exten:$data['Source'];
		}	
		//当前判断为 Source 与 CallerID 是否相等 且大于 3 位 (DestinationContext不等于 from-internal-xfer 排除转接影响的数据)，认为 设置了中继线号码呼出 ,如果中继号码设置"61865551" <61865551>将无法判断  
		//或者 Source 为空时， 且主叫通道为SIP值的
		$tempexten = preg_match("'<([0-9]*)>'", $data['CallerID'],$matches)?$matches[1]:'';
		if((($data['Source'] == $data['CallerID'] && strlen($data['Destination'])>=3) && $data['DestinationContext'] != 'from-internal-xfer') || (empty($data['Source']) && strstr($data['Channel'],'SIP'))){
			if(strstr($data['Channel'],'Local') || strstr($data['Channel'],'SIP')){
				$tmpChannel = explode("/", str_replace(array("@","-"), "/", $data['Channel']));
				$tmpExten['Exten'] = isset($tmpChannel[1])?$tmpChannel[1]:'';
				if(is_numeric($tmpExten['Exten']) && $tmpExten['Exten'] != $data['Destination']){ // 过滤可能不是电话号码的情况,   排除 截取的通道号码等于被叫号码情况(一号通可以产生)
					$data['Source'] = $tmpExten['Exten'];
				}
			}
		}
		//修正时间
		if($data['Disposition'] == 'BUSY' || $data['Disposition'] == 'NO ANSWER'){
			$data['BillableSeconds'] = 0;
		}
		return $data;
	}

	/**
	 * CDR数据修正
	 * 修正转接、电话会议号码
	 * @param array $data CDR 请求数据
	 * @return Array
	 * @author jhj
	 */
	public function advModifyData($data){
		//修正转接数据
		//如果LastApplication = Transferred Call
		//拿取LastApplication(数据存在一下可能性Local/7106@from-internal,SIP/6104-09ca3be8,Zap/2-1) 中的Local与sip中的分机号码 
		//替换Destination中的s为分机号码，DestinationChannel(如果该字段存在并且为SIP的，将SIP/6109-083a9298 替换成SIP/7106-083a9298)中的分机号码，其他不改变
		if($data['LastApplication'] == 'Transferred Call'){
			if(strstr($data['LastData'],'Local')){
				$temp = preg_match("'^Local/(.*)@'", $data['LastData'],$matches)?$matches[1]:'';
			}
			if(strstr($data['LastData'],'SIP')){
				$temp = preg_match("'^SIP/(.*)-'", $data['LastData'],$matches)?$matches[1]:'';
			}
			if(is_numeric($temp)){ //判断是否为数字，防止Local/@from-internal-xfer-6016 这种数据截取到空值进行覆盖
				$data['Destination'] = $temp;
				if(strstr($data['DestinationChannel'],'SIP')){ //判断是否有SIP 更改SIP/6109-083a9298 为SIP/7106-083a9298
					$pattern = "/(SIP\/)(.*)(-.*)/i";
					$replacement = "\${1}$temp\$3";
					$data['DestinationChannel'] = preg_replace($pattern, $replacement, $data['DestinationChannel']);
				}
			}
		}
		//修正会议
		if($data['Destination'] == 'STARTMEETME'){ // 300|cMr|  截取  300
			preg_match("'^(\d*)(\D*)(.*)'", $data['LastData'],$matches);
			$data['Destination'] = $matches[1];
		}
		//修正一号通号码
		//		if(strstr($data['Destination'],'FM')){
		//			preg_match("'^FM(\D*)(\d*)'", $data['Destination'],$matches)?$data['Destination']=$matches[2]:'';
		//		}
		//修正外呼数据
		if(strstr($data['CallerID'],'autocall')){
			$tempArray = explode('-', $data['CallerID']);
			$tempArr = explode('"',$tempArray[2]);
			$data['Source'] = $tempArr[0];
		}
		//对LastApplication=Congestion  Disposition=ANSWERED数据进行修正
		if($data['LastApplication']=='Congestion' && $data['Disposition']=='ANSWERED' ){
			$data['Disposition'] = 'FAILED';
			$data['billsec'] = 0;
		}
		//对于Destination最终还存在s 数据，修改被叫通道，主叫通道 ，进行保留存入数据库中方便后期观察
		if($data['Destination'] == 's'){
			$data['DestinationChannel'] = "s-".$data['DestinationChannel'];
			$data['Channel'] = "s-".$data['Channel'];
		}
		return $data;
	}

	/**
	 * 获取指定通道信息
	 *
	 * @param $chan		通道
	 * @param $astman
	 * @return string $chan
	 */
	function chanConvert($chan, $astman){
		if(preg_match("'SIP/([0-9]+)-'", $chan, $mats)){
			$device = $mats[1];
			$data = $astman->command("database get DEVICE/$device user");
			$data = $data['data'];
			if(preg_match("'Value: ([0-9]*)'", $data, $mats)){
				$chan=str_replace($device, $mats[1], $chan);
			}
		}
		return $chan;
	}

	/**
	 * 根据设备号码获取分机号码
	 * $deivce 设备号码
	 */
	private function getExtenByDevice($deivce, $astman){
		$data=$astman->command("database get DEVICE/$deivce user");
		$data=$data['data'];
		if(preg_match("'Value: ([0-9]*)'", $data, $mats)){
			$data=$mats[1];
		}else{
			$data='';
		}
		return $data;
	}
	
	/**
	 * 事件转发日志
	 */
	public function event_log(){
		if($_POST){
			exec("mycmd -s chmod 777 {$this->logFile}",$out,$status);
			$fp = fopen($this->logFile, 'w') or exit("Can not open file {$this->logFile}!");
			flock($fp, LOCK_EX);
			$len = fwrite($fp, '');
			flock($fp, LOCK_UN);
			fclose($fp);
			$opInfo = "操作成功";
			header("refresh:2; url=");//按指定的时间刷新页面
		}else{
			$opInfo = '';
		}
		$logs = '';
		$handle = fopen($this->logFile, 'r');
		while (!feof($handle)){
			$line = htmlspecialchars(fgets($handle));
			$logs .= $line;
		}		
		include admin_tpl('event_log');
	}	
}
