<?
include_once("./_common.php");
include_once(G5_PATH.'/lib/insidebank.lib.php');

if($_POST['mb_no']) {

	$MB = sql_fetch("SELECT mb_no, member_type, mb_level, mb_id, mb_name, mb_co_name, mb_point FROM g5_member WHERE mb_no='".$_POST['mb_no']."'");
	$mb_id = $MB['mb_id'];

	$LOCK = sql_fetch("
		SELECT
			IFNULL(SUM(A.amount),0) AS invest_amount,
			COUNT(B.idx) AS invest_count
		FROM
			cf_product_invest A
		LEFT JOIN
			cf_product B  ON A.product_idx=B.idx
		WHERE 1
			AND A.member_idx='".$MB['mb_no']."'
			AND A.invest_state='Y'
			AND B.state=''
			AND B.display='Y'
	");

}

// 고객 투자정보조회(4100)
$ARR['REQ_NUM'] = "041";
$ARR['CUST_ID'] = $_POST['mb_no'];

$IB_RESULT = insidebank_request('256', $ARR);

if($IB_RESULT['RCODE']=='00000000') {

	$company_balance = get_point_sum($mb_id);

	if( in_array($MB['mb_level'], array('1','2','3','4','5')) && ($company_balance <> $MB['mb_point']) ) {
		$sqlx = "UPDATE g5_member SET mb_point = '".$company_balance."' WHERE mb_no = '".$MB['mb_no']."'";
		sql_query($sqlx);
	}

	$bank_balance = $IB_RESULT['BALANCE_AMT'];

	$print_name = ($MB['member_type']=='2') ? $MB['mb_co_name'] : $MB['mb_name'];

	echo "회원 : ".$mb_id." (".$print_name.")\n\n";
	echo "신한은행 예치금 : " . number_format($bank_balance) . "원 (투자대기 : ".number_format($LOCK['invest_count'])."개상품 " . number_format($LOCK['invest_amount']) . "원)\n";
	echo "헬로펀딩 예치금 : " . number_format($company_balance) . "원\n";
	if($company_balance <> $MB['mb_point']) {
		echo "회원표기액 : " . number_format($MB['mb_point']) . "원\n";
	}
	if($company_balance <> $MB['mb_point']) {
		echo "표기금 차액 : " . number_format($company_balance-$MB['mb_point']) . "원\n";
	}
	echo "\n";

	echo "신한은행 출금가능금액   : " . number_format($IB_RESULT['WITH_AMT']) . "원\n";

	if($bank_balance <> $company_balance) {
    echo "차액 : " . number_format($bank_balance - $company_balance) . "원\n";
	}
	else {
		if($company_balance > 0) echo "\n\n은행 예치금 잔액과 동일합니다.\n";
	}

	echo "\n[신한은행 자료]\n" .
	     "누적투자건수 : " . number_format($IB_RESULT['INV_CNT']) . "건\n" .
	     "누적투자금액 : " . number_format($IB_RESULT['INV_AMT']) . "원";

}
else {
	echo $IB_RESULT['ERRMSG'];
}

exit;

?>