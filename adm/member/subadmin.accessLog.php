<?
// 관리자 페이지 열람 기록

include_once("_common.php");

while( list($k, $v) = each($_REQUEST) ) { ${$k} = trim($v); }


if(!$sdate) $sdate = date('Y-m-d');
if(!$edate) $edate = date('Y-m-d');

$sdatetime = $sdate . ' 00:00:00';
$edatetime = $edate . ' 23:59:59';

$where = "1";
if($mb_no) $where.= " AND mb_no='".$mb_no."'";
$where.= " AND dt BETWEEN '".$sdatetime."' AND '".$edatetime."'";

$sql = "
	SELECT
		login_no, dt, mb_no, mb_name, title, path, param
	FROM
		g5_admin_access_log
	WHERE
		$where
	ORDER BY
		dt DESC";
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
			<col style='width:300px'>
			<col>
		</colgroup>
		<thead>
			<tr align='center'>
				<td class='tdx' style='background:#F8F8EF'>NO</td>
				<td class='tdx' style='background:#F8F8EF'>접속번호</td>
				<td class='tdx' style='background:#F8F8EF'>열람일시</td>
				<td class='tdx' style='background:#F8F8EF'>직무</td>
				<td class='tdx' style='background:#F8F8EF'>관리자명</td>
				<td class='tdx' style='background:#F8F8EF'>접속페이지</td>
				<td class='tdx' style='background:#F8F8EF'>URL</td>
			</tr>
		</thead>
		<tbody>\n";

if($rcount) {
	for($i=0,$j=$rcount; $i<$rcount; $i++,$j--) {

		$LIST[$i] = sql_fetch_array($result);

		$NAME = explode('-', $LIST[$i]['mb_name']);

		$page_url = "";
		$page_url.= $LIST[$i]['path'];
		if($LIST[$i]['param']) $page_url.= '?' . $LIST[$i]['param'];

		echo "			<tr align='center'>
				<td class='tdx'>" . $j . "</td>
				<td class='tdx'>" . $LIST[$i]['login_no'] . "</td>
				<td class='tdx'>" . $LIST[$i]['dt'] . "</td>
				<td class='tdx'>" . $NAME[0] . "</td>
				<td class='tdx'>" . $NAME[1] . "</td>
				<td class='tdx'><div style='height:18px;line-height:18px;overflow:hidden'>" . $LIST[$i]['title'] . "</div></td>
				<td class='tdx' align='left'><div style='height:18px;line-height:18px;overflow:hidden'>" . $page_url . "</div></td>
			</tr>\n";

	}
}
else {
	echo "			<tr align='center'><td colspan='10'>데이터가 없습니다.</td></tr>\n";
}

echo "
		</tbody>
	</table>\n";


sql_free_result($result);

?>