<?php
namespace callApi\Functions;
class autoload {
	function __construct() {  
	    spl_autoload_register('myAutoLoad');
	}
	function myAutoLoad(){
	    $file = 'global.php';
            if(file_exists($file)&&is_file($file)){
                require($file);
            }
	}  
}
