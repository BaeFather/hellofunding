#!/usr/local/php/bin/php -q
<?
$base_path = "/home/crowdfund/public_html";

include_once($base_path . '/common.cli.php');
include_once($base_path . '/lib/sms.lib.php');

$prd_idx = $_SERVER['argv'][1];
$sms_gubun = $_SERVER['argv'][2];


if(!$prd_idx) {
	echo json_encode(array('result'=>'FAIL', 'message'=>'데이터 없음'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;
}
?>

<?

if ($sms_gubun=="1") {  // 기표 안내 문자
	
	$prd = sql_fetch("select recruit_amount, loan_mb_no, invest_return, loan_end_date from cf_product where idx='$prd_idx'");
	$loan_mb_no = $prd["loan_mb_no"];
	$loan_amount = $prd["recruit_amount"];
	$loan_eyul = $prd["invest_return"] * 1;
	$loan_end_date = $prd["loan_end_date"];
	$loan_end_date_han = substr($loan_end_date, 0, 4) ."년 ". substr($loan_end_date, 5, 2)."월 ".substr($loan_end_date, 8, 2)."일";
	
	// 차주정보
	$chaju  = sql_fetch("SELECT mb_id, mb_name, mb_co_name, member_type, mb_hp FROM g5_member WHERE mb_no='$loan_mb_no'");
	$CHAJU_HP = masterDecrypt($chaju['mb_hp'], false);
	$CHAJU_NAME = $chaju["mb_name"];
	
	
	$chaju_sms = "[헬로펀딩] 대출 완료
안녕하세요 ".$CHAJU_NAME."님 헬로펀딩입니다.
고객님이 신청하신 대출금이 실행 되었습니다.

- 대출금 : ".number_format($loan_amount)."원
- 대출금리 : ".$loan_eyul." %
- 만기일 :  ".$loan_end_date_han." 

고객님의 이자 납부일은 매월 1일입니다.
감사합니다.

주택금융사업팀 : 1588-5210";

	//echo "prd_idx => $prd_idx , sms_gubun -> $sms_gubun , loan_mb_no -> $loan_mb_no , CHAJU_HP -> $CHAJU_HP \n\n";
	//echo $chaju_sms."\n";


	$from_hp = "1588-5210";
	$to_hp = $CHAJU_HP;
	//$to_hp = "010-6724-1409" ;
	$send_msg = $chaju_sms;
	$time = date("Y-m-d H:i:s");
	$send_date = date("Y-m-d H:i:s", strtotime("+20 minutes", strtotime($time)));

	//unit_sms_send_test($from_hp, $to_hp, $send_msg, $send_date, $send_id=null); 
	unit_sms_send($from_hp, $to_hp, $send_msg, $send_date, $send_id=null); 

} else if ($sms_gubun=="2") {  // 상환시

	$prd = sql_fetch("select recruit_amount, loan_mb_no, invest_return, loan_end_date from cf_product where idx='$prd_idx'");
	$loan_mb_no = $prd["loan_mb_no"];
	$loan_amount = $prd["recruit_amount"];
	$loan_eyul = $prd["invest_return"] * 1;
	$loan_end_date = $prd["loan_end_date"];
	$loan_end_date_han = substr($loan_end_date, 0, 4) ."년 ". substr($loan_end_date, 5, 2)."월 ".substr($loan_end_date, 8, 2)."일";
	
	// 차주정보
	$chaju  = sql_fetch("SELECT mb_id, mb_name, mb_co_name, member_type, mb_hp FROM g5_member WHERE mb_no='$loan_mb_no'");
	$CHAJU_HP = masterDecrypt($chaju['mb_hp'], false);
	$CHAJU_NAME = $chaju["mb_name"];

	$chaju_sms = "[헬로펀딩] 상환 완료
안녕하세요 ".$CHAJU_NAME."님 헬로펀딩입니다.
당사 대출을 이용해 주셔서 감사합니다.

- 대출금 : ".number_format($loan_amount)."원
- 대출금리 : ".$loan_eyul." %
- 상환일 :  ".$loan_end_date_han." 

주택금융사업팀 : 1588-5210";

	$from_hp = "1588-5210";
	$to_hp = $CHAJU_HP;
	//$to_hp = "010-6724-1409" ;
	$send_msg = $chaju_sms;
	$time = date("Y-m-d H:i:s");
	//$send_date = date("Y-m-d H:i:s", strtotime("+10 minutes", strtotime($time)));
	$send_date = "";

	//unit_sms_send_test($from_hp, $to_hp, $send_msg, $send_date, $send_id=null); 
	unit_sms_send($from_hp, $to_hp, $send_msg, $send_date, $send_id=null); 

}

?>