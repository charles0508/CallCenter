<?php
namespace callApi\Api;
class task{
    public function onConstruct(){
        $this->ztw_outgoingPath='/var/spool/asterisk/noticetask/';					//外呼文件临时路径
        $this->outgoingPath='/var/spool/asterisk/outgoing/';					//外呼文件目标路径
    }
    //入口
    public function init($data){
        $res = $this->createCallFile( $data);//生成外呼文件	
        return $res;
    }
    
    //生成call文件
    private function createCallFile($data){
        if(isset($data)&&$data){
            $id       = $data['id'];
            $callerid = $data['callerid'];   //主叫号码
            $calleeid = $data['phone'];   //被叫号码
            $actionId = $data['ActionID'];
            $context  = $data['Context'];
            //外线号码先振铃
            //$channel="SIP/trunkout/18320949530";

$call_data = "Channel: Local/{$calleeid}@from-internal
CallerID: {$calleeid} <{$callerid}>
MaxRetries: 0
RetryTime: 5
WaitTime: 60
Account: $actionId
Context: $context
Extension: s
Priority: 1
Set: autocallcallerid=$callerid
Set: autocallcalleeid=$calleeid
Set: __autocallfile=
Set: autocallvar=
Set: autocallid=$id
AlwaysDelete: Yes
Archive: YES
";
            //文件名：任务ID+模板ID+主叫号码+被叫号码+ID+自定义字段+uniqueid+外呼前缀
            $file=$id.'-'.$callerid.'-'.$calleeid.'-'.uniqid();
            $file=$this->ztw_outgoingPath.$file;
        
            $fp = @fopen($file, 'wr') or exit("Can not open file $file !");
            flock($fp, LOCK_EX);
            $len = @fwrite($fp, $call_data);
            flock($fp, LOCK_UN);
            @fclose($fp);
            // exec("dos2unix $file");
            exec("chmod -R 777 $file");
            exec("mv $file {$this->outgoingPath}");
            return true;
        }else{
            return false;
        }
    }
}
?>