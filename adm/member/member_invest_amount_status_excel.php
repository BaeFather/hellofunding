<?

// 2018-11-23 이정환 차장 요청분
// ID / 이름 / 예치금 / 투자잔액 / 투적투자금액 / 로그인수 출력

set_time_limit(0);

$base_path  = "/home/crowdfund/public_html";
include_once($base_path . '/common.cli.php');

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
else {
	$sql_order.= " live_invest_amount DESC,";
	$sql_order.= " total_invest_amount DESC,";
}
$sql_order.= " A.mb_no DESC";


$sql = "
	SELECT
		A.mb_no, A.mb_id, A.member_type, A.member_investor_type, A.is_creditor, A.is_owner_operator, A.mb_point,
		A.mb_name, A.mb_co_name, A.mb_datetime, A.mb_today_login, A.login_cnt, A.va_bank_code2, A.virtual_account2, A.va_private_name2,
		(SELECT COUNT(SA.idx) FROM cf_product_invest SA LEFT JOIN cf_product SB ON SA.product_idx=SB.idx WHERE SA.member_idx=A.mb_no AND SA.invest_state='Y' AND SB.state IN('1','2','5','8')) AS invest_count,
		(SELECT IFNULL(SUM(SA.amount), 0) FROM cf_product_invest SA LEFT JOIN cf_product SB ON SA.product_idx=SB.idx  WHERE SA.member_idx=A.mb_no AND SA.invest_state='Y' AND SB.state IN('1','2','5','8')) AS total_invest_amount,
		(SELECT IFNULL(SUM(SA.amount), 0) FROM cf_product_invest SA LEFT JOIN cf_product SB ON SA.product_idx=SB.idx  WHERE SA.member_idx=A.mb_no AND SA.invest_state='Y' AND SB.state IN('1','8')) AS live_invest_amount,
		(
			(SELECT IFNULL(SUM(SA.amount), 0) FROM cf_product_invest SA LEFT JOIN cf_product SB ON SA.product_idx=SB.idx  WHERE SA.member_idx=A.mb_no AND SA.invest_state='Y' AND SB.state IN('1','2','5','8')) -
			(SELECT IFNULL(SUM(SA.amount), 0) FROM cf_product_invest SA LEFT JOIN cf_product SB ON SA.product_idx=SB.idx  WHERE SA.member_idx=A.mb_no AND SA.invest_state='Y' AND SB.state IN('1','8'))
		) AS return_invest_amount,
		(SELECT insert_date FROM cf_product_invest WHERE member_idx=A.mb_no AND invest_state='Y' ORDER BY idx DESC LIMIT 1) AS last_invest_date
	FROM
		g5_member A
	WHERE 1=1
		$sql_search
	ORDER BY
		live_invest_amount DESC,
		total_invest_amount DESC,
		A.mb_no DESC";
//print_rr($sql, "font-size:12px"); exit;
$res = sql_query($sql);
$rows = sql_num_rows($res);

$file_name = "회원별 투자잔액 정리 " . date('Ymd_Hi') . ".xls";
$file_name = iconv("utf-8", "euc-kr", $file_name);

header( "Content-type: application/vnd.ms-excel;" );
header( "Content-Disposition: attachment; filename=$file_name" );
header( "Content-description: PHP5 Generated Data" );

debug_flush("<table border='1'>
	<tr>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>NO.</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>아이디</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>업체명.성명</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>회원구분</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>투자자격구분</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>예치금</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>누적투자액</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>상환완료액</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>투자잔액</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>최종투자일자</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>잔여투자한도</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>실시간<br>투자가능금액</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>로그인수</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>최종로그인</td>
		<td colspan='3' align='center' bgcolor='#EEEEEE'>가상계좌</td>
	</tr>
	<tr>
		<td align='center' bgcolor='#EEEEEE'>은행명</td>
		<td align='center' bgcolor='#EEEEEE'>가상계좌번호</td>
		<td align='center' bgcolor='#EEEEEE'>예금주</td>
	</tr>\n");

for($i=0,$num=$rows; $i<$rows; $i++,$num--) {

	$ROW = sql_fetch_array($res);

	if($ROW['invest_count'] > 0) {

		if($ROW['member_type']=='2') {
			$PRINT['member_type'] = "법인회원";
			$PRINT['name'] = $ROW['mb_co_name'];
			$PRINT['investor_type'] = "";
		}
		else {
			$PRINT['member_type'] = "개인회원";
			$PRINT['name'] = $ROW['mb_name'];
			$PRINT['investor_type'] = $INDI_INVESTOR[$ROW['member_investor_type']]['title'];
		}


		// 투자잔액
		if($ROW['member_type']=='2' || $ROW['member_investor_type']=='3') {
			$PRINT['inv_amt_limit'] = "<span style='display:block;text-align:center'>제한없음</span>";

			$realtime_invest_possible_amt_x = min($ROW['mb_point'], $INDI_INVESTOR['3']['site_limit']);

		}
		else {

			$inv_amt_limit = $INDI_INVESTOR[$ROW['member_investor_type']]['site_limit'] - $ROW['live_invest_amount'];
			$PRINT['inv_amt_limit'] = "<span style='display:block;text-align:right'>".number_format(max(0,$inv_amt_limit))."</span>";

			$realtime_invest_possible_amt_x = min($ROW['mb_point'], $inv_amt_limit);

		}

		$realtime_invest_possible_amt = floor($realtime_invest_possible_amt_x / 10000) * 10000;
		$PRINT['realtime_invest_possible_amt'] = "<span style='display:block;text-align:right'>".number_format(max(0,$realtime_invest_possible_amt))."</span>";


		debug_flush ("
	<tr>
		<td align='center'>".$num."</td>
		<td align='center'>".$ROW['mb_id']."</td>
		<td align='center'>".$PRINT['name']."</td>
		<td align='center'>".$PRINT['member_type']."</td>
		<td align='center'>".$PRINT['investor_type']."</td>
		<td align='right'>".number_format($ROW['mb_point'])."</td>
		<td align='right'>".number_format($ROW['total_invest_amount'])."</td>
		<td align='right'>".number_format($ROW['return_invest_amount'])."</td>
		<td align='right' style='color:red'>".number_format($ROW['live_invest_amount'])."</td>
		<td align='center' style='mso-number-format:\"@\";'>".substr($ROW['last_invest_date'],0,16)."</td>
		<td align='right' style='color:blue'>".$PRINT['inv_amt_limit']."</td>
		<td align='right' style='color:blue'>".$PRINT['realtime_invest_possible_amt']."</td>
		<td align='center'>".$ROW['login_cnt']."</td>
		<td align='center'>".substr($ROW['mb_today_login'], 0, 10)."</td>
		<td align='center'>".$BANK[$ROW['va_bank_code2']]."</td>
		<td align='center' style='mso-number-format:\"@\";'>".$ROW['virtual_account2']."</td>
		<td align='center'>".$ROW['va_private_name2']."</td>
	</tr>\n");

	}

	$inv_amt_limit = $realtime_invest_possible_amt = $realtime_invest_possible_amt_x = NULL;

	unset($ROW);
	unset($PRINT);

}

debug_flush("</table>\n");

sql_close();

exit;

?>