<?
###############################################################################
## 개명으로 인한 예금주명 수정 (신한은행 정보 포함) (인사이드뱅크 전문번호 1200)
//
// *** ajax_shinhan_update.php 와 내용이 중첩된다!! 구분 필요함
###############################################################################

include_once("./_common.php");
include_once(G5_LIB_PATH."/register.lib.php");
include_once(G5_LIB_PATH."/insidebank.lib.php");


//check_demo();
auth_check($auth[$sub_menu], 'w');
//check_admin_token();

$mb_no = $_POST['mb_no'];


if(!$mb_no) {
	$RESULT_ARR = array('result'=>'FAIL', 'msg'=>'회원번호 오류');
	echo json_encode($RETURN_ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);  exit;
}

$sqlx = "
	SELECT
		mb_no, mb_id, mb_name, mb_co_name, mb_co_reg_num, mb_co_owner, mb_hp, mb_hp_ineb,
		member_type, is_creditor, bank_name, bank_code, bank_private_name,
		account_num, account_num_ineb, va_bank_code2, virtual_account2, va_private_name2
	FROM
		g5_member
	WHERE 1
		AND mb_no='".$mb_no."' AND mb_level IN('1','2','3','4','5') AND member_group='F'";
$MB = sql_fetch($sqlx);
if(!$MB['mb_no']) {
	$RESULT_ARR = array('result'=>'FAIL', 'msg'=>'회원정보 오류');
	exit;
}

if( !$MB['mb_no'] ) {
	$RETURN_ARR = array('result' => 'FAIL', 'msg' => '회원정보 오류');
	echo json_encode($RETURN_ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);  exit;
}

if( $MB['bank_code']=='' || $MB['account_num']=='' || $MB['bank_private_name']=='' ) {
	$RETURN_ARR = array('result' => 'FAIL', 'msg' => '환급계좌 미등록자 뱅킹정보 수정 불가');
	echo json_encode($RETURN_ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);  exit;
}

if( $MB['va_bank_code2']=='' || $MB['virtual_account2']=='' || $MB['va_private_name2']=='' ) {
	$RETURN_ARR = array('result' => 'FAIL', 'msg' => '가상계좌 미발급자는 수정 불가');
	echo json_encode($RETURN_ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);  exit;
}


if($MB['mb_hp'] || $MB['mb_hp_ineb']) {
	$MB['mb_hp'] = ($MB['mb_hp_ineb']) ? DGuardDecrypt($MB['mb_hp_ineb']) : masterDecrypt($MB['mb_hp'], false);
}

if($MB['account_num'] || $MB['account_num_ineb']) {
	$MB['account_num'] = ($MB['account_num_ineb']) ?  DGuardDecrypt($MB['account_num_ineb']) : masterDecrypt($MB['account_num'], false);
}

$MB['mb_co_name']    = trim($MB['mb_co_name']);
$MB['mb_co_owner']   = trim($MB['mb_co_owner']);
$MB['mb_co_reg_num'] = @preg_replace("/(-| )/", "", $MB['mb_co_reg_num']);

$CUST_ID = $MB['mb_no'];	// 고객아이디 = 자사회원고유번호


if($MB['member_type']=='2') {		//법인 사업자 설정

	$CUST_NM = ($CORP_NAME_CUSTOM) ? $CORP_NAME_CUSTOM : $MB['mb_co_name'];
	$CUST_NM = preg_replace("/ /","", trim($CUST_NM));

	if( preg_match("/주식회사/", $CUST_NM) ) {
		$CUST_NM = preg_replace("/주식회사/","", $CUST_NM);
		$CUST_NM = "(주)".$CUST_NM;
	}
	else if( preg_match("/(유한회사|유한책임회사)/", $CUST_NM) ) {
		$CUST_NM = preg_replace("/주식회사/","", $CUST_NM);
		$CUST_NM = "(유)".$CUST_NM;
	}

	if(strlen($CUST_NM) > 30) $CUST_NM = mb_substr($CUST_NM, 0, 10);			// (30Byte제한)10자리로 고정함

	$CUST_SUB_NM = $CUST_NM;
	$REP_NM      = $MB['mb_co_owner'];
	$SUP_REG_NB  = $MB['mb_co_reg_num'];
	$PRI_SUP_GBN = '2';

}
else {

	$CUST_NM = $MB['mb_name'];
	if($MB['is_creditor']=='Y' && $MB['mb_co_reg_num']) {		// 개인사업자 설정
		$CUST_SUB_NM = $MB['mb_name'];
		$SUP_REG_NB  = $MB['mb_co_reg_num'];
		$PRI_SUP_GBN = '2';
	}
	else {																									// 일반 개인 설정
		$CUST_SUB_NM = "";
		$SUP_REG_NB  = '';
		$PRI_SUP_GBN = '1';
	}

	// 주민번호 가져옴 (본 함수 사용전에 정상적인 데이터 유무를 확인할 필요 있음)
	$jumin = getJumin($mb_no);
	$jumin = substr($jumin, 0, 6);
	$BIRTH_DATE = (substr($jumin, 6, 1) > 2) ? '20'.$jumin : '19'.$jumin;	//생년월일

}

$KSNET_CORP_NAME = ($CORP_NAME_CUSTOM) ? $CORP_NAME_CUSTOM : $CUST_NM."(헬로펀딩)";		// KSNET용 업체명 (고객입금시 보여지는 예금주명)

$HP_NO1 = substr($MB['mb_hp'], 0, 3);
$HP_NO2 = substr($MB['mb_hp'], 3, -4);
$HP_NO3 = substr($MB['mb_hp'], -4);

$VA_BANK_CODE = '088';

$ACCT_NB = $MB['account_num'];


// 고객정보수정(1200) 전문 발송
$ARR['REQ_NUM']     = '010';										//전문번호
$ARR['SUBMIT_GBN']  = '02';											//거래구분 (01:등록|02:변경)
$ARR['CUST_ID']     = $CUST_ID;									//고객ID
$ARR['CUST_NM']     = $CUST_NM;									//고객명 (법인사업자는 사업자명)
$ARR['CUST_SUB_NM'] = $CUST_SUB_NM;							//고객부기명
$ARR['REP_NM']      = $REP_NM;									//대표자고객명
$ARR['BIRTH_DATE']  = $BIRTH_DATE;							//생년월일자 YYYYMMDD
$ARR['SUP_REG_NB']  = $SUP_REG_NB;							//사업자번호
$ARR['PRI_SUP_GBN'] = $PRI_SUP_GBN;							//개인사업자구분
$ARR['HP_NO1']      = $HP_NO1;									//휴대폰지역번호
$ARR['HP_NO2']      = $HP_NO2;									//휴대폰국번호
$ARR['HP_NO3']      = $HP_NO3;									//휴대폰일련번호
$ARR['BANK_CD']     = $MB['bank_code'];					//은행코드
$ARR['ACCT_NB']     = $ACCT_NB;									//은행계좌
$ARR['CMS_NB']      = $MB['virtual_account2'];	//가상계좌번호


$IB_RETURN_ARR = insidebank_request('256', $ARR);

if($IB_RETURN_ARR['RCODE']=='00000000') {

	// 본사 가상계좌원장 할당정보(예금주명) 수정
	$sql = "UPDATE IB_vact SET cmf_nm='".$CUST_NM."' WHERE CUST_ID='".$CUST_ID."' AND acct_no='".$MB['virtual_account2']."' AND acct_st='1'";
	//echo $sql . "<br>\n";
	$res = sql_query($sql);

	// KSNET 가상계좌원장 할당정보(예금주명) 수정
	$sql = "UPDATE KSNET_VR_ACCOUNT SET CORP_NAME='".$KSNET_CORP_NAME."' WHERE VR_ACCT_NO='".$MB['virtual_account2']."' AND USE_FLAG = 'Y'";
	//echo $sql . "<br>\n";
	$res = sql_query($sql);

	// 회원정보 테이블에 기록 (KSNET 가상계좌원장 정보에 기록된 이름으로 등록)
	$sql = "UPDATE g5_member SET va_private_name2='".$KSNET_CORP_NAME."' WHERE mb_no = '".$mb_no."'";
	//echo $sql . "<br>\n";
	$res = sql_query($sql);


	$RETURN_ARR = array('result' => 'SUCCESS', 'msg' => '');
	echo json_encode($RETURN_ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

}
else {

	$RETURN_ARR = array('result' => 'FAIL', 'msg' => $IB_RETURN_ARR['ERRMSG']);
	echo json_encode($RETURN_ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

}

sql_close();
exit;

?>