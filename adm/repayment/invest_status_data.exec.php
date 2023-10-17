#!/usr/local/php/bin/php -q
<?
// invest_status_data.ajax.php 에서 호출됨

set_time_limit(300);

$base_path  = "/home/crowdfund/public_html";
include_once($base_path . '/common.cli.php');
include_once($base_path . '/lib/repay_calculation_new.php');


$prd_idx = @$_SERVER['argv']['1'];
if($prd_idx=='') exit;

$PSTATE = repayCalculationNew($prd_idx);

$TOTAL_REPAY['repay_principal'] = $PSTATE['TOTAL_REPAY_SUM']['repay_principal'];
$TOTAL_REPAY['repay_amount']    = $PSTATE['TOTAL_REPAY_SUM']['repay_principal'] + $PSTATE['TOTAL_REPAY_SUM']['invest_interest'] - $PSTATE['TOTAL_REPAY_SUM']['invest_usefee'] - $PSTATE['TOTAL_REPAY_SUM']['TAX']['sum'];
$TOTAL_REPAY['interest']        = $PSTATE['TOTAL_REPAY_SUM']['invest_interest'];
$TOTAL_REPAY['fee']             = $PSTATE['TOTAL_REPAY_SUM']['invest_usefee'];
$TOTAL_REPAY['tax']             = $PSTATE['TOTAL_REPAY_SUM']['TAX']['sum'];

$NUJUK_PAIED['repay_principal'] = $PSTATE['TOTAL_PAIED_SUM']['repay_principal'];
$NUJUK_PAIED['repay_amount']    = $PSTATE['TOTAL_PAIED_SUM']['repay_principal'] + $PSTATE['TOTAL_PAIED_SUM']['invest_interest'] - $PSTATE['TOTAL_PAIED_SUM']['invest_usefee'] - $PSTATE['TOTAL_PAIED_SUM']['TAX']['sum'];
$NUJUK_PAIED['interest']        = $PSTATE['TOTAL_PAIED_SUM']['invest_interest'];
$NUJUK_PAIED['fee']             = $PSTATE['TOTAL_PAIED_SUM']['invest_usefee'];
$NUJUK_PAIED['tax']             = $PSTATE['TOTAL_PAIED_SUM']['TAX']['sum'];
$NUJUK_PAIED['last_turn']       = $PSTATE['PAIED_SUM']['last_turn'];

if($PSTATE['INI']['repay_turn'] > $NUJUK_PAIED['last_turn']) {
	$NEXT_REPAY['turn'] = $NUJUK_PAIED['last_turn'] + 1;
	$i = max(0, $NUJUK_PAIED['last_turn']);
	$NEXT_REPAY['repay_principal'] = $PSTATE['REPAY'][$i]['SUM']['repay_principal'];

	$NEXT_REPAY['repay_amount']    = ($PSTATE['REPAY'][$i]['SUM']['repay_principal'] + $PSTATE['REPAY'][$i]['SUM']['invest_interest'] - $PSTATE['REPAY'][$i]['SUM']['invest_usefee'] - $PSTATE['REPAY'][$i]['SUM']['TAX']['sum'])
		                             + ($PSTATE['REPAY'][$i]['OVERDUE']['SUM']['invest_interest'] - $PSTATE['REPAY'][$i]['OVERDUE']['SUM']['invest_usefee'] - $PSTATE['REPAY'][$i]['OVERDUE']['SUM']['TAX']['sum']);

	$NEXT_REPAY['interest']        = $PSTATE['REPAY'][$i]['SUM']['invest_interest'] + $PSTATE['REPAY'][$i]['OVERDUE']['SUM']['invest_interest'];
	$NEXT_REPAY['fee']             = $PSTATE['REPAY'][$i]['SUM']['invest_usefee'] + $PSTATE['REPAY'][$i]['OVERDUE']['SUM']['invest_usefee'];
	$NEXT_REPAY['tax']             = $PSTATE['REPAY'][$i]['SUM']['TAX']['sum'] + $PSTATE['REPAY'][$i]['OVERDUE']['SUM']['TAX']['sum'];
}


$interest_paid_perc  = floatRtrim(@sprintf("%.2f", (($PSTATE['TOTAL_PAIED_SUM']['invest_interest'] / $PSTATE['TOTAL_REPAY_SUM']['invest_interest']) * 100))) . "%";
$principal_paid_perc = floatRtrim(@sprintf("%.2f", (($PSTATE['TOTAL_PAIED_SUM']['repay_principal'] / $PSTATE['TOTAL_REPAY_SUM']['repay_principal']) * 100))) . "%";

$RETURN_ARR = array(
	'result'        => 'SUCCESS',

	'total_turn'           => (string)max(0, $PSTATE['INI']['repay_turn']),
	'last_paid_turn'       => (string)max(0, $NUJUK_PAIED['last_turn']),
	'next_turn'            => (string)max(0, $NEXT_REPAY['turn']),

	'principal'            => (string)max(0, $TOTAL_REPAY['repay_principal']),
	'nujuk_paid_principal' => (string)max(0, $NUJUK_PAIED['repay_principal']),
	'next_principal'       => (string)max(0, $NEXT_REPAY['repay_principal']),

	'interest'             => (string)max(0, $TOTAL_REPAY['interest']),
	'nujuk_paid_interest'  => (string)max(0, $NUJUK_PAIED['interest']),
	'next_interest'        => (string)max(0, $NEXT_REPAY['interest']),

	'fee'                  => (string)max(0, $TOTAL_REPAY['fee']),
	'nujuk_paid_fee'       => (string)max(0, $NUJUK_PAIED['fee']),
	'next_fee'             => (string)max(0, $NEXT_REPAY['fee']),

	'tax'                  => (string)max(0, $TOTAL_REPAY['tax']),
	'nujuk_paid_tax'       => (string)max(0, $NUJUK_PAIED['tax']),
	'next_tax'             => (string)max(0, $NEXT_REPAY['tax']),

	'repay_amount'         => (string)max(0, $TOTAL_REPAY['repay_amount']),
	'nujuk_paid_amount'    => (string)max(0, $NUJUK_PAIED['repay_amount']),
	'next_repay_amount'    => (string)max(0, $NEXT_REPAY['repay_amount']),

	'interest_paid_perc'   => $interest_paid_perc,
	'principal_paid_perc'  => $principal_paid_perc
);

echo json_encode($RETURN_ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);

unset($PSTATE);
unset($TOTAL_REPAY);
unset($NUJUK_PAIED);
unset($NEXT_REPAY);

sql_close();
exit;

?>
