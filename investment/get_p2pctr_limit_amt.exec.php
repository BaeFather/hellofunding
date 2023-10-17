<?php
/******************************************************************************
★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★
★ 회원전체 금결원 누적투자액 API를 이용하여 잔여투자한도 때려박기
★ php -q /home/crowdfund/public_html/investment/get_p2pctr_limit_amt.exec.php 회원번호
★ 금결원 전산정비시간  00:00 ~ 00:30
★ 함부로 수정금지!!!!
★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★
******************************************************************************/

$mb_no = @$_SERVER['argv'][1];
if(!$mb_no) { echo "0"; exit; }

$base_path = "/home/crowdfund/public_html";
include_once($base_path . "/common.cli.php");
include_once($base_path . '/lib//p2pctr_svc.lib.php');		// 중앙기록관리 라이브러리 호출

if( date('H:i') >= $CONF['P2PCTR_PAUSE']['STIME'] || date('H:i') <= $CONF['P2PCTR_PAUSE']['ETIME'] ) { echo "0"; exit; }


$sql0 = "
	SELECT
		mb_no, mb_id, member_type, member_investor_type
	FROM
		g5_member
	WHERE 1
		AND mb_no = '".$mb_no."'
		AND member_group = 'F'
		AND mb_level BETWEEN 1 AND 5
		AND va_bank_code2 != '' AND virtual_account2 != ''
	ORDER BY
		mb_no ASC";
$res0 = sql_query($sql0);
$rcount = $res0->num_rows;

for($i=0,$j=1; $i<$rcount; $i++,$j++) {

	$MB = sql_fetch_array($res0);

	// 금결원 잔여투자한도 가져오기
	$LMT = get_p2pctr_limit($MB['mb_id']);
	//debug_flush("ALL_LIMIT: " . $LMT['ALL_LIMIT'] ."\n"); debug_flush("IMV_LIMIT: " . $LMT['IMV_LIMIT'] ."\n");

	// config.php 의 투자금액제한 배열의 값을 따라가게끔 처리
	$INVESTOR = $INDI_INVESTOR['1'];
	$INVESTOR = ($MB['member_type']=='2') ? $CORP_INVESTOR : $INDI_INVESTOR[$MB['member_investor_type']];


	if(is_array($LMT) && isSet($LMT['ALL_LIMIT']) && isSet($LMT['IMV_LIMIT']) ) {
		if( $MB['member_type'] == 2 || ($MB['member_type'] == 1 && $MB['member_investor_type'] == 3) ) {
			// 법인 또는 개인-전문투자자
			$p2pctr_all_limit = $INVESTOR['site_limit'];					// 투자가능금액(전체)
			$p2pctr_imv_limit = $INVESTOR['prpt_limit'];					// 투자가능금액(부동산)
			$p2pctr_mv_limit  = $INVESTOR['site_limit'];					// 투자가능금액(그외)
		}
		else {
			// 개인일반, 소득적격
			$p2pctr_all_limit = $LMT['ALL_LIMIT'];		// 투자가능금액(전체)
			$p2pctr_imv_limit = $LMT['IMV_LIMIT'];		// 투자가능금액(부동산)
			$p2pctr_mv_limit  = $LMT['ALL_LIMIT'];		// 투자가능금액(그외)
		}
	}
	else {
		// 금결원 등록자료 없는 경우
		$p2pctr_all_limit = $INVESTOR['site_limit'];
		$p2pctr_imv_limit = $INVESTOR['prpt_limit'];
		$p2pctr_mv_limit  = $INVESTOR['site_limit'];
	}

	$sqlx = "
		UPDATE
			g5_member
		SET
			p2pctr_all_limit = '".max(0, $p2pctr_all_limit)."',
			p2pctr_imv_limit = '".min(max(0,$p2pctr_all_limit), max(0,$p2pctr_imv_limit))."',
			p2pctr_mv_limit  = '".min(max(0,$p2pctr_all_limit), max(0,$p2pctr_mv_limit))."',
			p2pctr_check_dt = NOW()
		WHERE
			mb_no = '".$MB['mb_no']."'";

	//debug_flush($sqlx.";");
	if($resx = sql_query($sqlx)) { debug_flush($resx); }

}


sql_close();
exit;

?>