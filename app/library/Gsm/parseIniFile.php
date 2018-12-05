<?php
namespace callApi\Gsm;
class parseIniFile{
	private $fileName;
	//构造函数，将INI文件名保存起来。
	function __construct($fileName=''){
		$this->fileName=$fileName;
	}	
	//读取INI文件，并将文件内容保以二维数组形式返回。
	function read($fileName=''){
		if(!$fileName){
			$fileName=$this->fileName;
		}
		if(file_exists($fileName)){
			return parse_ini_file($fileName, true);
		}else{
			return array();
		}
	}
	//将二维数组内容写入到INI文件中。
	function write($content=array()){
		$data='';
		foreach($content as $key=>$value){
			$data.='['.$key.']'."\n";
			foreach($value as $k=>$v){
				$data.=$k.'="'.$v."\"\n";
			}
		}	
		$fp = @fopen($this->fileName, 'wb') or exit("Can not open file $file !");
		flock($fp, LOCK_EX);
		$len = @fwrite($fp, $data);
		flock($fp, LOCK_UN);
		@fclose($fp);
		return true;
	}
	//添加一节或多节
	function add($content=array()){
		$fileArr=$this->read();
		foreach(array_keys($content) as $row){
			if(in_array($row, array_keys($fileArr))){
				return false;
			}
		}		
		$content=array_merge($fileArr, $content);
		return $this->write($content);
	}
	//编辑一节
	function edit($content=array(),$mode='key'){//更新全部节点或更新节点数据
		$fileArr=$this->read();
		foreach(array_keys($content) as $row){
			if(!in_array($row, array_keys($fileArr))){
				return false;
			}else{
				if($mode=='key'){
					$intersect = array_intersect_key($fileArr[$row],$content[$row]);
					foreach($intersect as $k=>$v){
						$fileArr[$row][$k] = $content[$row][$k];
					}
				}else{
					$fileArr[$row]=$content[$row];
				}
			}
		}
		return $this->write($fileArr);
	}
	//删除一节
	function del($section=''){
		$fileArr=$this->read();
		if(array_key_exists($section, $fileArr)){
			unset($fileArr[$section]);
			return $this->write($fileArr);
		}else{
			return false;	
		}
	}
	//清空文件
	function delAll(){
		$fileArr=$this->read();
		foreach(array_keys($fileArr) as $row){
			if(substr($row, 0, 5)=='task-'){
				unset($fileArr[$row]);
			}
		}
		return $this->write($fileArr);
	}
}
