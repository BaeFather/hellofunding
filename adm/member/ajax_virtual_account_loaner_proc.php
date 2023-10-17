<?
################################################################################
## 대출상환용 신한은행 가상계좌 발급 처리 (어드민용)
## 제작일 : 2018-05-29
################################################################################

//print_r($_POST); exit;

include_once("_common.php");
//include_once(G5_PATH.'/lib/insidebank.lib.php');

// 로그인 체크
if(!$_SESSION['ss_mb_id']) { echo "ERROR:LOGIN"; exit; }

while(list($k, $v)=each($_POST)) { ${$k} = trim($v); }

if(!$mb_no) { echo "ERROR:NONE_PARAM_DATA"; exit; }

$MB = sql_fetch("SELECT mb_id, member_type, mb_name, mb_co_name FROM g5_member WHERE mb_no='$mb_no' AND mb_level='1' AND member_group='L'");
if(!$MB['mb_id']) { echo "ERROR:NONE_MEMBER"; exit; }

// 가상계좌명 설정
if(!$loaner_va_name) {
	$loaner_va_name = ($MB['member_type']=='2') ? $MB['mb_co_name'] : $MB['mb_name'];
}

$loaner_va_name = sql_real_escape_string($loaner_va_name);			// SQL인젝션 방어
$loaner_va_name = preg_replace("/( )/", "", $loaner_va_name);		// 예금주명 공백제거


// 유휴가상계좌 가져오기
$VACT = sql_fetch("SELECT bank_cd, acct_no FROM IB_vact_hellocrowd WHERE acct_st=0 ORDER BY acct_no ASC LIMIT 1");
if(!$VACT || $VACT['acct_no']=='') { echo "ERROR:SH_VA_INSUFFICIENCY"; exit; }

$VA_BANK_CODE = '088';

// 유휴가상계좌(헬로크라우드대부)정보 할당
$sql0 = "
	UPDATE
		IB_vact_hellocrowd
	SET
		CUST_ID = '".$mb_no."',
		cmf_nm  = '".$loaner_va_name."',
		acct_st = '1',
		open_il = '".date('Ymd')."'
	WHERE
		acct_no = '".$VACT['acct_no']."'";
//echo $sql0 . "\n\n";
$res0 = sql_query($sql0);
if($res0) {

	// KSNET 가상계좌원장 할당정보 기록
	$sql1 = "
		INSERT INTO
			KSNET_VR_ACCOUNT
		SET
			BANK_CODE  = '".$VACT['bank_cd']."',
			VR_ACCT_NO = '".$VACT['acct_no']."',
			CORP_NAME  = '".$loaner_va_name."',
			USE_FLAG   = 'Y'";
	//echo $sql1 . "\n\n";
	$res1 = sql_query($sql1);


	// 회원정보상에 등록
	$sql2 = "
		UPDATE
			g5_member
		SET
			va_bank_code2    = '".$VACT['bank_cd']."',
			virtual_account2 = '".$VACT['acct_no']."',
			va_private_name2 = '".$loaner_va_name."'
		WHERE
			mb_no='".$mb_no."'";
	//echo $sql2 . "\n\n";
	$res2 = sql_query($sql2);

	echo "SUCCESS:" . $BANK[$VA_BANK_CODE] . " &nbsp; " . $VACT['acct_no'] . " &nbsp; " . $loaner_va_name;

}
else {
	echo $RETURN_ARR['ERRMSG'];
	echo ($RETURN_ARR['RCODE']) ? '('.$RETURN_ARR['RCODE'].')' : '';
}

exit;

?>