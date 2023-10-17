<?php
include_once('./_common.php');

auth_check($auth[$sub_menu], "w");

header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=헬로펀딩탈퇴회원".DATE("YmdHis").".xls");  //File name extension was wrong
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false);


while( list($k, $v) = each($_REQUEST) ) { if(!is_array($k)) ${$k} = trim($v); }


if($start_date) $datetime_s = $start_date . ' 00:00:00';
if($end_date)   $datetime_e = $end_date . ' 23:59:59';

/* 검색 필드 조합 START */
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
	$sql_search.= " AND A.mb_level BETWEEN 0 AND 8 ";
}

if($mb_sms=='1') $sql_search.= " AND A.mb_sms='1' ";
if( $date_field && ($start_date || $end_date) ) {
	if($date_field=='join_date') {
		if($start_date && $end_date) {
			$sql_search.= " AND A.mb_datetime BETWEEN '$datetime_s' AND '$datetime_e'";
		}
		else {
			if($start_date) $sql_search.= " AND A.mb_datetime>='$datetime_s' ";
			if($end_date)   $sql_search.= " AND A.mb_datetime<='$datetime_e' ";
		}
	}
	else if($date_field=='drop_date') {
		if($start_date && $end_date) {
			$sql_search.= " AND A.mb_leave_date BETWEEN '".$start_date."' AND '".$end_date."'";
		}
		else {
			if($start_date) $sql_search.= " AND A.mb_leave_date >= '".$start_date."'";
			if($end_date)   $sql_search.= " AND A.mb_leave_date <= '".$end_date."'";
		}
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

if($key_search && $keyword) {
	if( $key_search == 'A.mb_no' || $key_search == 'A.mb_hp_key') {
		if( $key_search == 'A.mb_no' && preg_match("/\,/", $keyword) ) {
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
		$sql_search.= " AND concat(site_id,' ',site_ca) LIKE '%$keyword%' ";
	}
	else {
		$sql_search.= " AND $key_search LIKE '%$keyword%' ";
	}
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

if($mb_10) {
	$sql_search.= " AND mb_10='$mb_10' ";
}
/* 검색 필드 조합 E N D */

$sql_order = "";
if($sort_field) {
	$sql_order.= $sort_field." ".$sort.", ";
}
$sql_order.= " A.mb_leave_date DESC, A.mb_no DESC";

$sql = "
	SELECT
		A.mb_no, A.mb_id, A.mb_level, A.member_group, A.member_type, A.member_investor_type, A.is_creditor, A.is_owner_operator, A.mb_point,
		A.mb_name, A.mb_co_name, A.mb_email, A.mb_hp, A.mb_hp_key, A.mb_datetime, A.edit_datetime, A.mb_today_login, A.mb_login_ip, A.login_cnt, A.mb_ip, A.is_rest, A.mb_mailling, A.mb_sms,
		A.receive_method, A.bank_code, A.account_num, A.account_num_key, A.bank_private_name, A.va_bank_code2, A.virtual_account2, A.va_private_name2,
		A.syndi_id, A.finnq_userid, A.finnq_rdate, A.wowstar_userid, A.wowstar_rdate, A.chosun_userid, A.chosun_rdate, A.tvtalk_userid, A.tvtalk_rdate, A.r114_userid, A.r114_rdate, A.oligo_userid, A.oligo_rdate,
		A.event_id, A.pid, A.rec_mb_id,
		A.id_card, A.business_license, A.bankbook, A.loan_co_license, A.mb_leave_date, A.mb_leave_reason,
		B.rdate AS vi_date, B.rhour, B.referer, B.site_id, B.site_ca, B.keyword, B.is_paid,
		(SELECT COUNT(mb_no) FROM g5_member_drop WHERE rec_mb_no=A.mb_no AND virtual_account!='' AND rec_mb_id!='') AS recommend_count,
		(SELECT COUNT(SA.idx) FROM cf_product_invest SA LEFT JOIN cf_product SB ON SA.product_idx=SB.idx WHERE SA.member_idx=A.mb_no AND SA.invest_state='Y' AND SB.state IN('1','2','5')) AS invest_count,
		(SELECT IFNULL(SUM(SA.amount),0) FROM cf_product_invest SA LEFT JOIN cf_product SB ON SA.product_idx=SB.idx  WHERE SA.member_idx=A.mb_no AND SA.invest_state='Y' AND SB.state IN('1','2','5')) AS invest_amount,
		(SELECT rights_end_date FROM investor_type_change_request WHERE mb_no=A.mb_no AND allow='Y' ORDER BY idx DESC LIMIT 1) AS rights_end_date,
		(SELECT COUNT(SA.idx) FROM cf_product_invest SA LEFT JOIN cf_product SB ON SA.product_idx=SB.idx WHERE SA.member_idx=A.mb_no AND SA.invest_state='Y' AND SB.state IN('','1')) AS live_invest_count,
		(SELECT IFNULL(SUM(SA.amount),0) FROM cf_product_invest SA LEFT JOIN cf_product SB ON SA.product_idx=SB.idx WHERE SA.member_idx=A.mb_no AND SA.invest_state='Y' AND SB.state IN('','1')) AS live_invest_amount
	FROM
		g5_member_drop A
	LEFT JOIN
		cf_visit_status B  ON A.vi_idx=B.idx
	WHERE 1=1
		$sql_search
	ORDER BY
		$sql_order
";
//echo "<pre style='font-size:12px'>"; echo $sql; echo "</pre>";
$result = sql_query($sql);
$rcount = sql_num_rows($result);
for($i=0; $i<$rcount; $i++) {
	$LIST[] = sql_fetch_array($result);
}
sql_free_result($result);

$list_count = count($LIST);

$num = $list_count;
?>

	<table border="1" style="font-size:9pt">
		<thead>
			<tr>
				<th rowspan="2" align="center" style="background:#EFEFEF;">NO.</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">회원번호</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">아이디</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">법인명</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">투자권한구분</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">성명.담당자명</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">휴대폰</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">이메일</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">가입일</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">가입지</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">로그인수</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">최종로그인</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">최종접속지</th>
				<th colspan="2" align="center" style="background:#EFEFEF;">누적투자요약</th>
				<th colspan="2" align="center" style="background:#EFEFEF;">신디케이션</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">행사.추천인</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">유입경로</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">PID</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">탈퇴일</th>
				<th rowspan="2" align="center" style="background:#EFEFEF;">탈퇴사유</th>
			</tr>
			<tr>
				<th align="center" style="background:#EFEFEF;">건수</th>
				<th align="center" style="background:#EFEFEF;">금액</th>
				<th align="center" style="background:#EFEFEF;">플랫폼</th>
				<th align="center" style="background:#EFEFEF;">등록일</th>
			</tr>
			</thead>
		<tbody>
<?
if($list_count > 0) {
	for ($i=0; $i<$list_count; $i++) {

		$LIST[$i]['mb_hp'] = masterDecrypt($LIST[$i]['mb_hp'], false);
		$LIST[$i]['account_num'] = masterDecrypt($LIST[$i]['account_num'], false);

		switch($LIST[$i]['member_type']) {
			case '2' : $mType = "법인회원"; break;
			case '3' : $mType = "SNS회원";  break;
			default  : $mType = "개인회원"; break;
		}


		$member_qualify_title = "";
		$member_qualify_title.= ($LIST[$i]['member_type']=='2') ? "법인" : "";
		if($LIST[$i]['member_investor_type']=='1')      $member_qualify_title.= "일반";
		else if($LIST[$i]['member_investor_type']=='2') $member_qualify_title.= "소득적격";
		else if($LIST[$i]['member_investor_type']=='3') $member_qualify_title.= "전문";

		if($LIST[$i]['member_group']=='F')      $member_qualify_title.= "투자자";
		else if($LIST[$i]['member_group']=='L') $member_qualify_title.= "대출자";


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



		$print_syndi = $print_syndi_date = "";
		if($LIST[$i]['event_id']) {
			if ($LIST[$i]['event_id']=="100B") $event_name = "천억 돌파 이벤트";
			$print_syndi = $event_name;
		}
		if($LIST[$i]['finnq_userid']) {
			$print_syndi = $CONF['SYNDICATOR']['finnq']['name'];
			$print_syndi_date = substr($LIST[$i]['finnq_rdate'],0,16);
		}
		if($LIST[$i]['wowstar_userid']) {
			$print_syndi = $CONF['SYNDICATOR']['hktvwowstar']['name'];
			$print_syndi_date = substr($LIST[$i]['wowstar_rdate'],0,16);
		}
		if($LIST[$i]['chosun_userid'])	{
			$print_syndi = $CONF['SYNDICATOR']['chosun']['name'];
			$print_syndi_date = substr($LIST[$i]['chosun_rdate'],0,16);
		}
		if($LIST[$i]['tvtalk_userid']) {
			$print_syndi = $CONF['SYNDICATOR']['TvTalk']['name'];
			$print_syndi_date = substr($LIST[$i]['tvtalk_rdate'],0,16);
		}


		$print_vi_date = "";
		if($LIST[$i]['keyword']) $print_vi_date.= "키워드: ".$LIST[$i]['keyword']." ";
		if($LIST[$i]['vi_date']) $print_vi_date.= "(".$LIST[$i]['vi_date']. " " . $LIST[$i]['rhour']. "시)";

		$print_referer = $print_referer_tag = "";
		if($LIST[$i]['site_id']) $print_referer.= $LIST[$i]['site_id'];
		if($LIST[$i]['site_ca']) $print_referer.= ' '.$LIST[$i]['site_ca'];
		if($print_referer) {
			$print_referer_tag = $print_referer.'';
		}


		$print_today_login = (substr($LIST[$i]['mb_today_login'], 0, 10)>'0000-00-00') ? substr($LIST[$i]['mb_today_login'], 0, 10) : '';
		$fcolor = '';


		// 블라인드 처리
		$blind_mb_hp    = (strlen($LIST[$i]['mb_hp']) > 4) ? substr($LIST[$i]['mb_hp'], 0, strlen($LIST[$i]['mb_hp'])-4) . "****" : $LIST[$i]['mb_hp'];
		$blind_acct_num = (strlen($LIST[$i]['account_num']) > 4) ? substr($LIST[$i]['account_num'],0,strlen($LIST[$i]['account_num'])-4) . "****" : $LIST[$i]['account_num'];
		$blind_cms_num  = (strlen($LIST[$i]['virtual_account2']) > 4) ? substr($LIST[$i]['virtual_account2'],0,strlen($LIST[$i]['virtual_account2'])-4) . "****" : $LIST[$i]['virtual_account2'];

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
		if ($LIST[$i]['pid']=="A001")         $print_pid="트러스트 부동산(A001)";
		else if ($LIST[$i]['pid']=="A002")    $print_pid="인더뉴스(A002)";
		else if ($LIST[$i]['pid']=="A003")    $print_pid="와우스타 천억(A003)";
		else if ($LIST[$i]['pid']=="ppomppu") $print_pid="뽐뿌(ppomppu)";
		else if ($LIST[$i]['pid']=="cashcow") $print_pid="캐시카우";
		else $print_pid = $LIST[$i]['pid'];

		$print_rec_id="";
		if ($LIST[$i]['rec_mb_id']=="donga_expo")            $print_rec_id="동아재테크핀테크쇼";
		else if ($LIST[$i]['rec_mb_id']=="seoul_money_show") $print_rec_id="서울머니쇼";
		else $print_rec_id = $LIST[$i]['rec_mb_id'];


?>
		<tr>
			<td align="center"><?=$num?></td>
			<td align="center"><?=$LIST[$i]['mb_no']?></td>
			<td align="center"><?=$LIST[$i]['mb_id']?></td>
			<td align="center"><?=$LIST[$i]['mb_co_name']?></td>
			<td align="center"><?=$member_qualify_title?></td>
			<td align="center"><?=$LIST[$i]['mb_name']?></td>
			<td align="center" style="mso-number-format:'\@'"><?=$blind_mb_hp?></td>
			<td align="center"><?=$LIST[$i]['mb_email']?></td>
			<td align="center" style="mso-number-format:'\@'"><?=substr($LIST[$i]['mb_datetime'], 0, 10);?></td>
			<td align="center"><?=$LIST[$i]['mb_ip']?></td>
			<td align="right"><?=$LIST[$i]['login_cnt']?>회</td>
			<td align="center" style="mso-number-format:'\@'"><?=$print_today_login?></td>
			<td align="center"><?=$LIST[$i]['mb_login_ip']?></td>
			<td align="right"><?=number_format($LIST[$i]['invest_count'])?>건</td>
			<td align="right"><?=number_format($LIST[$i]['invest_amount'])?>원</td>
			<td align="center"><?=$print_syndi?></td>
			<td align="center"><?=$print_syndi_date?></td>
			<td align="center"><?=$print_rec_id?></td>
			<td align="center"><?=$print_referer_tag?></td>
			<td align="center"><?=$print_pid?></td>
			<td align="center"><?=date('Y-m-d', strtotime($LIST[$i]['mb_leave_date']))?></td>
			<td align="center"><?=$LIST[$i]['mb_leave_reason']?><?=$LIST[$i]['mb_3']?" ".$LIST[$i]['mb_3']:""?></td>
		</tr>
<?
		$num--;
	}
}
else {
?>

		<tr>
			<td colspan="22" height="300px";>검색된 데이터가 없습니다.</td>
		</tr>

<?
}
?>
	</table>