#!/usr/local/php/bin/php -c /etc/php.ini -q
<?
## 헬로펀딩 - 신한은행간 예치금 차액 조사


$action = (@$_SERVER['argv']['1']) ? $_SERVER['argv']['1'] : 'debug';

set_time_limit(0);
include_once('_common.php');
include_once(G5_LIB_PATH.'/insidebank.lib.php');

$yyyymmdd = date("Y-m-d");

$sql = "
	SELECT
		A.mb_no, A.mb_id, A.mb_point, A.is_rest,
		(SELECT COUNT(idx) FROM cf_product_invest WHERE member_idx=A.mb_no) AS invest_count
	FROM
		g5_member A
	WHERE 1
		AND A.member_group = 'F'
		AND A.account_num!='' AND A.virtual_account2 != ''
		AND (SELECT COUNT(idx) FROM cf_product_invest WHERE member_idx=A.mb_no) > 0
	ORDER BY
		A.mb_point DESC, invest_count DESC";
$res = sql_query($sql);

$j = 1;
while($ROW = sql_fetch_array($res)) {

	(int)$co_balance   = get_point_sum($ROW['mb_id']);
	(int)$bank_balance = 0;

	// 고객 투자정보조회(4100)
	$ARR['REQ_NUM'] = "041";
	$ARR['CUST_ID'] = $ROW['mb_no'];

	$INSIDEBANK_RESULT = insidebank_request('256', $ARR);

	if($INSIDEBANK_RESULT['RCODE']=='00000000') {
		$bank_balance = $INSIDEBANK_RESULT['BALANCE_AMT'];
	}

	$diff = $bank_balance - $co_balance;

	$sqlx = "
		INSERT INTO
			IB_balance_check
		SET
			check_date = '".$yyyymmdd."',
			check_time = '".date('H:i:s')."',
			mb_no = '".$ROW['mb_no']."',
			mb_id = '".$ROW['mb_id']."',
			bank_balance = '".$bank_balance."',
			co_balance = '".$co_balance."',
			diff = '".$diff."'";

	//debug_flush($j.":".$sqlx."\n");

	if($action=='yes') {
		$resx = sql_query($sqlx);
	}

	$j++;

}

echo "Finish!!\n\n";

sql_close();

?>