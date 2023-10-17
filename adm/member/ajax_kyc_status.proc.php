<?
###############################################################################
## KYC 승인처리
###############################################################################

include_once("_common.php");

while( list($k, $v) = each($_POST) ) { ${$k} = trim($v); }



$MB = sql_fetch("
	SELECT
		A.mb_id, A.member_group, A.member_type, A.va_bank_code2, A.virtual_account2, A.kyc_allow_dd, A.kyc_allow_yn, A.kyc_allow_cnt, A.kyc_next_dd,
		IF(
			A.member_group='F',
			(SELECT open_il FROM IB_vact WHERE acct_no=A.virtual_account2 AND acct_st=1),
			(SELECT open_il FROM IB_vact_hellocrowd WHERE acct_no=A.virtual_account2 AND acct_st=1)
		) AS open_il
	FROM
		g5_member A
	WHERE
		A.mb_no='".$mb_no."'");


if($kyc_allow_yn == 'Y') {

	// 가상계좌는 수동발급 처리
	if($MB['member_group']=='F' && $kyc_allow_yn=='Y') {
		if($MB['va_bank_code2']!='088' || $MB['virtual_account2']=='') {
			$ARR = array("result" => "fail", "message"=>"본 회원의 가상계좌가 발급되지 않았습니다.\n가상계좌를 먼저 발급하십시요.");
			echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT); sql_close(); exit;
		}
	}

	$kyc_allow_dd     = date('Y-m-d');
	$kyc_allow_cnt    = $MB['kyc_allow_cnt'] + 1;
	$kyc_next_dd      = date('Y-m-d', strtotime("+1 year"));
	$KYC_NEXT_EXEC_DD = preg_replace("/(-| )/", "", $kyc_next_dd);
}
else {

	$kyc_allow_dd     = '';
	$kyc_allow_cnt    = $MB['kyc_allow_cnt'];
	$kyc_next_dd      = '';
	$KYC_NEXT_EXEC_DD = '';

}

$sql = "
	UPDATE
		g5_member
	SET
		kyc_allow_dd  = '".$kyc_allow_dd."',
		kyc_allow_yn  = '".$kyc_allow_yn."',
		kyc_allow_cnt = '".$kyc_allow_cnt."',
		kyc_next_dd   = '".$kyc_next_dd."',
		edit_datetime = NOW()
	WHERE
		mb_no = '".$mb_no."'";

$res = sql_query($sql);
if(!$res) {
	$ARR = array("result" => "fail", "message"=>"회원정보 업데이트 중 오류가 발생 하였습니다.\n페이지 새로고침 후, 다시 진행하여 주십시요.");
	echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT); sql_close(); exit;
}

if( sql_affected_rows() ) member_edit_log($mb_no);		// ▶▶▶▶ 회원변경로그 기록

$MB2 = sql_fetch("SELECT * FROM g5_member WHERE mb_no = '".$mb_no."'");

$logsql = "
	INSERT INTO
		g5_member_kyc_judge_log
	SET
		mb_no         = '".$mb_no."',
		mb_id         = '".$MB2['mb_id']."',
		kyc_order_id  = '".$MB2['kyc_order_id']."',
		kyc_reg_dd    = '".$MB2['kyc_reg_dd']."',
		kyc_allow_yn  = '".$MB2['kyc_allow_yn']."',
		kyc_allow_dd  = '".$MB2['kyc_allow_dd']."',
		kyc_allow_cnt = '".$MB2['kyc_allow_cnt']."',
		kyc_next_dd   = '".$MB2['kyc_next_dd']."',
		judge_mb_id   = '".$member['mb_id']."',
		judge_dt      = NOW()";
sql_query($logsql);

$ARR = array("result" => "success", "message"=>"");



echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);


sql_close();
exit;

?>