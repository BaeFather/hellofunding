<?
###############################################################################
## 2021-06-24 금강원 자료 요청에 의해 전승찬 만듬
###############################################################################

$sub_menu = '';
include_once('./_common.php');


auth_check($auth[$sub_menu], "w");


$html_title = "투자중인 회원";
$g5['title'] = $html_title.' 정보';

include_once (G5_ADMIN_PATH.'/admin.head.php');

while( list($k, $v) = each($_REQUEST) ) { if(!is_array($k)) ${$k} = trim($v); }

if(!$start_date) $start_date = date("Y-m-d");
if(!$end_date)   $end_date   = date("Y-m-d");

$sql = "SELECT 
			C.mb_id,
			B.member_idx, SUM(B.amount) sum_amount, COUNT(A.idx) cnt_prd,
			C.mb_name, C.mb_co_name
		FROM 
			cf_product_invest B
		LEFT JOIN 
			cf_product A ON (A.idx=B.product_idx)
		LEFT JOIN 
			g5_member C ON (C.mb_no = B.member_idx)
		WHERE 
			A.loan_start_date<='$start_date'
			AND   A.loan_end_date  >='$end_date'
			AND   B.amount>1
		GROUP BY 
			B.member_idx
		ORDER BY 
			C.mb_name
		";
$res = sql_query($sql);
$cnt = sql_num_rows($res);

$sql2 = "
	SELECT 
		SUM(B.amount) AS amt
	FROM 
		cf_product_invest B 
	LEFT JOIN 
		cf_product A ON (A.idx=B.product_idx) 
	LEFT JOIN 
		g5_member C ON (C.mb_no = B.member_idx) 
	WHERE		
		A.loan_start_date <= '$start_date' AND A.loan_end_date >= '$end_date' AND B.amount > 1

	";

$row2 = sql_fetch($sql2);
$total = $row2['amt'];

?>


<div class="tbl_head02 tbl_wrap">

	<!-- 검색영역 START -->
	<div style="display:inline-block;line-height:28px;margin-bottom:8px; width:100%;">
		<form name="f" method="POST">
		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li><input type="text" name="start_date" value="<?=$start_date?>" class="form-control input-sm datepicker" style="width:120px" readonly></li>
			<li>~</li>
			<li><input type="text" name="end_date" value="<?=$end_date?>" class="form-control input-sm datepicker" style="width:120px" readonly></li>
			<li>
				<button class="btn btn-sm btn-warning" onClick="go_submit();">검색</button>
			</li>
		</ul>
		</form>
	</div>

<script>
function go_submit() {
	var f = document.f;
	f.submit;
}
</script>

	<!-- 리스트 START -->

	<div style="float:right; display:inline-block; font-size:12px;line-height:20px;width:100%;">

	<table id="dataList" class="table table-striped table-bordered table-hover" style="min-width:1000px; padding-top:0; font-size:12px;">
		<thead style="font-size:13px">
		<tr>
			<th scope="col" style="text-align:center;">NO.</th>
			<th scope="col">
				<div class="td bt_line">ID</div>
			</th>
			<th scope="col">
				<div class="td bt_line">회원번호</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">이름</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">투자건수</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">투자금액</div>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr style="background-color: #fff0f0;">
			<td colspan="5"></td>
			<td align="center">
				<span style="font-size:11px">총 <?=number_format($total)?>원</span><br>
			</td>
		</tr>
<?

$num = $cnt;
for ($i=0 ; $i<$cnt ; $i++) { 
	$row = sql_fetch_array($res);
	?>
		<tr>
			<td align="center">
				<span style="font-size:11px"><?=$num--?></span><br>
			</td>
			<td align="center">
				<span style="font-size:11px"><?=$row["mb_id"]?></span><br>
			</td>
			<td align="center">
				<span style="font-size:11px"><?=$row["member_idx"]?></span><br>
			</td>
			<td align="center">
				<span style="font-size:11px"><?=$row["mb_name"]?> <?=$row["mb_co_name"]?"/ ".$row["mb_co_name"]:""?></span><br>
			</td>
			<td align="center">
				<span style="font-size:11px"><?=$row["cnt_prd"]?></span><br>
			</td>
			<td align="center">
				<span style="font-size:11px"><?=number_format($row["sum_amount"])?></span><br>
			</td>
		</tr>
	<?
}
?>

	</table>

	</div>

</div>

<?
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>