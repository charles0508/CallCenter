<?php
namespace callApi\Api;
use callApi\Common\ResultCommon as admin;
class main extends admin{
	public function __construct($mix){
		parent::__construct();
		$conf=$this->config['common'];
		$this->mix=$mix;				
	}
	/*
	 * command初始化配置
	 */
	public function command_conf(){		
		$conf=$this->config['common'];
		echo '<?xml version="1.0" encoding="utf-8"?>';
		echo '<config>';
		echo '<node cchost="'.$conf['cchost'].'" ccuser="'.$conf['ccuser'].'" ccpassword="'.$conf['ccpassword'].'" ccport="'.$conf['ccport'].'"/>';
		echo '</config>';
	}
	/*
	 * 控制面板初始化配置信息
	 */
	public function panel_conf() {
		$config=$this->config;
		$conf=$config['common'];
		$br="\n";
		echo '<?xml version="1.0" encoding="utf-8"?>'.$br;
		echo '<config>'.$br;
		$extension=isset($_REQUEST['extension'])?$_REQUEST['extension']:'';
		if($extension){
			$v=$this->mix->extenStatus($extension);
			if($v['status']=='1'){
				$arr=$this->mix->getCalleeidDetail($v['exten']);			
				$num=isset($arr[7])?$arr[7]:'';
				$time=isset($arr[11])?$arr[11]:'';
			}else{
				$num='';
				$time='';			
			}
			$str='exten="'.$v['exten'].'" hint="'.$v['hint'].'" status="'.$v['status'].'" dnd="'.$v['dnd'].'" num="'.$num.'" time="'.$time.'"';
		}else{
			$str='';
		}
		echo '<base>'.$br;
		echo '<node cchost="'.$conf['cchost'].'" ccuser="'.$conf['ccuser'].'" ccpassword="'.$conf['ccpassword'].'" ccport="'.$conf['ccport'].'" '.$str.'/>'.$br;
		echo '</base>'.$br;
		echo '<busytype>'.$br;
		if($config['mixpanel']['busytype']){
			$busytype=explode("\r\n", $config['mixpanel']['busytype']);
			foreach($busytype as $v){
				$arr=explode("|", $v);
					echo '<node name="'.$arr[1].'" value="'.$arr[0].'"/>'.$br;
		
			}		
		}
		echo '</busytype>'.$br;
		$appList=$this->mix->appList();
		foreach($appList as $k=>$v){
			echo "<$k>".$br;
			foreach($v as $row){
				echo '<node name="'.$row['name'].'" context="'.$row['context'].'" exten="'.$row['exten'].'" priority="'.$row['priority'].'"/>'.$br;
			}
			echo "</$k>".$br;
		}
		echo '</config>';	
	}
	/*
	 * 队列监控初始化配置
	 */
	public function queuemonitor_conf(){
		$config=$this->config;
		$conf=$config['common'];
		$res=$this->mix->getConfig('interface_additional.conf', 'queue');
		$data = $this->mix->Command("queue show");
		$data=explode("\n",$data['data']);
		$queueinfo=array();
		$ringType=array('ringall'=>'全部振铃', 'roundrobin'=>'循环振铃', 'leastrecent'=>'最久未呼叫', 'fewestcalls'=>'最少呼叫', 'random'=>'随机振铃', 'rrmemory'=>'记忆振铃');
		foreach($data as $v){
			//printr($v);echo '<hr>';
			if(trim($v) && $v{0}!=' ' && is_numeric($v{0})){
				//第一行数据，如：97           has 2 calls (max unlimited) in 'fewestcalls' strategy (0s holdtime), W:0, C:0, A:0, SL:0.0% within 15s
				$curqueue=trim(substr($v,0,strpos($v,'has')));//队列号码
				$queueinfo[$curqueue]['name']=$res[$curqueue];//队列名称
				$strategy=$this->mix->getStr($v, "in '", "' strategy");//振铃策略
				$queueinfo[$curqueue]['ringtype']=isset($ringType[$strategy])?$ringType[$strategy]:$strategy;
				$queueinfo[$curqueue]['max']=$this->mix->getStr($v, "(max", ") in");//队列最大等待人数
				$queueinfo[$curqueue]['wait']=$this->mix->getStr($v, "has", "calls");//当前等待人数
				$queueinfo[$curqueue]['maxwaittime']='00:00';//当前最大等待时间
				$queueinfo[$curqueue]['maxwaittimetag']='0';//初始化没有人在等待
				$queueinfo[$curqueue]['holdtime']=$this->mix->getStr($v, "strategy (", "s holdtime");//平均等待时间
				$queueinfo[$curqueue]['weight']=$this->mix->getStr($v, "W:", ", C:");//队列优先级
				$queueinfo[$curqueue]['abandoned']=$this->mix->getStr($v, "A:", ", SL:");//未接来电
				$queueinfo[$curqueue]['completed']=$this->mix->getStr($v, "C:", ", A:");//已接来电
				$queueinfo[$curqueue]['servicelevel']=$this->mix->getStr($v, "within", "s");//服务级别时间
				$queueinfo[$curqueue]['servicelevelperf']=$this->mix->getStr($v, "SL:", "within");//服务级别时间内接通率
				$queueinfo[$curqueue]['agents']=array();
			}elseif(strpos($v,'Local/')!==false && strpos($v,'@from-queue')!==false){
				//如：6205 (Local/6205@from-queue/n) (In use) has taken no calls yet
				$queueinfo[$curqueue]['agents'][]=$this->mix->getStr($v, 'Local/', '@from-queue');//队列成员
			}elseif(strpos($v,'(wait:')!==false && strpos($v,', prio')!==false){
				//如：1. SIP/6205-00000036 (wait: 4:41, prio: 0)
				$queueinfo[$curqueue]['maxwaittimetag']='1';
				$waittime=$this->mix->getStr($v, "(wait:", ", prio");
				if(intval(str_replace(":", "", $waittime))>intval(str_replace(":", "", $queueinfo[$curqueue]['maxwaittime']))){
					$queueinfo[$curqueue]['maxwaittime']=strlen($waittime)==5?$waittime:'0'.$waittime;
				}
			}
		}
		ksort($queueinfo);
		//printr($queueinfo);exit;
		$height=isset($_REQUEST['height'])?$_REQUEST['height']:'';
		$height=$height?$height:$config['mixagentmonitor']['height'];
		echo '<?xml version="1.0" encoding="utf-8"?>';
		echo '<config>';
		echo '<base>';
		echo '<node cchost="'.$conf['cchost'].'" ccuser="'.$conf['ccuser'].'" ccpassword="'.$conf['ccpassword'].'" ccport="'.$conf['ccport'].'"/>';
		echo '</base>';
		echo '<queue>';
		$sw=strlen(count($queueinfo));//编号宽度
		$i=1;
		foreach($queueinfo as $k=>$v){
			echo '<node id="'.sprintf("%0".$sw."d", $i).'" name="'.$v['name'].'" exten="'.$k.'" ringtype="'.$v['ringtype'].'" max="'.$v['max'].'" wait="'.$v['wait'].'" maxwaittime="'.$v['maxwaittime'].'" maxwaittimetag="'.$v['maxwaittimetag'].'" holdtime="'.$v['holdtime'].'" weight="'.$v['weight'].'" abandoned="'.$v['abandoned'].'" completed="'.$v['completed'].'" servicelevel="'.$v['servicelevel'].'" servicelevelperf="'.$v['servicelevelperf'].'" agents="'.implode(',', $v['agents']).'"/>';
			$i++;
		}
		echo '</queue>';
		echo '</config>';
	}
	/*
	 * 队列监控初始化配置
	 * 生成配置文件不同--备份用
	 */
	public function queuemonitor_conf2(){
		$config=$this->config;
		$conf=$config['common'];
		$res=$this->mix->getConfig('interface_additional.conf', 'queue');
		$data = $this->mix->Command("show queues");
		$data=explode("\n",$data['data']);
		$queueinfo=array();
		$exteninfo=array();
		$ringType=array('ringall'=>'全部振铃', 'roundrobin'=>'循环振铃', 'leastrecent'=>'最久未呼叫', 'fewestcalls'=>'最少呼叫', 'random'=>'随机振铃', 'rrmemory'=>'记忆振铃');
		foreach($data as $v){
			//printr($v);echo '<hr>';
			if(trim($v) && $v{0}!=' ' && is_numeric($v{0})){
				//第一行数据，如：97           has 2 calls (max unlimited) in 'fewestcalls' strategy (0s holdtime), W:0, C:0, A:0, SL:0.0% within 15s
				$curqueue=trim(substr($v,0,strpos($v,'has')));//队列号码
				$queueinfo[$curqueue]['name']=$res[$curqueue];//队列名称
				$strategy=$this->mix->getStr($v, "in '", "' strategy");//振铃策略
				$queueinfo[$curqueue]['ringtype']=isset($ringType[$strategy])?$ringType[$strategy]:$strategy;
				$queueinfo[$curqueue]['max']=$this->mix->getStr($v, "(max", ") in");//队列最大等待人数
				$queueinfo[$curqueue]['wait']=$this->mix->getStr($v, "has", "calls");//当前等待人数
				$queueinfo[$curqueue]['maxwaittime']='00:00';//当前最大等待时间
				$queueinfo[$curqueue]['maxwaittimetag']='0';//初始化没有人在等待
				$queueinfo[$curqueue]['holdtime']=$this->mix->getStr($v, "strategy (", "s holdtime");//平均等待时间
				$queueinfo[$curqueue]['weight']=$this->mix->getStr($v, "W:", ", C:");//队列优先级
				$queueinfo[$curqueue]['abandoned']=$this->mix->getStr($v, "A:", ", SL:");//未接来电
				$queueinfo[$curqueue]['completed']=$this->mix->getStr($v, "C:", ", A:");//已接来电
				$queueinfo[$curqueue]['servicelevel']=$this->mix->getStr($v, "within", "s");//服务级别时间
				$queueinfo[$curqueue]['servicelevelperf']=$this->mix->getStr($v, "SL:", "within");//服务级别时间内接通率
				$queueinfo[$curqueue]['agents']=array();
			}elseif(strpos($v,'Local/')!==false && strpos($v,'@from-queue')!==false){
				//如：6205 (Local/6205@from-queue/n) (In use) has taken no calls yet
				$exten=$this->mix->getStr($v, 'Local/', '@from-queue');//队列成员
				$queueinfo[$curqueue]['agents'][]=$exten;
				$exteninfo[$exten]=$exten;
			}elseif(strpos($v,'(wait:')!==false && strpos($v,', prio')!==false){
				//如：1. SIP/6205-00000036 (wait: 4:41, prio: 0)
				$queueinfo[$curqueue]['maxwaittimetag']='1';
				$waittime=$this->mix->getStr($v, "(wait:", ", prio");
				if(intval(str_replace(":", "", $waittime))>intval(str_replace(":", "", $queueinfo[$curqueue]['maxwaittime']))){
					$queueinfo[$curqueue]['maxwaittime']=strlen($waittime)==5?$waittime:'0'.$waittime;
				}
			}
		}
		foreach($exteninfo as $k=>$v){
			$extensioninfo=$this->mix->extenStatus($v);
			$extenStatus=array('exten'=>$extensioninfo['exten'], 'dnd'=>$extensioninfo['dnd'], 'status'=>$extensioninfo['status']);
			$exteninfo[$k]=$extenStatus;
		}
		ksort($exteninfo);
		//printr($exteninfo);exit;
		ksort($queueinfo);
		//printr($queueinfo);exit;
		$height=isset($_REQUEST['height'])?$_REQUEST['height']:'';
		$height=$height?$height:$config['mixagentmonitor']['height'];
		$rn="\r\n";
		echo '<?xml version="1.0" encoding="utf-8"?>'.$rn;
		echo '<config>'.$rn;
		echo '<base>'.$rn;
		echo '<node cchost="'.$conf['cchost'].'" ccuser="'.$conf['ccuser'].'" ccpassword="'.$conf['ccpassword'].'" ccport="'.$conf['ccport'].'" dnd="'.(str_replace("\r\n", ",", $config['mixpanel']['busytype'])).'"/>'.$rn;
		echo '</base>'.$rn;
		echo '<queue>'.$rn;
		$sw=strlen(count($queueinfo));//编号宽度
		$i=1;
		foreach($queueinfo as $k=>$v){
			echo '<node id="'.sprintf("%0".$sw."d", $i).'" name="'.$v['name'].'" exten="'.$k.'" ringtype="'.$v['ringtype'].'" max="'.$v['max'].'" wait="'.$v['wait'].'" maxwaittime="'.$v['maxwaittime'].'" maxwaittimetag="'.$v['maxwaittimetag'].'" holdtime="'.$v['holdtime'].'" weight="'.$v['weight'].'" abandoned="'.$v['abandoned'].'" completed="'.$v['completed'].'" servicelevel="'.$v['servicelevel'].'" servicelevelperf="'.$v['servicelevelperf'].'" agents="'.implode(',', $v['agents']).'"/>'.$rn;
			$i++;
		}
		echo '</queue>'.$rn;
		echo '<exten>'.$rn;
		foreach($exteninfo as $k=>$v){
			echo '<node exten="'.$v['exten'].'" dnd="'.$v['dnd'].'" status="'.$v['status'].'"/>'.$rn;
		}
		echo '</exten>'.$rn;
		echo '</config>'.$rn;
	}
	/*
	 * 点击某队列时获取具体队列信息
	 */
	public function queuemonitor_info(){
		$queue=isset($_REQUEST['queue'])?$_REQUEST['queue']:'';
		if($queue){
			echo '<?xml version="1.0" encoding="utf-8"?>';
			echo '<config>';
			$data = $this->mix->Command("queue show $queue");
			$data=explode("\n",$data['data']);
			$exten=array();
			foreach($data as $v){
				if(strpos($v, 'Local/')!==false && strpos($v, '@from-queue/n')!==false){
					$extension=$this->mix->getStr($v, 'Local/', '@from-queue');
					$extensioninfo=$this->mix->extenStatus($extension);
					$exten[$extensioninfo['exten']]=$extensioninfo;
				}
			}
			ksort($exten);
			foreach($exten as $v){
				echo '<node exten="'.$v['exten'].'" dnd="'.$v['dnd'].'" status="'.$v['status'].'"/>';
			}
			//printr($queueinfo);
			echo '</config>';
		}
	}
	/*
	 * 座席监控初始化配置信息
	 */
	public function agentmonitor_conf(){
		//$startTime=microtime_float();
		set_time_limit(0);
		$rn="\r\n";
		$config=$this->config;
		$conf=$config['common'];		
		echo '<?xml version="1.0" encoding="utf-8"?>'.$rn;
		echo '<config>'.$rn;
		$busytype=str_replace("\r\n", ';', $config['mixpanel']['busytype']);
		echo '<node cchost="'.$conf['cchost'].'" ccuser="'.$conf['ccuser'].'" ccpassword="'.$conf['ccpassword'].'" ccport="'.$conf['ccport'].'" busytype="'.$busytype.'"/>'.$rn;
		$username=isset($_REQUEST['username'])?$_REQUEST['username']:'';
		$exturl=isset($_REQUEST['exturl'])?$_REQUEST['exturl']:'';
		$url=$exturl?$exturl:$config['mixagentmonitor']['url'];
		if($url)$url.=strpos($url, '?')===false?'?':'&';
		$url=$url?$url.'username='.$username:'';
		//$url='http://localhost/066.mixcalld13/?m=agent&c=agent_monitor_config&a=init&username=singhead';
		//分机姓名列表 array('agents'=>array('8001'=>'张三', '8002'=>'李四'), 'auths'=>array('dndon'=>'1', 'dndoff'=>'1', ……))
		$xmlArr=$this->loadXml($url, array('dbhost'=>$conf['dbhost'], 'dbuser'=>$conf['dbuser'], 'dbpassword'=>$conf['dbpassword'], 'dbname'=>$conf['dbname']));
		$agents=$xmlArr['agents'];
		//分机示忙列表 array('8001'=>'1', '8002'=>'3');
		$dndlist=array();
		$res=$this->mix->Command("database show dnd");
		$res=explode("\n", $res['data']);
		foreach($res as $val){
			if(substr($val, 0, 5)=='/DND/'){
				list($exten, $dnd)=explode(':', trim($val, '/DND/'));
				$dndlist[trim($exten)]=trim($dnd);
			}			
		}
		$extenList=array();
		foreach($agents as $k=>$v){
			//查询分机示忙状态==================================================
			$DND=isset($dndlist[$k])?$dndlist[$k]:'-1';
			//查询分机通话状态==================================================
			$res=$this->mix->send_request('extensionstate', array('exten'=>$k,'context'=>'from-internal'));
			$resarr=explode('&', $res['Hint']);
			$hint=$resarr[0]?$resarr[0]:'';			
			$extenList[$k]=array('agent'=>$v['agent'], 'exten'=>$k, 'status'=>$res['Status'], 'hint'=>$hint, 'dnd'=>$DND);
		}
		ksort($extenList);
		echo '<agent>'.$rn;
		$i=1;
		foreach($extenList as $v){
			$num='';
			$time='';
			if($v['status']=='1'){
				$arr=$this->mix->getCalleeidDetail($v['exten']);
				$num=isset($arr[7])?(isset($agents[$arr[7]])?$arr[7].'('.$agents[$arr[7]].')':$arr[7]):'';
				$time=isset($arr[11])?$arr[11]:'';
			}
			echo '<node id="'.sprintf("%03d", $i).'" agent="'.$v['agent'].'" exten="'.$v['exten'].'" status="'.$v['status'].'" hint="'.$v['hint'].'" dnd="'.$v['dnd'].'" num="'.$num.'" time="'.$time.'"/>'.$rn;
			$i++;
		}
		echo '</agent>'.$rn;
		echo '<auth>'.$rn;
		foreach($xmlArr['auths'] as $k=>$v){
			echo '<node name="'.$k.'" value="'.$v.'"/>'.$rn;
		}
		echo '</auth>'.$rn;
		echo '</config>';
		//echo '执行时间：'.substr(microtime_float()-$startTime, 0, 6).' 秒<br>';
	}
	public function agentmonitor_img(){
		//$startTime=microtime_float();
		set_time_limit(0);
		$rn="\r\n";
		$config=$this->config;
		$conf=$config['common'];		
		echo '<?xml version="1.0" encoding="utf-8"?>'.$rn;
		echo '<config>'.$rn;
		$busytype=str_replace("\r\n", ';', $config['mixpanel']['busytype']);
		echo '<base>';
		echo $rn;
		echo '	<node cchost="'.$conf['cchost'].'" ccuser="'.$conf['ccuser'].'" ccpassword="'.$conf['ccpassword'].'" ccport="'.$conf['ccport'].'" busytype="'.$busytype.'"/>'.$rn;
		echo '</base>';
		echo $rn;
		echo '<dndType>'.$rn;
		
		foreach(explode("\r\n", $config['mixpanel']['busytype']) as $val){
			$dndType=array();
			$dndType=explode("|", $val);
			echo '	<node index="'.$dndType[0].'" name="'.$dndType[1].'"/>'.$rn;
		}
		/**
		echo '	<node index="1" name="下班"/>'.$rn;
		echo '	<node index="2" name="开会"/>'.$rn;
		echo '	<node index="3" name="休息"/>'.$rn;
		**/
		echo '</dndType>'.$rn;
		$username=isset($_REQUEST['username'])?$_REQUEST['username']:'';
		$exturl=isset($_REQUEST['exturl'])?$_REQUEST['exturl']:'';
		$url=$exturl?$exturl:$config['mixagentmonitor']['url'];
		if($url)$url.=strpos($url, '?')===false?'?':'&';
		$url=$url?$url.'username='.$username:'';
		//$url='http://localhost/066.mixcalld13/?m=agent&c=agent_monitor_config&a=init&username=singhead';
		//分机姓名列表 array('agents'=>array('8001'=>'张三', '8002'=>'李四'), 'auths'=>array('dndon'=>'1', 'dndoff'=>'1', ……))
		$xmlArr=$this->loadXml($url, array('dbhost'=>$conf['dbhost'], 'dbuser'=>$conf['dbuser'], 'dbpassword'=>$conf['dbpassword'], 'dbname'=>$conf['dbname']));
		$agents=$xmlArr['agents'];
		//分机示忙列表 array('8001'=>'1', '8002'=>'3');
		$dndlist=array();
		$res=$this->mix->Command("database show dnd");
		$res=explode("\n", $res['data']);
		foreach($res as $val){
			if(substr($val, 0, 5)=='/DND/'){
				list($exten, $dnd)=explode(':', trim($val, '/DND/'));
				$dndlist[trim($exten)]=trim($dnd);
			}			
		}
		$extenList=array();
		foreach($agents as $k=>$v){
			//查询分机示忙状态==================================================
			$DND=isset($dndlist[$k])?$dndlist[$k]:'-1';
			//查询分机通话状态==================================================
			$res=$this->mix->send_request('extensionstate', array('exten'=>$k,'context'=>'from-internal'));			
			$extenList[$k]=array('agent'=>$v['agent'], 'exten'=>$k, 'status'=>$res['Status'], 'hint'=>$res['Hint'], 'dnd'=>$DND, 'img'=>$v['img']);
		}
		ksort($extenList);
		echo '<agent>'.$rn;
		$i=1;
		foreach($extenList as $v){
			$num='';
			$time='';
			if($v['status']=='1'){
				$arr=$this->mix->getCalleeidDetail($v['exten']);
				$num=isset($arr[7])?(isset($agents[$arr[7]])?$arr[7].'('.$agents[$arr[7]].')':$arr[7]):'';
				$time=isset($arr[11])?$arr[11]:'';
			}
			echo '<node id="'.sprintf("%03d", $i).'" agent="'.$v['agent'].'" exten="'.$v['exten'].'" status="'.$v['status'].'" hint="'.$v['hint'].'" dnd="'.$v['dnd'].'" num="'.$num.'" time="'.$time.'" img="'.$v['img'].'"/>'.$rn;
			$i++;
		}
		echo '</agent>'.$rn;
		echo '<auth>'.$rn;
		foreach($xmlArr['auths'] as $k=>$v){
			echo '<node name="'.$k.'" value="'.$v.'"/>'.$rn;
		}
		echo '</auth>'.$rn;
		echo '</config>';
		//echo '执行时间：'.substr(microtime_float()-$startTime, 0, 6).' 秒<br>';
	}	
	//加载XML文件并以数组形式返回,如果没有指定XML文件地址则查询呼叫中心数据库
	function loadXml($file='', $conf=array('dbhost'=>'localhost', 'dbuser'=>'root', 'dbpassword'=>'passw0rd', 'dbname'=>'asterisk')){
		$agents=array();//座席列表数组，如：array('8001'=>'张三', '8002'=>'李四')
		$auths=array(
			'dndon' => '1',
			'dndoff' => '1',
			'dial' => '1',
			'transout' => '1',
			'transin' => '1',
			'hangup' => '1',
			'chanspyb' => '1',
			'chanspyw' => '1',
			'chanspyw2' => '1',
		);//权限列表数组，如：array('dndon'=>'0', 'dndoff'=>'1');
		if(trim($file)){
			/*
			<?xml version="1.0" encoding="utf8"?>
			<config>
				<agent>
					<node exten='6105' name='张三'/>
					<node exten='6107' name='李四'/>
				</agent>
				<auth>
					<node name='dndon' value='1' desc='示忙' />
					<node name='dndoff' value='1' desc='示闲' />
				</auth>
			</config>
			*/
			$xml = trim(file_get_contents($file));
			$xml = $this->mix->XML2Array($xml);
			foreach($xml['agent']['node'] as $r){
				if($r['@attributes']['exten']){
					$agents[$r['@attributes']['exten']]['agent']=$r['@attributes']['agent'];
					$agents[$r['@attributes']['exten']]['img']=$r['@attributes']['img'];
				}
			}
			foreach($xml['auth']['node'] as $r){
				if($r['@attributes']['name'])$auths[$r['@attributes']['name']]=$r['@attributes']['value'];
			}
		}else{
			$conn=mysql_connect($conf['dbhost'], $conf['dbuser'], $conf['dbpassword']);
			mysql_select_db($conf['dbname']);
			mysql_query("set names utf-8");
			$result=mysql_query("select name, extension from users");
			while($row=mysql_fetch_array($result, MYSQL_ASSOC)){
				$agents[$row['extension']]['agent']=$row['name'];
				$agents[$row['extension']]['img']=IMG_URL.'default.jpg';
			}
		}
		return array('agents'=>$agents, 'auths'=>$auths);
	}

	/*
	 * 操作呼叫中心文件的接口
	 * 
	 * action	操作类型
	 * file		要操作的文件名
	 * content	更新文件的内容，为数组字符串
	 */
	public function mixfile(){
		extract($_REQUEST);
		$type=isset($type)?$type:'';
		$file=isset($file)?$file:'';
		$error_msg='';
		if($type=='read'){
			//read file
			if(file_exists($file)){
				//file exists
				$res=@parse_ini_file($file, true);
				if(is_array($res)){
					var_export($res);
				}else{
					$error_msg="Warning: Unrecognized ini file format!";
				}			
			}else{
				//file is not exists
				$error_msg="Warning: file $file is not exists!";
			}
		}elseif($type=='write'){
			//write file
			$content=isset($content)?$content:'';
			eval("\$content = $content;");
			if(is_array($content)){
				$data='';
				foreach($content as $key=>$value){
					$data.='['.$key.']'."\n";
					if(is_array($value)){
						foreach($value as $k=>$v){
							$data.=$k.'="'.$v."\"\n";
						}				
					}else{
						$error_msg="Warning: unknown file content format, file write fail!";
						break;
					}
				}
				$fp = @fopen($file, 'wb');
				if($fp){
					flock($fp, LOCK_EX);
					$len = @fwrite($fp, $data);
					flock($fp, LOCK_UN);
					@fclose($fp);
					$error_msg="Success: file write successful!";
				}else{
					$error_msg="Warning: No write permission!";
				}
			}else{
				$error_msg="Warning: unknown file content format, file write fail!";
			}
		}else{
			$error_msg="Warning: unknown action!";
		}
		print_r($error_msg);		
	}

	/*
	 * 通话录音下载
	 */
	public function record_download(){
		$filename=isset($_REQUEST['filename'])?$_REQUEST['filename']:'';
        
		$filepath = '/var/spool/asterisk/monitor/';
		$hostid = getenv('HOSTNAME');
		//$filename = $hostid.'/'.$filename;

		if($filename){
				$download=pc_base::load_app_class('download', '', 0);
				if($filename && !file_exists($filename)){
						$arr=explode('-', $filename);
						$dirarr=explode('/', $filename);
						$filename=substr($arr[0], -8, 4).'/'.substr($arr[0], -4, 4).'/'.$filename;
						$dirarr[count($dirarr)-1]=substr($arr[0], -8, 4).'/'.substr($arr[0], -4, 4).'/'.$dirarr[count($dirarr)-1];
						$filename=implode('/', $dirarr);
				}
				$filename = $filepath.$hostid.'/'.$filename;
				new download($filename);
		}else{
				echo 'Warning: file name can not be blank!';
		}
	}

}
