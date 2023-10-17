<?
###############################################################################
## 투자상품 상태값 변경처리
##		상품(투자) 진행 현황 설정값 변경
##		1:이자상환중 2:상환완료(투자종료) 3:투자금모집실패 4:부실 5:중도일시상환 6:대출실행취소
##		※ 제3자예치금관리 연계 대출상품등록의 수정은 ajax_invest_shinhan_proc.php 에서...
###############################################################################

set_time_limit(0);

$sub_menu = '700000';
include_once('./_common.php');

$g5['title'] = "투자상품상태값변경처리";
if($member['mb_level'] == '9') include_once(G5_ADMIN_PATH."/inc_sub_admin_access_check.php");		// 부관리자 접속로그 등록

include_once(G5_LIB_PATH.'/repay_calculation_new.php');		// 월별 정산내역 추출함수 호출
include_once(G5_LIB_PATH.'/insidebank.lib.php');

//ini_set('memory_limit','256M');
//print_rr($_POST); exit;

auth_check($auth[$sub_menu], 'w');
if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

include_once(G5_LIB_PATH.'/insidebank.lib.php');

// 올리고 레포팅 전송
function oligoSendReport($product_idx, $syndi_platform) {
	if($product_idx && $syndi_platform) {
		$PLATFORM = explode("|", $syndi_platform);
		if( in_array("oligo", $PLATFORM) )  {
			@shell_exec("/usr/local/php/bin/php -q " . G5_SYNDICATE_PATH . "/oligo/report/productStateReport.php " . $product_idx);
			$return_value = true;
		}
		else {
			$return_value = false;
		}
	}
	else {
		$return_value = false;
	}
	return $return_value;
}


$prd_idx      = trim($_POST['idx']);
$change_state = trim($_POST['state']);

if(!$prd_idx) { exit; }
if(!$change_state) { exit; }


$PRDT = sql_fetch("SELECT * FROM cf_product WHERE idx='".$prd_idx."'");
if(!$PRDT['idx']) { exit; }

$ib_trust = ($PRDT['ib_trust']=='Y' && $PRDT['ib_product_regist']=='Y') ? true : false;


// 올리고 투자유무 확인 (취소무관)
$TMP = sql_fetch("
	SELECT
		platform
	FROM
		cf_product
	WHERE 1
		AND display='Y' AND scrap_out='' AND isTest='' AND only_vip=''
		AND idx='".$prd_idx."'");

$platform = $TMP['platform'];


/////////////////////////////////////////////////
// state=1 : 대출실행 - 이자상환중 처리
// 10억을 초과한 투자금의 대출입금계좌는 10억단위로 분리하여야 한다. 단 신한은행계좌일 경우 10억이상도 가능
// 다른 투자상품간 대출지급계좌가 동일할 경우에는 정상대출이 된다.
/////////////////////////////////////////////////
if($change_state=='1') {

	// ▼ 제3자 예치금 관리시스템 적용 상품 처리 ----------------------
	if($ib_trust) {

		$start_date = new DateTime($_POST['date']);
		if($PRDT['invest_period']==1 && $PRDT['invest_days'] > 0) {
			$end_date = new DateTime(date("Y-m-d", strtotime($_POST['date']." +".$PRDT['invest_days']." day")));
		}
		else {
			$end_date = new DateTime(date("Y-m-d", strtotime($_POST['date']." +".$PRDT['invest_period']." month")));
		}
		$loan_end_date = $end_date->format('Y-m-d');

		// 차주정보
		$LMB  = sql_fetch("SELECT mb_id, mb_name, mb_co_name, member_type FROM g5_member WHERE mb_no='".$PRDT['loan_mb_no']."'");

		$LOAN_EXEC_DATE = preg_replace("/-| /", "", $_POST['date']);	// 대출실행일
		$LOAN_EXP_DATE  = preg_replace("/-| /", "", $loan_end_date);	// 대출만기일
		$LOAN_CUST_ID   = $PRDT['loan_mb_no'];												// 대출자 아이디는 회원번호로 설정함.
		$LOAN_CUST_NM   = ($LMB['member_type']=='2') ? $LMB['mb_co_name'] : $LMB['mb_name'];	// 대출자고객명
		$CMS_NB         = $PRDT['repay_acct_no'];											// 상환용 가상계좌번호

		$LOAN_DEP_CNT = 0;
		for($i=0,$j=1; $i<5; $i++,$j++) {
			if($PRDT['loan_dep_bank_cd'.$j] && $PRDT['loan_dep_acct_nb'.$j]) {
				$LOAN_DEP_CNT += 1;
			}
		}

		// 투자정보 업데이트 요청전문 (2500) 데이터 구성
		$INVEST  = sql_fetch("SELECT COUNT(idx) AS cnt, SUM(amount) AS amount FROM cf_product_invest WHERE product_idx='".$prd_idx."' AND invest_state='Y' AND ib_regist='1'");
		$INV_CNT = $INVEST['cnt'];

		$ARR['REQ_NUM']           = '020';											// 전문번호
		$ARR['SUBMIT_GBN']        = '05';												// 거래구분: 투자등록
		$ARR['LOAN_SEQ']          = $prd_idx;										// 대출식별번호
		$ARR['LOAN_AMT']          = $PRDT['recruit_amount'];		// 총대출금
		$ARR['LOAN_FEE']	        = 0;													// 취급수수료 (강제 0으로 처리 : 이상규대리 요청)  //$ARR['LOAN_FEE'] = (int)$PRDT['loan_usefee'];
		$ARR['LOAN_EXEC_DATE']    = $LOAN_EXEC_DATE;						// 대출실행일
		$ARR['LOAN_EXP_DATE']     = $LOAN_EXP_DATE;							// 대출만기일
		$ARR['LOAN_CUST_ID']      = $LOAN_CUST_ID;							// 대출자고객ID
		$ARR['LOAN_CUST_NM']      = $LOAN_CUST_NM;							// 대출자고객명
		$ARR['CMS_NB']            = $CMS_NB;										// 가상계좌번호 (모계좌 : 헬로크라우드대부 업체코드로 배당된 가상계좌)
		$ARR['LOAN_DEP_CNT']      = $LOAN_DEP_CNT;							// 대출입금계좌건수
		$ARR['INV_CNT']           = $INV_CNT;										// 투자자수
		for($i=0,$j=1; $i<5; $i++,$j++) {
			$ARR['LOAN_DEP_BANK_CD'.$j] = $PRDT['loan_dep_bank_cd'.$j];		// 대출금입금은행코드$j
			$ARR['LOAN_DEP_ACCT_NB'.$j] = $PRDT['loan_dep_acct_nb'.$j];		// 대출금입금계좌번호$j
			$ARR['LOAN_DEP_AMT'.$j]     = ($PRDT['loan_dep_amt'.$j] > 0) ? $PRDT['loan_dep_amt'.$j] : '';		// 대출금입금금액$j
		}

		// 다중차수 대출상품일 경우 첫회차 대출번호. 본 대출건이 최초대출이면 공백처리
		if($PRDT['gr_idx'] > 0 && $PRDT['idx'] > $PRDT['gr_idx']) {
			$INV_CUST_ID = $PRDT['gr_idx'];
		}
		else {
			$INV_CUST_ID = '';
		}
		$ARR['INV_CUST_ID'] = $INV_CUST_ID;

		// 대출실행(2300) 등록전문 데이터 구성
		$ARR2['REQ_NUM']    = "020";
		$ARR2['SUBMIT_GBN'] = "03";
		$ARR2['LOAN_SEQ']   = $prd_idx;		// 대출식별번호

		$RETURN_ARR2 = insidebank_request('256', $ARR2);		// 인사이드뱅크 대출실행 등록전문 (2300) 발송

		if($RETURN_ARR2['RCODE']!='00000000') {

			$RETURN_ARR = array('result'=>'FAIL', 'message'=>$RETURN_ARR2['ERRMSG']);
			echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); sql_close(); exit;

		}
		else {

			// 그룹 상품중 두번째 상품 대출실행시 첫번째 상품을 해당 그룹상품의 상환계좌 참조번호로 강제 설정한다. (참조번호 미설정시 발생할 입금처 미확인 사태를 방지하기 위함)
			$grp_prdt_count = sql_fetch("SELECT COUNT(idx) AS cnt FROM cf_product WHERE gr_idx='".$PRDT['gr_idx']."'");
			if($grp_prdt_count['cnt']==2) {
				$sql = "
					UPDATE
						KSNET_VR_ACCOUNT
					SET
						REF_NO='".$prd_idx."'
					WHERE 1
						AND USE_FLAG='Y'
						AND VR_ACCT_NO='".$CMS_NB."'";
				sql_query($sql);
			}

			$ib_loan_start = "S";		// 대출실행플래그(R:대기|S:실행됨|C:실행후취소됨)

			// 일별 이자,수수료 명세서 생성시작
			$exec_path   = "/usr/local/php/bin/php -q " . G5_ADMIN_PATH . "/repayment/make_bill_exec.php " . $prd_idx;
			$exec_result = shell_exec($exec_path);

		}

		unset($ARR2);
		unset($RETURN_ARR2);

	}
	// ▲ 제3자 예치금 관리시스템 적용 상품 처리 ----------------------


	$exceptionProduct = '';
	$shortTermProduct = ($PRDT['invest_period']==1 && $PRDT['invest_days'] > 0) ? true : false;

	$turn_cnt  = repayTurnCount($_POST['date'], $loan_end_date, $exceptionProduct, $shortTermProduct, $PRDT['calc_type']);
	$turn_cnt_orig  = $turn_cnt;

	$sql_add = ($ib_loan_start) ? ", ib_loan_start='$ib_loan_start'" : "";

	//상품정보값 수정
	$update_sql = "
		UPDATE
			cf_product
		SET
			state = '".$change_state."',
			loan_start_date = '".$_POST['date']."',
			loan_end_date   = '".$loan_end_date."',
			loan_end_date_orig = '".$loan_end_date."',
			turn_cnt = '".$turn_cnt."',
			turn_cnt_orig = '".$turn_cnt_orig."'
			$sql_add
		WHERE
			idx = '".$prd_idx."'";
	if( sql_query($update_sql) ) {

		$RETURN_ARR = array('result'=>'SUCCESS', 'message'=>'');
		echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);

		// 대출자 수수료 지급스케쥴 등록
		$exec_path2   = "/usr/local/php/bin/php -q " . G5_ADMIN_PATH . "/repayment/make_loaner_fee_collect_schedule.php " . $prd_idx;
		$exec_result2 = shell_exec($exec_path2);

		// 차주 이자 입금 안내 스케줄 생성
		$exec_path3    = "/usr/local/php/bin/php -q " . G5_ADMIN_PATH . "/mortgage/make_loaner_interest_sms_schedule.php " . $prd_idx;
		$exec_result3 = shell_exec($exec_path3);


		// 기표 안내 문자 차주에게 발송 (주담대 상품만) 2022-03-25 전차장
		if ($PRDT["category"]=="2" AND $PRDT["mortgage_guarantees"]=="1") {
			$exec_path4    = "/usr/local/php/bin/php -q " . G5_ADMIN_PATH . "/mortgage/chaju_sms.php " . $prd_idx . " 1";
			$exec_result4  = shell_exec($exec_path4);
		}

	}

}


/////////////////////////////////////////////////
// state=2 : 정상상환, 중도상환 (투자종료) 처리
/////////////////////////////////////////////////
else if( in_array($change_state, array('2','5')) ) {

	// 지급예상총액과 지급완료액간 차이가 있으면 STOP!!!
	$INV_ARR   = repayCalculationNew($prd_idx);
	$TOTAL_REPAY_SUM = $INV_ARR['TOTAL_REPAY_SUM'];
	$TOTAL_PAIED_SUM = $INV_ARR['TOTAL_PAIED_SUM'];
	unset($INV_ARR);

	$repay_total_amt = $TOTAL_REPAY_SUM['repay_principal'] + $TOTAL_REPAY_SUM['invest_interest'];
	$paid_total_amt  = $TOTAL_PAIED_SUM['repay_principal'] + $TOTAL_PAIED_SUM['invest_interest'];

	//echo json_encode(array('result'=>'FAIL', 'message'=>$repay_total_amt . ' : ' . $paid_total_amt), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;

	if($prd_idx!='3433' && ($repay_total_amt > $paid_total_amt)) {

		$fail_msg = "지급예상총액 : " . number_format($repay_total_amt) . "원\n";
		$fail_msg.= "지급완료총액 : " . number_format($paid_total_amt) . "원\n\n";
		$fail_msg.= "지급된 총액이 지급예상총액 보다 부족함";

		$RETURN_ARR = array(
			'result'=>'FAIL',
			'message'=> $fail_msg
		);
		echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;

	}

	// ▼ 제3자 예치금 관리시스템 적용 상품 처리 ----------------------
	if($ib_trust) {
		$loan_exp_date = preg_replace("/(-| )/", "", $PRDT['loan_end_date']);

		$ARR['REQ_NUM']				= "020";
		$ARR['SUBMIT_GBN']		= "08";											// 거래구분	(대출상환완료:08)
		$ARR['LOAN_SEQ']			= $prd_idx;									// 대출식별번호
		$ARR['LOAN_AMT']			= $PRDT['recruit_amount'];	// 대출상환금액 (총대출금)
		$ARR['LOAN_EXP_DATE'] = $loan_exp_date;						// 대출상환일자 (대출만기일)

		$RETURN_ARR = insidebank_request('256', $ARR);		// 대출상환완료 요청전문(2700) 발송

		if($RETURN_ARR['RCODE']!='00000000') {
			$RETURN_ARR = array('result'=>'FAIL', 'message'=>$RETURN_ARR['ERRMSG']);
			echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;
		}
	}
	// ▲ 제3자 예치금 관리시스템 적용 상품 처리 ----------------------

	//상품정보값 수정
	$sql_add = ($change_state=='5') ? ", loan_end_date = '".$PRDT['loan_end_date']."'" : "";		// 중도상환일 경우 대출종료일 변경

	$update_sql = "
		UPDATE
			cf_product
		SET
			 state = '".$change_state."',
			 down_date = CURDATE()
			$sql_add
		WHERE
			idx = '".$prd_idx."'";

	if( sql_query($update_sql) ) {

		if($change_state) {
			// 중도상환인 경우 대출자 수수료 지급스케쥴 수정
			$exec_path2   = "/usr/local/php/bin/php -q " . G5_ADMIN_PATH . "/repayment/make_loaner_fee_collect_schedule.php " . $prd_idx;
			$exec_result2 = shell_exec($exec_path2);
		}

		$RETURN_ARR = array('result'=>'SUCCESS', 'message'=>'');
		echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);

		// 상환 완료 문자 차주에게 발송 (주담대 상품만) 2022-03-30 전차장
		if ($PRDT["category"]=="2" AND $PRDT["mortgage_guarantees"]=="1") {
			$exec_path4    = "/usr/local/php/bin/php -q " . G5_ADMIN_PATH . "/mortgage/chaju_sms.php " . $prd_idx . " 2";
			$exec_result4  = shell_exec($exec_path4);
		}

	}

}


/////////////////////////////////////////////////
// state=3 : 투자금 모집실패
// state=6 : 기표전 대출취소 처리
// state=7 : 기표후 대출취소 처리
// 예치금 전체 반환 처리함.
/////////////////////////////////////////////////
else if( in_array($change_state, array('3','6','7')) ) {

	$sql = "
		SELECT
			A.idx, A.member_idx, A.amount, A.prin_rcv_no, A.ib_regist,
			B.mb_id
		FROM
			cf_product_invest A
		LEFT JOIN
			g5_member B  ON A.member_idx = B.mb_no
		WHERE 1
			AND A.product_idx = '".$prd_idx."'
			AND A.invest_state = 'Y'";
	$res = sql_query($sql);

	$po_content = $PRDT['title'] . "-투자금 반환";

	while( $INVEST = sql_fetch_array($res) ) {

		$resA = sql_query("UPDATE cf_product_invest        SET invest_state = 'R', cancel_date = NOW() WHERE idx = '".$INVEST['idx']."'");
		$resB = sql_query("UPDATE cf_product_invest_detail SET invest_state = 'R', cancel_date = NOW() WHERE invest_idx = '".$INVEST['idx']."'");

		// ▼ 제3자 예치금 관리시스템 적용 상품 처리 ----------------------
		if($ib_trust) {
			if($resA && $INVEST['ib_regist']=='1') {
				$ARR['REQ_NUM']     = "020";
				$ARR['SUBMIT_GBN']  = "07";												// 거래구분	(변경:06, 취소:07)
				$ARR['LOAN_SEQ']    = $prd_idx;										// 대출식별번호
				$ARR['INV_SEQ']     = $INVEST['idx'];							// 투자자건수일련번호(변경불가항목)
				$ARR['INV_CUST_ID'] = $INVEST['member_idx'];			// 투자자고객ID
				$ARR['PRIN_RCV_NO'] = $INVEST['prin_rcv_no'];			// 원리금수취권번호
				$ARR['INV_AMT']     = $INVEST['amount'];					// 투자금액
				$RETURN_ARR = insidebank_request('256', $ARR);		// 투자자취소(2600)

				// 투자금은 예치금으로 반환
				if($RETURN_ARR['RCODE']=='00000000') {
					insert_point($INVEST['mb_id'], $INVEST['amount'], $po_content, '@return', $INVEST['mb_id'], $member['mb_id'].'-'.uniqid(''), $config['cf_point_term']);
				}
			}
		}
		else {
			if($resA) {
				insert_point($INVEST['mb_id'], $INVEST['amount'], $po_content, '@return', $INVEST['mb_id'], $member['mb_id'].'-'.uniqid(''), $config['cf_point_term']);
			}
		}

	}		// end while( $INVEST = sql_fetch_array($res) )

	$sql_add = ($change_state=='6') ? ", cancel_date=NOW()" : "";

	//상품정보값 수정
	$update_sql = "
		UPDATE
			cf_product
		SET
			state = '".$change_state."'
			$sql_add
		WHERE
			idx = '".$prd_idx."'";

	if( sql_query($update_sql) ) {

		// ▼ 제3자 예치금 관리시스템 적용 상품 처리 ----------------------
		if($ib_trust) {
			if($change_state=='7') {
				$loan_exp_date = preg_replace("/(-| )/", "", $PRDT['loan_start_date']);

				// 기대출건 취소시 2700 전문 발송 : 대출상환금액을 0으로 셋팅. 대출상환일자는 대출실행일자로 셋팅
				$ARR2['REQ_NUM']		= "020";
				$ARR2['SUBMIT_GBN']	= "08";						// 거래구분	(대출상환완료:08)
				$ARR2['LOAN_SEQ']		= $prd_idx;				// 대출식별번호
				$ARR2['LOAN_AMT']		= "0";						// 대출상환금액
				$ARR2['LOAN_EXP_DATE']= $loan_exp_date;	// 대출상환일자->대출실행일
				$RETURN_ARR2 = insidebank_request('256', $ARR2);  // 대출취소 요청전문(2400) 발송 -> 투자금 반환은 자동으로 처리됨
			}

			// 대출취소(2400)
			$ARR3['REQ_NUM']    = "020";
			$ARR3['SUBMIT_GBN'] = "04";
			$ARR3['LOAN_SEQ']   = $prd_idx;		// 대출식별번호
			$RETURN_ARR3 = insidebank_request('256', $ARR3);  // 대출취소 요청전문(2400) 발송 -> 투자금 반환은 자동으로 처리됨
		}
		// ▲ 제3자 예치금 관리시스템 적용 상품 처리 ----------------------

		$RETURN_ARR = array('result'=>'SUCCESS', 'message'=>'');
		echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);

	}

}

/////////////////////////////////////////////////
// state=8 : 연체
/////////////////////////////////////////////////
else if($change_state=='8') {

	$turn       = trim($_POST['turn']);
	$start_date = trim($_POST['start_date']);

	$prdt_update_sql = "
		UPDATE
			cf_product
		SET
			state = '".$change_state."',
			overdue_start_date = '".$start_date."',
			overdue_turn = '".$turn."'
		WHERE
			idx = '".$prd_idx."'";
	$res = sql_query($prdt_update_sql);

	$SUCC = sql_fetch("SELECT idx, overdue_start_date FROM cf_product_success WHERE product_idx = '".$prd_idx."' AND turn = '".$turn."' AND turn_sno = '0'");		// 기준회차(정상상환)회차 레코드에만 연체일을 등록하도록 수정 (2022-02-09일 turn_sno = 0 조건절 추가)
//$SUCC = sql_fetch("SELECT idx, overdue_start_date FROM cf_product_success WHERE product_idx = '".$prd_idx."' AND turn = '".$turn."'");	// 2022-02-09일 이전까지 사용

	if($SUCC['idx']) {
		$sql = "
			UPDATE
				cf_product_success
			SET
				overdue_start_date = '".$start_date."'
			WHERE
				idx = '".$SUCC['idx']."'";
	}
	else {
		$sql = "
			INSERT INTO
				cf_product_success
			SET
				product_idx = '".$prd_idx."',
				turn = '".$turn."',
				overdue_start_date = '".$start_date."',
				date = CURRENT_DATE()";
	}
	$res2 = sql_query($sql);

	if($res && $res2) {
		$RETURN_ARR = array('result'=>'SUCCESS', 'message'=>'');
		echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
	}
	else {
		$RETURN_ARR = array('result'=>'FAIL', 'message'=>'이미 등록된 연체기록이 존재함.');
		echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
	}

}

/////////////////////////////////////////////////
// state=4 : 부실 처리
/////////////////////////////////////////////////
else if($change_state=='4') {

	$update_sql = "
		UPDATE
			cf_product
		SET
			state = '".$change_state."',
			fault_date = NOW()
		WHERE
			idx = '".$prd_idx."'";

	if( sql_query($update_sql) ) {
		$RETURN_ARR = array('result'=>'SUCCESS', 'message'=>'');
		echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
	}

}


if($change_state) {
	oligoSendReport($prd_idx, $platform);		// 올리고 레포팅
}

sql_close();

exit;

?>