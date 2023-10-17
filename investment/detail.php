<?
###############################################################################
## 투자하기 (투자금 입력 폼)
###############################################################################

//2017-04-24 : 개인회원 상품별 금액 제한 관련 내용 추가
//2017-07-19 : 사전투자 설정

include_once('./_common.php');

// ↓↓↓↓ 투자이후 팝업창 및 리다이렉션 설정 테스트
//if( $member['mb_id']=='sori9th' ) { include("detail.test.php"); return; }

$g5['title'] = '투자하기';
$g5['top_bn'] = "/images/investment/sub_investment.jpg";
$g5['top_bn_alt'] = "투자하기 투자자가 작은 금액들을 모아서 함께 투자하는 새로운 투자 방식입니다.";

while( list($k, $v) = each($_REQUEST) ) { if(!is_array($k)) ${$k} = addslashes(clean_xss_tags(trim($v))); }

if(!$member["mb_id"]) { alert("로그인 후 이용 가능합니다.", G5_BBS_URL."/login.php?url=" . urlencode($_SERVER[PHP_SELF]."?prd_idx=".$prd_idx)); exit; }


///////////////////////////////
// KYC 현황 체크
///////////////////////////////
//if( $office_connect ) {				// if( in_array($member['mb_id'], $kyc_test_member) ) {
//	if( in_array($member['kyc_allow_yn'], array('W','I')) ) {
//		msg_go('본인확인내역 검토중입니다.');
//	}
//}


$developer        = ( in_array($member['mb_id'], $CONF['DEVELOPER']) ) ? true : false;
$goods_officer    = ( in_array($member['mb_id'], $CONF['GOODS_OFFICER']) ) ? true : false;
$tmp_special_user = ( in_array($member['mb_id'], array('samo','samo001','samo002')) ) ? true : false;
//$tmp_user         = ( in_array($member['mb_id'], array('cocktailfunding')) ) ? true : false;

if($prd_idx=='') { goto_url('/'); exit; }
if(!preg_match('/^[0-9]{0,10}$/', $prd_idx)) { goto_url('/'); exit; }

/*
임시차단
if( in_array($member['mb_no'], array('48365','49793','50150','48519','46418','17891','48806','7736','49788','49063','49903','19541','48128','10527','49968','48184')) ) {
	msg_go("회원님의 예치금 확인 작업이 진행 중이며 확인 기간 동안 투자가 안 되는 점 양해 부탁드립니다.\\n빠른 정상화를 위해 노력하겠습니다.\\n이용에 불편을 드려 죄송합니다.");
}
*/


$is_advance_invest = ($advance == '1') ? 'Y' : 'N';			// 사전투자모드 설정


// 신디케이션사투자회원 헬로펀딩을 통한 중복투자 방지
$SYNDI_INVESTED = sql_fetch("
	SELECT
		idx, syndi_id
	FROM
		cf_product_invest_detail
	WHERE 1
		AND product_idx = '".$prd_idx."'
		AND member_idx = '".$member['mb_no']."'
		AND invest_state = 'Y'
		AND syndi_id IN('finnq','oligo','kakaopay')
	ORDER BY
		idx DESC
	LIMIT 1");
if($SYNDI_INVESTED['idx']) {
	$msg = "타사 앱(".$CONF['SYNDICATOR'][$SYNDI_INVESTED['syndi_id']]['name'].")를 통하여 본 상품에 투자한 내역이 존재하여 중복투자를 하실 수 없습니다.";
	msg_replace($msg, "/investment/invest_list.php");
}


$sql = "
	SELECT
		A.idx, A.gr_idx, A.state, A.category, A.title,
		A.recruit_amount, A.invest_return, A.invest_period, A.invest_usefee,
		A.open_datetime, A.start_datetime, A.end_datetime, A.recruit_period_start, A.recruit_period_end,
		A.advance_invest, A.advance_invest_ratio, A.only_vip, A.vip_mb_no,
		( SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state='Y' ) AS total_invest_amount,
		( SELECT COUNT(product_idx) AS total_invest_count FROM cf_product_invest WHERE product_idx=A.idx AND invest_state='Y' ) AS total_invest_count
	FROM
		cf_product A
	WHERE
		A.idx = '".$prd_idx."'";
$sql.= (($is_admin=='super' || $developer || $goods_officer || $tmp_special_user) || preg_match('/wowstar/i', $_COOKIE['PHPSESSID'])) ? "" : " AND A.display='Y'";  //관리자는 모조리 출력
//if($_COOKIE['debug_mode']) { echo $sql; }
$PRDT = sql_fetch($sql);
if(!$PRDT) { alert("올바른 경로가 아닙니다.","/"); exit; }

// 신한은행 점검시간 진입금지 --------------------------------------------------------------
if( date('Y-m-d H:i:s') >= $CONF['BANK_STOP_SDATE'] && date('Y-m-d H:i:s') < $CONF['BANK_STOP_EDATE'] ) {
	$msg = "금융기관 점검시간 입니다.";
	msg_replace($msg, "/investment/investment.php?prd_idx={$prd_idx}");
}

// 금결원 점검시간 진입금지 --------------------------------------------------------------
if( date('H:i') >= $CONF['P2PCTR_PAUSE']['STIME'] || date('H:i') < $CONF['P2PCTR_PAUSE']['ETIME'] ) {
	$msg = "투자가능시간이 아닙니다.\\n중앙기록관리기관 점검 시간(".$CONF['P2PCTR_PAUSE']['STIME']."~".$CONF['P2PCTR_PAUSE']['ETIME'].")에는 투자 신청 및 취소, 한도 조회가 불가능합니다.";
	msg_replace($msg, "/investment/investment.php?prd_idx={$prd_idx}");
}


// 지정투자상품 설정 ------------------------------------------------------------------

// 특정상품에 메세지 다르게 적용
$defeat_msg = "[본 투자상품 관련 공지]\\n\\n본 투자상품은 투자자와 사전에 협의가 완료된 지정투자상품입니다.\\n따라서 지정된 투자자 외 분들의 상품열람 및 투자가 제한되는 점 양해부탁드립니다.";
if($prd_idx=='148') {
	$defeat_msg = "[본 투자상품 관련 공지]\\n\\n본 투자상품은 사전에 협의완료된 대출자와 투자자가 제3자에 의한 체계적 담보권리확보 및 자금관리를 목적으로 헬로펀딩을 통해 펀딩을 진행합니다.\\n따라서 지정된 투자자 외 분들의 상품열람 및 투자가 제한되는 점 양해부탁드립니다.";
}

if(!$is_admin) {

	if( $PRDT['only_vip']=='1' ) {
		$VIP_MB_NO = explode(",", $PRDT['vip_mb_no']);
		if( count($VIP_MB_NO) > 0 && in_array($member['mb_no'], $VIP_MB_NO) ) {
			//
		} else {
			msg_replace($defeat_msg, "/investment/invest_list.php");
		}
	}

}
// 지정투자상품 설정 ------------------------------------------------------------------

$recruit_amount = $PRDT['recruit_amount'];
$print_recruit_amount = price_cutting($recruit_amount).'원';

$YmdHis = preg_replace("/(-|:| )/", "", G5_TIME_YMDHIS);
$recruit_period_start = preg_replace("/-/", "", $PRDT["recruit_period_start"]);
$recruit_period_end   = preg_replace("/-/", "", $PRDT["recruit_period_end"]);
$product_open_date    = preg_replace("/(-|:| )/", "", $PRDT["open_datetime"]);		// 상점오픈 (투자시작불가)
$product_invest_sdate = preg_replace("/(-|:| )/", "", $PRDT["start_datetime"]);		// 투자시작
$product_invest_edate = preg_replace("/(-|:| )/", "", $PRDT["end_datetime"]);			// 상품종료 (투자마감)

if($is_advance_invest=='Y') {
	$recruit_amount = round($recruit_amount * ($PRDT['advance_invest_ratio']/100));		// 사전투자비율에 따른 사전투자전체한도액
	if($PRDT['total_invest_amount'] >= $recruit_amount) { msg_replace("본 상품의 사전 투자 목표금액이 모집완료 되었습니다.", "/investment/investment.php?prd_idx=$prd_idx"); }
	if($product_invest_sdate <= $YmdHis) { msg_replace("사전 투자 가능 기간이 종료 되었습니다.", "/investment/investment.php?prd_idx=$prd_idx"); exit; }

	$print_recruit_amount = '<span style="color:#aaa">(전체 ' . price_cutting($PRDT['recruit_amount']) .'원 중)</span> ' . price_cutting($recruit_amount).'원';
}


/* 투자금액 */
if($recruit_amount > 0) {
	$invest_return = $PRDT["recruit_amount"];
}
else {
	msg_replace("[투자금액 설정오류] 관리자에 문의해주세요.","/investment/invest_list.php");
}

/* 투자 수익율 */
$invest_return = $PRDT["invest_return"];
/*
if($PRDT["invest_return"] > 0) {
	$invest_return = $PRDT["invest_return"];
}
else {
	msg_replace("[투자 수익율 설정오류] 관리자에 문의해주세요.","/investment/invest_list.php");
}
*/

/* 투자기간 */
if($PRDT["invest_period"] > 0) {
	$invest_period = $PRDT["invest_period"];
}
else {
	msg_replace("[투자 기간 설정오류] 관리자에 문의해주세요.","/investment/invest_list.php");
}

/* 투자자 플랫폼 이용료 */
$invest_usefee = ($PRDT['invest_usefee'] > 0) ? $PRDT['invest_usefee'] : 0;


// 투자모집진행률
$product_invest_percent = 0;
if($PRDT['total_invest_amount']) {
	$product_invest_percent = ($PRDT['total_invest_amount'] / $recruit_amount) * 100;
	$product_invest_percent = floatCutting($product_invest_percent, 2);
}


if($is_advance_invest!='Y' && $member['mb_id']!='sori9th') {
	if( $product_open_date < $YmdHis && $product_invest_edate > $YmdHis ) {
		if($product_invest_sdate < $YmdHis) {
			if($recruit_amount > $PRDT["total_invest_amount"]) {  // 투자액 세팅
				// 투자 가능
			}
			else {
				alert("모든 투자가 완료 되었습니다.");
			}
		}
		else {
			alert("투자 시작 시간이 아닙니다.");
		}
	}
	else {
		alert($product_open_date . " " . $YmdHis . " " . $product_invest_edate . " 투자 모집 기간이 아닙니다.");
	}
}

$min_invest_limit = $CONF['min_invest_limit'];
$max_invest_limit = $CONF['max_invest_limit'];


if( $member['member_type']=='1' && in_array($member['member_investor_type'], array('1','2')) ) {
	// 모집중이거나 이자상환중인 (원금상환이 완료되지 않은) 동일차주상품 SELECT (현재 열람중인 상품도 포함)
	$sql2 = "SELECT idx FROM cf_product WHERE state IN ('', '1') AND gr_idx='".$PRDT['gr_idx']."' AND idx > '{$CONF['old_type_end_prdt_idx']}' ORDER BY idx";
	//echo $sql2."<br>\n";
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

		$sql3 = "SELECT IFNULL(SUM(amount), 0) AS sum_invest_amount FROM cf_product_invest WHERE member_idx='".$member['mb_no']."' AND product_idx IN ($prd_idx_arr) AND invest_state='Y'";
		//echo $sql3."<br>\n";
		$INVEST_PRDT = sql_fetch($sql3);
	}
	if($res->num_rows) sql_free_result($res);
}



// 잔여 모집금액
$need_recruit_amount = $recruit_amount - $PRDT["total_invest_amount"];

// 투자 가능금액 설정
$invest_possible_amount = $need_recruit_amount;

// 20200826 추가 (법인, 개인전문투자자 : 총 모집금액의 40% 까지만 투자가능)
// 법인회원 및 개인전문투자자 최대 투자가능금액 체크
if($member['member_type']=='2' || ($member['member_type']=='1' && $member['member_investor_type']=='3')) {
		// 본상품 기투자금액
		$TMP2 = sql_fetch("SELECT IFNULL(SUM(amount), 0) AS invested_amount FROM cf_product_invest WHERE product_idx='".$PRDT['idx']."' AND member_idx='".$member['mb_no']."' AND invest_state='Y'");

		$pro_max_limit_amount = ($PRDT['recruit_amount'] * $INDI_INVESTOR['3']['invest_able_perc']) - $TMP2['invested_amount'];
		$pro_max_limit_amount = floor($pro_max_limit_amount);
}


//--- 개인회원의 경우 투자등급에 따른 투자 가능금액 산출 -------------------
if($member['member_type']=='1') {
	if( in_array($member['member_investor_type'], array('1','2')) ) {
		$limit_amount = ($is_group_product) ? $INDI_INVESTOR[$member['member_investor_type']]['group_product_limit'] : $INDI_INVESTOR[$member['member_investor_type']]['single_product_limit'];
		$_invest_possible_amount = $limit_amount - $INVEST_PRDT['sum_invest_amount'];

		if($_invest_possible_amount > $member['invest_possible_amount']) {
			$invest_possible_amount = ($PRDT['category']=='2' && $member['member_investor_type']=='1') ? $member['invest_possible_amount_prpt'] : $member['invest_possible_amount'];
		}
		else {
			$invest_possible_amount = ($PRDT['category']=='2' && $member['member_investor_type']=='1') ? $member['invest_possible_amount_prpt'] : $_invest_possible_amount;
		}
	}
}

// 투자 가능금액이 잔여 모집액보다 크면 투자 가능금액 = 잔여모집액
if($invest_possible_amount >= $need_recruit_amount) {
	$invest_possible_amount = $need_recruit_amount;
}


if($member['mb_id']) {
	$shinhan_vacct = ( trim($member['va_bank_code2']) && trim($member['virtual_account2']) ) ? true : false;
}

if($shinhan_vacct) {
	if($member['insidebank_after_trans_target']=='1') {
		// 기존계좌의 금액이전이 완료되지 않은 경우
		$tmp_msg = "신한은행 가상계좌 발급이 완료되어 현재 보유하신 예치금이 신한은행으로 이관중입니다. 이관에 소요되는 시간은 가상계좌 발급 후 영업일 기준 최장 48시간 이내이며 이관이 완료된 후 투자가 가능한 점 양해부탁드립니다.";
		$invest_button = '<span class="btn_big_green" onClick="alert(\''.$tmp_msg.'\');">투자하기</span>';
	}
	else {
		$invest_button = '<span class="btn_big_green" id="btn_invest">투자하기</span>';
	}

	if( date('Y-m-d') >= '2022-01-01' && $member['kyc_next_dd'] <= date('Y-m-d') ) {
		// 본인확인이 되지 않은 경우
		$invest_button = '<a href="javascript:;" onClick="KYCPopup();" class="btn_big_green">투자하기</a>';
	}

}
else {

	//$invest_button = '<span class="btn_big_green" id="btn_vacs">투자하기</span>';		// 가상계좌 발급창 오픈

	if( date('Y-m-d') >= '2022-01-01' && $member['kyc_next_dd'] <= date('Y-m-d') ) {
		// 본인확인이 되지 않은 경우
		$invest_button = '<a href="javascript:;" onClick="KYCPopup();" class="btn_big_green">투자하기</a>';
	}

}


if( $member['member_type']=='1' && in_array($member['member_investor_type'], array('1','2')) ) {
	// 동일차주 투자건 배열화
	$sqlx = "
		SELECT
			A.idx, A.title,
			(SELECT IFNULL(SUM(amount), 0) FROM cf_product_invest WHERE member_idx='".$member['mb_no']."' AND product_idx=A.idx AND invest_state='Y') AS amount
		FROM
			cf_product A
		WHERE (1)
			AND A.idx<>'$prd_idx'
			AND A.gr_idx='".$PRDT['gr_idx']."'
			AND A.state IN('', '1')
			AND A.display='Y'";
	//if($_SERVER['REMOTE_ADDR']=='220.117.134.164') echo $sqlx;
	$resx = sql_query($sqlx);
	$rcountx = $resx->num_rows;
	for($i=0; $i<$rcountx; $i++) {

		$TMP = sql_fetch_array($resx);
		if($TMP['amount'] > 0) {
			$GINVEST[] = $TMP;
		}
	}
	$g_invest_count = count($GINVEST);
}


if($co['co_include_head']) @include_once($co['co_include_head']);
else include_once('./_head.php');

///////////////////////////////
// 모바일 분기
///////////////////////////////
if(G5_IS_MOBILE){
	include_once('./detail_m.php');
	return;
}

?>
<!-- 본문내용 START -->

<div id="content">
	<!--div class="location"><span><a href="<?=G5_URL?>/investment/invest_list.php">투자하기</a></span><b class="blue"><?=($is_advance_invest=='Y')?'사전':'';?>투자 설정: <?=$PRDT["title"]?></b></div-->

	<div class="content invest_detail">

		<form method="post" name="frm" id="frm">
			<input type="<?=($_COOKIE['debug_mode'])?'text':'hidden';?>" name="prd_idx"                id="prd_idx"                 value="<?=$prd_idx?>">
			<input type="<?=($_COOKIE['debug_mode'])?'text':'hidden';?>" name="advance"                id="advance"                 value="<?=$advance?>">
			<input type="<?=($_COOKIE['debug_mode'])?'text':'hidden';?>" name="ajax_invest_value"      id="ajax_invest_value"       value="">
			<input type="<?=($_COOKIE['debug_mode'])?'text':'hidden';?>" name="need_recruit_amount"    id="need_recruit_amount"     value="<?=$need_recruit_amount?>">
			<input type="<?=($_COOKIE['debug_mode'])?'text':'hidden';?>" name="invest_possible_amount" id="invest_possible_amount"  value="0"><!--<?=$invest_possible_amount?>//-->
			<input type="<?=($_COOKIE['debug_mode'])?'text':'hidden';?>" name="balance_value"          id="balance_value"           value="<?=$member["mb_point"]?>">
			<input type="<?=($_COOKIE['debug_mode'])?'text':'hidden';?>" name="min_invest_limit"       id="min_invest_limit"        value="<?=$min_invest_limit?>">
			<input type="<?=($_COOKIE['debug_mode'])?'text':'hidden';?>" name="max_invest_limit"       id="max_invest_limit"        value="<?=$max_invest_limit?>">
		</form>

		<h2 class="big"><?=($is_advance_invest=='Y')?'<span class="red">[사전투자]</span> ':'';?><?=$PRDT["title"]?></h2>

		<h3><!--<span class="normal">(<?=$PRDT["total_invest_count"]?>명)</span>--></h3>
		<div class="rate">
			<img id="progress_bar" src="/images/investment/rate_blue.gif" alt="진행률" style="width:<?=$product_invest_percent?>%;" height="12">
			<b class="percent">0%</b>
			<b class="percent02" id="progress_data"><?=$product_invest_percent?>%</b>
		</div>

		<div class="my_invest">
			<table align="center" style="width:98%;font-size:14px;">
				<? if($shinhan_vacct) { ?>
				<tr style="border-bottom:2px solid #284893">
					<td style="width:200px; padding-left:20px">▣ 나의 가상계좌</td>
					<td><span style="font-weight:bold;color:#4A6FE2"><?=$BANK[$member['va_bank_code2']]." &nbsp ".$member['virtual_account2']?></span> &nbsp;&nbsp; <span style="color:#FF2222;font-size:14px">&gt;&gt;&gt; 가상계좌에 예치금을 입금한 후 투자해주세요.</span></td>
				</tr>
				<? } ?>
				<tr style="border-bottom:1px solid #ddd;">
					<th style="text-align:center;background:#fafafa">나의 예치금</th>
					<td style="text-align:right;padding-right:20px;font-weight:bold;color:#EE1D1D"><span id="realtime_point" class="price" style="color:#EE1D1D"><?=number_format($member["mb_point"])?></span>원</td>
				</tr>
				<tr style="border-top:1px solid #eee; border-bottom:1px solid #ddd;">
					<th style="text-align:center;background:#fafafa">목표금액</th>
					<td style="text-align:right;padding-right:20px;"><span style="font-weight:bold;color:#284893"><?=$print_recruit_amount?></span></td>
				</tr>
				<tr style="border-top:1px solid #eee; border-bottom:1px solid #ddd;">
					<th style="text-align:center;background:#fafafa">모집금액</th>
					<td style="text-align:right;padding-right:20px;"><span id="total_invest_amount_k" style="font-weight:bold;color:#284893"><?=price_cutting($PRDT['total_invest_amount'])?>원</span></td>
				</tr>
<?
if(G5_TIME_YMD >= $CONF['online_invest_policy_sdate']) {
	if( $member['member_type']=='2' || ($member['member_type']=='1' && $member['member_investor_type']=='3') ) {
?>
				<tr style="border-top:1px solid #eee; border-bottom:1px solid #ddd;">
					<th style="text-align:center;background:#fafafa">상품투자한도</th>
					<td style="text-align:right;padding-right:20px;"><span style="font-weight:bold;color:#284893"><?=price_cutting((floor($PRDT['recruit_amount']*$INDI_INVESTOR['3']['invest_able_perc'])/10000)*10000)?>원</span></td>
				</tr>
<?
	}
}
?>
				<tr style="border-top:1px solid #eee; border-bottom:1px solid #ddd;">
					<th style="text-align:center;background:#fafafa">잔여모집금액</th>
					<td style="text-align:right;padding-right:20px;"><span id="need_recruit_amount_k" style="font-weight:bold;color:#284893"><?=price_cutting($need_recruit_amount)?>원</span></td>
				</tr>
				<tr style="border-top:1px solid #eee; border-bottom:1px solid #ddd;">
					<th style="text-align:center;background:#fafafa">투자가능금액</th>
					<td style="text-align:right;padding-right:20px;"><span id="invest_possible_amount_k" style="font-weight:bold;color:green">>>> 로딩중</span></td><!-- <?=price_cutting($invest_possible_amount)?>원 -->
				</tr>
			</table>

			<p align="center" style="padding:20px 0 20px;">+
				<span id="" style="font-size:16px;font-weight:bold">투자금액</span>
				<input type="text" class="text" id="invest_value" name="invest_value" DISABLED placeholder="0" maxlength="9" onKeyUp="NumberFormat(this);" style="border:4px solid #284893; width:200px; text-align:right;"> 만원<br>
				<span style="padding-top:8px;">( <span class="blue" id="invest_value_text">0</span>원 )</span>
			</p>
<? if( $member['member_type']=='1' && in_array($member['member_investor_type'], array('1','2')) ) { ?>
			<ul style="clear:both;display:inline-block;padding:0; font-size:14px;">
				<li style="float:left; padding:0 0 0 20px">▣ 동일 대출자상품 투자내역</li>
				<li style="float:left; margin-left:30px; color:brown">* 개인(<?=$INDI_INVESTOR[$member['member_investor_type']]['title']?>)의 경우 동일 대출자에게는 <strong><?=price_cutting($INDI_INVESTOR[$member['member_investor_type']]['group_product_limit'])?>원</strong>까지만 투자가 가능합니다.</li>
			</ul>
			<table align="center" style="width:98%">
				<colgroup>
					<col style="width:50%">
					<col style="width:50%">
				</colgroup>
				<tr>
					<th style="background:#EFEFEF;border-top:2px solid #284893">상품명</th>
					<th style="background:#EFEFEF;border-top:2px solid #284893">투자금액</th>
				</tr>
<?
	if($g_invest_count) {
		for($i=0; $i<$g_invest_count; $i++) {
?>
				<tr>
					<td style="padding:0 10px;border-bottom:1px solid #ccc"><?=$GINVEST[$i]['title']?></td>
					<td style="padding:0 10px;border-bottom:1px solid #ccc" align="right"><?=number_format($GINVEST[$i]['amount'])?>원</td>
				</tr>
<?
		}
	}
	else {
?>
				<tr>
					<td colspan="2" align="center" style="border-bottom:1px solid #ccc">투자내역이 없습니다.</td>
				</td>
<?
	}
?>
			</table>
<?
}
?>
		</div>

		<!--
		<h3>안내사항</h3>
		<div class="box"></div>
		//-->

		<!--
		<h3>투자위험안내</h3>
		<div class="box"></div>
		//-->

		<!--<h3>이용약관</h3>-->
		<div class="textarea">
			<div class="agree">
				<label style="padding:0 10px 5px 0;">
				  <a href="<?=G5_URL?>/bbs/content.php?co_id=provision2" target="_blank" class="blue2">투자이용약관</a>에 동의합니다. &nbsp;
					<input type="checkbox" id="guide" value="Y" checked='checked'>
				</label>
			</div>
		</div>

		<div class="btnArea mt40">
			<?=$invest_button?>
		</div>

	</div>
</div>

<div id="complete" class="detail">
	<img src="/images/btn_close.gif" alt="close" class="close">
	<div class="title">예치금 투자 진행</div>
	<div class="text">예치금으로 투자를 진행하시겠습니까?</div>

	<button type="button" id="yes" class="btn_big_blue">확인</button> &nbsp;
	<button type="button" id="no"  class="btn_big_link">취소</button>
</div>

<div id="complete2" class="detail">
	<div class="title">투자완료</div>
	<div class="text">
		<span class="blue"><?=$member["mb_name"]?></span>고객님<br><br>
		<span class="blue"><?=$PRDT["title"]?></span>에<br>
		<span class="blue" id="value_text_show">{투자금액}</span>원 투자가 완료되었습니다.<br>
	</div>
	<a href="/deposit/deposit.php"><span class="btn_big_blue">투자내역확인</span></a>
</div>

<div id="complete3" class="detail">
	<img src="/images/btn_close.gif" alt="close" class="close">
	<div class="title">예치금 계좌 발급</div>
	<div class="text">예치금 계좌를 발급 받지 않았습니다<br>예치금 계좌 발급 후 투자를 진행해 주세요</div>

	<a href="/deposit/deposit.php?tab=3" id="main" class="btn_big_blue">발급받기</a> &nbsp;
	<span id="no" class="btn_big_link">취소</span>
</div>

<script>
$(document).ready(function() {

	setTimeout(function(){ajax_investment('<?=$member['mb_id']?>');}, Math.random()*1000);

	var tmr = setInterval(function() {
		var mb_id_js = "";

		$.ajax({
			type: "POST",
			url: "/sscheck.php",
			dataType: "json",
			success: function(data) {
				if (data.mb_id) mb_id_js = data.mb_id;
				ajax_investment(mb_id_js);
				ajax_point_check(mb_id_js);
			},
			error: function(e) { ajax_investment(mb_id_js); }
		});

	}, 5*1000);

	function ajax_investment(mb_id_js) {

		$.ajax({
			type: "POST",
			url: "<?=API_URL?>/investment/ajax_investment.php",
			dataType: "json",
			data: {"prd_idx":<?=$prd_idx;?>, "mb_id": mb_id_js, advance:'<?=$advance?>'},
			success: function(json) {
				//console.log(json);
				// 투자금입력란 활성화
				if( $("#invest_value").attr("disabled") ) {
					$('#invest_value').attr('DISABLED', false);
					$('#invest_value').focus();
				}
				$('#need_recruit_amount').val(json.data.need_recruit_amount);
				$('#invest_possible_amount').val(json.data.invest_possible_amount);
				$('#progress_data').html(json.data.progress);
				$('#progress_bar').attr('style', "width:" + json.data.progress_width);
				$('#invest_possible_amount_k').html(json.data.invest_possible_amount_k);
				$('#need_recruit_amount_k').html(json.data.need_recruit_amount_k);
				$('#total_invest_amount_k').html(json.data.total_invest_amount_k);
			},
			error: function(e) {

			}
		});

	}

	function ajax_point_check(mb_id_js) {

		$.ajax({
			type: "POST",
			data: {"mb_id": mb_id_js},
			url : "<?=API_URL?>/deposit/ajax_point_check.php",
			success: function(data) {
				if ($('#ajax_return_txt').length) $('#ajax_return_txt').val(data);
				// 단순출력항목
				if ($('#realtime_point').length) {
					$('#realtime_point').empty();
					$('#realtime_point').append(number_format(data));
				}
				// 변환불가항목
				if ($('#now_point').length) {
					$('#now_point').empty();
					$('#now_point').val(data);
				}
				// 실보유예치금 갱신
				if ($('#balance_value').length) {
					$('#balance_value').empty();
					$('#balance_value').val(data);
				}
			},
			error: function(e) {

			}
		});

	}

/*
	setInterval(function() {
		$.ajax({
			type: "GET",
			url: "/investment/ajax_investment.php",
			dataType: "json",
			data: {prd_idx:'<?=$prd_idx?>', advance:'<?=$advance?>'},
			success: function(json) {

				// 투자금입력란 활성화
				if( $("#invest_value").attr("disabled") ) {
					$('#invest_value').attr('DISABLED', false);
					$('#invest_value').focus();
				}

				$('#need_recruit_amount').val(json.data.need_recruit_amount);
				$('#invest_possible_amount').val(json.data.invest_possible_amount);
				$('#progress_data').html(json.data.progress);
				$('#progress_bar').attr('style', "width:" + json.data.progress_width);
				$('#invest_possible_amount_k').html(json.data.invest_possible_amount_k);
				$('#need_recruit_amount_k').html(json.data.need_recruit_amount_k);
				$('#total_invest_amount_k').html(json.data.total_invest_amount_k);
			},
			error: function(e) { }
		});
	}, 3 * 1000);
*/
});

// 팝업 닫기
$('#complete #no, #complete .close, #complete2 .close, #complete3 .close, #complete3 #no').click(function() {
	$.unblockUI();
	return false;
});

btn_event = function(arg) {
	if(arg=='send') {
		$('#yes').removeClass('btn_big_blue').addClass('btn_big_gray');
		$('#yes').text('전송중 >>>');
		$('#yes').attr('disabled', 'disabled');
	}
	else if(arg=='exit') {
		$('#yes').removeAttr('disabled');
		$('#yes').text('확인');
		$('#yes').removeClass('btn_big_gray').addClass('btn_big_blue');
	}
	else {
		return;
	}
}

// 레이어 팝업 = 확인 클릭시
$('#yes').on('click', function() {

	ajax_data = $("#frm").serialize();
	$.ajax({
		url : "./investment_proc.php",
		type: "POST",
		data : ajax_data,
		beforeSend: function() { btn_event('send'); },
		success: function(data) {
			$('#ajax_return_txt').val(data);
			if(data=="SUCCESS" || data=="SUCCESS:ADVANCE_INVEST") {
				$.unblockUI();
				setTimeout(
					function() {
						alert('투자가 성공 하였습니다.');
						window.location.replace('/deposit/deposit.php');
					}, 0.38*1000
				);
				/*
				// 투자 성공 메세지창 출력
				$.blockUI({
					message: $('#complete2'),
					css: { border:'0', cursor:'default', width:'585px', top:'35%', left:'35%', position:'fixed' }
				});
				*/
			}
			else {
				if(data=="ERROR:DATA")                       { alert("전송데이터에 오류가 있습니다. 잠시 후 다시 실행하여 주십시요."); window.location.replace('/investment/invest_list.php'); }
				else if(data=="ERROR:LOGIN")                 { window.location.replace('/bbs/login.php'); }
				else if(data=="ERROR:DATE")                  { alert("투자 기간이 아닙니다."); }
				else if(data=="ERROR:INVEST_END")            { alert("본상품의 투자모집이 완료되어 투자를 진행 하실 수 없습니다."); }
				else if(data=="ERROR:BALANCE")               { if(confirm('예치금 계좌를 발급받지 않았습니다.\n예치금 계좌를 발급 받으시겠습니까?')) { window.location.href="/deposit/deposit.php"; } }
				else if(data=="ERROR:INVEST")                { alert("투자 가능한 금액을 초과 입력 하셨습니다."); }
				else if(data=="ERROR:MIN_PRICE")             { alert("투자 최소 금액 미만 입니다. 투자금액을 확인해 주세요."); $("input[name='invest_value']").focus(); }
				else if(data=="ERROR:MAX_PRICE")             { alert("투자 최대 금액을 초과 하였습니다. 투자금액을 확인해 주세요. "); $("input[name='invest_value']").focus(); }
				else if(data=="ERROR:MAX_INVEST_AMOUNT_OVER"){ alert("투자 최대가능 한도액을 초과 하였습니다.\n전문투자자 최대투자가능 한도액 : <?=$pro_max_invest_amount?>원"); $("input[name='invest_value']").focus(); }
				else if(data=="ERROR:GROUP_INVEST_AMOUNT_LIMITED") { alert("동일차주상품 투자한도금액을 초과 하였습니다."); $("input[name='invest_value']").focus(); }
				else if(data=="ERROR:INVEST_AMOUNT_LIMITED") { alert("전체 상품에 대한 투자가능금액이 초과되어 투자를 진행 하실 수 없습니다."); }
				else if(data=="ERROR:ADVANCE_INVEST_AMOUNT") { alert("사전 투자 가능한 금액을 초과 입력 하셨습니다."); }
				else if(data=="ERROR:MONEY_UNIT")            { alert("투자금액중 만원 미만 단위 금액이 확인되어 투자를 진행 하실 수 없습니다."); }
				else if(data=="ERROR:ADVANCE_INVEST_DATE")   { alert("사전 투자 가능 기간이 종료 되었습니다."); window.location.replace('/investment/investment.php?prd_idx=<?=$prd_idx?>'); }
				else if(data=="ERROR:ADVANCE_INVEST_END")    { alert("사전투자가 마감 되었습니다."); window.location.replace('/investment/investment.php?prd_idx=<?=$prd_idx?>'); }
				else if(data=="ERROR:CHECKED_LIMIT_MEMBER")  { alert("헬로펀딩과 고객님간의 협의에 의해 투자가 제한된 계정 입니다."); window.location.replace('/investment/investment.php?prd_idx=<?=$prd_idx?>'); }
			//else if(data=="ERROR:INVESTEDFROM_FINNQ")    { alert("타사 앱(핀크)을 통한 투자내역이 존재하여 추가 투자가 불가합니다."); }
			//else if(data=="ERROR:INVESTEDFROM_OLIGO")    { alert("타사 앱(올리고)을 통한 투자내역이 존재하여 추가 투자가 불가합니다."); }
			//else if(data=="ERROR:INVESTEDFROM_KAKAOPAY") { alert("타사 앱(카카오페이)을 통한 투자내역이 존재하여 추가 투자가 불가합니다."); }
				else if(data=="ERROR:P2PCTR_PAUSE")          { alert("투자가능시간이 아닙니다.\n중앙기록관리기관 점검 시간(23:20~00:40)에는 투자 신청 및 취소, 한도 조회가 불가능합니다."); window.location.replace('/investment/investment.php?prd_idx=<?=$prd_idx?>'); }
				else if(data=="ERROR:P2PCTR_UPDATE_FAIL")    { alert("중앙기록관리 투자한도 업데이트 오류로 투자가 되지 않습니다.\n잠시 후 다시 시도하십시요."); window.location.replace('/investment/investment.php?prd_idx=<?=$prd_idx?>'); }
				else if(data=="ERROR:P2PCTR_FAIL_CANCEL")    { alert("중앙기록관리 전송 오류!\n잠시 후 다시 시도하십시요."); }
				else if(data=="ERROR:BANK_PAUSE")            { alert("금융기관(신한은행) 점검 시간입니다. 투자 신청 및 취소, 한도 조회가 불가능합니다."); window.location.replace('/investment/investment.php?prd_idx=<?=$prd_idx?>'); }
				else if(data=="ERROR:DUPLICATE_INVEST")      { alert("동일한 금액의 중복 투자건으로 의심되는 투자요청 입니다.\n잠시 후 다시 시도하십시요."); }
				else if(data=="ERROR:INVEST_AMOUNT_LIMITED_PRPT") { alert("부동산 상품에 대한 투자가능금액이 초과되어 투자를 진행 하실 수 없습니다."); }
				else if(data=="KYC_START" || data=="KYC_ING" )	{ KYCPopup(); }
				else if(data=="ERROR:ING_PROCESS")           { alert("이전 투자건에 대한 처리가 진행중입니다."); }
				else { alert(data); }

				btn_event('exit');
				$.unblockUI();
				return;
			}
		},
		complete: function() { btn_event('exit'); },
		error: function(e) { alert('네트워크 에러 입니다. 잠시 후 다시 시도 하십시요.'); return; }
	});

});


function price_cutting(val) {

	var unit_price        = 10000;
	var invest_value_str  = '';
	var million_value     = 0;
	var invest_real_value = 0;

	invest_value = parseInt(parseInt(val) / unit_price) * unit_price;

	if(invest_value >= 100000000) {
		million_value     = parseInt(invest_value / 100000000);
		invest_value_str  = String(million_value) + '억';
		invest_value      = invest_value - (million_value * 100000000);
		invest_real_value = (million_value * 100000000);
	}

	if(invest_value > 0) {
		invest_value      = Math.floor(invest_value / unit_price);
		invest_real_value = invest_real_value+ (invest_value * unit_price);
		invest_value_str  = invest_value_str + Number_Format(String(invest_value)) + '만';
	}
	else{
		if(invest_value_str == '') invest_value_str = '0';
	}

	return invest_value_str;

}

var pattern = /^[0-9]+$/;
$(document).ready(function(){
	$("input[name='invest_value']").keyup(function(evt){

		// 숫자단위 쉽표 제거
		var invest_value = $("input[name='invest_value']").val();
		var invest_value_len = invest_value.length;
		for (i=0; i<invest_value_len; i++) {
			invest_value = invest_value.replace(',', '');
		}

		var unit_price        = 10000;
		var invest_value_str  = '';
		var million_value     = 0;
		var invest_real_value = 0;

		invest_value = Number(invest_value) * unit_price;

		if(invest_value > 0) {
			invest_value = parseInt(parseInt(invest_value) / unit_price) * unit_price;

			if(invest_value >= 100000000) {
				million_value     = parseInt(invest_value / 100000000);
				invest_value_str  = String(million_value) + '억';
				invest_value      = invest_value - (million_value * 100000000);
				invest_real_value = (million_value * 100000000);
			}

			if(invest_value > 0) {
				invest_value      = Math.floor(invest_value / unit_price);
				invest_real_value = invest_real_value+ (invest_value * unit_price);
				invest_value_str  = invest_value_str + Number_Format(String(invest_value)) + '만';
			}
			else {
				if(invest_value_str == "") invest_value_str = '0';
			}
		}
		else {
			invest_value_str = '0';
		}

	  $("input[name='ajax_invest_value']").val(invest_real_value);
		$("#invest_value_text").text(invest_value_str);
	});

	$("#invest_value").keyup(function(){ $(this).val( $(this).val().replace(/[^0-9]/g,"") ); });


	$("#btn_vacs").click(function(evt){
		$.blockUI({
			message: $('#complete3'),
			css: { border:'0', cursor:'default', width:'585px', top:'35%', left:'35%', position:'fixed' }
		});
	});

	$("#btn_invest").click(function(evt){
		var unit_price             = 10000;
		var invest_value           = 0;
		var min_invest_limit       = 0;
		var max_invest_limit       = 0;
		var invest_possible_amount = 0;
		var balance_value          = 0;

		var min_invest_limit = "";
		var max_invest_limit = "";

		min_invest_limit = Number($("input[name='min_invest_limit']").val());
		max_invest_limit = Number($("input[name='max_invest_limit']").val());

		// 숫자단위 쉽표 제거
		invest_value = $("input[name='invest_value']").val();
		invest_value_len = invest_value.length;
		for (i=0; i<invest_value_len; i++) {
			invest_value = invest_value.replace(',', '');
		}

		if(invest_value=='' || invest_value=='0') { alert("투자금액을 입력해주세요"); $("input[name='invest_value']").focus(); return; }

		invest_possible_amount = Number($("input[name='invest_possible_amount']").val());
		balance_value = Number($("input[name='balance_value']").val());
		invest_value  = Number(invest_value);
		invest_value  = invest_value * unit_price;

		if(invest_possible_amount==0) { alert("투자가능금액이 없습니다."); return; }
		if(invest_possible_amount < invest_value) { alert('투자가능금액을 초과 입력 하셨습니다.'); return; }
	//if(invest_possible_amount < invest_value) { alert('투자가능금액을 초과 입력 하셨습니다.\n\n - 현재 투자가능금액:    ' + number_format(invest_possible_amount) + '원'); return; }
		if($("input[name='ajax_invest_value']").val() < min_invest_limit) { alert("최소투자금액은 " + min_invest_limit + "원 입니다."); $("input[name='invest_value']").focus(); return; }
		if(max_invest_limit != '') { if($("input[name='ajax_invest_value']").val() > max_invest_limit) { alert("투자 최대 금액은 " + max_invest_limit + "원 입니다."); $("input[name='invest_value']").focus(); return; } }

		// 예치금-투자금액 비교
		if(balance_value < invest_value) {
			alert('예치금이 부족합니다. \n\n' +
			      ' - 투자 하실 금액 :   ' + price_cutting(invest_value) + '원\n' +
			      ' - 예치금 잔액 :   ' + price_cutting(balance_value) + '원\n\n' +
			      '가상계좌에 예치금을 입금한 뒤 투자해주세요');
			return;
		}

		if( $("input:checkbox[id='guide']").is(":checked")==false ) { $("input:checkbox[id='guide']").focus(); alert("이용약관에 동의해주세요"); return; }

		$("#value_text_show").text($("#invest_value_text").text());

		$.blockUI({
			message: $('#complete'),
			css: { border:'0', cursor:'default', width:'585px', top:'35%', left:'35%', position:'fixed' }
		});

	});
});

function Number_Format(fn){
	var str = fn;
	var Re = /[^0-9]/g;
	var ReN = /(-?[0-9]+)([0-9]{3})/;
	str = str.replace(Re,'');
	while (ReN.test(str)) {
		str = str.replace(ReN, "$1,$2");
	}
	return str;
}
</script>

<script type="text/javascript">
//실시간 포인트 갱신
/*
$(document).ready(function(){
	setInterval(function() {
		$.ajax({
			url : "/deposit/ajax_point_check.php",
			success: function(data) {
				$('#ajax_return_txt').val(data);
				// 단순출력항목
				$('#realtime_point').empty();
				$('#realtime_point').append(number_format(data));
        // 변환불가항목
				$('#now_point').empty();
				$('#now_point').val(data);
				// 실보유예치금 갱신
				$('#balance_value').empty();
				$('#balance_value').val(data);
			}
		});
	}, 3 * 1000);
});
*/
</script>

<!-- 본문내용 E N D -->
<?
if ($co['co_include_tail'])
    @include_once($co['co_include_tail']);
else
    include_once('./_tail.php');
?>