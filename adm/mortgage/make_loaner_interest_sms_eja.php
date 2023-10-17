#!/usr/local/php/bin/php -q
<?
###############################################################################
## 차주 이자 납입 안내 스케줄러를 위한 이자 집계
###############################################################################

$base_path = "/home/crowdfund/public_html";
include_once($base_path . '/common.cli.php');
include_once($base_path . '/adm/mortgage/mortgage_common.php');
?>
<?
$sql = "SELECT * FROM cf_product_turn WHERE eja=0 AND canc='N' limit 100";  // 이자가 0, 취소 x
//$sql = "SELECT * FROM cf_product_turn WHERE idx=4368";  // 이자가 0, 취소 x
$res = sql_query($sql);
$cnt = $res->num_rows;

for ($i=0 ; $i<$cnt ; $i++) {

	$row = sql_fetch_array($res);

	$prd_idx = $row["product_idx"];
	$turn    = $row["turn"];

	$prd_sql = "SELECT loan_end_date FROM cf_product WHERE idx='$prd_idx'";  // 해당 상품 대출종료일
	$prd_res = sql_query($prd_sql);
	$prd_row = sql_fetch_array($prd_res);

	$tmp_end_date = str_replace( "-", "", substr($prd_row["loan_end_date"],0,7));

	if ($tmp_end_date<$row["tym"]) {  // 적용년월이 대출종료일보다 크면
		$canc_sql = "UPDATE cf_product_turn SET canc='Y' WHERE idx='$row[idx]'";  // 해당 상품의 취소 값을 Y로 업데이트
		sql_query($canc_sql);

		echo $prd_idx."-". $turn." - CANCELLEE\n";
		continue;
	}

	$bill_tbl = get_bill_tbl_name($prd_idx);

	/*
	$sql_ej = "SELECT SUM(floor(A.day_interest)) eja
				 FROM $bill_tbl A
				WHERE A.product_idx = $prd_idx
				  AND A.turn = $turn ";
	*/
	$sql_ej = "SELECT A.product_idx,A.turn,sum(ssum) eja
				 FROM (
						SELECT B.product_idx, B.turn , floor(SUM(B.day_interest)) ssum
						  FROM $bill_tbl B
						 WHERE B.product_idx=$prd_idx AND B.turn=$turn GROUP BY B.member_idx) A";

	$sql_row = sql_fetch($sql_ej);
	$eja = $sql_row["eja"];

	$up_sql1 = "UPDATE cf_product_turn SET eja='$eja' WHERE idx=$row[idx]";
	sql_query($up_sql1);

	$up_sql2 = "UPDATE cf_loaner_push_schedule SET eja='$eja' WHERE product_idx='$prd_idx' AND turn='$turn'";
	sql_query($up_sql2);


	echo $prd_idx."-". $turn."-".$eja."\n";
}


/*
$esql = "SELECT * FROM cf_loaner_push_schedule WHERE eja>0 AND msg LIKE '%price%'";
$eres = sql_query($esql);
$ecnt = $eres->num_rows;


for ($i=0 ; $i<$ecnt ; $i++) {
	$row = sql_fetch_array($eres);

	$chaju_info = get_chaju_info($row['product_idx']);
	$chaju_acc = $chaju_info[0]['virtual_account2'];

	$chunggu_amt = get_chaju_remain_amt($row[mb_no], $chaju_acc, $row[product_idx]) ;
	echo "*".$chunggu_amt;die();

	$up_sql = "UPDATE cf_loaner_push_schedule SET msg = REPLACE(msg , '{PRICE}' , '".number_format($row['eja'])."') WHERE idx=$row[idx]";
	echo $row["idx"]. " " . $up_sql1."\n";
	//sql_query($up_sql);
	//echo $row["msg"]."\n-----------------------------------------------\n";
	//$msg = str_replace("{PRICE}",number_format($row['eja']), $row['msg']);
	//echo $msg;

}
*/

?>

<?

?>