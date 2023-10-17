<?
###############################################################################
##   - 2019-01-21 업데이트 : 주민번호, 전화번호, 계좌번호 암,복호화 추가
###############################################################################

$sub_menu = '200400';
include_once('./_common.php');

auth_check($auth[$sub_menu], "w");

while( list($k, $v) = each($_REQUEST) ) { ${$k} = trim($v); }



header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=개인투자자승인".DATE("YmdHis").".xls");  //File name extension was wrong
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false);


$html_title = "개인투자자 승인";
$g5['title'] = $html_title.' 정보';



$sql_search = "1=1";
$sql_search.= " AND B.mb_level='1'";
$sql_search.= ($order_type) ? " AND A.order_type='$order_type'" : "";
$sql_search.= ($allow) ? " AND A.allow='$allow'" : "";

if($date_field=='order_date') $search_date = "LEFT(A.order_date, 10)";
else if($date_field=='allow_date') $search_date = "LEFT(A.allow_date, 10)";
else if($date_field=='rights_start_date') $search_date = "A.rights_start_date";
else if($date_field=='rights_end_date') $search_date = "A.rights_end_date";
if($search_date) {
	if($sdate && $edate) {
		$sql_search.= " AND $search_date BETWEEN '$sdate' AND '$edate'";
	}
	else {
	if($sdate) $sql_search.= " AND $search_date >= '$sdate'";
	if($edate) $sql_search.= " AND $search_date <= '$edate'";
	}
}

if($key_search && $keyword) {
	if( $key_search == 'B.mb_no' ) {
		$sql_search.= " AND $key_search='$keyword' ";
	}
	else if( $key_search == 'B.mb_hp' ) {
		$sql_search.= " AND $key_search='".masterEncrypt($keyword, false)."' ";
	}
	else {
		$sql_search.= " AND $key_search LIKE '%$keyword%' ";
	}
}


$sql = "
	SELECT
		COUNT(A.idx) AS cnt
	FROM
		investor_type_change_request A
	LEFT JOIN
		g5_member B  ON A.mb_no=B.mb_no
	WHERE
		$sql_search";
//print_rr($sql);
$row = sql_fetch($sql);
$total_count = $row['cnt'];


$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = "
	SELECT
		A.*,
		B.member_type, B.member_investor_type, B.is_creditor, B.is_owner_operator, B.mb_id, B.mb_name, B.mb_co_name, B.mb_hp, B.mb_email, LEFT(B.mb_datetime, 10) AS mb_datetime,mkind,
		(SELECT IFNULL(COUNT(amount),0) FROM
			(SELECT t1.amount,t1.member_idx,t1.insert_date FROM
			cf_product_invest t1 LEFT JOIN cf_product t2 ON t1.product_idx=t2.idx WHERE t2.category IN ('1','2') AND t1.invest_state='Y') tb1
			WHERE member_idx=A.mb_no AND insert_date>=LEFT(A.allow_date,10)
        ) as invest_cnt,
		(SELECT IFNULL(SUM(amount),0) FROM
			(SELECT t1.amount,t1.member_idx,t1.insert_date FROM
			cf_product_invest t1 LEFT JOIN cf_product t2 ON t1.product_idx=t2.idx WHERE t2.category IN ('1','2') AND t1.invest_state='Y') tb1
			WHERE member_idx=A.mb_no AND insert_date>=LEFT(A.allow_date,10)
        ) as invest_amt
	FROM
		investor_type_change_request A
	LEFT JOIN
		g5_member B  ON A.mb_no=B.mb_no
	WHERE
		$sql_search
	ORDER BY
		A.idx DESC";
//print_rr($sql);


$result = sql_query($sql);
$rcount = sql_num_rows($result);
for($i=0; $i<$rcount; $i++) {
	$LIST[$i] = sql_fetch_array($result);
	$LIST[$i]['mb_hp'] = masterDecrypt($LIST[$i]['mb_hp'], false);

	// 휴대폰 블라인드 처리
	if(!$_SESSION['ss_accounting_admin']) {
		$LIST[$i]['mb_hp'] = (strlen($LIST[$i]['mb_hp']) > 4) ? substr($LIST[$i]['mb_hp'], 0, strlen($LIST[$i]['mb_hp'])-4) . "****" : $LIST[$i]['mb_hp'];
	}

	//첨부파일 가져오기
	$fsql = "SELECT fname, description FROM investor_type_change_request_file WHERE req_idx='".$LIST[$i]['idx']."' ORDER BY idx";
	//echo $fsql."<br>";
	$fres = sql_query($fsql);
	while($frow = sql_fetch_array($fres)) {
		$LIST[$i]['file'][] = $frow;
	}

}
sql_free_result($result);

// 대기
$sql2 = "SELECT COUNT(A.idx) AS cnt FROM investor_type_change_request AS A LEFT JOIN g5_member AS B ON A.mb_no=B.mb_no WHERE $sql_search";
$sql2.= (preg_match("/A\.allow='wait'/", $sql_search)) ? "" : " AND A.allow='wait'";
//echo $sql2."<br>";
$row2   = sql_fetch($sql2);
$count2 = $row2['cnt'];

// 승인
$sql3 = "SELECT COUNT(A.idx) AS cnt FROM investor_type_change_request AS A LEFT JOIN g5_member AS B ON A.mb_no=B.mb_no WHERE $sql_search";
$sql3.= (preg_match("/A\.allow='Y'/", $sql_search)) ? "" : " AND A.allow='Y'";
//echo $sql3."<br>";
$row3   = sql_fetch($sql3);
$count3 = $row3['cnt'];

// 거부
$sql4 = "SELECT COUNT(A.idx) AS cnt FROM investor_type_change_request AS A LEFT JOIN g5_member AS B ON A.mb_no=B.mb_no WHERE $sql_search";
$sql4.= (preg_match("/A\.allow='N'/", $sql_search)) ? "" : " AND A.allow='N'";
//echo $sql4."<br>";
$row4   = sql_fetch($sql4);
$count4 = $row4['cnt'];

?>


	<!-- 리스트 START -->
	<table border=1>
		<thead>
		<tr>
			<th style="background-color:#eee;">NO</th>
			<th style="background-color:#eee;">회원번호</th>
			<th style="background-color:#eee;">아이디</th>
			<th style="background-color:#eee;">회원구분</th>
			<th style="background-color:#eee;">성명</th>
			<th style="background-color:#eee;">상호명</th>
			<th style="background-color:#eee;">휴대폰</th>
			<th style="background-color:#eee;">E-MAIL</th>
			<th style="background-color:#eee;">등록시 투자자격</th>
			<th style="background-color:#eee;">승인요청 투자자격</th>
			<th style="background-color:#eee;">요청일시</th>
			<th style="background-color:#eee;">승인상태</th>
			<th style="background-color:#eee;">승인일시</th>
			<th style="background-color:#eee;">자격만료일</th>
			<th style="background-color:#eee;">잔여일/유효일</th>
			<th style="background-color:#eee;">투자건수</th>
			<th style="background-color:#eee;">투자금액</th>
		</tr>
		</thead>
		<tbody>
<?
if(count($LIST) > 0) {
	for ($i=0; $i<count($LIST); $i++) {

		$list_num = $total_count - ($page - 1) * $rows - $i;

		if($LIST[$i]['allow']=='wait')   $print_allow = "<span style='color:blue'>대기</span>";
		else if($LIST[$i]['allow']=='Y') $print_allow = "<span style='color:green'>승인</span>";
		else if($LIST[$i]['allow']=='N') $print_allow = "<span style='color:brown'>거부</span>";

		$attach_file_tag = "";
		for($x=0,$y=1; $x<count($LIST[$i]['file']); $x++,$y++) {
			$file_path = '/data/member/investor/'. $LIST[$i]['file'][$x]['fname'];
			$attach_file_tag.= "<a href='$file_path' target='_blank' title='".addSlashes($LIST[$i]['file'][$x]['description'])."'><span class='fileMarker'>첨부파일".$y."</span></a>\n";
		}


		$rights_start_date = $rights_end_date = $rights_date = $total_auth_days = $valid_days = $print_valid_days = '';

		if($LIST[$i]['allow']=='Y') {

			$rights_start_date = ($LIST[$i]['rights_start_date'] > '0000-00-00') ? $LIST[$i]['rights_start_date'] : substr($LIST[$i]['allow_date'], 0, 10);
			$rights_end_date   = ($LIST[$i]['rights_end_date'] > '0000-00-00') ? $LIST[$i]['rights_end_date'] : date("Y-m-d", strtotime($LIST[$i]['allow_date']." +1 year"));
			if( (empty($LIST[$i]['rights_end_date']) || $LIST[$i]['rights_end_date'] <= '2018-11-30') && G5_TIME_YMD <= '2018-12-31' ) {
				$rights_end_date = "2018-11-30";
			}

			$rights_date = ($rights_start_date && $rights_end_date) ? $rights_end_date : '';

			$total_auth_days = ceil((strtotime($rights_end_date)-strtotime($rights_start_date))/86400) + 1;  // 자격 유효일수
			$valid_days = ceil((strtotime($rights_end_date)-time())/86400) + 1;		// 자격 잔여일수

			$print_valid_days = $valid_days."일 / ".$total_auth_days."일";

		}

?>
		<tr align="center">
			<td><?=$list_num?></td>
			<td><?=$LIST[$i]['mb_no']?></td>
			<td><?=$LIST[$i]['mb_id']?></td>
			<td><?=$LIST[$i]['mb_name']?> </td>
			<td><?php IF($LIST[$i]['mkind']) { ECHO ($LIST[$i]['mkind']=='1') ? '신규':'갱신'; } ?></td>
			<td><?=$LIST[$i]['mb_co_name']?></td>
			<td style="mso-number-format:\'@\';"><?=$LIST[$i]['mb_hp']?></td>
			<td><?=$LIST[$i]['mb_email']?></td>
			<td><?=$INDI_INVESTOR[$LIST[$i]['now_type']]['title']?></td>
			<td><?=$INDI_INVESTOR[$LIST[$i]['order_type']]['title']?></td>
			<td><?=substr($LIST[$i]['order_date'], 0, 16);?></td>
			<td><?=$print_allow?></td>
			<td><?=substr($LIST[$i]['allow_date'], 0, 16);?></td>
			<td><?=$rights_date?></td>
			<td><?=$print_valid_days?></td>
			<td align="right">
			<?php
				IF($LIST[$i]["mkind"] == "1") {
					ECHO number_format($LIST[$i]['invest_cnt']);
				} ELSE {
					ECHO "0";
				}
			?>
			건</td>
			<td align="right">
			<?php
				IF($LIST[$i]["mkind"] == "1") {
					ECHO number_format($LIST[$i]['invest_amt']);
				} ELSE {
					ECHO "0";
				}
				?> 원
			</td>
		</tr>
<?
		$article_num--;
	}
}else {
?>

		<tr>
			<td colspan="16" align="center" height="300px";>검색된 데이터가 없습니다.</td>
		</tr>

<?
}
?>
	</table>
	<!-- 리스트 E N D -->
