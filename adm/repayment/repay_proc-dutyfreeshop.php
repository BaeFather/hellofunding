<?
###############################################################################
## 3187번 상품을 처리하기 위한 정산처리 파일
##	- 정상상환건만 처리됨
##	- 이자 지급처리시 지급데이터가 있으면 기존 데이터에 업데이트 되도록 처리함.
##	- 특수 케이스 상품인 경우 환급계좌이체지급이 아닌 "예치금충전방식"으로 지급할것
###############################################################################

set_time_limit(0);
ini_set('memory_limit','256M');

$sub_menu = '700000';
include_once('./_common.php');


/*
3023           ::: 정상이자 지급:2020-08-05 / 원금,연체이자 지급:2020-08-10
3187,3194,3201 ::: 정상이자 지급:2020-09-07 / 원금,연체이자 지급:2020-09-10
3215,3223,3224 ::: 정상이자 지급:2020-09-07 / 원금,연체이자 지급:2020-09-15
3315,3324,3334,3341,3359,3382  ::: 정상이자 지급:2020-10-05
3391,3422
*/

if( !in_array(trim($_REQUEST['idx']), $CONF['OVDPRDT']) ) exit;


//print_r($_REQUEST); exit;

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

//if($_REQUEST['turn_sno']) {
//	$repay_turn_sno = trim($_REQUEST['turn_sno']);
//	$sno_arr_no     = $repay_turn_sno - 1;		// 정렬번호의 배열번호
//}
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


if($repay_turn==$INI['repay_turn']) {
	$is_last_turn = 1;
}



$ib_trust = ($PRDT['ib_trust']=='Y' && $PRDT['ib_product_regist']=='Y') ? true : false;

if($ib_trust) {

// 여기 작업 하자 ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
/*
	// 대출자 상품 idx 추출
	$loaner_res = sql_query("SELECT idx FROM cf_product WHERE loan_mb_no='".$PRDT['loan_mb_no']."' AND state='1' ORDER BY idx DESC");
	$loaner_product = '';
	while( $r = sql_fetch_array($loaner_res) ) {
		$loaner_product.= "'" . $r['idx'] . "',";
	}
	unset($loaner_res);
	$loaner_product = @substr($loaner_product, -1);

	// 대출자 상품 분배요청 및 처리중인 금액 합계
	if($loaner_product) {
		$banking_sql = sql_fetch("
			SELECT
				IFNULL(SUM(CAST(TR_AMT AS UNSIGNED)), 0) AS SUM_TR_AMT
			FROM
				IB_FB_P2P_REPAY_REQ_DETAIL
			WHERE 1
				AND DC_NB IN(".$loaner_product.")
				AND RESP_CODE = ''");
	}
*/
// 여기 작업 하자 ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

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
		WHERE
			product_idx = '".$prd_idx."'");

	$gived_amount = array_sum($GIVED);
	$loaner_deposit_amount = $DEPOSITED['TR_AMT'] - $gived_amount;		// 해당상품의 상환계좌 잔액
	$idle_money  = $loaner_deposit_amount - $gived_amount;						// 신탁계좌 여유잔액

}

if($repay_turn) {

	$TARGET_REPAY = $REPAY[$repay_arr_no];
	$repay_count  = count($TARGET_REPAY['LIST']);

	$FLAG = sql_fetch("
		SELECT
			idx, loan_interest_state, loan_principal_state, ib_request_ready, invest_give_state, invest_principal_give
		FROM
			cf_product_success
		WHERE 1
			AND product_idx = '".$prd_idx."' AND turn = '".$repay_turn."' AND turn_sno = '0'");

	$repay_amount = $TARGET_REPAY['SUM']['repay_principal'] + $TARGET_REPAY['SUM']['invest_interest'] - $TARGET_REPAY['SUM']['withhold'];	// 당회차 지급필요총액

}




if($action) {

	///////////////////////////////////////////////////////////////////////////////
	// 투자자 등록 -> 3자예치시스템 적용상품 투자자 전송
	///////////////////////////////////////////////////////////////////////////////
	if($action=='ib_investor_regist') {

		if(!$ib_trust) { debug_flush('제3자 예치시스템에 등록된 대출상품이 아닙니다!!!'); }

		$sql = "
			SELECT
				A.idx AS invest_idx,
				A.amount, A.prin_rcv_no,
				B.mb_no, B.mb_id, B.mb_name, B.mb_co_name, B.member_type
			FROM
				cf_product_invest A
			LEFT JOIN
				g5_member B  ON A.member_idx=B.mb_no
			WHERE 1
				AND A.product_idx = '".$prd_idx."'
				AND invest_state = 'Y'
				AND ib_regist = ''
			ORDER BY
				A.idx";
		//echo $sql;
		$res  = sql_query($sql);
		$rows = sql_num_rows($res);
		if($rows) {

			$CNT = array('succ'=>0, 'fail'=>0);

			for($i=0,$j=1; $i<$rows; $i++,$j++) {
				$LIST[$i] = sql_fetch_array($res);
				$print_name = ($LIST[$i]['member_type']=='2') ? $LIST[$i]['mb_co_name'] : $LIST[$i]['mb_name'];

				///////////////////////////////////////////////////////
				// 인사이드뱅크 전문 구성 및 발송 (전문번호:2200)
				///////////////////////////////////////////////////////
				$ARR['REQ_NUM']     = "020";											// 전문번호
				$ARR['SUBMIT_GBN']  = "02";												// 거래구분 (투자자등록:02 | 투자자변경:06 | 투자자취소:07)
				$ARR['LOAN_SEQ']    = $prd_idx;										// 대출식별번호 (대출상품번호)
				$ARR['INV_SEQ']     = $LIST[$i]['invest_idx'];		// 대출등록시 투자자건수에 대한 일련번호
				$ARR['INV_CUST_ID'] = $LIST[$i]['mb_no'];					// 투자자ID (투자자식별번호)
				$ARR['PRIN_RCV_NO'] = $LIST[$i]['prin_rcv_no'];		// 원리금 수취권 번호
				$ARR['INV_AMT']     = $LIST[$i]['amount'];				// 투자금액
				//print_r($ARR);

				$RETURN_ARR = insidebank_request('256', $ARR);	/*** 인사이드뱅크 전문(2200) 전송 ***/

				if($RETURN_ARR['RCODE']=='00000000') {
					sql_query("UPDATE cf_product_invest SET ib_regist='1' WHERE idx='".$LIST[$i]['invest_idx']."'");
					$result_text = 'SUCCESS';
					$CNT['succ']++;
				}
				else {
					$result_text = 'FAIL : ' . $RETURN_ARR['ERRMSG'];
					$CNT['fail']++;
				}
				unset($ARR);

				debug_flush('['.$j.'] ' . $LIST[$i]['mb_id']. '    ' . $print_name . ' :: ' . number_format($LIST[$i]['amount']) . "원 >>>>>>>>>> ". $result_text . " \n");

				if($j==$rows) {
					debug_flush("\n" .
						">>>>>>>>>> " . $CNT['succ'] . "건 정상\n" .
						">>>>>>>>>> " . $CNT['fail'] . "건 실패\n\n" .
						">>>>>>>>>> 페이지를 새로고침 하십시요!!!\n");
				}

			}

			sql_free_result($res);
		}
		else {
			debug_flush("전송처리 할 투자건이 없습니다!!!");
		}

	}		// end if($action=='ib_investor_regist')


	///////////////////////////////////////////////////////////////////////////////
	// (중도상환용)대출종료일자 변경
	///////////////////////////////////////////////////////////////////////////////
	if($action=='repay_date_change') {

		if($PRDT['state']!='1') { echo "대출만료일을 변경할 수 없는 상품입니다."; exit; }


		$sql = "UPDATE cf_product SET loan_end_date = '".$_POST['loan_end_date']."' WHERE idx = '".$prd_idx."'";
		if(sql_query($sql)) {

			// 수익명세서 재생성시작
			$exec_path   = "/usr/local/php/bin/php -q " . G5_ADMIN_PATH . "/repayment/make_bill_exec.php " . $prd_idx . " print_result";
			$exec_result = exec($exec_path);

			$RESULT_ARR = array('result' => 'SUCCESS', 'message' => $exec_result);
			echo json_encode($RESULT_ARR);

		}
		else {

			$RESULT_ARR = array('result' => 'ERROR', 'message' => 'UPDATE ERROR');
			echo json_encode($RESULT_ARR);

		}

	}		// if($action=='repay_date_change')


	///////////////////////////////////////////////////////////////////////////////
	// 대출이자 수급완료 플래그
	///////////////////////////////////////////////////////////////////////////////
	if($action=='loan_interest_success') {

		if($ib_trust) {

			if($loaner_deposit_amount <= 0) {
				$RESULT_ARR = array('result' => 'ERROR', 'message' => "상환계좌 잔액이 없습니다.");
				echo json_encode($RESULT_ARR);
				exit;
			}

			// 신탁계좌 잔액 체크
			/*
			if($idle_money < $repay_amount) {
				$need_amount = ($idle_money - $repay_amount) * -1;

				$msg = "본 상품의 상환계좌 잔액이 부족합니다.\n\n부족분: " . number_format($need_amount) . "원";
				$RESULT_ARR = array('result' => 'ERROR', 'message' => $msg);
				echo json_encode($RESULT_ARR);
				exit;
			}
			*/

		}


		if($FLAG['idx']) {
			$sql = "UPDATE cf_product_success SET loan_interest_state = 'Y' WHERE idx = '".$FLAG['idx']."' AND loan_interest_state = ''";
		}
		else {
			$sql = "INSERT INTO cf_product_success (loan_interest_state, product_idx, turn, turn_sno, `date`) VALUES ('Y', '".$prd_idx."', '".$repay_turn."', '0', '".$req_date."')";
		}

		if( sql_query($sql) ) {
			$RESULT_ARR = array('result' => 'SUCCESS', 'message' => '');
			echo json_encode($RESULT_ARR);
		}

	}		// end if($action=='loan_interest_success')


	///////////////////////////////////////////////////////////////////////////////
	// 대출원금 수급완료 플래그
	///////////////////////////////////////////////////////////////////////////////
	if($action=='loan_principal_success') {

		if($ib_trust) {

			if($loaner_deposit_amount <= 0) {
				$RESULT_ARR = array('result' => 'ERROR', 'message' => "상환계좌 잔액이 없습니다.");
				echo json_encode($RESULT_ARR);
				exit;
			}

			// 신탁계좌 잔액 체크
			/*
			if($idle_money < $repay_amount) {
				$need_amount = ($idle_money - $repay_amount) * -1;

				$msg = "본 상품의 상환계좌 잔액이 부족합니다.\n\n부족분: " . number_format($need_amount) . "원";
				$RESULT_ARR = array('result' => 'ERROR', 'message' => $msg);
				echo json_encode($RESULT_ARR);
				exit;
			}
			*/

		}

		// 수급완료 플래그 등록시 인사이드뱅크 처리용 플래그를 초기화 한다. (2020-09-09)
		$res = sql_query("
			UPDATE
				cf_product_success
			SET
				loan_principal_state = 'Y',
				ib_request_ready = ''
			WHERE
				idx = '".$FLAG['idx']."'
				AND loan_principal_state = ''");

		if($res) {
			$RESULT_ARR = array('result' => 'SUCCESS', 'message' => '');
			echo json_encode($RESULT_ARR);
		}
		else {
			$RESULT_ARR = array('result' => 'ERROR', 'message' => "DB UPDATE ERROR");
			echo json_encode($RESULT_ARR);
		}

	}		// end if($action=='loan_principal_success')


	///////////////////////////////////////////////////////////////////////////////
	// 원리금 배분요청 데이터 준비
	// (IB_FB_P2P_REPAY_REQ_DETAIL 테이블에 원리금 지금요청 자료 등록)
	///////////////////////////////////////////////////////////////////////////////
	if($action=='devide_ready') {

		$repay_only = $_REQUEST['repay_only'];		//***** interest:이자만 지급, principal:원금만 지급, 빈값:원리금 지급 *****//

		if($ib_trust) {

			if($loaner_deposit_amount <= 0) {
				$RESULT_ARR = array('result' => 'ERROR', 'message' => "상환계좌 잔액이 없습니다.");
				echo json_encode($RESULT_ARR);
				exit;
			}

			// 신탁계좌 잔액 체크
			/*
			if($idle_money < $repay_amount) {
				$need_amount = ($idle_money - $repay_amount) * -1;

				$msg = "본 상품의 상환계좌 잔액이 부족합니다.\n\n부족분: " . number_format($need_amount) . "원";
				$RESULT_ARR = array('result' => 'ERROR', 'message' => $msg);
				echo json_encode($RESULT_ARR);
				exit;
			}
			*/

		}
		else {
			$RESULT_ARR = array('result' => 'ERROR', 'message' => '제3자 예치시스템이 적용된 대출건이 아닙니다.');
			echo json_encode($RESULT_ARR);
			exit;
		}

		//if(date('Y-m-d') < $TARGET_REPAY['repay_date']) { echo $repay_turn . "회차 투자수익금의 지급완료 처리는 「" . date('Y년 m월 d일', strtotime($TARGET_REPAY['repay_date'])) . "」부터 가능합니다."; exit; }

		if($repay_only=='interest') {
			$tmp_title = "(이자만 지급)";
			if($FLAG['loan_interest_state']=='') {
				$RESULT_ARR = array('result' => 'ERROR', 'message' => "'대출이자 수급완료' 처리가 되지 않아 진행 할 수 없습니다.");
				echo json_encode($RESULT_ARR);
				exit;
			}
		}
		else if($repay_only=='principal') {
			$tmp_title = "(원금만 지급)";
			if($FLAG['loan_principal_state']=='') {
				$RESULT_ARR = array('result' => 'ERROR', 'message' => "'대출원금 수급완료' 처리가 되지 않아 진행 할 수 없습니다.");
				echo json_encode($RESULT_ARR);
				exit;
			}
		}
		else {
			$tmp_title = "(원리금 지급)";
			if($FLAG['loan_interest_state']=='' || $FLAG['loan_principal_state']=='') {
				$RESULT_ARR = array('result' => 'ERROR', 'message' => "'대출이자 및 원금 수급완료' 처리가 되지 않아 진행 할 수 없습니다.");
				echo json_encode($RESULT_ARR);
				exit;
			}
		}

		$SDATE = ($SDATE) ? preg_replace("/-/", "", $SDATE) : date('Ymd');

		$PARTNER_CD = 'P0012';

		//처리대기중인 데이터의 마지막 SEQ값 가져오기... SEQ필드가 문자형이라 형변환 처리 함 => CAST(SEQ AS unsigned)
		$TMP = sql_fetch("SELECT MAX(CAST(SEQ AS unsigned)) AS max_seq FROM IB_FB_P2P_REPAY_REQ_DETAIL WHERE SDATE='' AND REG_SEQ='' AND req_idx IS NULL");
		$seq = $TMP['max_seq'] + 1;
		unset($TMP);

		$dtlsql = "INSERT INTO IB_FB_P2P_REPAY_REQ_DETAIL (SEQ, PARTNER_CD, DC_NB, CUST_ID, TR_AMT, TR_AMT_P, CTAX_AMT, FEE, REPAY_RECEIPT_NB, invest_idx, turn, turn_sno, is_overdue, rdate) VALUES ";

		$insert_count = 0;
		for($j=0,$k=1; $j<$repay_count; $j++,$k++) {

			$REPAY_RECEIPT_NB = 'M' . $TARGET_REPAY['LIST'][$j]['member_idx'] . 'P' . $prd_idx . 'I' . $TARGET_REPAY['LIST'][$j]['invest_idx'];		// 원리금 수취권 번호

			if($repay_only=='interest') {
				$TR_AMT_P = 0;
				$TR_AMT   = $TARGET_REPAY['LIST'][$j]['interest'];		// 입금금액 = 세후이자 + 투자원금
				$CTAX_AMT = $TARGET_REPAY['LIST'][$j]['TAX']['sum'];							// 세금
				$FEE      = $TARGET_REPAY['LIST'][$j]['invest_usefee'];						// 수수료
			}
			else if($repay_only=='principal') {
				$TR_AMT_P = $TARGET_REPAY['LIST'][$j]['repay_principal'];
				$TR_AMT   = $TR_AMT_P;		// 입금금액 = 세후이자 + 투자원금
				$CTAX_AMT = 0;						// 세금
				$FEE      = 0;						// 수수료
			}
			else {
				$TR_AMT_P = ($FLAG['loan_principal_state']=='Y') ? $TARGET_REPAY['LIST'][$j]['repay_principal'] : 0;
				$TR_AMT   = $TARGET_REPAY['LIST'][$j]['interest'] + $TR_AMT_P;		// 입금금액 = 세후이자 + 투자원금
				$CTAX_AMT = $TARGET_REPAY['LIST'][$j]['TAX']['sum'];							// 세금
				$FEE      = $TARGET_REPAY['LIST'][$j]['invest_usefee'];						// 수수료
			}


			// 입력된 기록이 없을때에만 등록
			$RECORDED = sql_fetch("
				SELECT
					COUNT(SEQ) AS cnt
				FROM
					IB_FB_P2P_REPAY_REQ_DETAIL
				WHERE 1
					AND REPAY_RECEIPT_NB = '".$REPAY_RECEIPT_NB."'
					AND RESP_CODE = ''
					AND TR_AMT_P = '".$TR_AMT_P."'
					AND turn = '".$repay_turn."'
					AND turn_sno = '0'
					AND is_overdue = 'N'");
			if(!$RECORDED['cnt']) {

				$CUST_ID  = $TARGET_REPAY['LIST'][$j]['member_idx'];							// 투자자고객ID

				$dtlsql.= "(";
				$dtlsql.= "'".$seq."'";
				$dtlsql.= ",'".$PARTNER_CD."'";
				$dtlsql.= ",'".$prd_idx."'";
				$dtlsql.= ",'".$CUST_ID."'";
				$dtlsql.= ",'".$TR_AMT."'";
				$dtlsql.= ",'".$TR_AMT_P."'";
				$dtlsql.= ",'".$CTAX_AMT."'";
				$dtlsql.= ",'".$FEE."'";
				$dtlsql.= ",'".$REPAY_RECEIPT_NB."'";
				$dtlsql.= ",'".$TARGET_REPAY['LIST'][$j]['invest_idx']."'";
				$dtlsql.= ",'".$repay_turn."'";
				$dtlsql.= ",'0'";
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
				sql_query("UPDATE cf_product_success SET ib_request_ready = 'Y' WHERE idx = '".$FLAG['idx']."' AND ib_request_ready != 'Y'");

				$msg = "대출상품명 :  " . $PRDT['title'] . "\n" .
						 "상환회차 :  " . $repay_turn ."회차 (" . $tmp_title . ")\n" .
						 "요청건수 : " . number_format($repay_count) . "건\n" .
						 "금액합계 : " . number_format($TOTAL_TR_AMT) . "원\n\n" .
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

	}		// end if($action=='devide_ready')


	///////////////////////////////////////////////////////////////////////////////
	// 이자 배분요청 전문 구성 및 등록 (제3자 예치시스템)
	///////////////////////////////////////////////////////////////////////////////
	if($action=='devide_request') {

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
				AND turn_sno = '0'
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
						invest_give_state = 'W'
					WHERE 1
						AND product_idx = '".$prd_idx."'
						AND turn = '".$turn."'
						AND turn_sno = '".$turn_sno."'
						AND invest_give_state = ''";
				sql_query($sqlx);

			}


			$cnt_sql = "
				SELECT
					COUNT(CUST_ID) AS TOTAL_CNT,
					SUM(TR_AMT) AS TOTAL_TR_AMT,
					SUM(TR_AMT_P) AS TOTAL_TR_AMT_P,
					SUM(CTAX_AMT) AS TOTAL_CTAX_AMT,
					SUM(FEE) AS TOTAL_FEE
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
			sql_query($sql3);

			$RESULT_ARR = array("result" => "SUCCESS", "message" => "");
			echo json_encode($RESULT_ARR);

		}

	}		// end if($action=='devide_request')


	/////////////////////////////////////////////////////////////////////////////
	// 이자 지급 실행
	/////////////////////////////////////////////////////////////////////////////
	if($action=='loan_interest_give') {

		$repay_only = $_REQUEST['repay_only'];		//***** interest:이자만 지급, principal:원금만 지급, 빈값:원리금 지급 *****//

		$remit_fee = ($PRDT['invest_usefee']=='' || $PRDT['invest_usefee']=='0.00') ? '1' : '';

		if($FLAG['idx']=='') {

			if($repay_only=='interest') {
				$tmp_title = "(이자만 지급)";
				if($FLAG['loan_interest_state']=='') {
					$RESULT_ARR = array("result" => "ERROR", "message" => "'대출이자 수급완료' 처리가 되지 않아 진행 할 수 없습니다.");
					echo json_encode($RESULT_ARR);
					exit;
				}
			}
			else if($repay_only=='principal') {
				$tmp_title = "(원금만 지급)";
				if($FLAG['loan_principal_state']=='') {
					$RESULT_ARR = array("result" => "ERROR", "message" => "'대출원금 수급완료' 처리가 되지 않아 진행 할 수 없습니다.");
					echo json_encode($RESULT_ARR);
					exit;
				}
			}
			else {
				$tmp_title = "(원리금 지급)";
				if($FLAG['loan_interest_state']=='' || $FLAG['loan_principal_state']=='') {
					$RESULT_ARR = array("result" => "ERROR", "message" => "'대출이자 및 원금 수급완료' 처리가 되지 않아 진행 할 수 없습니다.");
					echo json_encode($RESULT_ARR);
					exit;
				}
			}

		}

		//print_r($_REQUEST);

		$proc_count = 0;
		for($j=0,$k=1; $j<$repay_count; $j++,$k++) {

			// 특수 케이스 상품인 경우 환급계좌이체지급이 아닌 예치금충전방식으로 지급할것 : 2020-08-10
			// 이자지급 원금지급이 분리된 경우에도 수취방식은 예치금 충전방식이어야 함.
			$TARGET_REPAY['LIST'][$j]['receive_method'] = '2';

			$proc_auth_flag = true;

			$bank_code         = $TARGET_REPAY['LIST'][$j]['bank_code'];
			$bank_name         = $BANK[$bank_code];
			$bank_private_name = $TARGET_REPAY['LIST'][$j]['bank_private_name'];
			$account_num       = preg_replace("/-/", "", $TARGET_REPAY['LIST'][$j]['account_num']);

			// 원리금 수취방식에 따른 입금계좌 설정(제3자 예치시스템 적용 상품 일 경우에만 적용됨)
			if($TARGET_REPAY['LIST'][$j]['receive_method']=='2') {		// 가상계좌환급
				$MB = sql_fetch("SELECT va_bank_code2, virtual_account2, va_private_name2, insidebank_after_trans_target FROM g5_member WHERE mb_no='".$TARGET_REPAY['LIST'][$j]['mb_no']."'");

				$bank_code         = $MB['va_bank_code2'];
				$bank_name         = $BANK[$MB['va_bank_code2']];
				$bank_private_name = $MB['va_private_name2'];
				$account_num       = preg_replace("/-/", "", $MB['virtual_account2']);
			}

			// ** 기입금자 동일한 데이터로 입금요청시 중복 지급방지 처리 **
			$cntsql = "
				SELECT
					A.idx, A.interest, A.principal, A.interest_tax, A.local_tax, A.fee,
					( SELECT IFNULL(SUM(principal),0) FROM cf_product_give WHERE invest_idx = A.invest_idx ) AS sum_principal
				FROM
					cf_product_give A
				WHERE 1
					AND A.invest_idx = '".$TARGET_REPAY['LIST'][$j]['invest_idx']."'
					AND A.turn = '".$repay_turn."'
					AND A.turn_sno = '0'
					AND A.is_overdue = 'N'";
			$ROW = sql_fetch($cntsql);

			$give_log_idx = $ROW['idx'];

			// 데이터 대조 후 기존 데이터와 다를 경우 UPDATE 실행

			///////////////////////////////////////////////////////////////
			//	이자만 지급 처리
			///////////////////////////////////////////////////////////////
			if($repay_only=='interest') {

				$final_trans_amount = $TARGET_REPAY['LIST'][$j]['interest'];
				$point_subject      = '예치금 충전: '.$PRDT['title'].' ('.$repay_turn.'회차 이자)';

				if($give_log_idx) {

					if($ROW['interest']==0 && $ROW['interest_tax']==0 && $ROW['local_tax']==0 && $ROW['fee']==0) {

						$point_give_exec = 1;

						$give_sql = "
							UPDATE
								cf_product_give
							SET
								interest          = '".$TARGET_REPAY['LIST'][$j]['interest']."',
								interest_tax      = '".$TARGET_REPAY['LIST'][$j]['TAX']['interest_tax']."',
								local_tax         = '".$TARGET_REPAY['LIST'][$j]['TAX']['local_tax']."',
								fee               = '".$TARGET_REPAY['LIST'][$j]['invest_usefee']."',
								banking_date      = NOW()
							WHERE
								idx = '".$give_log_idx."'";

					}

				}
				else {

					$point_give_exec = 1;

					$give_sql = "
						INSERT INTO
							cf_product_give
						SET
							`date`            = '".$TARGET_REPAY['repay_date']."',
							invest_amount     = '".$TARGET_REPAY['LIST'][$j]['invest_amount']."',
							interest          = '".$TARGET_REPAY['LIST'][$j]['interest']."',
							principal         = '0',
							interest_tax      = '".$TARGET_REPAY['LIST'][$j]['TAX']['interest_tax']."',
							local_tax         = '".$TARGET_REPAY['LIST'][$j]['TAX']['local_tax']."',
							fee               = '".$TARGET_REPAY['LIST'][$j]['invest_usefee']."',
							invest_idx        = '".$TARGET_REPAY['LIST'][$j]['invest_idx']."',
							member_idx        = '".$TARGET_REPAY['LIST'][$j]['member_idx']."',
							product_idx       = '".$prd_idx."',
							turn              = '".$repay_turn."',
							is_overdue        = 'N',
							is_creditor       = '".$TARGET_REPAY['LIST'][$j]['is_creditor']."',
							remit_fee         = '".$remit_fee."',
							receive_method    = '".$TARGET_REPAY['LIST'][$j]['receive_method']."',
							bank_name         = '".$bank_name."',
							bank_private_name = '".$bank_private_name."',
							account_num       = '".$account_num."',
							banking_date      = NOW()";

				}

			}

			///////////////////////////////////////////////////////////////
			// 원금만 지급 처리
			///////////////////////////////////////////////////////////////
			else if($repay_only=='principal') {

				$final_trans_amount = $TARGET_REPAY['LIST'][$j]['repay_principal'];

				$point_subject      = '예치금 충전: '.$PRDT['title'].' ('.$repay_turn.'회차 원금)';

				if($give_log_idx) {

					if( $ROW['principal'] <> $TARGET_REPAY['LIST'][$j]['repay_principal'] ) {

						$point_give_exec = 1;

						$give_sql = "
							UPDATE
								cf_product_give
							SET
								principal = '".$TARGET_REPAY['LIST'][$j]['repay_principal']."',
								banking_date = NOW()
							WHERE
								idx = '".$give_log_idx."'";

					}

				}
				else {

					$point_give_exec = 1;

					$give_sql = "
						INSERT INTO
							cf_product_give
						SET
							`date`            = '".$TARGET_REPAY['repay_date']."',
							invest_amount     = '".$TARGET_REPAY['LIST'][$j]['invest_amount']."',
							principal         = '".$TARGET_REPAY['LIST'][$j]['repay_principal']."',
							invest_idx        = '".$TARGET_REPAY['LIST'][$j]['invest_idx']."',
							member_idx        = '".$TARGET_REPAY['LIST'][$j]['member_idx']."',
							product_idx       = '".$prd_idx."',
							turn              = '".$repay_turn."',
							is_overdue        = 'N',
							is_creditor       = '".$TARGET_REPAY['LIST'][$j]['is_creditor']."',
							remit_fee         = '".$remit_fee."',
							receive_method    = '".$TARGET_REPAY['LIST'][$j]['receive_method']."',
							bank_name         = '".$bank_name."',
							bank_private_name = '".$bank_private_name."',
							account_num       = '".$account_num."',
							banking_date      = NOW()";

				}

			}

			///////////////////////////////////////////////////////////////
			// 이자+원금 지급 처리
			///////////////////////////////////////////////////////////////
			else {

				$final_trans_amount = $TARGET_REPAY['LIST'][$j]['repay_principal'] + $TARGET_REPAY['LIST'][$j]['interest'];

				if( !$give_log_idx ) {

					$point_give_exec = 1;

					$give_sql = "
						INSERT INTO
							cf_product_give
						SET
							`date`            = '".$TARGET_REPAY['repay_date']."',
							invest_amount     = '".$TARGET_REPAY['LIST'][$j]['invest_amount']."',
							interest          = '".$TARGET_REPAY['LIST'][$j]['interest']."',
							principal         = '".$TARGET_REPAY['LIST'][$j]['repay_principal']."',
							invest_idx        = '".$TARGET_REPAY['LIST'][$j]['invest_idx']."',
							member_idx        = '".$TARGET_REPAY['LIST'][$j]['member_idx']."',
							product_idx       = '".$prd_idx."',
							turn              = '".$repay_turn."',
							is_overdue        = 'N',
							is_creditor       = '".$TARGET_REPAY['LIST'][$j]['is_creditor']."',
							remit_fee         = '".$remit_fee."',
							receive_method    = '".$TARGET_REPAY['LIST'][$j]['receive_method']."',
							bank_name         = '".$bank_name."',
							bank_private_name = '".$bank_private_name."',
							account_num       = '".$account_num."',
							banking_date      = NOW()";

				}

			}

			//print_r($update_sql); echo "\n\n";


/*
			if($point_give_exec) {
				echo "insert_point({$TARGET_REPAY['LIST'][$j]['mb_id']}, {$final_trans_amount}, {$point_subject}, '@repay', {$member['mb_id']}, {$member['mb_id']}.'-'.uniqid(''));<br>\n";
				echo $give_sql . "\n\n";
			}
*/
			if($point_give_exec) {

				if(sql_query($give_sql)) {

					if(!$give_log_idx) $give_log_idx = sql_insert_id();

					////////////////////////
					// 예치금 지급 처리
					////////////////////////
					insert_point($TARGET_REPAY['LIST'][$j]['mb_id'], $final_trans_amount, $point_subject, '@repay', $member['mb_id'], $member['mb_id'].'-'.uniqid(''));
					$proc_count += sql_affected_rows();

					////////////////////////
					// 지급 상세내역 기록
					////////////////////////
					$give_sql2_add = "";
					if( in_array($repay_only, array('','principal')) ) {
						$give_sql2_add.= " principal = '".$TARGET_REPAY['LIST'][$j]['repay_principal']."',";
					}
					else if( in_array($repay_only, array('','interest')) ) {
						$give_sql2_add.= " interest = '".$TARGET_REPAY['LIST'][$j]['interest']."',";
						$give_sql2_add.= " interest_tax = '".$TARGET_REPAY['LIST'][$j]['TAX']['interest_tax']."',";
						$give_sql2_add.= " local_tax = '".$TARGET_REPAY['LIST'][$j]['TAX']['local_tax']."',";
						$give_sql2_add.= " fee = '".$TARGET_REPAY['LIST'][$j]['invest_usefee']."',";
					}

					$give_sql2 = "
						INSERT INTO
							cf_product_give_detail
						SET
							give_idx          = '".$give_log_idx."',
							`date`            = '".$TARGET_REPAY['repay_date']."',
							invest_amount     = '".$TARGET_REPAY['LIST'][$j]['invest_amount']."',
							$give_sql2_add
							invest_idx        = '".$TARGET_REPAY['LIST'][$j]['invest_idx']."',
							member_idx        = '".$TARGET_REPAY['LIST'][$j]['member_idx']."',
							product_idx       = '".$prd_idx."',
							turn              = '".$repay_turn."',
							is_overdue        = 'N',
							is_creditor       = '".$TARGET_REPAY['LIST'][$j]['is_creditor']."',
							remit_fee         = '".$remit_fee."',
							receive_method    = '".$TARGET_REPAY['LIST'][$j]['receive_method']."',
							bank_name         = '".$bank_name."',
							bank_private_name = '".$bank_private_name."',
							account_num       = '".$account_num."',
							banking_date      = NOW()";
					sql_query($give_sql2);

				}
				else {

					$RESULT_ARR = array("result" => "ERROR", "message" => "DB QUERY ERROR");
					echo json_encode($RESULT_ARR);
					break;

				}

			}


			$give_sql = $point_give_exec = NULL;

		}		// end for

		if($proc_count) {
			$msg = $proc_count . "건 지급처리 완료";
		}
		else {
			$msg = "재지급 처리건이 없습니다.\n투자수익금 지급완료 처리 하십시요.";
		}

		$RESULT_ARR = array("result" => "SUCCESS", "message" => $msg);
		echo json_encode($RESULT_ARR);

	}		// end if($action=='loan_interest_give')


	///////////////////////////////////////////////////////////////////////////////
	// 이자 지급완료 플래그
	///////////////////////////////////////////////////////////////////////////////
	if($action=='invest_give_success') {

		/*
		if(date('Y-m-d') < $REPAY[$repay_arr_no]['repay_date']) {
			echo $repay_turn . "회차 투자수익금의 지급완료 처리는 「" . date('Y년 m월 d일', strtotime($REPAY[$repay_arr_no]['repay_date'])) . "」부터 가능합니다."; exit;
		}
		*/

		$ROW = sql_fetch("
			SELECT
				COUNT(idx) AS give_count
			FROM
				cf_product_give
			WHERE 1
				AND product_idx = '".$prd_idx."'
				AND turn = '".$repay_turn."'
				AND turn_sno = '0'
				AND is_overdue = 'N'");

		$gived_count = count($REPAY[$repay_arr_no]['LIST']);

		if($ROW['give_count'] <> $gived_count) {
			$msg = "투자자수와 지급처리수가 동일하지 않습니다.\n다음 사항을 확인 하십시요.\n\n투자자: " . $ROW['give_count'] ."명\n지급수: " . number_format($gived_count) . "건";
			$RESULT_ARR = array('result' => 'ERROR', 'message' => $msg);
			echo json_encode($RESULT_ARR);
			exit;
		}
		else {
			if($FLAG['idx']=='' || $FLAG['loan_interest_state']=='') {
				$RESULT_ARR = array('result' => 'ERROR', 'message' => $msg);
				echo json_encode($RESULT_ARR);
				exit;
			}

			$res = sql_query("UPDATE cf_product_success SET invest_give_state = 'Y' WHERE idx = '".$FLAG['idx']."'");
			if($res) {
				$RESULT_ARR = array('result' => 'SUCCESS', 'message' => '');
				echo json_encode($RESULT_ARR);
			}
		}

	}		// end if($action=='invest_give_success')


	///////////////////////////////////////////////////////////////////////////////
	// 원금 지급완료 플래그
	///////////////////////////////////////////////////////////////////////////////
	if($action=='invest_principal_give_success') {

		$sql1 = "
			UPDATE
				cf_product_success
			SET
				invest_principal_give = 'Y'
			WHERE 1
				AND product_idx = '".$prd_idx."'
				AND turn = '".$repay_turn."'
				AND turn_sno = '0'";
		$res1 = sql_query($sql1);

		$sql2 = "
			UPDATE
				cf_product_invest
			SET
				is_return = 'Y',
				return_date = NOW()
			WHERE 1
				AND product_idx = '".$prd_idx."'
				AND invest_state = 'Y'";
		$res2 = sql_query($sql2);

		if($res1 && $res2) {
			$RESULT_ARR = array('result' => 'SUCCESS', 'message' => '');
			echo json_encode($RESULT_ARR);
		}
		else {
			$RESULT_ARR = array('result' => 'ERROR', 'message' => "DB UPDATE ERROR");
			echo json_encode($RESULT_ARR);
		}

	}		// end if($action=='invest_principal_give_success')


	/////////////////////////////////////////////////////////
	// 다중차수 동일상환계좌 상품의 참조번호 셋팅
	/////////////////////////////////////////////////////////
	if($action=='set_repay_target') {

		$vacct = trim($_REQUEST['vacct']);
		if(!$vacct) {
			$RESULT_ARR = array("result" => "FAIL", "message" => "전송된 계좌번호가 없습니다.");
			echo json_encode($RESULT_ARR); exit;
		}

		$sql = "
			UPDATE
				KSNET_VR_ACCOUNT
			SET
				REF_NO = '".$prd_idx."'
			WHERE 1
				AND USE_FLAG = 'Y'
				AND VR_ACCT_NO = '".$vacct."'";

		if(sql_query($sql)) {
			$RESULT_ARR = array("result" => "SUCCESS", "message" => "");
			echo json_encode($RESULT_ARR);
		}

	}		// end if($action=='set_repay_target')

}


sql_close();

// 최초 투자자 마킹 cf_product_invest.first_inv
shell_exec("/usr/local/php/bin/php " . G5_ADMIN_PATH . "/jipyo/first_inv_cli.php" . " > /dev/null 2>/dev/null &");

exit;

?>