<?

//sleep(2); exit;

///////////////////////////////////////////////////////
// 환급계좌등록, 신한가상계좌원장 등록
// 개인 및 개인/대부업회원 전용
///////////////////////////////////////////////////////

include_once('./_common.php');
include_once(G5_PATH . "/lib/insidebank.lib.php");
include_once(G5_PATH . '/lib/sms.lib.php');

//print_r($_REQUEST); exit;

if(!$is_member) { echo "ERROR:LOGIN_CHECK"; exit; }		// 로그인 체크

$ck_entry_key = get_cookie('ck_entry_key');		// 1000억돌파이벤트2 용 쿠키

$mb_no                 = $_POST['mb_no'];
$bank_private_name     = trim($_POST['USERNM1']);
$bank_private_name_sub = trim($_POST['bank_private_name_sub']);	//부기명
$jumin                 = trim($_POST['JUMINNO1']);
$member_type           = $_POST['member_type'];
$private_yn            = $_POST['private_yn'];
$zip_num               = $_POST['zip_num'];
$mb_addr1              = $_POST['address_road'];
$mb_addr_jibeon        = $_POST['address_dong'];
$mb_addr2              = trim($_POST['mb_addr2']);
$bank_code             = $_POST['strBankCode'];
$account_num           = trim($_POST['strAccountNo']);
$account_num_enc       = masterEncrypt($account_num, false);
$account_num_key       = substr($account_num, -4);
$bank_name             = $BANK[$bank_code];
$receive_method        = '2';															// 예치금으로 수취 강제 적용 (2021-10-01)


if(!$mb_no || ($mb_no!=$member['mb_no']) || !$bank_private_name || !$jumin || !$private_yn || !$bank_code || !$account_num) { echo "ERROR:DATA_CHECK"; exit; }		// 필수 데이터 누락
if($private_yn!='Y') { echo "ERROR:ACCOUNT_MISMATCH"; exit; }		// 본인 계좌인증 실패
if(!preg_match("/".$member['mb_name']."/", $bank_private_name)) { echo "ERROR:NAME_MISMATCH"; exit; }		// 예금주명과 회원명이 매치되지 않음.

$bank_private_name     = sql_real_escape_string($bank_private_name);
$bank_private_name_sub = sql_real_escape_string($bank_private_name_sub);
$jumin                 = sql_real_escape_string($jumin);
$zip_num               = sql_real_escape_string($zip_num);
$mb_addr1              = sql_real_escape_string($mb_addr1);
$mb_addr_jibeon        = sql_real_escape_string($mb_addr_jibeon);
$mb_addr2              = sql_real_escape_string($mb_addr2);
$account_num           = sql_real_escape_string($account_num);

// 주민번호에서 생년월일 및 성별 추출
$ARR = getBirthGender($jumin);
$birthdate = $ARR[0];
$gender    = $ARR[1];


///////////////////////////////////////////////////////////////////////////////////////////////////
// 주민번호 암호화 저장
///////////////////////////////////////////////////////////////////////////////////////////////////
$link2 = sql_connect(G5_MYSQL_HOST2, G5_MYSQL_USER2, G5_MYSQL_PASSWORD2, G5_MYSQL_DB2);

$encJumin = masterEncrypt($jumin, true);
$md5Jumin = strtoupper(md5(masterEncrypt($jumin, false)));

$sql = "SELECT * FROM member_private WHERE mb_no='".$mb_no."' ORDER BY idx DESC LIMIT 1";
$member_private_row = sql_fetch($sql, '', $link2);
if(!$member_private_row) {
	$sql = "INSERT INTO member_private (mb_no, regist_number, 5dm) VALUES('$mb_no', '$encJumin', '$md5Jumin');";
	$res = sql_query($sql, '', $link2);
	if(!$res) { echo "ERROR:PRIVATE_SAVE_FAILED"; exit; }		// 개인 기밀정보 저장 에러
}
else {
	if(@trim($member_private_row['regist_number'])=='') {
		$sql = "UPDATE member_private SET regist_number='$encJumin', 5dm='$md5Jumin' WHERE mb_no='$mb_no'";
		$res = sql_query($sql, '', $link2);
		if(!$res) { echo "ERROR:PRIVATE_UPDATE_FAILED"; exit; }		// 개인 기밀정보 저장 에러
	}
}
///////////////////////////////////////////////////////////////////////////////////////////////////

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
		account_num = '".$account_num_enc."',
		account_num_key = '".$account_num_key."',
		mb_birth = '".$birthdate."',
		mb_sex = '".$gender."'
	WHERE
		mb_no = '".$mb_no."'";
$res = sql_query($sql);


// 재발급 금지
if($member['va_bank_code2'] && $member['virtual_account2'] && $member['va_private_name2']) {
	echo "ERROR:DUPLICATE_REQUEST";
	exit;
}


////////////////////////////////////////////////////////////////////
// 신한 가상계좌 생성 (원장정보는 함수 내부에서 입력/수정 처리됨)
////////////////////////////////////////////////////////////////////
$result = sh_make_account($mb_no);

if($result['RCODE']=='00000000') {

	// 회원정보 재호출
	$MB = sql_fetch("
		SELECT
			mb_no, mb_id, mb_name, mb_co_name, mb_co_reg_num, mb_co_owner, mb_hp, member_type, is_creditor, receive_method, bank_name, bank_code, bank_private_name, account_num, va_bank_code2, virtual_account2, va_private_name2
		FROM
			g5_member
		WHERE
			mb_no='$mb_no'");
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

	// 천억돌파이벤트2 참여확정 (탈퇴후 재응모자 걸러내기 위해 hp값을 기록하여둠)
	if( preg_match("/100BEVENT2/", $ck_entry_key) ) {
		if( sql_query("UPDATE event_entry_log SET hp='".masterEncrypt($MB['mb_hp'], false)."', member_idx='".$member['mb_no']."' WHERE invalid='' AND entry_key='".$ck_entry_key."'") ) {
			set_cookie('ck_entry_key', '', -100);
		}
	}

	echo 'SUCCESS:' . $va_bank . '^' . $MB['virtual_account2'] . '^' . $MB['va_private_name2'];

}
else {
	echo $result['ERRMSG']. ':' . $result['RCODE'];
}

exit;

?>
