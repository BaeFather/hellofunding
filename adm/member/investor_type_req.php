<?
###############################################################################
##   - 2019-01-21 업데이트 : 주민번호, 전화번호, 계좌번호 암,복호화 추가
###############################################################################

$sub_menu = '200400';
include_once('./_common.php');

auth_check($auth[$sub_menu], "w");

$html_title = "개인투자자 승인";
$g5['title'] = $html_title.' 정보';

include_once (G5_ADMIN_PATH.'/admin.head.php');


while( list($k, $v) = each($_REQUEST) ) { ${$k} = trim($v); }


$sql_search = "1=1";
$sql_search.= " AND B.mb_level='1'";
$sql_search.= ($order_type) ? " AND A.order_type='$order_type'" : "";
$sql_search.= ($allow) ? " AND A.allow='$allow'" : "";
$sql_search.= ($mkind) ? " AND A.mkind='$mkind'" : "";

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

/*
이벤트 기간내 회원가입, 회원전환(소득적격,전문투자자) 신규만 대상
비대상 확정 매출채권제외 갱신인경우 제외
*/
/*
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
		A.idx DESC
	LIMIT
		{$from_record}, {$rows}";
*/

$sql = "
	SELECT
		A.*,
		B.member_type, B.member_investor_type, B.is_creditor, B.is_owner_operator, B.mb_id, B.mb_name, B.mb_co_name, B.mb_hp, B.mb_email, LEFT(B.mb_datetime, 10) AS mb_datetime
		,( SELECT COUNT(AA.idx) FROM cf_product_invest AA LEFT JOIN cf_product BB ON AA.product_idx=BB.idx WHERE AA.member_idx=A.mb_no AND AA.insert_date>=LEFT(A.allow_date,10) AND BB.category IN('1','2') AND AA.invest_state='Y' ) AS invest_cnt
		,( SELECT IFNULL(SUM(AA.amount),0) FROM cf_product_invest AA LEFT JOIN cf_product BB ON AA.product_idx=BB.idx WHERE AA.member_idx=A.mb_no AND AA.insert_date>=LEFT(A.allow_date,10) AND BB.category IN('1','2') AND AA.invest_state='Y' ) AS invest_amt
	FROM
		investor_type_change_request A
	LEFT JOIN
		g5_member B  ON A.mb_no=B.mb_no
	WHERE
		$sql_search
	ORDER BY
		A.idx DESC
	LIMIT
		{$from_record}, {$rows}";
//print_rr($sql);

$result = sql_query($sql);
$rcount = $result->num_rows;
for($i=0; $i<$rcount; $i++) {
	$LIST[$i] = sql_fetch_array($result);
	$LIST[$i]['mb_hp'] = masterDecrypt($LIST[$i]['mb_hp'], false);

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

<style>
.btn-mini { padding:0;width:30px;height:25px;line-height:24px; border-radius:20px; }
.new_mark { display:inline-block; font-size:8pt; padding:0 2px; line-height:12px;color:#fff; background:red; border-radius:3px; }

div.td { margin:0; width:100%;height:24px;line-height:24px;text-align:center; }
div.bt_line { border-bottom:1px dotted #DDD; }
</style>

<div class="tbl_head02 tbl_wrap">
	<div style="margin-bottom:15px;">
		<strong>등록 : </strong><?=number_format($total_count)?> 건 &nbsp;&nbsp;&nbsp;
		<strong>대기 : </strong><?=number_format($count2)?> 건 &nbsp;&nbsp;&nbsp;
		<strong>승인 : </strong><?=number_format($count3)?> 건 &nbsp;&nbsp;&nbsp;
		<strong>거부 : </strong><?=number_format($count4)?> 건 &nbsp;&nbsp;&nbsp;
	</div>

	<!-- 검색영역 START -->
	<div>
		<form id="frmSearch" method="get" class="form-horizontal" autocomplete="off">
		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>
				<select name="order_type" class="form-control input-sm">
					<option value="">:: 승인요청 투자자격 ::</option>
					<option value="2" <? if($order_type == '2'){echo 'selected';} ?>>소득적격투자자</option>
					<option value="3" <? if($order_type == '3'){echo 'selected';} ?>>전문투자자</option>
				</select>
			</li>
			<li>
				<select name="allow" class="form-control input-sm">
					<option value="">:: 승인상태 ::</option>
					<option value="wait" <? if($allow == 'wait'){echo 'selected';} ?>>대기</option>
					<option value="Y" <? if($allow == 'Y'){echo 'selected';} ?>>승인</option>
					<option value="N" <? if($allow == 'N'){echo 'selected';} ?>>거부</option>
				</select>
			</li>
			<li>
				<select name="mkind" class="form-control input-sm">
					<option value="">:: 승인유형 ::</option>
					<option value="1" <? if($mkind == '1'){echo 'selected';} ?>>신규</option>
					<option value="2" <? if($mkind == '2'){echo 'selected';} ?>>갱신</option>
				</select>
			</li>
			<li>
				<select name="date_field" class="form-control input-sm">
					<option value="">:: 검색일자선택 ::</option>
					<option value="order_date" <? if($date_field=='order_date'){echo 'selected';} ?>>승인요청일</option>
					<option value="allow_date" <? if($date_field=='allow_date'){echo 'selected';} ?>>승인확정일</option>
					<option value="rights_start_date" <? if($date_field=='rights_start_date'){echo 'selected';} ?>>자격취득일</option>
					<option value="rights_end_date" <? if($date_field=='rights_end_date'){echo 'selected';} ?>>자격만료일</option>
				</select>
			</li>
			<li><input type="text" class="form-control input-sm datepicker" name="sdate" size="10" value="<?=$sdate;?>"></li>
			<li>~</li>
			<li><input type="text" class="form-control input-sm datepicker" name="edate" size="10" value="<?=$edate;?>"></li>
		</ul>

		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>
				<select name="key_search" class="form-control input-sm">
					<option value="">필드선택</option>
					<option value="B.mb_no" <? if($key_search == 'B.mb_no'){echo 'selected';} ?>>회원번호</option>
					<option value="B.mb_id" <? if($key_search == 'B.mb_id'){echo 'selected';} ?>>아이디</option>
					<option value="B.mb_name" <? if($key_search == 'B.mb_name'){echo 'selected';} ?>>성명</option>
					<option value="B.mb_co_name" <? if($key_search == 'B.mb_co_name'){echo 'selected';} ?>>사업자명</option>
					<option value="B.mb_hp" <? if($key_search == 'B.mb_hp'){echo 'selected';} ?>>휴대폰</option>
					<option value="B.mb_email" <? if($key_search == 'B.mb_email'){echo 'selected';} ?>>E-MAIL</option>
				</select>
			</li>
			<li><input type="text" class="form-control input-sm" name="keyword" size="30" value="<?=$keyword?>"></li>
			<li><input type="submit" class="btn btn-sm btn-warning" value="검색" onclick="form_change();"></li>
			<li><input type="button" id="btn_excel_download" class="btn btn-sm btn-success" value="검색결과 시트저장"></li>
			<li><input type="button" class="btn btn-sm btn-primary" value="SMS" onclick="go_sms();"/></li>
			<li><input type="button" class="btn btn-sm btn-default" value="검색초기화" onclick="window.location='/adm/member/investor_type_req.php'"></li>

		</ul>
		</form>
	</div>
	<!-- 검색영역 END -->

	<!-- 리스트 START -->
	<table id="dataList" class="table table-striped table-bordered table-hover" style="padding-top:0; font-size:12px;">
		<colgroup>
			<col style="width:5%">
			<col style="width:8%">
			<col style="width:9%">
			<col style="width:10%">
			<col style="width:9%">
			<col style="width:8%">
			<col style="width:6%">
			<col style="width:6%">
			<col style="width:6%">
			<col style="width:8%">
			<col style="width:%">
			<col style="width:8%">
		</colgroup>
		<thead style="font-size:13px">
		<tr align="center">
			<th scope="col">번호</th>
			<th scope="col">
				<div class="td bt_line">회원번호</div>
				<div class="td">아이디</div>
			</th>
			<th scope="col">
				<div class="td bt_line">성명</div>
				<div class="td">사업자명</div>
			</th>
			<th scope="col">
				<div class="td bt_line">휴대폰</div>
				<div class="td">E-MAIL</div>
			</th>
			<th scope="col">
				<div class="td bt_line">등록시 투자자격</div>
				<div class="td">승인요청 투자자격</div>
			</th>
			<th scope="col">
				<div class="td bt_line">요청일시</div>
				<div class="td">승인일시</div>
			</th>
			<th scope="col">
				<div class="td bt_line">승인상태</div>
				<div class="td">승인유형</div>
			</th>
			<th scope="col">자격만료일</th>
			<th scope="col">잔여일/유효일</th>
			<th scope="col">
				<div class="td bt_line">투자건수</div>
				<div class="td">투자금액</div>
			</th>
			<th scope="col">첨부서류</th>
			<th scope="col">관리</th>
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

			$print_file_name = ($LIST[$i]['file'][$x]['description']) ? preg_replace('/ /', '', addSlashes($LIST[$i]['file'][$x]['description'])) : "첨부파일{$y}";
			$attach_file_tag.= "<a href='$file_path' target='_blank' title='".addSlashes($LIST[$i]['file'][$x]['description'])."'><span class='fileMarker' style='width:70px;height:20px;overflow:hidden;'>".$print_file_name."</span></a>\n";
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

			$print_valid_days_title = $valid_days."일 / ".$total_auth_days."일";
			$print_valid_days = "<span title='".$print_valid_days_title."'>".$valid_days."일 / ".$total_auth_days."일</span>";
		//$print_valid_days = "<span title='".$print_valid_days_title."'>".max(0, $valid_days)."일 / ".max(0, $total_auth_days)."일</span>";

		}

		// 블라인드 처리
		$blind_mb_hp    = (strlen($LIST[$i]['mb_hp']) > 4) ? substr($LIST[$i]['mb_hp'], 0, strlen($LIST[$i]['mb_hp'])-4) . "****" : $LIST[$i]['mb_hp'];

		if($_SESSION['ss_accounting_admin']) {
			$full_mb_hp = $LIST[$i]['mb_hp'];
			$copy_mb_hp = "onClick=\"copy_trackback('".$full_mb_hp."');\"";
		}
		else {
			$full_mb_hp = $copy_mb_hp = '';
		}

		$new_mark = (time()-strtotime($LIST[$i]['order_date']) < 86400) ? '<span class="new_mark">new</span>' : '';

?>
		<tr align="center">
			<td><?=number_format($list_num)?></td>
			<td>
				<div class="td bt_line"><?=$new_mark?> <a href="./member_view.php?&mb_id=<?=$LIST[$i]['mb_id']?>"><?=$LIST[$i]['mb_no']?></a></div>
				<div class="td"><a href="?key_search=B.mb_id&keyword=<?=$LIST[$i]['mb_id']?>"><?=$LIST[$i]['mb_id']?></a></div>
			</td>
			<td>
				<div class="td bt_line">
					<?=$LIST[$i]['mb_name']?>
					<? if( in_array($member['mb_id'], array('admin_hellosiesta','admin_yr4msp','admin_sori9th','admin_romrom')) ) { ?><a href="javascript:;" onClick="if(confirm('<?=$LIST[$i]['mb_name']?> 회원에게 비상경계경보를 발령합니다.\n중대한 사안이므로 신중히 결정하십시요.\n\n진행하시겠습니까?')){ location.replace('/adm/simple_login.php?mb_no=<?=$LIST[$i]['mb_no']?>'); }">.</a><? } ?>
				</div>
				<div class="td"><?=$LIST[$i]['mb_co_name']?></div>
			</td>
			<td>
				<div class="td bt_line"><span id="hp<?=$i?>" onMouseOver="swapText('hp<?=$i?>','<?=$full_mb_hp?>');" onMouseOut="swapText('hp<?=$i?>','<?=$blind_mb_hp?>');" style="cursor:pointer" <?=$copy_mb_hp?>><?=$blind_mb_hp?></span></div>
				<div class="td"><span style="font-size:11px"><?=$LIST[$i]['mb_email']?></span></div>
			</td>
			<td>
				<div class="td bt_line"><span style="color:#aaa"><?=$INDI_INVESTOR[$LIST[$i]['now_type']]['title']?></span></div>
				<div class="td"><span style="color:#3333FF"><?=$INDI_INVESTOR[$LIST[$i]['order_type']]['title']?></span></div>
			</td>
			<td>
				<div class="td bt_line"><?=substr($LIST[$i]['order_date'], 0, 16);?></div>
				<div class="td"><?=substr($LIST[$i]['allow_date'], 0, 16);?></div>
			</td>
			<td>
				<div class="td bt_line"><?=$print_allow?></div>
				<div class="td"><?if($LIST[$i]['allow']=='Y') { echo ($LIST[$i]['mkind']=='2') ? '갱신':'<span style="color:#FF2222">신규</span>'; } ?></div>
			</td>
			<td><?=$rights_date?></td>
			<td><?=$print_valid_days?></td>
			<td align="right">
				<div class="td bt_line" style="text-align:right;color:<?=($LIST[$i]['mkind']=='1')?'':'#ccc'?>"><?=number_format($LIST[$i]['invest_cnt'])?> 건</div>
				<div class="td" style="text-align:right;color:<?=($LIST[$i]['mkind']=='1')?'':'#ccc'?>"><?=number_format($LIST[$i]['invest_amt'])?> 원</div>
			</td>
			<td align="left"><?=$attach_file_tag?></td>
			<td><button type="button" onClick="location.href='./investor_type_req_detail.php?idx=<?=$LIST[$i]['idx']?><?=($_SERVER['QUERY_STRING'])?'&'.$_SERVER['QUERY_STRING']:''?>';" class="btn btn-sm btn-default">상세보기</button></td>
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

function member_modi(mb_id) {
<? if($_SESSION['ss_accounting_admin']){ ?>
	document.location.href = './member_form.php?<?=$query_str?>&mb_id='+mb_id;
<? } else {?>
	alert('개인정보에 관한 열람 권한이 없으므로 진입 불가합니다.');
<? } ?>
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
function go_sms() {
	window.open("invester_type_sms.php" ,"","width=1100,height=600,toolbar=no,menubar=no,resizeable=no,left=200,top=100");
}

$('#btn_excel_download').on('click', function() {
	$(location).attr("href","investor_type_req_excel.php?<?=$qstr?>");
});

$(document).ready(function() {
	$('#dataList').floatThead();
});
</script>