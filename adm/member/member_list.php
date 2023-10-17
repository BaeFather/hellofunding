<?
###############################################################################
## 2019-01-21 업데이트 : 주민번호, 전화번호, 계좌번호 암,복호화 추가
###############################################################################

include_once('./_common.php');

$sub_menu = '200100';
auth_check($auth[$sub_menu], "w");
//include_once('../secure_data_connect_log.php');

$html_title = "전체 회원";
$g5['title'] = $html_title.' 정보';

include_once (G5_ADMIN_PATH.'/admin.head.php');

while( list($k, $v) = each($_REQUEST) ) { if(!is_array($k)) ${$k} = trim($v); }

//print_rr($_GET,'font-size:12px');


if($start_date) $datetime_s = $start_date . ' 00:00:00';
if($end_date)   $datetime_e = $end_date . ' 23:59:59';
if($start_date && $end_date) {
	if($start_date > $end_date) msg_go("검색일 설정이 잘못되었습니다.");
}


//print_rr($_SESSION,'font-size:12px');

//-- ▼ 검색 필드 조합 끝 ▼ -----------------------------------------------------------
if(!$member_group) $member_group = "F";

$sql_search = "";
if($member_group)           $sql_search.= " AND A.member_group='$member_group' ";
if($member_type)            $sql_search.= " AND A.member_type='$member_type' ";
if($member_investor_type)   $sql_search.= " AND A.member_investor_type='$member_investor_type' ";
if($is_creditor=='Y')       $sql_search.= " AND A.is_creditor='Y' ";
if($is_owner_operator=='1') $sql_search.= " AND A.is_owner_operator='1' ";
if($is_sbiz_owner=='1')     $sql_search.= " AND A.is_sbiz_owner='1' ";
if($is_invest_manager=='1') $sql_search.= " AND A.is_invest_manager='1' ";
if($remit_fee=='1')         $sql_search.= " AND A.remit_fee='1' ";
if($mb_mailling=='1')       $sql_search.= " AND A.mb_mailling='1' ";
if($invested_mailling=='1') $sql_search.= " AND A.invested_mailling='1' ";
if($insidebank_after_trans_target) $sql_search.= " AND A.insidebank_after_trans_target='$insidebank_after_trans_target' ";
if($is_rest=='Y')           $sql_search.= " AND A.is_rest='Y' ";
if($mb_level) {
	$sql_search.= ($mb_level=='null') ? " AND A.mb_level='0' " : " AND A.mb_level='$mb_level' ";
}
else {
	$sql_search.= " AND A.mb_level = 1";
}

if($mb_sms=='1') $sql_search.= " AND A.mb_sms='1' ";

if( $dateField && ($start_date || $end_date) ) {
	if($dateField=='kyc_reg_dd' || $dateField=='kyc_allow_dd') {
		if($start_date) $sql_search.= " AND A.{$dateField}>='$start_date' ";
		if($end_date)   $sql_search.= " AND A.{$dateField}<='$end_date' ";
	}
	else {
		if($start_date) $sql_search.= " AND $dateField >= '$datetime_s' ";
		if($end_date)   $sql_search.= " AND $dateField <= '$datetime_e' ";
	}
}

if($start_point && $end_point) {
	$sql_search.= " AND A.mb_point BETWEEN '$start_point' AND '$end_point' ";
}
else {
	if($start_point) $sql_search.= " AND A.mb_point >= '$start_point' ";
	if($end_point)   $sql_search.= " AND A.mb_point <= '$end_point' ";
}
if($receive_method) {
	$sql_search.= ($receive_method=='unknown') ? " AND A.receive_method=''" : " AND A.receive_method='$receive_method' ";
}

if($platform) {
	if($platform=='hello')                  $sql_search.= " AND (finnq_userid='' AND wowstar_userid='' AND chosun_userid='' AND r114_userid='' AND oligo_userid='')";
	else if($platform=='finnq')             $sql_search.= " AND finnq_userid!='' AND mb_datetime=finnq_rdate";
	else if($platform=='hello-finnq')       $sql_search.= " AND finnq_userid!=''";
	else if($platform=='hktvwowstar')       $sql_search.= " AND wowstar_userid!='' AND mb_datetime=wowstar_rdate";
	else if($platform=='hello-hktvwowstar') $sql_search.= " AND wowstar_userid!=''";
	else if($platform=='chosun')            $sql_search.= " AND chosun_userid!='' AND mb_datetime=chosun_rdate";
	else if($platform=='hello-chosun')      $sql_search.= " AND chosun_userid!=''";
	else if($platform=='r114')              $sql_search.= " AND r114_userid!='' AND mb_datetime=r114_rdate";
	else if($platform=='hello-r114')        $sql_search.= " AND r114_userid!=''";
	else if($platform=='oligo')             $sql_search.= " AND oligo_userid!='' AND mb_datetime=oligo_rdate";
	else if($platform=='hello-oligo')       $sql_search.= " AND oligo_userid!=''";
	else if($platform=='itembay')           $sql_search.= " AND itembay_userid!=''";
	else if($platform=='kakaopay')          $sql_search.= " AND kakaopay_userid!='' AND mb_datetime=kakaopay_rdate";
	else if($platform=='hello-kakaopay')    $sql_search.= " AND kakaopay_userid!=''";
}

if($bank_code) {
	$sql_search.= " AND bank_code='".$bank_code."'";
}

if($key_search && $keyword) {
	if( $key_search == 'A.mb_no' || $key_search == 'A.mb_hp_key') {
		if($key_search == 'A.mb_no' && preg_match("/\,/", $keyword) ) {
			$sql_search.= " AND $key_search IN(".preg_replace("/( )/", "", $keyword).") ";
		}
		else {
			$sql_search.= " AND $key_search='$keyword' ";
		}
	}
	else if( in_array($key_search, array('A.mb_hp', 'A.account_num')) ) {
		$sql_search.= " AND $key_search='".masterEncrypt($keyword, false)."' ";
	}
	else if($key_search=='A.event_id') {
		if( in_array($keyword, array('100B','100BEVENT2')) ) {
			$sql_search.=  " AND event_id='".$keyword."'";
		}
	}
	else if($key_search == "B.site_id")  {
		$sql_search.= " AND CONCAT(site_id,' ',site_ca) LIKE '%$keyword%' ";
	}
	else if( in_array($key_search, array('A.kyc_reg_dd','A.kyc_allow_dd')) )  {
		$sql_search.= " AND $key_search >= '$keyword' ";
	}
	else {
		$sql_search.= " AND $key_search LIKE '%$keyword%' ";
	}
}

if($pid) {
	$sql_search.= " AND A.pid='".$pid."'";
}

$limit20ds = date('Y', strtotime('-30 years'));
$limit20de = date('Y', strtotime('-21 years'));
$limit30ds = date('Y', strtotime('-40 years'));
$limit30de = date('Y', strtotime('-31 years'));
$limit40ds = date('Y', strtotime('-50 years'));
$limit40de = date('Y', strtotime('-41 years'));
$limit50ds = date('Y', strtotime('-60 years'));
$limit50de = date('Y', strtotime('-51 years'));
$limit60ds = date('Y', strtotime('-70 years'));
$limit60de = date('Y', strtotime('-61 years'));

if($age) {
	if($age=='10') $sql_search.= " AND LEFT(A.mb_birth,4) > '$limit20de'";
	if($age=='20') $sql_search.= " AND LEFT(A.mb_birth,4) BETWEEN '$limit20ds' AND '$limit20de'";
	if($age=='30') $sql_search.= " AND LEFT(A.mb_birth,4) BETWEEN '$limit30ds' AND '$limit30de'";
	if($age=='40') $sql_search.= " AND LEFT(A.mb_birth,4) BETWEEN '$limit40ds' AND '$limit40de'";
	if($age=='50') $sql_search.= " AND LEFT(A.mb_birth,4) BETWEEN '$limit50ds' AND '$limit50de'";
	if($age=='60') $sql_search.= " AND LEFT(A.mb_birth,4) BETWEEN '$limit60ds' AND '$limit60de'";
	if($age=='70') $sql_search.= " AND LEFT(A.mb_birth,4) < '$limit60ds'";
}

if($foreigner) {
	$sql_search.= ($foreigner=='none') ? " AND foreigner = ''" : " AND foreigner = '1'";
}



if($mb_10) {
	$sql_search.= " AND mb_10='$mb_10' ";
}

if($kyc_allow_yn) {
	$sql_search.= " AND kyc_allow_yn='$kyc_allow_yn' ";
}

//-- ▲ 검색 필드 조합 끝 ▲ -----------------------------------------------------------

$sql_order = "";
if($sort_field) {
	if($sort_field=='A.mb_no') {
		$sql_order.= $sort_field." ".$sort."";
	}
	else {
		$sql_order.= $sort_field." ".$sort.", ";
		$sql_order.= " A.mb_no DESC ";
	}
}
else {
	$sql_order.= " A.mb_no DESC ";
}


if(false) {
	// 누적 투자금 합계	(slow query 발생)
	$tot_inv_sql = "
		SELECT
			SUM(SA.amount) total_amount
		FROM
			g5_member A
		LEFT JOIN
			cf_visit_status B ON A.vi_idx=B.idx
		LEFT JOIN
			cf_product_invest SA ON A.mb_no=SA.member_idx
		LEFT JOIN
			cf_product SB ON SA.product_idx=SB.idx
		WHERE 1
			AND SA.invest_state='Y' AND SB.state IN('','1','2','5')
			$sql_search";
	$tot_inv_row = sql_fetch($tot_inv_sql);
	$tot_inv_amt = $tot_inv_row["total_amount"];
}

$sql = "
	SELECT
		COUNT(mb_no) AS cnt,
		IFNULL(SUM(mb_point), 0) AS sum_point
	FROM
		g5_member A
	LEFT JOIN
		cf_visit_status B  ON A.vi_idx=B.idx
	WHERE 1=1
		$sql_search";

$row = sql_fetch($sql);
$total_count = $row['cnt'];
$total_point = $row['sum_point'];

$rows = 10;
$total_page  = ceil($total_count / $rows);		// 전체 페이지 계산
if($page < 1) $page = 1;											// 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows;						// 시작 열을 구함


$sql = "
	SELECT
		A.mb_no, A.mb_id, A.mb_level, A.member_group, A.member_type, A.member_investor_type, A.is_creditor, A.is_owner_operator, A.mb_point,
		A.mb_name, A.mb_co_name, A.mb_email, A.mb_hp, A.mb_hp_key, A.mb_datetime, A.edit_datetime, A.mb_today_login, A.mb_login_ip, A.login_cnt, A.mb_ip, A.is_rest, A.mb_mailling, A.mb_sms,
		A.receive_method, A.bank_code, A.account_num, A.account_num_key, A.bank_private_name, A.va_bank_code2, A.virtual_account2, A.va_private_name2,
		A.syndi_id, A.finnq_userid, A.finnq_rdate, A.wowstar_userid, A.wowstar_rdate, A.chosun_userid, A.chosun_rdate, A.tvtalk_userid, A.tvtalk_rdate, A.r114_userid, A.r114_rdate, A.oligo_userid, A.oligo_rdate,A.itembay_userid, A.itembay_rdate, A.kakaopay_userid, A.kakaopay_rdate,
		A.event_id, A.pid, A.rec_mb_id,
		A.id_card, A.business_license, A.bankbook, A.loan_co_license,
		A.kyc_reg_dd, A.kyc_allow_yn, A.kyc_allow_dd, A.kyc_next_dd,
		B.rdate AS vi_date, B.rhour, B.referer, B.site_id, B.site_ca, B.keyword, B.is_paid,
		(SELECT COUNT(mb_no) FROM g5_member WHERE rec_mb_no=A.mb_no AND virtual_account!='' AND rec_mb_id!='') AS recommend_count,
		(SELECT COUNT(SA.idx) FROM cf_product_invest SA LEFT JOIN cf_product SB ON SA.product_idx=SB.idx WHERE SA.member_idx=A.mb_no AND SA.invest_state='Y' AND SB.state IN('1','2','5','8')) AS invest_count,
		(SELECT IFNULL(SUM(SA.amount),0) FROM cf_product_invest SA LEFT JOIN cf_product SB ON SA.product_idx=SB.idx  WHERE SA.member_idx=A.mb_no AND SA.invest_state='Y' AND SB.state IN('1','2','5','8')) AS invest_amount,
		(SELECT rights_end_date FROM investor_type_change_request WHERE mb_no=A.mb_no AND allow='Y' ORDER BY idx DESC LIMIT 1) AS rights_end_date,
		(SELECT COUNT(SA.idx) FROM cf_product_invest SA LEFT JOIN cf_product SB ON SA.product_idx=SB.idx WHERE SA.member_idx=A.mb_no AND SA.invest_state='Y' AND SB.state IN('','1','8')) AS live_invest_count,
		(
			( SELECT IFNULL(SUM(SA.amount),0) FROM cf_product_invest SA LEFT JOIN cf_product SB ON SA.product_idx=SB.idx WHERE SA.member_idx=A.mb_no AND SA.invest_state='Y' AND SB.state NOT IN('4','9') ) -
			( SELECT IFNULL(SUM(GA.principal),0) FROM cf_product_give GA LEFT JOIN cf_product GB ON GA.product_idx=GB.idx WHERE GA.member_idx=A.mb_no )
		) AS live_invest_amount
	FROM
		g5_member A
	LEFT JOIN
		cf_visit_status B  ON A.vi_idx=B.idx
	WHERE 1=1
		$sql_search
	ORDER BY
		$sql_order
	LIMIT
		$from_record, $rows";
//print_rr($sql, 'font-size:12px');
$result = sql_query($sql);
$rcount = $result->num_rows;

for($i=0; $i<$rcount; ++$i) {
	$LIST[$i] = sql_fetch_array($result);
}
sql_free_result($result);

$list_count = count($LIST);

$num = $total_count - $from_record;

?>

<style>
.min60 {min-width:60px;}
.min80 {min-width:80px;}
</style>

<div class="tbl_head02 tbl_wrap">
<? if($page == '1') { ?>
	<div style="margin-bottom:8px;">
		<ul style="list-style:none;display:inline-block; padding:0; margin:0 0 9px 0">
			<li style="float:left;">
				<table class="table-bordered" style="font-size:14px">
					<tr>
						<th colspan="6" style="background:#F0FFF0;">투자회원</th>
					</tr>
					<tr>
						<th class="min80" style="background:#F0FFF0;">개인</th>
						<th class="min80" style="background:#F0FFF0;">법인</th>
						<th class="min80" style="background:#F0FFF0;">합계</th>
						<th class="min80" style="background:#F0FFF0;">승인대기</th>
						<th class="min80" style="background:#F0FFF0;">승인거절</th>
						<th class="min80" style="background:#F0FFF0;">휴면계정</th>
					</tr>
					<tr align="center">
						<td><a id="cnt1_1" class="counter" href="?member_group=F&member_type=1">&nbsp;</span></td>
						<td><a id="cnt1_2" class="counter" href="?member_group=F&member_type=2"></a></td>
						<td><a id="cnt1_3" class="counter" href="?member_group=F" style="color:#FF2222"></a></td>
						<td><a id="cnt1_4" class="counter" href="?mb_level=null"></a></td>
						<td><a id="cnt1_5" class="counter" href="?mb_level=100"></a></td>
						<td><a id="cnt1_6" class="counter" href="?is_rest=Y"></a></td>
					</tr>
				</table>
			</li>
			<li style="float:left;margin-left:9px;">
				<table class="table-bordered" style="font-size:14px">
					<tr>
						<th colspan="3" style="background:#FFEFD5">대출회원</th>
					</tr>
					<tr>
						<th class="min80" style="background:#FFEFD5">개인</th>
						<th class="min80" style="background:#FFEFD5">법인</th>
						<th class="min80" style="background:#FFEFD5">합계</th>
					</tr>
					<tr align="center">
						<td><a id="cnt2_1" class="counter" href="?member_group=L&member_type=1">&nbsp;</a></td>
						<td><a id="cnt2_2" class="counter" href="?member_group=L&member_type=2"></a></td>
						<td><a id="cnt2_3" class="counter" href="?member_group=L"></a></td>
					</tr>
				</table>
			</li>
			<li style="float:left;margin-left:9px;">
				<table class="table-bordered" style="font-size:14px">
					<tr>
						<th colspan="7" style="background:#F8F8EF">투자회원 연령대별 (개인회원, 만나이)</th>
					</tr>
					<tr>
						<th class="min80" style="background:#F8F8EF">10대 이하</th>
						<th class="min80" style="background:#F8F8EF">20대</th>
						<th class="min80" style="background:#F8F8EF">30대</th>
						<th class="min80" style="background:#F8F8EF">40대</th>
						<th class="min80" style="background:#F8F8EF">50대</th>
						<th class="min80" style="background:#F8F8EF">60대</th>
						<th class="min80" style="background:#F8F8EF">70대 이상</th>
					</tr>
					<tr align="center">
						<td><a id="cnt3_1" class="counter" href="?member_type=1&age=10">&nbsp;</a></td>
						<td><a id="cnt3_2" class="counter" href="?member_type=1&age=20"></a></td>
						<td><a id="cnt3_3" class="counter" href="?member_type=1&age=30"></a></td>
						<td><a id="cnt3_4" class="counter" href="?member_type=1&age=40"></a></td>
						<td><a id="cnt3_5" class="counter" href="?member_type=1&age=50"></a></td>
						<td><a id="cnt3_6" class="counter" href="?member_type=1&age=60"></a></td>
						<td><a id="cnt3_7" class="counter" href="?member_type=1&age=70"></a></td>
					</tr>
				</table>
			</li>
			<li style="float:left;margin-left:9px;">
				<table class="table-bordered" style="font-size:14px">
					<tr>
						<th colspan="2" style="background:#FFF5EE">투자회원 알림수신 설정</th>
					</tr>
					<tr>
						<th class="min80" style="background:#FFF5EE">SMS</th>
						<th class="min80" style="background:#FFF5EE">Email</th>
					</tr>
					<tr align="center">
						<td><span id="cnt4_1">&nbsp;</span></td>
						<td><span id="cnt4_2"></span></td>
					</tr>
				</table>
			</li>
		</ul>
		<ul style="list-style:none;display:inline-block; padding:0;">
			<li style="float:left;">
				<table class="table-bordered" style="font-size:14px">
					<tr>
						<th colspan="7" style="background:#F0F8FF">제휴사 유치</th>
					</tr>
					<tr>
						<th class="min80" style="background:#F0F8FF">한경TV</th>
						<th class="min80" style="background:#F0F8FF">핀크</th>
						<th class="min80" style="background:#F0F8FF">땅집고</th>
						<th class="min80" style="background:#F0F8FF">부동산114</th>
						<th class="min80" style="background:#F0F8FF">아이템베이</th>
						<th class="min80" style="background:#F0F8FF">올리고</th>
						<!--<th class="min80" style="background:#F0F8FF">카카오페이</th>-->
					</tr>
					<tr align="center">
						<td><a id="cnt5_1" class="counter" href="?platform=hktvwowstar">&nbsp;</td>
						<td><a id="cnt5_2" class="counter" href="?platform=finnq"></a></td>
						<td><a id="cnt5_3" class="counter" href="?platform=chosun"></a></td>
						<td><a id="cnt5_4" class="counter" href="?platform=r114"></a></td>
						<td><a id="cnt5_5" class="counter" href="?platform=itembay"></a></td>
						<td><a id="cnt5_6" class="counter" href="?platform=oligo"></a></td>
						<!--<td><a id="cnt5_7" class="counter" href="?platform=kakaopay"></a></td>-->
					</tr>
				</table>
			</li>
			<li style="float:left;margin-left:9px;">
				<table class="table-bordered" style="font-size:14px">
					<tr>
						<th colspan="7" style="background:#FFF0F5">마케팅 제휴사 유치</th>
					</tr>
					<tr>
						<th class="min80" style="background:#FFF0F5">티비톡</th>
						<th class="min80" style="background:#FFF0F5">캐시카우</th>
						<th class="min80" style="background:#FFF0F5">투믹스</th>
						<th class="min80" style="background:#FFF0F5">공감엠엔씨</th>
						<th class="min80" style="background:#FFF0F5">네이버페이</th>
						<th class="min80" style="background:#FFF0F5">네이버GFA</th>
						<th class="min80" style="background:#FFF0F5">오케이캐쉬백</th>
					</tr>
					<tr align="center">
						<td><a id="cnt6_1" class="counter" href="?pid=tvtalk">&nbsp;</a></td>
						<td><a id="cnt6_2" class="counter" href="?pid=cashcow"></a></td>
						<td><a id="cnt6_3" class="counter" href="?pid=toomics"></a></td>
						<td><a id="cnt6_4" class="counter" href="?pid=gmnc"></a></td>
						<td><a id="cnt6_5" class="counter" href="?pid=naverpay"></a></td>
						<td><a id="cnt6_6" class="counter" href="?pid=N_gfa"></a></td>
						<td><a id="cnt6_7" class="counter" href="?pid=okcashbag"></a></td>
					</tr>
				</table>
			</li>
			<li style="float:left;margin-left:9px;">
				<table class="table-bordered" style="font-size:14px">
					<tr>
						<th colspan="2" style="background:#FFFACD">외부행사 유치</th>
					</tr>
					<tr>
						<th class="min80" style="background:#FFFACD">서울머니쇼</th>
						<th class="min80" style="background:#FFFACD">동아박람회</th>
					</tr>
					<tr align="center">
						<td><a id="cnt7_1" class="counter" href="?key_search=A.rec_mb_id&keyword=seoul_money_show">&nbsp;</a></td>
						<td><a id="cnt7_2" class="counter" href="?key_search=A.rec_mb_id&keyword=donga_expo"></a></td>
					</tr>
				</table>
			</li>
			<li style="float:left;margin-left:9px;">
				<table class="table-bordered" style="font-size:14px">
					<tr>
						<th colspan="2" style="background:#E0FFFF">자체이벤트 유치</th>
					</tr>
					</tr>
						<th class="min80" style="background:#E0FFFF">천억돌파 이벤트</th>
						<th class="min80" style="background:#E0FFFF">럭키박스 이벤트</th>
					</tr>
					<tr align="center">
						<td><a id="cnt8_1" class="counter" href="?key_search=A.event_id&keyword=100B">&nbsp;</a></td>
						<td><a id="cnt8_2" class="counter" href="?key_search=A.event_id&keyword=100BEVENT2"></a></td>
					</tr>
				</table>
			</li>
			<li style="float:left;margin-left:9px;">
				<table class="table-bordered" style="font-size:14px">
					<tr>
						<th colspan="4" style="background:#F8F8EF">KYC (등록일1개월기준)</th>
					</tr>
					</tr>
						<th class="min80" style="background:#F8F8EF">대기</th>
						<th class="min80" style="background:#F8F8EF">심사중</th>
						<th class="min80" style="background:#F8F8EF">승인</th>
						<th class="min80" style="background:#F8F8EF">반려</th>
					</tr>
					<tr align="center">
						<td><a id="cnt9_1" class="counter" href="?sort_field=A.edit_datetime&sort=DESC&dateField=kyc_reg_dd&start_date=<?=date("Y-m-d", strtotime("first day of -1month"))?>&kyc_allow_yn=W" style="color:#FF2222">&nbsp;</a></td>
						<td><a id="cnt9_2" class="counter" href="?sort_field=A.edit_datetime&sort=DESC&dateField=kyc_reg_dd&start_date=<?=date("Y-m-d", strtotime("first day of -1month"))?>&kyc_allow_yn=I"></a></td>
						<td><a id="cnt9_3" class="counter" href="?sort_field=A.edit_datetime&sort=DESC&dateField=kyc_reg_dd&start_date=<?=date("Y-m-d", strtotime("first day of -1month"))?>&kyc_allow_yn=Y"></a></td>
						<td><a id="cnt9_4" class="counter" href="?sort_field=A.edit_datetime&sort=DESC&dateField=kyc_reg_dd&start_date=<?=date("Y-m-d", strtotime("first day of -1month"))?>&kyc_allow_yn=N"></a></td>
					</tr>
				</table>
			</li>
		</ul>
	</div>

	<script src="//cdnjs.cloudflare.com/ajax/libs/waypoints/2.0.3/waypoints.min.js"></script>
	<script src="/js/jquery.counterup.min.js"></script>
	<script>
	$(document).ready(function() {
		$.ajax({
			url : "member_list.ajax.php",
			dataType: "json",
			type: "post",
			success:function(data) {
				$('#cnt1_1').html(data.cnt1_1);
				$('#cnt1_2').html(data.cnt1_2);
				$('#cnt1_3').html(data.cnt1_3);
				$('#cnt1_4').html(data.cnt1_4);
				$('#cnt1_5').html(data.cnt1_5);
				$('#cnt1_6').html(data.cnt1_6);

				$('#cnt2_1').html(data.cnt2_1);
				$('#cnt2_2').html(data.cnt2_2);
				$('#cnt2_3').html(data.cnt2_3);

				$('#cnt3_1').html(data.cnt3_1);
				$('#cnt3_2').html(data.cnt3_2);
				$('#cnt3_3').html(data.cnt3_3);
				$('#cnt3_4').html(data.cnt3_4);
				$('#cnt3_5').html(data.cnt3_5);
				$('#cnt3_6').html(data.cnt3_6);
				$('#cnt3_7').html(data.cnt3_7);

				$('#cnt4_1').html(data.cnt4_1);
				$('#cnt4_2').html(data.cnt4_2);

				$('#cnt5_1').html(data.cnt5_1);
				$('#cnt5_2').html(data.cnt5_2);
				$('#cnt5_3').html(data.cnt5_3);
				$('#cnt5_4').html(data.cnt5_4);
				$('#cnt5_5').html(data.cnt5_5);
				$('#cnt5_6').html(data.cnt5_6);
				//$('#cnt5_7').html(data.cnt5_7);

				$('#cnt6_1').html(data.cnt6_1);
				$('#cnt6_2').html(data.cnt6_2);
				$('#cnt6_3').html(data.cnt6_3);
				$('#cnt6_4').html(data.cnt6_4);
				$('#cnt6_5').html(data.cnt6_5);
				$('#cnt6_6').html(data.cnt6_6);
				$('#cnt6_7').html(data.cnt6_7);

				$('#cnt7_1').html(data.cnt7_1);
				$('#cnt7_2').html(data.cnt7_2);

				$('#cnt8_1').html(data.cnt8_1);
				$('#cnt8_2').html(data.cnt8_2);

				$('#cnt9_1').html(data.cnt9_1);
				$('#cnt9_2').html(data.cnt9_2);
				$('#cnt9_3').html(data.cnt9_3);
				$('#cnt9_4').html(data.cnt9_4);

				//$('.counter').counterUp({ delay: 1, time: 100 });

			},
			error: function () { console.log(); }
		});
	});
	</script>
<? } ?>

	<!-- 검색영역 START -->
	<div style="display:inline-block;line-height:28px;margin-bottom:8px;">
		<form id="member_list_frm" method="get" action="/adm/member/member_list.php" class="form-horizontal">
		<input type="hidden" name="token" value="">
		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>
				<select name="member_group" id="member_group" class="form-control input-sm" style="width:150px">
					<option value="F" <? if($member_group=='F'){echo 'selected';} ?>>투자회원</option>
					<option value="L" <? if($member_group=='L'){echo 'selected';} ?>>대출회원</option>
				</select>
			</li>
			<li>
				<select name="member_type" id="member_type" class="form-control input-sm" style="width:150px">
					<option value="">회원구분</option>
					<option value="1" <? if($member_type=='1'){echo 'selected';} ?>>개인회원</option>
					<option value="2" <? if($member_type=='2'){echo 'selected';} ?>>법인회원</option>
					<option value="3" <? if($member_type=='3'){echo 'selected';} ?>>SNS회원</option>
				</select>
			</li>
			<li>
				<select name="member_investor_type" id="member_investor_type" <?=($member_type=='1')?'':'disabled'?> class="form-control input-sm" style="width:150px">
					<option value="">투자자구분</option>
<?
$ARR_KEYS = array_keys($INDI_INVESTOR);
for($i=0; $i<count($INDI_INVESTOR); ++$i) {
	$selected = ($member_investor_type==$ARR_KEYS[$i]) ? "selected" : "";
?>
					<option value="<?=$ARR_KEYS[$i]?>" <?=$selected?>><?=$INDI_INVESTOR[$ARR_KEYS[$i]]['title']?></option>
<?
}
?>
				</select>
			</li>
			<li>
				<select name="receive_method" class="form-control input-sm">
					<option value="">원리금수취방식</option>
					<option value="1" <? if($receive_method == '1'){echo 'selected';} ?>>환급계좌</option>
					<option value="2" <? if($receive_method == '2'){echo 'selected';} ?>>예치금(가상계좌)</option>
					<option value="unknown" <? if($receive_method == 'unknown'){echo 'selected';} ?>>미지정</option>
				</select>
			</li>
			<li>
				<select name="platform" class="form-control input-sm">
					<option value="">::가입플랫폼::</option>
					<option value="hello" <?if($platform=='hello'){ echo "selected";} ?>>헬로펀딩</option>
<?
	$scount = count($CONF['SYNDICATOR']);
	$skey = array_keys($CONF['SYNDICATOR']);
	for($i=0; $i<$scount; ++$i) {
		$selected = ($skey[$i]== $platform) ? "selected" : "";
		$selected2 = ('hello-'.$skey[$i]== $platform) ? "selected" : "";
		echo "					<option value='".$skey[$i]."' $selected>".$CONF['SYNDICATOR'][$skey[$i]]['name']."</option>\n";
		echo "					<option value='hello-".$skey[$i]."' $selected2>".$CONF['SYNDICATOR'][$skey[$i]]['name']."-기존자사회원포함</option>\n";
	}
?>
				</select>
			</li>
			<li>
				<select name="bank_code" class="form-control input-sm">
					<option value="">::환급계좌은행::</option>
<?

	$bank_res = sql_query("
		SELECT
			A.bank_code, A.bank
			-- , (SELECT COUNT(mb_no) FROM g5_member WHERE member_group='F' AND mb_level='1' AND bank_code=A.bank_code) AS use_count
		FROM
			bank_info A
		WHERE 1
			AND display='1'
		ORDER BY
			A.display DESC, A.favorite DESC, A.bank_code ASC");
	$bank_rows = $bank_res->num_rows;
	for($i=0; $i<$bank_rows; ++$i) {
		$BANK_ROW = sql_fetch_array($bank_res);

		$selected = ($BANK_ROW['bank_code']== $bank_code) ? "selected" : "";
		echo "					<option value='".$BANK_ROW['bank_code']."' $selected>".$BANK_ROW['bank']."</option>\n";
	}
?>
				</select>
			</li>
			<li>
				<select name="kyc_allow_yn" class="form-control input-sm">
					<option value="">::고객확인(KYC)::</option>
					<option value="Y" <?=($kyc_allow_yn=='Y')?'selected':'';?>>승인</option>
					<option value="W" <?=($kyc_allow_yn=='W')?'selected':'';?>>대기</option>
					<option value="N" <?=($kyc_allow_yn=='N')?'selected':'';?>>반려</option>
				</select>
			</li>
		</ul>


		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>
				<select name="dateField" class="form-control input-sm">
					<option value="">::데이트 필드선택::</option>
					<option value="A.mb_datetime" <?=($dateField=='A.mb_datetime')?'selected':''?>>가입일</option>
					<option value="A.mb_today_login" <?=($dateField=='A.mb_today_login')?'selected':''?>>최종로그인일</option>
					<option value="A.edit_datetime" <?=($dateField=='A.edit_datetime')?'selected':''?>>본인정보수정일</option>
					<option value="kyc_reg_dd" <?=($dateField=='kyc_reg_dd')?'selected':''?>>KYC등록/갱신일</option>
					<option value="kyc_allow_dd" <?=($dateField=='kyc_allow_dd')?'selected':''?>>KYC승인일</option>
				</select>
			</li>
			<li><input type="text" name="start_date" value="<?=$start_date?>" class="form-control input-sm datepicker" style="width:120px" readonly></li>
			<li>~</li>
			<li><input type="text" name="end_date" value="<?=$end_date?>" class="form-control input-sm datepicker" style="width:120px" readonly></li>
			<li></li>
			<li>예치금</li>
			<li><input type="text" name="start_point" value="<?=$start_point?>" class="form-control input-sm" style="width:120px"></li>
			<li>~</li>
			<li><input type="text" name="end_point" value="<?=$end_point?>" class="form-control input-sm" style="width:120px"></li>
			<li>
				<select name="age" class="form-control input-sm">
					<option value=''>연령대 (만나이)</option>
					<option value='10' <?=($age=='10')?'selected':'';?>>10대 이하</option>
					<option value='20' <?=($age=='20')?'selected':'';?>>20대</option>
					<option value='30' <?=($age=='30')?'selected':'';?>>30대</option>
					<option value='40' <?=($age=='40')?'selected':'';?>>40대</option>
					<option value='50' <?=($age=='50')?'selected':'';?>>50대</option>
					<option value='60' <?=($age=='60')?'selected':'';?>>60대</option>
					<option value='70' <?=($age=='70')?'selected':'';?>>70대 이상</option>
				</select>
			</li>
			<li>
				<select name="foreigner" class="form-control input-sm">
					<option value=''>내/외국인여부</option>
					<option value='none' <?=($foreigner=='none')?'selected':'';?>>내국인</option>
					<option value='1' <?=($foreigner=='1')?'selected':'';?>>외국인</option>
				</select>
			</li>
			<li>
				<select name="pid" class="form-control input-sm">
					<option value=''>마케팅제휴사ID</option>
<?
$PARTNER = array_keys($CONF['PARTNER']);
$PARTNER = array_reverse($PARTNER);

for($i=0; $i<count($CONF['PARTNER']); ++$i) {
	$selected = ($PARTNER[$i] == $pid) ? 'selected' : '';
	echo "<option value='".$PARTNER[$i]."' $selected>".$CONF['PARTNER'][$PARTNER[$i]]['name']." (".$PARTNER[$i].")</option>\n";
}
?>
				</select>
			</li>
		</ul>

		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li><label class="checkbox-inline"><input type="checkbox" name="mb_10" value="1" <? if($mb_10=='1'){echo 'checked';} ?>> 임직원</label></li>
			<li><label class="checkbox-inline"><input type="checkbox" name="is_owner_operator" value="1" <? if($is_owner_operator=='1'){echo 'checked';} ?>> 개인사업자</label></li>
			<li><label class="checkbox-inline"><input type="checkbox" name="is_creditor" value="Y" <? if($is_creditor=='Y'){echo 'checked';} ?>> 대부업</label></li>
			<li><label class="checkbox-inline"><input type="checkbox" name="remit_fee" value="1" <? if($remit_fee=='1'){echo 'checked';} ?>> 플랫폼수수료면제</label></li>
			<li><label class="checkbox-inline"><input type="checkbox" name="is_sbiz_owner" value="1" <? if($is_sbiz_owner=='1'){echo 'checked';} ?>> 소상공인우대정책대상자</label></li>
			<li><label class="checkbox-inline"><input type="checkbox" name="is_invest_manager" value="1" <? if($is_invest_manager=='1'){echo 'checked';} ?>> 자산운용사</label></li>
			<li><label class="checkbox-inline"><input type="checkbox" name="insidebank_after_trans_target" value="1" <? if($insidebank_after_trans_target=='1'){echo 'checked';} ?>> 예치금전환대상자(세틀뱅크→신한)</label></li>
			<li><label class="checkbox-inline" style="margin-left:30px;"><input type="checkbox" name="is_rest" value="Y" <? if($is_rest=='Y'){echo 'checked';} ?>> 휴면계정</label></li>
			<li><label class="checkbox-inline"><input type="checkbox" name="mb_mailling" value="1" <? if($mb_mailling=='1'){echo 'checked';} ?>> 메일수신</label></li>
			<li><label class="checkbox-inline"><input type="checkbox" name="mb_sms" value="1" <? if($mb_sms=='1'){echo 'checked';} ?>> SMS수신</label></li>
			<li><label class="checkbox-inline"><input type="checkbox" name="invested_mailling" value="1" <? if($invested_mailling=='1'){echo 'checked';} ?>> 투자설명서 발급동의</label></li>
		</ul>

		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>
				<select id="key_search" name="key_search" class="form-control input-sm" style="width:150px">
					<option value="">필드선택</option>
					<option value="A.mb_no" <? if($key_search == 'A.mb_no'){echo 'selected';} ?>>회원번호</option>
					<option value="A.mb_id" <? if($key_search == 'A.mb_id'){echo 'selected';} ?>>아이디</option>
					<option value="A.mb_email" <? if($key_search == 'A.mb_email'){echo 'selected';} ?>>이메일</option>
					<option value="A.mb_name" <? if($key_search == 'A.mb_name'){echo 'selected';} ?>>이름(담당자명)</option>
					<option value="A.mb_co_name" <? if($key_search == 'A.mb_co_name'){echo 'selected';} ?>>상호명</option>
					<option value="A.mb_co_reg_num" <? if($key_search == 'A.mb_co_reg_num'){echo 'selected';} ?>>사업자번호</option>
					<option value="A.mb_hp" <? if($key_search == 'A.mb_hp'){echo 'selected';} ?>>휴대폰</option>
					<option value="A.mb_hp_key" <? if($key_search == 'A.mb_hp_key'){echo 'selected';} ?>>휴대폰(뒷 4자리)</option>
					<option value="A.virtual_account2" <? if($key_search == 'A.virtual_account2'){echo 'selected';} ?>>가상계좌번호(신한)</option>
					<option value="A.virtual_account" <? if($key_search == 'A.virtual_account'){echo 'selected';} ?>>가상계좌번호(세틀뱅크)</option>
					<option value="A.account_num" <? if($key_search == 'A.account_num'){echo 'selected';} ?>>환급계좌번호</option>
					<option value="A.bank_private_name" <? if($key_search == 'A.bank_private_name'){echo 'selected';} ?>>환급계좌예금주</option>
					<option value="A.syndi_userid" <? if($key_search == 'A.syndi_userid'){echo 'selected';} ?>>신디케이션 회원인덱스</option>
					<option value="A.rec_mb_id" <? if($key_search == 'A.rec_mb_id'){echo 'selected';} ?>>추천인아이디</option>
					<option value="A.pid" <? if($key_search == 'A.pid'){echo 'selected';} ?>>마케팅제휴사ID</option>
					<option value="A.event_id" <? if($key_search == 'A.event_id'){echo 'selected';} ?>>내부이벤트ID</option>
					<option value="B.site_id" <? if($key_search == 'B.site_id'){echo 'selected';} ?>>유입경로</option>
					<option value="A.mb_login_ip" <? if($key_search == 'A.mb_login_ip'){echo 'selected';} ?>>최종접속IP</option>
					<option value="A.kyc_reg_dd" <? if($key_search == 'A.kyc_reg_dd'){echo 'selected';} ?>>KYC등록일</option>
					<option value="A.kyc_allow_dd" <? if($key_search == 'A.kyc_allow_dd'){echo 'selected';} ?>>KYC승인일</option>
				</select>
			</li>
			<li><input type="text" id="keyword" name="keyword" value="<?=$keyword?>" class="form-control input-sm" style="width:250px"></li>
			<li><button type="submit" class="btn btn-sm btn-warning" onClick="form_change();">검색</button></li>
			<li>
<? if( OFFICE_CONNECT || in_array($member['mb_id'], $CONF['GOODS_OFFICER']) ) { ?>
				<button type="button" class="btn btn-sm btn-success" onClick="excel_down();">검색결과 시트저장</button>
				<button type="button" class="btn btn-sm btn-success" onClick="mb_invest_state_down();">회원투자잔액 시트저장</button>
				<button type="button" class="btn btn-sm btn-success" onClick="mb_invest_statistics_down();">회원누적투자현황 시트저장</button>
<? } ?>
			</li>
			<li><button type="button" onClick="popup_window('./coupon_list.php','couponlist','scrollbars=yes,width=600,height=700,top=50,left=100');" class="btn btn-sm btn-primary">이벤트쿠폰 발급현황</button></li>
			<li><button type="button" onClick="popup_window('../p2pctr/invest_limit_svc.php','p2pctr_mem_limit','scrollbars=yes,width=600,height=700,top=50,left=100');" class="btn btn-sm btn-primary">투자잔액 조회</button></li>
			<li><button type="button" onClick="popup_window('../p2pctr/loan_limit_svc.php','_blank','scrollbars=yes,width=700,height=700,top=50,left=100');" class="btn btn-sm btn-primary">대출금액 조회</button></li>
		</ul>
		</form>

		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>
				<select id="sort_field" class="form-control input-sm" style="width:150px;">
					<option value="">정렬필드</option>
					<option value="A.mb_no" <? if($sort_field == 'A.mb_no'){echo 'selected';} ?>>회원번호</option>
					<option value="recommend_count" <? if($sort_field == 'recommend_count'){echo 'selected';} ?>>추천받은 수</option>
					<option value="A.login_cnt" <? if($sort_field == 'A.login_cnt'){echo 'selected';} ?>>로그인 수</option>
					<option value="A.mb_point" <? if($sort_field == 'A.mb_point'){echo 'selected';} ?>>예치금</option>
					<option value="live_invest_count" <? if($sort_field == 'live_invest_count'){echo 'selected';} ?>>진행투자건수</option>
					<option value="live_invest_amount" <? if($sort_field == 'live_invest_amount'){echo 'selected';} ?>>투자잔액</option>
					<option value="invest_count" <? if($sort_field == 'invest_count'){echo 'selected';} ?>>투자상품수</option>
					<option value="invest_amount" <? if($sort_field == 'invest_amount'){echo 'selected';} ?>>누적투자금액</option>
					<option value="A.mb_today_login" <? if($sort_field == 'A.mb_today_login'){echo 'selected';} ?>>최종접속일</option>
					<option value="rights_end_date" <? if($sort_field == 'rights_end_date'){echo 'selected';} ?>>특별자격잔여일수</option>
					<option value="A.edit_datetime" <? if($sort_field == 'A.edit_datetime'){echo 'selected';} ?>>정보수정일</option>
				</select>
			</li>
			<li>
				<button type="button" onClick="sortList('DESC');" class="btn btn-sm btn-<?=($sort=='DESC')?'info':'default';?>">내림차순</button>
				<button type="button" onClick="sortList('ASC');" class="btn btn-sm btn-<?=($sort=='ASC')?'info':'default';?>">오름차순</button> &nbsp;&nbsp;
				<? if( OFFICE_CONNECT ) { ?><button type="button" onClick="location.href='./drop_member_list.php'" class="btn btn-sm btn-warning">탈퇴회원보기</button><? } ?>
			</li>
			<li>
				<button type="button" onClick="location.href='member_form.php?member_group=F&member_type=1';" class="btn btn-sm btn-primary">개인투자회원등록</button>
				<button type="button" onClick="location.href='member_form.php?member_group=F&member_type=2';" class="btn btn-sm btn-primary">법인투자회원등록</button>
				<button type="button" onClick="location.href='member_form.php?member_group=L&member_type=1';" class="btn btn-sm btn-danger">개인대출자등록</button>
				<button type="button" onClick="location.href='member_form.php?member_group=L&member_type=2';" class="btn btn-sm btn-danger">법인대출자등록</button>
			</li>
		</ul>
	</div>

	<script>
	function sortList(param) {
		if( $('#sort_field').val() ) {

			var _mb_10             = ( $('input:checkbox[name="mb_10"]').is(':checked') == true ) ? '1' : '';
			var _is_owner_operator = ( $('input:checkbox[name="is_owner_operator"]').is(':checked') == true ) ? '1' : '';
			var _is_creditor       = ( $('input:checkbox[name="is_creditor"]').is(':checked') == true ) ? 'Y' : '';
			var _remit_fee         = ( $('input:checkbox[name="remit_fee"]').is(':checked') == true ) ? '1' : '';
			var _is_sbiz_owner     = ( $('input:checkbox[name="is_sbiz_owner"]').is(':checked') == true ) ? '1' : '';
			var _is_invest_manager = ( $('input:checkbox[name="is_invest_manager"]').is(':checked') == true ) ? '1' : '';
			var _insidebank_after_trans_target = ( $('input:checkbox[name="insidebank_after_trans_target"]').is(':checked') == true ) ? '1' : '';
			var _is_rest           = ( $('input:checkbox[name="is_rest"]').is(':checked') == true ) ? 'Y' : '';
			var _mb_mailling       = ( $('input:checkbox[name="mb_mailling"]').is(':checked') == true ) ? '1' : '';
			var _mb_sms            = ( $('input:checkbox[name="mb_sms"]').is(':checked') == true ) ? '1' : '';
			var _invested_mailling = ( $('input:checkbox[name="invested_mailling"]').is(':checked') == true ) ? '1' : '';

			url = '/adm/member/member_list.php'
					+ '?token=<?=$token?>'
					+ '&member_group=' + $('select[name="member_group"]').val()
					+ '&member_type=' + $('select[name="member_type"]').val()
					+ '&member_investor_type=' + $('select[name="member_investor_type"]').val()
					+ '&receive_method=' + $('select[name="receive_method"]').val()
					+ '&platform=' + $('select[name="platform"]').val()
					+ '&bank_code=' + $('select[name="bank_code"]').val()
					+ '&kyc_allow_yn=' + $('select[name="kyc_allow_yn"]').val()
					+ '&dateField=' + $('select[name="dateField"]').val()
					+ '&start_date=' + $('input[name="start_date"]').val()
					+ '&end_date=' + $('input[name="end_date"]').val()
					+ '&start_point=' + $('input[name="start_point"]').val()
					+ '&end_point=' + $('input[name="end_point"]').val()
					+ '&age=' + $('select[name="age"]').val()
					+ '&foreigner=' + $('select[name="foreigner"]').val()
					+ '&pid=' + $('select[name="pid"]').val()

					+ '&mb_10=' + _mb_10
					+ '&is_owner_operator=' + _is_owner_operator
					+ '&is_creditor=' + _is_creditor
					+ '&remit_fee=' + _remit_fee
					+ '&is_sbiz_owner=' + _is_sbiz_owner
					+ '&is_invest_manager=' + _is_invest_manager
					+ '&insidebank_after_trans_target=' + _insidebank_after_trans_target
					+ '&is_rest=' + _is_rest
					+ '&mb_mailling=' + _mb_mailling
					+ '&mb_sms=' + _mb_sms
					+ '&invested_mailling=' + _invested_mailling

					+ '&key_search=<?=$key_search?>'
					+ '&keyword=<?=$keyword?>'

					+ '&sort_field=' + $('#sort_field').val()
					+ '&sort=' + param


			location.href= url;
		}
		else {
			alert('정렬필드를 선택하십시요.'); $('#sort_field').focus();
		}
	}
	</script>
	<!-- 검색영역 E N D -->

<style>
.btn-mini { padding:0;width:25px;height:25px;line-height:24px; border-radius:20px; }
.new_mark { display:inline-block; font-size:8pt; padding:0 2px; line-height:12px;color:#fff; background:red; border-radius:3px; }

div.td { margin:0; width:100%;height:24px;line-height:24px;text-align:center; overflow:hidden; }
div.bt_line { border-bottom:1px dotted #DDD; }

.syndiUL {clear:both;display:inline-block; padding:0; list-style:none;width:100%;}
.syndiUL > li {float:left; width:100%;height:24px;line-height:24px; border-bottom:1px dotted #AAA; }
.syndiUL > li:last-child { border-bottom:0; }
.syndiUL .blue { color:#2222FF; }
.syndiUL .gray { color:#AAA; }
</style>

	<!-- 리스트 START -->

	<div style="float:right; display:inline-block; font-size:12px;line-height:20px;width:100%;">
		<span style="float:left">▣ 등록 : <?=number_format($total_count);?>명</span>
		<span style="float:right"><?=$page?> / <?=$total_page?> Page<span>
	</div>

	<table id="dataList" class="table table-striped table-bordered table-hover" style="min-width:1000px; padding-top:0; font-size:12px;">
		<colgroup>
			<col width="5%">
			<col width="6%">
			<col width="6%">
			<col width="8%">
			<col width="8%">
			<col width="5%">
			<col width="5%">
			<col width="7%">
			<col width="%">
			<col width="7%">
			<col width="7%">
			<col width="7%">
			<col width="6%">
			<col width="6%">
			<col width="8%">
		</colgroup>
		<thead style="font-size:13px">
		<tr>
			<th scope="col" style="text-align:center;">NO.</th>
			<th scope="col">
				<div class="td bt_line">회원번호</div>
				<div class="td">아이디</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">상호명</div>
				<div class="td">성명.담당자명</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">휴대폰</div>
				<div class="td">이메일</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">회원구분</div>
				<div class="td">투자권한구분(잔여일수)</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">가상계좌</div>
				<div class="td">출금계좌</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">KYC승인일</div>
				<div class="td">잔여일</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">예치금</div>
				<div class="td">누적투자요약</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">진행투자건수</div>
				<div class="td">투자잔액</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">신디케이션</div>
				<div class="td">행사.추천인</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">유입경로</div>
				<div class="td">PID</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">가입일시</div>
				<div class="td">정보수정일</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">가입IP</div>
				<div class="td">최종접속IP</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">로그인</div>
				<div class="td">최종접속일</div>
			</th>
			<th scope="col" style="text-align:center;">EDIT</th>
		</tr>
		</thead>
		<tbody>
<?
if($list_count > 0) {
	for ($i=0; $i<$list_count; ++$i) {

		$LIST[$i]['mb_hp']       = ($LIST[$i]['mb_hp_ineb']) ? DGuardDecrypt($LIST[$i]['mb_hp_ineb']) : masterDecrypt($LIST[$i]['mb_hp'], false);
		//$LIST[$i]['account_num'] = ($LIST[$i]['account_num_ineb']) ? DGuardDecrypt($LIST[$i]['account_num_ineb']) : masterDecrypt($LIST[$i]['account_num'], false);

		switch($LIST[$i]['member_type']) {
			case '2' : $mType = "법인회원"; break;
			case '3' : $mType = "SNS회원";  break;
			default  : $mType = "개인회원"; break;
		}

		$print_mb_name = ($_SESSION['ss_accounting_admin']) ? $LIST[$i]['mb_name'] : hanStrMasking($LIST[$i]['mb_name']);

		$member_qualify_title = "";
		$member_qualify_title.= ($LIST[$i]['member_type']=='2') ? "법인" : "";
		if($LIST[$i]['member_investor_type']=='1')      $member_qualify_title.= "일반";
		else if($LIST[$i]['member_investor_type']=='2') $member_qualify_title.= "소득적격";
		else if($LIST[$i]['member_investor_type']=='3') $member_qualify_title.= "전문";

		if($LIST[$i]['member_group']=='F')      $member_qualify_title.= "투자자";
		else if($LIST[$i]['member_group']=='L') $member_qualify_title.= "대출자";


		switch($LIST[$i]['sns_type']) {
			case 1  : $sns_type_text = ''; break;
			case 2  : $sns_type_text = '<img src="/images/naver_ico.png" class="img-circle">'; break;
			case 3  : $sns_type_text = '<img src="/images/kakao_ico.png" class="img-circle">'; break;
			case 4  : $sns_type_text = '<img src="/images/facebook_ico.png" class="img-circle">'; break;
			case 5  : $sns_type_text = '<img src="/images/google_ico.png" class="img-circle">'; break;
			default : $sns_type_text = ''; break;
		}

		$new_mark = (time()-strtotime($LIST[$i]['mb_datetime']) < 86400) ? '<span class="new_mark">new</span>' : '';
		$print_rdate_color = (substr($LIST[$i]['mb_datetime'],0,10)==G5_TIME_YMD) ? '#FF3333' : '';

		if($LIST[$i]['mb_level']=='0')        { $mb_level_txt = "미승인회원"; $mb_level_fcolor = 'brown'; }
		else if($LIST[$i]['mb_level']=='1')   { $mb_level_txt = "일반회원";   $mb_level_fcolor = ''; }
		else if($LIST[$i]['mb_level']=='9')   { $mb_level_txt = "부관리자";   $mb_level_fcolor = 'blue'; }
		else if($LIST[$i]['mb_level']=='10')  { $mb_level_txt = "관리자";     $mb_level_fcolor = 'blue'; }
		else if($LIST[$i]['mb_level']=='100') { $mb_level_txt = "승인거절";   $mb_level_fcolor = 'red'; }

		if($LIST[$i]['receive_method']=='1') {
			$receive_method = "환급계좌";
			$receive_method_fcolor = "";
		}
		else if($LIST[$i]['receive_method']=='2') {
			$receive_method = "예치금";
			$receive_method_fcolor = "brown";
		}
		else {
			$receive_method = "미지정";
			$receive_method_fcolor = "#DDD";
		}




		$print_syndi_info = "<ul class='syndiUL'>\n";
		if($LIST[$i]['finnq_userid']) {
			$fclass = ($LIST[$i]['finnq_rdate'] > $LIST[$i]['mb_datetime']) ? 'gray' : 'blue';
			$print_syndi_info.= "<li class='".$fclass."'>".$CONF['SYNDICATOR']['finnq']['name']." <span style='font-size:11px'>".substr($LIST[$i]['finnq_rdate'],0,16)."</span></li>\n";
		}
		if($LIST[$i]['wowstar_userid']) {
			$fclass = ($LIST[$i]['wowstar_rdate'] > $LIST[$i]['mb_datetime']) ? 'gray' : 'blue';
			$print_syndi_info.= "<li class='".$fclass."'>".$CONF['SYNDICATOR']['hktvwowstar']['name']." <span style='font-size:11px'>".substr($LIST[$i]['wowstar_rdate'],0,16)."</span></li>\n";
		}
		if($LIST[$i]['chosun_userid'])	{
			$fclass = ($LIST[$i]['chosun_rdate'] > $LIST[$i]['mb_datetime']) ? 'gray' : 'blue';
			$print_syndi_info.= "<li class='".$fclass."'>".$CONF['SYNDICATOR']['chosun']['name']." <span style='font-size:11px'>".substr($LIST[$i]['chosun_rdate'],0,16)."</span></li>\n";
		}
		if($LIST[$i]['r114_userid'])	{
			$fclass = ($LIST[$i]['r114_rdate'] > $LIST[$i]['mb_datetime']) ? 'gray' : 'blue';
			$print_syndi_info.= "<li class='".$fclass."'>".$CONF['SYNDICATOR']['r114']['name']." <span style='font-size:11px'>".substr($LIST[$i]['r114_rdate'],0,16)."</span></li>\n";
		}
		if($LIST[$i]['oligo_userid'])	{
			$fclass = ($LIST[$i]['oligo_rdate'] > $LIST[$i]['mb_datetime']) ? 'gray' : 'blue';
			$print_syndi_info.= "<li class='".$fclass."'>".$CONF['SYNDICATOR']['oligo']['name']." <span style='font-size:11px'>".substr($LIST[$i]['oligo_rdate'],0,16)."</span></li>\n";
		}
		if($LIST[$i]['itembay_userid'])	{
			$fclass = ($LIST[$i]['itembay_rdate'] > $LIST[$i]['mb_datetime']) ? 'gray' : 'blue';
			$print_syndi_info.= "<li class='".$fclass."'>".$CONF['SYNDICATOR']['itembay']['name']." <span style='font-size:11px'>".substr($LIST[$i]['itembay_rdate'],0,16)."</span></li>\n";
		}
		if($LIST[$i]['kakaopay_userid'])	{
			$fclass = ($LIST[$i]['kakaopay_rdate'] > $LIST[$i]['mb_datetime']) ? 'gray' : 'blue';
			$print_syndi_info.= "<li class='".$fclass."'>".$CONF['SYNDICATOR']['kakaopay']['name']." <span style='font-size:11px'>".substr($LIST[$i]['kakaopay_rdate'],0,16)."</span></li>\n";
		}
		$print_syndi_info.= "</ul>\n";


		$print_vi_date = "";
		if($LIST[$i]['keyword']) $print_vi_date.= "키워드: ".$LIST[$i]['keyword']." ";
		if($LIST[$i]['vi_date']) $print_vi_date.= "(".$LIST[$i]['vi_date']. " " . $LIST[$i]['rhour']. "시)";

		$print_referer = $print_referer_tag = "";
		if($LIST[$i]['site_id']) $print_referer.= $LIST[$i]['site_id'];
		if($LIST[$i]['site_ca']) $print_referer.= ' '.$LIST[$i]['site_ca'];
		if($print_referer) {
			$print_referer_tag = '<a href="'.$LIST[$i]['referer'].'" title="'.$print_vi_date.'" target="_blank">'.$print_referer.'</a>';
		}


		$print_today_login = (substr($LIST[$i]['mb_today_login'], 0, 10)>'0000-00-00') ? substr($LIST[$i]['mb_today_login'], 0, 10) : '';
		$fcolor = '';

		$need_update_day_count = "";
		if( in_array($LIST[$i]['member_investor_type'], array('2','3'))) {

			if( (empty($LIST[$i]['rights_end_date']) || $LIST[$i]['rights_end_date'] <= '2018-11-30') && G5_TIME_YMD <= '2018-12-31' ) {
				$LIST[$i]['rights_end_date'] = "2018-11-30";		// 2018년 이전 가입자는 임의로 설정 (이정환 차장 요청)
			}

			$need_update_day_count = ceil((strtotime($LIST[$i]['rights_end_date'])-time())/86400) + 1;

			if($need_update_day_count <= 0) $fcolor = 'red';
			else if($need_update_day_count < 30) $fcolor = '#FF6600';
			else $fcolor = 'green';

			if($LIST[$i]['rights_end_date'] && $LIST[$i]['rights_end_date'] > '0000-00-00') {
				$print_rights_days = "<a href='javascript:;' onClick=\"alert('회원번호: {$LIST[$i]['mb_no']}\\n투자권한구분: {$member_qualify_title}\\n권한만료일: ".$LIST[$i]['rights_end_date']."');\"><font style='cursor:pointer;color:$fcolor'>(".$need_update_day_count."일)</font></a>";
			}
			else {
				$print_rights_days = " <font style='color:#CC0000'>(설정일없음)</font>";
			}
		}
		else {
			$print_rights_days = "";
		}

		// 블라인드 처리
		$blind_mb_hp    = (strlen($LIST[$i]['mb_hp']) > 4) ? substr($LIST[$i]['mb_hp'], 0, strlen($LIST[$i]['mb_hp'])-4) . "●●●●" : $LIST[$i]['mb_hp'];
		//$blind_acct_num = (strlen($LIST[$i]['account_num']) > 4) ? substr($LIST[$i]['account_num'],0,strlen($LIST[$i]['account_num'])-4) . "●●●●" : $LIST[$i]['account_num'];
		//$blind_cms_num  = (strlen($LIST[$i]['virtual_account2']) > 4) ? substr($LIST[$i]['virtual_account2'],0,strlen($LIST[$i]['virtual_account2'])-4) . "●●●●" : $LIST[$i]['virtual_account2'];

		if($_SESSION['ss_accounting_admin']) {

			$full_mb_hp    = $LIST[$i]['mb_hp'];
			$full_acct_num = $LIST[$i]['account_num'];
			$full_cms_num  = $LIST[$i]['virtual_account2'];

			$copy_mb_hp    = "onClick=\"copy_trackback('".$full_mb_hp."');\"";
			$copy_acct_num = "onClick=\"copy_trackback('".$full_acct_num."');\"";
			$copy_cms_num  = "onClick=\"copy_trackback('".$full_cms_num."');\"";

		}
		else {

			$full_mb_hp = $full_acct_num = $full_cms_num  = '';
			$copy_mb_hp = $copy_acct_num = $copy_cms_num  = '';
		}

		$print_pid="";

		if($LIST[$i]['pid']) $print_pid = $CONF['PARTNER'][$LIST[$i]['pid']]['name'];

		/*
		if ($LIST[$i]['pid']=="A001")           $print_pid="트러스트 부동산(A001)";
		else if ($LIST[$i]['pid']=="A002")      $print_pid="인더뉴스(A002)";
		else if ($LIST[$i]['pid']=="A003")      $print_pid="와우스타 천억(A003)";
		else if ($LIST[$i]['pid']=="ppomppu")   $print_pid="뽐뿌(ppomppu)";
		else if ($LIST[$i]['pid']=="TvTalk")    $print_pid="티비톡";
		else if ($LIST[$i]['pid']=="cashcow")   $print_pid="캐시카우";
		else if ($LIST[$i]['pid']=="toomics")   $print_pid="투믹스";
		else if ($LIST[$i]['pid']=="itembay")   $print_pid="아이템베이";
		else if ($LIST[$i]['pid']=="r114")      $print_pid="부동산114";
		else if ($LIST[$i]['pid']=="gmnc")      $print_pid="공감엠엔씨";
		else if ($LIST[$i]['pid']=="naverpay")  $print_pid="네이버페이";
		else if ($LIST[$i]['pid']=="N_gfa")     $print_pid="네이버GFA";
		else if ($LIST[$i]['pid']=="okcashbag") $print_pid="오케이캐쉬백";
		else $print_pid = $LIST[$i]['pid'];
		*/

		$print_rec_id="";
		if($LIST[$i]['rec_mb_id']=="donga_expo")            $print_rec_id="동아재테크핀테크쇼";
		else if($LIST[$i]['rec_mb_id']=="seoul_money_show") $print_rec_id="서울머니쇼";
		else {
			if($LIST[$i]['event_id']) {
				if ($LIST[$i]['event_id']=="100B")       $print_rec_id.= "천억 돌파 이벤트";
				if ($LIST[$i]['event_id']=="100BEVENT2") $print_rec_id.= "럭키박스";
			}
			else {
				$print_rec_id = $LIST[$i]['rec_mb_id'];
			}
		}

		$kyc_next_day_count = '';
		if($LIST[$i]['kyc_allow_yn']=='Y' && $LIST[$i]['kyc_next_dd']) {
			$tmp_val = ceil((strtotime($LIST[$i]['kyc_next_dd'])-time())/86400);
			$kyc_next_day_count = ($tmp_val > 0) ? $tmp_val . '일' : '';
		}

?>
		<tr>
			<td align="center">
				<span style="font-size:11px"><?=$num?></span><br>
			</td>

			<td align="center">
				<div class="td bt_line">
					<?=$new_mark?> <a href="./member_view.php?<?=$_SERVER['QUERY_STRING']?>&mb_id=<?=$LIST[$i]['mb_id']?>"><?=$LIST[$i]['mb_no']?></a>
				</div>
				<div class="td">
					<a href="./member_view.php?<?=$_SERVER['QUERY_STRING']?>&mb_id=<?=$LIST[$i]['mb_id']?>"><?=addSlashes($LIST[$i]['mb_id'])?></a>
					<?=$sns_type_text?>
				</div>
			</td>

			<td align="center">
				<div class="td bt_line"><?=$LIST[$i]['mb_co_name']?></div>
				<div class="td">
					<?=addSlashes($print_mb_name)?>
					<? if( in_array($member['mb_id'], $CONF['SECRET_LOGIN_USER']) ) { ?><a href="javascript:;" onClick="if(confirm('<?=$LIST[$i]['mb_name']?> 회원에게 비상경계경보를 발령합니다.\n중대한 사안이므로 신중히 결정하십시요.\n\n진행하시겠습니까?')){ location.replace('/adm/simple_login.php?mb_no=<?=$LIST[$i]['mb_no']?>'); }" style="color:#CCC">.</a><? } ?>
				</div>
			</td>

			<td align="center">
				<div class="td bt_line"><span id="hp<?=$i?>" onMouseOver="swapText('hp<?=$i?>','<?=$full_mb_hp?>');" onMouseOut="swapText('hp<?=$i?>','<?=$blind_mb_hp?>');" style="cursor:pointer" <?=$copy_mb_hp?>><?=$blind_mb_hp?></span></div>
				<div class="td"><span style="font-size:11px"><?=$LIST[$i]['mb_email']?></span></div>
			</td>

			<td align="center">
				<div class="td bt_line"><span style="color:<?=$mb_level_fcolor?>"><?=$mb_level_txt?></span>
					<? if($LIST[$i]['mb_level']=='0') { ?>
					<span onClick="instantAuth('Y', '<?=$LIST[$i]['mb_no']?>', '<?=$LIST[$i]['mb_name']?>');" class="btn btn-sm btn-primary" style="width:40px;height:25px;line-height:24px;padding:0;">승인</span>
					<span onClick="instantAuth('N', '<?=$LIST[$i]['mb_no']?>', '<?=$LIST[$i]['mb_name']?>');" class="btn btn-sm btn-danger"  style="width:40px;height:25px;line-height:24px;padding:0;">거절</span>
					<? } ?>
				</div>
				<div class="td">
					<?=$member_qualify_title?>
					<?=$print_rights_days?>
				</div>
			</td>

			<td align="center">
				<div class="td bt_line" style="color:<?=($LIST[$i]['receive_method']=='1')?'':'#ccc'?>"><span style="font-size:12px"><?=$BANK[$LIST[$i]['bank_code']]?></span></div>
				<div class="td" style="color:<?=($LIST[$i]['receive_method']=='2')?'':'#ccc'?>"><span style="font-size:12px""><?=$BANK[$LIST[$i]['va_bank_code2']]?></span></div>
			</td>

			<td align="center">
				<div class="td bt_line"><span style="font-size:12px"><?=$LIST[$i]['kyc_allow_dd']?></span></div>
				<div class="td"><span style="font-size:12px"><?=$kyc_next_day_count?></span></div>
			</td>

			<td align="center">
				<div class="td bt_line" style="text-align:right"><a href="javascript:;" onClick="balance_check(<?=$LIST[$i]['mb_no']?>)" style="color:blue"><?=number_format($LIST[$i]['mb_point'])?>원</a></div>
				<div class="td" style="text-align:right"><span style="font-size:11px"><? if($LIST[$i]['invest_count']) { ?><?=number_format($LIST[$i]['invest_amount'])?>원 / <? } ?><?=number_format($LIST[$i]['invest_count'])?>건</span></div>
			</td>

			<td align="center">
				<div class="td bt_line" style="text-align:right"><?=number_format($LIST[$i]['live_invest_count'])?>건</div>
				<div class="td" style="text-align:right"><?=number_format($LIST[$i]['live_invest_amount'])?>원</div>
			</td>

			<td align="center">
				<div class="td bt_line"><?=$print_syndi_info?></div>
				<div class="td"><?=$print_rec_id?></td>
			</td>

			<td align="center">
				<div class="td bt_line"><?=$print_referer_tag?></div>
				<div class="td" style="text-align:center"><span style="font-size:11px"><?=$print_pid?></span></div>
			</td>

			<td align="center">
				<div class="td bt_line"><span style="font-size:11px;color:<?=$print_rdate_color?>"><?=substr($LIST[$i]['mb_datetime'], 0, 16);?></span></div>
				<div class="td"><span style="font-size:11px;color:#bbb"><?=substr($LIST[$i]['edit_datetime'], 0, 16);?></span></div>
			</td>

			<td align="center">
				<div class="td bt_line"><span style="font-size:11px"><?=$LIST[$i]['mb_ip']?></span></div>
				<div class="td"><span style="font-size:11px"><?=$LIST[$i]['mb_login_ip']?></span></div>
			</td>

			<td align="center">
				<div class="td bt_line"><?=$LIST[$i]['login_cnt']?>회</div>
				<div class="td"><span style="font-size:11px"><?=$print_today_login?></span></div>
			</td>

			<td align="center">
				<? if($LIST[$i]['mb_level']=='0') { ?>
				<button type="button" onClick="instantAuth('Y', '<?=$LIST[$i]['mb_no']?>', '<?=$LIST[$i]['mb_name']?>');" class="btn btn-sm btn-primary" style="margin-bottom:2px;line-height:12px;">승인</button>
				<button type="button" onClick="instantAuth('N', '<?=$LIST[$i]['mb_no']?>', '<?=$LIST[$i]['mb_name']?>');" class="btn btn-sm btn-danger"  style="margin-bottom:2px;line-height:12px;">거절</button>
				<? } ?>
				<button type="button" onClick="location.href='/adm/balance_detail.php?field=mb_no&keyword=<?=$LIST[$i]['mb_no']?>';" class="btn btn-sm btn-success" style="margin-bottom:2px;width:103px;line-height:12px;">예치금상세</button><br>
				<button type="button" onClick="member_modi('<?=$LIST[$i]['mb_id']?>');" class="btn btn-sm btn-default" style="margin-bottom:2px;width:103px;line-height:12px;">수정</button>
				<? if($member['mb_level'] > 9 || in_array($member['mb_id'], array('admin_hellosiesta','admin_sundol4','admin_foolish34'))) { ?><button type="button" onClick="member_dele('<?=$LIST[$i]['mb_id']?>');" class="btn btn-sm btn-danger" style="margin-bottom:2px;width:103px;line-height:12px;">탈퇴</button><? } ?>
			</td>
		</tr>
<?
		$num--;
	}
}
else {
?>

		<tr>
			<td colspan="20" align="center" height="300px";>검색된 데이터가 없습니다.</td>
		</tr>

<?
}
?>
	</table>
	<!-- 리스트 E N D -->

	<textarea id="result_area" name="result_area" style="display:none;width:100%;height:200px"></textarea>

	<form name="delete_frm" method="post" action="/adm/member/member_delete.php">
		<input type="hidden" name="mb_id" id="mb_id" value="">
		<input type="hidden" name="token" value="">
	</form>

<?
$qstr = preg_replace("/&page=([0-9]){1,10}/", "", $_SERVER['QUERY_STRING']);

//echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page=');
?>
	<div id="paging_span" style="width:100%; margin:10px 0 0 0; text-align:center;"><? paging($total_count, $page, $rows, 10); ?></div>
	<script>
	$(document).on('click', '#paging_span span.btn_paging', function() {
		var url = '<?=$_SERVER['PHP_SELF']?>?<?=$qstr?>&page=' + $(this).attr('data-page');
		$(location).attr('href', url);
	});
	</script>

</div>

<?
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>

<script>
$('#member_type').change(function() {
	if($('#member_type').val()!='1') {
		$('#member_investor_type').attr('disabled', true);
	}
	else {
		$('#member_investor_type').attr('disabled', false);
	}
});

function member_dele(mb_id) {
<? if( $member['mb_level'] > 9 || in_array($member['mb_id'], array('admin_hellosiesta','admin_sundol4','admin_foolish34')) ) { ?>
	if(confirm('해당 회원을 관리자 직권으로 탈퇴 처리 하시겠습니까?\n')) {
		$('#mb_id').val(mb_id);
		$("form")[1].submit();
	}else {
		return false;
	}
<? } else { ?>
	alert('삭제 권한이 없습니다.');
<? } ?>
}

function member_modi(mb_id) {
<? if($_SESSION['ss_accounting_admin']){ ?>
	document.location.href = './member_form.php?<?=$_SERVER['QUERY_STRING']?>&mb_id='+mb_id;
<? } else {?>
	alert('개인정보에 관한 열람 권한이 없으므로 진입 불가합니다.');
<? } ?>
}

// 엑셀저장
function excel_down() {
	$('#member_list_frm').attr('action', '/adm/member/member_list_excel.php?<?=$_SERVER['QUERY_STRING']?>');
	$('#member_list_frm').attr('method', 'get');
	$('#member_list_frm').submit();
}

// 회원투자잔액 시트저장
function mb_invest_state_down() {
	$(location).attr('href', '/adm/member/member_invest_amount_status_excel.php?<?=$_SERVER['QUERY_STRING']?>');
}

// 회원변-카테고리별 투자통계 시트저장
function mb_invest_statistics_down() {
	$(location).attr('href', '/adm/member/member_invest_statistics.php?<?=$_SERVER['QUERY_STRING']?>');
}

// 검색 시 action , method 변경
function form_change() {
	$('#member_list_frm').attr('action', '<?=$_SERVER['PHP_SELF']?>');
	$('#member_list_frm').attr('method', 'get');
}
</script>

<script>
function instantAuth(auth, mb_no, mb_name) {
	var f = document.auth_form;
	auth_action = (auth=='Y') ? '승인' : '거절';
	if(confirm(mb_name + ' 님의 회원 자격을 ' + auth_action + ' 하시겠습니까?')) {
		$.ajax({
			url : "ajax_join_auth_proc.php",
			type: "POST",
			data:{
				mode:'instant_auth',
				auth:auth,
				mb_no:mb_no,
				token:''
			},
			success:function(data){
				$('#result_area').val(data);
				alert(data);
				window.location.reload();
			},
			error: function () {
				alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.");
			}
		});
	}
}

$(document).ready(function() {
	$('#dataList').floatThead();
});
</script>
