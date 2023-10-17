<?
include_once("_common.php");
include_once(G5_PATH.'/lib/insidebank.lib.php');

while( list($k,$v)=each($_REQUEST) ) { ${$k} = trim($v); }

$sql = "
	SELECT
		A.member_idx, B.mb_id, B.mb_name, B.mb_co_name, A.amount
	FROM
		cf_product_invest A
	LEFT JOIN
		g5_member B  ON A.member_idx=B.mb_no
	WHERE (1)
		AND A.product_idx='".$prd_idx."'
		AND A.invest_state='Y'
	ORDER BY
		A.idx DESC";
//print_rr($sql,'font-size:12px;line-height:13px;');

$res = sql_query($sql);

debug_flush("<table border=1 style='width:100%;font-size:12px;border-collapse:collapse'>
	<tr align='center'>
		<td>회원번호</td>
		<td>ID</td>
		<td>성명</td>
		<td>법인명</td>
		<td>본상품투자금</td>
		<td>신한은행예치금</td>
		<td>헬로예치금</td>
		<td>투자대기건</td>
		<td>투자대기금액</td>
		<td>차액(은행기준)</td>
	</tr>\n");


while( $row = sql_fetch_array($res) ) {
	//print_rr($row, 'font-size:12px;line-height:13px;');

	$ARR['REQ_NUM'] = "041";
	$ARR['CUST_ID'] = $row['member_idx'];

	$INSIDEBANK_RESULT = insidebank_request('256', $ARR);

	$company_balance = get_point_sum($row['mb_id']);
	$bank_balance = 0;

	if($INSIDEBANK_RESULT['RCODE']=='00000000') {

		$company_balance = get_point_sum($row['mb_id']);
		$bank_balance = $INSIDEBANK_RESULT['BALANCE_AMT'];

	}

	$LOCK = sql_fetch("
		SELECT
			IFNULL(SUM(A.amount),0) AS invest_amount,
			COUNT(B.idx) AS invest_count
		FROM
			cf_product_invest A
		LEFT JOIN
			cf_product B  ON A.product_idx=B.idx
		WHERE 1
			AND A.member_idx='".$row['member_idx']."'
			AND A.invest_state='Y'
			AND B.state=''
			AND B.display='Y'
	");

	debug_flush("
		<tr align='center'>
			<td>".$row['member_idx']."</td>
			<td>".$row['mb_id']."</td>
			<td>".$row['mb_name']."</td>
			<td>".$row['mb_co_name']."</td>
			<td align='right'>".number_format($row['amount'])."</td>
			<td align='right'>".number_format($bank_balance)."</td>
			<td align='right'>".number_format($company_balance)."</td>
			<td align='right'>".number_format($LOCK['invest_count'])."</td>
			<td align='right'>".number_format($LOCK['invest_amount'])."</td>
			<td align='right'>".number_format($bank_balance - (MAX($company_balance,0)+$LOCK['invest_amount']))."</td>
		</tr>\n");
}

debug_flush("</table>\n");

sql_close();

?>