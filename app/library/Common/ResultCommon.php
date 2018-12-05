<?php
namespace callApi\Common;
class ResultCommon
{
	public $config;//系统通用配置信息
	public $callback = '';
	public function __construct() {
		$config = include(CONFIG_PATH."config.php");
		$http_host=$_SERVER['HTTP_HOST'];//主机名
		if(($index=strpos($http_host, ':')) !== false){
			$http_host=substr($http_host, 0, $index);//主机名去掉端口号
		}

		$base_config = $config;
		
		if(trim($base_config['common']['cchost'])==''){
			$base_config['common']['cchost']=$http_host;//呼叫中心IP地址
		}
		if(trim($base_config['common']['dbhost'])==''){
			$base_config['common']['dbhost']=$http_host;//数据库IP地址
		}
		//如果接口使用本机运行模式，则可以直接使用localhost连接呼叫中心
		if($base_config['common']['runmode']=='local'){
			$base_config['common']['cchost_php']='localhost';
		}else{
			$base_config['common']['cchost_php']=$base_config['common']['cchost'];
		}
		$this->call_config=$base_config;
		$this->callback = isset($_REQUEST['callback'])?$_REQUEST['callback']:'';
	}
	public function success($response, $data = ''){
		$response->setJsonContent(array('code' => '200', 'msg' => '成功', 'data' => $data));
		$response->send();exit;
	}
	public function error($response, $code = '00000', $msg = ''){
		if($msg){
			$response->setJsonContent(array('code' => $code, 'msg' => $this->code_msg[$code], 'data' => $msg));
		}else{
			$response->setJsonContent(array('code' => $code, 'msg' => $this->code_msg[$code], 'data' => ''));
		}
		$response->send();exit;
	}

	public function returnCore($code, $param='', $result=''){
		$arr = array(
			'200'    => '请求成功！',
			'10000'  => '缺少'.$param.'参数！',
			'10002'  => $param.'参数格式有误！',
			'10003'  => '未发起呼叫！',
			'10004'  => '座席未创建！',
			'10005'  => '录音未找到！',
			'20001'  => '内容不能超过1024个字节！',
			'20002'  => '语音合成失败！',
			'20003'  => '无创建目录权限！',
			'20004'  => '语音文件未找到！',
			'30001'  => '语音识别失败！',
			'30002'  => '接口返回错误',
		);
		$data = array('Response'=>$code,'Message'=>$arr[$code],'Data'=>$result);
		if($this->callback){
			exit($this->callback.'('.json_encode($data).')');
		}else{
			exit(json_encode($data));
		}
	}

	/**
	 * 判断数据是否为json
	 */
	public function isJson($data = '', $assoc = false) {
		$data = json_decode($data, $assoc);
		if ($data && (is_object($data)) || (is_array($data) && !empty(current($data)))) {
			return $data;
		}
		return false;
	}
}

