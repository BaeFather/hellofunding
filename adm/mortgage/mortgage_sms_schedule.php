<?
/**
 * 주담대 문자 스케줄링
 */
include_once('./_common.php');

if ($is_admin != 'super' && $w == '') {
	alert('최고관리자만 접근 가능합니다.');
}

while(list($key, $value) = each($_GET)) {
	if(!is_array(${$key})) ${$key} = trim($value);
}
?>
<table>
<?
$sql = "SELECT * FROM cf_loaner_push_schedule WHERE product_idx='$prd_idx' ORDER BY schedule_date DESC";
$res = sql_query($sql);
$cnt = $res->num_rows;

for ($i=0 ; $i<$cnt ; $i++) {
	$row = sql_fetch_array($res);
	?>
	<tr>
		<td><?=$row['turn']?></td>
		<td><?=$row['schedule_date']?></td>
		<!--td><pre><?=$row['msg']?></pre></td-->
	</tr>
	<?
}
?>
</table>