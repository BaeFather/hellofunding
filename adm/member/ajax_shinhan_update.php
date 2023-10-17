<?
///////////////////////////////////////////////////////////////////////////////
// 은행에 등록된 투자회원 기본정보 변경 (인사이드뱅크 전문번호 1200)
//
// *** ajax_shinhan_member_update.php 와 내용이 중첩된다!! 구분 필요함
///////////////////////////////////////////////////////////////////////////////
include_once("./_common.php");
include_once(G5_PATH . "/lib/insidebank.lib.php");

$mb_no = trim($_REQUEST['mb_no']);

$ROW = sql_fetch("SELECT mb_id FROM g5_member WHERE mb_no='".$mb_no."' AND mb_level IN('1','2','3','4','5') AND member_group='F'");

$MB = get_member($ROW['mb_id']);
//print_rr($MB, 'font-size:11px');

if($MB['mb_no']) {

	$HP_NO1  = substr($MB['mb_hp'], 0, 3);
	$HP_NO2  = (strlen($MB['mb_hp']) > 10) ? substr($MB['mb_hp'], 3, -4) : substr($MB['mb_hp'], 3, -3);
	$HP_NO3  = substr($MB['mb_hp'], -4);

	$BANK_CD = $MB['bank_code'];
	$ACCT_NB = $MB['account_num'];
	$CMS_NB  = $MB['virtual_account2'];

	if($MB['member_type']=='2') {

		//법인회원 --------------------

		if($MB['mb_id']=='KJHInvest1019') $CORP_NAME_CUSTOM = "(주) 케이제이에이치인베";		// 업체명 예외처리

		$CUST_NM = ($CORP_NAME_CUSTOM) ? $CORP_NAME_CUSTOM : $MB['mb_co_name'];
		$CUST_NM = preg_replace("/ /","", trim($CUST_NM));

		if( preg_match("/주식회사/", $CUST_NM) ) {
			$CUST_NM = preg_replace("/주식회사/", "", $CUST_NM);
			$CUST_NM = "(주)".$CUST_NM;
		}
		else if( preg_match("/(유한회사|유한책임회사)/", $CUST_NM) ) {
			$CUST_NM = preg_replace("/(유한회사|유한책임회사)/", "", $CUST_NM);
			$CUST_NM = "(유)".$CUST_NM;
		}

		if(strlen($CUST_NM) > 30) $CUST_NM = mb_substr($CUST_NM, 0, 10);			// (30Byte제한)10자리로 고정함


		$CUST_ID     = $MB['mb_no'];
		$CUST_NM     = $CUST_NM;
		$CUST_SUB_NM = $CUST_NM;
		$REP_NM      = $MB['mb_co_owner'];
		$BIRTH_DATE  = '';
		$SUP_REG_NB  = @preg_replace("/(-| )/", "", $MB['mb_co_reg_num']);
		$PRI_SUP_GBN = '2';

	}
	else {

		//개인회원 --------------------

		if($MB['is_creditor']=='Y' && $MB['mb_co_reg_num']) {		// 개인사업자 설정
			$CUST_SUB_NM = $MB['mb_name'];
			$SUP_REG_NB  = $MB['mb_co_reg_num'];
			$PRI_SUP_GBN = '2';
		}
		else {		// 일반 개인 설정
			$CUST_SUB_NM = "";
			$SUP_REG_NB  = '';
			$PRI_SUP_GBN = '1';
		}

		$jumin = getJumin($mb_no);
		$jumin = substr($jumin, 0, 6);
		$BIRTH_DATE = (substr($jumin, 6, 1) > 2) ? '20'.$jumin : '19'.$jumin;	// 생년월일


		$CUST_ID     = $MB['mb_no'];
		$CUST_NM     = $MB['mb_name'];
		$CUST_SUB_NM = ($MB['is_creditor']=='Y' && $MB['mb_co_reg_num']) ? $MB['mb_name'] : '';							// 부기명 : 개인사업자 및 일반개인
		$REP_NM      = '';
		$BIRTH_DATE  = $BIRTH_DATE;
		$SUP_REG_NB  = ($MB['is_creditor']=='Y' && $MB['mb_co_reg_num']) ? $MB['mb_co_reg_num'] : '';				// 사업자번호 : 개인사업자 및 일반개인
		$PRI_SUP_GBN = ($MB['is_creditor']=='Y' && $MB['mb_co_reg_num']) ? '2' : '1';												// 개인사업자구분

	}

	// 고객정보수정(1200) 전문 발송
	$ARR['REQ_NUM']     = '010';						// 전문번호
	$ARR['SUBMIT_GBN']  = '02';							// 거래구분 (02:변경)
	$ARR['CUST_ID']     = $CUST_ID;					// 고객ID
	$ARR['CUST_NM']     = $CUST_NM;					// 고객명
	$ARR['CUST_SUB_NM'] = $CUST_SUB_NM;			// 고객부기명
	$ARR['REP_NM']      = $REP_NM;					// 대표자고객명
	$ARR['BIRTH_DATE']  = $BIRTH_DATE;			// 생년월일자 YYYYMMDD
	$ARR['SUP_REG_NB']  = $SUP_REG_NB;			// 사업자번호
	$ARR['PRI_SUP_GBN'] = $PRI_SUP_GBN;			// 개인사업자구분
	$ARR['HP_NO1']      = $HP_NO1;					// 휴대폰지역번호
	$ARR['HP_NO2']      = $HP_NO2;					// 휴대폰국번호
	$ARR['HP_NO3']      = $HP_NO3;					// 휴대폰일련번호
	$ARR['BANK_CD']     = $BANK_CD;					// 은행코드
	$ARR['ACCT_NB']     = $ACCT_NB;					// 은행계좌(환급계좌)
	$ARR['CMS_NB']      = $CMS_NB;					// 가상계좌번호

	$RETURN_ARR = insidebank_request('256', $ARR);

	// (테스트서버에서만) 수정전문 실패시 등록전문 실행 (신한은행측 DB초기화에 대응하기 위함)
	if( preg_match("/dev\.hellofunding/", $_SERVER['HTTP_HOST']) ) {
		if($RETURN_ARR['RCODE']!='00000000') {
			$ARR['SUBMIT_GBN']  = '01';							// 거래구분 (01:등록)
			$RETURN_ARR = insidebank_request('256', $ARR);
		}
	}


	if($RETURN_ARR['RCODE']=='00000000') {
		$value = array('result' => 'success', 'message' => '');
	}
	else {
		$value = array('result' => 'fail', 'message' => $RETURN_ARR['ERRMSG']);		// 딱히 자세한 오류는 안준다.
	}

	echo json_encode($value, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

}


sql_close();

?>