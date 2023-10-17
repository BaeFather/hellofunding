<?

include_once("_common.php");

while( list($k, $v) = each($_REQUEST) ) { if( !is_array(${$k}) )${$k} = trim($v); }

if(!$mode) {
	echo json_encode(array('result'=>'FAIL', 'message'=>'요청 파라미터 오류 (mode)'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;
}

if($mode=='list') {

	$where = " AND isTest='' AND recruit_amount >= '10000' AND ib_product_regist='Y'";
	if($idx) {
		$where.= " AND idx='".$idx."'";
	}
	if($state) {
		switch($state) {
			case 'investing' : $where.= " AND state='' AND invest_end_date='' AND recruit_period_start >= '".date('Y-m-d')."' AND recruit_period_end <= '".date('Y-m-d')."'";		break;		// 모집중
			case 'finished'  : $where.= " AND state IN('2','5')";		break;			// 모집종료
			default          : $where.= " AND state='".$state."'";		break;		// 각 상황별
		}
	}

	$sql = "
		SELECT
			idx, state, category, category2, mortgage_guarantees, title, recruit_amount,
			loan_usefee, loan_usefee_type, loan_usefee_repay_count,
			loan_start_date, loan_end_date, invest_period, invest_days
		FROM
			cf_product
		WHERE 1
			$where
		ORDER BY
			start_num DESC";
	//echo $sql; exit;
	$res = sql_query($sql);
	while( $R = sql_fetch_array($res) ) {

		if($R['loan_usefee_type']) {
			$R['title'].= ($R['loan_usefee_type']=='B') ? ' (선취)' : ' (후취)';
		}

		$R['print_invest_period']  = ($R['state'] == '') ? $R['invest_period'].'개월' : preg_replace('/-/', '.', $R['loan_start_date']).' ~ '.preg_replace('/-/', '.', $R['loan_end_date']);		// 대출기간

		// 특별처리상품 플래그 (초기상품중 종료일이 5일 이전일때 이전회차와 최종상환회차를 동일회차로 처리한 상품 구분)
		$exceptionProduct = ($R['idx'] < 162  && $R['ib_trust']=='N' && substr($R['loan_end_date'],-2) <= '05') ? 1 : 0;
		$shortTermProduct = ($R['invest_days']>0) ? 1 : 0;

		$R['repay_count']  = repayTurnCount($LIST[$i]['loan_start_date'], $LIST[$i]['loan_end_date'], $exception_product, $shortTermProduct);
		$R['total_days']   = repayDayCount($LIST[$i]['loan_start_date'], $LIST[$i]['loan_end_date']);

		$PLIST[] = $R;
	}

	if( !count($PLIST) ) {
		echo json_encode(array('result'=>'FAIL', 'message'=>'데이터 없음'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;
	}

	echo json_encode($PLIST, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);

}

if($mode=='detail') {

	if(!$idx) {
		echo json_encode(array('result'=>'NULL', 'message'=>''), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);	exit;
		//echo json_encode(array('result'=>'FAIL', 'message'=>'요청 파라미터 오류 (idx)'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);	exit;
	}

	$sql = "
		SELECT
			idx, category, category2, mortgage_guarantees, title, recruit_amount, loan_usefee, loan_usefee_type, loan_usefee_repay_count, loan_start_date, loan_end_date
		FROM
			cf_product
		WHERE
			idx = '".$idx."'";
	//echo $sql; exit;
	$PRDT = sql_fetch($sql);
	if(!$PRDT['idx']) {
		echo json_encode(array('result'=>'FAIL', 'message'=>'데이터 없음'), JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES); exit;
	}

	$PRDT['print_category'] = '';
	if($PRDT['category']=='1') {
		$PRDT['print_category'].= "부동산";
		$PRDT['print_category'].= ($PRDT['mortgage_guarantees']=='1') ? "&gt;주택담보" : "&gt;PF";
	}
	else if($PRDT['category']=='2') {
		$PRDT['print_category'].= "동산";
	}
	else if($PRDT['category']=='3') {
		$PRDT['print_category'].= "헬로페이";
		$PRDT['print_category'].= ($PRDT['category2']=='1') ? "&gt;면세점" : "&gt;소상공인";
	}


	$PRDT['print_loan_usefee_type'] = '';
	$PRDT['print_loan_usefee_repay_count'] = '';
	if($PRDT['loan_usefee_type']) {
		$PRDT['print_loan_usefee_type'] = ($PRDT['loan_usefee_type']=='B') ? '선취(일시납부)' : '후취(분할납부)';
		$PRDT['print_loan_usefee_repay_count'] = ($PRDT['loan_usefee_type']=='B') ? '-' : $PRDT['loan_usefee_repay_count'] . '회';
	}

	$PRDT['print_invest_period'] = '';
	if($PRDT['loan_start_date'] && $PRDT['loan_end_date']) {
		$PRDT['print_invest_period'] = preg_replace("/-/", ".", $PRDT['loan_start_date']) . ' ~ ' . preg_replace("/-/", ".", $PRDT['loan_end_date']) . ' ('.repayDayCount($PRDT['loan_start_date'], $PRDT['loan_end_date']).'일)';
	}

	echo json_encode($PRDT, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);

}

sql_close();
exit;

?>