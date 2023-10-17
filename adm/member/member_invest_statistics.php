<?
###############################################################################
## 검색조건 회원별 카테고리별 투자내역
###############################################################################

set_time_limit(300);

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
	$sql_order.= " invest_amount DESC, invest_count DESC, ";
}
$sql_order.= " A.mb_no DESC";


$sql = "
	SELECT
		A.mb_no, A.mb_id, A.member_type, A.member_investor_type, A.mb_point, A.mb_name, A.mb_co_name, A.mb_datetime,
		(SELECT COUNT(idx) FROM cf_product_invest WHERE member_idx=A.mb_no AND invest_state='Y') AS invest_count,
		(SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE member_idx=A.mb_no AND invest_state='Y') AS invest_amount
	FROM
		g5_member A
	WHERE 1=1
		$sql_search
	ORDER BY
		$sql_order";
//print_rr($sql,'font-size:12px'); exit;
$res = sql_query($sql);
$rows = sql_num_rows($res);

for($i=0; $i<$rows; $i++) {
	$LIST[$i] = sql_fetch_array($res);
	if($LIST[$i]['mb_no']) {
		$LIST[$i]['C1']   = sql_fetch("SELECT COUNT(A.idx) AS invest_cnt, IFNULL(SUM(A.amount),0) AS invest_amt FROM cf_product_invest A LEFT JOIN cf_product B  ON A.product_idx=B.idx WHERE member_idx = '".$LIST[$i]['mb_no']."' AND invest_state = 'Y' AND B.category='1'");
		$LIST[$i]['C2-1'] = sql_fetch("SELECT COUNT(A.idx) AS invest_cnt, IFNULL(SUM(A.amount),0) AS invest_amt FROM cf_product_invest A LEFT JOIN cf_product B  ON A.product_idx=B.idx WHERE member_idx = '".$LIST[$i]['mb_no']."' AND invest_state = 'Y' AND B.category='2' AND mortgage_guarantees=''");
		$LIST[$i]['C2-2'] = sql_fetch("SELECT COUNT(A.idx) AS invest_cnt, IFNULL(SUM(A.amount),0) AS invest_amt FROM cf_product_invest A LEFT JOIN cf_product B  ON A.product_idx=B.idx WHERE member_idx = '".$LIST[$i]['mb_no']."' AND invest_state = 'Y' AND B.category='2' AND mortgage_guarantees='1'");
		$LIST[$i]['C3-1'] = sql_fetch("SELECT COUNT(A.idx) AS invest_cnt, IFNULL(SUM(A.amount),0) AS invest_amt FROM cf_product_invest A LEFT JOIN cf_product B  ON A.product_idx=B.idx WHERE member_idx = '".$LIST[$i]['mb_no']."' AND invest_state = 'Y' AND B.category='3' AND category2='1'");
		$LIST[$i]['C3-2'] = sql_fetch("SELECT COUNT(A.idx) AS invest_cnt, IFNULL(SUM(A.amount),0) AS invest_amt FROM cf_product_invest A LEFT JOIN cf_product B  ON A.product_idx=B.idx WHERE member_idx = '".$LIST[$i]['mb_no']."' AND invest_state = 'Y' AND B.category='3' AND category2='2'");
	}
}
$list_count = count($LIST);

$file_name = "회원별 투자현황 " . date('Ymd_Hi') . ".xls";
$file_name = iconv("utf-8", "euc-kr", $file_name);

header( "Content-type: application/vnd.ms-excel;" );
header( "Content-Disposition: attachment; filename=$file_name" );
header( "Content-description: PHP5 Generated Data" );

debug_flush("
<table border='1'>
	<tr>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>NO.</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>회원번호</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>아이디</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>업체명.성명</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>회원구분</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>투자자격구분</td>
		<td rowspan='2' align='center' bgcolor='#EEEEEE'>가입일</td>
		<td colspan='12' align='center' bgcolor='#EEEEEE'>누적투자현황</td>
	</tr>
	<tr>
		<td colspan='2' align='center' bgcolor='#EEEEEE'>동산</td>
		<td colspan='2' align='center' bgcolor='#EEEEEE'>부동산-PF</td>
		<td colspan='2' align='center' bgcolor='#EEEEEE'>부동산-주택담보</td>
		<td colspan='2' align='center' bgcolor='#EEEEEE'>매출채권-소상공인</td>
		<td colspan='2' align='center' bgcolor='#EEEEEE'>매출채권-면세점</td>
		<td colspan='2' align='center' bgcolor='#EEEEEE'>합계</td>
	</tr>
\n");

for($i=0,$num=$list_count; $i<$list_count; $i++,$num--) {

	if($LIST[$i]['member_type']=='2') {
		$PRINT['member_type'] = "법인회원";
		$PRINT['name'] = $LIST[$i]['mb_co_name'];
		$PRINT['investor_type'] = "";
	}
	else {
		$PRINT['member_type'] = "개인회원";
		$PRINT['name'] = $LIST[$i]['mb_name'];
		$PRINT['investor_type'] = $INDI_INVESTOR[$LIST[$i]['member_investor_type']]['title'];
	}


debug_flush ("
	<tr>
		<td align='center'>".$num."</td>
		<td align='center'>".$LIST[$i]['mb_no']."</td>
		<td align='center'>".$LIST[$i]['mb_id']."</td>
		<td align='center'>".$PRINT['name']."</td>
		<td align='center'>".$PRINT['member_type']."</td>
		<td align='center'>".$PRINT['investor_type']."</td>
		<td align='center'>".substr($PRINT['mb_datetime'], 0, 10)."</td>
		<td align='right' style='width:70px;'>".number_format($LIST[$i]['C1']['invest_cnt'])."건</td>
		<td align='right' style='width:120px;'>".number_format($LIST[$i]['C1']['invest_amt'])."</td>
		<td align='right' style='width:70px;'>".number_format($LIST[$i]['C2-1']['invest_cnt'])."건</td>
		<td align='right' style='width:120px;'>".number_format($LIST[$i]['C2-1']['invest_amt'])."</td>
		<td align='right' style='width:70px;'>".number_format($LIST[$i]['C2-2']['invest_cnt'])."건</td>
		<td align='right' style='width:120px;'>".number_format($LIST[$i]['C2-2']['invest_amt'])."</td>
		<td align='right' style='width:70px;'>".number_format($LIST[$i]['C3-1']['invest_cnt'])."건</td>
		<td align='right' style='width:120px;'>".number_format($LIST[$i]['C3-1']['invest_amt'])."</td>
		<td align='right' style='width:70px;'>".number_format($LIST[$i]['C3-2']['invest_cnt'])."건</td>
		<td align='right' style='width:120px;'>".number_format($LIST[$i]['C3-2']['invest_amt'])."</td>
		<td align='right' style='width:70px;'><b>".number_format($LIST[$i]['invest_count'])."건</b></td>
		<td align='right' style='width:120px;'><b>".number_format($LIST[$i]['invest_amount'])."</b></td>
	</tr>\n");

	unset($LIST[$i]);
	unset($PRINT);

}

debug_flush("</table>\n");

sql_close();

exit;

?>