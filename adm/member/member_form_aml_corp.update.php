<?
###############################################################################
## 법인회원 AML자료 등록
###############################################################################

include_once("./_common.php");
if($member['mb_level'] == '9') include_once(G5_ADMIN_PATH."/inc_sub_admin_access_check.php");		// 부관리자 접속로그 등록

while( list($k, $v) = each($_POST) ) { if(!is_array(${$k})) ${$k} = trim($v); }

include_once(G5_PATH . '/data/aml_inc/kofiu_code.inc.php');
include_once(G5_PATH . '/data/aml_inc/aml_array.inc.php');


$MB_TABLE  = "g5_member";
$AML_TABLE = "g5_member_aml_corp";

$MB = sql_fetch("SELECT * FROM {$MB_TABLE} WHERE mb_no = '".$mb_no."'");
if(!$MB['mb_no']) { msg_replace('등록된 회원정보가 없습니다!'); }



$AML = sql_fetch("SELECT * FROM {$AML_TABLE} WHERE mb_no = '".$MB['mb_no']."' ORDER BY reg_dt DESC LIMIT 1");
$mode = ($AML['mb_no']) ? 'edit' : 'new';


if(!$AML['order_id']) {
	$ARR['order_id'] = "ADMIN-".strtoupper(uniqid());
}

$ARR['mb_no'] = $mb_no;
$ARR['mb_id'] = ($ARR['mb_id']) ? $ARR['mb_id'] : $MB['mb_id'];


//print_rr($_POST, 'font-size:12px;line-height:14px;');

if($MB['corp_forigner']) {
	if($COUNTRY_CD == 'KR') msg_replace('해외법인은 대한민국외 국가를 선택하십시요.');
}


// 직접입력필드 escape string
if($CUSTOMER_NM)     $CUSTOMER_NM = sql_real_escape_string($CUSTOMER_NM);
if($CUSTOMER_ENG_NM) $CUSTOMER_ENG_NM = sql_real_escape_string($CUSTOMER_ENG_NM);

if($CEO_NM)           $CEO_NM = sql_real_escape_string($CEO_NM);
if($CEO_ENG_FIRST_NM) $CEO_ENG_FIRST_NM = strtoupper(sql_real_escape_string($CEO_ENG_FIRST_NM));
if($CEO_ENG_LAST_NM)  $CEO_ENG_LAST_NM = strtoupper(sql_real_escape_string($CEO_ENG_LAST_NM));
$CEO_ENG_NM = trim($CEO_ENG_LAST_NM . ' ' . $CEO_ENG_FIRST_NM);

if($TRAN_FUND_SOURCE_OTHER) $TRAN_FUND_SOURCE_OTHER = sql_real_escape_string($TRAN_FUND_SOURCE_OTHER);
if($ACCOUNT_NEW_PURPOSE_OTHER) $ACCOUNT_NEW_PURPOSE_OTHER = sql_real_escape_string($ACCOUNT_NEW_PURPOSE_OTHER);





$ARR['TMS_CUSTOMER_DIV']          = $TMS_CUSTOMER_DIV;															// 고객구분(01:개인 02:법인 03:개인사업자)
$ARR['CUSTOMER_TP_CD']            = $CUSTOMER_TP_CD;																// 고객유형코드(01:비영리단체 02:고액자산가 03:신용불량자 04:금융기관 05:국가.지방자치단체 06:UN산하 국제자선기구 07:상장회사 08:기타)
$ARR['VIRTUAL_MONEY_BUSINESS_YN'] = $VIRTUAL_MONEY_BUSINESS_YN;											// 가상통화취급사업자여부
$ARR['VIRTUAL_MONEY_HANDLE_CD']   = $VIRTUAL_MONEY_HANDLE_CD;												// 가상통화취급여부
$ARR['CUSTOMER_NM']               = $CUSTOMER_NM;																		// 법인명
$ARR['CUSTOMER_ENG_NM']           = $CUSTOMER_ENG_NM;																// 법인명(영문)
$ARR['AGENT_YN']                  = 'N';																						// 대리인여부
$ARR['RNM_NO_DIV']                = $RNM_NO_DIV;																		// 실명번호구분
$ARR['RNM_NO']                    = $RNM_NO;																				// 실명번호
$ARR['PERMIT_NO']                 = $PERMIT_NO;																			// 사업자등록번호
$ARR['CREATE_DD']                 = $CREATE_DD;																			// 법인설립일
$ARR['NONPROFIT_CORP_YN']         = $NONPROFIT_CORP_YN;															// 비영리법인여부
$ARR['NONPROFIT_CORP_REG_YN']     = $NONPROFIT_CORP_REG_YN;													// 비영리법인등록여부
$ARR['CORP_REG_NO']               = $CORP_REG_NO;																		// 법인등록번호
$ARR['COUNTRY_CD']                = $COUNTRY_CD;																		// 법인국적코드
$ARR['LIVE_COUNTRY_CD']           = $LIVE_COUNTRY_CD;																// 소재국가코드
$ARR['FOREIGNER_DIV']             = $FOREIGNER_DIV;																	// 법인속성(내.외국 구분)

if($CEO_ADDR_COUNTRY_CD=='KR') {
	$LIVE_YN = 'Y';
}
else {
	$LIVE_YN = ($CEO_ADDR_COUNTRY_CD) ? 'N' : '';
}

$ARR['LIVE_YN']                   = $LIVE_YN;																				// 국내거주여부
$ARR['INDUSTRY_CD']               = $INDUSTRY_CD;																		// 업종코드
$ARR['LSTNG_YN']                  = $LSTNG_YN;																			// 상장여부
$ARR['LSTNG_DIV']                 = $LSTNG_DIV;																			// 상장구분(01:유가증권시장 02:코스닥시장 03:뉴욕증권거래소 04:NASDAQ  05:런던증권거래소 06:홍콩증권거래소 99:기타)
$ARR['CEO_NM']                    = $CEO_NM;																				// 대표자 성명
$ARR['CEO_ENG_NM']                = $CEO_ENG_NM;																		// 대표자 영문명
$ARR['CEO_ENG_FIRST_NM']          = $CEO_ENG_FIRST_NM;															// 대표자 영문명: 이름
$ARR['CEO_ENG_LAST_NM']						= $CEO_ENG_LAST_NM;																// 대표자 영문명: 성

$ARR['CEO_COUNTRY_CD']            = $CEO_COUNTRY_CD;																// 대표자 국적코드
$ARR['MAND_VERIF_PAPER_REG_YN']   = ($MB['all_doc_check_yn']=='1') ? 'Y' : 'N';			// 필수 검증문서 등록 여부
$ARR['AML_RA_CHANNEL_CD']         = $AML_RA_CHANNEL_CD;															// 접근경로 (01:대면 02:전화 03:모바일/인터넷)

//////////////////////
// CDD
//////////////////////
$ARR['CEO_RNM_NO_DIV']      = $CEO_RNM_NO_DIV;								// 대표자 실명번호구분
$ARR['CEO_RNM_NO']          = $CEO_RNM_NO;										// 대표자 실명번호
$ARR['CEO_ADDR_COUNTRY_CD'] = $CEO_ADDR_COUNTRY_CD;
$ARR['CEO_POST_NO']         = $CEO_POST_NO;										// 대표자 우편번호
$ARR['CEO_ADDR']            = $CEO_ADDR;
$ARR['CEO_ADDR_jibeon']     = $CEO_ADDR_jibeon;
$ARR['CEO_DTL_ADDR']        = $CEO_DTL_ADDR;									// 대표자 주소 상세

//대표자 주소표기 구분 (KR:도로명 KS:지번 KZ:기타)
//$ARR['CEO_ADDR_DISPLAY_DIV'] = 'KZ';		// 기타
if($CEO_ADDR) {
	$ARR['CEO_ADDR_DISPLAY_DIV'] = 'KR';		// 도로명주소
}
else if($CEO_ADDR_jibeon) {
	$ARR['CEO_ADDR_DISPLAY_DIV'] = 'KS';		// 지번주소
}


//////////////////////
// EDD
//////////////////////
$ARR['COMPANY_SIZE_DIV']         = $COMPANY_SIZE_DIV;							// 기업규모
$ARR['CREATE_COUNTRY_CD']        = $CREATE_COUNTRY_CD;						// 법인설립지국가
$ARR['TRAN_FUND_SOURCE_DIV']     = $TRAN_FUND_SOURCE_DIV;					// 거래자금출처코드
$ARR['TRAN_FUND_SOURCE_NM']		   = $TRAN_FUND_SOURCE_NM;					// 거래자금출처명
$ARR['TRAN_FUND_SOURCE_OTHER']   = $TRAN_FUND_SOURCE_OTHER;				// 거래자금출처 직접입력
$ARR['ACCOUNT_NEW_PURPOSE_CD']   = $ACCOUNT_NEW_PURPOSE_CD;				// 거래목적코드
$ARR['ACCOUNT_NEW_PURPOSE_NM']   = $ACCOUNT_NEW_PURPOSE_NM;				// 거래목적명
$ARR['ACCOUNT_NEW_PURPOSE_OTHER']= $ACCOUNT_NEW_PURPOSE_OTHER;		// 거래목적 직접입력
$ARR['REAL_OWNR_CHK_CD']         = $REAL_OWNR_CHK_CD;							// 법인실소유자구분 선택


$aml_data_count = count($AML);
if($aml_data_count) {
	$AMLKEY = array_keys($AML);
	for($i=0; $i<$aml_data_count; $i++) {
		// DB자료와 신규입력자료가 동일하면 배열에서 제외
		if( isset($AML[$AMLKEY[$i]]) && ($AML[$AMLKEY[$i]]==$ARR[$AMLKEY[$i]]) ) {
			unset($ARR[$AMLKEY[$i]]);
		}
	}
}

/////////////////////////////////////
// DB처리
/////////////////////////////////////
$arr_count = count($ARR);
$ARRKEY = array_keys($ARR);

if($mode == 'new') {

	////////////////////
	// 신규등록
	////////////////////

	$sqlx = "INSERT INTO {$AML_TABLE} SET ";
	for($k=0,$n=1; $k<$arr_count; $k++,$n++) {
		$sqlx.= $ARRKEY[$k]."='".$ARR[$ARRKEY[$k]]."'";
		$sqlx.= ($n < $arr_count) ? ", " : ", reg_dt='".date('Y-m-d H:i:s')."'";
		$sqlx.= "\n";
	}

	if( $resx = sql_query($sqlx) ) {
		// KYC 심사중인 상태로 만들어 두기
		$mbsql = "
			UPDATE
				g5_member
			SET
				kyc_order_id = '".$ARR['order_id']."',
				kyc_reg_dd   = '".date('Y-m-d')."',
				kyc_allow_yn = 'W',
				edit_datetime = NOW()
			WHERE
				mb_no = '".$mb_no."'";
		if( $resx2 = sql_query($mbsql) ) {
			member_edit_log($mb_no);
			$msg = 'AML자료 신규등록 완료';
		}
		else {
			$msg = "DB처리오류 - INSERT g5member";
			msg_replace($msg);
		}
	}
	else {
		$msg = "DB처리오류 - INSERT {$AML_TABLE}";
		msg_replace($msg);
	}

}
else if($mode == 'edit') {

	////////////////////
	// 정보수정
	////////////////////

	if($arr_count) {

		$sqlx = "UPDATE {$AML_TABLE} SET\n";
		for($k=0,$n=1; $k<$arr_count; $k++,$n++) {
			$sqlx.= $ARRKEY[$k]."='".$ARR[$ARRKEY[$k]]."'";
			$sqlx.= ($n < $arr_count) ? ", " : ", edit_dt='".date('Y-m-d H:i:s')."'";
			$sqlx.= "\n";
		}
		$sqlx.= " WHERE mb_no='".$mb_no."'";
		if( $resx = sql_query($sqlx) ) {
			$msg = 'AML자료 수정 완료';
		}

	}
	else {
		$msg = "데이터 변동사항 없음!";
		msg_replace($msg);
	}

}


sql_close();

//print_rr($sqlx, 'font-size:12px;line-height:14px;'); exit;
msg_reload($msg, 'top');


exit;

?>