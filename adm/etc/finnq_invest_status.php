<?
###############################################################################
## 월별 핀크 투자액 및 수수료 추출 (대상월이 없을 경우 이전달 데이터를 출력함)
###############################################################################


include_once('_common.php');

while( list($k, $v) = each($_REQUEST) ) { ${$k} = trim($v); }

if(!$target_date) {
	$target_date = date("Y-m", strtotime("first day of -1 month"));
	$print_target_date = date("Y년m월", strtotime("first day of -1 month"));
}

$sql = "
	SELECT
		(SELECT syndi_invest_idx FROM cf_product_invest WHERE idx=A.invest_idx) AS syndi_invest_idx,
		A.invest_idx, A.product_idx,
		B.title,
		C.finnq_userid,
		A.amount,
		ROUND((A.amount * 0.008)) AS syndi_fee,
		A.insert_date
	FROM
		cf_product_invest_detail A
	LEFT JOIN
		cf_product B  ON A.product_idx=B.idx
	LEFT JOIN
		g5_member C  ON A.member_idx=C.mb_no
	WHERE (1)
		AND A.invest_state = 'Y'
		AND A.syndi_id = 'finnq'
		AND LEFT(B.loan_start_date,7) = '".$target_date."'
	ORDER BY
	B.start_num ASC,
	B.loan_start_date ASC,
	syndi_invest_idx ASC";
//print_rr($sql, 'font-size:12px');

$res = sql_query($sql);
$rows = $res->num_rows;

$SUM = array(
	'invest_count'  => 0,
	'invest_amount' => 0,
	'syndi_fee'     => 0
);
for($i=0,$j=1; $i<$rows; $i++,$j++) {
	$LIST[$i] = sql_fetch_array($res);

	$SUM['invest_count']  += 1;
	$SUM['amount'] += $LIST[$i]['amount'];
	$SUM['syndi_fee']     += $LIST[$i]['syndi_fee'];

}
$list_count = count($LIST);

$now_date  = date('Ymd');
$file_name = $now_date . "_핀크투자내역(".$print_target_date.").xls";
$file_name = iconv("utf-8", "euc-kr", $file_name);

header( "Content-type: application/vnd.ms-excel;" );
header( "Content-Disposition: attachment; filename=$file_name" );
header( "Content-description: PHP4 Generated Data" );

?>

<table border="0" style="font-size:10pt">
	<tr>
		<th colspan="9" style="font-size:16pt">신디케이션(핀크) 투자 및 수수료 현황 (<?=$print_target_date?>)</th>
	</tr>
</table>

<table border="1" style="font-size:10pt">
	<tr>
		<th style="background:#F8F8EF">NO</th>
		<th style="background:#F8F8EF">헬로투자번호</th>
		<th style="background:#F8F8EF">핀크투자번호</th>
		<th style="background:#F8F8EF">핀크회원번호</th>
		<th width="450" style="background:#F8F8EF">상품번호</th>
		<th style="background:#F8F8EF">상품명</th>
		<th style="background:#F8F8EF">투자일</th>
		<th style="background:#F8F8EF">투자금액</th>
		<th style="background:#F8F8EF">핀크수수료</th>
	</tr>
	<tr align="center">
		<td style="background:#FFDDDD;color:brown">합계 <?=number_format($SUM['invest_count'])?>건</td>
		<td style="background:#FFDDDD;color:brown"></td>
		<td style="background:#FFDDDD;color:brown"></td>
		<td style="background:#FFDDDD;color:brown"></td>
		<td style="background:#FFDDDD;color:brown"></td>
		<td style="background:#FFDDDD;color:brown"></td>
		<td style="background:#FFDDDD;color:brown"></td>
		<td style="background:#FFDDDD;color:brown;text-align:right"><?=number_format($SUM['amount'])?></td>
		<td style="background:#FFDDDD;color:brown;text-align:right"><?=number_format($SUM['syndi_fee'])?></td>
	</tr>
<?
for($i=0,$j=1; $i<$list_count; $i++,$j++) {

	echo "	<tr align='center'>
		<td>" . $j . "</td>
		<td>" . $LIST[$i]['invest_idx'] . "</td>
		<td>" . $LIST[$i]['syndi_invest_idx'] . "</td>
		<td>" . $LIST[$i]['finnq_userid'] . "</td>
		<td>" . $LIST[$i]['product_idx'] . "</td>
		<td style='text-align:left;'>" . $LIST[$i]['title'] . "</td>
		<td>" . $LIST[$i]['insert_date'] . "</td>
		<td style='text-align:right'>" . number_format($LIST[$i]['amount']) . "</td>
		<td style='text-align:right'>" . number_format($LIST[$i]['syndi_fee']) . "</td>
	</tr>\n";

}
?>

</table>

<?

sql_close();
exit;

?>