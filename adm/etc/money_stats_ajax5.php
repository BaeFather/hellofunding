<?
###############################
## 상품별 이벤트 투자지급액
###############################

include_once('_common.php');

$sdate = $_REQUEST['sdate'];
$edate = $_REQUEST['edate'];

$sql = "
SELECT
	A.idx, A.title, A.start_date, A.end_date,
	COUNT(B.idx) AS invest_cnt,
	IFNULL(SUM(B.amount), 0) AS invest_amt
FROM
	cf_event_product A
LEFT JOIN
	cf_event_product_invest B
ON
	A.idx=B.product_idx
WHERE (1)
	AND A.state='2'
	AND B.invest_state='Y'
	AND B.insert_date BETWEEN '$sdate' AND '$edate'
GROUP BY
	A.idx
ORDER BY
	A.idx DESC";
//debug_flush($sql."<br>\n");

$res  = sql_query($sql);
$rows = $res->num_rows;
for($i=0,$j=1; $i<$rows; $i++,$j++) {
	$LIST[$i] = sql_fetch_array($res);

	$sql2 = "
		SELECT
			COUNT(idx) AS give_cnt,
			IFNULL(SUM(invest_amount), 0) AS give_amt
		FROM
			cf_event_product_give
		WHERE (1)
			AND product_idx='".$LIST[$i]['idx']."'
			AND date BETWEEN '$sdate' AND '$edate'";
	//debug_flush($sql2."<br>\n");
	$GIVE = sql_fetch($sql2);

	$LIST[$i]['give_cnt'] = $GIVE['give_cnt'];
	$LIST[$i]['give_amt'] = $GIVE['give_amt'];

	$TOTAL['invest_cnt'] += $LIST[$i]['invest_cnt'];
	$TOTAL['invest_amt'] += $LIST[$i]['invest_amt'];
	$TOTAL['give_cnt']   += $LIST[$i]['give_cnt'];
	$TOTAL['give_amt']   += $LIST[$i]['give_amt'];
}


if($_SERVER['REQUEST_METHOD']=='POST') {
	$button = '<button type="button" class="btn btn-sm btn-success" onClick="axFrame.location.replace(\''.$_SERVER['PHP_SELF'].'?sdate='.$sdate.'&edate='.$edate.'\')">엑셀 다운로드</button>';
}
else {
	$button = '';

	$now_date = date('Ymd');
	$file_name = "($now_date)회원별_이벤트투자지급액.xls";
	$file_name = iconv("utf-8", "euc-kr", $file_name);

	header( "Content-type: application/vnd.ms-excel;" );
	header( "Content-Disposition: attachment; filename=$file_name" );
	header( "Content-description: PHP5 Generated Data" );
}

debug_flush("
	<table style='border:0'>
		<tr>
			<td colspan=4><h3>이벤트 투자/지급액 {$button}</h3></td>
		</tr>
	</table>
	<table border=1 class='table-striped table-hover' style='font-size:9pt'>
		<colgroup>
			<col width=''>
			<col width='15%'>
			<col width='15%'>
			<col width='15%'>
			<col width='15%'>
			<col width='15%'>
		</colgroup>
		<tr align='center'>
			<th>이벤트명</th>
			<th>시행기간</th>
			<th>모집건수</th>
			<th>모집금액</th>
			<th>지급건수</th>
			<th>지급금액</th>
		</tr>
		<tr align='center'>
			<td style='background:#DDFFFF'>합계</td>
			<td style='background:#DDFFFF'></td>
			<td style='background:#DDFFFF' align='right'>".number_format($TOTAL['invest_cnt'])."</td>
			<td style='background:#DDFFFF' align='right'>".number_format($TOTAL['invest_amt'])."</td>
			<td style='background:#DDFFFF' align='right'>".number_format($TOTAL['give_cnt'])."</td>
			<td style='background:#DDFFFF' align='right'>".number_format($TOTAL['give_amt'])."</td>
		</tr>\n");


for($i=0,$j=1; $i<$rows; $i++,$j++) {

	if($LIST[$i]['member_type']) { $LIST[$i]['member_type']= ($LIST[$i]['member_type']=='2') ? '법인' : '개인'; }

	debug_flush("
		<tr align='center'>
			<td style='background:$bgcolor'><a href='/adm/event_product_form.php?idx=".$LIST[$i]['idx']."'>".$LIST[$i]['title']."</a></td>
			<td style='background:$bgcolor'>".$LIST[$i]['start_date']." ~ ".$LIST[$i]['end_date']."</td>
			<td style='background:$bgcolor' align='right'><a href='/adm/event_product_calculate.php?idx=".$LIST[$i]['idx']."'>".number_format($LIST[$i]['invest_cnt'])."</a></td>
			<td style='background:$bgcolor' align='right'><a href='/adm/event_product_calculate.php?idx=".$LIST[$i]['idx']."'>".number_format($LIST[$i]['invest_amt'])."</a></td>
			<td style='background:$bgcolor' align='right'><a href='/adm/event_product_calculate.php?idx=".$LIST[$i]['idx']."'>".number_format($LIST[$i]['give_cnt'])."</a></td>
			<td style='background:$bgcolor' align='right'><a href='/adm/event_product_calculate.php?idx=".$LIST[$i]['idx']."'>".number_format($LIST[$i]['give_amt'])."</a></td>
		</tr>\n");
}
debug_flush("</table>");

?>