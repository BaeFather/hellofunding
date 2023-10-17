#!/usr/local/php/bin/php -q
<?

$path = "/home/crowdfund/public_html";
include_once($path . '/common.cli.php');

$res = sql_query("
	SELECT
		idx, invest_period, invest_days, loan_start_date, loan_end_date, loan_end_date_orig
	FROM
		cf_product
	WHERE 1
		AND state IN('1','2','4','5','7','8','9') AND recruit_amount > 10000
		AND (loan_start_date > '0000-00-00' AND loan_end_date > '0000-00-00')
		AND turn_cnt = ''
	ORDER BY
		loan_start_date ASC");
$rows = $res->num_rows;

$no = 1;
while($PRDT = sql_fetch_array($res)) {

	$exceptionProduct = '';
	$shortTermProduct = ($PRDT['invest_period']==1 && $PRDT['invest_days'] > 0) ? true : false;
	$calcType = '';

	$turn_cnt  = repayTurnCount($PRDT['loan_start_date'], $PRDT['loan_end_date'], $exceptionProduct, $shortTermProduct, $calcType);
	$turn_cnt_orig  = ($PRDT['loan_end_date_orig']=='0000-00-00') ? $turn : repayTurnCount($PRDT['loan_start_date'], $PRDT['loan_end_date_orig'], $exceptionProduct, $shortTermProduct, $calcType);


	$sqlx = "UPDATE cf_product SET turn_cnt = '".$turn_cnt."', turn_cnt_orig = '".$turn_cnt_orig."' WHERE idx = '".$PRDT['idx']."'";
	echo $sqlx;
	if($_SERVER['argv']['1'] == 'yes') {
		sql_query($sqlx);
		echo "(". sql_affected_rows() .")";
	}
	echo "\n";



	$turn = $turn_orig = '';

	if($no==10) {
		//break;
	}
	$no++;
}



sql_close();
exit;

?>