#!/usr/local/php/bin/php -c /etc/php.ini -q
<?

set_time_limit(0);

include_once("_common.php");
include_once(G5_LIB_PATH."/insidebank.lib.php");


sql_query("TRUNCATE `cf_balance_check`");

/*
$sql = "
	SELECT
		(SELECT COUNT('idx') FROM cf_product_invest WHERE member_idx=A.mb_no AND product_idx > '161') AS invest_count,
		A.mb_no, A.mb_id, A.mb_point
	FROM
		g5_member A
	WHERE 1
		AND A.mb_level='1'
	ORDER BY
		A.mb_point DESC,
		A.mb_no ASC";
*/
$sql = "
	SELECT
		B.mb_no, B.mb_id, B.mb_point,
		(SELECT COUNT('idx') FROM cf_product_invest WHERE member_idx=A.member_idx AND product_idx > '161') AS invest_count
	FROM
		cf_product_invest A
	LEFT JOIN
		g5_member B  ON A.member_idx=B.mb_no
	WHERE 1
		AND A.product_idx='174'
--		AND A.product_idx='212'
--		AND A.product_idx='301'
		AND A.invest_state='Y'
	ORDER BY
		A.idx DESC";
$res = sql_query($sql);
$rows = sql_num_rows($res);

$j = 1;
for($i=0; $i<$rows; $i++) {

	$MB = sql_fetch_array($res);

	// 투자이력이 있는 사람만 체크
	if($MB['invest_count']) {
		//echo $j . ": "; print_r($MB); echo "\n";

		// 고객 투자정보조회(4100)
		$ARR['REQ_NUM'] = "041";
		$ARR['CUST_ID'] = $MB['mb_no'];
		if($insidebank_result = insidebank_request('256', $ARR)) {

			$difference = $MB['mb_point'] - $insidebank_result['BALANCE_AMT'];

			$sqlx = "
				INSERT INTO
					cf_balance_check
				SET
					check_date = '".date('Y-m-d')."',
					check_time = '".date('H:i:s')."',
					member_idx = '".$MB['mb_no']."',
					now_mb_point = '".$MB['mb_point']."',
					BALANCE_AMT = '".$insidebank_result['BALANCE_AMT']."',
					difference = '".$difference."',
					INV_CNT = '".$insidebank_result['INV_CNT']."',
					INV_AMT = '".$insidebank_result['INV_AMT']."'";
			$resx = sql_query($sqlx);
			if($resx) {
				debug_flush($j ." :: ". $MB['mb_no'] . " :: " . number_format($MB['mb_point']) . " :: " . number_format($insidebank_result['BALANCE_AMT']) . "\n");
			}
		}

		$j++;

	}

}

exit;

?>