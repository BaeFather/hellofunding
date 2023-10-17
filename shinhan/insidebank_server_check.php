<?php
// 크론탭으로 5분간격 실행
// php -q /home/crowdfund/public_html/shinhan/insidebank_server_check.php

set_time_limit(60);

$path = '/home/crowdfund/public_html';

include_once($path . '/common.cli.php');
include_once($path . '/lib/insidebank.lib.php');
include_once($path . '/lib/sms.lib.php');


// 점검시간중 STOP
if( date('Y-m-d H:i:s') >= $CONF['BANK_STOP_SDATE'] && date('Y-m-d H:i:s') < $CONF['BANK_STOP_EDATE'] ) {
	exit;
}

if( !in_array(date('H'), array('00','04')) ) {

	$RESULT = insidebank_request('000');
	//print_r($RESULT);

	if($RESULT['RCODE'] != '00000000') {
		for($i=0; $i<count($CONF['event_receive_phone']); $i++) {
			$msg = "인사이드뱅크서버(상용) 통신 체크요망! \n" . date('Y-m-d H:i:s');
			//$rst = unit_sms_send($CONF['admin_sms_number'], $CONF['event_receive_phone'][$i], $msg);             // 문자발송 실행
		}
	}

}

sql_close();
exit;

?>
