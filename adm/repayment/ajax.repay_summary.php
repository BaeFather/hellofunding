<?

###############################################################################
# 상품별 정산요약
# 원금, 세전이자, 원천징수, 플랫폼이용료, 세후이자 예정 및 지급액
# /adm/repayment/repay_calculate.php 에 의해 호출
# 필수파라미터 : 상품번호(prd_idx)
# 옵션파라미터 : 회원번호(mb_no)
###############################################################################

$base_path  = "/home/crowdfund/public_html";
include_once($base_path . '/common.cli.php');


$prd_idx = trim($_REQUEST['prd_idx']);
$mb_no   = trim($_REQUEST['mb_no']);

if(!$prd_idx) { sql_close(); exit; }


$TBL['product'] = 'cf_product';
$TBL['invest']  = 'cf_product_invest';
$TBL['invest_detail'] = 'cf_product_invest_detail';
$TBL['success'] = 'cf_product_success';
$TBL['give']    = 'cf_product_give_test';
$TBL['member']  = 'g5_member';


function get_repay_data($prd_idx, $mb_no='', $repay_gubun='', $paid_gubun='', $degug='') {

	global $g5, $TBL;

	$sql = "
	SELECT
		IFNULL(SUM(principal),0) AS principal,
		IFNULL(SUM(interest+interest_tax+local_tax+fee),0) AS invest_interest,
		IFNULL(SUM(interest),0) AS interest,
		IFNULL(SUM(interest_tax + local_tax),0) AS tax,
		IFNULL(SUM(fee),0) AS fee
	FROM
		{$TBL['give']}
	WHERE 1
		AND product_idx='".$prd_idx."'";
	$sql.= ($mb_no) ? " AND member_idx='".$mb_no."'" : "";
	$sql.= ($repay_gubun == 'overdue') ? " AND is_overdue='Y'" : " AND is_overdue='N'";
	$sql.= ($paid_gubun) ? " AND (banking_date IS NOT NULL OR banking_date > '0000-00-00 00:00:00')" : "";

	if($degug) print_rr($sql,'text-align:left;font-size:12px;color:gray;');

	$DATA = sql_fetch($sql);

	return $DATA;

}


// 투자금 조회
$sql = "SELECT SUM(amount) AS amount FROM {$TBL['invest']} WHERE product_idx='".$prd_idx."'";
if($mb_no) $sql.= " AND member_idx='".$mb_no."'";
$sql.= " AND invest_state='Y'";
$INVEST = sql_fetch($sql);


$REPAY = get_repay_data($prd_idx, $mb_no);												// 정상상환 예정정보
$PAID  = get_repay_data($prd_idx, $mb_no, '', 'paid');						// 정상상환 지급정보

$OVD_REPAY = get_repay_data($prd_idx, $mb_no, 'overdue');						// 연체상환 예정정보
$OVD_PAID  = get_repay_data($prd_idx, $mb_no, 'overdue', 'paid');		// 연체상환 지급정보

// 일반정산 잔여액
$DIFF = array(
	'principal'       => $INVEST['amount'] - $PAID['principal'],
	'invest_interest' => $REPAY['invest_interest'] - $PAID['invest_interest'],
	'interest'        => $REPAY['interest'] - $PAID['interest'],
	'tax'             => $REPAY['tax'] - $PAID['tax'],
	'fee'             => $REPAY['fee'] - $PAID['fee']
);

// 연체 잔여액
$OVD_DIFF = array(
	'principal'       => $OVD_REPAY['principal'] - $OVD_PAID['principal'],
	'invest_interest' => $OVD_REPAY['invest_interest'] - $OVD_PAID['invest_interest'],
	'interest'        => $OVD_REPAY['interest'] - $OVD_PAID['interest'],
	'tax'             => $OVD_REPAY['tax'] - $OVD_PAID['tax'],
	'fee'             => $OVD_REPAY['fee'] - $OVD_PAID['fee']
);

function print_number($n) {
	if($n > 0) {
		$value = number_format($n);
	}
	else {
		$value = '<font color=#aaaaaa>0</font>';
	}

	return $value;
}

// 연체상환 지급정보

sql_close();

?>

							<table class="tblx table-bordered prdt_table">
								<colgroup>
									<col style="width:%">
									<col style="width:6.25%"><col style="width:6.25%"><col style="width:6.25%">
									<col style="width:6.25%"><col style="width:6.25%"><col style="width:6.25%">
									<col style="width:6.25%"><col style="width:6.25%"><col style="width:6.25%">
									<col style="width:6.25%"><col style="width:6.25%"><col style="width:6.25%">
									<col style="width:6.25%"><col style="width:6.25%"><col style="width:6.25%">
								</colgroup>
								<tr align="center" style="background:#EEE">
									<td rowspan="2">상환<br>구분</td>
									<td colspan="3">원금</td>
									<td colspan="3">이자</td>
									<td colspan="3">플랫폼이용료</td>
									<td colspan="3">원천징수</td>
									<td colspan="3">세후이자</td>
								</tr>
								<tr align="center" style="background:#EEE">
									<td>예정</td><td>지급</td><td>잔여</td>
									<td>예정</td><td>지급</td><td>잔여</td>
									<td>예정</td><td>수취</td>
									<td>잔여</td><td>예정</td>
									<td>수취</td><td>잔여</td>
									<td>예정</td><td>지급</td><td>잔여</td>
								</tr>
								<tr align="right" style="font-size:12px">
									<td align="center">일반</td>
									<td id="principal_repay"><?=print_number($INVEST['amount'])?></td>
									<td id="principal_paid"><?=print_number($PAID['principal'])?></td>
									<td id="principal_diff"><?=print_number($DIFF['principal'])?></td>
									<td id="invest_interest_repay"><?=print_number($REPAY['invest_interest'])?></td>
									<td id="invest_interest_paid"><?=print_number($PAID['invest_interest'])?></td>
									<td id="invest_interest_diff"><?=print_number($DIFF['invest_interest'])?></td>
									<td id="fee_repay"><?=print_number($REPAY['fee'])?></td>
									<td id="fee_paid"><?=print_number($PAID['fee'])?></td>
									<td id="fee_diff"><?=print_number($DIFF['fee'])?></td>
									<td id="tax_repay"><?=print_number($REPAY['tax'])?></td>
									<td id="tax_paid"><?=print_number($PAID['tax'])?></td>
									<td id="tax_diff"><?=print_number($DIFF['tax'])?></td>
									<td id="interest_repay"><?=print_number($REPAY['interest'])?></td>
									<td id="interest_paid"><?=print_number($PAID['interest'])?></td>
									<td id="interest_diff"><?=print_number($DIFF['interest'])?></td>
								</tr>
								<tr align="right" style="font-size:12px">
									<td align="center">연체</td>
									<td align="center">-</td>
									<td align="center">-</td>
									<td align="center">-</td>
									<td id="ovd_invest_interest_repay"><?=print_number($OVD_REPAY['invest_interest'])?></td>
									<td id="ovd_invest_interest_paid"><?=print_number($OVD_PAID['invest_interest'])?></td>
									<td id="ovd_invest_interest_diff"><?=print_number($OVD_DIFF['invest_interest'])?></td>
									<td id="ovd_fee_repay"><?=print_number($OVD_REPAY['fee'])?></td>
									<td id="ovd_fee_paid"><?=print_number($OVD_PAID['fee'])?></td>
									<td id="ovd_fee_diff"><?=print_number($OVD_DIFF['fee'])?></td>
									<td id="ovd_tax_repay"><?=print_number($OVD_REPAY['tax'])?></td>
									<td id="ovd_tax_paid"><?=print_number($OVD_PAID['tax'])?></td>
									<td id="ovd_tax_diff"><?=print_number($OVD_DIFF['tax'])?></td>
									<td id="ovd_interest_repay"><?=print_number($OVD_REPAY['interest'])?></td>
									<td id="ovd_interest_paid"><?=print_number($OVD_PAID['interest'])?></td>
									<td id="ovd_interest_diff"><?=print_number($OVD_DIFF['interest'])?></td>
								</tr>
							</table>

<?
exit;
?>