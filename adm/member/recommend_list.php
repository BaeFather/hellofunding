<?
include_once('./_common.php');

auth_check($auth[$sub_menu], "w");

while(list($key, $value)=each($_REQUEST)) { ${$key} = trim($value); }

$MEM = sql_fetch("SELECT mb_id FROM g5_member WHERE mb_no='$mb_no'");

$where = "1=1";
$where.= " AND rec_mb_no='$mb_no' AND va_bank_code!=''";
if($field) {
	if($sdate && $edate) {
		$where.= " AND LEFT($field, 10) BETWEEN '$sdate' AND '$edate'";
	}
	else {
		if($sdate) $where.= " AND LEFT($field, 10) >= '$sdate'";
		else if($edate) $where.= " AND LEFT($field, 10) <= '$edate'";
	}
}

$sql  = "SELECT mb_no, mb_id, mb_name, mb_hp, mb_datetime, rec_date FROM g5_member WHERE $where ORDER BY rec_date DESC";
$res  = sql_query($sql);
$rows = sql_num_rows($res);

for($i=0; $i<$rows; $i++) {
	$LIST[$i] = sql_fetch_array($res);
}
//print_r($LIST);

?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta http-equiv="imagetoolbar" content="no">
<meta http-equiv="X-UA-Compatible" content="IE=10,chrome=1">
<title>전체 회원 정보 | 헬로펀딩</title>
<link rel="stylesheet" href="/adm/css/admin.css">
<!--[if lte IE 8]><script src="/js/html5.js"></script><![endif]-->
<script src="/js/jquery-1.8.3.min.js"></script>
<script src="/js/jquery.menu.js"></script>
<script src="/js/common.js"></script>
<script src="/js/wrest.js"></script>
<link href="/adm/css/bootstrap.min.css" rel="stylesheet">
<link href="/adm/css/jquery-ui.min.css" rel="stylesheet">
<style>
html, body, table { font-size:12px; }
ul { list-style-type:none; margin:0; padding:0 }
li { float:left; display:inline; }
</style>
<script src="/adm/js/jquery-ui.min.js"></script>
<script>
$(function() {
	$(".datepicker").datepicker({
		dateFormat: 'yy-mm-dd'
	});
});
</script>

<body style="margin:8px;">

<h3><?=$MEM['mb_id']?> 회원 추천 내역</h3>
<div style="margin-top:10px; display:inline-block;width:660px; background-color:#FFF;">
	<ul>
	  <li style="width:18%; margin-left:8px;"><?=number_format($rows)?>건</li>
		<li style="width:80%">
			<form method="get" style="margin:0 5px 0 0; text-align:right">
				<input type="hidden" name="mb_no" value="<?=$mb_no?>">
				<select name="field" class="frm_input">
					<option value="">:: 선택 ::</option>
					<option value="mb_datetime" <?=($field=='mb_datetime')?'selected':''?>>가입일</option>
					<option value="rec_date" <?=($field=='rec_date')?'selected':''?>>추천확정일</option>
				</select> &nbsp;
				<input type="text" class="frm_input datepicker" name="sdate" value="<?=$sdate?>"> ~
				<input type="text" class="frm_input datepicker" name="edate" value="<?=$edate?>">
				<button class="btn btn-sm btn-info" style="line-height:12px;">검색</button>
			</form>
		</li>
	</ul>
</div>
<div style="width:660px; background-color:#FFF;">
  <div style="width:100%;height:30px;">
	  <table border="1">
			<colgroup>
				<col width="60px">
				<col width="100px">
				<col width="100px">
				<col width="100px">
				<col width="150px">
				<col width="150px">
			</colgroup>
			<tr style="background-color:#F8F8EF">
				<th style="height:30px">NO</th>
				<th style="height:30px">아이디</th>
				<th style="height:30px">성명</th>
				<th style="height:30px">휴대폰</th>
				<th style="height:30px">가입일</th>
				<th style="height:30px">추천확정일</th>
			</tr>
		</table>
	</div>
	<div style="width:100%;height:451px;overflow-y:auto;">
		<table border="1">
			<colgroup>
				<col width="60px">
				<col width="100px">
				<col width="100px">
				<col width="100px">
				<col width="150px">
				<col width="150px">
			</colgroup>
<?
if($rows) {
	for($i=0, $no=$rows; $i<$rows; $i++,$no--) {
?>
			<tr onMouseOver="this.bgColor='#FFFFB5'" onMouseOut="this.bgColor=''">
				<td style="height:30px;text-align:center;"><?=$no?></td>
				<td style="text-align:center;"><?=$LIST[$i]['mb_id']?></td>
				<td style="text-align:center;"><?=$LIST[$i]['mb_name']?></td>
				<td style="text-align:center;"><?=$LIST[$i]['mb_hp']?></td>
				<td style="text-align:center;"><?=substr($LIST[$i]['mb_datetime'], 0, 16)?></td>
				<td style="text-align:center;"><?=substr($LIST[$i]['rec_date'], 0, 16)?></td>
			</tr>
<?
	}
}
else {
	echo "<tr><td colspan='5' style='height:29px;text-align:center;'>추천 내역이 없습니다.</td></tr>\n";
}
?>
		</table>
	</div>
</div>

</body>
</html>