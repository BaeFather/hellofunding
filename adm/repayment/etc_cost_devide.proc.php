<?

include_once("_common.php");

while( list($k, $v) = each($_POST) ) { ${$k} = trim($v); }


/////////////////////////////////////////////////
// 기타비용 배분 명목 데이터 가져오기 (폼수정용)
/////////////////////////////////////////////////
if($action=='get') {

	if( $R = sql_fetch("SELECT * FROM cf_etc_cost WHERE idx='".$idx."'") ) {

		$DATA['idx']            = $R['idx'];
		$DATA['product_idx']    = $R['product_idx'];
		$DATA['title']          = $R['title'];
		$DATA['memo']           = $R['memo'];
		$DATA['principal']      = $R['principal'];
		$DATA['interest']       = $R['interest'];
		$DATA['interest_tax']   = $R['interest_tax'];
		$DATA['local_tax']      = $R['local_tax'];
		$DATA['fee']            = $R['fee'];
		$DATA['rdatetime']      = $R['rdatetime'];
		$DATA['writer_id']      = $R['writer_id'];
		$DATA['edatetime']      = $R['edatetime'];
		$DATA['last_writer_id'] = $R['last_writer_id'];

		$ARR = array(
			'result'=>'success',
			'data_arr'=>$DATA,
			'message'=>''
		);
		echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

	}
	else {

		$ARR = array('result'=>'fail', 'message'=>'데이터가 없습니다.');
		echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);

	}

}


/////////////////////////////////////////////////
// 기타비용 배분 명목 등록
/////////////////////////////////////////////////
if($action=='new') {

	$title = sql_real_escape_string($title);
	$memo  = sql_real_escape_string($memo);

	$principal = preg_replace("/\,/", "", $principal);
	$interest = preg_replace("/\,/", "", $interest);
	$interest_tax = preg_replace("/\,/", "", $interest_tax);
	$local_tax = preg_replace("/\,/", "", $local_tax);
	$fee = preg_replace("/\,/", "", $fee);

	$sql = "
		INSERT INTO
			cf_etc_cost
		SET
			product_idx = '".$product_idx."',
			title = '".$title."',
			memo = '".$memo."',
			principal = '".$principal."',
			interest = '".$interest."',
			interest_tax = '".$interest_tax."',
			local_tax = '".$local_tax."',
			fee = '".$fee."',
			rdatetime = NOW(),
			writer_id = '".$member['mb_id']."'";
	//print_r($sql);

	if( $res = sql_query($sql) ) {
		$ARR = array('result'=>'success', 'message'=>'');
		echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);
	}
	else {
		$ARR = array('result'=>'fail', 'message'=>'DB처리중 오류발생!!'); echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);
	}

}

/////////////////////////////////////////////////
// 기타비용 배분 명목 수정
/////////////////////////////////////////////////
if($action=='edit') {

	//$title = sql_real_escape_string($title);
	//$memo  = sql_real_escape_string($memo);

	$fee = preg_replace("/\,/", "", $fee);

	$sql = "
		UPDATE
			cf_etc_cost
		SET
			product_idx = '".$product_idx."',
			title = '".$title."',
			memo = '".$memo."',
			fee = '".$fee."',
			edatetime = NOW(),
			last_writer_id = '".$member['mb_id']."'
		WHERE
			idx = '".$idx."'";
	//print_r($sql);

	if( $res = sql_query($sql) ) {
		$ARR = array('result'=>'success', 'message'=>'');
		echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);
	}
	else {
		$ARR = array('result'=>'fail', 'message'=>'DB처리중 오류발생!!'); echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);
	}

}


/////////////////////////////////////////////////
// 기타비용 배분 명목을 전문발송용 데이터로 등록
/////////////////////////////////////////////////
if($action=='ib_devide_ready') {

	/*
	Array
	(
			[action] => ib_devide_ready
			[etc_cost_idx] => 34
			[product_idx] => 8268
	)
	*/

	$ETC_COST = sql_fetch("SELECT idx, product_idx, principal, interest, interest_tax, local_tax, fee FROM cf_etc_cost WHERE idx = '".$etc_cost_idx."'");
	if(!$ETC_COST['idx']) {
		$ARR = array('result'=>'fail', 'message'=>'기타비용배분 데이터가 없습니다.');
		echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT); sql_close(); exit;
	}


	$invest_sql = "
		SELECT
			A.idx, A.amount, A.product_idx, A.member_idx, A.prin_rcv_no
		FROM
			cf_product_invest A
		LEFT JOIN
			cf_product B  ON A.product_idx = B.idx
		WHERE 1
			AND A.product_idx = '".$product_idx."' AND A.invest_state = 'Y'
			AND B.state = '1'
			AND B.isEtcCost = '1'
		ORDER BY
			A.idx DESC
		LIMIT 1";
	$INVEST = sql_fetch($invest_sql);
	if(!$INVEST['idx']) {
		$ARR = array('result'=>'fail', 'message'=>'기타비용배분처리용 투자정보가 없습니다.');
		echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT); sql_close(); exit;
	}

	$SDATE = ($SDATE) ? preg_replace("/-/", "", $SDATE) : date('Ymd');
	$PARTNER_CD = 'P0012';

	//처리대기중인 데이터의 마지막 SEQ값 가져오기... SEQ필드가 문자형이라 형변환 처리 함 => CAST(SEQ AS unsigned)
	$TMP = sql_fetch("SELECT MAX(CAST(SEQ AS unsigned)) AS max_seq FROM IB_FB_P2P_REPAY_REQ_DETAIL WHERE SDATE='' AND REG_SEQ='' AND req_idx IS NULL");
	$SEQ = $TMP['max_seq'] + 1;
	unset($TMP);

	$TR_AMT   = $ETC_COST['interest'] + $ETC_COST['principal'];			// 입금금액 => 세후이자 + 투자원금
	$TR_AMT_P = $ETC_COST['principal'];															// 입금금액중 원금
	$CTAX_AMT = $ETC_COST['interest_tax'] + $ETC_COST['local_tax'];
	$FEE      = $ETC_COST['fee'];

	// turn 값 무조건 +1 ===> 처리하지 않으면 기관전송예약시 같은 turn 값을 가진 전송대기대이터들과 개별처리가 불가해짐.
	// 기타비용배분처리용 상품은 turn 값이 배분요청을 구분하기 위한 파라미터로만 쓰여야 함. 정상정산페이지에서는 turn 을 이용하여 IB_FB_P2P_REPAY_REQ_DETAIL 테이블과 연계시 정상적인 정산이 되지 않는다.
	$ib_check_sql = "SELECT MAX(turn) AS max_turn FROM IB_FB_P2P_REPAY_REQ_DETAIL WHERE DC_NB = '".$INVEST['product_idx']."'";
	$IB_REQ_DETAIL = sql_fetch($ib_check_sql);


	$next_turn = $IB_REQ_DETAIL['max_turn'] + 1;


	$dtlsql = "
		INSERT INTO
			IB_FB_P2P_REPAY_REQ_DETAIL
		SET
			SEQ        = '".$SEQ."',
			PARTNER_CD = '".$PARTNER_CD."',
			DC_NB      = '".$INVEST['product_idx']."',
			CUST_ID    = '".$INVEST['member_idx']."',
			TR_AMT     = '".$TR_AMT."',
			TR_AMT_P   = '".$TR_AMT_P."',
			CTAX_AMT   = '".$CTAX_AMT."',
      FEE        = '".$FEE."',
			REPAY_RECEIPT_NB = '".$INVEST['prin_rcv_no']."',
			invest_idx = '".$INVEST['idx']."',
			turn       = '".$next_turn."',
			turn_sno   = '0',
			is_overdue = 'N',
			etc_cost_idx = '".$etc_cost_idx."',
			rdate      = NOW()";
	$res = sql_query($dtlsql);
	$insert_ok = sql_affected_rows();

	if($insert_ok) {
		$ARR = array('result'=>'success', 'message'=>'');
		echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);
	}
	else {
		$ARR = array('result'=>'fail', 'message'=>'전문대기자료 등록중 오류 발생');
		echo json_encode($ARR, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT); sql_close(); exit;
	}

}



@sql_close();
exit;

?>