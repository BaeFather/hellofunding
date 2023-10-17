<?
// 본파일을 정상적으로 사용하게 되면 /adm/ajax_ib_send_wait_list.php 는 삭제 할 것!!
################################################################################
## 신한인사이드뱅크 전송 대기 리스트
## product_calculate.php 에서 호출됨
################################################################################

include_once("_common.php");

if(!$is_admin) echo "ERROR:LOGIN-CHECK";

$where = "";
$where.= " AND SDATE = '' AND REG_SEQ = ''";
switch($_REQUEST['repay_type']) {
	case 'P' :	$where.= " AND is_overdue = 'N' AND turn_sno > '0'";		break;		// 원금일부상환
	case 'O' :	$where.= " AND is_overdue = 'Y' AND turn_sno = '0'";		break;		// 연체이자상환
	case 'R' :
	default  :	$where.= " AND is_overdue = 'N' AND turn_sno = '0'";		break;		// 정상원리금상환
}

$sql = "
	SELECT
		DC_NB,
		(SELECT title FROM cf_product WHERE idx=DC_NB) AS title,
		turn, turn_sno, is_overdue, etc_cost_idx,
		COUNT(PARTNER_CD) AS cnt,
		SUM(TR_AMT) AS sum_tr_amt,
		SUM(TR_AMT_P) AS sum_tr_amt_p,
		SUM(CTAX_AMT) AS sum_ctax_amt,
		SUM(FEE) AS sum_fee
	FROM
		IB_FB_P2P_REPAY_REQ_DETAIL
	WHERE 1
		$where
	GROUP BY
		DC_NB, turn, turn_sno, is_overdue, etc_cost_idx
	ORDER BY
		DC_NB, turn, turn_sno, is_overdue DESC";
//print_rr($sql,'font-size:11px'); exit;
$res = sql_query($sql);
$rows = $res->num_rows;

$TOTAL = array(
	'repay_count'  => 0,
	'principal'    => 0,
	'invest_interest'=> 0,
	'interest'     => 0,
	'tax'          => 0,
	'fee'          => 0,
	'repay_amount' => 0
);

for($i=0; $i<$rows; $i++) {

	$LIST[$i] = sql_fetch_array($res);

	if($LIST[$i]['etc_cost_idx']) {
		if( $ETC_COST = sql_fetch("SELECT idx, product_idx, title FROM cf_etc_cost WHERE idx = '".$LIST[$i]['etc_cost_idx']."'") ) {
			$LIST[$i]['title'] = "기타비용: " . $ETC_COST['title'] . "(".$ETC_COST['product_idx'].")";
		}
	}


	$LIST[$i]['print_turn'] = $LIST[$i]['turn'] . "회차";
	if($LIST[$i]['is_overdue']=='Y') {
		$LIST[$i]['print_gubun'] = '연체이자';
	}
	else {
		if($LIST[$i]['turn_sno'] > '0') {
			$LIST[$i]['print_gubun'] = '부분상환('.$LIST[$i]['turn_sno'].')';
		}
		else {
			$LIST[$i]['print_gubun'] = '정규상환';
		}
	}

	//prdt_turn => 상품번호 & 회차번호 & 회차정렬번호 & 연체상환여부
	$LIST[$i]['prdt_turn'] = $LIST[$i]['DC_NB']."&".$LIST[$i]['turn']."&".$LIST[$i]['turn_sno']."&".$LIST[$i]['is_overdue'];

	$LIST[$i]['interest']  = $LIST[$i]['sum_tr_amt'] - $LIST[$i]['sum_tr_amt_p'];		// 세후이자	(2020-08-05 수정)
//$LIST[$i]['interest']  = $LIST[$i]['sum_tr_amt'] - $LIST[$i]['sum_tr_amt_p'] - $LIST[$i]['sum_ctax_amt'] - $LIST[$i]['sum_fee'];		// 세후이자 (2020-08-05 주석처리)

	if($LIST[$i]['interest'] > 0) {
		$LIST[$i]['invest_interest'] = $LIST[$i]['interest'] + $LIST[$i]['sum_ctax_amt'] + $LIST[$i]['sum_fee'];		// 세전이자
	}
	else {
		$LIST[$i]['invest_interest'] = 0;		// 세전이자
	}


	$TOTAL['repay_count']     += $LIST[$i]['cnt'];
	$TOTAL['principal']       += $LIST[$i]['sum_tr_amt_p'];
	$TOTAL['invest_interest'] += $LIST[$i]['invest_interest'];
	$TOTAL['tax']             += $LIST[$i]['sum_ctax_amt'];
	$TOTAL['fee']             += $LIST[$i]['sum_fee'];
	$TOTAL['interest']        += $LIST[$i]['interest'];
	$TOTAL['repay_amount']    += $LIST[$i]['sum_tr_amt'];

}

//print_rr($LIST,'font-size:11px');

?>

<table class="table-bordered table-striped table-hover" style='margin:0;width:100%;font-size:12px;'>
	<colgroup>
		<col width='4%'>
		<col width='18%'>
		<col width='%'>
		<col width='%'>
		<col width='%'>
		<col width='%'>
		<col width='%'>
		<col width='%'>
		<col width='%'>
		<col width='%'>
	</colgroup>
	<tr style='background:#F8F8EF' align='center' height='20'>
		<td><input type='checkbox' id='chkall'></td>
		<td style="overflow:hidden;">상품명</td>
		<td>회차</td>
		<td>구분</td>
		<td>건수</td>
		<td>세전이자</td>
		<td>원천징수</td>
		<td>수수료</td>
		<td>세후이자</td>
		<td>원금</td>
		<td>실지급액합계</td>
	</tr>

<?

if($rows) {
?>
	<tr style='background:#DDD;color:brown'>
		<td colspan='2' align='center'>합계</td>
		<td align='center'><?=$rows?>건</td>
		<td align='center'>-</td>
		<td align='right'><?=number_format($TOTAL['repay_count'])?>건</td>
		<td align='right'><?=number_format($TOTAL['invest_interest'])?>원</td>
		<td align='right'><?=number_format($TOTAL['tax'])?>원</td>
		<td align='right'><?=number_format($TOTAL['fee'])?>원</td>
		<td align='right'><?=number_format($TOTAL['interest'])?>원</td>
		<td align='right'><?=number_format($TOTAL['principal'])?>원</td>
		<td align='right'><b><?=number_format($TOTAL['repay_amount'])?>원</b></td>
	</tr>
<?
	for($i=0; $i<$rows; $i++) {
?>
	<tr>
		<td align='center'><input type='checkbox' name='PRDT_TURN[]' value='<?=$LIST[$i]['prdt_turn']?>'></td>
		<td title='<?=$LIST[$i]['title']?>'><div style='width:100%;height:20px;line-height:20px;overflow:hidden;<?if($LIST[$i]['etc_cost_idx'])echo"color:#3366FF";?>'><?=$LIST[$i]['title']?></div></td>
		<td align='center'><?=$LIST[$i]['print_turn']?></td>
		<td align='center'><?=$LIST[$i]['print_gubun']?></td>
		<td align='right'><?=number_format($LIST[$i]['cnt'])?>건</td>
		<td align='right'><?=number_format($LIST[$i]['invest_interest'])?>원</td>
		<td align='right'><?=number_format($LIST[$i]['sum_ctax_amt'])?>원</td>
		<td align='right'><?=number_format($LIST[$i]['sum_fee'])?>원</td>
		<td align='right' style="color:#2233FF"><?=number_format($LIST[$i]['interest'])?>원</td>
		<td align='right' style="color:#2233FF"><?=number_format($LIST[$i]['sum_tr_amt_p'])?>원</td>
		<td align='right' style="color:#2233FF"><b><?=number_format($LIST[$i]['sum_tr_amt'])?>원</b></td>
	</tr>
<?
	}
}
else {
	echo "  <tr><td colspan='11' align='center'>전송 대기중인 데이터가 없습니다.</td></tr>\n";
}
?>
</table>

<script>
$("#chkall").click(function() {
	$("input[name='PRDT_TURN[]']").prop('checked', this.checked);
});
</script>

<?
sql_close();
exit;
?>