#!/usr/local/php/bin/php -c /etc/php.ini -q
<?
///////////////////////////////////////////////////////////////////////////////
// 회원전체 금결원 누적투자액 API를 이용하여 잔여투자한도 때려박기
// php -q /home/crowdfund/public_html/adm/etc/tmp_p2pctr_limit_amt.php
// 금결원 전산정비시간 11:30 ~ 12:30
///////////////////////////////////////////////////////////////////////////////

$base_path = "/home/crowdfund/public_html";
include_once($base_path . "/common.php");
include_once($base_path . '/lib//p2pctr_svc.lib.php');		// 중앙기록관리 라이브러리 호출

define('G5_DISPLAY_SQL_ERROR', true);

$sql0 = "
	SELECT
		mb_no, mb_id, member_type, member_investor_type
	FROM
		g5_member
	WHERE 1
		AND mb_level='1' AND member_group='F'
		AND member_type='1' AND member_investor_type='1'
		AND va_bank_code2!='' AND virtual_account2!=''
		-- AND p2pctr_check_dt IS NULL
	ORDER BY
		mb_no ASC";
$res0 = sql_query($sql0);
$rcount = $res0->num_rows;

for($i=0,$j=1; $i<$rcount; $i++,$j++) {

	$MB = sql_fetch_array($res0);

	// 금결원 잔여투자한도 가져오기
	$LMT = get_p2pctr_limit($MB['mb_id']);
	//debug_flush("ALL_LIMIT: " . $LMT['ALL_LIMIT'] ."\n"); debug_flush("IMV_LIMIT: " . $LMT['IMV_LIMIT'] ."\n");


	$INVESTOR = ($MB['member_type']=='2') ? $CORP_INVESTOR : $INDI_INVESTOR[$MB['member_investor_type']];		// config.php 의 투자금액제한 배열의 값을 따라가게끔 처리


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
			$p2pctr_mv_limit  = $LMT['MV_LIMIT'];			// 투자가능금액(그외)
		}

		$sqlAdd = ", p2pctr_check_dt = NOW()";
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
			p2pctr_mv_limit  = '".min(max(0,$p2pctr_all_limit), max(0,$p2pctr_mv_limit))."'
			$sqlAdd
		WHERE
			mb_no = '".$MB['mb_no']."'";

	debug_flush($j . " : " . $sqlx . ";");
	if($resx = sql_query($sqlx)) { debug_flush(" (" . $resx . ")"); }
	debug_flush("\n");

	if($j%1000==0) sleep(1);

}


sql_close();
exit;

?>