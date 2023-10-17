<?
###############################################################################
##  투자수익 지급내역 통계
###############################################################################

include_once('./_common.php');

$sub_menu = "700300";
$g5['title'] = $menu['menu700'][3][1];

include_once (G5_ADMIN_PATH.'/admin.head.php');

auth_check($auth[$sub_menu], 'w');
if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');


foreach($_GET as $k=>$v) { ${$_GET[$k]} = trim($v); }

if($syear=='') $syear = date('Y');

$sdate = $syear;
$sdate.= ($smonth) ?  "-" . $smonth : "";

$date_length = (strlen($sdate)==7) ? 10 : 7;


$sql = "
	SELECT
		LEFT(banking_date, ".$date_length.") AS banking_date2,
		COUNT(idx) AS repay_count,
		SUM(interest) AS interest,
		SUM(principal) AS principal,
		SUM(interest_tax) AS interest_tax,
		SUM(local_tax) AS local_tax,
		SUM(fee) AS fee
	FROM
		cf_product_give
	WHERE
		LEFT(banking_date,".strlen($sdate).")='".$sdate."'
	GROUP BY
		banking_date2
	ORDER BY
		banking_date ASC";
//echo $sql;
$result = sql_query($sql);
$rcount = $result->num_rows;

for($i=0; $i<$rcount; $i++) {
	$LIST[$i] = sql_fetch_array($result);

	$LIST[$i]['before_tax_interest'] = $LIST[$i]['interest'] + $LIST[$i]['interest_tax'] + $LIST[$i]['local_tax'] + $LIST[$i]['fee'];
	$LIST[$i]['repay_amount']        = $LIST[$i]['principal'] + $LIST[$i]['before_tax_interest'];

	$TOTAL['repay_count']  += $LIST[$i]['repay_count'];
	$TOTAL['principal']    += $LIST[$i]['principal'];
	$TOTAL['interest']     += $LIST[$i]['interest'];
	$TOTAL['repay_amount'] += $LIST[$i]['repay_amount'];
	$TOTAL['interest_tax'] += $LIST[$i]['interest_tax'];
	$TOTAL['local_tax']    += $LIST[$i]['local_tax'];
	$TOTAL['fee']          += $LIST[$i]['fee'];
	$TOTAL['before_tax_interest'] += $LIST[$i]['before_tax_interest'];
}
sql_free_result($result);

$list_count = count($LIST);

//print_rr($LIST,'font-size:9pt');

?>

<div class="tbl_head02 tbl_wrap">

	<!-- 검색영역 START -->
	<div style="line-height:28px;">
		<form name="frmSearch" method="get" class="form-horizontal">
		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>
				<select id="syear" name="syear" class="form-control input-sm" onChange="fSubmit();">
<?
for($i=2016; $i<=date(Y); $i++) {
	$selected = ($i==$syear) ? 'selected' : '';
	echo "<option value='".$i."' $selected>".$i."년</option>\n";
}
?>
				</select>
			</li>
			<li>
				<select id="smonth" name="smonth" class="form-control input-sm" onChange="fSubmit();">
					<option value="">전체</option>
<?
for($i=1; $i<=12; $i++) {
	$i = sprintf("%02d", $i);
	$selected = ($i==$smonth) ? 'selected' : '';
	echo "<option value='".$i."' $selected>".$i."월</option>\n";
}
?>
				</select>
			</li>
			<li><button type="submit" class="btn btn-sm btn-warning">검색</button></li>
		</ul>
		</form>
	</div>
	<!-- 검색영역 E N D -->

	<table class="table-striped table-bordered table-hover">
		<colgroup>
			<col style="width:11.1%">
			<col style="width:11.1%">
			<col style="width:11.1%">
			<col style="width:11.1%">
			<col style="width:11.1%">
			<col style="width:11.1%">
			<col style="width:11.1%">
			<col style="width:11.1%">
			<col style="width:11.1%">
		</colgroup>
		<tr>
			<th style="background:#F8F8EF">지급처리일</th>
			<th style="background:#F8F8EF">지급건수</th>
			<th style="background:#F8F8EF">원금</th>
			<th style="background:#F8F8EF">이자</th>
			<th style="background:#F8F8EF">원리금 합계</th>
			<th style="background:#F8F8EF">이자소득세</th>
			<th style="background:#F8F8EF">지방소득세</th>
			<th style="background:#F8F8EF">플랫폼이용료</th>
			<th style="background:#F8F8EF">차감지급액</th>
		</tr>

		<!-- 합계 -->
		<tr align="right" style="background:#FFDDDD;color:red">
			<td align="center">합계</td>
			<td><?=number_format($TOTAL['repay_count'])?></td>
			<td><?=number_format($TOTAL['principal'])?></td>
			<td><?=number_format($TOTAL['before_tax_interest'])?></td>
			<td><?=number_format($TOTAL['repay_amount'])?></td>
			<td><?=number_format($TOTAL['interest_tax'])?></td>
			<td><?=number_format($TOTAL['local_tax'])?></td>
			<td><?=number_format($TOTAL['fee'])?></td>
			<td><?=number_format($TOTAL['interest'])?></td>
		</tr>
		<!-- 합계 -->

<?
if($list_count) {
	for($i=0,$j=1; $i<$list_count; $i++,$j++) {

		$DATE = explode("-",$LIST[$i]['banking_date2']);

		if(count($DATE) < 3) {
			$href = "?syear=".$DATE[0];
			$href.= ($DATE[1]) ? "&smonth=".$DATE[1] : "";
			$href.= ($DATE[2]) ? "&sday=".$DATE[2] : "";
		}
		else {
			$href = "profit_give_detail.php?syear=".$DATE[0]."&smonth=".$DATE[1]."&sday=".$DATE[2];
		}

?>
		<tr align="right">
			<td align="center"><a href="<?=$href?>"><?=$LIST[$i]['banking_date2']?></a></td>
			<td><?=number_format($LIST[$i]['repay_count'])?></td>
			<td><?=number_format($LIST[$i]['principal'])?></td>
			<td><?=number_format($LIST[$i]['before_tax_interest'])?></td>
			<td><?=number_format($LIST[$i]['repay_amount'])?></td>
			<td><?=number_format($LIST[$i]['interest_tax'])?></td>
			<td><?=number_format($LIST[$i]['local_tax'])?></td>
			<td><?=number_format($LIST[$i]['fee'])?></td>
			<td><?=number_format($LIST[$i]['interest'])?></td>
		</tr>
<?
	}
}
else {
	echo '
		<tr>
			<td colspan="15" align="center">데이터가 없습니다.</th>
		</tr>' . PHP_EOL;
}
?>
	</table>

</div>

<script>
fSubmit = function() {
	f = document.frmSearch;
	f.submit();
}
</script>

<?

include_once ('../admin.tail.php');

?>