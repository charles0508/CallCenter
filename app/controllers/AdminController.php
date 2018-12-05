<?php
namespace callApi\Controllers;
use callApi\Gsm\parseIniFile;
use callApi\Common\ResultCommon as common;
/**
 * Display the default index page.
 */
class AdminController extends ControllerBase
{
	private $file = APP_PATH.'/config/gsmcall.conf';
	protected $common;
    /**
     * Default action. Set the public layout (layouts/public.volt)
     */
    public function indexAction()
    {
		$this->view->setVar('web_url', WEB_URL);
    }

    public function errorAction()
    {
		
    }
    public function onConstruct()
    {
    	$this->common = new common();
    }
    public function settingAction()
    {
		$gsm_notice = false;
		if(file_exists($this->file)){
			$gsm_notice = true;
			$gsmfile = new parseIniFile($this->file);
			$gsmconf = $gsmfile->read();
			$start = explode('|',$gsmconf['general']['starttime']);
			$end = explode('|',$gsmconf['general']['endtime']);
		}
		$originalData = $config = $this->common->call_config;
		if($_POST){
			$arr = $_POST['info'];
			//呼叫中心密码--amp111
			if(isset($arr['common']['ccpassword']) && !$arr['common']['ccpassword']){
				unset($arr['common']['ccpassword']);
			}
			//数据库密码--passw0rd(访问asterisk数据库的密码)
			if(isset($arr['common']['dbpassword']) && !$arr['common']['dbpassword']){
				unset($arr['common']['dbpassword']);
			}
			//数据库密码--passw0rd(访问mixcall数据库的密码)
			if(isset($arr['mixevent']['dbPassword']) && !$arr['mixevent']['dbPassword']){
				unset($arr['mixevent']['dbPassword']);
			}	
			//MMT数据库名
			$arr['common']['dbname']='asterisk';
			//原始数据二维数组
			if(isset($_POST['gsm']) && file_exists($this->file)){
				$starttime = implode('|',$_POST['gsm']['starttime']);
				$endtime = implode('|',$_POST['gsm']['endtime']);
				
				$gsmfile->edit(array('general'=>array('starttime'=>$starttime,'endtime'=>$endtime)));
			}
			// $arr['mixevent']['mixeventurl'] = isset($arr['mixevent']['mixeventurl'])?$arr['mixevent']['mixeventurl']:'';
			// $arr['mixevent']['mixeventurl'] = implode('|', $arr['mixevent']['mixeventurl']);
			//最终要写入到配置文件中的数据
			foreach($originalData as $key=>$value){
				if(is_array($value)){
					foreach($value as $k=>$v){
						if(isset($arr[$key][$k])){
							$originalData[$key][$k]=$arr[$key][$k];
						}
					}
				}
			}
			$tip = set_config('config', $originalData)?'操作成功':'操作失败，请给配置文件赋予写的权限';
			$this->common->returnCore('200', '', $tip);
			
		}else{
			$config2 = new \Phalcon\Config\Adapter\Php(APP_PATH."/config/call.php");
			file_put_contents('/opt/log/data.txt', 'Time: '.date('Y-m-d H:i:s')."\r\n".var_export($config2, true)."\r\n\r\n\r\n", FILE_APPEND);
			// extract($config);
			//初始化时间数组
			$config['time_array']['yearArray'] = $this->yearArray(2);
			$config['time_array']['monthArray'] = $this->monthArray();
			$config['time_array']['mdayArray'] = $this->mdayArray();
			$config['time_array']['wdayArray'] = $this->wdayArray();
			$config['time_array']['hourArray'] = $this->hourArray();
			$config['time_array']['minuteArray'] = $this->minuteArray();	
			// print_r($config['time_array']);exit;
			$config['time_start']['year_start'] = isset($start[0])?$start[0]:'*';		
			$config['time_start']['month_start'] = isset($start[1])?$start[1]:'*';		
			$config['time_start']['mday_start'] = isset($start[2])?$start[2]:'*';		
			$config['time_start']['wday_start'] = isset($start[3])?$start[3]:'*';		
			$config['time_start']['hour_start'] = isset($start[4])?$start[4]:'*';
			$config['time_start']['minute_start'] = isset($start[5])?$start[5]:'*';
			
			$config['time_end']['year_end'] = isset($end[0])?$end[0]:'*';
			$config['time_end']['month_end'] = isset($end[1])?$end[1]:'*';
			$config['time_end']['mday_end'] = isset($end[2])?$end[2]:'*';
			$config['time_end']['wday_end'] = isset($end[3])?$end[3]:'*';
			$config['time_end']['hour_end'] = isset($end[4])?$end[4]:'*';
			$config['time_end']['minute_end'] = isset($end[5])?$end[5]:'*';
			$this->view->setVar('gsm_notice', $gsm_notice);
			$this->view->setVar('config', $config);
		}
    }

    	/**
	 * 返回年份数组
	 * @param int $count 表示要显示多少个年信息
	 * @return array
	 */
	public function yearArray($count=1)
	{
		$array = array("*"=>"-");
		for($i=date('Y');$i<=date('Y')+$count;$i++){
			$array[$i] = $i;
		}
		return $array;
	}
	/**
	 * 返回月份数组
	 * @param string $str 单位，默认为月
	 * @return array
	 */
	public function monthArray($str='月')
	{
		$array = array("*"=>"-");
		for($i=1;$i<=12;$i++){
			$array[$i] = $i."&nbsp;$str";
		}
		return $array;
	}
	/**
	 * 返回日份数组
	 * @return array
	 */
	public function mdayArray()
	{
		$array = array("*"=>"-");
		for($i=1;$i<=31;$i++){
			$array[$i] = $i;
		}
		return $array;
	}
	/**
	 * 返回星期数组
	 * @return array
	 */
	public function wdayArray()
	{
		$array = array("*"=>"-");
		$array[1] = '周一';
		$array[2] = '周二';
		$array[3] = '周三';
		$array[4] = '周四';
		$array[5] = '周五';
		$array[6] = '周六';
		$array[7] = '周日';
		return $array;
	}
	/**
	 * 返回小时数组
	 * @return array
	 */
	public function hourArray()
	{
		$array = array("*"=>"-");
		for($i=0;$i<=23;$i++){
			$key = str_pad($i, 2, "0", STR_PAD_LEFT);
			$array[$key] = $key;
		}
		return $array;
	}
	/**
	 * 返回分钟数组
	 * @return array
	 */
	public function minuteArray()
	{
		$array = array("*"=>"-");
		for($i=0;$i<=59;$i++){
			$key = str_pad($i, 2, "0", STR_PAD_LEFT);
			$array[$key] = $key;
		}
		return $array;
	}	
}
	
