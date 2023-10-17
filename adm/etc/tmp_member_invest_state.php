<?
/*
개인회원 엑셀출력
예치금 / 투자잔액 / 누적투자금액
*/

include_once("_common.php");

$res = sql_query("
	SELECT
		A.mb_id, A.mb_name, A.mb_point,
		(SELECT COUNT(amount) FROM cf_product_invest WHERE member_idx=A.mb_no AND invest_state='Y') AS invest_cnt,
		(SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE member_idx=A.mb_no AND invest_state='Y') AS invest_amt,
		(
			SELECT
				IFNULL(SUM(amount),0)
			FROM
				cf_product_invest CPI
			LEFT JOIN
				cf_product CP  ON CPI.product_idx=CP.idx
			WHERE 1
				AND CPI.invest_state='Y'
				AND CP.state IN('','1')
				AND member_idx=A.mb_no
		) AS live_invest_amt
	FROM
		g5_member A
	WHERE 1
		AND A.mb_level='1'
		AND A.member_type='1'
	ORDER BY
		A.mb_point DESC,
		live_invest_amt DESC,
		invest_amt DESC,
		A.mb_no
");

$rows = $res->num_rows;


$now_date  = date('Ymd');
$file_name = "개인회원투자금액정리_".$now_date.".xls";
$file_name = iconv("utf-8", "euc-kr", $file_name);

header("Content-type: application/vnd.ms-excel;");
header("Content-Disposition: attachment; filename=$file_name");
header("Content-description: PHP4 Generated Data");


debug_flush("
	<table border=1 style='font-size:10pt'>
		<tr>
			<td align='center'>ID</td>
			<td align='center'>성명</td>
			<td align='center'>예치금</td>
			<td align='center'>투자잔액</td>
			<td align='center'>누적투자액</td>
		</tr>\n");

for($i=0; $i<$rows; $i++) {
	$ROW = sql_fetch_array($res);

	if($ROW['mb_point'] > 0 || $ROW['invest_amt'] > 0 || $ROW['live_invest_amt'] > 0) {
		debug_flush("
		<tr>
		<td align='center'>".$ROW['mb_id']."</td>
		<td align='center'>".$ROW['mb_name']."</td>
		<td align='right'>".number_format($ROW['mb_point'])."</td>
		<td align='right'>".number_format($ROW['live_invest_amt'])."</td>
		<td align='right'>".number_format($ROW['invest_amt'])."</td>
		</tr>\n");
	}
	unset($ROW);
}

debug_flush("</table>");

sql_free_result($res);

sql_close();
exit;

?>