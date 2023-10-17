<?
include_once('./_common.php');

while( list($k, $v) = each($_REQUEST) ) { if(!is_array($k)) ${$k} = trim($v); }

$sub_menu = '200700';
auth_check($auth[$sub_menu], "w");


$g5['title'] = "080 수신거부";
include_once (G5_ADMIN_PATH.'/admin.head.php');
?>
<?
if ($srch_hp) $wh1 = " AND mb_hp = '".masterEncrypt($srch_hp,false)."'";
if ($start_date) $wh2 = " AND input_datetime >= '$start_date 00:00:00'";
if ($end_date)   $wh3 = " AND input_datetime <= '$end_date 23:59:59'";


$total_sql = "SELECT COUNT(*) cnt FROM cf_sms_block WHERE 1>0 $wh1 $wh2 $wh3";
$total_res = sql_query($total_sql);
$total_row = sql_fetch_array($total_res);
$total_count = $total_row['cnt'];

$rows = 20;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함
$num = $total_count - $from_record;


$sql = "SELECT * FROM cf_sms_block WHERE 1>0 $wh1 $wh2 $wh3 order by idx desc LIMIT $from_record, $rows";
$res = sql_query($sql);
$cnt = $res->num_rows;

?>

<div class="tbl_head02 tbl_wrap">
	<form name="fm" method="post">
	<div style="display:inline-block;margin-bottom:8px;">
		<input type="text" class="input-sm" name="srch_hp" id="srch_hp" value="<?=$srch_hp?>" style="width:140px;margin-right:15px;" />
		<input type="text" name="start_date" value="<?=$start_date?>" class="input-sm datepicker" style="width:100px;margin-left:20px;" readonly>
		~
		<input type="text" name="end_date"   value="<?=$end_date?>"   class="input-sm datepicker" style="width:100px;margin-right:15px;" readonly>
		<input type="button" value="search" class="btn btn-sm btn-warning" onclick="go_srch();" />
	</div>

	<table class="table table-striped table-bordered table-hover" style="min-width:1000px; padding-top:0; font-size:12px;">
		<tr>
			<th>No</th>
			<th>회원번호</th>
			<th>휴대폰번호</th>
			<th>처리완료</th>
			<th>처리일시</th>
		</tr>
<?
for ($i=0 ; $i<$cnt ; $i++) {
	$row = sql_fetch_array($res);
	if (strlen($row["mb_hp"]) >15) $hp = masterDecrypt($row["mb_hp"],false);
	else $hp = $row["mb_hp"];
	$prc = "";
	if ($row["rcv_sms"]=="N") $prc="처리완료";

	if ($row["mb_no"]>0) $mem_no = $row["mb_no"];
	else if ($row["mb_no"]<0) $mem_no = "비회원";
	else $mem_no = "";
	?>
		<tr>
			<td style="text-align:center;"><?=$num--?></td>
			<td style="text-align:center;"><?=$mem_no?></td>
			<td style="text-align:center;"><?=$hp?></td>
			<td style="text-align:center;"><?=$prc?></td>
			<td style="text-align:center;"><?=$row["input_datetime"]?></td>
		</tr>
	<?
}
?>
	</table>


<?
$qstr = preg_replace("/&page=([0-9]){1,10}/", "", $_SERVER['QUERY_STRING']);
echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page=');
?>

	</form>
</div>

<?
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>

<script>
setTimeout(function(){ $("#srch_hp").focus(); }, 1);

function go_srch() {
	var f=document.fm;
	f.submit();
}

function press(f) {
	alert(f.keyCode);
	if(f.keyCode == 13){ //javascript에서는 13이 enter키를 의미함
		fm.submit(); //formname에 사용자가 지정한 form의 name입력
	}
}

$('input[type="text"]').keydown(function() {
  if (event.keyCode === 13) {
    document.fm.submit();
  };
});
</script>