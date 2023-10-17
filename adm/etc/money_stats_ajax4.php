<?
###############################
## 아이디별 원리금 상환액
##	계좌출금건만...
###############################

include_once('_common.php');

$sdate = $_REQUEST['sdate'];
$edate = $_REQUEST['edate'];

$banking_date_s = $sdate . ' 00:00:00';
$banking_date_e = $edate . ' 23:59:59';

$sql = "
	SELECT
		A.member_idx,
		COUNT(A.idx) AS interest_repay_cnt,
		SUM(A.interest) AS interest_amt,
		(SELECT IFNULL(COUNT(idx), 0) FROM cf_product_give WHERE member_idx=A.member_idx AND receive_method='1' AND principal>0) AS principal_repay_cnt,
		SUM(A.principal) AS principal_amt,
		A.banking_date
	FROM
		cf_product_give A
	WHERE (1)
		AND A.receive_method='1'
		AND A.banking_date BETWEEN '$banking_date_s' AND '$banking_date_e'
	GROUP BY
		A.member_idx
	ORDER BY
		interest_repay_cnt DESC,
		A.invest_idx ASC";
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

	$LIST[$i]['interest_repay_cnt']  = $ROW['interest_repay_cnt'];
	$LIST[$i]['interest_amt']        = $ROW['interest_amt'];
	$LIST[$i]['principal_repay_cnt'] = $ROW['principal_repay_cnt'];
	$LIST[$i]['principal_amt']       = $ROW['principal_amt'];
	$LIST[$i]['sum_amt']             = $ROW['interest_amt'] + $ROW['principal_amt'];

	$TOTAL['interest_repay_cnt']   += $LIST[$i]['interest_repay_cnt'];
	$TOTAL['interest_amt']         += $LIST[$i]['interest_amt'];
	$TOTAL['principal_repay_cnt']  += $LIST[$i]['principal_repay_cnt'];
	$TOTAL['principal_amt']        += $LIST[$i]['principal_amt'];
	$TOTAL['sum_amt']              += $LIST[$i]['sum_amt'];
}

if(($_SERVER['REQUEST_METHOD']=='POST')) {
	$button = '<button type="button" class="btn btn-sm btn-success" onClick="axFrame.location.replace(\''.$_SERVER['PHP_SELF'].'?sdate='.$sdate.'&edate='.$edate.'\')">엑셀 다운로드</button>';
}
else {
	$button = '';

	$now_date = date('Ymd');
	$file_name = "($now_date)회원별_원리금상환액.xls";
	$file_name = iconv("utf-8", "euc-kr", $file_name);

	header( "Content-type: application/vnd.ms-excel;" );
	header( "Content-Disposition: attachment; filename=$file_name" );
	header( "Content-description: PHP5 Generated Data" );
}

debug_flush("
	<table style='border:0'>
		<tr>
			<td colspan=9><h3>원리금 상환액 (실계좌 지급건) {$button}</h3></td>
		</tr>
	</table>
	<table border=1 class='table-striped table-hover' style='font-size:9pt'>
		<tr align='center'>
			<th>회원번호</th>
			<th>ID</th>
			<th>회원구분</th>
			<th>성명.법인명</th>
			<th>이자지급건수</th>
			<th>이자지급액</th>
			<th>원금지급건수</th>
			<th>원금지급액</th>
			<th>지급액합계</th>
		</tr>
		<tr align='center'>
			<td style='background:#DDFFFF'>합계</td>
			<td style='background:#DDFFFF' colspan='3'></td>
			<td style='background:#DDFFFF' align='right'>".number_format($TOTAL['interest_repay_cnt'])."</td>
			<td style='background:#DDFFFF' align='right'>".number_format($TOTAL['interest_amt'])."</td>
			<td style='background:#DDFFFF' align='right'>".number_format($TOTAL['principal_repay_cnt'])."</td>
			<td style='background:#DDFFFF' align='right'>".number_format($TOTAL['principal_amt'])."</td>
			<td style='background:#DDFFFF' align='right'>".number_format($TOTAL['sum_amt'])."</td>
		</tr>\n");


for($i=0,$j=1; $i<$rows; $i++,$j++) {
	$bgcolor = ($LIST[$i]['mb_id']=='') ? '#FFDDDD' : '';

	if($LIST[$i]['member_type']) { $LIST[$i]['member_type']= ($LIST[$i]['member_type']=='2') ? '법인' : '개인'; }

	debug_flush("
		<tr align='center'>
			<td style='background:$bgcolor'>".$LIST[$i]['mb_no']."</td>
			<td style='background:$bgcolor'>".$LIST[$i]['mb_id']."</td>
			<td style='background:$bgcolor'>".$LIST[$i]['member_type']."</td>
			<td style='background:$bgcolor'>".$LIST[$i]['mb_name']."</td>
			<td style='background:$bgcolor' align='right'>".number_format($LIST[$i]['interest_repay_cnt'])."</td>
			<td style='background:$bgcolor' align='right'>".number_format($LIST[$i]['interest_amt'])."</td>
			<td style='background:$bgcolor' align='right'>".number_format($LIST[$i]['principal_repay_cnt'])."</td>
			<td style='background:$bgcolor' align='right'>".number_format($LIST[$i]['principal_amt'])."</td>
			<td style='background:$bgcolor' align='right'>".number_format($LIST[$i]['sum_amt'])."</td>
		</tr>\n");
}
debug_flush("</table>");


?>