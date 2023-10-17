<?
###############################################################################
##  투자수익 지급내역 통계
###############################################################################

include_once('./_common.php');

if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

foreach($_POST as $k=>$v) { ${$k} = trim($v); }


$where = "";
$where.= " AND SDATE = '".$sdate."'";
$where.= " AND REG_SEQ = '".$reg_seq."'";


$LIST = array();

$SUM = array(
	'repay_cnt'   => 0,
	'tr_amt'      => 0,
	'tr_amt_p'    => 0,
	'interest'    => 0,
	'ctax_amt'    => 0,
	'fee'         => 0,
);

// 예약내역
$sql = "
	SELECT
		DC_NB,
		(SELECT title FROM cf_product WHERE idx=DC_NB) AS title,
		turn, turn_sno, is_overdue, etc_cost_idx,
		COUNT(PARTNER_CD) AS repay_cnt,
		SUM(TR_AMT) AS tr_amt,
		SUM(TR_AMT_P) AS tr_amt_p,
		SUM(CTAX_AMT) AS ctax_amt,
		SUM(FEE) AS fee
	FROM
		IB_FB_P2P_REPAY_REQ_DETAIL
	WHERE 1
		$where
	GROUP BY
		DC_NB, turn, turn_sno, is_overdue, etc_cost_idx
	ORDER BY
		DC_NB DESC, turn DESC, turn_sno DESC, is_overdue DESC";
//print_rr($sql);
$result = sql_query($sql);
$rcount = $result->num_rows;

for($i=0; $i<$rcount; $i++) {

	$ROW = sql_fetch_array($result);
	$ROW['interest'] = ($ROW['tr_amt'] > 0) ? $ROW['tr_amt'] - $ROW['tr_amt_p'] : 0;

	array_push($LIST, $ROW);

	$SUM['repay_cnt']+= $ROW['repay_cnt'];
	$SUM['tr_amt']   += $ROW['tr_amt'];
	$SUM['tr_amt_p'] += $ROW['tr_amt_p'];
	$SUM['interest'] += $ROW['interest'];
	$SUM['ctax_amt'] += $ROW['ctax_amt'];
	$SUM['fee']      += $ROW['fee'];

}
sql_free_result($result);

$list_count = count($LIST);
$num = $total_count - $from_record;

//print_rr($LIST);
//print_rr($SUM);

?>

<style>
#dataList th,td { padding:2px 8px; }
</style>


<div style="width:100%;max-height:700px;overflow-y:auto;padding:0">

	<table id="dataList" class="table-bordered table-striped" style="font-size:12px">
		<colgroup>
			<col style="width:6%">
			<col style="width:6%">
			<col style="width:%">
			<col style="width:6%">
			<col style="width:8%">
			<col style="width:8%">
			<col style="width:10%">
			<col style="width:10%">
			<col style="width:10%">
			<col style="width:10%">
		</colgroup>
		<thead>
			<tr align="center" style="font-size:12px;background:#F8F8EF">
				<th style="background:#F8F8EF">NO</th>
				<th style="background:#F8F8EF">품번</th>
				<th style="background:#F8F8EF">상품명</th>
				<th style="background:#F8F8EF">상환회차</th>
				<th style="background:#F8F8EF">상환구분</th>
				<th style="background:#F8F8EF">배분건수</th>
				<th style="background:#F8F8EF">원금</th>
				<th style="background:#F8F8EF">이자</th>
				<th style="background:#F8F8EF">세금</th>
				<th style="background:#F8F8EF">수수료</th>
			</tr>

<? if($list_count > 1) { ?>
			<tr align="center" style="font-size:12px;background:#EEEEFF;color:brown;">
				<td>합계</td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td style="text-align:right;"><?=number_format($SUM['repay_cnt'])?>건</td>
				<td style="text-align:right;"><?=number_format($SUM['tr_amt_p'])?>원</td>
				<td style="text-align:right;"><?=number_format($SUM['interest'])?>원</td>
				<td style="text-align:right;"><?=number_format($SUM['ctax_amt'])?>원</td>
				<td style="text-align:right;"><?=number_format($SUM['fee'])?>원</td>
			</tr>
<? } ?>
		</thead>
		<tbody>
<?
if($list_count) {
	for($i=0,$j=$list_count; $i<$list_count; $i++,$j--) {
		$print_gubun = $fcolor = '';
		if($LIST[$i]['is_overdue']=='Y') {
			$print_gubun = '연체상환';
			$fcolor = '#FF3333';
		}
		else {
			if($LIST[$i]['turn_sno'] > 0) {
				$print_gubun = '원금일부상환';
				$fcolor = 'brown';
			}
			else {
				$print_gubun = '정규상환';
				$fcolor = '';
			}
		}
?>
			<tr align="center">
				<td><?=$j?></td>
				<td><?=$LIST[$i]['DC_NB']?></td>
				<td style="text-align:left;"><?=$LIST[$i]['title']?></td>
				<td><?=$LIST[$i]['turn']?>회차</td>
				<td style="color:<?=$fcolor?>"><?=$print_gubun?></td>
				<td style="text-align:right;"><?=number_format($LIST[$i]['repay_cnt'])?>건</td>
				<td style="text-align:right;"><?=number_format($LIST[$i]['tr_amt_p'])?>원</td>
				<td style="text-align:right;"><?=number_format($LIST[$i]['interest'])?>원</td>
				<td style="text-align:right;"><?=number_format($LIST[$i]['ctax_amt'])?>원</td>
				<td style="text-align:right;"><?=number_format($LIST[$i]['fee'])?>원</td>
			</tr>
<?
		$num--;
	}
}
else {
	echo "<tr><td colspan='15' align='center'>데이터가 없습니다.</th></tr>\n";
}
?>
		<tbody>
	</table>

</div>

<script>
$(document).ready(function() {
	//$('#dataList').floatThead();
});
</script>

<?
sql_close();
exit;
?>