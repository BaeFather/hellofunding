#!/usr/local/php/bin/php -q
<?
$base_path = "/home/crowdfund/public_html";
include_once($base_path . '/common.cli.php');
?>
<?
//$product_idx = $_REQUEST["product_idx"];
$product_idx = $argv[1];
//$product_idx = "4579";

if (!$product_idx) die("product_idx error");

make_turn_member($product_idx);


?>


<?
function make_turn_member($product_idx) {

	if (!$product_idx) return;

	$tbl_bill = getBillTable($product_idx);

	$turn_sql = "SELECT turn, turn_sno, bill_date, repay_date, if(turn_sno>0, bill_date,repay_date) bdate
				   FROM $tbl_bill 
				  WHERE product_idx='$product_idx' 
				  GROUP BY turn, turn_sno ORDER BY bdate";

	$turn_res = sql_query($turn_sql);
	$turn_cnt = $turn_res->num_rows;

	$total_turn = 0;

	$LIST = array();

	for ($i=0 ; $i<$turn_cnt ; $i++) {

		$turn_row = sql_fetch_array($turn_res);

		// 이미 정산된 데이타는 다시 정리하지 않는다.
		$chk_sql = "SELECT COUNT(idx) chk_cnt FROM cf_product_turn_member 
					 WHERE product_idx='$product_idx'
					   AND turn='".$turn_row["turn"]."' AND turn_sno='".$turn_row["turn_sno"]."' AND repay_yn='Y' ";
		$chk_row = sql_fetch($chk_sql);
		$chk_repay = $chk_row["chk_cnt"];
	

		$total_turn = $total_turn+1;
	

		$LIST[$i]["total_turn"] = $total_turn;
		$LIST[$i]["turn"] = $turn_row["turn"];
		$LIST[$i]["turn_sno"] = $turn_row["turn_sno"];

		if ($chk_repay) $LIST[$i]["repay_yn"] ="Y";
		else $LIST[$i]["repay_yn"] ="N";

	}


	for ($i=0 ; $i<count($LIST) ; $i++) {

		if ($LIST[$i]["repay_yn"] == "N") {
			$del_sql = "DELETE FROM cf_product_turn_member 
						 WHERE product_idx='$product_idx' 
						   AND turn='".$LIST[$i]["turn"]."'
						   AND turn_sno='".$LIST[$i]["turn_sno"]."'
						   AND repay_yn='N'
						";
			sql_query($del_sql);

		}

	}



	for ($i=0 ; $i<count($LIST) ; $i++) {


		if ($LIST[$i]["repay_yn"] == "Y") continue;

		$g_sql = "SELECT * FROM cf_product_give 
				   WHERE product_idx='$product_idx' 
				     AND turn='".$LIST[$i]["turn"]."'
					 AND turn_sno='".$LIST[$i]["turn_sno"]."'
					 ";

		$g_res = sql_query($g_sql);
		$g_cnt = $g_res->num_rows;

		if ($g_cnt) {		// 지급 내역이 있으면 지급내역으로 

			for ($j=0 ; $j<$g_cnt ; $j++) {

				$g_row = sql_fetch_array($g_res);

				$tot_inter = $g_row["interest"] + $g_row["interest_tax"] + $g_row["local_tax"] + $g_row["fee"];

				$ins_sql = "INSERT INTO cf_product_turn_member
									SET product_idx='$product_idx',
										member_idx='".$g_row["member_idx"]."',
										invest_idx='".$g_row["invest_idx"]."',
										total_turn='".$LIST[$i]["total_turn"]."',
										turn='".$LIST[$i]["turn"]."',
										turn_sno='".$LIST[$i]["turn_sno"]."',
										repay_date='".substr($g_row["banking_date"],0,10)."',
										principal='".$g_row["principal"]."',
										total_interest='".$tot_inter."',
										interest='".$g_row["interest"]."',
										fee = '".$g_row["fee"]."',
										interest_tax = '".$g_row["interest_tax"]."',
										local_tax = '".$g_row["local_tax"]."',
										repay_yn = 'Y',
										ins_datetime=NOW()
							";

				sql_query($ins_sql);
			}

		} else {        // 지급 내역이 없으면 청구내역으로

			if ($LIST[$i]["turn_sno"]=="0") {

				$sql = "SELECT member_idx, 
							   invest_idx,
							   sum(day_interest) sum_day_interest,
							   sum(fee) sum_day_fee,
							   partial_principal,
							   remain_principal,
							   repay_date,
							   rtimestamp
						  FROM $tbl_bill 
						 WHERE product_idx='$product_idx' 
						   AND turn='".$LIST[$i]["turn"]."' 
						 GROUP BY member_idx 
						 ORDER BY idx";

			} else {

				$sql = "SELECT member_idx,
							   invest_idx,
							   sum(day_interest) sum_day_interest,
							   sum(fee) sum_day_fee,
							   partial_principal,
							   remain_principal,
							   repay_date,
							   rtimestamp
						  FROM $tbl_bill 
						 WHERE product_idx='$product_idx' 
						   AND turn='".$LIST[$i]["turn"]."' 
						   AND turn_sno='".$LIST[$i]["turn_sno"]."' 
						 GROUP BY member_idx 
						 ORDER BY idx";

			}

			$res = sql_query($sql);
			$cnt = $res->num_rows;	

			for ($j=0 ; $j<$cnt ; $j++) {

				$row = sql_fetch_array($res);

				$mem_sql = "SELECT member_type, is_creditor FROM g5_member WHERE mb_no='$row[member_idx]'";
				$mem_row = sql_fetch($mem_sql);

				if( preg_match("/\.9999/", $row["sum_day_interest"])) $j_inter = floor(customRoundOff($row["sum_day_interest"]));
				else $j_inter = floor($row["sum_day_interest"]);

				if( preg_match("/\.9999/", $row["sum_day_fee"])) $j_fee = floor(customRoundOff($row["sum_day_fee"]));
				else $j_fee = floor($row["sum_day_fee"]);

				if ($i==count($LIST)-1) $prin = $row["remain_principal"];
				else $prin = 0;
				
				$interest_tax_ratio = get_inter_tax_ratio($mem_row["member_type"], $mem_row["is_creditor"]);
				$local_tax_ratio = 0.1;


				$interest_tax    = floor( $j_inter * $interest_tax_ratio / 10 ) * 10;		// 당월 이자소득세 = 이자수익 * 0.25
				$local_tax       = floor( $interest_tax * $local_tax_ratio / 10 ) * 10;					// 당월 지방소득세(원단위 절사)
				$inter2          = $j_inter - $interest_tax - $local_tax - $j_fee;

				$ins_sql = "INSERT INTO cf_product_turn_member
									SET product_idx='$product_idx',
										member_idx='".$row["member_idx"]."',
										invest_idx='".$row["invest_idx"]."',
										total_turn='".$LIST[$i]["total_turn"]."',
										turn='".$LIST[$i]["turn"]."',
										turn_sno='".$LIST[$i]["turn_sno"]."',
										repay_date='".$row["repay_date"]."',
										principal='".$prin."',
										total_interest='".$j_inter."',
										interest='".$inter2."',
										interest_tax = '".$interest_tax."',
										local_tax = '".$local_tax."',
										fee = '".$j_fee."',
										bill_rtimestamp = '".$row["rtimestamp"]."',
										ins_datetime=NOW()
							";


				sql_query($ins_sql);

				$LIST[$i]["member"][$j]["member_idx"] = $row["member_idx"];
				$LIST[$i]["member"][$j]["sum_day_interest"] = $row["sum_day_interest"];
				$LIST[$i]["member"][$j]["sum_day_interest_jojung"] = $j_inter;

			}

		}
		

	}


	make_turn_sum($product_idx);

}

function make_turn_sum($product_idx) {

	$sql = "SELECT total_turn, turn, turn_sno,repay_date, repay_yn,
				   SUM(principal) sum_principal,
				   SUM(total_interest) sum_tot_interest,
				   SUM(interest) sum_interest,
				   SUM(interest_tax) sum_interest_tax,
				   SUM(local_tax) sum_local_tax,
				   SUM(fee) sum_fee,
				   SUM(overdue_interest) sum_overdue_interest,
				   bill_rtimestamp
			  FROM cf_product_turn_member
			 WHERE product_idx='$product_idx'
			 GROUP BY turn, turn_sno
			 ORDER BY total_turn";
	$res = sql_query($sql);
	$cnt = $res->num_rows;

	for ($i=0 ; $i<$cnt ; $i++) {

		$row = sql_fetch_array($res);

		$chk_sql = "SELECT * FROM cf_product_turn_sum WHERE product_idx='$product_idx' AND turn='".$row["turn"]."' AND turn_sno='".$row["turn_sno"]."'";
		$chk_res = sql_query($chk_sql);
		$chk_cnt = $chk_res->num_rows;

		if ($chk_cnt) {
			$chk_row = sql_fetch_array($chk_res);
			if ($chk_row["repay_yn"]=="Y") continue;
			else {
				$del_sql = "DELETE FROM cf_product_turn_sum WHERE idx='".$chk_row["idx"]."'";
				sql_query($del_sql);
			}
		}

		// 실수령액 계산
		$in = $row["sum_principal"] + $row["sum_interest"] + $row["sum_overdue_interest"];
		$out = $row["sum_interest_tax"] + $row["sum_local_tax"] + $row["sum_fee"];
		$net = $in - $out;

		$ins_sql = "INSERT INTO cf_product_turn_sum
							SET product_idx='$product_idx',
								total_turn='".$row["total_turn"]."',
								turn='".$row["turn"]."',
								turn_sno='".$row["turn_sno"]."',
								repay_date='".$row["repay_date"]."',
								repay_yn='".$row["repay_yn"]."',
								principal='".$row["sum_principal"]."',
								total_interest='".$row["sum_tot_interest"]."',
								interest='".$row["sum_interest"]."',
								interest_tax='".$row["sum_interest_tax"]."',
								local_tax='".$row["sum_local_tax"]."',
								fee='".$row["sum_fee"]."',
								net='".$net."',
								overdue_interest='".$row["sum_overdue_interest"]."',
								repayment_date='".$row["repay_date"]."',
								bill_rtimestamp='".$row["bill_rtimestamp"]."',
								ins_datetime=NOW()";
		sql_query($ins_sql);

	}


}

?>

<?
function get_inter_tax_ratio($member_type, $is_creditor) {

	if ($is_creditor=="Y") $interest_tax_ratio = 0 ;
	else $interest_tax_ratio = ($member_type=='2') ? 0.25 : 0.14;

	return $interest_tax_ratio;

}
?>
