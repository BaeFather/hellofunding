<?
###############################################################
## 실시간 전체 투자내역 확인
## 2017-04-24 : 개인회원 상품별 투자 금액 제한 관련 내용 추가
###############################################################

//set_time_limit(300);

include_once('./_common.php');

/*
// 실행시간 측정시작
$log_idx = shell_exec("/usr/local/php/bin/php -q /home/crowdfund/public_html/investment/test_log_start.exec.php {$_SERVER['REMOTE_ADDR']} {$_SERVER['SCRIPT_NAME']}");
$sdt = get_microtime();
*/

$prd_idx = trim($_REQUEST['prd_idx']);

if($prd_idx=="") { exit; }
if(!preg_match('/^[0-9]{0,10}$/', $prd_idx)) { exit; }

$advance = trim($_REQUEST['advance']);
$is_advance_invest = ($advance==1) ? 'Y' : 'N';			// 사전투자모드 설정

$sql = "
	SELECT
		A.idx, A.gr_idx, A.ai_grp_idx, A.category, A.invest_period, A.invest_days,
		A.recruit_amount, A.live_invest_amount AS total_invest_amount,
		A.recruit_period_start, A.recruit_period_end, A.open_datetime, A.start_datetime, A.end_datetime, A.start_date, A.loan_start_date, A.start_hour,
		A.advance_invest, A.advance_invest_ratio, A.only_vip, A.vip_mb_no
	FROM
		cf_product A
	WHERE
		A.idx = '".$prd_idx."'";
$PRDT = sql_fetch($sql);
if(!$PRDT) { exit; }

//$TMP = sql_fetch("SELECT IFNULL(SUM(amount),0) AS total_invest_amount FROM cf_product_invest WHERE product_idx='".$prd_idx."' AND invest_state='Y'");
//$PRDT['total_invest_amount'] = $TMP['total_invest_amount'];

$YmdHis = preg_replace("/(-|:| )/", "", G5_TIME_YMDHIS);
$recruit_period_start = preg_replace("/-/", "", $PRDT['recruit_period_start']);
$recruit_period_end   = preg_replace("/-/", "", $PRDT['recruit_period_end']);
$product_open_date    = preg_replace("/(-|:| )/", "", $PRDT['open_datetime']);		// 상점오픈 (투자시작불가)
$product_invest_sdate = preg_replace("/(-|:| )/", "", $PRDT['start_datetime']);		// 투자시작
$product_invest_edate = preg_replace("/(-|:| )/", "", $PRDT['end_datetime']);			// 상품종료 (투자마감)

$recruit_amount = $PRDT['recruit_amount'];
// 사전투자일 경우
if($is_advance_invest=='Y') {
	$recruit_amount = round($recruit_amount * ($PRDT['advance_invest_ratio']/100));		// 사전투자비율에 따른 사전투자전체한도액 설정
	if($PRDT['total_invest_amount'] >= $recruit_amount) { exit; }											// 사전투자 가능한 금액을 초과 입력 하셨습니다.
	if($product_invest_sdate <= $YmdHis) { exit; }																		// 사전투자 가능 시간이 지났습니다.
}


// 투자모집진행률
$product_invest_percent = 0;
if($PRDT['total_invest_amount']) {
	$product_invest_percent = ($PRDT['total_invest_amount'] / $recruit_amount) * 100;
	$product_invest_percent = floatCutting($product_invest_percent, 2);
}


if($member['mb_id']) {
	$need_virtual_account = ( empty($member['va_bank_code2']) || empty($member['va_private_name2']) || empty($member['virtual_account2']) ) ? true : false;
}

###################################
## 리턴 상태코드(code) 예시 : getProductStat($prd_idx) 리턴 배열
## A01 : 이자상환중
## A02 : 투자상환완료 (상품마감)
## A03 : 투자모집실패
## A04 : 부실
## A05 : 중도일시상환
## B00 : 상품준비중
## B01 : 투자대기중
## B02 : 투자모집중
## B03 : 투자모집완료
## B04 : 투자모집실패
###################################
$PRDT_STATE = getProductStat($prd_idx);

$invest_finished = false;
if($PRDT_STATE['code']=='A02') {
	$invest_finished = true;
	$button_class    = 'btn_big_gray';
	$invest_button   = '<a href="javascript:;" onClick="alert(\'본 상품의 투자가 종료 되었습니다.\');" class="'.$button_class.'">투자상환완료</a>';
}
else if(preg_match('/(B00|B01)/', $PRDT_STATE['code'])) {
	$button_class  = 'btn_big_green';
	if($product_open_date > $YmdHis) {
		$msg = "투자 가능 시간이 아닙니다.";
	}
	else {
		if($product_invest_sdate > $YmdHis) {
			$print_day = date("Y.m.d", strtotime($PRDT['start_date']))." ".get_yoil($PRDT['start_date'])."요일";
			$print_time = ($PRDT['start_hour']<=12) ? '오전' : '오후';
			$print_time.= date("g:i", strtotime($PRDT['start_datetime'])); //출력표기 시간
			$msg = $print_day." ".$print_time." 부터 투자가 가능합니다.";
		}
	}

	if($is_member) {
		if($member['invest_warning_agree']=='Y') {
			$invest_button = '<a href="javascript:;" onClick="alert(\''.$msg.'\');" class="'.$button_class.'">'.$PRDT_STATE['code_str'].'</a>';
			$advance_invest_button = '<a id="btn_advance_invest" href="/investment/detail.php?prd_idx='.$PRDT['idx'].'&advance=1" class="btn_big_maple">사전투자하기</a>';
		}
		else {
			$invest_button = '<a href="javascript:;" onClick="invest_warning_agree_open();"  class="'.$button_class.'">'.$PRDT_STATE['code_str'].'</a>';  //투자위험고지 팝업 : /popup/inc_invest_warning_agree_form.php
			$advance_invest_button = '<a id="btn_advance_invest" href="javascript:;" onClick="invest_warning_agree_open();" class="btn_big_maple">사전투자하기</a>';
		}
	}
	else {
		$invest_button = '<a href="javascript:;" onClick="alert(\'본 서비스는 로그인이 필요합니다.\');" class="'.$button_class.'">'.$PRDT_STATE['code_str'].'</a>';
		$advance_invest_button = '<a id="btn_advance_invest" href="javascript:;" onClick="alert(\'본 서비스는 로그인이 필요합니다.\');" class="btn_big_maple">사전투자하기</a>';
	}

	if($PRDT['advance_invest']!='Y') {
		$advance_invest_button = "";
	}

}
else if($PRDT_STATE['code']=='B02') {
	$button_class  = 'btn_big_blue';
	if($member['mb_id']) {
		if($member['invest_warning_agree']=='Y') {
			$invest_button = '<a href="/investment/detail.php?prd_idx='.$PRDT['idx'].'" class="'.$button_class.'">'.$PRDT_STATE['code_str'].'</a>';
		}
		else {
			$invest_button = '<a href="javascript:;" onClick="invest_warning_agree_open();" class="'.$button_class.'">'.$PRDT_STATE['code_str'].'</a>';  //투자위험고지 팝업 : /popup/inc_invest_warning_agree_form.php
		}

		if($need_virtual_account) {
			//$invest_button = '<a href="javascript:;" onClick="if(confirm(\'발급된 가상계좌 정보가 없습니다.\\n가상계좌 신청 페이지로 이동하시겠습니까?\')){ location.href=\'/deposit/deposit.php?tab=4\'; }" class="'.$button_class.'">투자하기</a>';
			$invest_button = '<a href="javascript:;" onClick="KYCPopup();" class="'.$button_class.'">'.$PRDT_STATE['code_str'].'</a>';
		}

	}
	else {
		$invest_button = '<a href="/bbs/login.php?url='.urlencode('/investment/investment.php?prd_idx='.$PRDT['idx']).'" class="'.$button_class.'">'.$PRDT_STATE['code_str'].'</a>';
	}
}
else {

	$invest_finished = true;
	$button_class    = 'btn_big_gray';

	$msg = "본 상품의 투자가 종료 되었습니다.";
	$msg.= (!$member["mb_id"]) ? "\\n`투자상품 알림받기`로 헬로펀딩의\\n신규상품 정보를 가장 먼저 받아보세요." : "";
	$invest_button   = '<a href="javascript:;" onClick="alert(\''.$msg.'\');" class="'.$button_class.'">'.$PRDT_STATE['code_str'].'</a>';

}

///////////////////////////////
// KYC 현황 체크
///////////////////////////////
if( in_array($member['mb_level'],array('1','2','3','4','5')) ) {
	//if( $office_connect ) {				// if( in_array($member['mb_id'], $kyc_test_member) ) {
		if(date('Y-m-d') >= '2022-01-01' && $member['kyc_allow_yn'] != 'Y' ) {
			$invest_button = '<a href="javascript:;" onClick="KYCPopup();" class="'.$button_class.'">'.$PRDT_STATE['code_str'].'</a>';
		}
	//}
}

$invest_button2 = $invest_button;  // 하단 투자하기 버튼



// 대출실행 완료건에 대하여 이자지급 차수 표시
if($PRDT['loan_start_date'] && $PRDT['loan_start_date']!='0000-00-00') {
	$loan_start_date_day = (int)substr($PRDT['loan_start_date'], -2);
	$total_repay_count = ($loan_start_date_day > 1) ? $PRDT['invest_period'] + 1 : $PRDT['invest_period'];  //총 지급횟수
	$PAIED = sql_fetch("SELECT IFNULL(MAX(turn),0) AS max_turn FROM cf_product_success WHERE product_idx='".$PRDT['idx']."' AND invest_give_state='Y'");
	$repay_count = ($PAIED['max_turn']) ? $PAIED['max_turn'] : 0;
}

if( in_array($PRDT_STATE['code'], array('A01','A02','A05')) ) {
	$fcolor = ($repay_count) ? '#FF6633' : '#AAA';
	$area3_title = '지급회차';
	$area3_data = '<span style="color:'.$fcolor.'">'.$repay_count.'</span> / '.$total_repay_count;
}
else {
	$area3_title = '목표금액';
	$area3_data = price_cutting($recruit_amount).'원';
}
$area4_data = price_cutting($PRDT['total_invest_amount']).'원';  // 모집금액

if ($PRDT['total_invest_amount'] > 0) {
    $tmpRecruitData = wonFormat($PRDT["recruit_amount"]);
    $area5_data = ($tmpRecruitData[0]) ? $tmpRecruitData[0] : $PRDT["recruit_amount"];
    $area5_data .= ($tmpRecruitData[1]) ? $tmpRecruitData[1] : null;
}

$progress = $product_invest_percent.'%';  // 진행률
$progress_width = ($product_invest_percent) ? $product_invest_percent . '%' : '0.2%';

$button_area1_data = "";
//$button_area1_data.= ($invest_finished==false) ? ' <li class="simulation"><a href="/investment/simulation.php?prd_idx='.$PRDT['idx'].'" class="btn_big_link">투자시뮬레이션</a></li>' : '';
$button_area1_data.= ' <li class="invest">' . $invest_button . '</li>';
$button_area1_data.= ($advance_invest_button) ? ' <li class="auto_invest">' . $advance_invest_button . '</li>' : '';
//$button_area1_data.= ($PRDT['ai_grp_idx']) ? ' <li class="reser_invest"><a href="/deposit/deposit.php?tab=5" class="btn_big_orange">자동투자설정</a></li>' : '';


// 동일차주그룹상품 판별
if( $member['member_type']=='1' && in_array($member['member_investor_type'], array('1','2')) ) {
	// 모집중이거나 이자상환중인 (원금상환이 완료되지 않은) 동일차주상품 SELECT (현재 열람중인 상품도 포함)
	$sql2 = "SELECT idx FROM cf_product WHERE gr_idx='".$PRDT['gr_idx']."' AND state IN('','1','8') AND idx > '{$CONF['old_type_end_prdt_idx']}' ORDER BY idx";
	$res  = sql_query($sql2);
	$rcnt = $res->num_rows;
	if($rcnt) {
		if($rcnt > 1) {
			$is_group_product = true;
		}
		$prd_idx_arr = '';
		for($i=0,$j=1; $i<$rcnt; $i++,$j++) {
			$r = sql_fetch_array($res);
			$prd_idx_arr.="'".$r['idx']."'";
			$prd_idx_arr.= ($j<$rcnt) ? "," : "";
		}

		$sql3 = "
			SELECT
				IFNULL(SUM(amount), 0) AS sum_invest_amount
			FROM
				cf_product_invest
			WHERE 1
				AND member_idx='".$member['mb_no']."'
				AND product_idx IN ($prd_idx_arr)
				AND invest_state='Y'";
		$INVEST_PRDT = sql_fetch($sql3);
	}
	@sql_free_result($res);
}


// 잔여 모집금액
$need_recruit_amount = $recruit_amount - $PRDT['total_invest_amount'];
if($need_recruit_amount < 0) $need_recruit_amount = '0';

// 투자 가능금액 설정
$invest_possible_amount = $need_recruit_amount;

if($member['member_type']=='1') {

	if( in_array($member['member_investor_type'], array('1','2')) ) {
		$limit_amount = ($is_group_product) ? $INDI_INVESTOR[$member['member_investor_type']]['group_product_limit'] : $INDI_INVESTOR[$member['member_investor_type']]['single_product_limit'];
		$_invest_possible_amount = $limit_amount - $INVEST_PRDT['sum_invest_amount'];

		if($_invest_possible_amount > $member['invest_possible_amount']) {
			$invest_possible_amount = $member['invest_possible_amount'];
		}
		else {
			$invest_possible_amount = $_invest_possible_amount;
		}
	}

}


// 특수회원 (회원번호:1203(apollon)) 예외은 투자제한금액 예외 처리
if($member['mb_no']=='1203') {

	$pro_max_limit_amount = $need_recruit_amount;

}
else {

	// 20200826 추가 (법인, 개인전문투자자 : 총 모집금액의 40% 까지만 투자가능)
	// 법인회원 및 개인전문투자자 최대 투자가능금액 체크
	if($member['member_type']=='2' || ($member['member_type']=='1' && $member['member_investor_type']=='3')) {
		// 본상품 기투자금액
		$TMP2 = sql_fetch("SELECT IFNULL(SUM(amount), 0) AS invested_amount FROM cf_product_invest WHERE product_idx='".$PRDT['idx']."' AND member_idx='".$member['mb_no']."' AND invest_state='Y'");

		$pro_max_limit_amount = ($PRDT['recruit_amount'] * $INDI_INVESTOR['3']['invest_able_perc']) - $TMP2['invested_amount'];
		$pro_max_limit_amount = @floor($pro_max_limit_amount / 10000) * 10000;			// 만원단위로 변경

		//지정투자자 상품이며, 투자자가 본인이며, 법인인 경우 투자가능금액은 제한을 받지 않도록...
		//if( $PRDT['only_vip']=='1' && in_array($member['mb_no'], explode(",", $PRDT['vip_mb_no'])) && $member['member_type']=='2' ) {
		//	$pro_max_limit_amount = $invest_possible_amount;
		//}
	}

}


$my_money = @floor(get_point_sum($member['mb_id']) / 10000) * 10000;		// 현재보유예치금 (투자가능단위인 만원단위로 변환)

if($member['member_type']=='1') {
	if($member['member_investor_type']=='1') {
		$invest_possible_amount_ca = ($PRDT['category']=='2') ? $member['invest_possible_amount_prpt'] : $member['invest_possible_amount_ds'];        // 동산,부동산별 본인의 현재 투자 가능금액
		$invest_possible_amount = min($need_recruit_amount, $invest_possible_amount, $invest_possible_amount_ca, $my_money);		// 개인-일반투자자의 경우 카테고리별 투자 가능금액도 실제투자가능금액의 추출인자가 된다.
	}
	else if($member['member_investor_type']=='2') {
		$invest_possible_amount = min($need_recruit_amount, $invest_possible_amount, $my_money);
	}
	else {		// 개인전문투자자
		$invest_possible_amount = min($need_recruit_amount, $invest_possible_amount, $my_money, $pro_max_limit_amount);
	}
}
else {
	$invest_possible_amount = min($need_recruit_amount, $invest_possible_amount, $my_money, $pro_max_limit_amount);
}

$invest_possible_amount = max(0, $invest_possible_amount);		// 마이너스로 넘어온 경우 0으로 출력



/*
// 관리자가 아닐 경우 사전 투자 버튼 및 진행률 나오지 않도록 모집금액 0처리 (중요)
if($is_advance_invest != 'Y') {
	if(!$is_admin) {
		if($PRDT['advance_invest']=='Y' && $product_invest_sdate > $YmdHis) {
			$area4_data     = '0원';  // 모집금액
			$progress       = '0%';		// 모집율
			$progress_width = '0.2%';	// 진행바 사이즈(너비)
		}
	}
}
*/


$ARR['data'] = array(
	'invest_finished'            => $invest_finished,
	'area3_title'                => $area3_title,
	'area3_data'                 => $area3_data,
	'area4_data'                 => $area4_data,
	'area5_data'                 => $area5_data,
	'progress'                   => $progress,
	'progress_width'             => $progress_width,
	'button_data1'               => $button_area1_data,
	'advance_invest_button_data' => $advance_invest_button,
	'button_data2'               => $invest_button2,
	'need_recruit_amount'        => $need_recruit_amount,
	'need_recruit_amount_k'      => price_cutting($need_recruit_amount).'원',
	'invest_possible_amount'     => $invest_possible_amount,
	'invest_possible_amount_k'   => price_cutting($invest_possible_amount).'원',
	'total_invest_amount'        => $PRDT['total_invest_amount'],
	'total_invest_amount_k'      => price_cutting($PRDT['total_invest_amount']).'원',
	'version'                    => $YmdHis,
	'referer'                    => $_SERVER['HTTP_REFERER']
);



header("Content-Type:application/json");


if( $office_connect ) {
	$json = json_encode($ARR, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
}
else {
	$json = json_encode($ARR);
}

echo $json;


$PRDT = $PRDT_STATE = $DATA = NULL;


sql_close();

/*
// 실행시간 로깅 종료
if($log_idx) {
	$thrSec  = get_microtime() - $sdt;
	@shell_exec("/usr/local/php/bin/php -q /home/crowdfund/public_html/investment/test_log_finish.exec.php {$log_idx} {$thrSec}");
}
*/

exit;

?>