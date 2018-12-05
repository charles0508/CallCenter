<?php
pc_base::load_sys_class('model', '', 0);
class api_data extends model{
	public function __construct() {
		$this->db_config = pc_base::load_config('database');
		$this->db_setting = 'cdrdb';
		parent::__construct();
	}
	public function getvar($name){
		return $this->$name;
	}
	public function setvar($name, $value){
		$this->$name=$value;
	}
	public function construct(){
		parent::__construct();
	}
}