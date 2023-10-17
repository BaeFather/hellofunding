#!/usr/local/php/bin/php -q
<?
########################################################
## cf_product_give 테이블에 세금, 수수료 데이터 삽입함
########################################################


set_time_limit(0);

include_once("/home/crowdfund/public_html/common.php");

//auth_check($auth[$sub_menu], 'w');
//if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

include_once(G5_LIB_PATH.'/repay_calculation.php');		// 월별 정산내역 추출함수 호출


//$PRD_IDX = array(91,94,97,99);
//$PRD_IDX = array(103,104,109,110,117,120,121,122,123,124,125,126,127,128,129,130,131,132,133,134,135,141,142,143,144,145,146,147,149,151,153,154,155,156,157);
//$PRD_IDX = array(160,168,169,170,171,172,173,174,175,176,177,178,180,181,182,184,185,187,188,189,190,191,192,193,194,195,196,197,198,199);
//$PRD_IDX = array(201,202,203,205,206,207,212,213,215,218,222,224,225,226,227,228,229,230,231,232,233,234,236,238,239,240,241,242,243,245,247,250);
$PRD_IDX = array(251,252,253,254,255,256,257,258,259,260,261,263,264,266,268,269,271,273,274,275);

debug_flush("start ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::\n");

for($x=0; $x<count($PRD_IDX); $x++) {

	$INV_ARR = repayCalculation($PRD_IDX[$x]);

	$REPAY = $INV_ARR['REPAY'];
	//print_rr($REPAY, 'font-size:11px');

	$repay_count = count($REPAY);

	for($i=0,$turn=1; $i<$repay_count; $i++,$turn++) {

		$list_count = count($REPAY[$i]['LIST']);
		for($j=0,$num=$list_count; $j<$list_count; $j++,$num--) {

			$principal    = (int)$REPAY[$i]['LIST'][$j]['repay_principal'];
			$invest_idx   = (int)$REPAY[$i]['LIST'][$j]['invest_idx'];
			$interest_tax = (int)$REPAY[$i]['LIST'][$j]['TAX']['interest_tax'];
			$local_tax    = (int)$REPAY[$i]['LIST'][$j]['TAX']['local_tax'];
			$fee          = (int)$REPAY[$i]['LIST'][$j]['invest_usefee'];
		//$repay_principal = (int)$REPAY[$i]['LIST'][$j]['TAX']['repay_principal'];

			$row = sql_fetch("SELECT idx, interest_tax FROM cf_product_give WHERE invest_idx='$invest_idx' AND turn='$turn'");

			if($row['idx'] && ($row['interest_tax']<>$interest_tax)) {
				$sql = "UPDATE cf_product_give SET interest_tax=$interest_tax, local_tax=$local_tax, fee=$fee WHERE idx='".$row['idx']."'";
				$res = sql_query($sql);
				debug_flush("[".$PRD_IDX[$x]."] " . $sql . " : (" . sql_affected_rows() . ")\n");
				//usleep(10000);

				$principal = $invest_idx = $interest_tax = $local_tax = $fee = 0;
			}

		}

		unset($REPAY[$i]);

	}

}

debug_flush("finish ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::\n");

sql_close();
exit;

?>