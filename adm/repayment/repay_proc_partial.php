<?
###############################################################################
## 정산처리 파일 - 원금일부상환
##
###############################################################################

set_time_limit(0);
ini_set('memory_limit','1024M');


$sub_menu = '700000';
include_once('./_common.php');

$g5['title'] = "정산처리(일부상환)";
if($member['mb_level'] == '9') include_once(G5_ADMIN_PATH."/inc_sub_admin_access_check.php");		// 부관리자 접속로그 등록


auth_check($auth[$sub_menu], 'w');
if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

include_once(G5_LIB_PATH.'/repay_calculation_new.php');		// 월별 정산내역 추출함수 호출
include_once(G5_LIB_PATH.'/insidebank.lib.php');


$action  = trim($_REQUEST['action']);
$prd_idx = trim($_REQUEST['idx']);											// 상품번호기준
$mb_id   = trim($_REQUEST['mb_id']);										// 특정 투자자만 조회 할 경우

if($_REQUEST['turn']) {
	$repay_turn     = trim($_REQUEST['turn']);
	$repay_arr_no   = $repay_turn - 1;
}
if($_REQUEST['turn_sno']) {
	$repay_turn_sno = trim($_REQUEST['turn_sno']);
	$sno_arr_no     = $repay_turn_sno - 1;		// 정렬번호의 배열번호
}
if($_REQUEST['date']) {
	$req_date = $_REQUEST['date'];
}

$INV_ARR   = repayCalculationNew($prd_idx, $mb_id);

$INI       = $INV_ARR['INI'];
$PRDT      = $INV_ARR['PRDT'];
$LOANER    = $INV_ARR['LOANER'];
$INVEST    = $INV_ARR['INVEST'];
$REPAY     = $INV_ARR['REPAY'];
$REPAY_SUM = $INV_ARR['REPAY_SUM'];


$ib_trust = ($PRDT['ib_trust']=='Y' && $PRDT['ib_product_regist']=='Y') ? true : false;

if($ib_trust) {

	// 대출자 입금총액
	$DEPOSITED = sql_fetch("
		SELECT
			IFNULL(SUM(TR_AMT),0) AS TR_AMT
		FROM
			IB_FB_P2P_IP
		WHERE
			ACCT_NB = '".$PRDT['repay_acct_no']."'");

	// 해당상품의 투자자 상환총액
	$GIVED = sql_fetch("
		SELECT
			IFNULL(SUM(principal),0) AS principal,
			IFNULL(SUM(interest),0) AS interest,
			IFNULL(SUM(interest_tax),0) AS interest_tax,
			IFNULL(SUM(local_tax),0) AS local_tax,
			IFNULL(SUM(fee),0) AS fee
		FROM
			cf_product_give
		WHERE 1
			AND product_idx = '".$prd_idx."'
			AND banking_date IN NOT NULL");

	$gived_amount = $GIVED['principal'] + $GIVED['interest'] + $GIVED['interest_tax'] + $GIVED['local_tax'] + $GIVED['fee'];		// 해당상품의 투자자 상환총액
	$loaner_deposit_amount = $DEPOSITED['TR_AMT'];		// 해당상품의 상환계좌 잔액
//$loaner_deposit_amount = $DEPOSITED['TR_AMT'] - $gived_amount;		// 해당상품의 상환계좌 잔액
	$idle_money  = $loaner_deposit_amount - $gived_amount;						// 신탁계좌 여유잔액

}

if($repay_turn && $repay_turn_sno) {

	$TARGET_REPAY = $REPAY[$repay_arr_no]['PARTIAL'][$sno_arr_no];
	$repay_count  = count($TARGET_REPAY['LIST']);

	$FLAG = sql_fetch("
		SELECT
			idx, loan_principal_state, ib_request_ready, invest_give_state, invest_principal_give
		FROM
			cf_product_success
		WHERE 1
			AND product_idx = '".$prd_idx."' AND turn = '".$repay_turn."' AND turn_sno = '".$repay_turn_sno."'");

	$repay_amount = $TARGET_REPAY['SUM']['repay_principal'];	// 당회차 지급필요총액

}


if($action) {

	///////////////////////////////////////////////////////////////////////////////
	// 일부상환원금 수급완료 플래그
	///////////////////////////////////////////////////////////////////////////////
	if($action=='partial_principal_success') {

		if($ib_trust) {

			/*
			if($loaner_deposit_amount <= 0) {
				$RESULT_ARR = array('result' => 'ERROR', 'message' => "상환계좌 잔액이 없습니다.");
				echo json_encode($RESULT_ARR);
				exit;
			}

			// 신탁계좌 잔액 체크
			if($idle_money < $repay_amount) {
				$need_amount = ($idle_money - $repay_amount) * -1;

				$msg = "본 상품의 상환계좌 잔액이 부족합니다.\n\n";
				$msg.= "  계좌잔액: " . number_format($idle_money) . "원\n";
				$msg.= "  지급요청분: " . number_format($repay_amount) . "원\n";
				$msg.= "  부족분: " . number_format($need_amount) . "원\n";

				$RESULT_ARR = array('result' => 'ERROR', 'message' => $msg);
				echo json_encode($RESULT_ARR);
				exit;
			}
			*/

		}

		if($FLAG['idx']) {
			$sql = "UPDATE cf_product_success SET loan_principal_state = 'Y' WHERE idx = '".$FLAG['idx']."' AND loan_principal_state = ''";
		}
		else {
			$sql = "INSERT INTO cf_product_success (loan_principal_state, product_idx, turn, turn_sno, `date`) VALUES ('Y', '".$prd_idx."', '".$repay_turn."', '".$repay_turn_sno."', CURRENT_DATE())";
		}

		if( sql_query($sql) ) {
			$RESULT_ARR = array('result' => 'SUCCESS', 'message' => '');
			echo json_encode($RESULT_ARR);
		}

	}		// end if($action=='partial_principal_success')


	///////////////////////////////////////////////////////////////////////////////
	// 원금일부상환 배분요청데이터 준비
	// (IB_FB_P2P_REPAY_REQ_DETAIL 테이블에 원리금 지금요청 자료 등록)
	///////////////////////////////////////////////////////////////////////////////
	if($action=='partial_devide_ready') {

		if(!$ib_trust) {
			$RESULT_ARR = array('result' => 'ERROR', 'message' => '제3자 예치시스템이 적용된 대출건이 아닙니다.');
			echo json_encode($RESULT_ARR);
			exit;
		}

		if($FLAG['idx']=='' || $FLAG['loan_principal_state'] != 'Y') {
			$RESULT_ARR = array('result' => 'ERROR', 'message' => "'일부상환 수급완료' 처리가 되지 않아 진행 할 수 없습니다.");
			echo json_encode($RESULT_ARR);
			exit;
		}

		$SDATE = ($SDATE) ? preg_replace("/-/", "", $SDATE) : date('Ymd');
		$PARTNER_CD = 'P0012';
		$DC_NB = $prd_idx;


		//처리대기중인 데이터의 마지막 SEQ값 가져오기... SEQ필드가 문자형이라 형변환 처리 함 => CAST(SEQ AS unsigned)
		$TMP = sql_fetch("
			SELECT
				MAX(CAST(SEQ AS unsigned)) AS max_seq
			FROM
				IB_FB_P2P_REPAY_REQ_DETAIL
			WHERE 1
				AND SDATE = ''
				AND REG_SEQ = ''
				AND req_idx IS NULL");

		$seq = $TMP['max_seq'] + 1;
		unset($TMP);

		$dtlsql = "INSERT INTO IB_FB_P2P_REPAY_REQ_DETAIL (SEQ, PARTNER_CD, DC_NB, CUST_ID, TR_AMT, TR_AMT_P, CTAX_AMT, FEE, REPAY_RECEIPT_NB, invest_idx, turn, turn_sno, is_overdue, rdate) VALUES ";

		$insert_count = 0;
		for($j=0,$k=1; $j<$repay_count; $j++,$k++) {

			$REPAY_RECEIPT_NB = $TARGET_REPAY['LIST'][$j]['prin_rcv_no'];		// 원리금 수취권 번호
		//$REPAY_RECEIPT_NB = 'M' . $TARGET_REPAY['LIST'][$j]['member_idx'] . 'P' . $prd_idx . 'I' . $TARGET_REPAY['LIST'][$j]['invest_idx'];		// 원리금 수취권 번호

			// 입력된 기록이 없을때에만 등록
			$RECORDED = sql_fetch("
				SELECT
					COUNT(SEQ) AS cnt
				FROM
					IB_FB_P2P_REPAY_REQ_DETAIL
				WHERE 1
					AND REPAY_RECEIPT_NB = '".$REPAY_RECEIPT_NB."'
					AND RESP_CODE = ''
					AND turn = '".$repay_turn."'
					AND turn_sno = '".$repay_turn_sno."'
					AND is_overdue = 'N'");
			if(!$RECORDED['cnt']) {

				$SEQ		  = $seq;		// 해당 회차의 데이터 일련번호
				$CUST_ID  = $TARGET_REPAY['LIST'][$j]['member_idx'];							// 투자자고객ID
				$TR_AMT_P = $TARGET_REPAY['LIST'][$j]['repay_principal'];					// 상환원금
				$TR_AMT   = $TARGET_REPAY['LIST'][$j]['interest'] + $TR_AMT_P;		// 입금금액 = 세후 투자수익금(세금+수수료를 제외한 재예치 대상 수익금)
				$CTAX_AMT = $TARGET_REPAY['LIST'][$j]['TAX']['sum'];							// 세금
				$FEE      = $TARGET_REPAY['LIST'][$j]['invest_usefee'];						// 수수료

				$dtlsql.= "(";
				$dtlsql.= "'".$SEQ."'";
				$dtlsql.= ",'".$PARTNER_CD."'";
				$dtlsql.= ",'".$DC_NB."'";
				$dtlsql.= ",'".$CUST_ID."'";
				$dtlsql.= ",'".$TR_AMT."'";
				$dtlsql.= ",'".$TR_AMT_P."'";
				$dtlsql.= ",'".$CTAX_AMT."'";
				$dtlsql.= ",'".$FEE."'";
				$dtlsql.= ",'".$REPAY_RECEIPT_NB."'";
				$dtlsql.= ",'".$TARGET_REPAY['LIST'][$j]['invest_idx']."'";
				$dtlsql.= ",'".$repay_turn."'";
				$dtlsql.= ",'".$repay_turn_sno."'";
				$dtlsql.= ",'N'";
				$dtlsql.= ", NOW()";
				$dtlsql.= ")";
				$dtlsql.= ($k<$repay_count) ? "," : "";

				$TOTAL_TR_AMT += $TR_AMT;

				$seq++;
				$insert_count++;

			}

		}

		if($insert_count) {
			if( sql_query($dtlsql) ) {

				sql_query("
					UPDATE
						cf_product_success
					SET
						ib_request_ready = 'Y'
					WHERE 1
						AND idx = '".$FLAG['idx']."'
						AND ib_request_ready = ''");

				$msg = "대출상품명 :  " . $PRDT['title'] . "\n" .
						 "상환회차 :  " . $repay_turn ."회차-원금일부상환(".$repay_turn_sno.")\n" .
						 "요청건수 : " . number_format($repay_count) . "건\n" .
						 "실입금액합계 : " . number_format($TOTAL_TR_AMT) . "원\n\n" .
						 "입금요청대기건으로 등록 되었습니다.";

				$RESULT_ARR = array('result' => 'SUCCESS', 'message' => $msg);
				echo json_encode($RESULT_ARR);

			}
			else {
				$RESULT_ARR = array('result' => 'ERROR', 'message' => "DB 입력 오류가 발생하였습니다. 관리자에게 문의하십시요.");
				echo json_encode($RESULT_ARR);
			}
		}
		else {
			$RESULT_ARR = array('result' => 'ERROR', 'message' => "기등록된 요청이거나, 등록 할 데이터가 없습니다.");
			echo json_encode($RESULT_ARR);
		}

	}		// end if($action=='partial_devide_ready')


	///////////////////////////////////////////////////////////////////////////////
	// 부분상환금 배분요청 전문 구성 및 등록 (제3자 예치시스템)
	///////////////////////////////////////////////////////////////////////////////
	if($action=='partial_devide_request') {

		$sdate = $_POST['req_sdate'];
		$stime = $_POST['req_stime'];
		$sdatetime = $sdate.' '.$stime;

		$SDATE = preg_replace('/-/', '', $sdate);
		$STIME = preg_replace('/:/', '', $stime);

		if( substr(G5_TIME_YMDHIS, 0, 16) >= substr($sdatetime, 0, 16) ) {
			$msg = "등록가능한 시간이 아닙니다. 다음 시간대를 이용하십시요.\n\n";
			$msg.= "현재시간 : " . substr(G5_TIME_YMDHIS, 0, 16);
			$RESULT_ARR = array("result" => "ERROR", "message" => $msg);
			echo json_encode($RESULT_ARR);
			exit;
		}

		// 중복 회차 거부설정
		$ROW_A = sql_fetch("SELECT COUNT(*) AS cnt FROM IB_FB_P2P_REPAY_REQ WHERE SDATE = '".$SDATE."' AND STIME = '".$STIME."' AND EXEC_STATUS = '00'");  // 요청처리상태 (00:처리전,01:처리중,02:처리완료)
		$ROW_B = sql_fetch("SELECT COUNT(*) AS cnt FROM IB_FB_P2P_REPAY_REQ_ready WHERE SDATE = '".$SDATE."' AND STIME = '".$STIME."'");

		if( $ROW_A['cnt'] > 0 || $ROW_B['cnt'] > 0 ) {
			$msg = "해당 시간대의 예약 내역이 존재합니다. 다음 시간대를 이용하십시요.\n\n";
			$msg.= "현재시간 : " . substr(G5_TIME_YMDHIS, 0, 16);
			$RESULT_ARR = array("result" => "ERROR", "message" => $msg);
			echo json_encode($RESULT_ARR);
			exit;
		}


		$where = "1 AND SDATE = '' AND REG_SEQ = '' AND req_idx IS NULL";

		$sql = "
			SELECT
				COUNT(CUST_ID) AS TOTAL_CNT,
				IFNULL(SUM(TR_AMT),0) AS TOTAL_TR_AMT,
				IFNULL(SUM(TR_AMT_P),0) AS TOTAL_TR_AMT_P,
				IFNULL(SUM(CTAX_AMT),0) AS TOTAL_CTAX_AMT,
				IFNULL(SUM(FEE),0) AS TOTAL_FEE
			FROM
				IB_FB_P2P_REPAY_REQ_DETAIL
			WHERE
				$where
				AND turn_sno > '0'
				AND is_overdue = 'N'";

		$ROW = sql_fetch($sql);

		// 다음 회차 설정
		$ROW_C = sql_fetch("SELECT COUNT(*) AS cnt FROM IB_FB_P2P_REPAY_REQ WHERE SDATE = '".$SDATE."' AND STIME <= '".$STIME."'");
		$ROW_D = sql_fetch("SELECT COUNT(*) AS cnt FROM IB_FB_P2P_REPAY_REQ_ready WHERE SDATE = '".$SDATE."' AND STIME <= '".$STIME."'");
		$reg_seq = $ROW_C['cnt'] + $ROW_D['cnt'] + 1;

		// 요청자료 금액 및 카운트 추출
		$REG_SEQ        = sprintf('%02d', $reg_seq);
		$PARTNER_CD     = 'P0012';
		$STIME					= $STIME;
		$TOTAL_CNT      = $ROW['TOTAL_CNT'];	// 해당 회차의 요청한 총 건수를 나타냅니다.
		$TOTAL_TR_AMT   = $ROW['TOTAL_TR_AMT'];
		$TOTAL_TR_AMT_P = $ROW['TOTAL_TR_AMT_P'];
		$TOTAL_CTAX_AMT = $ROW['TOTAL_CTAX_AMT'];
		$TOTAL_FEE      = $ROW['TOTAL_FEE'];

		$TOTAL_S_CNT		= '';							// 총정상처리건수
		$TOTAL_E_CNT		= '';							// 총에러처리건수
		$TRAN_DATE			= '';							// 처리일자 (YYYYMMDD)
		$TRAN_TIME			= '';							// 처리시간 (hhmmss)
		$RESP_CODE			= '';							// 응답코드
		$RESP_MSG				= '';							// 응답메세지
		$EXEC_STATUS		= '00';						// 00:처리전 01:처리중 02:처리완료

		// 펌뱅킹 원리금지급요청 및 실행정보 등록
		$sql = "
			INSERT INTO
				IB_FB_P2P_REPAY_REQ_ready
			SET
				SDATE          = '".$SDATE."',
				REG_SEQ        = '".$REG_SEQ."',
				PARTNER_CD     = '".$PARTNER_CD."',
				STIME          = '".$STIME."',
				TOTAL_CNT      = '".$TOTAL_CNT."',
				TOTAL_TR_AMT   = '".$TOTAL_TR_AMT."',
				TOTAL_TR_AMT_P = '".$TOTAL_TR_AMT_P."',
				TOTAL_CTAX_AMT = '".$TOTAL_CTAX_AMT."',
				TOTAL_FEE      = '".$TOTAL_FEE."',
				TRAN_DATE      = '".$TRAN_DATE."',
				TRAN_TIME      = '".$TRAN_TIME."',
				TOTAL_S_CNT    = '".$TOTAL_S_CNT."',
				TOTAL_E_CNT    = '".$TOTAL_E_CNT."',
				RESP_CODE      = '".$RESP_CODE."',
				RESP_MSG       = '".$RESP_MSG."',
				EXEC_STATUS    = '".$EXEC_STATUS."',
				apply          = ''";
		sql_query($sql);
		$insert_idx = sql_insert_id();

		if($insert_idx) {

			$PRDT_TURN = $_POST['PRDT_TURN'];

			//상품-차수별 상세 상환요청내역 전송정보 수정
			for($i=0; $i<count($PRDT_TURN); $i++) {

				$TMP_ARR  = explode("&", trim($PRDT_TURN[$i]));

				$prd_idx  = $TMP_ARR[0];
				$turn     = $TMP_ARR[1];
				$turn_sno = $TMP_ARR[2];
				$overdue  = $TMP_ARR[3];

				// 펌뱅킹 원리금지급요청 상세정보 등록. 미리 등록된 상세정보내역에서 발송일자 및 실행순번 수정
				$sql2 = "
					UPDATE
						IB_FB_P2P_REPAY_REQ_DETAIL
					SET
						SDATE = '".$SDATE."',
						REG_SEQ = '".$REG_SEQ."',
						req_idx = '".$insert_idx."'
					WHERE
						$where
						AND DC_NB = '".$prd_idx."'
						AND turn = '".$turn."'
						AND turn_sno = '".$turn_sno."'
						AND is_overdue = '".$overdue."'";
				sql_query($sql2);

				// 지급상태정보 변경 *** 최후 지급완료처리 IB_FB_P2P_REPAY_REQ 테이블의 RESP_CODE 값(정상:00000000)으로 구분 -> 자동스케쥴러가 필요함. ***
				$sqlx = "
					UPDATE
						cf_product_success
					SET
						invest_principal_give = 'W'
					WHERE 1
						AND product_idx = '".$prd_idx."'
						AND turn = '".$turn."'
						AND turn_sno = '".$turn_sno."'
						AND invest_principal_give = ''";
				sql_query($sqlx);

			}


			$cnt_sql = "
				SELECT
					COUNT(CUST_ID) AS TOTAL_CNT,
					IFNULL(SUM(TR_AMT),0) AS TOTAL_TR_AMT,
					IFNULL(SUM(TR_AMT_P),0) AS TOTAL_TR_AMT_P,
					IFNULL(SUM(CTAX_AMT),0) AS TOTAL_CTAX_AMT,
					IFNULL(SUM(FEE),0) AS TOTAL_FEE
				FROM
					IB_FB_P2P_REPAY_REQ_DETAIL
				WHERE 1
					AND SDATE = '".$SDATE."'
					AND req_idx = '".$insert_idx."'";
			$INPUTED = sql_fetch($cnt_sql);

			// 예약테이블 카운트 재수정
			$sql3 = "
				UPDATE
					IB_FB_P2P_REPAY_REQ_ready
				SET
					TOTAL_CNT      = '".$INPUTED['TOTAL_CNT']."',
					TOTAL_TR_AMT   = '".$INPUTED['TOTAL_TR_AMT']."',
					TOTAL_TR_AMT_P = '".$INPUTED['TOTAL_TR_AMT_P']."',
					TOTAL_CTAX_AMT = '".$INPUTED['TOTAL_CTAX_AMT']."',
					TOTAL_FEE      = '".$INPUTED['TOTAL_FEE']."'
				WHERE
					idx = '".$insert_idx."'";
			//print_r($sql3);
			sql_query($sql3);

			$RESULT_ARR = array("result" => "SUCCESS", "message" => "");
			echo json_encode($RESULT_ARR);

		}

	}		// end if($action=='partial_devide_request')


	/////////////////////////////////////////////////////////////////////////////
	// 일부상환 지급 실행
	/////////////////////////////////////////////////////////////////////////////
	if($action=='partial_give') {

		if($FLAG['idx']=='' || $FLAG['loan_principal_state'] != 'Y') {
			$RESULT_ARR = array("result" => "ERROR", "message" => "'일부상환 수급완료' 처리가 되지 않아 진행 할 수 없습니다.");
			echo json_encode($RESULT_ARR);
			exit;
		}

		$proc_count = 0;
		for($j=0,$k=1; $j<$repay_count; $j++,$k++) {

			//▼▼▼▼ 가상계좌지급방식으로 강제 적용 ▼▼▼▼//
			$TARGET_REPAY['LIST'][$j]['receive_method'] = '2';
			//▲▲▲▲ 가상계좌지급방식으로 강제 적용 ▲▲▲▲//

			// ** 기입금자 중복 지급방지 체크 **
			$cntsql = "
				SELECT
					COUNT(idx) AS cnt_idx
				FROM
					cf_product_give
				WHERE 1
					AND invest_idx = '".$TARGET_REPAY['LIST'][$j]['invest_idx']."'
					AND turn = '".$repay_turn."'
					AND turn_sno = '".$repay_turn_sno."'
					AND is_overdue = 'N'
					AND banking_date IN NOT NULL";
			$ROW = sql_fetch($cntsql);

			if(!$ROW['cnt_idx']) {

				$bank_code         = $TARGET_REPAY['LIST'][$j]['bank_code'];
				$bank_name         = $BANK[$bank_code];
				$bank_private_name = $TARGET_REPAY['LIST'][$j]['bank_private_name'];
				$account_num       = preg_replace("/-/", "", $TARGET_REPAY['LIST'][$j]['account_num']);

				$proc_auth_flag = true;

				if($ib_trust) {

					// 원리금 수취방식에 따른 입금계좌 설정(제3자 예치시스템 적용 상품 일 경우에만 적용됨)
					if($TARGET_REPAY['LIST'][$j]['receive_method']=='2') {		// 가상계좌환급
						$MB = sql_fetch("SELECT va_bank_code2, virtual_account2, va_private_name2, insidebank_after_trans_target FROM g5_member WHERE mb_no='".$TARGET_REPAY['LIST'][$j]['mb_no']."'");
						if($MB['insidebank_after_trans_target']=='Y') {
							$proc_auth_flag = false;
						}
						else {
							$bank_code         = $MB['va_bank_code2'];
							$bank_name         = $BANK[$MB['va_bank_code2']];
							$bank_private_name = $MB['va_private_name2'];
							$account_num       = preg_replace("/-/", "", $MB['virtual_account2']);
						}
					}

					if($proc_auth_flag && $bank_code && $account_num) {

						// 최종 이체금액 설정
						$final_trans_amount = $TARGET_REPAY['LIST'][$j]['repay_principal'];

						/////////////////////////////////////////////////////////////////////////////
						// 환급계좌로 원리금 수취하는 회원 지급을 위한 예치금 출금전문(3200) 발송
						/////////////////////////////////////////////////////////////////////////////
						if($TARGET_REPAY['LIST'][$j]['receive_method']=='1') {

							// 지급액이 0원 보다 클 경우에만 지급 실행
							if($final_trans_amount > 0) {

								$REQ_NUM         = '032';																																// 전문번호(출금: 032)
								$CUST_ID         = $TARGET_REPAY['LIST'][$j]['member_idx'];															// 투자자고객ID (투자자번호로 처리함)
								$TRAN_BANK_CD    = $TARGET_REPAY['LIST'][$j]['bank_code'];															// 이체은행코드(출금신청한 예치금을 입금받을 은행코드)
								$TRAN_ACCT_NB    = preg_replace("/-/", "", $TARGET_REPAY['LIST'][$j]['account_num']);		// 이체계좌번호(출금신청한 예치금을 입금받을 계좌번호)

								$TRAN_REMITEE_NM = "";
								$TRAN_REMITEE_NM.= "헬로펀딩";
								$TRAN_REMITEE_NM.= str_f6($PRDT['title'], '[제', '호]');

								$ARR['REQ_NUM']         = $REQ_NUM;
								$ARR['CUST_ID']         = $CUST_ID;
								$ARR['TRAN_BANK_CD']    = $TRAN_BANK_CD;																						// 입금계좌은행코드
								$ARR['TRAN_ACCT_NB']    = $TRAN_ACCT_NB;																						// 입금계좌번호
								$ARR['TRAN_REMITEE_NM'] = $TRAN_REMITEE_NM."(".sprintf("%02d", rand(0,99)).")";			// 이체계좌성명 (동일 이체계좌성명 발생시 이체가 되지 않으므로 랜덤숫자붙여줌)
								$ARR['TRAN_AMT']        = $final_trans_amount;																			// 이체금액
								$ARR['TRAN_MEMO']       = $TRAN_REMITEE_NM;																					// 이체계좌통장메모
								$ARR['GUAR_MEMO']       = '원금상('.$PRDT['idx'].')';																// 예치금모계좌통장메모
								$ARR['FUND_KIND']       = '10';																											// 자금성격(10:예치금)
								//print_r($ARR);
								$RETURN_ARR = insidebank_request('256', $ARR);

								$proc_auth_flag = false;

								if($RETURN_ARR['RCODE']=='00000000') {
									$proc_auth_flag = true;
								}
								else {
									// IS0102 코드 발생시 결번요청(8400)으로 지급전문 실행 결과값 재전송받기
									if( $RETURN_ARR['RCODE']=='IS0102') {

										$LAST_REQUEST = sql_fetch("
											SELECT
												idx, request_arr
											FROM
												IB_request_log
											WHERE 1
												AND request_code='3200' AND rcode='IS0102'
												AND exec_path='/adm/repayment/repay_proc_partial.php'
												AND request_arr LIKE '%CUST_ID=".$CUST_ID."&%'
											ORDER BY
												idx DESC LIMIT 1");

										if($LAST_REQUEST['idx']) {
											$REQUEST_ARR = explode("&", $LAST_REQUEST['request_arr']);
											$last_fbseq = preg_replace("/FB_SEQ=/", "", $REQUEST_ARR[0]);

											if($last_fbseq) {
												// 결번요청(8400)  -> 전문 실행 결과값 재전송받기
												$ARR2['SUBMIT_GBN'] = "04";						//전문번호
												$ARR2['TRAN_DATE']  = date('Ymd');		//date('Ymd');
												$ARR2['ORI_FB_SEQ'] = $last_fbseq;

												$RETURN_ARR2 = insidebank_request("000", $ARR2);
												if($RETURN_ARR2['ORI_FB_REQCODE']=='00000000') {
													sql_query("UPDATE IB_request_log SET rcode='00000000' WHERE idx='".$LAST_REQUEST['idx']."'");
													$proc_auth_flag = true;
													$RETURN_ARR['GUAR_SEQ'] = $RETURN_ARR2['GUAR_SEQ'];
												}
											}
										}

									}
								}

							}	// end if($final_trans_amount > 0)

						}		// end if($TARGET_REPAY['LIST'][$j]['receive_method']=='1')

						/////////////////////////////////////////////////////////////////////////////
						// 가상계좌로 받는 사람은 원리금만큼 포인트 부여.
						// 출금처리 안함
						/////////////////////////////////////////////////////////////////////////////
						else if($TARGET_REPAY['LIST'][$j]['receive_method']=='2') {

							// 지급액이 0원 보다 클 경우에만 지급 실행
							if($final_trans_amount > 0) {
								$point_subject = '예치금 충전: '.$PRDT['title'].' (원금일부상환 '.$repay_turn.'-'.$repay_turn_sno.')';
								insert_point($TARGET_REPAY['LIST'][$j]['mb_id'], $final_trans_amount, $point_subject, '@partial_repay', $member['mb_id'], $member['mb_id'].'-'.uniqid(''));
							}

						}

						/////////////////////////////////////////////////////////////////////////////
						// 환급계좌 미지정시 이체 또는 포인트 부여 차단
						/////////////////////////////////////////////////////////////////////////////
						else {
							$proc_auth_flag = false;
						}

					}
					else {
						$proc_auth_flag = false;
					}		// end if($proc_auth_flag && $bank_code && $account_num)

				}

				$remit_fee = ($PRDT['invest_usefee']=='' || $PRDT['invest_usefee']=='0.00') ? '1' : '';

				if($proc_auth_flag) {

					// 지급액이 0원일 경우
					if($final_trans_amount <= 0) {
						$bank_name = $bank_private_name = $account_num = '';
					}

					// 입금로그 등록	(invest_amount 는 실수령 이자만 등록)
					$insert_sql = "
						INSERT INTO
							cf_product_give
						SET
							  `date`            = '".$req_date."'
							, invest_amount     = '".$TARGET_REPAY['LIST'][$j]['invest_amount']."'
							, interest          = '".$TARGET_REPAY['LIST'][$j]['interest']."'
							, principal         = '".$TARGET_REPAY['LIST'][$j]['repay_principal']."'
							, interest_tax      = '".$TARGET_REPAY['LIST'][$j]['TAX']['interest_tax']."'
							, local_tax         = '".$TARGET_REPAY['LIST'][$j]['TAX']['local_tax']."'
							, fee               = '".$TARGET_REPAY['LIST'][$j]['invest_usefee']."'
							, invest_idx        = '".$TARGET_REPAY['LIST'][$j]['invest_idx']."'
							, member_idx        = '".$TARGET_REPAY['LIST'][$j]['member_idx']."'
							, product_idx       = '".$prd_idx."'
							, turn              = '".$repay_turn."'
							, turn_sno          = '".$repay_turn_sno."'
							, is_overdue        = 'N'
							, remit_fee         = '".$remit_fee."'
							, receive_method    = '".$TARGET_REPAY['LIST'][$j]['receive_method']."'
							, bank_name         = '".$bank_name."'
							, bank_private_name = '".$bank_private_name."'
							, account_num       = '".$account_num."'
							, banking_date      = NOW()
							, GUAR_SEQ          = '".$RETURN_ARR['GUAR_SEQ']."'
							, mb_type           = '".$TARGET_REPAY['LIST'][$j]['member_type']."'
							, investor_type     = '".$TARGET_REPAY['LIST'][$j]['member_investor_type']."'
							, is_creditor       = '".$TARGET_REPAY['LIST'][$j]['is_creditor']."'";

					//print_r($insert_sql)."\n\n";
					if(sql_query($insert_sql)) {
						$proc_count += sql_affected_rows();
					}
					else {
						$RESULT_ARR = array("result" => "ERROR", "message" => "DB INSERT ERROR");
						echo json_encode($RESULT_ARR);
						break;
					}

				}

				$RETURN_ARR = NULL;

			}

		}		// end for

		if($proc_count) {
			$msg = $proc_count . "건 지급처리 완료";
		}
		else {
			$msg = "재지급 처리건이 없습니다.\n일부상환 지급완료 처리 하십시요.";
		}

		$RESULT_ARR = array("result" => "SUCCESS", "message" => $msg);
		echo json_encode($RESULT_ARR);

	}		// end if($action=='partial_give')


	///////////////////////////////////////////////////////////////////////////////
	// 일부상환원금 지급완료 플래그
	///////////////////////////////////////////////////////////////////////////////
	if($action=='partial_principal_give_success') {

		if($FLAG['idx']=='' || $FLAG['loan_principal_state'] != 'Y') {
			$RESULT_ARR = array('result' => 'ERROR', 'message' => "'일부상환 수급완료' 처리가 되지 않아 진행 할 수 없습니다.");
			echo json_encode($RESULT_ARR);
			exit;
		}

		$ROW = sql_fetch("
			SELECT
				(
					SELECT
						COUNT(invest_idx)
					FROM
						IB_FB_P2P_REPAY_REQ_DETAIL
					WHERE 1
						AND DC_NB = '".$prd_idx."' AND turn = '".$repay_turn."' AND turn_sno = '".$repay_turn_sno."' AND is_overdue = 'N'
				) AS reques_count,
				(
					SELECT
						COUNT(idx)
					FROM
						cf_product_give
					WHERE 1
						AND product_idx = '".$prd_idx."' AND turn = '".$repay_turn."' AND turn_sno = '".$repay_turn_sno."' AND is_overdue = 'N' AND banking_date IN NOT NULL
				)  AS give_count");


		if($ROW['reques_count'] <> $ROW['give_count']) {
			$msg = "배분요청수와 지급처리수가 동일하지 않습니다.\n" .
			       "다음 사항을 확인 하십시요.\n\n" .
			       "배분요청: " . number_format($ROW['reques_count']) . "건\n" .
			       "지급수: " . number_format($ROW['give_count']) . "건";
			$RESULT_ARR = array('result' => 'ERROR', 'message' => $msg);
			echo json_encode($RESULT_ARR);
			exit;
		}

		$res = sql_query("UPDATE cf_product_success SET invest_principal_give = 'Y' WHERE idx = '".$FLAG['idx']."' AND invest_principal_give != 'Y'");

		if($res) {
			$RESULT_ARR = array('result' => 'SUCCESS', 'message' => '');
			echo json_encode($RESULT_ARR);
		}
		else {
			$RESULT_ARR = array('result' => 'ERROR', 'message' => '플래그수정 실패');
			echo json_encode($RESULT_ARR);
		}

	}		// end if($action=='partial_principal_give_success')

}


sql_close();

exit;

?>