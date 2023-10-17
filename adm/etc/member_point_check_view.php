<?

// 포인트체크 출력자료

$path = "/home/crowdfund/public_html";
include_once($path . '/common.cli.php');

$sql = "SELECT * FROM member_point_check ORDER BY dt DESC";
$res = sql_query($sql);
$rows = $res->num_rows;

echo "
<div style='width:1600px;height:99%;overflow-y:scroll'>
	<table border='1' style='width:100%;font-size:10pt;border-collapse:collapse;'>
		<tr align='center' style='background:#F8F8EF'>
			<td>NO</td>
			<td>SDATE</td>
			<td>회원번호</td>
			<td>아이디</td>
			<td>은행예치금</td>
			<td>투자대기금</td>
			<td>헬로예치금</td>
			<td>예치금차액</td>
			<td>취합일시</td>
		</tr>\n";

$no = $rows;
while($R = sql_fetch_array($res)) {

	$fcolor = ($R['diff_amt']<>0) ? '#FF2222' : '';

	echo "
		<tr align='center' style='color:$fcolor'>
			<td>".$no."</td>
			<td>".$R['sdate']."</td>
			<td>".$R['mb_no']."</td>
			<td>".$R['mb_id']."</td>
			<td align='right'>".number_format($R['bpoint'])."</td>
			<td align='right'>".number_format($R['lockpoint'])."</td>
			<td align='right'>".number_format($R['hpoint'])."</td>
			<td align='right'>".number_format($R['diff_amt'])."</td>
			<td>".$R['dt']."</td>
		</tr>\n";

	$no--;

}

echo "	</table>
</div>\n";

sql_close();
exit;

?>