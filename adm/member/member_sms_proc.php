<?php

include_once('./_common.php');

include_once('../../lib/sms.lib.php');


// post로 받은 데이터를 변수화
foreach($_POST as $k=>$v) {
	$$_POST[$k] = $v;
}

if($send_time == 'r') {
	$send_date = $send_ymd.' '.$send_h.':'.$send_i.':00';
}else {
	$send_date = null;
}


$sms_res = unit_sms_send($from_hp,$to_hp,$send_msg,$send_date);

echo $sms_res;


?>