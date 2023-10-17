#!/usr/local/php/bin/php -q
<?
###############################################################################
## 과지급건에 대한 환수처리 (함부로 실행하지 말것)
###############################################################################

exit;

set_time_limit(0);

$path = '/home/crowdfund/public_html';
include_once($path . '/common.php');
include_once($path . '/lib/insidebank.lib.php');


$action = ($_SERVER['argv']['1']) ? $_SERVER['argv']['1'] : 'debug';
$now = date('YmdHi');

$ACCT = array(
	'bank_code'      => '088',
	'bank_acct_no'   => '100032239810',
	'bank_acct_name' => '(주)헬로크라우드대부 (대출금상환계좌)'
);


$tran_remitee_nm = "헬로핀테크" ;
$memo = "과지급금환수";

$LIST = array(
	array('member_idx'=>'243', 'amount'=>'166'),
	array('member_idx'=>'287', 'amount'=>'166'),
	array('member_idx'=>'1261', 'amount'=>'166'),
	array('member_idx'=>'1822', 'amount'=>'166'),
	array('member_idx'=>'2306', 'amount'=>'166'),
	array('member_idx'=>'2978', 'amount'=>'166'),
	array('member_idx'=>'3190', 'amount'=>'166'),
	array('member_idx'=>'3677', 'amount'=>'166'),
	array('member_idx'=>'4090', 'amount'=>'166'),
	array('member_idx'=>'4101', 'amount'=>'166'),
	array('member_idx'=>'4318', 'amount'=>'166'),
	array('member_idx'=>'4386', 'amount'=>'166'),
	array('member_idx'=>'4435', 'amount'=>'166'),
	array('member_idx'=>'4444', 'amount'=>'166'),
	array('member_idx'=>'4507', 'amount'=>'166'),
	array('member_idx'=>'4350', 'amount'=>'226'),
	array('member_idx'=>'1108', 'amount'=>'314'),
	array('member_idx'=>'1213', 'amount'=>'314'),
	array('member_idx'=>'1652', 'amount'=>'314'),
	array('member_idx'=>'3518', 'amount'=>'314'),
	array('member_idx'=>'3853', 'amount'=>'314'),
	array('member_idx'=>'3874', 'amount'=>'314'),
	array('member_idx'=>'4035', 'amount'=>'314'),
	array('member_idx'=>'4047', 'amount'=>'314'),
	array('member_idx'=>'4224', 'amount'=>'314'),
	array('member_idx'=>'4463', 'amount'=>'314'),
	array('member_idx'=>'2381', 'amount'=>'491'),
	array('member_idx'=>'2548', 'amount'=>'491'),
	array('member_idx'=>'3396', 'amount'=>'491'),
	array('member_idx'=>'3641', 'amount'=>'491'),
	array('member_idx'=>'4481', 'amount'=>'491'),
	array('member_idx'=>'895', 'amount'=>'794'),
	array('member_idx'=>'2584', 'amount'=>'794'),
	array('member_idx'=>'2586', 'amount'=>'794'),
	array('member_idx'=>'2608', 'amount'=>'794'),
	array('member_idx'=>'3660', 'amount'=>'794'),
	array('member_idx'=>'3919', 'amount'=>'794'),
	array('member_idx'=>'4153', 'amount'=>'794'),
	array('member_idx'=>'4465', 'amount'=>'794'),
	array('member_idx'=>'4511', 'amount'=>'794'),
	array('member_idx'=>'798', 'amount'=>'1589'),
	array('member_idx'=>'909', 'amount'=>'1589'),
	array('member_idx'=>'1372', 'amount'=>'1589'),
	array('member_idx'=>'1396', 'amount'=>'1589'),
	array('member_idx'=>'1578', 'amount'=>'1589'),
	array('member_idx'=>'1961', 'amount'=>'1589'),
	array('member_idx'=>'2289', 'amount'=>'1589'),
	array('member_idx'=>'2492', 'amount'=>'1589'),
	array('member_idx'=>'3085', 'amount'=>'1589'),
	array('member_idx'=>'3167', 'amount'=>'1589'),
	array('member_idx'=>'3184', 'amount'=>'1589'),
	array('member_idx'=>'3269', 'amount'=>'1589'),
	array('member_idx'=>'3316', 'amount'=>'1589'),
	array('member_idx'=>'3517', 'amount'=>'1589'),
	array('member_idx'=>'3725', 'amount'=>'1589'),
	array('member_idx'=>'3899', 'amount'=>'1589'),
	array('member_idx'=>'3954', 'amount'=>'1589'),
	array('member_idx'=>'4083', 'amount'=>'1589'),
	array('member_idx'=>'4094', 'amount'=>'1589'),
	array('member_idx'=>'4104', 'amount'=>'1589'),
	array('member_idx'=>'4110', 'amount'=>'1589'),
	array('member_idx'=>'4150', 'amount'=>'1589'),
	array('member_idx'=>'4192', 'amount'=>'1589'),
	array('member_idx'=>'4437', 'amount'=>'1589'),
	array('member_idx'=>'4487', 'amount'=>'1589'),
	array('member_idx'=>'4494', 'amount'=>'1589'),
	array('member_idx'=>'4522', 'amount'=>'1589'),
	array('member_idx'=>'2637', 'amount'=>'1746'),
	array('member_idx'=>'2251', 'amount'=>'2394'),
	array('member_idx'=>'3207', 'amount'=>'2394'),
	array('member_idx'=>'3743', 'amount'=>'2706'),
	array('member_idx'=>'215', 'amount'=>'3177'),
	array('member_idx'=>'312', 'amount'=>'3177'),
	array('member_idx'=>'2383', 'amount'=>'3177'),
	array('member_idx'=>'2484', 'amount'=>'3177'),
	array('member_idx'=>'2569', 'amount'=>'3177'),
	array('member_idx'=>'3198', 'amount'=>'3177'),
	array('member_idx'=>'3443', 'amount'=>'3177'),
	array('member_idx'=>'3524', 'amount'=>'3177'),
	array('member_idx'=>'3639', 'amount'=>'3177'),
	array('member_idx'=>'4123', 'amount'=>'3177'),
	array('member_idx'=>'4314', 'amount'=>'3177'),
	array('member_idx'=>'4447', 'amount'=>'3177'),
	array('member_idx'=>'4473', 'amount'=>'3177'),
	array('member_idx'=>'3840', 'amount'=>'3971'),
	array('member_idx'=>'2129', 'amount'=>'4537'),
	array('member_idx'=>'1883', 'amount'=>'4776'),
	array('member_idx'=>'2468', 'amount'=>'4776'),
	array('member_idx'=>'2743', 'amount'=>'4776'),
	array('member_idx'=>'2922', 'amount'=>'4776'),
	array('member_idx'=>'2947', 'amount'=>'4776'),
	array('member_idx'=>'4495', 'amount'=>'4776'),
	array('member_idx'=>'4523', 'amount'=>'4776'),
	array('member_idx'=>'2126', 'amount'=>'5559'),
	array('member_idx'=>'4178', 'amount'=>'5559'),
	array('member_idx'=>'2844', 'amount'=>'7168'),
	array('member_idx'=>'267', 'amount'=>'7942'),
	array('member_idx'=>'857', 'amount'=>'7942'),
	array('member_idx'=>'1107', 'amount'=>'7942'),
	array('member_idx'=>'1763', 'amount'=>'7942'),
	array('member_idx'=>'1903', 'amount'=>'7942'),
	array('member_idx'=>'2145', 'amount'=>'7942'),
	array('member_idx'=>'2932', 'amount'=>'7942'),
	array('member_idx'=>'3000', 'amount'=>'7942'),
	array('member_idx'=>'3171', 'amount'=>'7942'),
	array('member_idx'=>'3313', 'amount'=>'7942'),
	array('member_idx'=>'3404', 'amount'=>'7942'),
	array('member_idx'=>'3427', 'amount'=>'7942'),
	array('member_idx'=>'3447', 'amount'=>'7942'),
	array('member_idx'=>'3555', 'amount'=>'7942'),
	array('member_idx'=>'3605', 'amount'=>'7942'),
	array('member_idx'=>'3671', 'amount'=>'7942'),
	array('member_idx'=>'4175', 'amount'=>'7942'),
	array('member_idx'=>'4299', 'amount'=>'7942'),
	array('member_idx'=>'4309', 'amount'=>'7942'),
	array('member_idx'=>'4323', 'amount'=>'7942'),
	array('member_idx'=>'4409', 'amount'=>'7942'),
	array('member_idx'=>'4419', 'amount'=>'7942'),
	array('member_idx'=>'4422', 'amount'=>'7942'),
	array('member_idx'=>'3728', 'amount'=>'11342'),
	array('member_idx'=>'3865', 'amount'=>'12718'),
	array('member_idx'=>'2984', 'amount'=>'15895'),
	array('member_idx'=>'3102', 'amount'=>'15895'),
	array('member_idx'=>'3293', 'amount'=>'31809'),
	array('member_idx'=>'451', 'amount'=>'47705'),
	array('member_idx'=>'3497', 'amount'=>'233161')
);

$list_count = COUNT($LIST);

for($i=0,$j=1; $i<$list_count; $i++,$j++) {

	$ARR['REQ_NUM']         = "032";
	$ARR['CUST_ID']         = $LIST[$i]['member_idx'];
	$ARR['TRAN_BANK_CD']    = $ACCT['bank_code'];
	$ARR['TRAN_ACCT_NB']    = $ACCT['bank_acct_no'];
	$ARR['TRAN_REMITEE_NM'] = $tran_remitee_nm."(".$LIST[$i]['member_idx'].")";
	$ARR['TRAN_AMT']        = $LIST[$i]['amount'];
	$ARR['TRAN_MEMO']       = $memo;
	$ARR['GUAR_MEMO']       = $memo;
	$ARR['FUND_KIND']       = "10";

	debug_flush($j . ": "); print_r($ARR); debug_flush("\n");

	if($action==$now) {

		$insidebank_result = insidebank_request('256', $ARR);
		print_r($insidebank_result);
		debug_flush("(" . $insidebank_result['RCODE'] . ")\n");

		if($insidebank_result['RCODE']=='00000000') {
			$amount_sum += $LIST[$i]['amount'];
		}

	}
	else {

		$amount_sum += $LIST[$i]['amount'];

	}

	//if($j==10) break;

}

debug_flush(number_format($amount_sum) . "\n");

exit;

?>