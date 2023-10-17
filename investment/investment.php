<?
###############################################################################
## 투자상품 상세보기
###############################################################################

include_once('./_common.php');


// 실행시간 로깅 시작
//$log_idx = @shell_exec("/usr/local/php/bin/php -q /home/crowdfund/public_html/investment/test_log_start.exec.php {$_SERVER['REMOTE_ADDR']} {$_SERVER['SCRIPT_NAME']}");
//$sdt = get_microtime();


include_once(G5_LIB_PATH.'/review.lib.php');

$g5['title'] = '투자상품 상세보기';
$g5['top_bn'] = "/images/investment/sub_investment.jpg";
$g5['top_bn_alt'] = "투자하기 투자자가 작은 금액들을 모아서 함께 투자하는 새로운 투자 방식입니다.";


while( list($k, $v) = each($_REQUEST) ) { if(!is_array($k)) ${$k} = addslashes(clean_xss_tags(trim($v))); }



$developer        = ( in_array($member['mb_id'], $CONF['DEVELOPER']) ) ? true : false;
$goods_officer    = ( in_array($member['mb_id'], $CONF['GOODS_OFFICER']) ) ? true : false;
$tmp_special_user = ( in_array($member['mb_id'], array('samo','samo001','samo002')) ) ? true : false;
//$tmp_user         = ( in_array($member['mb_id'], array('cocktailfunding')) ) ? true : false;


if($prd_idx=='') { goto_url('/'); exit; }
if(!preg_match('/^[0-9]{0,10}$/', $prd_idx)) { header('Location: /', true, 302); exit; }
if( in_array($prd_idx, array('3875')) ) {
	if(!$goods_officer) msg_go("본 상품은 대출종료 후, 차주 요청에 의하여 비노출 처리 되었습니다.");		// 차주 비노출처리 요청 상품
}

if($prd_idx=='164') {
	if(!$goods_officer) { header('Location: /investment/investment.php?prd_idx=168', true, 302); exit; }
}


$sql = "
	SELECT
		A.*,
		B.*
	FROM
		cf_product A
	LEFT JOIN
		cf_product_container B  ON A.idx=B.product_idx
	WHERE
		A.idx='".$prd_idx."'";
$sql.= ($is_admin=='super' || $developer || $goods_officer || $tmp_special_user || $tmp_user) ? "" : " AND A.display='Y'";
$PRDT = sql_fetch($sql);
//if($is_admin=='super') { echo "(".$product_cnt.") ". $product_query; exit; }

if(!$PRDT) { alert("올바른 경로가 아닙니다.","/"); exit; }

//while(list($row_key, $row_value)=each($PRDT)) { $PRDT[$row_key] = trim($row_value); }

// 법인전용상품 설정 ------------------------------------------------------------------

// 특정상품에 메세지 다르게 적용
$defeat_msg = "[본 투자상품 관련 공지]\\n\\n본 투자상품은 투자자와 사전에 협의가 완료된 법인전용상품입니다.\\n따라서 지정된 투자자 외 분들의 상품열람 및 투자가 제한되는 점 양해부탁드립니다.";
if($prd_idx=='148') {
	$defeat_msg = "[본 투자상품 관련 공지]\\n\\n본 투자상품은 사전에 협의완료된 대출자와 투자자가 제3자에 의한 체계적 담보권리확보 및 자금관리를 목적으로 헬로펀딩을 통해 펀딩을 진행합니다.\\n따라서 지정된 투자자 외 분들의 상품열람 및 투자가 제한되는 점 양해부탁드립니다.";
}

if(!$is_admin) {

	if( $PRDT['only_vip']=='1' ) {
		$VIP_MB_NO = explode(",", $PRDT['vip_mb_no']);
		if( count($VIP_MB_NO) > 0 && in_array($member['mb_no'], $VIP_MB_NO) ) {
			//
		}
		else {
			msg_replace($defeat_msg, "/investment/invest_list.php");
		}
	}

	/*
	if( $PRDT['only_vip']=='1' ) {
		if($prd_idx=='148' && !in_array($member['mb_id'], array('moreamc','uildnm2012','yr4msp','sori9th'))) { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if($prd_idx=='157' && !in_array($member['mb_id'], array('fintech05','yr4msp','sori9th'))) { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if($prd_idx=='171' && !in_array($member['mb_id'], array('KJHInvest1019','GraceInvest1102','master'))) { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if(in_array($prd_idx, array('175','176')) && $member['mb_id']!='apollon') { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if($prd_idx=='225' && $member['mb_id']!='dividend01') { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if($prd_idx=='231' && $member['mb_id']!='directlending') { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if($prd_idx=='238' && $member['mb_id']!='akorea') { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if($prd_idx=='357' && $member['mb_id']!='hanilfund') { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if(in_array($prd_idx, array('378','380','396')) && $member['mb_id']!='hanilfirst' ) { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if($prd_idx=='644' && $member['mb_id']!='nnsco1129') { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if($prd_idx=='883' && $member['mb_id']!='hanilfund2') { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if($prd_idx=='832' && $member['mb_id']!='nkco022801') { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if(in_array($prd_idx, array('1225','1226'))  && !in_array($member['mb_id'] , array('akorea','nkco022801')) ) { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if($prd_idx=='1712' && $member['mb_id']!='apollon') { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if($prd_idx=='2000' && !in_array($member['mb_no'], array('17818','17819'))) { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if($prd_idx=='2052' && !in_array($member['mb_no'], array('17818','17819'))) { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if($prd_idx=='2061' && !in_array($member['mb_no'], array('17818','17819'))) { msg_replace($defeat_msg, "/investment/invest_list.php"); }
		if($prd_idx=='2116' && !in_array($member['mb_no'], array('17818','17819'))) { msg_replace($defeat_msg, "/investment/invest_list.php"); }
	}
	*/

}
// 법인전용상품 설정 ------------------------------------------------------------------

// 개발페이지에서 https 도메인 혼용 금지
if(preg_match("/dev\.hello/", G5_URL)) {
	$PRDT['extend_7']            = preg_replace("/https:\/\//i", "http://", $PRDT['extend_7']);
	$PRDT['extend_7']            = preg_replace("/www\.hello/i", "dev.hello", $PRDT['extend_7']);
	$PRDT['extend_8']            = preg_replace("/https:\/\//i", "http://", $PRDT['extend_8']);
	$PRDT['extend_8']            = preg_replace("/www\.hello/i", "dev.hello", $PRDT['extend_8']);
	$PRDT['extend_9']            = preg_replace("/https:\/\//i", "http://", $PRDT['extend_9']);
	$PRDT['extend_9']            = preg_replace("/www\.hello/i", "dev.hello", $PRDT['extend_9']);
	$PRDT['extend_10']           = preg_replace("/https:\/\//i", "http://", $PRDT['extend_10']);
	$PRDT['extend_10']           = preg_replace("/www\.hello/i", "dev.hello", $PRDT['extend_10']);
	$PRDT['invest_summary']      = preg_replace("/https:\/\//i", "http://", $PRDT['invest_summary']);
	$PRDT['invest_summary']      = preg_replace("/www\.hello/i", "dev.hello", $PRDT['invest_summary']);
	$PRDT['invest_summary_m']    = preg_replace("/https:\/\//i", "http://", $PRDT['invest_summary_m']);
	$PRDT['invest_summary_m']    = preg_replace("/www\.hello/i", "dev.hello", $PRDT['invest_summary_m']);
	$PRDT['core_invest_point']   = preg_replace("/https:\/\//i", "http://", $PRDT['core_invest_point']);
	$PRDT['core_invest_point']   = preg_replace("/www\.hello/i", "dev.hello", $PRDT['core_invest_point']);
	$PRDT['product_summary']     = preg_replace("/https:\/\//i", "http://", $PRDT['product_summary']);
	$PRDT['product_summary']     = preg_replace("/www\.hello/i", "dev.hello", $PRDT['product_summary']);
	$PRDT['product_description'] = preg_replace("/https:\/\//i", "http://", $PRDT['product_description']);
	$PRDT['product_description'] = preg_replace("/www\.hello/i", "dev.hello", $PRDT['product_description']);
}

$sql2 = "
	SELECT
		COUNT(A.product_idx) AS total_invest_count,
		IFNULL(SUM(A.amount), 0) AS total_invest_amount,
		(B.recruit_amount - B.live_invest_amount) AS need_recruit_amount
	FROM
		cf_product_invest A
	LEFT JOIN
		cf_product B  ON A.product_idx = B.idx
	WHERE 1
		AND A.product_idx='".$PRDT['idx']."'";
$sql2.= ($PRDT['state']=='6') ? " AND A.invest_state='R'" : " AND A.invest_state='Y'";  //투자취소 상품의 경우 반환 처리된 투자금 내역을 가져온다.

$tmpres = sql_fetch($sql2);
//print_rr($sql2, 'margin-top:200px');

$PRDT['total_invest_count']  = $tmpres['total_invest_count'];
$PRDT['total_invest_amount'] = $tmpres['total_invest_amount'];
$PRDT['need_recruit_amount'] = $tmpres['need_recruit_amount'];
unset($sql2);


/*
// 관리자가 아닐 경우 사전 투자상품의 진행률 나오지 않도록 모집금액 0처리 (중요)
if($is_advance_invest != 'Y') {
	if(!$is_admin) {
		if($PRDT['advance_invest']=='Y' && $PRDT['start_datetime'] > G5_TIME_YMDHIS) {
			$total_invest_amount = $PRDT["total_invest_amount"];
			$PRDT["total_invest_amount"] = 0;
		}
	}
}
*/


// 투자모집진행률
$product_invest_percent = 0;
if($PRDT['total_invest_amount']) {
	$product_invest_percent = ($PRDT['total_invest_amount'] / $PRDT['recruit_amount']) * 100;
	$product_invest_percent = floatCutting($product_invest_percent, 2);
}


if($member['mb_id']) {
	$shinhan_vacct = ( trim($member['va_bank_code2']) && trim($member['virtual_account2']) ) ? true : false;
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
	$button_class   = 'btn_big_gray';

	$msg = "본 상품의 투자가 종료 되었습니다.";
	$msg.= (!$member["mb_id"]) ? "\\n`투자상품 알림받기`로 헬로펀딩의\\n신규상품 정보를 가장 먼저 받아보세요." : "";
	$invest_button   = '<a href="javascript:;" onClick="alert(\''.$msg.'\');" class="'.$button_class.'">투자상환완료</a>';
}

else if(preg_match('/(B00|B01)/', $PRDT_STATE['code'])) {
	$button_class  = 'btn_big_green';
	if($PRDT['open_datetime'] > G5_TIME_YMDHIS) {
		$msg = "투자 가능 시간이 아닙니다.";
	}
	else {
		if($PRDT['start_datetime'] > G5_TIME_YMDHIS) {
			$print_day = date("Y.m.d", strtotime($PRDT['start_date']))." ".get_yoil($PRDT['start_date'])."요일";
			$print_time = ($PRDT['start_hour']<=12) ? '오전' : '오후';
			$print_time.= date("g:i", strtotime($PRDT['start_datetime'])); //출력표기 시간
			$msg = $print_day." ".$print_time." 부터 투자가 가능합니다.";
		}
	}

	if( $is_member && in_array($member['mb_level'], array('1','2','3','4','5')) ) {

		if($member['invest_warning_agree']=='Y') {
			$invest_button = '<a href="javascript:;" onClick="alert(\''.$msg.'\');" class="'.$button_class.'">'.$PRDT_STATE['code_str'].'</a>';
			$advance_invest_button = '<a id="btn_advance_invest" href="/investment/detail.php?prd_idx='.$PRDT['idx'].'&advance=1" class="btn_big_maple">사전투자하기</a>';
		}
		else {
			$invest_button = '<a href="javascript:;" onClick="invest_warning_agree_open();"  class="'.$button_class.'">'.$PRDT_STATE['code_str'].'</a>';  //투자위험고지 팝업 : /popup/inc_invest_warning_agree_form.php
			$advance_invest_button = '<a id="btn_advance_invest" href="javascript:;" onClick="invest_warning_agree_open();"  class="btn_big_maple">사전투자하기</a>';
		}

		// KYC 진행현황에 따른 팝업오픈 (투자차단)
		//if( $office_connect ) {				// if( in_array($member['mb_id'], $kyc_test_member) ) {
			if( $member['kyc_allow_yn'] != 'Y' ) {
				$invest_button = '<a href="javascript:;" onClick="KYCPopup();" class="'.$button_class.'">'.$PRDT_STATE['code_str'].'</a>';
			}
		//}

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
		if($shinhan_vacct) {
			if($member['insidebank_after_trans_target']=='1') {
				// 기존계좌의 금액이전이 완료되지 않은 경우
				$tmp_msg = "신한은행 가상계좌 발급이 완료되어 현재 보유하신 예치금이 신한은행으로 이관중입니다.\n\n이관에 소요되는 시간은 가상계좌 발급 후 영업일 기준 최장 48시간 이내이며,\n\n이관이 완료된 후 투자가 가능한 점 양해부탁드립니다.";
				$invest_button = '<a href="javascript:;" onClick="alert(\''.$tmp_msg.'\');" class="'.$button_class.'">투자하기</span>';
			}
			else {

				if($member['invest_warning_agree']=='Y') {
					$invest_button = '<a href="/investment/detail.php?prd_idx='.$PRDT['idx'].'" class="'.$button_class.'">투자하기</a>';
				}
				else {
					$invest_button = '<a href="javascript:;" onClick="invest_warning_agree_open();" class="'.$button_class.'">투자하기</a>';  //투자위험고지 팝업 : /popup/inc_invest_warning_agree_form.php
				}

				if( date('Y-m-d') >= '2022-01-01' && $member['kyc_next_dd'] <= date('Y-m-d') ) {
					// 본인확인이 되지 않은 경우
					$invest_button = '<a href="javascript:;" onClick="KYCPopup();" class="'.$button_class.'">투자하기</a>';
				}

			}
		}
		else {

			//$invest_button = '<a href="javascript:;" onClick="if(confirm(\'발급된 가상계좌 정보가 없습니다.\\n가상계좌 신청 페이지로 이동하시겠습니까?\')) { location.href=\'/deposit/deposit.php?tab=3\'; }" class="'.$button_class.'">투자하기</a>';
			$invest_button = '<a href="javascript:;" onClick="KYCPopup();" class="'.$button_class.'">투자하기</a>';

		}
	}
	else {
		$invest_button = '<a href="/bbs/login.php?url='.urlencode($_SERVER['REQUEST_URI']).'" class="'.$button_class.'">투자하기</a>';
	}

}

else {
	$invest_finished = true;
	$button_class	   = 'btn_big_gray';

	$msg = "본 상품의 투자가 종료 되었습니다.";
	$msg.= (!$member["mb_id"]) ? "\\n`투자상품 알림받기`로 헬로펀딩의\\n신규상품 정보를 가장 먼저 받아보세요." : "";
	$invest_button   = '<a href="javascript:;" onClick="alert(\''.$msg.'\');" class="'.$button_class.'">'.$PRDT_STATE['code_str'].'</a>';
}

if($is_admin) {
	$msg = "관리자는 이용할 수 없는 기능입니다.";
	$invest_button   = '<a href="javascript:;" onClick="alert(\''.$msg.'\');" class="'.$button_class.'">'.$PRDT_STATE['code_str'].'</a>';
}

$invest_button2 = $invest_button;  // 하단 투자하기 버튼



//투자요약
$invest_summary = $PRDT["invest_summary"];
if(G5_IS_MOBILE) {
	$invest_summary = ($PRDT['invest_summary_m']) ? $PRDT['invest_summary_m'] : $PRDT["invest_summary"];
}
else {
	$invest_summary = $PRDT["invest_summary"];
}


if($PRDT["evaluate_star1"] && $PRDT["evaluate_star2"] && $PRDT["evaluate_star3"]) {
	$grade_type = "v1";
	//-- 기존 등급 산정방식 v1--------------------------------------------------//
	$level_score = $PRDT["evaluate_star1"] + $PRDT["evaluate_star2"] + $PRDT["evaluate_star3"];
	$grade = $_evaluation_grade_array[$level_score];
	//-- 기존 등급 산정방식 v1--------------------------------------------------//
}
else if($PRDT["evaluate_score1"] && $PRDT["evaluate_score2"] && $PRDT["evaluate_score3"] && $PRDT['evaluate_score4']) {
	$grade_type = "v2";
	//-- 개정 등급 산정방식 v2--------------------------------------------------//
	$level_score = round(($PRDT["evaluate_score1"] + $PRDT["evaluate_score2"] + $PRDT["evaluate_score3"] + $PRDT["evaluate_score4"]) / 5);
	$grade = $_gudge_grade_array[$level_score];
	$evaluate_score1 = round($PRDT["evaluate_score1"]/48*100);
	$evaluate_score2 = round($PRDT["evaluate_score2"]/5*100);
	$evaluate_score3 = round($PRDT["evaluate_score3"]/5*100);
	$evaluate_score4 = round($PRDT["evaluate_score4"]/42*100);
	//-- 개정 등급 산정방식 v2--------------------------------------------------//
}
else if($PRDT["evaluate_score1"] && $PRDT["evaluate_score2"]==0 && $PRDT["evaluate_score3"] && $PRDT['evaluate_score4']) {
	$grade_type = "v3";
	//-- 개정 등급 산정방식 v3--------------------------------------------------//
	$level_score = round(($PRDT["evaluate_score1"] + $PRDT["evaluate_score3"] + $PRDT["evaluate_score4"]) / 5);
	$grade = $_gudge_grade_array[$level_score];
	$evaluate_score1 = round($PRDT["evaluate_score1"]/40*100);
	$evaluate_score3 = round($PRDT["evaluate_score3"]/30*100);
	$evaluate_score4 = round($PRDT["evaluate_score4"]/30*100);
	//-- 개정 등급 산정방식 v3--------------------------------------------------//
}

// 대출실행 완료건에 대하여 이자지급 차수 표시
if($PRDT['loan_start_date'] && $PRDT['loan_start_date']!='0000-00-00') {
	$loan_start_date_day = (int)substr($PRDT['loan_start_date'], -2);
	$total_repay_count = ($loan_start_date_day > 1) ? $PRDT['invest_period'] + 1 : $PRDT['invest_period'];  //총 지급횟수
	$PAIED = sql_fetch("SELECT MAX(turn) as max_turn FROM cf_product_success WHERE product_idx='".$PRDT['idx']."' AND invest_give_state='Y'");
	$repay_count = ($PAIED['max_turn']) ? $PAIED['max_turn'] : 0;
}

// 슬라이드 구현을 위한 상품이미지 배열화

// 차주의 요쳉에 의한 과거 상품포함 이미지 교체 (보고플레이) 20220607
if (preg_match("/온라인 쇼핑몰 확정매출채권/",$PRDT['title']) AND $prd_idx<="9062") $PRDT["detail_image"]="online_pc.jpg";

$DTLIMG_ARR  = explode("|", $PRDT["detail_image"]);
for($i=0; $i<count($DTLIMG_ARR); $i++) {
	$DTLIMG_ARR[$i] = trim($DTLIMG_ARR[$i]);
	if(is_file(G5_DATA_PATH."/product/".$DTLIMG_ARR[$i])) {
		$PRDTIMG[] = G5_DATA_URL."/product/".$DTLIMG_ARR[$i];
	}
}

if ($_SERVER['REMOTE_ADDR']=="220.117.134.166") {
	//echo "<br/><br/><br/>";print_rr($DTLIMG_ARR);
	//echo $PRDT["detail_image"];
}

// 대표 이미지
if(!count($PRDTIMG) && count($PRDTIMG) <= 0) {
	if(!G5_IS_MOBILE) {
		if($PRDT["main_image"]!="" && is_file(G5_DATA_PATH."/product/".$PRDT["main_image"])) {
			$PRDTIMG[] = G5_DATA_URL."/product/".$PRDT["main_image"];
			$title_image_size = fileSize(G5_DATA_PATH."/product/".$PRDT["main_image"]);
		}
	}
	else {
		if($PRDT["main_image_m"] != "" && is_file(G5_DATA_PATH . "/product/" . $PRDT["main_image_m"])) {
			$PRDTIMG[] = G5_DATA_URL . "/product/" . $PRDT["main_image_m"];
			$title_image_size = fileSize(G5_DATA_PATH . "/product/" . $PRDT["main_image_m"]);
		}
	}
}

$product_image_count = count($PRDTIMG);

if($PRDT['main_image']) {
	if( file_exists(G5_DATA_PATH . "/product/" . $PRDT['main_image']) && filesize(G5_DATA_PATH . "/product/" . $PRDT['main_image']) ) {
		$PRDT['title_image_url'] = G5_DATA_URL."/product/" . $PRDT['main_image'];
	}
}

if($PRDT['main_image_m']) {
	if( file_exists(G5_DATA_PATH . "/product/" . $PRDT['main_image_m']) && filesize(G5_DATA_PATH . "/product/" . $PRDT['main_image_m']) ) {
		$PRDT['title_image_url_m'] = G5_DATA_URL."/product/" . $PRDT['main_image_m'];
		// 차주의 요쳉에 의한 과거 상품포함 이미지 교체 (보고플레이) 20220607
		if (preg_match("/온라인 쇼핑몰 확정매출채권/",$PRDT['title']) AND $prd_idx<="9062") $PRDT['title_image_url_m'] = G5_DATA_URL."/product/online_mobile.jpg" ;
	}
}

// 모집시작시간
$start_timestamp  = strtotime($PRDT["start_datetime"]);
$print_sdate = date('Y년 m월 d일', $start_timestamp);
$print_sdate.= ' '.get_yoil($PRDT["start_datetime"]).'요일 ';
$print_sdate.= (date(H, $start_timestamp) < 12) ? ' 오전 ' : ' 오후 ';
$print_sdate.= date('h:i', $start_timestamp);

// 최신 새상품여부
$new_flag = (G5_TIME_YMD <= date('Y-m-d', strtotime('+5day', strtotime($PRDT['open_datetime']))) && ($PRDT['recruit_amount'] > $PRDT['total_invest_amount'])) ? 'Y' : 'N';

// 몇호 상품인지 제목 구분
$titleAndSubject = ($PRDT['title']) ? extractText($PRDT['title']) : $PRDT['title'];
$productNum = ($titleAndSubject[0]) ? $titleAndSubject[0] : $PRDT['title'];
$productTitle = ($titleAndSubject[1]) ? $titleAndSubject[1] : $PRDT['title'];

// 모집금액
/*
$recruit_amount = 0;
$recruit_amount_unit = '원';
if($PRDT["recruit_amount"] > 0) {
	$tmpRecruitData = wonFormat($PRDT["recruit_amount"]);
	$recruit_amount = ($tmpRecruitData[0]) ? $tmpRecruitData[0] : $PRDT["recruit_amount"];
	$recruit_amount_unit = ($tmpRecruitData[1]) ? $tmpRecruitData[1] : null;
}
*/

// 투자수익률
$invest_return = floatRtrim($PRDT['invest_return']);

// 예상 순수익률 계산
$est_amount   = (($PRDT['recruit_amount']*$PRDT['invest_return']/365)/100)*365;  // 예상 당월 이자
$platform_tax = (($PRDT['recruit_amount']*$PRDT['invest_usefee']/365)/100)*365;  // 플랫폼 이용료
$interest_tax = $est_amount * 0.14;												 // 소득세
$local_tax    = $interest_tax * 0.1;											 // 지방세
$real_amount  = $est_amount - ($platform_tax + $interest_tax + $local_tax);		 // 실수령액
$profit_perc  = floatRtrim(@sprintf("%.2f",($real_amount / $PRDT['recruit_amount']) * 100));  // 예상 순수익률

// 상환방식
$repay_pay_title = "";
if($PRDT['repay_type'] == 1)	    $repay_pay_title = "만기일시상환";
else if($PRDT['repay_type'] == 2) $repay_pay_title = "원리금균등상환";
else if($PRDT['repay_type'] == 3) $repay_pay_title = "원금균등상환";
else if($PRDT['repay_type'] == 4) $repay_pay_title = "원리금 만기일시상환";


// LIVE TV 버튼 클릭시 보여줄 임시 이미지 지정상품 배열
$TMP_IMG_PRDT = array(
	'228',		//인천 신흥동 오피스텔 준공자금
	'205','207',		//대구 이시아폴리스 메가맥스타워 유동화자금 라이브 오류로 인한 임시처리
	'174','212','301',		//남양주시 별내동 다가구 주택 건축자금 라이브 오류로 인한 임시처리
	'402',		//원주 기업도시 근린생활시설 준공자금 라이브 종료 처리
	'321','299',		//경기도 양주시 다세대주택 준공자금
	'462','509','553',	//면목동 쉐어하우스 준공자금
	'735','674','577','523','478',		//경기도 남양주시 화도읍 다세대주택 신축사업
	'233','262','328','364','367','389','507','524',		//여수 봉산동 다세대주택 준공자금
	'421','445','449',		//경기 가평군 현리 주상복합오피스텔
	'458','483','502','620',		//경기도 가평군 묵안리 단독주택(풀빌라) 준공자금
	'279','281','336','342','363','372','375','387','409','416','434','442','448','457','460','464','493','536','599','699','848','1162','1225','1226',		//제주 신화역사공원 다세대주택 준공자금
	'548','578','627','628','700','886','1133',		//제주 영어교육도시 다세대
	'426','476','489','492','987', //천안시 사직동 주상복합
	'519','592','840','1150','1354',		//천안시 사직동 주상복합
	'1563','1625','1816','1932','2109','2319','2516',		//영종도 하늘도시
	'1550','1729','1854','1883','2069','2243','2621',		//다산신도시 역세권 근생
	'2013','2258','2410','2827','3100','3351',		//서초구다세대
	'1211','1484','1672','1843','2029','2123','2286','2495','3108','3286','3553','3554','3409','3555',		//화곡동까치산역 오피스텔
	'3107','832',		//신림 역세권 숙박시설
	'3512','3824','4306','4515','4630','4745',		//상봉 도시형생활주택
	'4222','4159','1730',		//용인 주상복합
	'1014','1093','3225','3247','3248',		//신림역 주상복합
	'3271','3323','3577','3765','4074','4295','4472','4482','4734','5156','5340','5577',		//여수 충무동
	'4629','4703','4723','4780','4933','2145','2228','2411','2742','2997','3208','3366','3510','3731','4091','4273','4519','5230','5361','5545','5793','5908','6025',		//광명시 광명동 주택
	'1232','1300','1731','1925','2102','2284','2448','2677','2899','3186','3331','3462','3472','3493','3551','3550','3788','4063','4253','4445','4694','4791','4912','4934','5116','5309','5513','5759',		//수원인계동
	'1533','3982','4157','5039','5219','5402','5663','5829','6093','6364','6585','6653','6735','7055','7272','7554','7845',		//의정부 역세권
	'3720','3787','3810','3823',		//성동구 오피스텔
	'4396','5250',		//화곡동 단지 다세대
	'6677','6234','5932','5828','5599','5442','5362','5240','5169','4744','4536','4045',		//용산구 다세대
	'6282','4867','4835','4824','2363','2244',		//광진구 화양동 오피스텔
	'6186','5805','5695','5490','5308','5097','4889','4666','4407','4384','4383','4381','4243','4044','3764','3511','3401','3155','2898','1782',		//로데오거리 아파트
	'6854','6853','6830','6734','6427','5600','5391','5350','5179','5000','4792','4340','4065','3983','3911','3641','3390','3307','3226','2801','2379','2362','2190','1884','1627',		//광주 우산동
	'5588'		//용인 수지구 신봉동
);

if( in_array($prd_idx, $TMP_IMG_PRDT) ) { $PRDT['stream_url1'] = 'ready'; }

// 실시간 카메라 스트림
if($PRDT['stream_url1']) {
	if($PRDT['stream_url1']=='ready') {
		$live_link = "openStreamReady();";  // /popup/inc_stream_ready.php 에 함수 정의
	}
	else {
		$play_url = "http://hellolivetv.co.kr/onair.php?prd_idx=".$prd_idx;
		$play_url.= (preg_match("/dev.hellofunding/", $_SERVER['HTTP_HOST'])) ? "&mode=test" : "";
		if(G5_IS_MOBILE) {
			$live_link = "window.open('".$play_url."','stream_win','toolbar=0,menubar=0,status=0,scrollbars=0,resizable=0');";
		}
		else {
			$live_link = "window.open('".$play_url."','stream_win','width=730,height=500,toolbar=0,menubar=0,status=0,scrollbars=0,resizable=0');";
		}
	}
}

// 상단 출력용 모집금액 형식 설정
$print_recruit_amount = price_cutting($PRDT['recruit_amount']);
$print_recruit_amount = preg_replace("/억/", "<b>억</b>", $print_recruit_amount);
$print_recruit_amount = preg_replace("/천/", "<b>천</b>", $print_recruit_amount);
$print_recruit_amount = preg_replace("/만/", "<b>만</b>", $print_recruit_amount);


// 투자기간 표기
//$print_invest_period = ($PRDT['invest_days'] > 0 && $PRDT['invest_days'] < 30) ? $PRDT['invest_days'] . '일' : $PRDT['invest_period'] . '개월';

// 투자기간 표기 변경 : 2018-02-19
if($PRDT['invest_period']==1 && $PRDT['invest_days'] > 0) {
	$invest_period = $PRDT['invest_days'];
	$invest_period_unit = '일';
}
else {
	$invest_period = $PRDT['invest_period'];
	$invest_period_unit = '개월';
}


///////////////////////////////
// 핀크용 스킨 적용 분기
///////////////////////////////
if($syndi_id=='finnq') {
	$inc_file = "investment_finnq";
	$inc_file.= ($prd_idx <= 244) ? "_old" : "";
	$inc_file.= ".php";
	include_once($inc_file);
	return;
}

if($co['co_include_head']) @include_once($co['co_include_head']);
else include_once('./_head.php');

///////////////////////////////
// 모바일 분기
///////////////////////////////
if(G5_IS_MOBILE) {
	if($prd_idx <= 244) {
		include_once('./investment.m.old.php');
	}
	else {
		include_once('./investment.m.php');
	}
	return;
}
else {
	if($prd_idx <= 244) {
		include_once('./investment.old.php');
		return;
	}
}

$thisPageUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

add_stylesheet('<link rel="stylesheet" type="text/css" href="/investment/css/investment_info.css?ver=20220620">', 0);

switch($PRDT['category']) {
	case '1' : $cFlag = '<li class="p_ca-B">동산</li>'; break;
	case '2' : $cFlag = ($PRDT['mortgage_guarantees']=='1') ? '<li class="p_ca-A2">주택담보</li>' : '<li class="p_ca-A">부동산</li>'; break;
	case '3' : $cFlag = '<li class="p_ca-C">SCF</li>'; break;
	default  : $cFlag = ''; break;
}

$aiFlag  = ($PRDT['ai_grp_idx']>0) ? '<li class="p_ai">자동투자</li>' : '';
$newFlag = ($PRDT['new_flag']=='Y') ? '<li class="p_new">N</li>' : '';
$srmFlag = ($PRDT["stream_url1"] OR $PRDT["stream_url2"]) ? '<li class="p_live_tv"  onClick="'.$live_link.'"><i class="fa fa-tv"></i> LIVE TV</li>' : '';
$adiFlag = ($PRDT['advance_invest']=='Y') ? '<li class="p_adir">사전투자 ' . floatRtrim($PRDT['advance_invest_ratio']).'% <i class="fa fa-question-circle" id="question_1"></i></li>' : '';
$pgFlag  = ($PRDT['purchase_guarantees']=='Y' && preg_match("/dev\.hello/", G5_URL)) ? '<li class="p_pg">채권매입계약</li>' : '';
$adpFlag = ($PRDT['advanced_payment']=='Y') ? '<li class="p_adpy">이자 선지급</li>' : '';
$conFlag = ($PRDT['isConsor']=='1') ? '<li class="p_con">컨소시엄</li>' : '';

$NAME_TAG = explode("|", $PRDT['name_tag']);
$nameTagFlag1 = (in_array("권원보험", $NAME_TAG)) ? '<li class="p_ai" style="background:#ff9900">권원보험</li>' : '';
$nameTagFlag2 = (in_array("매입확약", $NAME_TAG)) ? '<li class="p_ai" style="background:#cc00ff">매입확약</li>' : '';
?>

	<input type="hidden" id="prd_idx" name="prd_idx" value="<?=$prd_idx;?>">
	<input type="hidden" id="invest_finished" name="invest_finished" value="<?=($invest_finished) ? 'true' : 'false'; ?>">

	<div class="product_cont">
		<div class="main_image">
		<? if($PRDTIMG[0]) { ?><img src="<?=$PRDTIMG[0]?>" alt="<?=$print_sdate;?>"><? } ?>
		</div>
		<div class="product_wrapper">
			<div class="product_info">
				<!-- <span class="numb"><? echo ($productNum) ? '['.$productNum.']' : '';?></span> //-->
				<div class="p_flags">
					<ul>
						<?=$cFlag?><?=$aiFlag?><?=$conFlag?><?=$newFlag?><?=$srmFlag?><?=$adiFlag?><?=$pgFlag?><?=$adpFlag?><?=$nameTagFlag1?><?=$nameTagFlag2?>
					</ul>
				</div>
				<span class="titles"><?=$PRDT['title']?></span>
				<span class="date">모집기간 : <?=$print_sdate?> ~</span>

				<!-- 상품 현황 -->
				<span class="percent">
					<strong>예상 투자수익률(연)</strong><br>
					<?=$invest_return?><b>%</b>
				</span>
				<span class="percent">
					<strong>투자기간</strong><br>
					<?=$invest_period?><b><?=$invest_period_unit?></b>
				</span>
				<span class="percent">
					<strong>모집금액</strong><br>
					<?=$print_recruit_amount?><b>원</b>
				</span>
			</div>
			<ul class="sns_share">
				<li>
					<a href="#" data-toggle="sns_share" data-service="facebook" data-title="페이스북 SNS공유">
						<img src="<?=G5_THEME_IMG_URL?>/sub/sns_f_btn01.png" alt="facebook">
					</a>
				</li>
				<li>
					<a href="#" data-toggle="sns_share" data-service="naver" data-title="네이버 SNS공유">
						<img src="<?=G5_THEME_IMG_URL?>/sub/sns_b_btn01.png" alt="naver">
					</a>
				</li>
				<li>
					<a href="#" data-toggle="sns_share" data-service="kakaostory" data-title="카카오스토리 SNS공유">
						<img src="<?=G5_THEME_IMG_URL?>/sub/sns_k_btn01.png" alt="kakao">
					</a>
				</li>
				<!--
				<li>
					<a href="#" data-toggle="sns_share" data-service="instagram" data-title="인스타그램 SNS공유">
						<img src="<?=G5_THEME_IMG_URL?>/sub/sns_i_btn01.png" alt="instagram">
					</a>
				</li>
				//-->
				<li>
					<a href="#" data-toggle="sns_share" data-service="url_copy" data-title="주소복사하기">
						<img src="<?=G5_THEME_IMG_URL?>/investment/url_icon.png" alt="url_copy">
					</a>
				</li>
			</ul>
			<div class="process_wrap">
				<div class="process">
					<div id="progressLayer" class="process_tag" style="left:<?=($product_invest_percent <= 100) ? $product_invest_percent - 11.7 : 88.2;?>%">
<? if(false) { ?>
<!--
						<span>투자모집률 / 모집된 금액</span>
						<strong class="p_t_n" id="progressData"><?=$product_invest_percent;?>%</strong> / <strong class="p_t_t" id="totalRecruitValue"><?=price_cutting($PRDT["total_invest_amount"]+0);?>원</strong>
-->
<? } ?>
						<span>투자모집률 / 투자가능금액</span>
						<strong class="p_t_n" id="progressData"><?=$product_invest_percent;?>%</strong> / <strong class="p_t_t" id="totalRecruitValue"><?=price_cutting($PRDT['need_recruit_amount']);?>원</strong>

					</div>
					<div id="progressBar" class="process_bar" style="width:<?=($product_invest_percent <= 100) ? $product_invest_percent : 100;?>%"></div>
					<div class="process_total">모집금액 : <?=($PRDT["recruit_amount"]) ? number_format($PRDT["recruit_amount"]) : 0;?>원</div>
				</div>
			</div>
			<div class="process_btn">
				<ul id="processBtn">

					<? if($invest_finished == false) { ?>
					<!--<li class="simulation"><a href="/investment/simulation.php?prd_idx=<?=$PRDT['idx']?>" class="btn_big_link">투자시뮬레이션</a></li>-->
					<? } ?>

					<? if($invest_button) { ?>
					<li class="invest"><?=$invest_button?></li>
					<? } ?>

					<? if($PRDT['ai_grp_idx']) { ?>
					<!--li class="auto_invest"><a href="/deposit/deposit.php?tab=5" class="btn_big_orange">자동투자설정</a></li-->
					<? } ?>

					<? if($advance_invest_button) { ?>
					<li class="reser_invest"><?=$advance_invest_button?></li>
					<? } ?>

					<? if(!$is_member && $invest_finished) { ?>
					<li><a id="reqsms_btn2" class="btn_big_blue">다음 상품 알림받기</a></li>
					<? } ?>

				</ul>
			</div>

			<? if($PRDT['product_summary']) { echo $PRDT['product_summary']; } ?>
		</div>
	</div>

	<!-- 예상 수익금-->
	<div class="pre_earn" style="margin-bottom:-100px;">
		<!--p class="pre_earn_tit">예상 수익금</p-->
		<ul class="pre_earn_c">
			<li>지금 이 상품에</li>
			<li><input type="text" name="principal_value" value="<?=number_format(5000000)?>" maxlength="11" placeholder="투자금액을 입력하세요. 예) 1,000,000원" onkeyup="formatNumber(this);simulation();"></li>
			<li>원을 투자하시면</li>
			<!--<li class="equal"><a href="javascript:;" onclick="simulation();">계산하기</a></li>-->
		</ul>
		<div class="earn_info">
			<p class="earn_btn">
				예상 총 실수익금(세후) <span id="earninfo1-claim-mark" class="claim-mark">?</span><br/>
				<strong id="ajxTotalInterestPrice">0</strong>원
			</p>
<? if($PRDT['invest_period'] > 1) { /* 2018-12-07 이정환 차장 요청으로 블락처리 */ ?>
			<p class="earn_btn <?=($PRDT['open_datetime'] < '2018-08-31 09:00:00')?'blind':'';?>" style="width:1px;height:1px;overflow:hidden;">
				월 평균 예상 수익금 지급액 <span id="earninfo2-claim-mark" class="claim-mark">?</span><br/>
				<strong id="ajxInvestMonth">0</strong>개월 동안 매월 <strong id="ajxMonthAvrPrice">0</strong>원
			</p>
<? } ?>
			<p class="earn_btn <?=($PRDT['open_datetime'] < '2018-08-31 09:00:00')?'blind':'';?>">
				은행예금 대비 수익<span id="earninfo3-claim-mark" class="claim-mark">?</span><br/>
				<strong id="ajaDiffEarning">0</strong>배
			</p>
		</div>
		<div class="simulation_detail_btn" onClick="location.href='simulation.php?prd_idx=<?=$prd_idx?>';">투자시뮬레이션 자세히보기 > </div>
	</div>
	<script type="text/javascript">
		var msg = "본 상품의 투자금액에 따른 수익금에서 세금과 플랫폼 이용료를 제외한 금액이며, 조기상환 등 투자기간 변동에 의해 실제와 다를 수 있습니다.";
		$('#earninfo1-claim-mark').webuiPopover({ title: "예상 총 실수익금(세후)", content: msg, closeable: true, width: 330, height: 70, trigger: "click", placement: 'bottom', backdrop: false});
		var msg = "투자기간 중 헬로펀딩이 매월 지급해 드리는 세후 수익금으로, 이자산정일에 따라 변동될 수 있습니다.";
		$('#earninfo2-claim-mark').webuiPopover({ title: "월 평균 지급수익금 ", content: msg, closeable: true, width: 330, height: 50, trigger: "click", placement: 'bottom', backdrop: false});
		var msg = "1금융권 정기예금 평균 금리 1.7% 대비 본 투자상품의 수익률입니다. (각 세후 실수익 기준)";
		$('#earninfo3-claim-mark').webuiPopover({ title: "은행에 예금시보다 ", content: msg, closeable: true, width: 330, height: 50, trigger: "click", placement: 'bottom', backdrop: false});
	</script>


	<!-- 헬로펀딩 이벤트(220501 임시 주석처리) -->
	<div class="product_description mt120">

	<div class="new-event" style="display: flex; margin:30px auto 50px; text-align: center; width:1150px; padding-top:10px;">
	<div style="margin-right: 30px;">
		<a href="/review/review_event/review_2205.php"><img src="/theme/2018/img/new/new_main_banner_01.jpg" alt="투자후기이벤트"></a>
	</div>
	<div>
		<a href="/event/2205/"><img src="/theme/2018/img/new/new_main_banner_02.jpg" alt="친구초대이벤트"></a>
	</div>
	</div>

<!-- **
<?php
$gstrHelloBanner = NEW Hello_Banner();
$gstrHelloBanner->CODE = "0004";
$strVal = $gstrHelloBanner->RsContent();
?>
		<p class="product_info_cont"><a href="<?php ECHO unique_un_replace($strVal[0]["targeturl"])?>"><img src="<?php ECHO "/data/event/".$strVal[0]["repimg"]?>" /></a></p>


<!--?php
$gstrHelloBanner->CODE = "0006";
$strVal2 = $gstrHelloBanner->RsContent();
?>
		<p class="product_info_cont"><a href="<?php ECHO unique_un_replace($strVal2[0]["targeturl"])?>"><img src="<?php ECHO "/data/event/".$strVal2[0]["repimg"]?>" /></a></p>
		<!--<p class="product_info_cont"><a href="/event/nhcma_event.php"><img src="/evnt/nhCMA/event_banner_web.jpg"></a></p>//-->


	</div>


	<?
		if(trim($PRDT['core_invest_point'])) {
			//echo $PRDT['core_invest_point'];

			$core_invest_point = $PRDT['core_invest_point'];

			// 이미지를 프래임 내부에서 호출되도록 하기 위한 작업
			$str = preg_replace("/<p>/i", "", $core_invest_point);
			$_ARR = explode("</p>", $str);
			for($i=0; $i<count($_ARR); $i++) {
				$target_string = trim( str_f6($_ARR[$i], "<a href=\"", "\" rel=\"noopener noreferrer\" target=\"_blank\">") );
				$target_string = preg_replace("/(\\r|\\n)/", "", $target_string);

				if($target_string) {
					$change_string = "/hello/image/" . preg_replace("/\=/", "hello", base64_encode($target_string)).".html\n";
				//$change_string = "/hello/image/" . preg_replace("/\=/", "hello", base64_encode($target_string))."&".time()."\n";

					$arg0 = "/".preg_replace("/\//", "\/", $target_string)."/i";

					$core_invest_point = preg_replace($arg0, $change_string, $core_invest_point);
				}
			}
			//$core_invest_point = preg_replace("/_blank/i", "_self", $core_invest_point);
			$core_invest_point = preg_replace("/(href=\"#\"|href='#')/i", "href='javascript:;'", $core_invest_point);

			echo $core_invest_point."\n";


		}
	?>


	<!-- 상품 개요 -->
	<div class="product_description">
		<p class="product_info_tit">상품 개요</p>
		<p class="product_info_cont">
		<span>
			투자모집액
			<strong><?=price_cutting($PRDT['recruit_amount']);?>원</strong>
		</span>
		<span>
			예상 투자 수익률
			<strong>연 <?=$invest_return?>%</strong>
		</span>
		<? if($PRDT['open_datetime'] >= '2021-10-27 00:00:00') { // 지정된 날짜 이후부터 출력 ?>
		<span>
			예상 순수익률
			<strong><?=$profit_perc;?>%</strong>
		</span>
		<? } ?>
		<span>
			투자기간
			<strong><?=$invest_period.$invest_period_unit;?></strong>
		</span>
		<span>
			상환방법
			<strong><?=$repay_pay_title;?></strong>
		</span>
		</p>

		<? if($description = nl2br($PRDT['product_description'])) { // 상품설명 ?>
		<p class="product_info_cont_c">
			<?=$description; ?>
		</p>
		<? } ?>

	</div>

	<!-- 실시간 현장 라이브 -->
	<? if($live_link) { ?>
	<div class="hello_live">
		<img src="<?=G5_THEME_IMG_URL?>/sub/live_tv_banner01.jpg" alt="실시간 현장 방송" onClick="<?=$live_link?>">
	</div>
	<? } ?>

	<!-- 예상 수익금 원래 자리 -->

	<!-- 안전장치 업데이트 -->
<?
	if(trim($PRDT['extend_8'])) {

		$extend_8 = $PRDT['extend_8'];

		// 이미지를 프래임 내부에서 호출되도록 하기 위한 작업
		$str = preg_replace("/<p>/i", "", $extend_8);
		$_ARR = explode("</p>", $str);
		for($i=0; $i<count($_ARR); $i++) {
			$target_string = trim( str_f6($_ARR[$i], "<a class=\"fr-file\" href=\"", "\" target=\"_blank\">") );
			$target_string = preg_replace("/(\\r|\\n)/", "", $target_string);

			if($target_string) {
				$change_string = "/hello/image/" . preg_replace("/\=/", "hello", base64_encode($target_string)).".html\n";
			//$change_string = "/hello/image/" . preg_replace("/\=/", "hello", base64_encode($target_string))."&".time()."\n";

				$arg0 = "/".preg_replace("/\//", "\/", $target_string)."/i";

				$extend_8 = preg_replace($arg0, $change_string, $extend_8);
			}
		}
		//$extend_8 = preg_replace("/_blank/i", "_self", $extend_8);
		$extend_8 = preg_replace("/(href=\"#\"|href='#')/i", "href='javascript:;'", $extend_8);

		echo $extend_8."\n";

	}
?>

	<!-- 신한은행 배너 -->
<? if($prd_idx < '10801') { ?>
	<div class="shinhan_ban" style="clear:both;"><img src="/theme/2018/img/sub/shinhan_ban01.jpg"></div>
<? } ?>

	<div id="detail_box" class="detail_box">
<?
	if(trim($PRDT['invest_summary'])) {

		$invest_summary = $PRDT['invest_summary'];

		// 이미지를 프래임 내부에서 호출되도록 하기 위한 작업
		$str = preg_replace("/<p>/i", "", $invest_summary);
		$_ARR = explode("</p>", $str);
		for($i=0; $i<count($_ARR); $i++) {
			$target_string = trim( str_f6($_ARR[$i], "<a class=\"fr-file\" href=\"", "\" target=\"_blank\">") );
			$target_string = preg_replace("/(\\r|\\n)/", "", $target_string);

			if($target_string) {
				$change_string = "/hello/image/" . preg_replace("/\=/", "hello", base64_encode($target_string)).".html\n";
			//$change_string = "/hello/image/" . preg_replace("/\=/", "hello", base64_encode($target_string))."&".time()."\n";

				$arg0 = "/".preg_replace("/\//", "\/", $target_string)."/i";
				$invest_summary = preg_replace($arg0, $change_string, $invest_summary);
			}
		}

		//$invest_summary = preg_replace("/_blank/i", "_self", $invest_summary);
		$invest_summary = preg_replace("/(href=\"#\"|href='#')/i", "href='javascript:;'", $invest_summary);

		echo $invest_summary."\n";

	}
?>

		<!-- 증빙 서류 -->
<?
	if(trim($PRDT['extend_9'])) {

		$extend_9 = $PRDT['extend_9'];

		// 이미지를 프래임 내부에서 호출되도록 하기 위한 작업
		$str = preg_replace("/<p>/i", "", $extend_9);
		$_ARR = explode("</p>", $str);
		for($i=0; $i<count($_ARR); $i++) {
			$target_string = trim( str_f6($_ARR[$i], "href=\"", "\" target=\"_blank\">") );
			$target_string = preg_replace("/(\\r|\\n)/", "", $target_string);

			if($target_string) {
				$change_string = "/hello/image/" . preg_replace("/\=/", "hello", base64_encode($target_string)).".html\n";
			//$change_string = "/hello/image/" . preg_replace("/\=/", "hello", base64_encode($target_string))."&".time()."\n";

				$arg0 = "/".preg_replace("/\//", "\/", $target_string)."/i";
				$extend_9 = preg_replace($arg0, $change_string, $extend_9);
			}
		}

		//$extend_9 = preg_replace("/_blank/i", "_self", $extend_9);
		$extend_9 = preg_replace("/(href=\"#\"|href='#')/i", "href='javascript:;'", $extend_9);

		echo $extend_9."\n";

	}

?>

		<? if($PRDT['extend_7']) { ?>
			<?=$PRDT['extend_7']?>
		<? } ?>
	</div>

<?
if ($PRDT['address']) {
	?>
	<script src="//dapi.kakao.com/v2/maps/sdk.js?appkey=a1a12feb2e53aac7f2424691b4532110&libraries=services"></script>
	<script>
	if ($('#area_kakaomap').length) {
		$("#area_kakaomap").append("<div id='kakao_map' style='width:740px; height:450px;border:1px solid #ADADAD;margin:0 auto 15px;'></div>");
		var container = document.getElementById('kakao_map');
		var options = {
			center: new kakao.maps.LatLng(33.450701, 126.570667),
			level: 3
		};
		var map = new kakao.maps.Map(container, options);

		// 주소-좌표 변환 객체를 생성합니다
		var geocoder = new kakao.maps.services.Geocoder();

		// 주소로 좌표를 검색합니다.
		geocoder.addressSearch('<?=$PRDT[address]?>', function(result,status) {

			// 정상적으로 검색이 완료됐으면
			if (status === kakao.maps.services.Status.OK) {

				var coords = new kakao.maps.LatLng(result[0].y , result[0].x);

				// 결과값으로 받은 위치를 마커로 표시합니다.
				var marker = new kakao.maps.Marker({
					map: map,
					position: coords
				});

				// 인포윈도우로 장소에 대한 설명을 표시합니다
				var infowindow = new kakao.maps.InfoWindow({
					content: '<div style="width:150px;text-align:center;padding:6px 0;">우리회사</div>'
				});
				//infowindow.open(map, marker);

				// 지도의 중심을 결과값으로 받은 위치로 이동시킵니다
				map.setCenter(coords);
			}

		});
	}
	</script>
	<?
}
if ($PRDT['loadview_url'] and preg_match('/\bkakao\b/i', $PRDT['loadview_url'] ,$matches)) {
	$tmp1 = explode("?",$PRDT['loadview_url']);
	parse_str($tmp1[1]);
	//echo "panoid (고유값) = > $panoid<br/>";
	//echo "pan (수평각) => $pan<br/>";
	//echo "tile (수직각) => $tilt<br/>";
	//echo "zoom (확대) => $zoom<br/>";
	?>
	<script>
	function isFlashEnabled()
	{
		var hasFlash = false;
		try
		{
			var fo = new ActiveXObject('ShockwaveFlash.ShockwaveFlash');
			if(fo) hasFlash = true;
		}
		catch(e)
		{
			if(navigator.mimeTypes ["application/x-shockwave-flash"] != undefined) hasFlash = true;
		}
		return hasFlash;
	}
	var flash_yn = isFlashEnabled();
	</script>
	<script src="//dapi.kakao.com/v2/maps/sdk.js?appkey=a1a12feb2e53aac7f2424691b4532110"></script>
	<script>
		$(".prdt_summ").append("<div id='kakao_roadview' style='width:90%; height:500px;border:1px solid black;margin:0 auto 5px;'></div><div style='width:888px;margin:5px auto 30px;text-align:center;'>화면을 클릭한 후 상하좌우로 움직여서 현장을 확인하세요 !</div>");
		//로드뷰를 표시할 div
		var roadviewContainer = document.getElementById('kakao_roadview');

		if (flash_yn) {
			//로드뷰 객체를 생성한다
			var roadview = new kakao.maps.Roadview(roadviewContainer, {
				panoId : <?=$panoid?>, // 로드뷰 시작 지역의 고유 아이디 값
				pan: <?=$pan?>, // 로드뷰 처음 실행시에 바라봐야 할 수평 각
				tilt: <?=$tilt?>, // 로드뷰 처음 실행시에 바라봐야 할 수직 각
				zoom: <?=$zoom?> // 로드뷰 줌 초기값
			});
		} else {
			$("#kakao_roadview").css('background-image','url("/images/bg_pattern.jpg")');
			$("#kakao_roadview").html("<div style='text-align:center;width:290px;height:86px;margin:20% auto;'><b>로드뷰 서비스를 이용하시려면<br/>Adobe Flash Player 설치 및 허용이 필요합니다.<br/><br/><a href='http://get.adobe.com/flashplayer/' target='_blank'>[최신버전 다운로드]</a></b></div>");
		}
	</script>
	<?
} else if ($PRDT['loadview_url'] and preg_match('/\bnaver\b/i', $PRDT['loadview_url'] ,$matches)) {
	// 네이버 파노라마(로드뷰) 2018-07-18 전승찬 추가
	/*
	$tmp1 = explode("?",$PRDT['loadview_url']);
	parse_str($tmp1[1]);
	?>
	<script type="text/javascript" src="https://openapi.map.naver.com/openapi/v3/maps.js?clientId=wgdMUaKHdFdJ8M6hMFJ_&submodules=panorama"></script>
	<script type="text/javascript">
		$(".prdt_summ").append("<div id='pano' style='width:100%; height:600px;border:1px solid black;margin:0 auto 5px;'></div><div style='width:888px;margin:5px auto 30px;text-align:center;'>화면을 클릭한 후 상하좌우로 움직여서 현장을 확인하세요! &nbsp;&nbsp;&nbsp;<a onclick='load_naver_map();' style='cursor:pointer;font-weight:bold;'>[로드뷰 초기화는 <span style='text-decoration:underline;'>여기</span>를 클릭해 주세요.]</a></div>");

		function load_naver_map() {
			var pano = new naver.maps.Panorama(document.getElementById("pano"), {
				size               : new naver.maps.Size(888, 600),
				panoId             : "<?=$vrpanoid?>",
				pov                : {pan : <?=$vrpanopan?>, tilt : <?=$vrpanotilt?>, fov : <?=$vrpanofov?> },
				aroundControl      : true,
				MapDataControl     : true,
				zoomControl        : true,
				zoomControlOptions : {position: naver.maps.Position.TOP_RIGHT}
			});
		}
		load_naver_map();
	</script>
	<?
	*/
}
?>


<?
	// 이전 월 말일
	$date = date("Y.m");
	$d = mktime(0,0,0, date("m"), 1, date("Y"));
	$prev_date = strtotime("-1 month", $d);
	$tmp_date = date('Y-m-t', $prev_date);
	$ym = str_replace('-', '.', substr($tmp_date, 0, 7));
?>

	<!--  공시정보 추가 -->
	<div class="gongsi_info_box">
		<div class="inner-wrap">
			<h3>헬로펀딩 법정공시정보 <span class="date-txt"><?=$ym." 말일 기준"?></span></h3>
			<h4>투자현황</h4>
			<div class="tb-wrap">
				<table class="gongsi-data">
					<colgroup>
						<col style="width:33.3%">
						<col style="width:33.3%">
						<col style="width:33.3%">
					</colgroup>
					<thead>
						<tr>
							<th>누적 대출금액</th>
							<th>대출잔액</th>
							<th>연체율 <span>(%)</span></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><div id="tot_amt" class="tot_amt"></div></td>
							<td><div id="tot_remain" class="tot_remain"></div></td>
							<td><div id="overdue_rate" class="overdue_rate"></div></td>
						</tr>
					</tbody>
				</table>
				<table class="gongsi-data">
					<colgroup>
						<col style="width:33.3%">
						<col style="width:33.3%">
						<col style="width:33.3%">
					</colgroup>
					<thead>
						<tr>
							<th>연체건수 <span>(건)</span></th>
							<th>누적 자기계산 투자현황</th>
							<th>자기계산 투자현황</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><div id="overdue_cnt" class="overdue_cnt"></div></td>
							<td><div id="hello_nujuk_invest_amt" class="hello_nujuk_invest_amt"></div></td>
							<td><div id="hello_live_invest_amt" class="hello_live_invest_amt"></div></td>
						</tr>
					</tbody>
				</table>
			</div>

			<h4>유형별 투자현황</h4>
			<div class="tb-wrap">
				<table class="type-invest">
					<thead>
						<tr>
							<th>상품 유형</th>
							<th style="text-align: right; width: 20%;">누적 대출금액 <span>(원)</span></th>
							<th style="text-align: right; width: 27%;">대출잔액 <span>(원)</span></th>
							<th>연체율 <span>(%)</span></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<th>부동산 PF</th>
							<td><div id="pf_tot_amt" class="pf_tot_amt"></div></td>
							<td><div id="pf_tot_remain" class="pf_tot_remain"></div></td>
							<td><div id="pf_overdue_rate" class="pf_overdue_rate"></div></td>
						</tr>
						<tr>
							<th>주택 담보</th>
							<td><div id="mgg_tot_amt" class="mgg_tot_amt"></div></td>
							<td><div id="mgg_tot_remain" class="mgg_tot_remain"></div></td>
							<td><div id="mgg_overdue_rate" class="mgg_overdue_rate"></div></td>
						</tr>
						<tr>
							<th>매출채권</th>
							<td><div id="hp_tot_amt" class="hp_tot_amt"></div></td>
							<td><div id="hp_tot_remain" class="hp_tot_remain"></div></td>
							<td><div id="hp_overdue_rate" class="hp_overdue_rate"></div></td>
						</tr>
						<tr>
							<th>동산</th>
							<td><div id="mvb_tot_amt" class="mvb_tot_amt"></div></td>
							<td><div id="mvb_tot_remain" class="mvb_tot_remain"></div></td>
							<td><div id="mvb_overdue_rate" class="mvb_overdue_rate"></div></td>
						</tr>
						<tr class="total">
							<th>합계</th>
							<td><div id="all_tot_amt" class="all_tot_amt"></div></td>
							<td><div id="all_tot_remain" class="all_tot_remain"></div></td>
							<td><div id="all_overdue_rate" class="all_overdue_rate"></div></td>
						</tr>
					<tbody>
				</table>
			</div>

			<a href="/company/status/status.php" class="gongsi-view">법정공시정보 전체 보기</a>
			<p>온라인투자연계금융업 및 이용자 보호에 관한 법률에 따라 이용자 권리 보호를 위해 필수적으로 공시하는 정보입니다.</p>
		</div>
	</div>

	<div class="review">
		<div class="review_t">
			<p>
				헬로펀딩 투자후기
				<span></span>
			</p>
		</div>
		<div class="review_wrap">
			<?=review('theme/basic', 6, 70);?>
		</div>
	</div>

	<!--div class="partner_logo">
		<p>
			제휴사<span></span>
		</p>
		<div style="height:60px;"></div>
		<ul>
			<li><a href="https://www.shinhan.com/index.jsp" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo01.jpg" alt="신한은행"></a></li>
			<li><a href="http://p2plending.or.kr/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo02.jpg" alt="한국P2P금융협회"></a></li>
			<li><a href="http://www.hanatrust.com/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo03.jpg" alt="하나자산신탁"></a></li>

		</ul>
		<ul>
			<li><a href="http://www.scri.co.kr/index.jsp" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo04.jpg" alt="SCR서울신용평가"></a></li>
			<li><a href="http://www.karic.or.kr/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo05.jpg" alt="한국부동산리츠투자자문협회"></a></li>
			<!--<li><a href="http://hyunlaw.co.kr/renew/kor/main/main.asp" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo06.jpg" alt="법무법인현"></a></li>-->
			<!--li><a href="https://www.kyoborealco.co.kr/realco/indexRealco.html" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo07.jpg" alt="교보리얼코"></a></li>

		</ul>
		<ul>

			<!--<li><a href="http://seinacct.co.kr/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo08.jpg" alt="세인세무법인"></a></li>-->
			<!--li><a href="http://www.apexlaw.co.kr/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo09.jpg" alt="법무법인 에이펙스"></a></li>
			<li><a href="http://www.fidelisam.co.kr/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo11.jpg" alt="피델리스"></a></li>
			<li><a href="http://www.kyungilnet.co.kr/main/main.php" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo12.jpg" alt="경일감정평가법인"></a></li>
		</ul>
		<ul>
			<!--<li><a href="http://wowtv.co.kr/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo10.jpg" alt="한국경제티브이"></a></li>-->
			<!--li><a href="http://korfin.kr/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo14.jpg" alt="한국핀테크산업협회"></a></li>
			<li><a href="https://www.finnq.com/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo13.jpg" alt="핀크"></a></li>
			<li><a href="https://www.sci.co.kr/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo15.jpg" alt="sci평가정보"></a></li>

		</ul>
		<p style="height:50px;"></p>
	</div-->


	<!-- 법정공시정보 필수 확인 - 220620 주석처리 완료 -->
	<!-- <?
		include_once('gongsi_popup.php');
	?> -->

<script type="text/javascript">
	function numberFormat(x) {
		return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
	}

	function numberToKorean(number){
			var inputNumber  = number < 0 ? false : number;
			var unitWords    = ['', '만', '억', '조', '경'];
			var splitUnit    = 10000;
			var splitCount   = unitWords.length;
			var resultArray  = [];
			var resultString = '';

			for (var i = 0; i < splitCount; i++){
					var unitResult = (inputNumber % Math.pow(splitUnit, i + 1)) / Math.pow(splitUnit, i);
					unitResult = Math.floor(unitResult);
					if (unitResult > 0){
							resultArray[i] = unitResult;
					}
			}

			for (var i = 0; i < resultArray.length; i++){
					if(!resultArray[i]) continue;
					resultString = " " + String(numberFormat(resultArray[i])) + unitWords[i] + resultString;
			}

			return resultString;
	}

	$(function() {

		$.ajax({
			url : "zip_ajax_gosi.php",
			type : 'post',
			data : {'gb': 'a1', 'ym': <?=$date?>},
			dataType : "json",
			success: function(data) {
				console.log(data);

				$(".tot_amt").text(numberToKorean(data.loan_amt)+'원');
				$(".tot_remain").text(numberToKorean(data.tot_remain_amt)+'원');
				$(".overdue_rate").text(data.overdue_rate);
				$(".overdue_cnt").text(data.overdue_cnt);
				$(".hello_nujuk_invest_amt").text(numberToKorean(data.nujuk_invest_amt)+'원');
				$(".hello_live_invest_amt").text(numberToKorean(data.remain_amt)+'원');

			},
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
		});

		$.ajax({
			url : "zip_ajax_gosi2.php",
			type : 'post',
			data : {'gb': 'a1', 'ym': <?=$date?>},
			dataType : "json",
			success: function(data) {
				console.log(data);

				// 부동산 PF
				$(".pf_tot_amt").text(number_format(data["1"].loan_amt));
				$(".pf_tot_remain").text(number_format(data["1"].remain_amt));
				$(".pf_overdue_rate").text(data["1"].overdue_rate);

				// 주택담보
				$(".mgg_tot_amt").text(number_format(data["2"].loan_amt));
				$(".mgg_tot_remain").text(number_format(data["2"].remain_amt));
				$(".mgg_overdue_rate").text(data["2"].overdue_rate);

				// 매출채권
				$(".hp_tot_amt").text(number_format(data["3"].loan_amt));
				$(".hp_tot_remain").text(number_format(data["3"].remain_amt));
				$(".hp_overdue_rate").text(data["3"].overdue_rate);

				// 동산
				$(".mvb_tot_amt").text(number_format(data["4"].loan_amt));
				$(".mvb_tot_remain").text(number_format(data["4"].remain_amt));
				$(".mvb_overdue_rate").text(data["4"].overdue_rate);

				// 합계
				$(".all_tot_amt").text(number_format(data["tot"].loan_amt));
				$(".all_tot_remain").text(number_format(data["tot"].remain_amt));
				$(".all_overdue_rate").text(data["tot"].overdue_rate);

			},
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
		});

	});
</script>



<script type="text/javascript">
simulation(5000000);

// faq
$(".faq a").click(function() {
	$(this).next().slideToggle("fast").parent().siblings().children("dd").hide();
	return false;
});


// 라이브 티비
function popupOpen() {
	var popUrl = "live.html"; //팝업창에 출력될 페이지 URL
	var popOption = "width=640, height=494, top=250, left=600, resizable=no, scrollbars=no, status=no;"; //팝업창 옵션(optoin)
	window.open(popUrl,"",popOption);
}

var galleryTop;
var galleryThumbs;
$(document).ready(function() {
	// 사전투자 설명
	var msg = "펀딩오픈 시간에 투자참여가 어려운 회원분들을 위하여 사전에 투자할 수 있는 서비스입니다. <br><br> <strong>사전 투자 유의사항</strong> <br><br>본 상품은 사전 투자가 가능한 상품으로 목표금액의 <? echo (int)$PRDT['advance_invest_ratio']?>%까지 사전 투자가 진행됩니다. \
				<p>1. 사전 투자는 가상계좌의 예치금으로 투자 가능합니다.</p> \
				<p>2. 사전 투자는 신청순으로 적용됩니다.</p>";
	$('#question_1').webuiPopover({ title: "사전 투자 서비스란?", content: msg, closeable: true, width: 400, trigger: "click", placement: 'bottom', backdrop: false});

	galleryTop = new Swiper('#gallery', {
		spaceBetween: 10,
		onSlideChangeEnd: function() {
			$(document).trigger("slide-change");
		},
		loopedSlides: $("#gallery .swiper-wrapper .swiper-slide").length,
		effect: "fade",
		observer: true,
		observeParents: true,
	});

	galleryThumbs = new Swiper('#gallery-thumbs', {
		spaceBetween: 10,
		centeredSlides: false,
		slidesPerView: 3,
		observer: true,
		observeParents: true,
	});

	// galleryTop.controller.control = galleryThumbs;
	// galleryThumbs.controller.control = galleryTop;

	$(document).on("click", "#gallery-thumbs .swiper-slide", function(e) {
		var index = $(this).index();
		galleryTop.slideTo(index);
	});


	var tmr = setInterval(function() {
		var mb_id_js = "";
		if($('#invest_finished').val() == 'false') {
			$.ajax({
				type: "POST",
				url: "/sscheck.php",
				dataType: "json",
				success: function(data) {
					if (data.mb_id) mb_id_js = data.mb_id;
					ajax_investment(mb_id_js);
				},
				error: function(e) { ajax_investment(mb_id_js); }
			});
		} else {
			clearInterval(tmr);
		}

	}, 5 * 1000);


	function ajax_investment(mb_id_js) {

			$.ajax({
				type: "POST",
				url: "<?=$CONF['api_server_url']?>/investment/ajax_investment.php",
				dataType: "json",
				data: {"prd_idx":<?=$prd_idx;?>, "mb_id": mb_id_js},
				success: function(json) {

					//console.log(json);
					// 3초간 데이터 조회
					// 바뀌는 값들 모집금액, 투자모집률, 남은 모집금액, 버튼들
					$('#invest_finished').val(json.data.invest_finished); // 현재진행상태
					$('#progressLayer').attr('style', "left:" + (json.data.progress.replace('%', '') <= 100 ? (json.data.progress.replace('%', '') - 11.7) : 88.2)+'%');  // 진행률 및 잔액출력 레이어
					$('#progressBar').attr('style', "width:" + json.data.progress_width); // 진행률 표시

					$('#processBtn').html(json.data.button_data1);
					//$('#progressBtn').html("<li>"+json.data.button_data1+"</li>");

					// progressData, totalRecruitValue
					$('#progressData').text(json.data.progress); // 진행률
					$('#totalRecruitValue').text(json.data.need_recruit_amount_k); // 잔여모집금액
					//$('#totalRecruitValue').text(json.data.total_invest_amount_k); // 현재모집금액

				},
				error: function(e) {

				}
			});

	}

/*
	setInterval(function() {
		if($('#invest_finished').val() == 'false') {
			$.ajax({
				type: "GET",
				url: "/investment/ajax_investment.php",
				dataType: "json",
				data: {prd_idx:<?=$prd_idx;?>},
				success: function(json) {

					// 3초간 데이터 조회
					// 바뀌는 값들 모집금액, 투자모집률, 남은 모집금액, 버튼들
					$('#invest_finished').val(json.data.invest_finished); // 현재진행상태
					$('#progressLayer').attr('style', "left:" + (json.data.progress.replace('%', '') <= 100 ? (json.data.progress.replace('%', '') - 11.7) : 88.2)+'%');  // 진행률 및 잔액출력 레이어
					$('#progressBar').attr('style', "width:" + json.data.progress_width); // 진행률 표시

					$('#processBtn').html(json.data.button_data1);
				//$('#progressBtn').html("<li>"+json.data.button_data1+"</li>");

					// progressData, totalRecruitValue
					$('#progressData').text(json.data.progress); // 진행률
					$('#totalRecruitValue').text(json.data.need_recruit_amount_k); // 잔여모집금액
				//$('#totalRecruitValue').text(json.data.total_invest_amount_k); // 현재모집금액

				},
				error: function(e) { }
			});
		}
	}, 3 * 1000);
*/


});

$("a[data-toggle='sns_share']").click(function(e) {
	e.preventDefault();
	var current_url = window.location.href;
	var _this       = $(this);
	var sns_type    = _this.attr('data-service');
	var href        = current_url;
	var title       = _this.attr('data-title');
	var img         = $("meta[name='og:image']").attr('content');
	var loc         = "";

	if( ! sns_type || !href || !title) return;

	if(sns_type == 'facebook') { loc = '//www.facebook.com/sharer/sharer.php?u='+href+'&t='+title; }
	else if(sns_type == 'twitter') { loc = '//twitter.com/home?status='+encodeURIComponent(title)+' '+href; }
	else if(sns_type == 'google') { loc = '//plus.google.com/share?url='+href; }
	else if(sns_type == 'pinterest') { loc = '//www.pinterest.com/pin/create/button/?url='+href+'&media='+img+'&description='+encodeURIComponent(title); }
	else if(sns_type == 'kakaostory') { loc = 'https://story.kakao.com/share?url='+encodeURIComponent(href); }
	else if(sns_type == 'band') { loc = 'http://www.band.us/plugin/share?body='+encodeURIComponent(title)+'%0A'+encodeURIComponent(href); }
	else if(sns_type == 'naver') { loc = "http://share.naver.com/web/shareView.nhn?url="+encodeURIComponent(href)+"&title="+encodeURIComponent(title); }
	else if(sns_type == 'url_copy') { copy_trackback(href); }
	else if(sns_type == 'instagram') { alert("현재 지원하지 않는 기능입니다."); loc = ""; return false; }
	else { return false; }

	if(sns_type != 'url_copy') { window.open(loc); }

	return false;
});

function copy_trackback(trb) {
	var IE=(document.all)?true:false;
	if(IE) {
		if(confirm("이 글의 트랙백 주소를 클립보드에 복사하시겠습니까?"))
			window.clipboardData.setData("Text", trb);
	} else {
		temp = prompt("이 글의 트랙백 주소입니다. Ctrl+C를 눌러 클립보드로 복사하세요", trb);
	}
}

$(document).on("keyup", 'input:text[name="principal_value"]', function() {
	var earn_btn = $("p.earn_btn");
	if(earn_btn.css("display") == "block") {
		earn_btn.hide();
	}
});

// 예상수익금 계산
function simulation(price) {
	var price = (price || '0');
	var pattern = /^[0-9]+$/;
	var prd_idx = ($("input:hidden[name='prd_idx']").val() || 0);
	var principal_value = ($("input:text[name='principal_value']").val() || price).replace(/[\D\s\._\-]+/g, "");
	var min_invest_limit = (<?=$CONF['min_invest_limit']?> || 0);

	if(principal_value == "") {
		alert("투자 금액을 입력해주세요.");
		$("input:text[name='principal_value']").focus();
		return;
	}
	if(!pattern.test(principal_value) ) {
		alert("투자 금액에 사용할수 없는 문자가 있습니다. 숫자만 입력해주세요.");
		$("input:text[name='principal_value']").focus();
		return;
	}

/*
	if(principal_value < min_invest_limit) {
		alert("최소 금액은 " + number_format(min_invest_limit) + "원 이상 입니다.");
		$("input:text[name='principal_value']").focus();
		return;
	}
*/

	if(principal_value >= <?=$CONF['min_invest_limit']?>) {
		$.ajax({
			url : g5_url + "/investment/ajax_simulation.php",
			type: "POST",
			data : {prd_idx: prd_idx, ajax_principal_value: principal_value, onlyInterest: 'Y'},
			success: function(data, textStatus, jqXHR)
			{
				if(data == "ERROR") {
					alert("시스템 오류입니다. 관리자에 문의해주세요.");
				}
				else if(data == "ERROR-MIN-PRICE") {
					alert("최소 금액은 " + number_format(min_invest_limit) + "원 이상 입니다.");
					$("input[name='principal_value']").focus();
					return;
				}
				else{
					var data = JSON.parse(data);
					if(data.success) {
						$("p.earn_btn").show();
						$("#ajxTotalInterestPrice").text(data.totalInterestPrice);
						$("#ajxInvestMonth").text(data.investMonth);
						$("#ajxMonthAvrPrice").text(data.monthAvrPrice);
						$("#ajaDiffEarning").text(data.diffEarning);
					}
				}
			},
			error: function (jqXHR, textStatus, errorThrown) {

			}
		});
	}
}

function formatNumber(numberString) {
	var selection = window.getSelection().toString();
	if(selection !== '') {
		return;
	}

	if( $.inArray( event.keyCode, [38,40,37,39] ) !== -1 ) {
		return;
	}
	var input = numberString.value;
	var input = input.replace(/[\D\s\._\-]+/g, "");
	input = input ? parseInt( input, 10 ) : 0;
	numberString.value = (input === 0 ) ? "" : input.toLocaleString('ko-KR', {maximumSignificantDigits : 21});
}
</script>

<?

// 투자위험고지 팝업
include_once(G5_PATH."/popup/inc_invest_warning_agree_form.php");

if($prd_idx == '518') {
	include_once(G5_PATH.'/popup/inc_product_358_notice.php');
}

if($prd_idx == '1038') {
	include_once(G5_PATH.'/popup/inc_product_1038_notice.php');
}

// 라이브스트림 준비중 팝업
if($PRDT['stream_url1'] == 'ready') {
	include_once(G5_PATH.'/popup/inc_stream_ready.php');
}

if($co['co_include_tail']) {
	@include_once($co['co_include_tail']);
}
else {
	include_once('./_tail.php');
}


// 실행시간 로깅 종료
//if($log_idx) {
//	$thrSec  = get_microtime() - $sdt;
//	@shell_exec("/usr/local/php/bin/php -q /home/crowdfund/public_html/investment/test_log_finish.exec.php {$log_idx} {$thrSec}");
//}


?>