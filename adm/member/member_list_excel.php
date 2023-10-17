<?
set_time_limit(0);

include_once('./_common.php');

auth_check($auth[$sub_menu], "w");

if( !OFFICE_CONNECT ) { header('HTTP/1.0 404 Not Found'); exit; }
if($_SESSION['ss_accounting_admin']) { include_once('../secure_data_connect_log.php'); }

$html_title = "전체 회원";
$g5['title'] = $html_title.' 정보';

while( list($k, $v) = each($_REQUEST) ) { if(!is_array($k)) ${$k} = trim($v); }

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
if($insidebank_after_trans_target) $sql_search.= " AND A.insidebank_after_trans_target='$insidebank_after_trans_target' ";
if($is_rest=='Y')           $sql_search.= " AND A.is_rest='Y' ";
if($mb_level) {
	$sql_search.= ($mb_level=='null') ? " AND A.mb_level='0' " : " AND A.mb_level='$mb_level' ";
}
else {
	$sql_search.= " AND A.mb_level BETWEEN 1 AND 8 ";
}

if($mb_sms=='1') $sql_search.= " AND A.mb_sms='1' ";
if($start_date && $end_date) {
	$sql_search.= " AND LEFT(A.mb_datetime,10) BETWEEN '$start_date' AND '$end_date'";
}
else {
	if($start_date) $sql_search.= " AND LEFT(A.mb_datetime,10)>='$start_date' ";
	if($end_date)   $sql_search.= " AND LEFT(A.mb_datetime,10)<='$end_date' ";
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
}

if($bank_code) {
	$sql_search.= " AND bank_code='".$bank_code."'";
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
$sql_order.= " A.mb_no DESC ";


$sql = "
	SELECT
		A.*,
		IFNULL(B.cnumber,'') as cnumber,
		IFNULL(B.use_date,'') as use_date,
		(SELECT COUNT(mb_no) FROM g5_member WHERE rec_mb_no=A.mb_no AND virtual_account!='' AND rec_mb_id!='') AS recommend_count,
		(SELECT COUNT(idx) FROM cf_product_invest WHERE member_idx=A.mb_no AND invest_state='Y') AS invest_count,
		(SELECT IFNULL(SUM(amount), 0) FROM cf_product_invest WHERE member_idx=A.mb_no AND invest_state='Y') AS invest_amount,
		(SELECT rights_end_date FROM investor_type_change_request WHERE mb_no=A.mb_no AND allow='Y' ORDER BY idx DESC LIMIT 1) AS rights_end_date
	FROM
		g5_member A
	LEFT JOIN
		(SELECT st1.member_idx,st1.cnumber,st2.use_date FROM recommend_reward_log st1 LEFT JOIN hloan_cupoint_reg st2 ON st1.rcidx=st2.rcidx where st1.position='recmder') B
	ON
		A.mb_no=B.member_idx
	LEFT JOIN
		cf_visit_status C  ON A.vi_idx=C.idx
	WHERE 1=1
		$sql_search
	ORDER BY
		$sql_order";
//echo "<pre>".$sql."</pre>"; exit;

$result = sql_query($sql);
$rcount = sql_num_rows($result);

$NUJUK_STAT = array('mb_point'=>0, 'invest_cnt'=>0, 'invest_amt'=>0);

for($i=0; $i<$rcount; $i++) {
	$LIST[$i] = sql_fetch_array($result);
	$LIST[$i]['mb_hp']       = ($LIST[$i]['mb_hp_ineb']) ? DGuardDecrypt($LIST[$i]['mb_hp_ineb']) : masterDecrypt($LIST[$i]['mb_hp'], false);
	$LIST[$i]['account_num'] = ($LIST[$i]['account_num_ineb']) ? DGuardDecrypt($LIST[$i]['account_num_ineb']) : masterDecrypt($LIST[$i]['account_num'], false);

	$NUJUK_STAT['mb_point']   += $LIST[$i]['mb_point'];
	$NUJUK_STAT['invest_cnt'] += $LIST[$i]['invest_count'];
	$NUJUK_STAT['invest_amt'] += $LIST[$i]['invest_amount'];
}

$num = $rcount;



$file_name = "헬로펀딩 회원목록 " . date('Ymd_Hi') . ".xls";
$file_name = iconv("utf-8", "euc-kr", $file_name);

header( "Content-type: application/vnd.ms-excel; charset=utf-8" );
header( "Content-Disposition: attachment; filename=$file_name" );
header( "Content-description: PHP5 Generated Data" );

debug_flush('<table border="1">
	<tr>
		<th align="center" bgcolor="#EEEEEE">NO.</th>
		<th align="center" bgcolor="#EEEEEE">회원구분</th>
		<th align="center" bgcolor="#EEEEEE">회원등급</th>
		<th align="center" bgcolor="#EEEEEE">법인명</th>
		<th align="center" bgcolor="#EEEEEE">법인등록번호</th>
		<th align="center" bgcolor="#EEEEEE">회원번호</th>
		<th align="center" bgcolor="#EEEEEE">아이디</th>
		<th align="center" bgcolor="#EEEEEE">성명/담당자명</th>
		<th align="center" bgcolor="#EEEEEE">휴대폰</th>
		<th align="center" bgcolor="#EEEEEE">이메일</th>
		<th align="center" bgcolor="#EEEEEE">대부업</th>
		<th align="center" bgcolor="#EEEEEE">가입일</th>
		<th align="center" bgcolor="#EEEEEE">잔여일</th>
		<th align="center" bgcolor="#EEEEEE">추천인</th>
		<th align="center" bgcolor="#EEEEEE">추천받은수</th>
		<th align="center" bgcolor="#EEEEEE">최종로그인</th>
		<th align="center" bgcolor="#EEEEEE">로그인수</th>
		<th align="center" bgcolor="#EEEEEE">예치금</th>
		<th align="center" bgcolor="#EEEEEE">누적투자상품수</th>
		<th align="center" bgcolor="#EEEEEE">누적투자금액</th>
		<th align="center" bgcolor="#EEEEEE">가상계좌</th>
		<th align="center" bgcolor="#EEEEEE">쿠폰번호</th>
		<th align="center" bgcolor="#EEEEEE">발급일</th>
	</tr>'.PHP_EOL);

	if($rcount > 0) {
		debug_flush('<tr>
		<td bgcolor="#DDDDFF" style="color:brown" colspan="2" align="center">합계 ' . number_format($rcount) . '</td>
		<td bgcolor="#DDDDFF" style="color:brown" colspan="15"></td>
		<td bgcolor="#DDDDFF" style="color:brown" align="right">' . number_format($NUJUK_STAT['mb_point']) . '</td>
		<td bgcolor="#DDDDFF" style="color:brown" align="right">' . number_format($NUJUK_STAT['invest_cnt']) . '</td>
		<td bgcolor="#DDDDFF" style="color:brown" align="right">' . number_format($NUJUK_STAT['invest_amt']) . '</td>
		<td bgcolor="#DDDDFF" style="color:brown" colspan="3"></td>
	</tr>'.PHP_EOL);
	}

	for($i=0,$num; $i<$rcount; $i++,$num--) {

		$print_mb_name = ($member['SUB_ADMIN']['privacy_auth']) ? $LIST[$i]['mb_name'] : hanStrMasking($LIST[$i]['mb_name']);

		$print_mb_hp = "";
		if(strlen($LIST[$i]['mb_hp']) > 4) {
			$print_mb_hp   = ($member['SUB_ADMIN']['hp_auth']=='Y') ? $LIST[$i]['mb_hp'] : substr($LIST[$i]['mb_hp'], 0, strlen($LIST[$i]['mb_hp'])-4) . "****";
		}

		$print_vacct = "";
		if(strlen($LIST[$i]['virtual_account2']) > 5) {
			$print_vacct = ($member['SUB_ADMIN']['privacy_auth']=='Y') ? $LIST[$i]['virtual_account2'].' '.$LIST[$i]['va_private_name2'] : substr($LIST[$i]['virtual_account2'], 0, strlen($LIST[$i]['virtual_account2'])-2) . "**" ;
		}

		if($LIST[$i]['member_type']=='2') {
			$print_mb_co_name = $LIST[$i]['mb_co_name'];
			//$print_mb_co_name.= '<br>('.$LIST[$i]['mb_co_reg_num'].')';
		}

		switch($LIST[$i]['member_type']) {
			case '3' : $member_type = "SNS";  break;
			case '2' : $member_type = "법인"; break;
			default  : $member_type = "개인"; break;
		}

		if($LIST[$i]['mb_level']=='0')        $mb_level_txt = "미승인회원";
		else if($LIST[$i]['mb_level']=='1')   $mb_level_txt = "일반회원";
		else if($LIST[$i]['mb_level']=='9')   $mb_level_txt = "부관리자";
		else if($LIST[$i]['mb_level']=='10')  $mb_level_txt = "관리자";
		else if($LIST[$i]['mb_level']=='100') $mb_level_txt = "승인거절";

		$need_update_day_count = "";
		$print_rights_days = "";
		if($LIST[$i]['member_investor_type']=='2') {
			$need_update_day_count = ceil((strtotime($LIST[$i]['rights_end_date'])-time())/86400) + 1;

			if($LIST[$i]['rights_end_date'] && $LIST[$i]['rights_end_date'] > '0000-00-00') {
				$print_rights_days = $need_update_day_count."일";
			}
			else {
				$print_rights_days = "설정일없음";
			}
		}

		$mb_today_login = ($LIST[$i]['mb_today_login']=='0000-00-00 00:00:00') ? '' : substr($LIST[$i]['mb_today_login'], 0, 16);

		$is_creditor = ($LIST[$i]['is_creditor']=='Y') ? '대부업' : '';

		if ($LIST[$i]['mb_sms']=="1") $mb_sms="Y";
		else $mb_sms="N";

		$mb_auto_inv_conf = array();
		$mb_auto_inv_conf = get_auto_inv_conf($LIST[$i]['mb_no']);
		if (count($mb_auto_inv_conf)) $setup_amount=$mb_auto_inv_conf[0]['setup_amount'];
		else $setup_amount=0;
		debug_flush('	<tr>
		<td align="center">'.$num.'</td>
		<td align="center">'.$member_type.'</td>
		<td align="center">'.$mb_level_txt.'</td>
		<td align="center">'.$print_mb_co_name.'</td>
		<td align="center">'.$LIST[$i]['mb_co_reg_num'].'</td>
		<td align="center">'.$LIST[$i]['mb_no'].'</td>
		<td align="center" style="mso-number-format:\'@\';">'.$LIST[$i]['mb_id'].'</td>
		<td align="center">'.$print_mb_name.'</td>
		<td align="center" style="mso-number-format:\'@\';">'.$print_mb_hp.'</td>
		<td align="center">'.$LIST[$i]['mb_email'].'</td>
		<td align="center">'.$is_creditor.'</td>
		<td align="center" style="mso-number-format:\'@\';">'.substr($LIST[$i]['mb_datetime'], 0, 16).'</td>
		<td align="center">'.$print_rights_days.'</td>
		<td align="center">'.$LIST[$i]['rec_mb_id'].'</td>
		<td align="right">'.number_format($LIST[$i]['recommend_count']).'</td>
		<td align="center" style="mso-number-format:\'@\';">'.$mb_today_login.'</td>
		<td align="right">'.number_format($LIST[$i]['login_cnt']).'</td>
		<td align="right">'.number_format($LIST[$i]['mb_point']).'</td>
		<td align="right">'.number_format($LIST[$i]['invest_count']).'</td>
		<td align="right">'.number_format($LIST[$i]['invest_amount']).'</td>
		<td align="center">'.$print_vacct.'</td>
		<td align="center">'.$LIST[$i]['cnumber'].'</td>
		<td align="center">'.$LIST[$i]['use_date'].'</td>
	</tr>'.PHP_EOL);

	$print_mb_co_name = $member_type = $mb_level_txt = $mb_today_login = $is_creditor = NULL;

}

debug_flush('</table>');
sql_close();
exit;

?>