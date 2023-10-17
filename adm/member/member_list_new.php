<?
$sub_menu = '200100';
include_once('./_common.php');
include_once(G5_EDITOR_LIB);

auth_check($auth[$sub_menu], "w");

//include_once('../secure_data_connect_log.php');

$html_title = "전체 회원";
$g5['title'] = $html_title.' 정보';

include_once (G5_ADMIN_PATH.'/admin.head.php');

/* 검색 필드 조합 START */

// 받은 데이터를 변수화
foreach($_REQUEST as $k=>$v) {
	$$_REQUEST[$k] = $v;
}


if(!$member_group) $member_group = "F";

//$sql_common = " FROM {$g5['member_table']} A";

$sql_search = " AND A.mb_leave_date=''";
$sql_search.= "";
//$sql_search.= " AND binary(A.mb_memo) NOT REGEXP '[0-9]+ 삭제함' ";
//$sql_search.= " AND A.mb_memo NOT REGEXP '[0-9]+ 삭제함' ";

if($member_group)           $sql_search.= " AND A.member_group='$member_group' ";
if($member_type)            $sql_search.= " AND A.member_type='$member_type' ";
if($member_investor_type)   $sql_search.= " AND A.member_investor_type='$member_investor_type' ";
if($is_creditor=='Y')       $sql_search.= " AND A.is_creditor='Y' ";
if($is_owner_operator=='1') $sql_search.= " AND A.is_owner_operator='1' ";
if($remit_fee=='1')         $sql_search.= " AND A.remit_fee='1' ";
if($mb_mailling=='1')       $sql_search.= " AND A.mb_mailling='1' ";
if($insidebank_after_trans_target) $sql_search.= " AND A.insidebank_after_trans_target='$insidebank_after_trans_target' ";
if($is_rest=='Y')           $sql_search.= " AND A.is_rest='Y' ";
if($mb_level) {
	$sql_search.= ($mb_level=='null') ? " AND A.mb_level='0' " : " AND A.mb_level='$mb_level' ";
}
else {
	$sql_search.= " AND A.mb_level IN(1,2,3,4,5) ";
}

if($mb_sms=='1') $sql_search.= " AND A.mb_sms='1' ";
if($start_date && $end_date) {
	$sql_search.= " AND LEFT(A.mb_datetime,10) BETWEEN '$start_date' AND '$end_date'";
}
else {
	if($start_date) $sql_search.= " AND LEFT(A.mb_datetime,10)>='$start_date' ";
	if($end_date)  $sql_search .= " AND LEFT(A.mb_datetime,10)<='$end_date' ";
}
if($start_point && $end_point) {
	$sql_search .= " AND A.mb_point BETWEEN '$start_point' AND '$end_point' ";
}
else {
	if($start_point) $sql_search .= " AND A.mb_point >= '$start_point' ";
	if($end_point)   $sql_search .= " AND A.mb_point <= '$end_point' ";
}
if($receive_method) {
	$sql_search .= ($receive_method=='unknown') ? " AND A.receive_method=''" : " AND A.receive_method='$receive_method' ";
}

if($key_search && $keyword) {
	if($key_search=='A.mb_no') {
		$sql_search .= " AND $key_search='$keyword' ";
	}
	else if($key_search=='A.syndi_id') {
		if($keyword=='finnq') {
			$sql_search .=  " AND finnq_userid!=''";
		}
		else if($keyword=='hktvwowstar') {
			$sql_search .=  " AND wowstar_userid!=''";
		}
		else if($keyword=='chosun') {
			$sql_search .=  " AND chosun_userid!=''";
		}
		else if($keyword=='tvtalk') {
			$sql_search .=  " AND tvtalk_userid!=''";
		}
	}
	else {
		$sql_search .= " AND $key_search LIKE '%$keyword%' ";
	}
}
/* 검색 필드 조합 E N D */


$sql_order = "";
if($sort_field) {
	$sql_order.= $sort_field." ".$sort.", ";
}
$sql_order.= " A.mb_no DESC ";

$sql = "SELECT COUNT(mb_no) AS cnt, IFNULL(SUM(mb_point), 0) AS sum_point FROM g5_member A WHERE 1=1 $sql_search";
$row = sql_fetch($sql);
$total_count = $row['cnt'];
$total_point = $row['sum_point'];

$rows = 100;
//$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함


$sql = "
	SELECT
		A.mb_no, A.mb_id, A.mb_level, A.member_group, A.member_type, A.member_investor_type, A.is_creditor, A.is_owner_operator, A.mb_point,
		A.mb_name, A.mb_co_name, A.mb_email, A.mb_hp, A.mb_datetime, A.edit_datetime, A.mb_today_login, A.mb_login_ip, A.login_cnt, A.mb_ip, A.is_rest, A.mb_mailling, A.mb_sms,
		A.receive_method, A.bank_code, A.account_num, A.bank_private_name, A.va_bank_code2, A.virtual_account2, A.va_private_name2,
		A.syndi_id, A.finnq_userid, A.finnq_rdate, A.wowstar_userid, A.wowstar_rdate, A.chosun_userid, A.chosun_rdate, A.tvtalk_userid, A.tvtalk_rdate,
		A.business_license, A.bankbook, A.loan_co_license,
		B.rdate AS vi_date, B.rhour, B.referer, B.site_id, B.site_ca, B.keyword, B.is_paid,
		(SELECT COUNT(mb_no) FROM g5_member WHERE rec_mb_no=A.mb_no AND virtual_account!='' AND rec_mb_id!='') AS recommend_count,
		(SELECT COUNT(SA.idx) FROM cf_product_invest SA LEFT JOIN cf_product SB ON SA.product_idx=SB.idx WHERE SA.member_idx=A.mb_no AND SA.invest_state='Y' AND SB.state IN('1','2','5')) AS invest_count,
		(SELECT IFNULL(SUM(SA.amount), 0) FROM cf_product_invest SA LEFT JOIN cf_product SB ON SA.product_idx=SB.idx  WHERE SA.member_idx=A.mb_no AND SA.invest_state='Y' AND SB.state IN('1','2','5')) AS invest_amount
	FROM
		g5_member A
	LEFT OUTER JOIN
		cf_visit_status B  ON A.vi_idx=B.idx
	WHERE 1=1
		$sql_search
	ORDER BY
		$sql_order
	LIMIT
		$from_record, $rows";
//echo "<pre>".$sql."</pre>";

$result = sql_query($sql);
$rcount = sql_num_rows($result);

$num = $total_count - $from_record;

// 등록회원 수
$R = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE mb_leave_date='' AND mb_level IN(1,2,3,4,5)");
$count1 = $R['cnt'];

// 투자회원
$R = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE mb_leave_date='' AND mb_level IN(1,2,3,4,5) AND member_group='F'");
$count1_1 = $R['cnt'];

// 대출회원
$R = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE mb_leave_date='' AND mb_level IN(1,2,3,4,5) AND member_group='L'");
$count1_2 = $R['cnt'];

// 개인회원
$R = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE mb_leave_date='' AND mb_level IN(1,2,3,4,5) AND member_group='F' AND member_type=1");
$count2 = $R['cnt'];

// 법인회원
$R = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE mb_leave_date='' AND mb_level IN(1,2,3,4,5) AND member_type=2");
$count3 = $R['cnt'];

// SNS회원
$R = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE mb_leave_date='' AND mb_level IN(1,2,3,4,5) AND member_type=3");
$count4 = $R['cnt'];

// 메일수신
$R = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE mb_leave_date='' AND mb_level IN(1,2,3,4,5) AND mb_mailling=1");
$count5 = $R['cnt'];

// SMS수신
$R = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE mb_leave_date='' AND mb_level IN(1,2,3,4,5) AND mb_sms=1");
$count6 = $R['cnt'];

// 승인대기 회원
$R = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE mb_leave_date='' AND mb_level='0' AND member_group='F'");
$count7 = $R['cnt'];

// 승인거절 회원
$R = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE mb_leave_date='' AND mb_level='100' AND member_group='F'");
$count8 = $R['cnt'];

// 한경TV조인 회원
$R = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE mb_leave_date='' AND mb_level IN(1,2,3,4,5)  AND member_group='F' AND wowstar_userid!=''");
$count9 = $R['cnt'];

// 핀크 회원
$R = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE mb_leave_date='' AND mb_level IN(1,2,3,4,5)  AND member_group='F' AND finnq_userid!=''");
$count11 = $R['cnt'];

// 동아 재테크.핀테크쑈 회원
$R = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE mb_leave_date='' AND mb_level IN(1,2,3,4,5)  AND member_group='F' AND rec_mb_id='donga_expo'");
$count12 = $R['cnt'];

// 조선일보 땅집고 회원
$R = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE mb_leave_date='' AND mb_level IN(1,2,3,4,5)  AND member_group='F' AND syndi_id='chosun'");
$count_chosun = $R['cnt'];

// 조선일보 땅집고 회원
$R = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE mb_leave_date='' AND mb_level IN(1,2,3,4,5)  AND member_group='F' AND tvtalk_userid!=''");
$count_tvtalk = $R['cnt'];

// 휴면계정(최종로그인이 1년 이상인 회원)
$R = sql_fetch("SELECT COUNT(mb_no) AS cnt FROM g5_member WHERE mb_leave_date='' AND mb_level IN(1,2,3,4,5)  AND member_group='F' AND is_rest='Y'");
$count10 = $R['cnt'];
?>

<link href="/adm/css/bootstrap.min.css" rel="stylesheet">
<link href="/adm/css/jquery-ui.min.css" rel="stylesheet">
<script src="/adm/js/jquery-ui.min.js"></script>
<script src="/adm/js/jquery.form.js"></script>

<div class="tbl_head02 tbl_wrap">
	<div style="display:inline-block;margin-bottom:8px;">
		<ul style="clear:both;list-style:none;padding:0">
			<li style="float:left;">등록회원 : <?=number_format($count1);?>명</li>
			<li style="float:left;margin-left:20px;">[투자회원 : <?=number_format($count1_1);?>명 | 대출회원 : <?=number_format($count1_2);?>명] /
				[개인회원 : <?=number_format($count2);?>명 | 법인회원 : <?=number_format($count3);?>명 | SNS회원 : <?=number_format($count4);?>명]
			</li>
		</ul>
		<ul style="clear:both;list-style:none;padding:0">
			<li style="float:left;">메일수신 : <?=number_format($count5);?>명</li>
			<li style="float:left;margin-left:20px;">SMS수신 : <?=number_format($count6);?>명</li>
		</ul>
		<ul style="clear:both;list-style:none;padding:0;">
			<li style="float:left;"><a href="?mb_level=null" style="color:brown">승인대기 : <?=number_format($count7);?>명</a></li>
			<li style="float:left;margin-left:20px;"><a href="?mb_level=100" style="color:red">승인거절 : <?=number_format($count8);?>명</a></li>
			<li style="float:left;margin-left:20px;"><a href="?key_search=A.syndi_id&keyword=hktvwowstar" style="color:green">한경TV회원 : <?=number_format($count9);?>명</a></li>
			<li style="float:left;margin-left:20px;"><a href="?key_search=A.syndi_id&keyword=finnq" style="color:green">핀크회원 : <?=number_format($count11);?>명</a></li>
			<li style="float:left;margin-left:20px;"><a href="?key_search=A.rec_mb_id&keyword=donga_expo" style="color:green">동아 재테크.핀테크쑈 유치회원 : <?=number_format($count12);?>명</a></li>
			<li style="float:left;margin-left:20px;"><a href="?key_search=A.syndi_id&keyword=chosun" style="color:green">땅집고 : <?=number_format($count_chosun);?>명</a></li>
			<li style="float:left;margin-left:20px;"><a href="?key_search=A.syndi_id&keyword=tvtalk" style="color:green">TvTalk : <?=number_format($count_tvtalk);?>명</a></li>
			<li style="float:left;margin-left:20px;"><a href="?is_rest=Y" style="color:#3366FF">휴면계정 : <?=number_format($count10);?>명</a></li>
		</ul>
	</div>

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
for($i=0; $i<count($INDI_INVESTOR); $i++) {
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
			<li></li>
			<li>가입일</li>
			<li><input type="text" name="start_date" value="<?=$start_date?>" class="form-control input-sm datepicker" style="width:120px" readonly></li>
			<li>~</li>
			<li><input type="text" name="end_date" value="<?=$end_date?>" class="form-control input-sm datepicker" style="width:120px" readonly></li>
			<li></li>
			<li>예치금</li>
			<li><input type="text" name="start_point" value="<?=$start_point?>" class="form-control input-sm" style="width:120px"></li>
			<li>~</li>
			<li><input type="text" name="end_point" value="<?=$end_point?>" class="form-control input-sm" style="width:120px"></li>
		</ul>

		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li><label class="checkbox-inline"><input type="checkbox" name="is_owner_operator" value="1" <? if($is_owner_operator=='1'){echo 'checked';} ?>> 개인사업자</label></li>
			<li><label class="checkbox-inline"><input type="checkbox" name="is_creditor" value="Y" <? if($is_creditor=='Y'){echo 'checked';} ?>> 대부업</label></li>
			<li><label class="checkbox-inline"><input type="checkbox" name="remit_fee" value="1" <? if($remit_fee=='1'){echo 'checked';} ?>> 플랫폼수수료면제</label></li>
			<li><label class="checkbox-inline"><input type="checkbox" name="mb_mailling" value="1" <? if($mb_mailling=='1'){echo 'checked';} ?>> 메일수신</label></li>
			<li><label class="checkbox-inline"><input type="checkbox" name="mb_sms" value="1" <? if($mb_sms=='1'){echo 'checked';} ?>> SMS수신</label></li>
			<li><label class="checkbox-inline"><input type="checkbox" name="is_rest" value="Y" <? if($is_rest=='Y'){echo 'checked';} ?>> 휴면계정</label></li>
			<li><label class="checkbox-inline"><input type="checkbox" name="insidebank_after_trans_target" value="1" <? if($insidebank_after_trans_target=='1'){echo 'checked';} ?>> 신한 예치금 전환 대상자</label></li>
		</ul>

		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>
				<select name="key_search" class="form-control input-sm" style="width:150px">
					<option value="">필드선택</option>
					<option value="A.mb_no" <? if($key_search == 'A.mb_no'){echo 'selected';} ?>>회원번호</option>
					<option value="A.mb_id" <? if($key_search == 'A.mb_id'){echo 'selected';} ?>>아이디</option>
					<option value="A.mb_email" <? if($key_search == 'A.mb_email'){echo 'selected';} ?>>이메일</option>
					<option value="A.mb_name" <? if($key_search == 'A.mb_name'){echo 'selected';} ?>>이름(담당자명)</option>
					<option value="A.mb_co_name" <? if($key_search == 'A.mb_co_name'){echo 'selected';} ?>>상호명</option>
					<option value="A.mb_co_reg_num" <? if($key_search == 'A.mb_co_reg_num'){echo 'selected';} ?>>사업자번호</option>
					<option value="A.mb_hp" <? if($key_search == 'A.mb_hp'){echo 'selected';} ?>>휴대폰</option>
					<option value="A.virtual_account2" <? if($key_search == 'A.virtual_account2'){echo 'selected';} ?>>가상계좌번호(신한)</option>
					<option value="A.virtual_account" <? if($key_search == 'A.virtual_account'){echo 'selected';} ?>>가상계좌번호(세틀뱅크)</option>
					<option value="A.account_num" <? if($key_search == 'A.account_num'){echo 'selected';} ?>>환급계좌번호</option>
					<option value="A.bank_private_name" <? if($key_search == 'A.bank_private_name'){echo 'selected';} ?>>환금계좌예금주</option>
					<option value="A.syndi_id" <? if($key_search == 'A.syndi_id'){echo 'selected';} ?>>신디케이터ID</option>
					<option value="A.syndi_userid" <? if($key_search == 'A.syndi_userid'){echo 'selected';} ?>>신디케이션 회원인덱스</option>
					<option value="A.rec_mb_id" <? if($key_search == 'A.rec_mb_id'){echo 'selected';} ?>>추천인아이디</option>
				</select>
			</li>
			<li><input type="text" name="keyword" value="<?=$keyword?>" class="form-control input-sm" style="width:250px"></li>
			<li><button type="submit" class="btn btn-sm btn-warning" onClick="form_change();">검색</button></li>
			<li><button type="button" class="btn btn-sm btn-success" onclick="excel_down();">엑셀저장</button></li>
		</ul>
		</form>

		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>
				<select id="sort_field" class="form-control input-sm" style="width:150px;">
					<option value="">정렬필드</option>
					<option value="recommend_count" <? if($sort_field == 'recommend_count'){echo 'selected';} ?>>추천받은 수</option>
					<option value="A.login_cnt" <? if($sort_field == 'A.login_cnt'){echo 'selected';} ?>>로그인 수</option>
					<option value="A.mb_point" <? if($sort_field == 'A.mb_point'){echo 'selected';} ?>>예치금</option>
					<option value="invest_count" <? if($sort_field == 'invest_count'){echo 'selected';} ?>>투자상품수</option>
					<option value="invest_amount" <? if($sort_field == 'invest_amount'){echo 'selected';} ?>>누적투자금액</option>
					<option value="A.mb_today_login" <? if($sort_field == 'A.mb_today_login'){echo 'selected';} ?>>최종접속일</option>
				</select>
			</li>
			<li>
				<button type="button" onClick="sortList('DESC');" class="btn btn-sm btn-<?=($sort=='DESC')?'info':'default';?>">내림차순</button>
				<button type="button" onClick="sortList('ASC');" class="btn btn-sm btn-<?=($sort=='ASC')?'info':'default';?>">오름차순</button> &nbsp;&nbsp;
				<button type="button" onClick="location.href='./drop_member_list.php'" class="btn btn-sm btn-warning">탈퇴회원보기</button>
			</li>
			<li>
				<button type="button" onClick="location.href='member_form.php?member_type=1';" class="btn btn-sm btn-primary">개인투자회원등록</button>
				<button type="button" onClick="location.href='member_form.php?member_type=2';" class="btn btn-sm btn-primary">법인투자회원등록</button>
				<!--<button type="button" onClick="location.href='member_form.php?member_type=1&member_group=L';" class="btn btn-sm btn-primary">개인대출자등록</button>-->
				<button type="button" onClick="location.href='member_form.php?member_type=2&member_group=L';" class="btn btn-sm btn-danger">법인대출자등록</button>
			</li>
		</ul>
	</div>

	<script>
	function sortList(param) {
		if(document.getElementById('sort_field').value!='') {
			url = '/adm/member/member_list.php'
					+ '?token=<?=$token?>'
					+ '&member_group=<?=$member_group?>'
					+ '&member_type=<?=$member_type?>'
					+ '&member_investor_type=<?=$member_investor_type?>'
					+ '&is_creditor=<?=$is_creditor?>'
					+ '&is_owner_operator=<?=$is_owner_operator?>'
					+ '&is_rest=<?=$is_rest?>'
					+ '&mb_level=<?=$mb_level?>'
					+ '&mb_mailling=<?=$mb_mailling?>'
					+ '&mb_sms=<?=$mb_sms?>'
					+ '&insidebank_after_trans_target=<?=$insidebank_after_trans_target?>'
					+ '&start_date=<?=$start_date?>'
					+ '&end_date=<?=$end_date?>'
					+ '&start_point=<?=$start_point?>'
					+ '&end_point=<?=$end_point?>'
					+ '&receive_method=<?=$receive_method?>'
					+ '&key_search=<?=$key_search?>'
					+ '&keyword=<?=urlencode($keyword)?>'
					+ '&sort_field=' + document.getElementById('sort_field').value
					+ '&sort=' + param
			location.href= url;
		}
		else {
			alert('정렬필드를 선택하십시요.'); document.getElementById('sort_field').focus();
		}
	}
	</script>


	<!-- 검색영역 E N D -->

<style>
.btn-mini { padding:0;width:25px;height:25px;line-height:24px; border-radius:20px; }

div.td { margin:0; width:100%;height:24px;line-height:24px;text-align:center; }
div.bt_line { border-bottom:1px dotted #DDD; }

.syndiUL {clear:both;display:inline-block; padding:0; list-style:none;width:100%;}
.syndiUL > li {float:left; width:100%;height:24px;line-height:24px; border-bottom:1px dotted #AAA; }
.syndiUL > li:last-child { border-bottom:0; }
.syndiUL .blue { color:#2222FF; }
.syndiUL .gray { color:#AAA; }

.new { display:inline; padding:0 3px;border-radius:3px;font-size:8pt;background:#FF2222;color:#fff}
</style>

	<!-- 리스트 START -->

	<div style="float:right; display:inline-block; font-size:12px;line-height:20px;width:100%;">
		<span style="float:left">▣ 등록 : <?=number_format($total_count);?>명</span>
		<span style="float:left;margin-left:20px;">▣ 예치금합계 : <?=number_format($total_point);?>원</span>
		<span style="float:right"><?=$page?> / <?=$total_page?> Page<span>
	</div>
	<table class="table table-striped table-bordered table-hover" style="padding-top:0; font-size:12px;">
		<caption style="padding:0"><?=$g5['title']?> 목록</caption>
		<thead>
		<tr>
			<!--th scope="col" id="mb_list_chk">
				<label for="chkall" class="sound_only">회원 전체</label>
				<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
			</th-->
			<th scope="col" style="text-align:center;width:60px">NO.</th>
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
				<div class="td bt_line">계정등급구분</div>
				<div class="td">회원자격구분</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">가입일시</div>
				<div class="td">정보수정일</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">로그인수</div>
				<div class="td">최종로그인</div>
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">가입지</div>
				<div class="td">최종접속지</div>
			</th>
			<th scope="col" style="text-align:center;">
				가상.수취계좌
			</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">예치금</div>
				<div class="td">누적투자요약</div>
			</th>
			<th scope="col" style="text-align:center;">신디케이션</th>
			<th scope="col" style="text-align:center;">
				<div class="td bt_line">유입경로</div>
				<div class="td">PID</div>
			</th>
			<th scope="col" style="text-align:center;">EDIT</th>
		</tr>
		</thead>
		<tbody>
<?
if($num > 0) {
	for ($i=0; $i<$rcount; $i++) {

		$row = sql_fetch_array($result);

		switch($row['member_type']) {
			case '2' : $mType = "법인회원"; break;
			case '3' : $mType = "SNS회원";  break;
			default  : $mType = "개인회원"; break;
		}


		$member_qualify_title = "";
		$member_qualify_title.= ($row['member_type']=='2') ? "법인" : "";
		if($row['member_investor_type']=='1')      $member_qualify_title.= "일반";
		else if($row['member_investor_type']=='2') $member_qualify_title.= "소득적격";
		else if($row['member_investor_type']=='3') $member_qualify_title.= "전문";

		if($row['member_group']=='F')      $member_qualify_title.= "투자자";
		else if($row['member_group']=='L') $member_qualify_title.= "대출자";


		switch($row['sns_type']) {
			case 1  : $sns_type_text = ''; break;
			case 2  : $sns_type_text = '<img src="/images/naver_ico.png" class="img-circle">'; break;
			case 3  : $sns_type_text = '<img src="/images/kakao_ico.png" class="img-circle">'; break;
			case 4  : $sns_type_text = '<img src="/images/facebook_ico.png" class="img-circle">'; break;
			case 5  : $sns_type_text = '<img src="/images/google_ico.png" class="img-circle">'; break;
			default : $sns_type_text = ''; break;
		}

		$new_mark = (time()-strtotime($row['mb_datetime']) < 86400) ? '<span class="new">new</span>' : '';

		if($row['mb_level']=='0')        { $mb_level_txt = "미승인회원"; $mb_level_fcolor = 'brown'; }
		else if($row['mb_level']=='1')   { $mb_level_txt = "일반회원";   $mb_level_fcolor = ''; }
		else if($row['mb_level']=='9')   { $mb_level_txt = "부관리자";   $mb_level_fcolor = 'blue'; }
		else if($row['mb_level']=='10')  { $mb_level_txt = "관리자";     $mb_level_fcolor = 'blue'; }
		else if($row['mb_level']=='100') { $mb_level_txt = "승인거절";   $mb_level_fcolor = 'red'; }

		if($row['receive_method']=='1') {
			$receive_method = "환급계좌";
			$receive_method_fcolor = "";
		}
		else if($row['receive_method']=='2') {
			$receive_method = "예치금";
			$receive_method_fcolor = "brown";
		}
		else {
			$receive_method = "미지정";
			$receive_method_fcolor = "#DDD";
		}




		$print_syndi_info = "<ul class='syndiUL'>\n";
		if($row['finnq_userid']) {
			$fclass = ($row['finnq_rdate'] > $row['mb_datetime']) ? 'gray' : 'blue';
			$print_syndi_info.= "<li class='".$fclass."'>".$CONF['SYNDICATOR']['finnq']['name']." <span style='font-size:11px'>".substr($row['finnq_rdate'],0,16)."</span></li>\n";
		}
		if($row['wowstar_userid']) {
			$fclass = ($row['wowstar_rdate'] > $row['mb_datetime']) ? 'gray' : 'blue';
			$print_syndi_info.= "<li class='".$fclass."'>".$CONF['SYNDICATOR']['hktvwowstar']['name']." <span style='font-size:11px'>".substr($row['wowstar_rdate'],0,16)."</span></li>\n";
		}
		if($row['chosun_userid'])	{
			$fclass = ($row['chosun_rdate'] > $row['mb_datetime']) ? 'gray' : 'blue';
			$print_syndi_info.= "<li class='".$fclass."'>".$CONF['SYNDICATOR']['chosun']['name']." <span style='font-size:11px'>".substr($row['chosun_rdate'],0,16)."</span></li>\n";
		}
		if($row['tvtalk_userid']) {
			$fclass = ($row['tvtalk_rdate'] > $row['mb_datetime']) ? 'gray' : 'blue';
			$print_syndi_info.= "<li class='".$fclass."'>".$CONF['SYNDICATOR']['TvTalk']['name']." <span style='font-size:11px'>".substr($row['tvtalk_rdate'],0,16)."</span></li>\n";
		}
		$print_syndi_info.= "</ul>\n";


		$print_vi_date = "";
		if($row['keyword']) $print_vi_date.= "키워드: ".$row['keyword']." ";
		if($row['vi_date']) $print_vi_date.= "(".$row['vi_date']. " " . $row['rhour']. "시)";

		$print_referer = "";
		if($row['site_id']) $print_referer.= $row['site_id'];
		if($row['site_ca']) $print_referer.= ' '.$row['site_ca'];
		if($print_referer) {
			$print_referer_tag = '<a href="'.$row['referer'].'" title="'.$print_vi_date.'" target="_blank">'.$print_referer.'</a>';
		}


		$print_today_login = (substr($row['mb_today_login'], 0, 10)>'0000-00-00') ? substr($row['mb_today_login'], 0, 10) : '';


?>
		<tr>
			<!--td headers="mb_list_chk" class="td_chk" align="center">
				<input type="hidden" name="mb_id[<?=$i?>]" value="<?=$row['mb_id']?>" id="mb_id_<?=$i?>">
				<label for="chk_<? echo $i; ?>" class="sound_only"><? echo get_text($row['mb_name']); ?> <? echo get_text($row['mb_nick']); ?>님</label>
				<input type="checkbox" name="chk[]" value="<? echo $i ?>" id="chk_<? echo $i ?>">
			</td-->
			<td align="center">
				<span style="font-size:11px"><?=$num?></span><br>
			</td>
			<td align="center">
				<div class="td bt_line">
					<?=$new_mark?> <?=$row['mb_no']?>
				</div>
				<div class="td">
					<a href="./member_view.php?<?=$_SERVER['QUERY_STRING']?>&mb_id=<?=$row['mb_id']?>"><?=$row['mb_id']?></a>
					<?=$sns_type_text?>
				</div>
			</td>
			<td align="center">
				<div class="td bt_line"><?=$row['mb_co_name']?></div>
<?
		if($row['member_group']=='F' && $row['member_type']=='2') {
			echo "				<div class='td bt_line'>\n";
			if($row['business_license']!="")  echo "<a href='".G5_URL."/mypage/license_download.php?mb_id=".$row["mb_id"]."' style='font-size:9pt'>[사업자등록증]</a>\n";
			if($row['bankbook']!="")          echo "<a href='".G5_URL."/mypage/bankbook_download.php?mb_id=".$row["mb_id"]."' style='font-size:9pt'>[통장사본]</a>\n";
			if($row['loan_co_license']!="")   echo "<a href='".G5_URL."/mypage/loan_co_license_download.php?mb_id=".$row["mb_id"]."' style='font-size:9pt'>[대부업등록증]</a>\n";
			echo "				</div>\n";
		}
?>
				<div class="td">
					<?=$row['mb_name']?>
					<? if( preg_match("/admin/", $member['mb_id']) ) { ?><a href="javascript:;" onClick="if(confirm('<?=$row['mb_name']?> 회원에게 비상경계경보를 발령합니다.\n중대한 사안이므로 신중히 결정하십시요.\n\n진행하시겠습니까?')){ location.replace('/adm/simple_login.php?mb_no=<?=$row['mb_no']?>'); }">.</a><? } ?>
				</div>
			</td>

			<td align="center">
				<div class="td bt_line"><?=($_SESSION['ss_accounting_admin']) ? $row['mb_hp'] : '<font style="color:#ccc">열람불가</font>'; ?></div>
				<div class="td"><?=($_SESSION['ss_accounting_admin']) ? '<span style="font-size:11px">'.$row['mb_email'].'</span>' : '<font style="color:#ccc">열람불가</font>'; ?></div>
			</td>
			<td align="center">
				<div class="td bt_line"><span style="color:<?=$mb_level_fcolor?>"><?=$mb_level_txt?></span>
				<? if($row['mb_level']=='0') { ?>
				<? if($row['id_card']!=""){ echo "<a href='".G5_URL."/adm/member/idcardview.php?mb_no=".$row["mb_no"]."' target='_blank'><img src='/images/investment/icon_file2.png' width='20px' height='30px'></a>"; } ?>
				<span onClick="instantAuth('Y', '<?=$row['mb_no']?>', '<?=$row['mb_name']?>');" class="btn btn-sm btn-primary" style="width:40px;height:25px;line-height:24px;padding:0;">승인</span>
				<span onClick="instantAuth('N', '<?=$row['mb_no']?>', '<?=$row['mb_name']?>');" class="btn btn-sm btn-danger" style="width:40px;height:25px;line-height:24px;padding:0;">거절</span>
				<? } ?>
				</div>
				<div class="td"><?=$member_qualify_title?></div>
			</td>
			<td align="center">
				<div class="td bt_line"><span style="font-size:11px"><?=substr($row['mb_datetime'], 0, 16);?></span></div>
				<div class="td"><span style="font-size:11px"><?=substr($row['edit_datetime'], 0, 16);?></span></div>
			</td>
			<td align="center">
				<div class="td bt_line"><?=$row['login_cnt']?>회</div>
				<div class="td"><span style="font-size:11px"><?=$print_today_login?></span></div>
			</td>
			<td align="center">
				<div class="td bt_line"><span style="font-size:11px"><?=$row['mb_ip']?></span></div>
				<div class="td"><span style="font-size:11px"><?=$row['mb_login_ip']?></span></div>
			</td>
			<td align="center">
				<div class="td bt_line" style="text-align:left;color:<?=($row['receive_method']=='1')?'':'#ccc'?>"><span style="font-size:12px">본인: <?=$BANK[$row['bank_code']] . " " . $row['account_num'] . " " . $row['bank_private_name']?></span></div>
				<div class="td" style="text-align:left;color:<?=($row['receive_method']=='2')?'':'#ccc'?>"><span style="font-size:12px">가상: <?=$BANK[$row['va_bank_code2']] . " " . $row['virtual_account2'] . " " . $row['va_private_name2']?></span></div>
			</td>
			<td align="center">
				<div class="td bt_line" style="text-align:right"><span style="font-size:11px;cursor:pointer;" onClick="balance_check(<?=$row['mb_no']?>)"><?=number_format($row['mb_point'])?>원</span></div>
				<div class="td" style="text-align:right"><span style="font-size:11px"><? if($row['invest_count']) { ?><?=number_format($row['invest_amount'])?>원 / <? } ?><?=number_format($row['invest_count'])?>건</span></div>
			</td>

			<td align="center"><?=$print_syndi_info?></td>
			<!--<td align="center"><span style="color:<?=($row['va_bank_code'])?'':'#DDD';?>"><?=$row['rec_mb_id']?></span></td>-->
			<!--<td align="center"><a href="javascript:;" onClick="popup_window('./recommend_list.php?mb_no=<?=$row['mb_no']?>', 'new_win', 'width=680, height=610');"><span style="color:<?=($row['recommend_count'])?'':'#DDD';?>"><?=number_format($row['recommend_count'])?></span></a></td>-->

			<td align="center">
				<div class="td bt_line"><?=$print_referer_tag?></div>
				<div class="td" style="text-align:right"><span style="font-size:11px"><?=$row['pid']?></span></div>
			</td>

			<td align="center">
				<button type="button" onclick="member_modi('<?=$row['mb_id']?>');" class="btn btn-sm btn-default">수정</button>
				<? if($member['mb_level'] > 9) { ?><button type="button" onclick="member_dele('<?=$row['mb_id']?>');" class="btn btn-sm btn-danger">탈퇴</button><? } ?>
			</td>
		</tr>
<?
		$num--;
	}
}else {
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

</div>

<?
$qstr = preg_replace("/&page=([0-9]){1,10}/", "", $_SERVER['QUERY_STRING']);
echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page=');
?>

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
<? if($member['mb_level'] > 9) { ?>
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
	$('#member_list_frm').attr('method', 'post');
	$('#member_list_frm').submit();
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
</script>