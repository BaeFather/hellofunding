<?

include_once('_common.php');


$sql = "
	SELECT
		idx, request_arr, request_code, request_summary, rcode, msg, mb_id AS exec_mb_id, exec_path, regdate, edate
	FROM
		IB_request_log
	WHERE 1
		AND request_code='3200'
		AND LEFT(regdate,10)='2019-09-06'
		AND rcode='00000000'
	ORDER BY
		idx DESC";
$res = sql_query($sql);
$rows = $res->num_rows;

$TOTAL = array(
	'count' => 0,
	'amount' => 0,
	'succ_count' => 0,
	'succ_amount' => 0
);

for($i=0; $i<$rows; $i++) {
	if( $R = sql_fetch_array($res) ) {

		$LIST[$i] = $R;

		$REQUEST_ARR = explode("&", $R['request_arr']);
		for($x=0; $x<count($REQUEST_ARR); $x++) {
			$ARR = explode("=", $REQUEST_ARR[$x]);

			$LIST[$i]['ARR'][$ARR[0]] = $ARR[1];
		}

		if($LIST[$i]['ARR']['CUST_ID']) {
			$R2 = sql_fetch("SELECT mb_id FROM g5_member WHERE mb_no='".$LIST[$i]['ARR']['CUST_ID']."'");
			$LIST[$i]['mb_id'] = $R2['mb_id'];
		}


		$TOTAL['count'] += 1;
		$TOTAL['amount'] += $LIST[$i]['ARR']['TRAN_AMT'];

		if($R['rcode']=='00000000') {
			$TOTAL['succ_count'] += 1;
			$TOTAL['succ_amount'] += $LIST[$i]['ARR']['TRAN_AMT'];
		}

		unset($LIST[$i]['request_arr']);

	}

}

$list_count = count($LIST);

//print_rr($TOTAL, 'font-size:12px;color:brown');
//print_rr($LIST, 'font-size:12px');

echo "
<style>
th, td {padding:2px 4px}
</style>
<table align='center' border='1' style='width:1200px;border-collapse:collapse;font-size:12px'>
	<tr style='background:#F8F8EF'>
		<th>NO</th>
		<th>로그번호</th>
		<th>전문번호</th>
		<th>전문구분</th>
		<th>출금금액</th>
		<th>대상회원번호</th>
		<th>대상회원ID</th>
		<th>실행자ID</th>
		<th>실행위치</th>
		<th>로그기록시간</th>
		<th>실행결과</th>
	</tr>
	<tr align='center' style='background:#FFDDDD'>
		<td>합계</td>
		<td></td>
		<td></td>
		<td colspan='3' align='center'>".number_format($TOTAL['succ_amount'])." / ".number_format($TOTAL['amount'])."</td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
	</tr>\n";

for($i=0,$j=$list_count; $i<$list_count; $i++,$j--) {

	$print_msg = ($LIST[$i]['rcode']=='00000000') ? '정상실행' : '<font color=red>' . $LIST[$i]['msg'] . '</font>';

	echo "	<tr align='center'>
		<td>".$j."</td>
		<td>".$LIST[$i]['idx']."</td>
		<td>".$LIST[$i]['ARR']['FB_SEQ']."</td>
		<td>".$LIST[$i]['request_summary']."</td>
		<td align='right'>".number_format($LIST[$i]['ARR']['TRAN_AMT'])."</td>
		<td>".$LIST[$i]['ARR']['CUST_ID']."</td>
		<td>".$LIST[$i]['mb_id']."</td>
		<td>".$LIST[$i]['exec_mb_id']."</td>
		<td align='left'>".$LIST[$i]['exec_path']."</td>
		<td>".$LIST[$i]['edate']."</td>
		<td align='center'>".$print_msg."</td>
	</tr>\n";

}

echo "</table>\n";


?>