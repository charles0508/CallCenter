<?php
namespace callApi\Controllers;
use callApi\Gsm\parseIniFile;
use callApi\Common\ResultCommon as common;
/**
 * Display the default index page.
 */
class SmsController extends ControllerBase
{
	/*
	 * 短信接口
	 */
	protected $smsUrl = 'http://new.yxuntong.com/newdata/sms/'; //短信接口
	protected $token;
	protected $common;
	public function onConstruct()
    {
    	$this->common = new common();
    }
	/**
	 * 查询短信余额
	 */
	public function checkBalanceAction(){
		$common = new common();
		$postData    = file_get_contents("php://input");
		$param       = json_decode($postData, true);
		$account = $this->common->returnCore('10000', 'account');
		$password = $this->common->returnCore('10000', 'password');
		$url = $this->smsUrl.'Balance';
        $account =  "{'account':'".$account."','password':'".md5($password)."'}";
		$sid = sha1($account);
		$data = array(
		    'message'=>$account,
		    'sid'=>$sid,
		    'type'=>'json'
		);
		$result = $this->_exec_curl2($url, $data);
		$this->common->returnCore('200', '', $result);
		
	}

	/**
	 *  发送普通短信
	 */
	public function sendGeneralAction(){
		$common = new common();
		$postData    = file_get_contents("php://input");
		$param       = json_decode($postData, true);
		$account = $this->common->returnCore('10000', 'account');
		$password = $this->common->returnCore('10000', 'password');
		$sign = $this->common->returnCore('10000', 'sign');
		$phones = $this->common->returnCore('10000', 'phones');
		$content = $this->common->returnCore('10000', 'content');
		$sendtime    = isset($param['sendtime']) && !empty($param['sendtime'])?date('YmdHi', strtotime($param['sendtime'])):"";
		$url = $this->smsUrl.'Submit?v=2.0&type=json';
		$message =  "{'account':'".$account."','password':'".md5($password)."','phones':'".$phones."','content':'".$content."','sign':'".$sign."','sendtime':'".$sendtime."'}";
		$sid = sha1($message);
		$data = array(
		    'message'=>$message,
		    'sid'=>$sid,
		    'type'=>'json'
		);
		$result = $this->_exec_curl2($url, $data);
		$this->common->returnCore('200', '', $result);
	}

	/**
	 * 发送个性短信
	 */
	public function sendPersonalAction(){
		$common = new common();
		$postData    = file_get_contents("php://input");
		$param       = json_decode($postData, true);
		$account = $this->common->returnCore('10000', 'account');
		$password = $this->common->returnCore('10000', 'password');
		$sign = $this->common->returnCore('10000', 'sign');
		$list = $this->common->returnCore('10000', 'list');
		$sendtime    = isset($param['sendtime']) && !empty($param['sendtime'])?date('YmdHi', strtotime($param['sendtime'])):"";		
		//判断list是否为正确的json格式
		$common->isJson($list) ? true : $this->common->returnCore('10002', 'list');
		$listArr  = json_decode($list, true);
		$url = $this->smsUrl.'Submit?v=2.0&type=json';
		$result = '';
		if(count($listArr) > 1000){
			//处理成多个个性化短信
			$arr = array_chunk($listArr, 100);
			$resData = array();
			foreach($arr as $k=>$v){
				$lists = '[';
				$listStr = '';
				$phone = ''; //单个号码
				if(count($v) == 1){
					$phone = $v[0]['phone'];
				}
				foreach($v as $key=>$value){
					$listStr .= '{"content":"'.$value['content'].'", "phone":"'.$value['phone'].'"},';
				}
				$lists .= rtrim($listStr, ',').']';
				$message =  '{"account":"'.$account.'","password":"'.md5($password).'","list":'.$lists.', "sign":"'.$sign.'", "sendtime":"'.$sendtime.'"}';
				$sid = sha1($message);
				$data = array(
					'message'=>$message,
					'sid'=>$sid,
					'type'=>'json'
				);
				$response = $this->_exec_curl2($url, $data);
				$res = json_decode($response, true);
				//file_put_contents(dirname(__FILE__).'/sms.txt', var_export($res, true)."\r\n", FILE_APPEND);
				if(isset($res['list']) && !empty($res['list'])){
					foreach($res['list'] as $rk=>$rv){
						array_push($resData, $rv);
					}
				}else{
					$single = array(
						'result'=>$res['result'],
						'phone'=>$phone,
						'msgid'=>$res['msgid'],
						'desc'=>$res['desc'],
						'blacklist'=>''
					);
					array_push($resData, $single);
				}
				$result['result'] = $res['result'];
				$result['desc'] = $res['desc'];
				$listStr = '';
			}
			$result['list'] =  $resData;
			$result = json_encode($result);
		}else{
			$lists = '[';
			$listStr = '';
			foreach($listArr as $key=>$value){
				$listStr .= '{"content":"'.$value['content'].'", "phone":"'.$value['phone'].'"},';
			}
			$lists .= rtrim($listStr, ',').']';
			$message =  '{"account":"'.$account.'","password":"'.md5($password).'","list":'.$lists.', "sign":"'.$sign.'", "sendtime":"'.$sendtime.'"}';
			$sid = sha1($message);
			$data = array(
				'message'=>$message,
				'sid'=>$sid,
				'type'=>'json'
			);
			$result = $this->_exec_curl2($url, $data);
		}
		$this->common->returnCore('200', '', $result);
	}

	/**
	 * 查询发送状态
	 */
	public function checkSendStatusAction(){
		$common = new common();
		$postData    = file_get_contents("php://input");
		$param       = json_decode($postData, true);
		$account = $this->common->returnCore('10000', 'account');
		$password = $this->common->returnCore('10000', 'password');

		$url = $this->smsUrl.'Report?type=json';
		$message =  '{"account":"'.$account.'","password":"'.md5($password).'"}';
		$sid = sha1($message);
		$data = array(
		    'message'=>$message,
		    'sid'=>$sid,
		    'type'=>'json'
		);
		$result = $this->_exec_curl2($url, $data);
		$this->common->returnCore('200', '', $result);
	}

	/**
	 * 通过curl函数执行短信接口
	 *
	 * @param string $url url地址
	 * @param int $outTime 设置超时
	 * @return string
	 */
    public function _exec_curl2($url, $data='', $timeOut=30){
        $param = '';
        if(is_array($data)){
            foreach ($data AS $k=>$v){
                $param .= $k."=".$v."&";
            }
            $param = substr($param, 0,-1);
        }else{
            $param = $data;
        }
        $ch = curl_init() or die (curl_error());
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_HEADER,false);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        //这里设置默认超时时间10s
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
        //$data="a=&b=";
        if($param!=''){
            curl_setopt($ch,CURLOPT_POST,1);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$param);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return trim($result);
    }
}