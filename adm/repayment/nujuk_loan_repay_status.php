<?
///////////////////////////////////////////////////////
//	대출.상환 일대사
///////////////////////////////////////////////////////

include_once('./_common.php');

$sub_menu = "700900";
$g5['title'] = $menu['menu700'][10][1];


while( list($k, $v) = each($_REQUEST) ) { ${$k} = trim($v); }

$G_TYPE = array(
	'A' => '전체상품군',
	'1' => '부동산 PF',
	'2' => '주택담보',
	'3' => '매출채권',
	'4' => '동산'
);
$G_TYPE_KEY = array_keys($G_TYPE);

// 다른 페이지에서 사용하는 카테고리 구분기호
$G_TYPE2 = array(
	'A' => '',
	'1' => '2A',
	'2' => '2B',
	'3' => '3',
	'4' => '4'
);
$G_TYPE2_KEY = array_keys($G_TYPE2);

$g_type = ($g_type) ? $g_type : 'A';

$where = "";
$where.= " AND g_type = '".$g_type."'";

if($sdate && $edate) {
	if($sdate > $edate) {
		msg_go('대상일 범위가 정상적이지 않습니다.');
	}
	else {
		$where.= " AND tDate BETWEEN '$sdate' AND '$edate'";
	}
}
else {
	$sdate = date('Y-m').'-01';
	$edate = date('Y-m-d');

	if($sdate) $where.= " AND tDate >= '$sdate'";
	if($edate) $where.= " AND tDate <= '$edate'";
}

$sort = ($sort) ? $sort : 'DESC';


$sql = "
	SELECT
		*
	FROM
		cf_loan_repay_status
	WHERE 1
		$where
	ORDER BY
		tDate $sort";
$result = sql_query($sql);
$rcount = sql_num_rows($result);

//print_r($sql);

$PERIOD_SUM = array(
	'loan_cnt'  => 0,
	'loan_amt'  => 0,
	'principal' => 0,
	'interest'  => 0,
	'tax'       => 0,
	'fee'       => 0,
	'before_interest' => 0
);

for($i=0; $i<$rcount; $i++) {
	$LIST[$i] = sql_fetch_array($result);

	// 세금합계
	$LIST[$i]['tax']     = $LIST[$i]['interest_tax'] + $LIST[$i]['local_tax'];
	$LIST[$i]['tax_sum'] = $LIST[$i]['interest_tax_sum'] + $LIST[$i]['local_tax_sum'];

	// 세전이자
	$LIST[$i]['before_interest']     = $LIST[$i]['interest'] + $LIST[$i]['tax'] + $LIST[$i]['fee'];
	$LIST[$i]['before_interest_sum'] = $LIST[$i]['interest_sum'] + $LIST[$i]['tax_sum'] + $LIST[$i]['fee_sum'];

	$PERIOD_SUM['loan_cnt']  += $LIST[$i]['loan_cnt'];
	$PERIOD_SUM['loan_amt']  += $LIST[$i]['loan_amt'];
	$PERIOD_SUM['principal'] += $LIST[$i]['principal'];
	$PERIOD_SUM['before_interest'] += $LIST[$i]['before_interest'];
	$PERIOD_SUM['interest']  += $LIST[$i]['interest'];
	$PERIOD_SUM['tax']       += $LIST[$i]['tax'];
	$PERIOD_SUM['fee']       += $LIST[$i]['fee'];
}
sql_free_result($result);

$list_count = count($LIST);

$num = $list_count;


$to_month = date("Y-m");
$to_month_sdate = $to_month . "-01";
$to_month_edate = $to_month . "-" . date('d');

$next_month = date('Y-m', strtotime($sdate . ' first day of +1 month'));
$next_month_sdate = $next_month . "-01";
$next_month_edate = $next_month . "-" . date('t', strtotime($next_month_sdate));

$prev_month = date('Y-m', strtotime($sdate . ' first day of -1 month'));
$prev_month_sdate = $prev_month . "-01";
$prev_month_edate = $prev_month . "-" . date('t', strtotime($prev_month_sdate));

$prev_12month = date('Y-m', strtotime($sdate . ' first day of -1 year'));
$prev_12month_sdate = $prev_12month . "-01";
$prev_12month_edate = $prev_12month . "-" . date('t', strtotime($prev_12month_sdate));


if($display_mode=="excel") {
	$excel_file_name = "대출및상환통계(".$G_TYPE[$g_type]."_".preg_replace("/-/",".",$sdate)."-".preg_replace("/-/",".",$edate).")";

	header( "Content-type: application/vnd.ms-excel;" );
	header( "Content-Disposition: attachment; filename=$excel_file_name.xls" );
	header( "Content-description: PHP5 Generated Data" );
}
else {
	include_once (G5_ADMIN_PATH.'/admin.head.php');
}

//print_rr($_SERVER, 'font-size:12px');

?>

<? if($display_mode=='') { ?>
<style>
.table th.border_r { border-right:1px solid #999; }
.table td.border_r { border-right:1px solid #999; }
input::placeholder { text-align:center; }
</style>

<div class="tbl_head02 tbl_wrap">

	<form id="frmSearch" name= "frmSearch" method="get" class="form-horizontal">
	<ul class="col col-md-* list-inline" style="padding:0;margin-bottom:5px">
		<li><button type='button' class="btn btn-sm btn-default" style="width:60px;height:24px;padding:0;line-height:24px;" onClick="setDateRange('toMonth');">당월</button></li>
		<li style="padding-left:0px;"><button type='button' class="btn btn-sm btn-default" style="width:60px;height:24px;padding:0;line-height:24px;" onClick="setDateRange('nextMonth');">차월</button></li>
		<li style="padding-left:0px;"><button type='button' class="btn btn-sm btn-default" style="width:60px;height:24px;padding:0;line-height:24px;" onClick="setDateRange('prevMonth');">전월</button></li>
		<li style="padding-left:0px;"><button type='button' class="btn btn-sm btn-default" style="width:60px;height:24px;padding:0;line-height:24px;" onClick="setDateRange('prev12Month');">전년</button></li>
	</ul>
	<ul class="col col-md-* list-inline" style="padding:0;margin-bottom:5px">
		<li>
			<select id="g_type" name="g_type" class="form-control input-sm" style="width:150px" onChange="return document.frmSearch.submit();">
<?
for($i=0; $i<count($G_TYPE); $i++) {
	$selected = ($G_TYPE_KEY[$i]==$g_type) ? 'selected' : '';
	echo "<option value='".$G_TYPE_KEY[$i]."' {$selected}>".$G_TYPE[$G_TYPE_KEY[$i]]."</option>\n";
}
?>
			</select>
		</li>
		<li></li>
		<li>대상일</li>
		<li><input type="text" id="sdate" name="sdate" value="<?=$sdate?>" class="form-control input-sm datepicker" style="width:100px;text-align:center;"></li>
		<li>~</li>
		<li><input type="text" id="edate" name="edate" value="<?=$edate?>" class="form-control input-sm datepicker" style="width:100px;text-align:center;"></li>
		<li></li>
		<li>
			<select name="sort" class="form-control input-sm" style="width:150px" onChange="return document.frmSearch.submit();">
				<option value='ASC' <?=($sort=='ASC')?'selected':''?>>대상일 오름차순 ▲</option>
				<option value='DESC' <?=($sort=='DESC')?'selected':''?>>대상일 내림차순 ▼</option>
			</select>
		</li>
		<li><button type="submit" class="btn btn-sm btn-warning">검색</button></li>
		<li><button type="button" onclick="go_excel();" class="btn btn-sm btn-success" style="width:150px">검색결과 다운로드</button></li>
	</ul>
	</form>
<? } ?>

	<table id="table0" <?if($display_mode=='excel'){?>border="1"<?}else{?>class="table table-bordered table-hover"<?}?> style="padding-top:0; font-size:12px;">
		<thead>
			<tr>
				<th rowspan="2">대상일</th>
				<th rowspan="2">상품구분</th>
				<th colspan="2">대출실행건수</th>
				<th colspan="2">대출실행금액</th>
				<th colspan="2">원금상환금액</th>
				<th rowspan="2">대출잔액</th>
				<th colspan="2">세전이자</th>
				<th colspan="2">원천징수</th>
				<th colspan="2">플랫폼이용료</th>
				<th colspan="2">세후이자</th>
				<th colspan="3">연체</th>
			</tr>
			<tr>
				<!-- 대출실행건수 -->
				<th>일별</th>
				<th>누적</th>
				<!-- 대출실행금액 -->
				<th>일별</th>
				<th>누적</th>
				<!-- 원금상환금액 -->
				<th>일별</th>
				<th>누적</th>
				<!-- 지급이자(세전) -->
				<th>일별</th>
				<th>누적</th>
				<!-- 원천징수 -->
				<th>일별</th>
				<th>누적</th>
				<!-- 플랫폼이용료 -->
				<th>일별</th>
				<th>누적</th>
				<!-- 세후이자 -->
				<th>일별</th>
				<th>누적</th>
				<!-- 연체 -->
				<th>상품수</th>
				<th>금액</th>
				<th>연체율</th>
			</tr>
		</thead>
		<tbody>

<? if($list_count > 1) { ?>
			<tr>
				<td style="text-align:center;background:#EFEFEF;">발생합계</td>
				<td style="text-align:center;background:#EFEFEF;"></td>
				<td style="text-align:right;background:#EFEFEF;"><?=number_format($PERIOD_SUM['loan_cnt'])?></td>
				<td style="text-align:center;background:#EFEFEF;"></td>
				<td style="text-align:right;background:#EFEFEF;"><?=number_format($PERIOD_SUM['loan_amt'])?></td>
				<td style="text-align:center;background:#EFEFEF;"></td>
				<td style="text-align:right;background:#EFEFEF;"><?=number_format($PERIOD_SUM['principal'])?></td>
				<td style="text-align:center;background:#EFEFEF;"></td>
				<td style="text-align:center;background:#EFEFEF;"></td>
				<td style="text-align:right;background:#EFEFEF;"><?=number_format($PERIOD_SUM['before_interest'])?></td>
				<td style="text-align:center;background:#EFEFEF;"></td>
				<td style="text-align:right;background:#EFEFEF;"><?=number_format($PERIOD_SUM['tax'])?></td>
				<td style="text-align:center;background:#EFEFEF;"></td>
				<td style="text-align:right;background:#EFEFEF;"><?=number_format($PERIOD_SUM['fee'])?></td>
				<td style="text-align:center;background:#EFEFEF;"></td>
				<td style="text-align:right;background:#EFEFEF;"><?=number_format($PERIOD_SUM['interest'])?></td>
				<td style="text-align:center;background:#EFEFEF;"></td>
				<td style="text-align:right;background:#EFEFEF;"></td>
				<td style="text-align:center;background:#EFEFEF;"></td>
				<td style="text-align:center;background:#EFEFEF;"></td>
			</tr>
<? } ?>

<?
if($list_count) {
	for($i=0; $i<$list_count; $i++) {

		$style = ( date('w', strtotime($LIST[$i]['tDate']))=='0' ) ? 'background:#FFDDDD' : '';

		$tax     = $LIST[$i]['interest_tax'] + $LIST[$i]['local_tax'];
		$tax_sum = $LIST[$i]['interest_tax_sum'] + $LIST[$i]['local_tax_sum'];

		if(!$display_mode) {

			$LINKTAG['loan_cnt']     = "onClick=\"window.open('/adm/product/product_list.php?ST[]=1&ST[]=2&ST[]=5&ST[]=8&ST[]=9&ST[]=4&display=Y&date_field=A.loan_start_date&sdate={$LIST[$i]['tDate']}&edate={$LIST[$i]['tDate']}&category={$G_TYPE2[$g_type]}');\" style='cursor:pointer;'";
			$LINKTAG['loan_cnt_sum'] = "onClick=\"window.open('/adm/product/product_list.php?ST[]=1&ST[]=2&ST[]=5&ST[]=8&ST[]=9&ST[]=4&display=Y&date_field=A.loan_start_date&sdate=&edate={$LIST[$i]['tDate']}&category={$G_TYPE2[$g_type]}');\" style='cursor:pointer;'";

			$LINKTAG['repay_detail']       = "onClick=\"window.open('/adm/repayment/repay_log.php?date_field=A.banking_date&sdate={$LIST[$i]['tDate']}&edate={$LIST[$i]['tDate']}&category={$G_TYPE2[$g_type]}');\" style='cursor:pointer;'";
			$LINKTAG['repay_detail_nujuk'] = "onClick=\"window.open('/adm/repayment/repay_log.php?date_field=A.banking_date&sdate=&edate={$LIST[$i]['tDate']}&category={$G_TYPE2[$g_type]}');\" style='cursor:pointer;'";


			$style = ( date('w', strtotime($LIST[$i]['tDate']))=='0' ) ? 'background:#FFDDDD' : '';

			$FCOLOR['loan_cnt']      = ($LIST[$i]['loan_cnt'] > 0) ? '' : '#BBB';
			$FCOLOR['loan_cnt_sum']  = ($LIST[$i]['loan_cnt_sum'] > 0) ? '' : '#BBB';
			$FCOLOR['loan_amt']      = ($LIST[$i]['loan_amt'] > 0) ? '' : '#BBB';
			$FCOLOR['loan_amt_sum']  = ($LIST[$i]['loan_amt_sum'] > 0) ? '' : '#BBB';
			$FCOLOR['principal']     = ($LIST[$i]['principal'] > 0) ? '' : '#BBB';
			$FCOLOR['principal_sum'] = ($LIST[$i]['principal_sum'] > 0) ? '' : '#BBB';
			$FCOLOR['remain_amt']    = ($LIST[$i]['remain_amt'] > 0) ? '' : '#BBB';
			$FCOLOR['before_interest']= ($LIST[$i]['before_interest'] > 0) ? '' : '#BBB';
			$FCOLOR['before_interest_sum'] = ($LIST[$i]['before_interest_sum'] > 0) ? '' : '#BBB';
			$FCOLOR['interest']      = ($LIST[$i]['before_interest'] > 0) ? '' : '#BBB';
			$FCOLOR['interest_sum']  = ($LIST[$i]['interest_sum'] > 0) ? '' : '#BBB';
			$FCOLOR['tax']           = ($LIST[$i]['tax'] > 0) ? '' : '#BBB';
			$FCOLOR['tax_sum']       = ($LIST[$i]['tax_sum'] > 0) ? '' : '#BBB';
			$FCOLOR['fee']           = ($LIST[$i]['fee'] > 0) ? '' : '#BBB';
			$FCOLOR['fee_sum']       = ($LIST[$i]['fee_sum'] > 0) ? '' : '#BBB';
		}

		$FCOLOR['overdue_count'] = ($LIST[$i]['overdue_count'] > 0) ? '' : '#BBB';
		$FCOLOR['overdue_principal'] = ($LIST[$i]['overdue_principal'] > 0) ? '' : '#BBB';
		$FCOLOR['overdue_perc'] = ($LIST[$i]['overdue_perc'] > 0) ? '' : '#BBB';

?>
			<tr>
				<td style="text-align:center;<?=$style?>"><?=$LIST[$i]['tDate']?></td>
				<td style="text-align:center;<?=$style?>"><?=$G_TYPE[$LIST[$i]['g_type']]?></td>
				<td style="text-align:right;color:<?=$FCOLOR['loan_cnt']?>;<?=$style?>"><span <?=$LINKTAG['loan_cnt']?>><?=number_format($LIST[$i]['loan_cnt'])?></span></td>
				<td style="text-align:right;color:<?=$FCOLOR['loan_cnt_sum']?>;<?=$style?>"><span <?=$LINKTAG['loan_cnt_sum']?>><?=number_format($LIST[$i]['loan_cnt_sum'])?></span></td>
				<td style="text-align:right;color:<?=$FCOLOR['loan_amt']?>;<?=$style?>"><span <?=$LINKTAG['loan_cnt']?>><?=number_format($LIST[$i]['loan_amt'])?></span></td>
				<td style="text-align:right;color:<?=$FCOLOR['loan_amt_sum']?>;<?=$style?>"><span <?=$LINKTAG['loan_cnt_sum']?>><?=number_format($LIST[$i]['loan_amt_sum'])?></span></td>
				<td style="text-align:right;color:<?=$FCOLOR['principal']?>;<?=$style?>"><span <?=$LINKTAG['repay_detail']?>><?=number_format($LIST[$i]['principal'])?></span></td>
				<td style="text-align:right;color:<?=$FCOLOR['principal_sum']?>;<?=$style?>"><span <?=$LINKTAG['repay_detail_nujuk']?>><?=number_format($LIST[$i]['principal_sum'])?></span></td>
				<td style="text-align:right;color:<?=$FCOLOR['remain_amt']?>;<?=$style?>"><span <?=$LINKTAG['loan_cnt_sum']?>><?=number_format($LIST[$i]['remain_amt'])?></span></td>
				<td style="text-align:right;color:<?=$FCOLOR['before_interest']?>;<?=$style?>"><span <?=$LINKTAG['repay_detail']?>><?=number_format($LIST[$i]['before_interest'])?></span></td>
				<td style="text-align:right;color:<?=$FCOLOR['before_interest_sum']?>;<?=$style?>"><span <?=$LINKTAG['repay_detail_nujuk']?>><?=number_format($LIST[$i]['before_interest_sum'])?></span></td>
				<td style="text-align:right;color:<?=$FCOLOR['tax']?>;<?=$style?>"><span <?=$LINKTAG['repay_detail']?>><?=number_format($LIST[$i]['tax'])?></span></td>
				<td style="text-align:right;color:<?=$FCOLOR['tax_sum']?>;<?=$style?>"><span <?=$LINKTAG['repay_detail_nujuk']?>><?=number_format($LIST[$i]['tax_sum'])?></span></td>
				<td style="text-align:right;color:<?=$FCOLOR['fee']?>;<?=$style?>"><span <?=$LINKTAG['repay_detail']?>><?=number_format($LIST[$i]['fee'])?></span></td>
				<td style="text-align:right;color:<?=$FCOLOR['fee_sum']?>;<?=$style?>"><span <?=$LINKTAG['repay_detail_nujuk']?>><?=number_format($LIST[$i]['fee_sum'])?></span></td>
				<td style="text-align:right;color:<?=$FCOLOR['interest']?>;<?=$style?>"><span <?=$LINKTAG['repay_detail']?>><?=number_format($LIST[$i]['interest'])?></span></td>
				<td style="text-align:right;color:<?=$FCOLOR['interest_sum']?>;<?=$style?>"><span <?=$LINKTAG['repay_detail_nujuk']?>><?=number_format($LIST[$i]['interest_sum'])?></span></td>
				<td style="text-align:right;color:<?=$FCOLOR['overdue_count']?>;<?=$style?>"><?=number_format($LIST[$i]['overdue_count'])?></td>
				<td style="text-align:right;color:<?=$FCOLOR['overdue_principal']?>;<?=$style?>"><?=number_format($LIST[$i]['overdue_principal'])?></td>
				<td style="text-align:right;color:<?=$FCOLOR['overdue_perc']?>;<?=$style?>"><?=$LIST[$i]['overdue_perc']?>%</td>
			</tr>
<?
	}
}
else {
	echo "<tr align='center'><td colspan='20'>데이터가 없습니다.</td></tr>";
}
?>

		</tbody>
	</table>

<?
if($display_mode=='') {
?>
	<br/><br/>

</div><!-- .tbl_head02 .tbl_wrap -->

<script>
setDateRange = function(target) {
	if(target=='prevMonth') {
		$('#sdate').val('<?=$prev_month_sdate?>');
		$('#edate').val('<?=$prev_month_edate?>');
	}
	else if(target=='prev12Month') {
		$('#sdate').val('<?=$prev_12month_sdate?>');
		$('#edate').val('<?=$prev_12month_edate?>');
	}
	else if(target=='nextMonth') {
		$('#sdate').val('<?=$next_month_sdate?>');
		$('#edate').val('<?=$next_month_edate?>');
	}
	else {
		$('#sdate').val('<?=$to_month_sdate?>');
		$('#edate').val('<?=$to_month_edate?>');
	}
	$('#frmSearch').submit();
}
</script>

<script>
$('#year').on('change', function() {
	$(location).attr('href','?year=' + $('#year').val());
});

function go_excel() {
	if( confirm('다운로드 하시겠습니까?') ) {
		url = '<?=$_SERVER['PHP_SELF']?>';
		url+= '?g_type=' + $('#g_type').val();
		url+= '&sdate=' + $('#sdate').val();
		url+= '&edate=' + $('#edate').val();
		url+= '&display_mode=excel';
		$(location).attr('href', url);
	}
}
</script>

<? if( !preg_match("/Trident\/7\.0/i", $_SERVER['HTTP_USER_AGENT']) ) { ?>
<script>
$(document).ready(function() {
	$('#table0').floatThead();
});
</script>
<? } ?>


<?

include_once ('../admin.tail.php');

}

?>