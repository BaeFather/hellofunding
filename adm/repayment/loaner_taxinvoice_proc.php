<?
###############################################################################
##  세금계산서,현금영수증 일괄발행 실행
###############################################################################

include_once('./_common.php');

if($is_admin != 'super') { msg_replace("/"); }


while( list($k, $v) = each($_POST) ) { if(is_array(${$k})) ${$k} = @trim($v); }

$IDX = $_POST['chk'];
$idxCount = count($IDX);

if(!$idxCount) {

	$RESULT_ARR = array('code' => 'ERROR', 'message' => '발급 대상일이 전송되지 않았습니다.');
	echo json_encode($RESULT_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
	exit;

}
else {

	$taxinvoiceSet = '';		// 세금계산서 요청자료 번호세트
	$cashbillSet   = '';		// 현금영수증 요청자료 번호세트

	for($i=0,$j=1; $i<$idxCount; $i++,$j++) {

		$sql = "
			SELECT
				C.member_type
			FROM
				cf_loaner_fee_collect A
			LEFT JOIN
				cf_product B  ON A.product_idx=B.idx
			LEFT JOIN
				g5_member C  ON B.loan_mb_no=C.mb_no
			WHERE
				A.idx='".$IDX[$i]."'";
		$R = sql_fetch($sql);
		if($R['member_type']) {
			if($R['member_type']=='2') {
				$taxinvoiceSet.= $IDX[$i].',';
			}
			else {
				$cashbillSet.= $IDX[$i].',';
			}
		}
	}
	$taxinvoiceSet = substr($taxinvoiceSet, 0, strlen($taxinvoiceSet)-1);
	$cashbillSet   = substr($cashbillSet, 0, strlen($cashbillSet)-1);


	$EXECPATH['c'] = G5_ADMIN_PATH . "/repayment/loaner_taxinvoice_request_c.php " . $taxinvoiceSet;
	$EXECPATH['p'] = G5_ADMIN_PATH . "/repayment/loaner_taxinvoice_request_p.php " . $cashbillSet;

	$exec_str = "";
	if($taxinvoiceSet) $exec_str.= "/usr/local/php/bin/php -q " . $EXECPATH['c'] . " > /dev/null &;";
	if($cashbillSet)   $exec_str.= "/usr/local/php/bin/php -q " . $EXECPATH['p'] . " > /dev/null &";

	//echo $exec_str ."\n";
	@shell_exec($exec_str);

	$RESULT_ARR = array('code' => 'SUCCESS', 'message' => '');

}

echo json_encode($RESULT_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
sql_close();

?>