<?
###############################################################################
##  세금계산서,현금영수증 일괄발행 실행
###############################################################################

include_once('./_common.php');

if($is_admin != 'super') { msg_replace("/"); }

while( list($k, $v) = each($_POST) ) { ${$k} = trim($v); }

if($type=='all') {
	$txt = "세금계산서.현금영수증";
}
else {
	$txt = ($type=='c') ? "세금계산서" : "현금영수증";
}
$g5['title'].= "계산서일괄발행처리(".$txt.")";
if($member['mb_level'] == '9') include_once(G5_ADMIN_PATH."/inc_sub_admin_access_check.php");		// 부관리자 접속로그 등록


if(!$request_date) {

	$RESULT_ARR = array('code' => 'ERROR', 'message' => '발급 대상일이 전송되지 않았습니다.');

}
else {

	$EXECPATH['c'] = G5_ADMIN_PATH . "/repayment/taxinvoice_request_c.php " . $request_date;
	$EXECPATH['p'] = G5_ADMIN_PATH . "/repayment/taxinvoice_request_p.php " . $request_date;

	if($type=='all') {

		$exec_str = "/usr/local/php/bin/php -q " . $EXECPATH['c'] . " > /dev/null &;";
		$exec_str.= "/usr/local/php/bin/php -q " . $EXECPATH['p'] . " > /dev/null &";

		@shell_exec($exec_str);

	}
	else {

		if( in_array($type, array('c','p')) ) {

			$exec_str = "/usr/local/php/bin/php -q " . $EXECPATH[$type];
			$exec_str.= " > /dev/null &";

			@shell_exec($exec_str);

		}

	}

	//echo $exec_str ."\n";

	$RESULT_ARR = array('code' => 'SUCCESS', 'message' => '');

}

echo json_encode($RESULT_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);

?>