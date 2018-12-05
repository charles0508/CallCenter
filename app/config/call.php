<?php
return array (
  'common' => 
  array (
    'cchost' => 'cc.jxuntel.com',
    'ccport' => '6048',
    'mixproxyport' => '1234',
    'ccuser' => 'admin',
    'ccpassword' => 'amp111',
    'dbhost' => '127.0.0.1',
    'dbuser' => 'root',
    'dbpassword' => 'root',
    'dbname' => 'asterisk',
    'runmode' => 'remote',
    'ping' => '0',
    'cchost_php' => 'cc.jxuntel.com',
  ),
  'mixpanel' => 
  array (
    'left' => '200',
    'busytype' => '1|下班
2|开会
3|休息',
  ),
  'mixagentmonitor' => 
  array (
    'height' => '300',
    'url' => '',
  ),
  'mixqueuemonitor' => 
  array (
    'height' => '330',
  ),
  'mixpopscreen' => 
  array (
    'pop_mode' => 'flash',
    'mix_debug' => 'YES',
    'pop_uri' => 'ws://203.195.204.21:8083/mqtt',
    'pop_type' => 'LINK',
    'pop_up_when_dial_in' => '1',
    'pop_up_when_dial_out' => '1',
    'trim_prefix' => '',
    'phone_number_length' => '3',
    'open_type' => '1',
    'pop_url' => 'http://www.baidu.com',
    'win_top' => '100',
    'win_left' => '200',
    'win_width' => '800',
    'win_height' => '400',
    'win_scrollbars' => 'yes',
    'win_resizable' => 'yes',
    'mixcallback' => 'mixcallback',
  ),
  'mixcontrol' => 
  array (
    'displaydiv' => '1',
    'releft' => '50%',
    'retop' => '5%;',
    'rewidth' => 'auto',
    'reheight' => '15px',
    'refont' => '12px',
    'recolor' => 'orange',
    'background' => '#F1EDED',
    'encode' => 'UTF-8',
  ),
  'mixevent' => 
  array (
    'method' => 'post',
    'mixeventurl' => 'http://localhost/mixcall/?m=agent&c=api&a=vmail&event=userevent&userevent=mixvoicemail|http://localhost/mixcall/?m=statistic&c=api&a=comment&event=userevent&userevent=mixcomment|http://localhost/mixcall/?m=statistic&c=api&a=cdr&event=cdr|http://localhost/mixcall/?m=statistic&c=api&a=dnd&event=userevent&userevent=mixdnd|http://localhost/mixcall/?m=agent&c=api&a=queue_misscall&event=QueueCallerAbandon|',
    'autocallurl' => '',
    'islog' => '0',
    'ampextensions' => 'extensions',
  ),
  'mixgsm' => 
  array (
    'starttime' => '2014|1|1|2|2|0',
    'endtime' => '2014|2|2|1|0|0',
  ),
  'monitorDir' => '/var/spool/asterisk/monitor/',
);
?>
