<?
###############################
## 아이디별 투자금액
###############################

include_once('_common.php');

$sdate = $_REQUEST['sdate'];
$edate = $_REQUEST['edate'];

$sql = "
SELECT
	A.member_idx,
	COUNT(A.idx) AS cnt,
	SUM(A.amount) AS amt
FROM
	cf_product_invest A
WHERE (1)
	AND A.invest_state='Y'
	AND insert_date BETWEEN '$sdate' AND '$edate'
GROUP BY
	A.member_idx
ORDER BY
	cnt DESC,
	A.member_idx ASC";
$res  = sql_query($sql);
$rows = $res->num_rows;
for($i=0,$j=1; $i<$rows; $i++,$j++) {
	$ROW = sql_fetch_array($res);

	//회원정보
	$MEM = sql_fetch("SELECT mb_no, member_type, mb_id, mb_name, mb_co_name FROM g5_member WHERE mb_no='".$ROW['member_idx']."'");
	$LIST[$i]['mb_no']      = $ROW['member_idx'];
	$LIST[$i]['mb_id']      = $MEM['mb_id'];
	$LIST[$i]['member_type']= $MEM['member_type'];
	$LIST[$i]['mb_name']    = ($MEM['member_type']==2) ? $MEM['mb_co_name'] : $MEM['mb_name'];

	$LIST[$i]['cnt'] = $ROW['cnt'];
	$LIST[$i]['amt'] = $ROW['amt'];

	$TOTAL['cnt'] += $LIST[$i]['cnt'];
	$TOTAL['amt'] += $LIST[$i]['amt'];
}

if(($_SERVER['REQUEST_METHOD']=='POST')) {
	$button = '<button type="button" class="btn btn-sm btn-success" onClick="axFrame.location.replace(\''.$_SERVER['PHP_SELF'].'?sdate='.$sdate.'&edate='.$edate.'\')">엑셀 다운로드</button>';
}
else {
	$button = '';

	$now_date = date('Ymd');
	$file_name = "($now_date)회원별_투자금액.xls";
	$file_name = iconv("utf-8", "euc-kr", $file_name);

	header( "Content-type: application/vnd.ms-excel;" );
	header( "Content-Disposition: attachment; filename=$file_name" );
	header( "Content-description: PHP5 Generated Data" );
}

debug_flush("
	<table style='border:0'>
		<tr>
			<td colspan=6><h3>투자금액 {$button}</h3></td>
		</tr>
	</table>
	<table border=1 class='table-striped table-hover' style='font-size:9pt'>
		<tr align='center'>
			<th>회원번호</th>
			<th>ID</th>
			<th>회원구분</th>
			<th>성명.법인명</th>
			<th>총투자건수</th>
			<th>총투자금액</th>
		</tr>
		<tr align='center'>
			<td style='background:#DDFFFF'>합계</td>
			<td style='background:#DDFFFF' colspan='3'></td>
			<td style='background:#DDFFFF' align='right'>".number_format($TOTAL['cnt'])."</td>
			<td style='background:#DDFFFF' align='right'>".number_format($TOTAL['amt'])."</td>
		</tr>\n");


for($i=0,$j=1; $i<$rows; $i++,$j++) {
	$bgcolor = ($LIST[$i]['mb_no']=='') ? '#FFDDDD' : '';

	if($LIST[$i]['member_type']) { $LIST[$i]['member_type']= ($LIST[$i]['member_type']=='2') ? '법인' : '개인'; }

	debug_flush("
		<tr align='center'>
			<td style='background:$bgcolor'>".$LIST[$i]['mb_no']."</td>
			<td style='background:$bgcolor'>".$LIST[$i]['mb_id']."</td>
			<td style='background:$bgcolor'>".$LIST[$i]['member_type']."</td>
			<td style='background:$bgcolor'>".$LIST[$i]['mb_name']."</td>
			<td style='background:$bgcolor' align='right'>".number_format($LIST[$i]['cnt'])."</td>
			<td style='background:$bgcolor' align='right'>".number_format($LIST[$i]['amt'])."</td>
		</tr>\n");
}
debug_flush("</table>");


?>