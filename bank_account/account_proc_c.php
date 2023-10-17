<?

//sleep(2); exit;

///////////////////////////////////////////////////////
// 환급계좌등록, 신한가상계좌원장 등록
// 법인회원 전용
///////////////////////////////////////////////////////

include_once('./_common.php');
include_once(G5_PATH . "/lib/insidebank.lib.php");
include_once(G5_PATH . '/lib/sms.lib.php');

//print_r($_REQUEST); exit;

if(!$is_member) { echo "ERROR:LOGIN_CHECK"; exit; }		// 로그인 체크

$mb_no                 = $_POST['mb_no'];
$mb_co_owner           = trim($_POST['mb_co_owner']);
$mb_co_reg_num         = trim($_POST['mb_co_reg_num']);
$receive_method        = '2';															// 예치금으로 수취 강제 적용 (2021-10-01)
//$receive_method        = $_POST['receive_method'];

$bank_private_name     = trim($_POST['bank_private_name']);
$bank_private_name_sub = trim($_POST['bank_private_name_sub']);
$member_type           = $_POST['member_type'];
$bank_code             = $_POST['strBankCode'];
$account_num          = trim($_POST['strAccountNo']);
$account_num_enc      = masterEncrypt($account_num, false);
$bank_name             = $BANK[$bank_code];

$zip_num               = $_POST['zip_num'];
$mb_addr1              = $_POST['address_road'];
$mb_addr_jibeon        = $_POST['address_dong'];
$mb_addr2              = trim($_POST['mb_addr2']);


if(!$mb_no || ($mb_no!=$member['mb_no']) || !$bank_private_name || !$bank_code || !$account_num) { echo "ERROR:DATA_CHECK"; exit; }		// 필수 데이터 누락


$bank_private_name     = sql_real_escape_string($bank_private_name);
$bank_private_name_sub = sql_real_escape_string($bank_private_name_sub);
//$jumin               = sql_real_escape_string($jumin);
$zip_num               = sql_real_escape_string($zip_num);
$mb_addr1              = sql_real_escape_string($mb_addr1);
$mb_addr_jibeon        = sql_real_escape_string($mb_addr_jibeon);
$mb_addr2              = sql_real_escape_string($mb_addr2);
$account_num           = sql_real_escape_string($account_num);


// 계좌변경정보가 있을 경우 기존 계좌와 내용확인
if($member['bank_code'] && $member['account_num'] && $member['bank_private_name']) {
	if($bank_name!=$member['bank_name'] || $bank_code!=$member['bank_code'] || $bank_private_name!=$member['bank_private_name'] || $account_num!=$member['account_num']) {
		echo "ERROR:ACCOUNT_MISMATCH"; exit;
	}
	else {
		// 환급 계좌 정보 변경
		$sql = "
			UPDATE
				g5_member
			SET
				zip_num = '".$zip_num."',
				mb_addr1 = '".$mb_addr1."',
				mb_addr2 = '".$mb_addr2."',
				mb_addr_jibeon = '".$mb_addr_jibeon."',
			-- receive_method = '1',
				bank_name = '".$bank_name."',
				bank_code = '".$bank_code."',
				bank_private_name = '".$bank_private_name."',
				bank_private_name_sub = '".$bank_private_name_sub."',
				account_num = '".$account_num_enc."'
			WHERE
				mb_no = '".$mb_no."'";
		$res = sql_query($sql);
	}
}


// 재발급 금지
if($member['va_bank_code2'] && $member['virtual_account2'] && $member['va_private_name2']) { echo "ERROR:DUPLICATE_REQUEST"; exit; }


////////////////////////////////////////////////////////////////////
// 신한 가상계좌 생성 (원장정보는 함수 내부에서 입력/수정 처리됨)
////////////////////////////////////////////////////////////////////
$result = sh_make_account($mb_no);

if($result['RCODE']=='00000000') {

	// 회원정보 재호출
	$MB = sql_fetch("SELECT mb_no, mb_id, mb_name, mb_co_name, mb_co_reg_num, mb_co_owner, mb_hp, member_type, is_creditor, receive_method, bank_name, bank_code, bank_private_name, account_num, va_bank_code2, virtual_account2, va_private_name2 FROM g5_member WHERE mb_no='$mb_no'");
	$MB['mb_hp']       = masterDecrypt($MB['mb_hp'], false);
	$MB['account_num'] = masterDecrypt($MB['account_num'], false);

	$va_bank = $BANK[$MB['va_bank_code2']];

	/*
	$sms_row = sql_fetch("SELECT msg FROM `g5_sms_userinfo` WHERE use_yn='1' AND idx='15'");
	if($sms_row['msg']) {
		$sms_msg = str_replace("{USER_NAME}", $MB['mb_name'], $sms_row['msg']);       // 성명변경
		$sms_msg = str_replace("{BANK}", $va_bank, $sms_msg);                         // 은행명 변경
		$sms_msg = str_replace("{ACCOUNT_NAME}", $MB['va_private_name2'], $sms_msg);  // 예금주명 변경
		$sms_msg = str_replace("{ACCOUNT}", $MB['virtual_account2'], $sms_msg);       // 계좌번호 변경
		$rst = unit_sms_send($_admin_sms_number, $MB['mb_hp'], $sms_msg);             // 문자발송 실행
	}
	*/
	/*카카오 모듈로 교체 */
	$member["va_private_name2"] = $MB["va_private_name2"];
	$member["virtual_account2"] = $MB["virtual_account2"];
	$member["bank_name"] 				= $va_bank;

	$tcode = "hello002";
	$KaKao_Message_Send = new KaKao_Message_Send();
	$KaKao_Message_Send->MEMBER = $member;	// common.lib member 환경변수
	$KaKao_Message_Send->kakao_insert($tcode);
	/*카카오 모듈로 교체 */

	echo 'SUCCESS:' . $va_bank . '^' . $MB['virtual_account2'] . '^' . $MB['va_private_name2'];

}
else {
	echo $result['ERRMSG']. ':' . $result['RCODE'];
}

exit;

?>
