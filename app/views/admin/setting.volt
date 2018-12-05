{{ content() }}
{{ javascript_include('public/js/jquery.form.js') }}
<script type="text/javascript">
$(function(){
  var open_type = 0;
  open_type_display(open_type);
});
function open_type_display(open_type){
  var myClass='';
  if(open_type==1){
    myClass='open_win';
  }else{
    myClass='open_callback';
  }
  $(".open_win").hide();
  $(".open_callback").hide();
  $("."+myClass).show();
}

function form_submit(){
  $('#config_form').ajaxForm(function(data) {
    $('.tip', window.parent.document).html(data.data);
    $('.tip', window.parent.document).css("display","block");
    setTimeout(function(){
      $('.tip', window.parent.document).hide();
      $('#submitbtn', window.parent.document).attr('disabled', false);
    }, 3000);
  });
}
</script>

<div class="container">
<div class="tip"></div>
<form action="" method='post' id="config_form" name="myform" target="submitform">
<table width="100%" class="setting_table">
<!--系统通用配置-->
  <tr class="head">
    <th width="120">系统配置</th>
    <th width="340"></th>
    <th style="text-align: right;">
      <!-- <span style="cursor: pointer;" onclick="$('.advanced').toggle()">高级 </span>| -->
      <span style="cursor: pointer;padding-right:15px;" onclick="location.href='/callApi/admin/index'">首页</span>
    </th>
  </tr>
  <tr>
    <th>服务端地址：</th>
    <td><input name="info[common][cchost]" type="text" value="{{ config['common']['cchost'] }}"/></td>
    <td>呼叫中心服务器IP地址，动态获取请留空</td>
  </tr>
  <tr>
    <th>呼叫中心端口：</th>
    <td><input name="info[common][ccport]" type="text" value="{{ config['common']['ccport'] }}"/></td>
    <td>呼叫中心服务器CallCenter端口号</td>
  </tr>
  <tr>
    <th>弹屏端口：</th>
    <td><input name="info[common][mixproxyport]" type="text" value="{{ config['common']['mixproxyport'] }}"/></td>
    <td>呼叫中心服务器弹屏端口号，多个端口号之间使用英文逗号分隔</td>
  </tr>
  <tr>
    <th>呼叫中心用户名：</th>
    <td><input name="info[common][ccuser]" type="text" value="{{ config['common']['ccuser'] }}"/></td>
    <td>呼叫中心服务器CallCenter用户名</td>
  </tr>
  <tr>
    <th>呼叫中心密码：</th>
    <td><input name="info[common][ccpassword]" type="text" value=""/></td>
    <td>呼叫中心服务器CallCenter密码</td>
  </tr>
  <tr>
    <th>数据库地址：</th>
    <td><input name="info[common][dbhost]" type="text" value="{{ config['common']['dbhost'] }}"/></td>
    <td>呼叫中心服务器CallCenter数据库IP地址，动态获取请留空</td>
  </tr>
  <tr>
    <th>数据库用户名：</th>
    <td><input name="info[common][dbuser]" type="text" value="{{ config['common']['dbuser'] }}"/></td>
    <td>呼叫中心服务器CallCenter数据库用户名</td>
  </tr>
  <tr>
    <th>数据库密码：</th>
    <td><input name="info[common][dbpassword]" type="text" value=""/></td>
    <td>呼叫中心服务器CallCenter数据库密码</td>
  </tr>
  <tr>
    <th>调试模式：</th>
    <td><select name="info[mixpopscreen][mix_debug]">
      {% set yessel = config['mixpopscreen']['mix_debug']=='YES'?'selected':'' %}
      {% set nosel = config['mixpopscreen']['mix_debug']=='NO'?'selected':'' %}
      <option value="YES"  {{ yessel }} >开启</option>
      <option value="NO"  {{ nosel }} >关闭</option>
    </select></td>
    <td>注意调试模式下，请在火狐、谷歌浏览器下</td>
  </tr>
  <tr>
    <th>弹屏时机：</th>
    <td><select name="info[mixpopscreen][pop_type]">
      {% set ringsel = config['mixpopscreen']['pop_type']=='RING'?'selected':'' %}
      {% set linksel = config['mixpopscreen']['pop_type']=='LINK'?'selected':'' %}
      <option value="RING"  {{ ringsel }} >振铃时弹屏</option>
      <option value="LINK"  {{ linksel }} >接通时弹屏</option>
    </select></td>
    <td>设定弹屏的时机，振铃时弹并或接通时弹屏</td>
  </tr>
<!--    高级设置-->
<!-- <tbody class="advanced"> -->

<!--   {% set localsel = config['common']['runmode']=='local'?'selected':'' %}
  {% set remotesel = config['common']['runmode']=='remote'?'selected':'' %} -->
  <!-- <tr>
    <th>接口运行模式：</th>
    <td><select name="info[common][runmode]">
      <option value="local"  {{ localsel }}>本机运行</option>
      <option value="remote" {{ remotesel }} >远程调用</option>
    </select></td>
    <td>当接口与呼叫中心位于同一台服务器时请选择本机运行模式，否则请选择远程调用模式</td>
  </tr>
  <tr>
    <th>心跳机制：</th>
    <td> 
      {# 判断时长是否超过60秒 #}
      {%- macro overtime(time) %}
          {% if time >= 60 %}
            {% set display_time = time/60 ~ '分钟' %}
          {% else %}
            {% set display_time = time ~ '秒' %}
          {% endif %}
          {{ display_time }}
      {%- endmacro %}
      <select name="info[common][ping]">
        {% set times = [0, 10, 20, 30, 40, 50, 60, 120, 300, 600, 1200, 1800] %}
        {% for time in times %}
          {% if config['common']['ping'] == time %}
            {% set timesel = 'selected' %}
          {% else %}
            {% set timesel = '' %}
          {% endif %}
          <option value="{{ time }}" {{ timesel }}>{{ overtime(time) }}</option>
        {% endfor %}
      </select>
    </td>
    <td>设置心跳频率，选择'无'表示禁用心跳机制</td>
  </tr> -->

<!--控制面板配置-->
  <!-- <tr class="head">
    <th>控制面板配置</th>
    <th>&nbsp;</th>
    <th>&nbsp;</th>
  </tr>
  <tr>
    <th>左侧边距：</th>
    <td><input name="info[mixpanel][left]" type="text" value="{{ config['mixpanel']['left'] }}"/></td>
    <td>控制面板距离浏览器的左侧边距，如300px则输入300即可</td>
  </tr>
  <tr>
    <th>示忙类型：</th>
    <td><textarea name="info[mixpanel][busytype]" rows="3">{{ config['mixpanel']['busytype'] | trim }}</textarea></td>
    <td>分机示忙时的类型选择列表，格式为：值|名称，如：<br>1|下班<br>添加时一种类型一行，如果示忙时不需要选择类型，则将此项留空</td>
  </tr> -->
<!--座席监控配置-->
 <!--  <tr class="head">
    <th>座席监控配置</th>
    <th>&nbsp;</th>
    <th>&nbsp;</th>
  </tr>
  <tr>
    <th>显示高度：</th>
    <td><input name="info[mixagentmonitor][height]" type="text" value="{{ config['mixagentmonitor']['height'] }}"/></td>
    <td>座席监控默认显示高度</td>
  </tr>
  <tr>
    <th>分机列表地址：</th>
    <td><input name="info[mixagentmonitor][url]" type="text" value="{{ config['mixagentmonitor']['url'] }}"/></td>
    <td>座席监控页面要监控的分机列表</td>
  </tr> -->
<!--队列监控配置-->
  <!-- <tr class="head">
    <th>队列监控配置</th>
    <th>&nbsp;</th>
    <th>&nbsp;</th>
  </tr>
  <tr>
    <th>显示高度：</th>
    <td><input name="info[mixqueuemonitor][height]" type="text" value="{{ config['mixqueuemonitor']['height'] }}"/></td>
    <td>队列监控默认显示高度</td>
  </tr> -->
<!-- </tbody> -->
<!--电话弹屏配置-->
<!--   <tr class="head">
    <th width="120">电话弹屏配置</th>
    <th width="340">&nbsp;</th>
    <th>&nbsp;</th>
  </tr>
  <tr>
    <th>弹屏机制：</th>
    <td><select name="info[mixpopscreen][pop_mode]">
      {% set flashsel = config['mixpopscreen']['pop_mode']=='flash'?'selected':'' %}
      {% set html5sel = config['mixpopscreen']['pop_mode']=='html5'?'selected':'' %}
      <option value="flash"  {{ flashsel }} >FLASH方式</option>
      <option value="html5"  {{ html5sel }} >html5方式</option>
    </select></td>
    <td>设定弹屏的时机，振铃时弹并或接通时弹屏</td>
  </tr> -->
 <!--  <tr>
    <th>服务器地址：</th>
    <td><input name="info[mixpopscreen][pop_uri]" type="text" value="{{ config['mixpopscreen']['pop_uri'] }}" /></td>
    <td>此选项html5方式生效,为空为默认服务器地址</td>
  </tr> -->

<!--   <tr>
    <th>呼入弹屏：</th>
    <td><select name="info[mixpopscreen][pop_up_when_dial_in]">
      {% set dial_in_yes_sel = config['mixpopscreen']['pop_up_when_dial_in']=='1'?'selected':'' %}
      {% set dial_in_no_sel = config['mixpopscreen']['pop_up_when_dial_in']=='0'?'selected':'' %}
      <option value='1'  {{ dial_in_yes_sel }} >是</option>
      <option value='0'  {{ dial_in_no_sel }} >否</option>
    </select></td>
    <td>设定呼入是否弹屏</td>
  </tr>
  <tr>
    <th>呼出弹屏：</th>
    <td><select name="info[mixpopscreen][pop_up_when_dial_out]">
      {% set dial_out_yes_sel = config['mixpopscreen']['pop_up_when_dial_out']=='1'?'selected':'' %}
      {% set dial_out_no_sel = config['mixpopscreen']['pop_up_when_dial_out']=='0'?'selected':'' %}
      <option value='1'  {{ dial_out_yes_sel }} >是</option>
      <option value='0'  {{ dial_out_no_sel }} >否</option>
    </select></td>
    <td>设定呼出是否弹屏</td>
  </tr> -->
  <!-- <tr>
    <th>号码前缀：</th>
    <td><input name="info[mixpopscreen][trim_prefix]" type="text" value="{{ config['mixpopscreen']['trim_prefix'] }}" /></td>
    <td>配置需去除的号码前缀，多个数字之间用','分隔，不需要时为空</td>
  </tr>
  <tr>
    <th>号码长度：</th>
    <td><input name="info[mixpopscreen][phone_number_length]" type="text" value="{{ config['mixpopscreen']['phone_number_length'] }}" /></td>
    <td>当号码长度大于或等于此处数值时，系统才能弹屏</td>
  </tr>
  <tr>
    <th>弹屏方式：</th>
    <td><select name="info[mixpopscreen][open_type]" onChange="open_type_display(this.value)">
      {% set open_type_sel1 = config['mixpopscreen']['open_type']=='1'?'selected':'' %}
      {% set open_type_sel2 = config['mixpopscreen']['open_type']=='2'?'selected':'' %}
      <option value='1'  {{ open_type_sel1 }} >弹出新窗口</option>
      <option value='2'  {{ open_type_sel2 }} >注册JS回调函数</option>
    </select></td>
    <td>设定弹屏方式，如弹出新窗口或注册JS回调函数</td>
  </tr>
  <tr class="open_win">
    <th>弹屏地址：</th>
    <td><input name="info[mixpopscreen][pop_url]" type="text" value="{{ config['mixpopscreen']['pop_url'] }}" /></td>
    <td>当弹弹方式为弹出窗口时，窗口将显示该地址的内容</td>
  </tr>
  <tr class="open_callback">
    <th>js回调函数：</th>
    <td><input name="info[mixpopscreen][mixcallback]" type="text" value="{{ config['mixpopscreen']['mixcallback'] }}" />
    </td>
    <td>当有弹屏事件发生时，要调用的JavaScript回调函数。该回调函数名为mixcallback， 函数体由客户来实现，有且仅有一个参数。该函数执行时系统会将字符串&quot;Method=Dialin&amp;Callerid=7205&amp;Calleeid=6205&amp;DateTime=2011-12-10 17:50:47&amp;Uniqueid=1323510646.173&amp;RecordFile=IN6205-7205-20111210-175046-1323510646.173.WAV&amp;CallerIDName=510646.174&quot;传递给该函数</td>
  </tr> -->

{# 指定格式生产select #}
{%- macro create_time_select(name, currenttime, timeArray) %}
    <select name="{{ name }}">
      {% for key, time in timeArray %}
        {% if currenttime == key %}
          {% set timesel = 'selected' %}
        {% else %}
          {% set timesel = '' %}
        {% endif %}
        <option value="{{ key }}" {{ timesel }}>{{ time }}</option>
      {% endfor %}
    </select>
{%- endmacro %}

{% if gsm_notice != '' %}
<tbody class="tab-5 tab-content tab-hide"  id='gsm_table' >
<!--洗号系统配置-->
  <tr class="head">
    <th width="120">洗号配置</th>
    <th width="550">&nbsp;</th>
    <th>&nbsp;</th>
  </tr>
  <tr>
  <th>启动时间：</th> 
  <td>年:{{ create_time_select('gsm[starttime][year_start]', config['time_start']['year_start'], config['time_array']['yearArray']) }}
    月:{{ create_time_select('gsm[starttime][month_start]', config['time_start']['month_start'], config['time_array']['monthArray']) }}
    日:{{ create_time_select('gsm[starttime][mday_start]', config['time_start']['mday_start'], config['time_array']['mdayArray']) }}
    星期:{{ create_time_select('gsm[starttime][wday_start]', config['time_start']['wday_start'], config['time_array']['wdayArray']) }}
    时间:{{ create_time_select('gsm[starttime][hour_start]', config['time_start']['hour_start'], config['time_array']['hourArray']) }}:
    {{ create_time_select('gsm[starttime][minute_start]', config['time_start']['minute_start'], config['time_array']['minuteArray']) }}
  </td>
    <td>设置任务的开始呼叫时间</td>
  </tr>
  <tr>
    <th>结束时间：</th>
    <td>年:{{ create_time_select('gsm[endtime][year_end]', config['time_end']['year_end'], config['time_array']['yearArray']) }}
    月:{{ create_time_select('gsm[endtime][month_end]', config['time_end']['month_end'], config['time_array']['monthArray']) }}
    日:{{ create_time_select('gsm[endtime][mday_end]', config['time_end']['mday_end'], config['time_array']['mdayArray']) }}
    星期:{{ create_time_select('gsm[endtime][wday_end]', config['time_end']['wday_end'], config['time_array']['wdayArray']) }}
    时间:{{ create_time_select('gsm[endtime][hour_end]', config['time_end']['hour_end'], config['time_array']['hourArray']) }}:
    {{ create_time_select('gsm[endtime][minute_end]', config['time_end']['minute_end'], config['time_array']['minuteArray']) }}
  </td>
    <td>设置任务的结束呼叫时间</td>
  </tr>
</tbody>
{% endif %}
<tbody>
  <tr class="head">
    <th><input type="submit" name="submitbtn" id="submitbtn" onclick="form_submit();" value="提  交 " style="width: 100px;"/></th>
    <th>&nbsp;</th>
    <th><div style="width: 370px;">&nbsp;</div></th>
  </tr>
</tbody>
</table>
</form>
</div>
<iframe name="submitform" style="position: absolute; left: -2000px;"></iframe>
