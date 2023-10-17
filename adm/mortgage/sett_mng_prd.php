<?
include_once('./_common.php');
print_r($_REQUEST);echo "<br/><br/>";
while(list($key, $value) = each($_REQUEST)) {
	if(!is_array(${$key})) ${$key} = trim($value);
}

include_once('../admin.head.nomenu.php');
?>
<?
if ($mode=="save") {
	$up_sql = "UPDATE cf_debt_product
				  SET sms_yn='$sms_yn'
				WHERE product_idx='$prd_idx'";
	sql_query($up_sql);
}
?>
<?
$sql = "SELECT * FROM cf_debt_product WHERE product_idx='$prd_idx'";
echo $sql."<br/>";
$res = sql_query($sql);
$cnt = $res->num_rows;
if (!$cnt) die("상품 번호 오류");
$row = sql_fetch_array($res);

//echo "$sql (".$cnt.")";
echo "<br/><br/><br/>";
?>
<form method="POST" name="fm">
<input type="hidden" name="mode"/>
<div style="margin-top:20px;width: 100%; text-align:center;">
	<div>
		<span style="margin-right: 20px;">문자 수진 여부</span>
		<input type="radio" name="sms_yn" value="Y" <?=$row["sms_yn"]=="Y"?"checked":""?> > 수신
		<input type="radio" name="sms_yn" value="N" <?=$row["sms_yn"]=="N"?"checked":""?> style="margin-left:10px;" > 미수신
	</div>
	<div style="margin-top: 30px;">
		<button class='btn btn-default' onclick="go_save();">저장</button>
	</div>
</div>
</form>

<script>
function go_save() {

	var yn = confirm("저장하시겠습니까?");
	if (yn) {
		var f = document.fm;
		f.mode.value="save";
		f.submit();
	}
}
</script>