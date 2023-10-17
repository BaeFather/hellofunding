#!/usr/local/php/bin/php -q
<?
// repay_schedule_detail.ajax.php 에서 호출됨

set_time_limit(0);

$base_path  = "/home/crowdfund/public_html";

include_once($base_path . '/common.cli.php');


$prd_idx = (@$_SERVER['argv']['1']) ? @$_SERVER['argv']['1'] : $_REQUEST['prd_idx'];
$turn = (@$_SERVER['argv']['2']) ? @$_SERVER['argv']['2'] : $_REQUEST['turn'];
$date = (@$_SERVER['argv']['3']) ? @$_SERVER['argv']['3'] : $_REQUEST['date'];

if($prd_idx=='' || $turn=='') {
	$RETURN_ARR = array('result'=>'ERROR', 'msg'=>'품번,회차 전송 안됨');
	echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
	sql_close(); exit;
}

$bill_table = getBillTable($prd_idx);

$sql = "
	SELECT
		idx, start_num, title, recruit_amount, invest_return, loan_start_date, loan_end_date, invest_period, invest_days
	FROM
		cf_product
	WHERE 1
		AND idx = '".$prd_idx."'";
//print_rr($sql);
$PRDT = sql_fetch($sql);
if(!$PRDT['idx']) {
	$RETURN_ARR = array('result'=>'ERROR', 'msg'=>'상품정보없음');
	echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
	sql_close(); exit;
}

$sql = "
	SELECT
		A.idx
	FROM
		cf_product_invest A
	LEFT JOIN
		g5_member B  ON A.member_idx=B.mb_no
	WHERE 1
		AND A.product_idx = '".$prd_idx."'
		AND A.invest_state = 'Y'
	ORDER BY
		idx ASC";
$res = sql_query($sql);
$invest_count = $res->num_rows;

$invest_idx_arr = $first_invest_idx = '';
for($i=0; $i<$invest_count; $i++) {
	if( $r = sql_fetch_array($res) ) {
		if($i==0) $first_invest_idx = $r['idx'];
		$invest_idx_arr.= "'".$r['idx']."',";
	}
}
$invest_idx_arr = substr($invest_idx_arr, 0, -1);
//echo $invest_idx_arr . "<br/><br/>\n";

// 빌링테이블에서 해당 회차의 가장작은 날짜번호 추출 ($first_invest_idx 조건절 없을시 검색속도 확 떨어짐)
$sql = "
	SELECT
		dno, bill_date
	FROM
		{$bill_table}
	WHERE 1
		AND invest_idx = '".$first_invest_idx."' AND turn = '".$turn."' AND turn_sno = '0' AND is_overdue = 'N'
	ORDER BY
		dno ASC
	LIMIT 1";
//print_rr($sql);
$BILL_START = sql_fetch($sql);

if(!$BILL_START['dno']) {
	$RETURN_ARR = array('result'=>'ERROR', 'msg'=>'빌링정보없음');
	echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
	sql_close(); exit;
}

$exceptionProduct = ($PRDT['idx'] < 162  && substr($PRDT['loan_end_date'],-2) <= '05') ? 1 : 0;
$shortTermProduct = ($PRDT['invest_days'] > 0) ? 1 : 0;

$print_alt_data = $PRDT['title'] . " / ";
$print_alt_data.= price_cutting($PRDT['recruit_amount']) . "원 / ";
$print_alt_data.= floatRtrim($PRDT['invest_return']) . "% / ";
$print_alt_data.= preg_replace('/-/', '.', $PRDT['loan_start_date']) . "~" . preg_replace('/-/', '.', $PRDT['loan_end_date']);
$print_alt_data.= ($shortTermProduct) ? " (".$PRDT['invest_days']."일)" : " (".$PRDT['invest_period']."개월)";

$PRDT['last_turn'] = repayTurnCount($PRDT['loan_start_date'], $PRDT['loan_end_date'], $exceptionProduct, $shortTermProduct);

$print_invest_return = floatRtrim($PRDT['invest_return']) . "%";
if($turn > $PRDT['last_turn']) {
	$print_turn = "<font color=red>" . $turn . " / " . $PRDT['last_turn'] . "</font>";
}
else {
	$print_turn = $turn . " / " . $PRDT['last_turn'];
}


$s_sql = "
	SELECT
		idx, loan_interest_state, loan_principal_state, ib_request_ready, invest_give_state, invest_principal_give
	FROM
		cf_product_success
	WHERE 1
		AND product_idx = '".$prd_idx."' AND turn = '".$turn."' AND turn_sno='0'";
$SUCC = sql_fetch($s_sql);


$print_repay_state = "<font color='#999'>-</font>";
if($SUCC['idx']) {
	if($SUCC['loan_interest_state']=='Y')  $print_repay_state = "<font color='magenta'>이자수급완료</font>";
	if($SUCC['loan_principal_state']=='Y') $print_repay_state = "<font color='magenta'>원금수급완료</font>";
	if($SUCC['loan_interest_state']=='Y' && $SUCC['loan_principal_state'])  $print_repay_state = "<font color='magenta'>원리금수급완료</font>";
	if($SUCC['ib_request_ready']=='Y')		 $print_repay_state = "<font color='#FF222'>전문발송대기</font>";
	if($SUCC['invest_give_state']) {
		if($SUCC['invest_give_state']=='W')      $print_repay_state = "<font color='#FF222'>배분처리중</font>";
		else if($SUCC['invest_give_state']=='S') $print_repay_state = "<font color='#FF222'>배분완료</font>";
		else if($SUCC['invest_give_state']=='Y') $print_repay_state = "<font color='#3366ff'>이자지급완료</font>";
	}
	if($SUCC['invest_principal_give']=='Y')		 $print_repay_state = "<font color='#000'>원금상환완료</font>";
}

$is_paid = ($SUCC['invest_give_state']=='Y' || $SUCC['invest_principal_give']=='Y') ? 1 : 0;


// 명세내역 가져오기 (부분상환 또는 연체명세 제외)
$sql = "
	SELECT
		A.bill_date, A.remain_principal, A.repay_date,
		( SELECT FLOOR(IFNULL(SUM(day_interest),0)) FROM {$bill_table} WHERE invest_idx = A.invest_idx AND turn = A.turn AND A.turn_sno = A.turn_sno AND is_overdue = A.is_overdue ) AS _interest,
		( SELECT FLOOR(IFNULL(SUM(fee),0)) FROM {$bill_table} WHERE invest_idx = A.invest_idx AND turn = A.turn AND A.turn_sno = A.turn_sno AND is_overdue = A.is_overdue ) AS _fee,
		A.member_idx, B.member_type, B.is_creditor, B.remit_fee
	FROM
		{$bill_table} A
	LEFT JOIN
		g5_member B  ON A.member_idx=B.mb_no
	WHERE 1
		AND A.invest_idx IN(".$invest_idx_arr.")
		AND A.turn = '".$turn."' AND A.turn_sno = '0' AND A.is_overdue = 'N'
		AND A.dno = '".$BILL_START['dno']."'
	-- ORDER BY A.idx
	";
//print_rr($sql);
$res = sql_query($sql);
$rows = $res->num_rows;

$SCHEDULE = array(
	'principal'     => 0,
	'interest'      => 0,
	'interest_tax'  => 0,
	'local_tax'     => 0,
	'tax'           => 0,
	'fee_supply'    => 0,
	'fee_vat'       => 0,
	'fee'           => 0,
	'last_interest' => 0,
	'last_amount'   => 0
);

for($i=0; $i<$rows; $i++) {
	$ROW = sql_fetch_array($res);


	//--------------------------------------------------------------------------------------------
	// 이자정산지급일에 따른 세율 변환
	/*
	// [개인]
	$interest_tax_ratio = $CONF['indi']['interest_tax_ratio'];		// 이자소득세 : 14%
	$local_tax_ratio    = $CONF['indi']['local_tax_ratio'];		// 지방세: 이자소득세의 10% => 합계 15.4%
	// [법인]
	if($ROW['member_type']=='2') {
		$interest_tax_ratio = $CONF['corp']['interest_tax_ratio'];		// 이자소득세 : 25%
		$local_tax_ratio    = $CONF['corp']['local_tax_ratio'];				// 지방세: 이자소득세의 10% => 합계 27.5%
	}
	*/


$CONF['corp'] = array(
	'interest_tax_ratio' => 0.25,		// 이자소득세 : 25%
	'local_tax_ratio'    => 0.1			// 지방세: 이자소득세의 10% => 합계 27.5%
);
$CONF['indi'] = array(
	'interest_tax_ratio' => 0.14,		// 이자소득세 : 14%
	'local_tax_ratio'    => 0.1			// 지방세: 이자소득세의 10% => 합계 15.4%
);

	if($PRDT['loan_start_date'] >= '2021-08-27') {
		$interest_tax_ratio = ($ROW['member_type']=='2') ? $CONF['corp']['interest_tax_ratio'] : $CONF['indi']['interest_tax_ratio'];
	}
	else {
		if( $ROW['repay_date'] < '2021-10-21' ) {

			$interest_tax_ratio = $CONF['interest_tax_ratio'];

			// 0.14로 정산된것 상품회차 예외처리
			if( $prd_idx == '6281' && $turn >= 3) {
				if($ROW['member_type']=='1') $interest_tax_ratio = $CONF['indi']['interest_tax_ratio'];
			}

			// 0.14로 정산된 상품 예외처리
			if( in_array($prd_idx, array('6561','6573','6584','6596','6607')) ) {
				if($ROW['member_type']=='1') $interest_tax_ratio = $CONF['indi']['interest_tax_ratio'];
			}

		}
		else {
			$interest_tax_ratio = ($ROW['member_type']=='2') ? $CONF['corp']['interest_tax_ratio'] : $CONF['indi']['interest_tax_ratio'];
		}
	}

	$local_tax_ratio = 0.1;		// interest_tax_ratio의 10%


	//--------------------------------------------------------------------------------------------

	//echo $ROW['repay_date'] . " : " . $interest_tax_ratio . " " . $local_tax_ratio . "\n";


	$principal = ($turn==$PRDT['last_turn']) ? $ROW['remain_principal'] : '0';
	$interest  = $ROW['_interest'];			// 세전이자
	$fee       = $ROW['_fee'];					// 플랫폼수수료

	$interest_tax = floor( ($interest * $interest_tax_ratio) / 10 ) * 10;		// 당월 이자소득세 = 이자수익 * 0.25
	$local_tax    = floor( ($interest_tax * $local_tax_ratio) / 10 ) * 10;					// 당월 지방소득세(원단위 절사)

	// 원천징수 제외
	if($ROW['is_creditor']=='Y') {
		// 대부업 회원
		$interest_tax = 0;
		$local_tax    = 0;
	}
	else {
		// 법인 이자소득세 1000원 미만인 경우 (소액부징수)
		if($ROW['member_type']=='2') {
			if($interest_tax < 1000 && $ROW['repay_date'] > '2021-11-19') {
				$interest_tax = 0;
				$local_tax    = 0;
			}
		}
	}


	$tax = $interest_tax + $local_tax;					// 당월 세금 합계

	$fee_supply = ceil($fee / 1.1);					// 공급가액
	$fee_vat    = ($fee - $fee_supply) ;		// 부가세

	$last_interest = $interest - $tax - $fee;			// 실지급이자
	$last_amount = $principal + $last_interest;		// 실지급총액


	$SCHEDULE['principal']     += $principal;
	$SCHEDULE['interest']      += $interest;

	$SCHEDULE['interest_tax']  += $interest_tax;
	$SCHEDULE['local_tax']     += $local_tax;
	$SCHEDULE['tax']           += $tax;

	$SCHEDULE['fee_supply']    += $fee_supply;
	$SCHEDULE['fee_vat']       += $fee_vat;
	$SCHEDULE['fee']           += $fee;

	$SCHEDULE['last_interest'] += $last_interest;
	$SCHEDULE['last_amount']   += $last_amount;

	$principal = $interest = $interest_tax = $local_tax = $tax = $fee_supply = $fee_vat = $fee = $last_interest = $last_amount = NULL;

	unset($ROW);

}
sql_free_result($res);




$RETURN_ARR = array(
	'idx'           => (string)$PRDT['idx'],
	'title'         => (string)$PRDT['title'],
	'alt_data'      => (string)$print_alt_data,
	'turn'          => (string)$print_turn,
	'taret_turn'    => (string)$turn,
	'repay_state'   => (string)$print_repay_state,
	'invest_return' => (string)$print_invest_return,
	'invest_count'  => (string)$invest_count,
	'principal'     => (string)$SCHEDULE['principal'],
	'interest'      => (string)$SCHEDULE['interest'],
	'interest_tax'  => (string)$SCHEDULE['interest_tax'],
	'local_tax'     => (string)$SCHEDULE['local_tax'],
	'tax'           => (string)$SCHEDULE['tax'],
	'fee_supply'    => (string)$SCHEDULE['fee_supply'],
	'fee_vat'       => (string)$SCHEDULE['fee_vat'],
	'fee'           => (string)$SCHEDULE['fee'],
	'last_interest' => (string)$SCHEDULE['last_interest'],
	'last_amount'   => (string)$SCHEDULE['last_amount']
);

echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);

$PRDT = $SUCC = $SCHEDULE = NULL;
sql_close();
exit;

?>