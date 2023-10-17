<?

//set_time_limit(100);

include_once($_SERVER['DOCUMENT_ROOT'] . '/common.cli.php');

while(list($k, $v)=each($_REQUEST)) { ${$k} = @trim($v); }
//foreach($_REQUEST as $k=>$v) { ${$_REQUEST[$k]} = trim($v); }

$datetime_s = $date . ' 00:00:00';
$datetime_e = $date . ' 23:59:59';


/////////////////////////////////////////
// 지급 스케쥴 내역
/////////////////////////////////////////

$R = sql_fetch("
	SELECT
		MAX(idx) AS max_product_idx
	FROM
		cf_product
	WHERE 1
		AND display='Y' AND isTest=''
		AND state IN('1','2','4','5','8')
		AND loan_start_date <= '".$date."' AND loan_end_date >='".$date."'");

// 명세서 테이블 가져오기
$BILL_TABLE = array();
for($i=0,$j=1; $i<$R['max_product_idx']; $i++,$j++)		{
	if(($j%100)==1) {
		$bill_table = getBillTable($j);
		if(!in_array($bill_table, $BILL_TABLE)) array_push($BILL_TABLE, $bill_table);
	}
}

$table_count = count($BILL_TABLE);

$add_where = " AND B.display = 'Y' AND B.isTest='' AND B.recruit_amount >= '10000'";
if($type) {
	$add_where.= ($type=='long') ? " AND B.invest_days = 0" : " AND B.invest_days > 0";
}

$SCHEDULE = array(
	'product_count' => 0,		// 상품수
	'product' => '',				// 상품번호배열
	'turn' => ''						// 회차배열
);

for($i=0,$j=1; $i<$table_count; $i++,$j++) {

	$sql= "
		SELECT
			A.product_idx, A.turn,
			B.start_num
		FROM
			".$BILL_TABLE[$i]." A
		LEFT JOIN
			cf_product B  ON A.product_idx=B.idx
		WHERE 1
			AND A.repay_date = '".$date."' AND A.turn_sno = '0' AND A.is_overdue = 'N'
			$add_where
		GROUP BY
			A.product_idx,
			A.turn
		ORDER BY
			B.start_num ASC,
			A.turn ASC";

	//if($_REQUEST['mode']=='test') { print_rr($sql.";"); exit; }
	$res = sql_query($sql);
	$rows = sql_num_rows($res);

	for($k=0,$l=1; $k<$rows; $k++,$l++) {

		$ROW = sql_fetch_array($res);
		if($ROW['product_idx']) {
			$SCHEDULE['product_count'] += 1;
			$SCHEDULE['product'] .= $ROW['product_idx'] . ',';
			$SCHEDULE['turn']    .= $ROW['turn'] . ',';
		}
		unset($ROW);

	}

	sql_free_result($res);

}

if($_REQUEST['mode']=='test') { print_rr($SCHEDULE); exit; }

$SCHEDULE['product'] = substr($SCHEDULE['product'], 0, strlen($SCHEDULE['product'])-1);
$SCHEDULE['turn']    = substr($SCHEDULE['turn'], 0, strlen($SCHEDULE['turn'])-1);

//print_rr($SCHEDULE);



/////////////////////////////////////////
// 지급 완료 내역
/////////////////////////////////////////
if($date <= date('Y-m-d')) {
	$add_where2 = "";
	if($type) {
		$add_where2 = ($type=='long') ? " AND B.invest_days = 0" : " AND B.invest_days > 0";
	}

	// 지급내역 배열화 (지급처리일 기준)
	$sql = "
		SELECT
			A.product_idx
		FROM
			cf_product_give A
		LEFT JOIN
			cf_product B  ON A.product_idx=B.idx
		WHERE 1
			AND A.banking_date BETWEEN '".$datetime_s."' AND '".$datetime_e."'
			AND A.turn_sno = '0'
			AND A.is_overdue = 'N'
			AND B.recruit_amount >= '10000'
			$add_where2
		GROUP BY
			A.product_idx, A.turn, A.turn_sno, A.is_overdue
		ORDER BY
			B.start_num,
			B.open_datetime,
			B.idx";
	$res = sql_query($sql);
	$paid_product_count = sql_num_rows($res);

	$paid_product = "";
	for($i=0,$j=1; $i<$paid_product_count; $i++,$j++) {
		$ROW = sql_fetch_array($res);
		$paid_product.= $ROW['product_idx'];
		if($j < $paid_product_count) $paid_product.= ",";

		unset($ROW);

	}
	sql_free_result($res);
}

$DATE = explode("-", $date);


$RETURN_ARR = array(
	'schedule_count'     => (int)$SCHEDULE['product_count'],
	'schedule_product'   => (string)$SCHEDULE['product'],
	'schedule_turn'      => (string)$SCHEDULE['turn'],
	'paid_product_count' => (int)$paid_product_count,
	'paid_product'       => (string)$paid_product,
	'paid_year'          => (string)$DATE[0],
	'paid_month'         => (string)$DATE[1],
	'paid_day'           => (string)$DATE[2]
);

//print_rr($RETURN_ARR);

echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);


sql_close();
exit;

?>