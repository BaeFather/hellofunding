<?
function get_eja($prd_idx, $turn) {

	global $path;

	$eja = 0;

	$tbl_name = get_bill_tbl_name($prd_idx);
	
	$exec_path = "/usr/local/php/bin/php  $path/adm/repayment/repay_schedule_detail.exec.php $prd_idx $turn";
	exec($exec_path, $retval);
	
	$res_str = implode("", $retval);
	$res_arr = json_decode($res_str, true);

	$eja = $res_arr['interest'];
	
	$up_sql = "UPDATE cf_loaner_push_schedule SET eja='$eja' WHERE product_idx='$prd_idx' AND turn='$turn' AND send_status=0";
	sql_query($up_sql);
	
	return $eja;
}
function get_bill_tbl_name($prd_idx) {
	$tbl_name = floor($prd_idx/1000)*1000;
	$tbl_name = "cf_product_bill_".str_pad($tbl_name, 5, "0", STR_PAD_LEFT);

	return $tbl_name;
}
function get_chaju_remain_amt($mb_no, $acct_no, $prd_idx) {
	$in_amt  = get_chaju_in_amt($mb_no, $acct_no, $prd_idx); // 입금액
	$out_amt = get_chaju_out_amt($prd_idx);                  // 출금액 
	$tmp_amt = get_chaju_tmp_amt($prd_idx);

	$remain_amt = $in_amt - $out_amt + $tmp_amt;
	//if ($prd_idx=="3332") echo "$remain_amt = $in_amt - $out_amt + $tmp_amt";
	return $remain_amt;
}
function get_chul_amt($prd_idx,$ym) {
	$out_amt = 0 ;
	$out_sql = "
		SELECT SUM(principal + interest + interest_tax + local_tax + fee) AS sum_out_amt, banking_date
		FROM
			cf_product_give
		WHERE 1
			AND product_idx='".$prd_idx."'
			AND date>='$ym-05' AND date<='$ym-31'";
	$out_row = sql_fetch($out_sql);
	if ($out_row["sum_out_amt"]) $out_amt = $out_row["sum_out_amt"];

	return $out_amt;
	//return $out_row;
}
function get_chaju_tmp_amt($prd_idx) {
	$sql = "SELECT  sum(IF(gubun=2,amount*(-1),amount)) amt FROM cf_repay_tmp_log WHERE product_idx=$prd_idx AND draw_id=''";
	$row = sql_fetch($sql);
	return $row["amt"];
}
function get_chaju_in_amt($mb_no, $acct_no, $prd_idx) {

	$in_amt = 0;

	//$add_where = ($PRDT['use_product_count'] > 1) ? " AND repay_prd_idx='".$prd_idx."'" : "";		// 그룹상품일 경우 자기 상품번호가 등록된 입금내역을 가져오도록... 자기 상품번호는 사전에 수동 입력해줘야 함

	$in_sql = "
		SELECT SUM(TR_AMT) sum_in_amt
		FROM
			IB_FB_P2P_IP
		WHERE 1
			AND CUST_ID='$mb_no'
			AND ACCT_NB='$acct_no'
			AND TR_AMT_GBN='20'
			AND repay_prd_idx IN('', '$prd_idx')
			$add_where
		ORDER BY
			SR_DATE, FB_SEQ, ERP_TRANS_DT";
	$in_row = sql_fetch($in_sql);

	if ($in_row["sum_in_amt"]) $in_amt = $in_row["sum_in_amt"];

	return $in_amt;
}

function get_chaju_out_amt($prd_idx) {

	$out_amt = 0 ;
	$out_sql = "
		SELECT SUM(principal + interest + interest_tax + local_tax + fee) AS sum_out_amt
		FROM
			cf_product_give
		WHERE 1
			AND product_idx='".$prd_idx."'
		ORDER BY
			turn, turn_sno, is_overdue";
	$out_row = sql_fetch($out_sql);

	if ($out_row["sum_out_amt"]) $out_amt = $out_row["sum_out_amt"];

	return $out_amt;
}

function get_chaju_info($prd_idx) {
	
	$LIST = array();
	
	$sql = "SELECT loan_mb_no FROM cf_product WHERE idx='$prd_idx'";
	$row = sql_fetch($sql);
	//echo $row[loan_mb_no]."\n";
	
	$sqlm = "SELECT mb_no, mb_name, mb_hp, va_bank_code2, virtual_account2, va_private_name2 FROM g5_member WHERE mb_no='$row[loan_mb_no]'";
	$rowm = sql_fetch($sqlm);
	$rowm['mb_hp'] = masterDecrypt($rowm['mb_hp'], false);
	//$rowm['mb_hp'] = "010-1234-5678";
	
	$LIST[0] = $rowm;

	return $LIST;
}

function get_msg($idx) {

	$sql = "SELECT A.*, B.virtual_account2 
			  FROM cf_loaner_push_schedule A
			  LEFT JOIN g5_member B ON(A.mb_no = B.mb_no)
			 WHERE idx=$idx";
	$row = sql_fetch($sql);

	$acc_amt = get_chaju_remain_amt($row['mb_no'], $row['virtual_account2'], $row['product_idx']);
	$remain_amt = $row['eja'] - $acc_amt ;
	if ($remain_amt<0) $chung_gu_amt=0;
	else $chung_gu_amt=$remain_amt;

	$msg = $row["msg"];
	$msg = str_replace("{PRICE}", number_format($chung_gu_amt) , $msg);;

	//$msg = $row['virtual_account2']." 이자 $chung_gu_amt"." ".$row["msg"];
	return $msg;
}
?>