<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style type="text/css">
body{padding: 0;margin: 0;font-size: 12px;}
a {color: #0D72BC;TEXT-DECORATION:none}
.header{background: url(<?php echo MODULE_URL?>statics/images/mymenu.png) repeat left -30px;height: 30px;font-size: 14px;font-weight: bold;}
form.myform{margin: 0;}
form.myform textarea {overflow-y:auto;overflow-x:hidden;}
table.mytable{border-collapse: collapse;}
table.mytable td{border: 1px solid #A6C9E2;padding: 2px 5px;line-height: 22px;}
table.mytable .url{width: 500px;}
.btn{border-left: 1px solid #CCC;border-top: 1px solid #CCC;border-bottom: 1px solid #999;border-right: 1px solid #999;padding: 3px;height: 22px;cursor: pointer;background: #EFEEE1;}
#footer{text-align: center;line-height: 40px;}
#footer a{text-decoration: none;color: gray;}
#footer a:hover{color: #93C;}
</style>
<script type="text/javascript" src="<?php echo JS_URL?>jquery-1.3.2.min.js" charset="utf-8"></script>
<script type="text/javascript">
$(function(){
	initWindow();
	$(window).resize(function (){
		initWindow();
	});
	toBotton();
});
//设置页面适应窗口大小
function initWindow(){
	$("#logs").css("height",$(window).height()-120);
	$("#logs").css("width",$(window).width()-20);
}
//加载页面时textaera滚动条置底
function toBotton(){
	var t = document.getElementById('logs');
	window.scrollTo(0,0);//解决IE6下textaera滚动条不能置底问题
	t.scrollTop = t.scrollHeight;//设置textaera滚动条置底
}
//刷新页面
function refreshLog(){
	window.location.href = "?action=event_log";
}
</script>
</head>
<body>
<form action="" method="post" class="myform">
<table class="mytable" width="100%">
<tr class='header'>
    <td>
        事件转发日志&nbsp;&nbsp;<span style="color:red"><?php echo $opInfo;?></span>
    </td>
</tr>
<tr>
    <td>
        <textarea id="logs" onBlur="this.scrollTop=this.scrollHeight"><?php echo $logs;?></textarea>
    </td>
</tr>
<tr>
    <td>
        <input type="button" value="刷新日志" onClick="refreshLog()" class="btn"/>&nbsp;&nbsp;&nbsp;&nbsp;
        <input type="submit" name="submit" value="清空日志" class="btn"/>        
    </td>
</tr>
</table>
</form>
<div id="footer"><a href='http://www.singhead.com' target="_blank">深圳市深海捷科技有限公司 2007-<?php echo date('Y');?>, 版权所有</a></div>
</body>
</html>