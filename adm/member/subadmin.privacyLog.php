<?
// 개인정보 열람 내역

include_once("_common.php");

while( list($k, $v) = each($_REQUEST) ) { ${$k} = trim($v); }
$linkX = sql_connect(G5_MYSQL_HOST2, G5_MYSQL_USER2, G5_MYSQL_PASSWORD2, G5_MYSQL_DB2) or die('DB2 Connect Error!!!');

if(!$sdate) $sdate = date('Y-m-d');
if(!$edate) $edate = date('Y-m-d');

$sdatetime = $sdate . ' 00:00:00';
$edatetime = $edate . ' 23:59:59';

$where = "1";
if($mb_no) $where.= " AND mb_no='".$mb_no."'";
$where.= " AND rdate BETWEEN '".$sdatetime."' AND '".$edatetime."'";

$sql = "
	SELECT
		rdate AS dt, mb_no, mb_id, target_mb_no, target_mb_id, request_url, ip
	FROM
		connect_log
	WHERE
		$where
	ORDER BY
		rdate DESC";
$result = sql_query($sql, G5_DISPLAY_SQL_ERROR, $linkX);
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
			<col style='width:150px'>
			<col style='width:150px'>
			<col style='width:100px'>
			<col style='width:150px'>
			<col style='width:150px'>
			<col>
		</colgroup>
		<thead>
			<tr align='center'>
				<td class='tdx' style='background:#F8F8EF'>NO</td>
				<td class='tdx' style='background:#F8F8EF'>열람일시</td>
				<td class='tdx' style='background:#F8F8EF'>관리자번호</td>
				<td class='tdx' style='background:#F8F8EF'>회원번호</td>
				<td class='tdx' style='background:#F8F8EF'>회원ID</td>
				<td class='tdx' style='background:#F8F8EF'>IP</td>
				<td class='tdx' style='background:#F8F8EF'>요청페이지</td>
			</tr>
		</thead>
		<tbody>\n";

if($rcount) {
	for($i=0,$j=$rcount; $i<$rcount; $i++,$j--) {

		$LIST[$i] = sql_fetch_array($result);

		$request_url = '';
		if(preg_match("/https\:\/\/www\.hellofunding\.co\.kr/i", $LIST[$i]['request_url'])) $request_url.= preg_replace("/https\:\/\/www\.hellofunding\.co\.kr/i", "", $LIST[$i]['request_url']);

		echo "			<tr align='center'>
				<td class='tdx'>" . $j . "</td>
				<td class='tdx'>" . $LIST[$i]['dt'] . "</td>
				<td class='tdx'>" . $LIST[$i]['mb_id'] . "</td>
				<td class='tdx'>" . $LIST[$i]['target_mb_no'] . "</td>
				<td class='tdx'>" . $LIST[$i]['target_mb_id'] . "</td>
				<td class='tdx'>" . $LIST[$i]['ip'] . "</td>
				<td class='tdx' align='left'><div style='height:18px;line-height:18px;overflow:hidden'>" . $request_url . "</div></td>
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
sql_close($linkX);

?>