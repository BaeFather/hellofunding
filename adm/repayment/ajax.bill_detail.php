<?
###############################################################################
## 해당회차의 일별 정산내역 상세보기
###############################################################################

include_once("_common.php");

while(list($k, $v) = each($_REQUEST)) { ${$k} = trim($v); }

/*
$prd_idx    = '1051';
$turn       = '4';
$member_idx = '770';
*/
$is_overdue = ($is_overdue) ? $is_overdue : 'N';

$PRDT = sql_fetch("SELECT idx, title, loan_start_date FROM cf_product WHERE idx='".$prd_idx."'");
$MEM  = sql_fetch("SELECT mb_id, IF(member_type='2', mb_co_name, mb_name) AS member_title FROM g5_member WHERE mb_no='".$member_idx."'");

$sql  = "SELECT account_day FROM cf_partial_redemption WHERE product_idx='".$prd_idx."' AND turn='".$turn."' ORDER BY idx ASC";
$res  = sql_query($sql);
$PTLDATE = array();
while( $row = sql_fetch_array($res) ) {
	if($row['account_day'])	array_push($PTLDATE, $row['account_day']);
}


$bill_table = getBillTable($prd_idx);		// 정산내역 기록 테이블 (** 테이블명에 상품번호대역(100단위) 붙음 **)

$where = " AND product_idx='".$prd_idx."'";
$where.= " AND turn='".$turn."'";
$where.= " AND member_idx='".$member_idx."'";
$where.= " AND is_overdue='".$is_overdue."'";

$sql = "
	SELECT
		member_idx, bill_date, dno, partial_principal, remain_principal, day_interest, fee
	FROM
		$bill_table
	WHERE 1
		$where
	ORDER BY
		dno ASC,
		invest_idx DESC";
//echo $sql;
$res = sql_query($sql);
$rows = sql_num_rows($res);
for($i=0, $j=1; $i<$rows; $i++,$j++) {
	$LIST[$i] = sql_fetch_array($res);

	$TOTAL['day_interest'] += $LIST[$i]['day_interest'];
	$TOTAL['fee'] += $LIST[$i]['fee'];
}
sql_free_result($res);

if($mode=='old') {
	//
}
else {
	$TOTAL['day_interest'] = customRoundOff($TOTAL['day_interest']);
	$TOTAL['fee'] = customRoundOff($TOTAL['fee']);
}

?>
<style>
.tdx { padding:2px 4px; }
</style>

<div id="detailTable" style="width:700px; margin:4% auto; padding:0; border:1px solid #000; background:#fff;">
	<div style="width:100%; margin:0; padding:6px 0 7px 8px; background:#0033cc;text-align:left">
		<span style="font-size:12px;color:#fff;"><?=$PRDT['title']?> ＞ <?=$turn?>회차 ＞ <?=$MEM['member_title']."(".$MEM['mb_id'].")";?></span>
		<span onClick="popupClose();" style="float:right;margin-right:8px;cursor:pointer;color:#FFF">X</span>
	</div>
	<div style="width:100%; margin:0; padding:0; max-height:590px; overflow-y:scroll;">
		<table align="center" class="tableZ table-bordered table-hover" style="width:100%;margin:0; font-size:12px;">
			<tr style="background:#F8F8EF;">
				<th class="tdx">일차</th>
				<th class="tdx">Date</th>
				<th class="tdx">원금상환누적(원)</th>
				<th class="tdx">잔여투자원금(원)</th>
				<th class="tdx">이자(원)</th>
				<th class="tdx">플랫폼이용료(원)</th>
			</tr>
			<tr align="center" style="background:#ffcccc;color:brown">
				<td class="tdx">합계</td>
				<td class="tdx"><?=number_format($rows)?>일</td>
				<td class="tdx">-</td>
				<td class="tdx">-</td>
				<td class="tdx" align="right"><?=number_format(floor($TOTAL['day_interest']))?>원</td>
				<td class="tdx" align="right"><?=number_format(floor($TOTAL['fee']))?>원</td>
			</tr>
<?
$list_count = count($LIST);
if($list_count) {
	for($i=0, $j=1; $i<$list_count; $i++,$j++) {

		$fcolor = ( in_array($LIST[$i]['bill_date'], $PTLDATE) ) ? '#ff2222' : '';

		echo "		<tr align='center' style='color:$fcolor'>";
		echo "			<td class='tdx'>".$j."일차</td>";
		echo "			<td class='tdx'>".$LIST[$i]['bill_date']."</td>";
		echo "			<td class='tdx' align='right'>".number_format($LIST[$i]['partial_principal'])."</td>";
		echo "			<td class='tdx' align='right'>".number_format($LIST[$i]['remain_principal'])."</td>";
		echo "			<td class='tdx' align='right'>".number_format($LIST[$i]['day_interest'], 8)."</td>";
		echo "			<td class='tdx' align='right'>".number_format($LIST[$i]['fee'], 8)."</td>";
		echo "		</tr>\n";
	}
}
else {
		echo "		<tr>";
		echo "			<td colspan='7' align='center'>데이터가 없습니다.</td>";
		echo "		</tr>\n";
}
?>
		</table>
	</div>
</div>