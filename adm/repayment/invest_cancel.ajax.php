<?
###############################################################################
## (관리자용) 투자취소처리
###############################################################################

set_time_limit(0);

include_once('./_common.php');
include_once(G5_LIB_PATH . '/insidebank.lib.php');

if($is_admin != 'super' && $w == '') {
	$RESULT_ARR = array('result' => 'ERROR', 'message' => 'LOGIN_PLEASE');
	echo json_encode($RESULT_ARR);
	exit;
}

$invest_idx = trim($_REQUEST['invest_idx']);
if(!$invest_idx) {
	$RESULT_ARR = array('result' => 'ERROR', 'message' => '투자번호가 없습니다.');
	echo json_encode($RESULT_ARR);
	exit;
}

$INVEST = sql_fetch("
	SELECT
		A.idx, A.amount, A.member_idx, A.product_idx, A.invest_state, A.prin_rcv_no, A.syndi_id, A.ib_regist,
		B.title, B.state, B.recruit_amount, B.invest_end_date
	FROM
		cf_product_invest A
	INNER JOIN
		cf_product B  ON A.product_idx = B.idx
	WHERE 1
		AND A.idx = '".$invest_idx."'");

if(!$INVEST) {
	$RESULT_ARR = array('result' => 'ERROR', 'message' => '해당 투자번호에 대한 투자정보가 없습니다.');
	echo json_encode($RESULT_ARR);
	exit;
}

if($INVEST['invest_state'] == 'N') {
	$RESULT_ARR = array('result' => 'ERROR', 'message' => '이미 취소처리 된 투자건 입니다.');
	echo json_encode($RESULT_ARR);
	exit;
}

if($INVEST['state'] != '') {
	$RESULT_ARR = array('result' => 'ERROR', 'message' => '대출실행 되었거나 종료된 투자건은 취소 불가합니다.');
	echo json_encode($RESULT_ARR);
	exit;
}


// 은행측에 취소전문 발송
if($INVEST['ib_regist']=='1') {

	$ARR['REQ_NUM']     = "020";
	$ARR['SUBMIT_GBN']  = "07";												// 거래구분	(변경:06, 취소:07)
	$ARR['LOAN_SEQ']    = $INVEST['product_idx'];			// 대출식별번호
	$ARR['INV_SEQ']     = $INVEST['idx'];							// 투자자건수일련번호(변경불가항목)
	$ARR['INV_CUST_ID'] = $INVEST['member_idx'];			// 투자자고객ID
	$ARR['PRIN_RCV_NO'] = $INVEST['prin_rcv_no'];			// 원리금수취권번호: M회원번호P상품번호I투자번호
	$ARR['INV_AMT']     = $INVEST['amount'];			    // 투자금액
	$insidebank_result = insidebank_request('256', $ARR);

}


$update_sql = "
	UPDATE
		cf_product_invest
	SET
		invest_state='N',
		cancel_date=NOW(),
		cancel_by='admin'
	WHERE 1
		AND idx='".$invest_idx."'";
if( sql_query($update_sql) ) {
	// 투자내역상세정보 변경
	$update_sql2 = "
		UPDATE
			cf_product_invest_detail
		SET
			invest_state='N',
			cancel_date=NOW()
		WHERE 1
			AND invest_idx='".$invest_idx."'";
	$result2 = sql_query($update_sql2);

	$MB = sql_fetch("SELECT * FROM g5_member WHERE mb_no='".$INVEST['member_idx']."'");
	$po_content = $INVEST['title']. '-관리자에 의한 투자 취소';
	$po_rel_action = $MB['mb_id'].'-'.uniqid('');

	if($MB['mb_no']) {

		insert_point($MB['mb_id'], $INVEST['amount'], $po_content, '@cancel', $MB['mb_id'], $po_rel_action, 0);

		//////////////////////////////////////////////////////////////////////////
		// (!중요)상품관리테이블에 실시간 모집금액 반영하기 :: 2021-02-15 추가
		//////////////////////////////////////////////////////////////////////////
		sql_query("UPDATE cf_product SET live_invest_amount = live_invest_amount - {$INVEST['amount']} WHERE idx = '".$INVEST['product_idx']."'");
		//////////////////////////////////////////////////////////////////////////

	}


	// 현재모집총액 다시 호출
	$INVESTED = sql_fetch("SELECT IFNULL(SUM(amount),0) AS amount FROM cf_product_invest WHERE product_idx='".$INVEST['product_idx']."' AND invest_state='Y'");


	// 모집완료일이 기록된 상품일 경우 현재 모집총액이 모집목표금액보다 적은 경우 모집 모집중인 상품으로 변경처리 (모집완료일 날리기)
	if($INVEST['invest_end_date']) {
		if($INVEST['recruit_amount'] > $INVESTED['amount']) {
			$update_sql3 = "
				UPDATE
					cf_product
				SET
					invest_end_date = '',
					live_invest_amount = '".$INVESTED['amount']."'
				WHERE
					idx = '".$INVEST['product_idx']."'";
			$result3 = sql_query($update_sql3);
		}
	}

	$RESULT_ARR = array('result' => 'SUCCESS', 'message' => '');
	echo json_encode($RESULT_ARR);

	// 올리고 취소자 전송 전송 --------------------
	//if($INVEST['syndi_id']=='oligo') {
	//	@shell_exec("/usr/local/php/bin/php -q " . G5_PATH."/syndicate/oligo/report/investCancelReport.php " . $INVEST['idx']);
	//	@shell_exec("/usr/local/php/bin/php -q " . G5_PATH."/syndicate/oligo/report/productStateReport.php " . $INVEST['product_idx']);
	//}
	// 올리고 취소자 전송 전송 --------------------


	//////////////////////////////////////////////////////////////////////
	// 금결원 중앙기록관리 투자신청취소 전송
	//////////////////////////////////////////////////////////////////////
	$p2pctr_canc_result = p2pctr_invest_register_canc($INVEST['member_idx'], $INVEST['product_idx'], $INVEST['idx']);
	if($p2pctr_canc_result) {

		// 투자한도 업데이트 실행
		$exec_str = "/usr/local/php/bin/php -q /home/crowdfund/public_html/investment/get_p2pctr_limit_amt.exec.php " .  $INVEST['member_idx'];
		$exec_result = shell_exec($exec_str);

	}

}
else {

	$RESULT_ARR = array('result' => 'ERROR', 'message' => '투자정보 업데이트가 실패하였습니다.\\n관리자에게 문의하십시요.');
	echo json_encode($RESULT_ARR);
	exit;

}



sql_close();

exit;

?>