<?
// 관리자 로그인 기록

include_once("_common.php");

while( list($k, $v) = each($_REQUEST) ) { ${$k} = trim($v); }


if(!$sdate) $sdate = date('Y-m-d');
if(!$edate) $edate = date('Y-m-d');

$sdatetime = $sdate . ' 00:00:00';
$edatetime = $edate . ' 23:59:59';

$where = "1";
if($mb_no) $where.= " AND mb_no='".$mb_no."'";
$where.= " AND all_datetime BETWEEN '".$sdatetime."' AND '".$edatetime."'";

$sql = "
	SELECT
		all_id AS login_no, all_datetime AS dt, mb_no, mb_name, all_ip AS ip, all_device AS device
	FROM
		g5_admin_login_log
	WHERE
		$where
	ORDER BY
		all_id DESC";
$result = sql_query($sql);
$rcount = $result->num_rows;

echo "
<style>
.tdx { padding:2px 4px; }
</style>
\n";

echo "
	<table id='logList' class='tableZ table-bordered table-striped' style='width:100%;font-size:12px'>
		<colgroup>
			<col style='width:80px'>
			<col style='width:100px'>
			<col style='width:150px'>
			<col style='width:150px'>
			<col style='width:150px'>
			<col style='width:150px'>
			<col style='width:100px'>
			<col>
		</colgroup>
		<thead>
			<tr align='center'>
				<td class='tdx' style='background:#F8F8EF'>NO</td>
				<td class='tdx' style='background:#F8F8EF'>접속번호</td>
				<td class='tdx' style='background:#F8F8EF'>접속일시</td>
				<td class='tdx' style='background:#F8F8EF'>직무</td>
				<td class='tdx' style='background:#F8F8EF'>관리자명</td>
				<td class='tdx' style='background:#F8F8EF'>IP</td>
				<td class='tdx' style='background:#F8F8EF'>접속기기</td>
				<td class='tdx' style='background:#F8F8EF'></td>
			</tr>
		</thead>
		<tbody>\n";

if($rcount) {
	for($i=0,$j=$rcount; $i<$rcount; $i++,$j--) {

		$LIST[$i] = sql_fetch_array($result);

		$NAME = explode('-', $LIST[$i]['mb_name']);

		echo "			<tr align='center'>
				<td class='tdx'>" . $j . "</td>
				<td class='tdx'>" . $LIST[$i]['login_no'] . "</td>
				<td class='tdx'>" . $LIST[$i]['dt'] . "</td>
				<td class='tdx'>" . $NAME[0] . "</td>
				<td class='tdx'>" . $NAME[1] . "</td>
				<td class='tdx'>" . $LIST[$i]['ip'] . "</td>
				<td class='tdx'>" . $LIST[$i]['device'] . "</td>
				<td class='tdx'></td>
			</tr>\n";

	}
}
else {
	echo "			<tr align='center'><td colspan='10' class='tdx'>데이터가 없습니다.</td></tr>\n";
}

echo "
		</tbody>
	</table>\n";

sql_free_result($result);


?>