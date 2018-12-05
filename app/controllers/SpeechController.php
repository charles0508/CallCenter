<?php
namespace callApi\Controllers;
use callApi\Gsm\parseIniFile;
use callApi\Common\ResultCommon as common;
use callApi\Api\AipSpeech;
use callApi\Api\download;
/**
 * Display the default index page.
 */
class SpeechController extends ControllerBase
{
	/*
	 * 短信接口
	 */
	protected $APP_ID;
	protected $API_KEY;
	protected $SECRET_KEY;
	protected $client;
	protected $path;
	protected $common;

	public function onConstruct()
    {
    	$this->common = new common();
        $conf=$this->common->call_config['baidutts'];
        $this->client = new AipSpeech($conf['APP_ID'], $conf['API_KEY'], $conf['SECRET_KEY']);
		$this->path = dirname(__FILE__).'/voicefile';
    }

	/**
	 * 语音合成接口
	 */
	public function SynthesisAction(){
		$postData  = file_get_contents("php://input");
		$param     = json_decode($postData, true);
		$content   = isset($param['content'])? (strlen($param['content'])<1024?$param['content']:$this->common->returnCore('20001')) : $this->common->returnCore('10000','content');
		$filename  = isset($param['filename'])?$param['filename']:$this->common->returnCore('10000','filename');
		//mode: file 返回文件名， source 文件资源
		$mode  = isset($param['mode'])?$param['mode']:$this->common->returnCore('10000','mode');

		//文件类型和发音人
		$per_arr = array(0,1,3,4);
		$aue_arr = array(3,4,5,6);
		
		$per = isset($param['per']) && in_array($param['per'], $per_arr)?$param['per']:0;
		$aue = isset($param['aue']) && in_array($param['aue'], $aue_arr)?$param['aue']:3;
		$vol = isset($param['vol']) && $param['vol']>0 && $param['vol']<9?$param['vol']:5;
		$spd = isset($param['spd']) && $param['spd']>0 && $param['spd']<15?$param['spd']:5;

		$result =$this->client->synthesis($content, 'zh', 1, array(
			'vol' => $vol, //音量 0-9
			'per' => $per, //发音人选择, 0为普通女声，1为普通男生，3为情感合成-度逍遥，4为情感合成-度丫丫，默认为普通女声
			'aue' => $aue, //文件格式：3：mp3(default) 4： pcm-16k  5： pcm-8k  6. wav
			'spd' => $spd, //语速 0-15
		));
		
		$formats = array(3 => 'mp3', 4 => 'pcm', 5 =>'pcm', 6 => 'wav');
		$format = $formats[$aue];
		if(!is_array($result)){
			if($mode == 'source'){
				$this->common->returnCore('200', '', $result);
			}else{
				$yearDir  = '/'.date('Y').'/'.date('md');
				$filepath = $this->path.$yearDir;
				if($this->mk_dir($filepath)){
					$file = $filepath.'/'.$filename.'.'.$format;
					file_put_contents($file, $result);
					$this->common->returnCore('200', '', $yearDir.'/'.$filename.'.'.$format);
				}else{
					$this->common->returnCore('20003');
				}
			}
		}else{
			$res = json_decode($result);
			$err_arr = array(
				'500'=>'不支持的输入', 
				'501'=>'输入参数不正确', 
				'502'=>'token验证失败', 
				'503'=>'合成后端错误'
			);
			$this->common->returnCore('20002', '', $err_arr[$res['err_no']]);
		}
	}

	/**
	 * 下载文件
	 */
	public function SpeechDownFileAction(){
		$filepath  = isset($_GET['filepath'])? $_GET['filepath']: $this->common->returnCore('10000','filepath');
		$file = $this->path.$filepath;
		if(!file_exists($file)){
			$this->common->returnCore('20004', '', $filepath);
		}
		new download($file);
	}

	/**
	 * 语音识别
	 */
	public function SpeechRecognitionAction(){
		$postData  = file_get_contents("php://input");
		$param     = json_decode($postData, true);
		//文件资源
		$source   = isset($param['source'])?urldecode($param['source']): $this->common->returnCore('10000','source');
		//文件格式 语音文件的格式，pcm 或者 wav 或者 amr
		$format   = isset($param['format'])?$param['format']: $this->common->returnCore('10000','format');
		//语种  1536普通话(支持简单的英文识别) ，1537普通话(纯中文识别)， 1737英语， 1637粤语， 1837四川话， 1936普通话远场
		$lang  = isset($param['lang'])?$param['lang']:'1536';
		$rate  = isset($param['rate'])?$param['rate']:'16000';

		$result = $this->client->asr($source, $format, $rate, array(
			'dev_pid' => $lang,
		));
		if(is_array($result)){
			if($result['err_no']!=0){
				$err_arr = array(
					'3300'=>'输入参数不正确, lang参数错误！', 
					'3301'=>'音频质量过差', 
					'3302'=>'鉴权失败', 
					'3303'=>'语音服务器后端问题', 
					'3304'=>'用户的请求QPS超限',
					'3305'=>'用户的日pv（日请求量）超限',
					'3307'=>'语音服务器后端识别出错问题',
					'3308'=>'音频过长,音频时长不超过60s',
					'3309'=>'音频数据问题',
					'3310'=>'输入的音频文件过大',
					'3311'=>'采样率rate参数不在选项里,仅提供8000,16000两种',
					'3312'=>'音频格式format参数不在选项里,仅支持pcm，wav或amr'
				);
				$this->common->returnCore('30001', '', $err_arr[$result['err_no']]);
			}else{
				$this->common->returnCore('200', '', $result['result']);
			}
		}else{
			$this->common->returnCore('30002');
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