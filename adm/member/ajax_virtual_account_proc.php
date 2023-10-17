<?
################################################################################
# 신한은행 가상계좌 발급 처리 (어드민용)
# 사용자용 처리용은 개인회원 : public_html/bank_account/account_proc_p.php
# 일반회원 처리용은 법인회원 : public_html/bank_account/account_proc_c.php
################################################################################
##   - 2019-01-21 업데이트 : 주민번호, 전화번호, 계좌번호 암,복호화 추가
################################################################################

$mode = $_REQUEST['mode'];

/*
// 자사 도메인이 아닌곳에서 호출된 경우 exit
$allow_domain = "hellofunding.co.kr";
if(isset($_SERVER['HTTP_REFERER'])) {
	if(!preg_match("/$allow_domain/i", $_SERVER['HTTP_REFERER'])) {
		header('HTTP/1.1 404 Not Found');
	}
}
*/


include_once("_common.php");

// 로그인 체크
if(!$_SESSION['ss_mb_id']) { echo "ERROR:LOGIN"; exit; }

include_once(G5_PATH.'/lib/insidebank.lib.php');
include_once(G5_PATH.'/lib/sms.lib.php');


while(list($key, $value) = each($_POST)) { ${$key} = trim($value); }


///////////////////////////////////////////////////////////////////////////////
// 신규발급
///////////////////////////////////////////////////////////////////////////////
if($mode=='new') {

	$MB = sql_fetch("
		SELECT mb_no, mb_id, mb_name, mb_co_name, mb_co_reg_num, mb_co_owner, mb_hp, mb_hp_ineb, member_type, is_creditor, receive_method, bank_name, bank_code, bank_private_name, account_num, account_num_ineb, va_bank_code2, virtual_account2, va_private_name2
		FROM g5_member
		WHERE mb_no = '$mb_no'");

	if($MB['mb_hp'] || $MB['mb_hp_ineb']) {
		$MB['mb_hp'] = ($MB['mb_hp_ineb']) ? DGuardDecrypt($MB['mb_hp_ineb']) : masterDecrypt($MB['mb_hp'], false);
		$MB['mb_hp'] = @preg_replace("/(-| )/", "", $MB['mb_hp']);
	}

	if($MB['account_num'] || $MB['account_num_ineb']) {
		$MB['account_num'] = ($MB['account_num_ineb']) ? DGuardDecrypt($MB['account_num_ineb']) : masterDecrypt($MB['account_num'], false);
		$MB['account_num'] = @preg_replace("/(-| )/", "", $MB['account_num']);
	}

	if(!$MB) { echo "ERROR:NONE_MEMBER"; exit; }
	if($MB['member_type']=='2') {
		if($MB['mb_co_name']=='' || $MB['mb_co_reg_num']=='' || $MB['mb_co_owner']=='') { echo "ERROR:EMPTY_COMPANY_INFO"; exit; }
	}
	else {
		if($MB['bank_code']=='' || $MB['account_num']=='') { echo "ERROR:EMPTY_BANK_INFO"; exit; }

		$jumin = @getJumin($mb_no);
		if($jumin && strlen($jumin)==13) {
			$jumin = substr($jumin, 0, 6);
			$birth_date = (substr($jumin, 6, 1) > 2) ? '20'.$jumin : '19'.$jumin;
		}
		else { echo "ERROR:EMPTY_JUMINNO"; exit; }
	}

	if($MB['va_bank_code2'] && $MB['virtual_account2']) { echo "ERROR:DUPLICATE_REQUEST"; }		// 증복생성방지용 (재생성 하려면 회원정보테이블상의 신한가상계좌정보를 모두 삭제한후 시도할것)


	////////////////////////////////////////////////////////////////////
	// 신한 가상계좌 생성 (원장정보는 함수 내부에서 입력/수정 처리됨)
	////////////////////////////////////////////////////////////////////
	$result = sh_make_account($mb_no);


	if($result['RCODE']=='00000000') {

		// 상환방식 미설정시 임의설정
		if($MB['receive_method']=='') {
			sql_query("UPDATE g5_member SET receive_method='1' WHERE mb_no='$mb_no'");
			member_edit_log($mb_no);	// 변경로그생성
		}

		// 회원정보 재호출
		$MB = sql_fetch("
			SELECT mb_no, mb_id, mb_name, mb_co_name, mb_co_reg_num, mb_co_owner, mb_hp, mb_hp_ineb, member_type, is_creditor, receive_method, bank_name, bank_code, bank_private_name, account_num, account_num_ineb, va_bank_code2, virtual_account2, va_private_name2
			FROM g5_member
			WHERE mb_no = '$mb_no'");

		if($MB['mb_hp'] || $MB['mb_hp_ineb']) {
			$MB['mb_hp'] = ($MB['mb_hp_ineb']) ? DGuardDecrypt($MB['mb_hp_ineb']) : masterDecrypt($MB['mb_hp'], false);
			$MB['mb_hp'] = @preg_replace("/(-| )/", "", $MB['mb_hp']);
		}

		$va_bank = $BANK[$MB['va_bank_code2']];

		$sms_row = sql_fetch("SELECT msg FROM `g5_sms_userinfo` WHERE use_yn='1' AND idx='15'");
		if($sms_row['msg']) {
			$sms_msg = str_replace("{USER_NAME}", $MB['mb_name'], $sms_row['msg']);       // 성명변경
			$sms_msg = str_replace("{BANK}", $va_bank, $sms_msg);                         // 은행명 변경
			$sms_msg = str_replace("{ACCOUNT_NAME}", $MB['va_private_name2'], $sms_msg);  // 예금주명 변경
			$sms_msg = str_replace("{ACCOUNT}", $MB['virtual_account2'], $sms_msg);       // 계좌번호 변경
			$rst = unit_sms_send($_admin_sms_number, $MB['mb_hp'], $sms_msg);             //** 문자발송 실행 **//
		}

		echo "SUCCESS:" . $va_bank . " " . $MB['virtual_account2'] . " " . $MB['va_private_name2'];

	}
	else {
		echo $result['ERRMSG']. ':' . $result['RCODE'];
	}

}

exit;

?>