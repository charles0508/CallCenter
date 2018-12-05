function tip(info="操作成功"){
    $('.tip', window.parent.document).html(info);
	$('.tip', window.parent.document).css("display","block");
	setTimeout(function(){
		$('.tip', window.parent.document).hide();
		$('#submitbtn', window.parent.document).attr('disabled', false);
	}, 3000);
}

function dial(){
	//电话呼出
	CallOut(jQuery("#dial1").val(), jQuery("#dial2").val(), '', 'callout_res');
}
function hangup(){
	//电话挂断
	HangUp(jQuery("#hangup1").val(), 'hangup_res');	
}
function transfer(){
	//电话转接
	CallTransfer(jQuery("#transfer1").val(), jQuery("#transfer2").val(), 'transfer_res');
}
function busy1(){
	//分机示忙
	SignInAndOut(jQuery("#busy1").val(), 'out', 'signout_res');
}
function busy2(){
	//分机示闲
	SignInAndOut(jQuery("#busy2").val(), 'in', 'signin_res');
}
function busystatus(){
	//获取示忙状态
	CheckSignStatus(jQuery("#busystatus1").val(), 'signStatus_res');	
}
function extenstatus(){
	//分机状态
	CheckStatus(jQuery("#extenstatus1").val(), 'extenStatus_res')	
}
function pickup(){
	//电话拦截
	Snatch(jQuery("#pickup1").val(), jQuery("#pickup2").val(), 'snatch_res');
}
function hold(){
	//通话保持
	HoldAndRecover(jQuery("#hold1").val(), 'hold_res');	
}
function chanspy(){
	//分机监听
	TapPhone(jQuery("#chanspy1").val(), jQuery("#chanspy2").val(), '0', 'tap_res')	
}
function singheadGetDNDCallback(str){
	if(str.result==1){
		alert(str.data);
	}else{
		alert('分机示忙状态获取失败');
	}
}
	
function singheadExtenStatusCallback(str){
	if(str.result==1){
		alert(str.data);
	}else{
		alert('分机状态获取失败');
	}
}

