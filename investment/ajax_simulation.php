<?
/**
 * 투자시뮬레이션 시작
 */
include_once('./_common.php');

if($_SERVER["REQUEST_METHOD"]!="POST") { echo "ERROR"; exit; }

$prd_idx = $_REQUEST['prd_idx'];
$ajax_principal_value = $_REQUEST['ajax_principal_value'];
$onlyInterestYN = ($_REQUEST['onlyInterest'] == 'Y') ? true : false;

//echo $prd_idx."<br/>";
//echo $ajax_principal_value."<br/>";
//echo $onlyInterestYN."<br/>";

$sql = "SELECT idx, invest_return, invest_period, invest_days FROM cf_product WHERE idx='".$prd_idx."' ";
$DATA = sql_fetch($sql);

if(!$DATA) { echo "ERROR"; exit; }
if(!$DATA["invest_return"]) { echo "ERROR"; exit; }		// 투자수익율 체크
if(!$DATA["invest_period"] || $DATA["invest_period"] < 1) { echo "ERROR"; exit; }		// 투자기간 체크
if($ajax_principal_value < $CONF['min_invest_limit']) { echo "ERROR-MIN-PRICE"; exit; }


// 상품내역 및 정산내역 호출
$PSTATE = investStatement($prd_idx, $ajax_principal_value);
//echo "<pre style='font-size:9pt;'>";print_r($PSTATE['INI']);echo "</pre>";

//2018-02-19 투자기간 표기 변경
if($DATA['invest_period']==1 && $DATA['invest_days'] > 0) {
	$invest_period = $DATA['invest_days'];
	$invest_period_unit = '일';
}
else {
	$invest_period = $DATA['invest_period'];
	$invest_period_unit = "개월";
}

// 회차별 평균 이자
$monthAvrPrice = $PSTATE['REPAYSUM']['interest'] / $DATA['invest_period'];

// 은행금리 적용이자
$bankInterestPrice = (($ajax_principal_value * 0.017 / 365) * 0.725) * $PSTATE['INI']['total_day_count'];

// 은행금리 적용이자 대비
$diffEarning = @($PSTATE['REPAYSUM']['interest'] / (int)$bankInterestPrice);

if($onlyInterestYN) {
	$ARR['success']            = 1;
	$ARR['totalInterestPrice'] = number_format($PSTATE['REPAYSUM']['interest']);
	$ARR['monthAvrPrice']      = number_format($monthAvrPrice);
	$ARR['investMonth']        = $DATA['invest_period'];
	$ARR['bankInterestPrice']  = number_format($bankInterestPrice);
	$ARR['diffEarning']        = sprintf("%.1f", $diffEarning);
}


if($onlyInterestYN) {
	echo json_encode($ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
}
else {
	if(G5_IS_MOBILE) {
		include_once("ajax_simulation_m.php");
		return;
	}
}

// $PSTATE['PRDT']['invest_usefee'] -> 플랫폼이용료
// $PSTATE['PRDT']['withhold_tax_rate'] -> 세율

if(!$onlyInterestYN) {
?>

	<h3><?=$PSTATE['PRDT']["title"];?></h3>
	<div style="height:16px;margin-bottom:4px;font-size:13px;line-height:16px;text-align:right;padding-right:8px;">
		● 수익산정기간 : <span style="color:#222"><?=preg_replace("/-/", ".", $PSTATE['INI']['loan_start_date']); ?> ~ <?=preg_replace("/-/", ".", $PSTATE['INI']['loan_end_date']); ?> (<?=number_format($PSTATE['INI']['total_day_count']); ?>일)</span>
	</div>
	<div class="type04 mb30">
		<table>
			<colgroup>
				<col style="width:33.4%">
				<col style="width:33.3%">
				<col style="width:33.3%">
			</colgroup>
			<tbody>
				<tr height="59">
					<td valign="top">투자원금
						<span class="num"><?=number_format($PSTATE['INI']['principal']);?><span class="f08">원</span></span></td>
					<td valign="top">수익률/투자기간
						<span class="num">
						<span style="font-size:13px">(연)</span><?=$DATA['invest_return'];?>
						<span style="font-size:13px">%</span> / <?=$invest_period?>
						<span style="font-size:13px"><?=$invest_period_unit?></span></span>
					</td>
					<td valign="top">수익
						<span style="font-size:13px">(세전)</span>
						<span class="num"><?=number_format($PSTATE['REPAYSUM']['invest_interest']);?>
						<span style="font-size:13px">원</span></span>
					</td>
				</tr>
				<tr height="59">
					<td valign="top">플랫폼이용료
						<span class="num"><?=number_format($PSTATE['REPAYSUM']['invest_usefee']);?>
						<span style="font-size:13px">원</span></span></span></td>
					<td valign="top">세금
						<span class="num"><?=number_format($PSTATE['REPAYSUM']['withhold']);?>
						<span style="font-size:13px">원</span></span>
					</td>
					<td valign="top">총수익<span style="font-size:13px">(세후)</span>
						<span class="num"><?=number_format($PSTATE['INI']['principal'] + $PSTATE['REPAYSUM']['interest']);?>
						<span style="font-size:13px">원</span></span>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="type03 profit mb40">
		<table>
			<tbody>
			<tr>
				<th>지급일자 (차수)</th>
				<th>원금</th>
				<th>투자일수</th>
				<th>수익</th>
				<th>플랫폼이용료</th>
				<th>세금</th>
				<th>실입금액</th>
			</tr>
			<? for($i=0,$j=1; $i<count($PSTATE['REPAY']);$i++,$j++) { ?>
				<tr>
					<td><?=$PSTATE['REPAY'][$i]['repay_day'] ?> (<?= $j ?>차)</td>
					<td style="text-align:right"><?=number_format($PSTATE['REPAY'][$i]['principal']);?>원</td>
					<td style="text-align:right"><?=number_format($PSTATE['REPAY'][$i]['day_count']);?>일</td>
					<td style="text-align:right"><?=number_format($PSTATE['REPAY'][$i]['invest_interest']);?>원</td>
					<td style="text-align:right"><?=number_format($PSTATE['REPAY'][$i]['invest_usefee']);?>원</td>
					<td style="text-align:right"><?=number_format($PSTATE['REPAY'][$i]['withhold']);?>원</td>
					<td style="text-align:right"><?=number_format($PSTATE['REPAY'][$i]['send_price']);?>원</td>
				</tr>
			<? } ?>
			<tfoot>
			<tr>
				<td>합계</td>
				<td style="text-align:right"><?=number_format($PSTATE['REPAYSUM']['principal']);?>원</td>
				<td style="text-align:right"><?=number_format($PSTATE['REPAYSUM']['day_count']);?>일</td>
				<td style="text-align:right"><?=number_format($PSTATE['REPAYSUM']['invest_interest']);?>원</td>
				<td style="text-align:right"><?=number_format($PSTATE['REPAYSUM']['invest_usefee']);?>원</td>
				<td style="text-align:right"><?=number_format($PSTATE['REPAYSUM']['withhold']);?>원</td>
				<td style="text-align:right"><?=number_format($PSTATE['REPAYSUM']['send_price']);?>원</td>
			</tr>
			</tfoot>
			</tbody>
		</table>
	</div>
<?
}


unset($PSTATE);
exit;

?>