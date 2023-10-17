<?
###############################################################################
## 보고용 통계자료
##	추출대상 : 개인일반투자자
##		월별 : 누적투자건수 | 누적투자금액  | 신규투자건수 | 신규투자금액 | 신규투자비율 | 신규투자금액비율
###############################################################################

$sub_menu = "";
include_once('./_common.php');

$g5['title'] = '보고용 통계자료';
include_once('../admin.head.php');


while(list($k,$v)=each($_REQUEST)) { ${$k}=trim($v); }

$nowdate = date("Y-m");

$dateY = (!$dateY) ? date('Y') : $dateY;
$dateYM = $dateY . "-01";

$MLISTSUM = array(
	'nujuk_invest_count'  => 0,
	'nujuk_invest_amount' => 0,
	'now_invest_count'    => 0,
	'now_invest_amount'   => 0,
	'first_invest_count'  => 0,
	'first_invest_amount' => 0,
	'first_count_perc'    => 0,
	'first_amount_perc'   => 0
);

for($i=0,$j=1; $i<12; $i++,$j++) {

	if($i > 0) {
		$dateYM = date("Y-m", strtotime("first day of" . $dateYM . " +1 month"));
	}

	$MLIST[$i] = array(
		'date'                => $dateYM,
		'nujuk_invest_count'  => 0,
		'nujuk_invest_amount' => 0,
		'now_invest_count'    => 0,
		'now_invest_amount'   => 0,
		'first_invest_count'  => 0,
		'first_invest_amount' => 0,
		'first_count_perc'    => 0,
		'first_amount_perc'   => 0
	);

	$sql = "
		SELECT
			A.member_idx,
			COUNT(A.idx) AS now_invest_count,
			SUM(A.amount) AS now_invest_amount,
			( SELECT COUNT(idx) FROM cf_product_invest WHERE member_idx=A.member_idx AND LEFT(insert_date,7) < '$dateYM' AND invest_state = 'Y' ) AS prev_invest_count,
			( SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE member_idx=A.member_idx AND LEFT(insert_date,7) < '$dateYM' AND invest_state = 'Y' ) AS prev_invest_amount,
			( SELECT amount FROM cf_product_invest WHERE member_idx=A.member_idx AND LEFT(insert_date,7) = '$dateYM' AND invest_state = 'Y' ORDER BY insert_datetime ASC LIMIT 1 ) AS first_invest_amount
		FROM
			cf_product_invest A
		LEFT JOIN
			g5_member B  ON A.member_idx=B.mb_no
		WHERE (1)
			AND LEFT(A.insert_date,7)='$dateYM'
			AND A.invest_state='Y'
			AND B.member_type='1' AND B.member_investor_type='1'
		GROUP BY
			A.member_idx
		ORDER BY
			prev_invest_count ASC,
			now_invest_count ASC";
	//debug_flush(print_rr($sql, 'font-size:12px'));
	$res = sql_query($sql);

	while( $R = sql_fetch_array($res) ) {

		$MLIST[$i]['nujuk_invest_count']  += ($R['prev_invest_count'] + $R['now_invest_count']);
		$MLIST[$i]['nujuk_invest_amount'] += ($R['prev_invest_amount'] + $R['now_invest_amount']);

		$MLIST[$i]['now_invest_count']  += $R['now_invest_count'];
		$MLIST[$i]['now_invest_amount'] += $R['now_invest_amount'];

		$first_invest_count = $first_invest_amount = 0;
		if($R['prev_invest_count']==0 && $R['now_invest_amount'] > 0) {
			$first_invest_count  = 1;
			$first_invest_amount = $R['first_invest_amount'];
		}

		$MLIST[$i]['first_invest_count']  += $first_invest_count;
		$MLIST[$i]['first_invest_amount'] += $first_invest_amount;

		$MLIST[$i]['first_count_perc']  = sprintf("%.2f", ($MLIST[$i]['first_invest_count'] / $MLIST[$i]['now_invest_count']) * 100 ) . "%";
		$MLIST[$i]['first_amount_perc'] = sprintf("%.2f", ($MLIST[$i]['first_invest_amount'] / $MLIST[$i]['now_invest_amount']) * 100 ) . "%";



		$MLISTSUM['nujuk_invest_count']  = $MLIST[$i]['nujuk_invest_count'];
		$MLISTSUM['nujuk_invest_amount'] = $MLIST[$i]['nujuk_invest_amount'];

		$MLISTSUM['first_invest_count']  += $first_invest_count;
		$MLISTSUM['first_invest_amount'] += $first_invest_amount;

		$MLISTSUM['now_invest_count']    += $R['now_invest_count'];
		$MLISTSUM['now_invest_amount']   += $R['now_invest_amount'];

	}

	$MLISTSUM['first_count_perc']  = @sprintf("%.2f", ($MLISTSUM['first_invest_count'] / $MLISTSUM['now_invest_count']) * 100 ) . "%";
	$MLISTSUM['first_amount_perc'] = @sprintf("%.2f", ($MLISTSUM['first_invest_amount'] / $MLISTSUM['now_invest_amount']) * 100 ) . "%";

	if($dateYM >= $nowdate) break;

}

//print_rr($MLISTSUM, 'font-size:12px');
//print_rr($MLIST, 'font-size:12px');

?>

1. 월별 개인일반투자자 리포트<br/><br/>
대상년도: <select id='dateY'>
<?
	for($i=2016; $i<=date('Y'); $i++) {
		$selected = ($i==$dateY) ? 'selected' : '';
		echo "	<option value='$i' $selected>$i</option>\n";
	}
?>
</select>
<table style="width:98%" class="table table-striped table-bordered table-hover">
	<tr>
		<td style='width:%;background:#F8F8EF;text-align:center;'>Date</td>
		<td style='width:11%;background:#F8F8EF;text-align:center;'>월간투자수</td>
		<td style='width:11%;background:#F8F8EF;text-align:center;'>월간투자금액</td>
		<td style='width:11%;background:#F8F8EF;text-align:center;'>누적투자수</td>
		<td style='width:11%;background:#F8F8EF;text-align:center;'>누적투자금액</td>
		<td style='width:11%;background:#F8F8EF;text-align:center;'>최초투자수</td>
		<td style='width:11%;background:#F8F8EF;text-align:center;'>최초투자액</td>
		<td style='width:11%;background:#F8F8EF;text-align:center;'>신규투자건수비율</td>
		<td style='width:11%;background:#F8F8EF;text-align:center;'>신규투자금액비율</td>
	</tr>

	<tr>
		<td style='background:#FFDDDD;text-align:center;'>합계</td>
		<td style='background:#FFDDDD;text-align:right;'><?=number_format($MLISTSUM['now_invest_count'])?></td>
		<td style='background:#FFDDDD;text-align:right;'><?=number_format($MLISTSUM['now_invest_amount'])?></td>
		<td style='background:#FFDDDD;text-align:right;'><?=number_format($MLISTSUM['nujuk_invest_count'])?></td>
		<td style='background:#FFDDDD;text-align:right;'><?=number_format($MLISTSUM['nujuk_invest_amount'])?></td>
		<td style='background:#FFDDDD;text-align:right;'><?=number_format($MLISTSUM['first_invest_count'])?></td>
		<td style='background:#FFDDDD;text-align:right;'><?=number_format($MLISTSUM['first_invest_amount'])?></td>
		<td style='background:#FFDDDD;text-align:right;'><?=$MLISTSUM['first_count_perc']?></td>
		<td style='background:#FFDDDD;text-align:right;'><?=$MLISTSUM['first_amount_perc']?></td>
	</tr>

<?
for($i=0,$j=1; $i<count($MLIST); $i++,$j++) {
?>
	<tr>
		<td style='text-align:center;'><?=$MLIST[$i]['date']?></td>
		<td style='text-align:right;'><?=number_format($MLIST[$i]['now_invest_count'])?></td>
		<td style='text-align:right;'><?=number_format($MLIST[$i]['now_invest_amount'])?></td>
		<td style='text-align:right;'><?=number_format($MLIST[$i]['nujuk_invest_count'])?></td>
		<td style='text-align:right;'><?=number_format($MLIST[$i]['nujuk_invest_amount'])?></td>
		<td style='text-align:right;'><?=number_format($MLIST[$i]['first_invest_count'])?></td>
		<td style='text-align:right;'><?=number_format($MLIST[$i]['first_invest_amount'])?></td>
		<td style='text-align:right;'><?=$MLIST[$i]['first_count_perc']?></td>
		<td style='text-align:right;'><?=$MLIST[$i]['first_amount_perc']?></td>
	</tr>
<?
}
?>
</table>

<script>
$('#dateY').on('change', function() {
	$(location).attr('href', '?dateY='+$('#dateY').val());
});
</script>

<?

include_once ('../admin.tail.php');

?>