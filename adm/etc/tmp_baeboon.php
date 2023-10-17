<?

exit;

include_once("_common.php");

$DATA = "M49112P6838I326405	50
M49735P6838I326415	120
M49298P6837I326404	370
M25218P6836I326457	60
M18766P6835I326411 	400
M48290P6834I326370	20
M21920P6828I326305	120
M49901P6828I326312	120
M49983P6827I326192	30";

$ARR = explode("\r\n", $DATA);

$SDATE      = '20210903';
$REG_SEQ    = '02';
$PARTNER_CD = 'P0012';

$req_idx    = '165';
$turn       = '1';
$turn_sno   = '0';
$is_overdue = 'N';

$sql = "INSERT INTO IB_FB_P2P_REPAY_REQ_DETAIL (SDATE, REG_SEQ, SEQ, PARTNER_CD, CUST_ID, DC_NB, invest_idx, TR_AMT, TR_AMT_P, CTAX_AMT, FEE, REPAY_RECEIPT_NB, req_idx, turn, turn_sno, is_overdue, rdate) VALUES \n";

$arr_count = count($ARR);
$sum_amt = 0;

for($i=0,$j=1; $i<$arr_count; $i++,$j++) {

	$CUST_ID = $DC_NB = $invest_idx = $TMP = $TMP_STR = '';

	if( trim($ARR[$i]) ) {

		$TMP = explode("\t", $ARR[$i]);

		$REPAY_RECEIPT_NB = $TMP[0];
		$TR_AMT = $TMP[1];
		$sum_amt += $TMP[1];

		$STR = preg_replace("/(M|P|I)/", "|", $REPAY_RECEIPT_NB);
		$STR = explode("|", $STR);

		$CUST_ID    = $STR[1];
		$DC_NB      = $STR[2];
		$invest_idx = $STR[3];

		$sql = "
			INSERT INTO
				IB_FB_P2P_REPAY_REQ_DETAIL
			SET
				SDATE = '$SDATE',
				REG_SEQ = '$REG_SEQ',
				SEQ = '$j',
				PARTNER_CD = '$PARTNER_CD',
				CUST_ID = '$CUST_ID',
				DC_NB = '$DC_NB',
				invest_idx = '$invest_idx',
				TR_AMT = '$TR_AMT',
				TR_AMT_P = '0',
				CTAX_AMT = '0',
				FEE = '0',
				REPAY_RECEIPT_NB = '$REPAY_RECEIPT_NB',
				req_idx = '$req_idx',
				turn = '$turn',
				turn_sno = '$turn_sno',
				is_overdue = '$is_overdue',
				rdate = NOW()";

		print_rr($sql);


	}


}

$STIME = "164500";
$sql2 = " INSERT INTO IB_FB_P2P_REPAY_REQ_ready (SDATE, REG_SEQ, PARTNER_CD, STIME, TOTAL_CNT, TOTAL_TR_AMT, TOTAL_TR_AMT_P, TOTAL_CTAX_AMT, TOTAL_FEE, EXEC_STATUS) VALUES ('$SDATE', '$REG_SEQ', '$PARTNER_CD', '$STIME', '$arr_count', '$sum_amt', '0', '0', '0', '00');";
echo $sql2 . "\n";




?>