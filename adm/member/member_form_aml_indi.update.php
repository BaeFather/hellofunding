<?
###############################################################################
## 개인회원 AML자료 등록
###############################################################################

include_once("./_common.php");
if($member['mb_level'] == '9') include_once(G5_ADMIN_PATH."/inc_sub_admin_access_check.php");		// 부관리자 접속로그 등록

while( list($k, $v) = each($_POST) ) { if(!is_array(${$k})) ${$k} = trim($v); }

include_once(G5_PATH . '/data/aml_inc/kofiu_code.inc.php');
include_once(G5_PATH . '/data/aml_inc/aml_array.inc.php');


$MB_TABLE  = "g5_member";
$AML_TABLE = "g5_member_aml_indi";

$MB = sql_fetch("SELECT * FROM {$MB_TABLE} WHERE mb_no = '".$mb_no."'");
if(!$MB['mb_no']) { msg_replace('등록된 회원정보가 없습니다!'); }
$MB['mb_hp'] = masterDecrypt($MB['mb_hp'], false);
$MB['jumin'] = getJumin($MB['mb_no']);

$AML = sql_fetch("SELECT * FROM {$AML_TABLE} WHERE mb_no = '".$MB['mb_no']."' ORDER BY reg_dt DESC LIMIT 1");
$mode = ($AML['mb_no']) ? 'edit' : 'new';


if(!$AML['order_id']) {
	$ARR['order_id'] = "ADMIN-".strtoupper(uniqid());
}

$ARR['mb_no'] = $mb_no;
$ARR['mb_id'] = ($ARR['mb_id']) ? $ARR['mb_id'] : $MB['mb_id'];


//print_rr($_POST, 'font-size:12px;line-height:14px;');

//if($MB['corp_forigner']) {
//	if($COUNTRY_CD == 'KR') msg_replace('해외법인은 대한민국외 국가를 선택하십시요.');
//}


// 국내거주여부
$LIVE_YN = '';
if($HOME_ADDR_COUNTRY_CD) {
	$LIVE_YN = ($HOME_ADDR_COUNTRY_CD=='KR') ? 'Y' : 'N';
}

//거주.비거주.내국인.외국인구분:: 01:비거주외국인 02:비거주내국인 03:거주외국인 04:거주내국인
$AML_LIVE_DIV = '04';
if($FOREIGNER_DIV=='A') {
	$AML_LIVE_DIV = ($LIVE_YN=='Y') ? '04' : '02';
}
else if($FOREIGNER_DIV=='B') {
	$AML_LIVE_DIV = ($LIVE_YN=='Y') ? '03' : '01';
}

// 주소표기구분: KZ:기타, KR:도로명주소, KS:지번주소
$HOME_ADDR_DISPLAY_DIV = '';
if($HOME_ADDR) {
	$HOME_ADDR_DISPLAY_DIV  = 'KR';
}
else if($HOME_ADDR_jibeon) {
	$HOME_ADDR_DISPLAY_DIV  = 'KS';
}

$WORK_ADDR_DISPLAY_DIV = '';
if($WORK_ADDR) {
	$WORK_ADDR_DISPLAY_DIV = 'KR';
}
else if($WORK_ADDR_jibeon) {
	$WORK_ADDR_DISPLAY_DIV = 'KS';
}


// 직접입력필드 escape string
if($CUSTOMER_NM)               $CUSTOMER_NM = sql_real_escape_string($CUSTOMER_NM);
if($CUSTOMER_ENG_NM)           $CUSTOMER_ENG_NM = sql_real_escape_string($CUSTOMER_ENG_NM);
if($REAL_OWNER_NM)             $REAL_OWNER_NM = sql_real_escape_string($REAL_OWNER_NM);
if($REAL_OWNER_ENG_NM)         $REAL_OWNER_ENG_NM = strtoupper(sql_real_escape_string($REAL_OWNER_ENG_NM));
if($TRAN_FUND_SOURCE_OTHER)    $TRAN_FUND_SOURCE_OTHER = sql_real_escape_string($TRAN_FUND_SOURCE_OTHER);
if($ACCOUNT_NEW_PURPOSE_OTHER) $ACCOUNT_NEW_PURPOSE_OTHER = sql_real_escape_string($ACCOUNT_NEW_PURPOSE_OTHER);

$RNM_NO = ($AML['RNM_NO'] && ($AML['RNM_NO']==$MB['jumin'])) ? $AML['RNM_NO'] : $MB['jumin'];		// 실명번호 (AES256)

$ARR['TMS_CUSTOMER_DIV']          = $TMS_CUSTOMER_DIV;															// 01:개인 03:개인사업자
$ARR['CUSTOMER_TP_CD']            = $CUSTOMER_TP_CD;																// 고객유형코드(01:비영리단체 02:고액자산가 03:신용불량자 04:금융기관 05:국가.지방자치단체 06:UN산하 국제자선기구 07:상장회사 08:기타)
$ARR['VIRTUAL_MONEY_BUSINESS_YN'] = ($VIRTUAL_MONEY_BUSINESS_YN) ? $VIRTUAL_MONEY_BUSINESS_YN : 'N';											// 가상통화취급사업자여부
$ARR['CUSTOMER_NM']               = $CUSTOMER_NM;																		// 법인명
$ARR['CUSTOMER_ENG_NM']           = $CUSTOMER_ENG_NM;																// 법인명(영문)
$ARR['AGENT_YN']                  = $AGENT_YN;																			// 대리인여부
$ARR['AGENT_RELA_DIV']            = $AGENT_RELA_DIV;																// 회원과의 관계 01:배우자 02:부모 03:자녀 04:형제자매 05:친척 06:상사 07:동료(친구) 08:기타(기재)
$ARR['AGENT_mb_id']               = $AGENT_mb_id;																		// 대리인 회원번호
$ARR['RNM_NO_DIV']                = '01';																						// 실명번호구분 01:주민등록번호(개인) 02:주민등록번호(기타단체) 03:사업자등록번호 04:여권번호 05:법인등록번호 06:외국인등록번호 07:재외국민거소신고번호 08:투자자등록번호 09:고유번호/납세번호 11:BIC코드(SWIFT) 12:해당국가법인번호 13:재정경제부문서번호 99:기타
$ARR['RNM_NO']                    = masterEncrypt($RNM_NO, false);									// 실명번호 (AES256)
$ARR['PERMIT_NO']                 = ($AML['PERMIT_NO'] && ($AML['PERMIT_NO']==$MB['mb_co_reg_num'])) ? $AML['PERMIT_NO'] : $MB['mb_co_reg_num'];		// 사업자등록번호
$ARR['COUNTRY_CD']                = $COUNTRY_CD;																		// 법인국적코드
$ARR['BIRTH_DD']                  = $BIRTH_DD;																			// 생년월일(yyyymmdd)
$ARR['SEX_CD']										= $SEX_CD;																				// 성별
$ARR['LIVE_YN']                   = $LIVE_YN;																				// 국내거주여부
$ARR['FOREIGNER_DIV']             = $FOREIGNER_DIV;																	// 내.외국 구분 : A:내국인 B:외국인
$ARR['AML_LIVE_DIV']              = $AML_LIVE_DIV;																	// 거주.비거주.내국인.외국인구분 : 01:비거주외국인 02:비거주내국인 03:거주외국인 04:거주내국인
$ARR['KOFIU_JOB_DIV_CD']          = $JOB_DIV_CD;																		// KOFIU_직업구분코드
//$ARR['BUSINESS_DTL_CD']           = '';
$ARR['LIVE_COUNTRY_CD']           = $HOME_ADDR_COUNTRY_CD;													// 거주국가코드
$ARR['INDV_INDUSTRY_CD']          = $INDV_INDUSTRY_CD;															// 업종코드
$ARR['AML_RA_CHANNEL_CD']         = $AML_RA_CHANNEL_CD;															// 접근채널코드
$ARR['MAND_VERIF_PAPER_REG_YN']   = ($MB['all_doc_check_yn']=='1') ? 'Y' : 'N';			// 필수 검증문서 등록 여부


//////////////////////
// CDD
//////////////////////
$ARR['REAL_OWNER_YN']         = $REAL_OWNER_YN;
$ARR['REAL_OWNER_RNM_NO_DIV'] = $REAL_OWNER_RNM_NO_DIV;
$ARR['REAL_OWNER_RNM_NO']     = ($REAL_OWNER_RNM_NO) ? masterEncrypt($REAL_OWNER_RNM_NO, false) : '';				// 실소유자 본인확인번호 AES256
$ARR['REAL_OWNER_NM']         = $REAL_OWNER_NM;
$ARR['REAL_OWNER_ENG_NM']     = $REAL_OWNER_ENG_NM;
$ARR['HOME_ADDR_COUNTRY_CD']  = $HOME_ADDR_COUNTRY_CD;
$ARR['HOME_ADDR_DISPLAY_DIV'] = $HOME_ADDR_DISPLAY_DIV;
$ARR['HOME_POST_NO']          = $HOME_POST_NO;
$ARR['HOME_ADDR']             = $HOME_ADDR;
$ARR['HOME_ADDR_jibeon']      = $HOME_ADDR_jibeon;
$ARR['HOME_DTL_ADDR']         = $HOME_DTL_ADDR;
$ARR['HOME_PHONE_NO']         = $HOME_PHONE_NO;
$ARR['CELL_PHONE_NO']         = ($CELL_PHONE_NO) ? masterEncrypt($CELL_PHONE_NO, false) : '';								// 실소유자 휴대전화번호 AES256



//////////////////////
// EDD
//////////////////////
$ARR['WORK_ADDR_COUNTRY_CD'] = $WORK_ADDR_COUNTRY_CD;
$ARR['WORK_ADDR_DISPLAY_DIV']= $WORK_ADDR_DISPLAY_DIV;
$ARR['WORK_POST_NO']         = $WORK_POST_NO;
$ARR['WORK_ADDR']            = $WORK_ADDR;
$ARR['WORK_ADDR_jibeon']     = $WORK_ADDR_jibeon;
$ARR['WORK_DTL_ADDR']        = $WORK_DTL_ADDR;
$ARR['WORK_AREA_PHONE_NO']   = $WORK_AREA_PHONE_NO;
$ARR['WORK_FAX_NO']          = $WORK_FAX_NO;

$ARR['REAL_OWNR_RNM_NO']     = ($REAL_OWNER_RNM_NO) ? masterEncrypt($REAL_OWNER_RNM_NO, false) : '';				// 실소유자 본인확인번호 AES256
$ARR['REAL_OWNR_NM']         = $REAL_OWNER_NM;


$ARR['TRAN_FUND_SOURCE_DIV']     = $TRAN_FUND_SOURCE_DIV;					// 거래자금출처코드
$ARR['TRAN_FUND_SOURCE_NM']		   = $TRAN_FUND_SOURCE_NM;					// 거래자금출처명
$ARR['TRAN_FUND_SOURCE_OTHER']   = $TRAN_FUND_SOURCE_OTHER;				// 거래자금출처 직접입력
$ARR['ACCOUNT_NEW_PURPOSE_CD']   = $ACCOUNT_NEW_PURPOSE_CD;				// 거래목적코드
$ARR['ACCOUNT_NEW_PURPOSE_NM']   = $ACCOUNT_NEW_PURPOSE_NM;				// 거래목적명
$ARR['ACCOUNT_NEW_PURPOSE_OTHER']= $ACCOUNT_NEW_PURPOSE_OTHER;		// 거래목적 직접입력

//print_rr($ARR, 'font-size:12px; line-height:14px;'); exit;

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