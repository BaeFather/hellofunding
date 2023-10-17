<?
###############################################################################
## 투자현황
###############################################################################

include_once('./_common.php');

$sub_menu = "700700";
$g5['title'] = $menu['menu700'][8][1];
$g5['title'].= ": 내역검색";

if($display_mode=="excel") {
	header( "Content-type: application/vnd.ms-excel;" );
	header( "Content-Disposition: attachment; filename=invest_list.xls" );
	header( "Content-description: PHP5 Generated Data" );
}
else {
	include_once (G5_ADMIN_PATH.'/admin.head.php');
}

while( list($k, $v) = each($_REQUEST) ) { if(!is_array($k)) ${$k} = trim($v); }


$where = "";

if(!$date_field) {
	$date_field = 'insert_date';
	$sdate = date('Y-m') . '-01';
}

if( $date_field && ($sdate || $edate) ) {
	if($date_field=='insert_date')          $dfield = "A.insert_date";
	else if($date_field=='loan_start_date') $dfield = "B.loan_start_date";
	else if($date_field=='loan_end_date')   $dfield = "B.loan_end_date";
	else if($date_field=='mb_date')         $dfield = "STR_TO_DATE(C.mb_datetime, '%Y-%m-%d')";
//else if($date_field=='mb_date')         $dfield = "LEFT(C.mb_datetime,10)";

	if($sdate) $where.= " AND $dfield >= '$sdate'";
	if($edate) $where.= " AND $dfield <= '$edate'";
}

if($member_type)   $where.= " AND C.member_type='$member_type' ";
if($investor_type) $where.= " AND C.member_investor_type='$investor_type' ";
if($is_creditor)   $where.= " AND C.is_creditor='$is_creditor' ";
if($is_invest_manager) $where.= " AND C.is_invest_manager='$is_invest_manager' ";

if($iv_type) {
	if($iv_type=='static') $where.= " AND ((SELECT COUNT(idx) FROM cf_product_invest_detail WHERE invest_idx=A.idx AND is_auto_invest='') > 0)";
	if($iv_type=='auto')   $where.= " AND ((SELECT COUNT(idx) FROM cf_product_invest_detail WHERE invest_idx=A.idx AND is_auto_invest='1') > 0)";
}

if($iv_state) {
	if($iv_state=='N_byUser')         $where.= " AND A.invest_state='N' AND A.cancel_by='user'";
	else if($iv_state=='N_byUserApp') $where.= " AND A.invest_state='N' AND A.cancel_by='user-api'";
	else if($iv_state=='N_bySystem')  $where.= " AND A.invest_state='N' AND A.cancel_by='system'";
	else if($iv_state=='N_byAdmin')   $where.= " AND A.invest_state='N' AND A.cancel_by='admin'";
	else                              $where.= " AND A.invest_state='".$iv_state."'";
}

if($first_inv=='Y') $where.= " AND A.first_inv='Y'";
if($product_idx) $where.= " AND B.idx='".$product_idx."'";

if($search_state) {
	if($search_state=='recruiting.recruitend.1.8') $where.= " AND A.invest_state='Y' AND B.state IN('','1','8')";
	else if($search_state=='recruiting')           $where.= " AND A.invest_state='Y' AND B.state='' AND B.invest_end_date=''";
	else if($search_state=='recruitend')           $where.= " AND A.invest_state='Y' AND B.state='' AND B.invest_end_date!=''";
	else if($search_state=='2.5')                  $where.= " AND B.state IN('2','5')";
	else if($search_state=='6.7')                  $where.= " AND B.state IN('6','7')";
	else                                           $where.= " AND B.state='".$search_state."'";
}

if($ai_grp_idx) $where.= " AND B.ai_grp_idx='".$ai_grp_idx."'";
if($samount) $where.= " AND A.amount >= '$samount'";
if($eamount) $where.= " AND A.amount <= '$eamount'";
if($platform) {
	$where.= ($platform=='null') ? " AND A.syndi_id=''" : " AND A.syndi_id='".$platform."'";
}
if($category) {
	if( in_array($category, array('1','2','3')) ) {
		$where.= " AND B.category='".$category."'";
	}
	else {
		if($category=='2A') $where.= " AND B.category='2' AND B.mortgage_guarantees=''";
		if($category=='2B') $where.= " AND B.category='2' AND B.mortgage_guarantees='1'";
		if($category=='3A') $where.= " AND B.category='3' AND B.category2='1'";
		if($category=='3B') $where.= " AND B.category='3' AND B.category2='2'";
	}
}

if($pfield && $pkeyword) {
	if( in_array($pfield, array('B.idx','B.start_num')) ) {
		$where.= ( preg_match("/\,/", $pkeyword) ) ? " AND $pfield IN(".$pkeyword.")" : " AND $pfield='".$pkeyword."'";
	}
	else if($pfield=='B.title') {
		$where.= " AND $pfield LIKE '%".$pkeyword."%'";
	}
	else {
		$where.= " AND $pfield='".$pkeyword."'";
	}
}

if($field && $keyword) {
	if( in_array($field, array('A.idx','C.mb_no')) ) {
		$where.= ( preg_match("/\,/", $keyword) ) ? " AND $field IN(".$keyword.")" : " AND $field='".$keyword."'";
	}
	else if($field=='mb_name') {
		$where.= " AND (mb_name LIKE '%".$keyword."%' OR mb_co_name LIKE '%".$keyword."%')";
	}
	else if($field=='mb_hp') {
		$where.= " AND C.mb_hp='".masterEncrypt($keyword, false)."'";
	}
	else {
		$where.= " AND $field='".$keyword."'";
	}
}

$sql_order = "";
if($sort_field) {
	$sql_order.= $sort_field." ".$sort;
	if($sort_field!='A.idx' && $sort_field!='A.insert_datetime') {
		$sql_order.= ", invest_idx DESC ";
	}
}
else {
	$sql_order.= " invest_idx DESC ";
}

$sql = "
	SELECT
		COUNT(A.idx) AS cnt,
		IFNULL(SUM(amount),0) AS amount
	FROM
		cf_product_invest A
	LEFT JOIN
		cf_product B  ON A.product_idx=B.idx
	LEFT JOIN
		g5_member C  ON A.member_idx=C.mb_no
	WHERE 1
		$where";
$row = sql_fetch($sql);
$total_count  = $row['cnt'];
$total_amount = $row['amount'];

$page_rows = 20;
$total_page  = ceil($total_count / $page_rows);
if($page < 1) $page = 1;
$from_record = ($page - 1) * $page_rows;
$num = $total_count - $from_record;


// 투자내역 검색
$sql = "
	SELECT
		A.idx AS invest_idx,
		A.amount, A.invest_state, A.insert_datetime, A.cancel_date, A.cancel_by, A.cancel_date, A.is_return, A.syndi_id, A.syndi_invest_idx, A.first_inv, A.memo,
		A.product_idx, A.member_idx, A.investment_register_id, A.contract_id,
		(SELECT COUNT(idx) FROM cf_product_invest_detail WHERE invest_idx=A.idx AND is_auto_invest='') AS static_invest_count,
		(SELECT COUNT(idx) FROM cf_product_invest_detail WHERE invest_idx=A.idx AND is_auto_invest='1') AS auto_invest_count,

		B.gr_idx, B.ai_grp_idx, B.state, B.category, B.mortgage_guarantees, B.title, B.recruit_amount, B.invest_period, B.invest_days, B.invest_return,
		B.end_datetime, B.invest_end_date, B.loan_start_date, B.loan_end_date,

		C.mb_id, C.member_type, C.member_investor_type, C.is_creditor, C.mb_point, C.mb_name, C.mb_co_name, C.corp_num, C.mb_hp, C.mb_birth, C.mb_sex
	FROM
		cf_product_invest A
	LEFT JOIN
		cf_product B  ON A.product_idx=B.idx
	LEFT JOIN
		g5_member C  ON A.member_idx=C.mb_no
	WHERE 1
		$where
	ORDER BY
		$sql_order";
if ($display_mode<>"excel") $sql=$sql."	LIMIT $from_record, $page_rows";
//print_rr($sql);

$res = sql_query($sql);
$rows = $res->num_rows;
for($i=0; $i<$rows; $i++) {
	$LIST[] = sql_fetch_array($res);
}
sql_free_result($res);

$list_count = count($LIST);

//print_rr($LIST, 'font-size:12px');

$res2 = sql_query("
	SELECT
		idx, state, category, mortgage_guarantees, title, start_date, end_date, end_datetime, loan_start_date, loan_end_date, invest_end_date
	FROM
		cf_product
	WHERE (1)
		AND state IN('','1')
		AND display='Y' AND isTest=''
		AND start_datetime >= '0000-00-00 00:00:00'
	ORDER BY
		start_num DESC");
$rows2 = $res2->num_rows;
for($i=0; $i<$rows2; $i++) {
	$PLIST[] = sql_fetch_array($res2);
}
$plist_count = count($PLIST);

$res3 = sql_query("
	SELECT
		idx, grp_title
	FROM
		cf_auto_invest_config
	ORDER BY
		idx DESC");
$rows3 = $res3->num_rows;
for($i=0; $i<$rows3; $i++) {
	$CLIST[] = sql_fetch_array($res3);
}
$clist_count = count($CLIST);

//if($member['mb_id']=='admin_sori9th') print_rr($PLIST);

?>

<? if($display_mode!="excel") { ?>

<style>
div.overhidden {width:100%;height:16px;text-align:center;line-height:16px;overflow:hidden;}
.cancel_row {color:#FF8888;}
</style>

<div class="tbl_head02 tbl_wrap">

	<? if ($display_mode<>"excel") { ?>
	<!-- 검색영역 START -->
	<div style="line-height:28px;">
		<form id="frmSearch" name= "frmSearch" method="get" class="form-horizontal">
		<ul class="col col-md-* list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>
				<select name="date_field" class="form-control input-sm">
					<option value="">::날짜선택::</option>
					<option value="insert_date" <?=($date_field=='insert_date')?'selected':'';?>>투자일</option>
					<option value="loan_start_date" <?=($date_field=='loan_start_date')?'selected':'';?>>대출실행일</option>
					<option value="loan_end_date" <?=($date_field=='loan_end_date')?'selected':'';?>>대출종료일</option>
					<option value="mb_date" <?=($date_field=='mb_date')?'selected':'';?>>회원가입일</option>
				</select>
			</li>
			<li><input type="text" id="sdate" name="sdate" value="<?=$sdate?>" readonly class="form-control input-sm datepicker" placeholder="대상일자(시작)"></li>
			<li>~</li>
			<li><input type="text" id="edate" name="edate" value="<?=$edate?>" readonly class="form-control input-sm datepicker" placeholder="대상일자(종료)"></li>
			<li></li>
			<li>
				<select name="iv_state" class="form-control input-sm">
					<option value="">::투자상태 선택::</option>
					<option value="Y" <?=($iv_state=='Y')?'selected':'';?>>정상</option>
					<option value="N" <?=($iv_state=='N')?'selected':'';?>>취소전체</option>
					<option value="N_byUser" <?=($iv_state=='N_byUser')?'selected':'';?>>- 사용자 취소: 웹</option>
					<option value="N_byUserApp" <?=($iv_state=='N_byUserApp')?'selected':'';?>>- 사용자 취소: 앱</option>
					<option value="N_bySystem" <?=($iv_state=='N_bySystem')?'selected':'';?>>- 시스템 취소: 입금지연</option>
					<option value="N_byAdmin" <?=($iv_state=='N_byAdmin')?'selected':'';?>>- 관리자 취소</option>
					<option value="R" <?=($iv_state=='R')?'selected':'';?>>반환</option>
				</select>
			</li>
			<li>
				<select name="iv_type" class="form-control input-sm">
					<option value="">::투자 유형 선택::</option>
					<option value="static" <?=($iv_type=='static')?'selected':'';?>>일반투자</option>
					<option value="auto" <?=($iv_type=='auto')?'selected':'';?>>자동투자</option>
				</select>
			</li>
			<li><label class="checkbox-inline"><input type="checkbox" name="first_inv" value="Y" <?=($first_inv=='Y')?'checked':''?>>최초투자</label></li>
		</ul>
		<ul class="col col-md-* list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>투자금액</li>
			<li><input type="text" id="samount" name="samount" value="<?=$samount?>" onKeyUP="onlyDigit(this);" class="form-control input-sm"></li>
			<li>~</li>
			<li><input type="text" id="eamount" name="eamount" value="<?=$eamount?>" onKeyUP="onlyDigit(this);" class="form-control input-sm"></li>
			<li>
				<select name="platform" class="form-control input-sm">
					<option value="">::투자플랫폼::</option>
					<option value="null" <?if($platform=='null'){ echo "selected";} ?>>헬로펀딩</option>
<?
	$scount = count($CONF['SYNDICATOR']);
	$skey = array_keys($CONF['SYNDICATOR']);
	for($i=0; $i<$scount; $i++) {
		$selected = ($skey[$i]== $platform) ? "selected" : "";
		echo "					<option value='".$skey[$i]."' $selected>".$CONF['SYNDICATOR'][$skey[$i]]['name']."</option>\n";
	}
?>
				</select>
			</li>
		</ul>
		<ul class="col col-md-* list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>
				<select name="category" class="form-control input-sm">
					<option value="">::상품카테고리::</option>
					<option value="2" <?if($category=='2')echo"SELECTED";?>>부동산</option>
					<option value="2A" <?if($category=='2A')echo"SELECTED";?>>-부동산(PF)</option>
					<option value="2B" <?if($category=='2B')echo"SELECTED";?>>-부동산(주택담보)</option>
					<option value="3" <?if($category=='3')echo"SELECTED";?>>헬로페이</option>
					<option value="3A" <?if($category=='3A')echo"SELECTED";?>>-헬로페이(소상공인)</option>
					<option value="3B" <?if($category=='3B')echo"SELECTED";?>>-헬로페이(면세점)</option>
					<option value="1" <?if($category=='1')echo"SELECTED";?>>동산</option>
				</select>
			</li>
			<li>
				<select name="ai_grp_idx" class="form-control input-sm">
					<option value="">::자동투자그룹::</option>
<?
for($i=0; $i<$clist_count; $i++) {
	$selected = ($CLIST[$i]['idx']==$ai_grp_idx) ? "selected" : "";
	echo "					<option value='".$CLIST[$i]['idx']."' $selected>".$CLIST[$i]['grp_title']."</option>\n";
}
?>
				</select>
			</li>
			<li>
				<select name="product_idx" class="form-control input-sm" style="width:200px">
					<option value="">::상품선택 (모집중,상환중)::</option>
<?
for($i=0; $i<$plist_count; $i++) {
	$selected = ($PLIST[$i]['idx']==$product_idx) ? "selected" : "";

	if($PLIST[$i]['state']) {
		$p_state = "상환중";
	}
	else {
		if($PLIST[$i]['invest_end_date']=='') {
			$p_state = ( $PLIST[$i]['end_datetime'] >= date("Y-m-d") ) ? "모집중" : "모집실패";
		}
		else {
			$p_state = "모집완료";
		}
	}

	$print_title = "";
	if($PLIST[$i]['state']) {
		$print_title = $PLIST[$i]['title']." ($p_state / 투자기간: ".preg_replace("/-/",".",$PLIST[$i]['loan_start_date']) . "~" . preg_replace("/-/",".",$PLIST[$i]['loan_end_date']).")";
	}
	else {
		$print_title = $PLIST[$i]['title']." ($p_state / 모집기간: ".preg_replace("/-/",".",$PLIST[$i]['start_date']) . "~" . preg_replace("/-/",".",$PLIST[$i]['end_date']).")";
	}
	echo "					<option value='".$PLIST[$i]['idx']."' $selected>".$print_title."</option>\n";
}
?>
				</select>
			</li>
			<li>
				<select name="search_state" class="form-control input-sm" style="width:200px">
					<option value="">::투자진행현황선택::</option>
					<option value="recruiting.recruitend.1.8" <?=($search_state=='recruiting.recruitend.1.8')?'selected':'';?>>상환중전체</option>
					<option value="recruiting" <?=($search_state=='recruiting')?'selected':'';?>> - 모집중</option>
					<option value="recruitend" <?=($search_state=='recruitend')?'selected':'';?>> - 모집완료(기표전)</option>
					<option value="1" <?=($search_state=='1')?'selected':'';?>> - 상환중</option>
					<option value="8" <?=($search_state=='8')?'selected':'';?>> - 상환지연,연체</option>
					<option value="2.5" <?=($search_state=='2.5')?'selected':'';?>>상환완료전체</option>
					<option value="2" <?=($search_state=='2')?'selected':'';?>> - 정상상환</option>
					<option value="5" <?=($search_state=='5')?'selected':'';?>> - 중도상환</option>
					<option value="3" <?=($search_state=='3')?'selected':'';?>>투자금모집실패</option>
					<option value="6.7" <?=($search_state=='6.7')?'selected':'';?>>대출취소전체</option>
					<option value="6" <?=($search_state=='6')?'selected':'';?>> - 기표전취소</option>
					<option value="7" <?=($search_state=='7')?'selected':'';?>> - 기표후취소</option>
					<option value="4" <?=($search_state=='4')?'selected':'';?>>부실</option>
				</select>
			</li>
			<li></li>
			<li>
				<select name="pfield" class="form-control input-sm">
					<option value="">::상품필드선택::</option>
					<option value="B.idx" <?=($pfield=='B.idx')?'selected':''?>>품번</option>
					<option value="B.start_num" <?=($pfield=='B.start_num')?'selected':''?>>호번</option>
					<option value="B.title" <?=($pfield=='B.title')?'selected':''?>>상품명</option>
				</select>
			</li>
			<li><input type="text" name="pkeyword" value="<?=$pkeyword?>" class="form-control input-sm" style="width:250px" placeholder="상품필드 검색어"></li>
		</ul>
		<ul class="col col-md-* list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>
				<select name="member_type" class="form-control input-sm">
					<option value="">::회원구분::</option>
					<option value="1" <?=($member_type=='1')?'selected':'';?>>개인회원</option>
					<option value="2" <?=($member_type=='2')?'selected':'';?>>법인회원</option>
				</select>
			</li>
			<li>
				<select name="investor_type" class="form-control input-sm">
					<option value="">::투자자 유형 선택::</option>
					<option value="1" <?=($investor_type=='1')?'selected':'';?>>- 일반투자자</option>
					<option value="2" <?=($investor_type=='2')?'selected':'';?>>- 소득적격투자자</option>
					<option value="3" <?=($investor_type=='3')?'selected':'';?>>- 전문투자자</option>
				</select>
			</li>
			<li><label class="checkbox-inline"><input type="checkbox" name="is_creditor" value="Y" <?=($is_creditor)?'checked':''?>>대부업</label></li>
			<li><label class="checkbox-inline"><input type="checkbox" name="is_invest_manager" value="1" <?=($is_invest_manager)?'checked':''?>>자산운용사</label></li>
			<li></li>
			<li>
				<select id="field" name="field" class="form-control input-sm" style="width:150px">
					<option value="">::회원필드선택::</option>
					<option value="A.idx" <? if($field=='A.idx') { echo "SELECTED"; } ?>>투자번호</option>
					<option value="C.mb_no" <? if($field=='C.mb_no') { echo "SELECTED"; } ?>>회원번호</option>
					<option value="C.mb_id" <? if($field=='C.mb_id') { echo "SELECTED"; } ?>>아이디</option>
					<option value="mb_name" <? if($field=='mb_name') { echo "SELECTED"; } ?>>성명.상호명</option>
					<option value="C.mb_co_reg_num" <? if($field=='C.mb_co_reg_num') { echo "SELECTED"; } ?>>사업자번호</option>
					<option value="mb_hp" <? if($field=='mb_hp') { echo "SELECTED"; } ?>>휴대폰</option>
					<option value="C.virtual_account2" <? if($field=='C.virtual_account2') { echo "SELECTED"; } ?>>가상계좌번호(신한)</option>
					<option value="C.account_num" <? if($field=='C.account_num') { echo "SELECTED"; } ?>>환급계좌번호</option>
				</select>
			</li>
			<li><input type="text" id="keyword" name="keyword" value="<?=$keyword?>" class="form-control input-sm" style="width:250px" placeholder="회원필드 검색어"></li>
			<li></li>
			<li><button type="submit" class="btn btn-sm btn-warning">검색</button></li>
			<li><button type="button" class="btn btn-sm btn-default" onClick="location.href='<?=$_SERVER['PHP_SELF']?>';">초기화</button></li>
			<li>
				<button type="button" onclick="go_excel();" class="btn btn-sm btn-success" style="width:150px">검색결과 다운로드</button>
				<button type="button" onclick="if(confirm('순수 핀크 투자 및 수수료 현황 시트를 다운로드 합니다.\n - <?=date("Y년m월", strtotime("first day of -1 month"))?> 기준')){ location.href='/adm/etc/finnq_invest_status.php'; }" class="btn btn-sm btn-success" style="width:160px">핀크수수료내역 다운로드</button>
				<input type=hidden name="display_mode" value=""/>
			</li>
		</ul>
		<!-- sort 선택 -->
		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>
				<select id="sort_field" class="form-control input-sm" style="width:150px;">
					<option value="">정렬필드</option>
					<option value="A.idx" <? if($sort_field=='A.idx'){echo 'selected';} ?>>투자번호</option>
					<option value="A.insert_datetime" <? if($sort_field=='A.insert_datetime'){echo 'selected';} ?>>투자일시</option>
					<option value="A.amount" <? if($sort_field=='A.amount'){echo 'selected';} ?>>투자금액</option>
					<option value="B.idx" <? if($sort_field=='B.idx'){echo 'selected';} ?>>품번</option>
					<option value="B.start_num" <? if($sort_field=='B.start_num'){echo 'selected';} ?>>호번</option>
					<option value="B.loan_start_date" <? if($sort_field=='B.loan_start_date'){echo 'selected';} ?>>대출실행일</option>
				</select>
			</li>
			<li>
				<button type="button" onClick="sortList('DESC');" class="btn btn-sm btn-<?=($sort=='DESC')?'info':'default';?>">내림차순</button>
				<button type="button" onClick="sortList('ASC');" class="btn btn-sm btn-<?=($sort=='ASC')?'info':'default';?>">오름차순</button>
			</li>
		</ul>
		<!-- sort 선택 END -->
	</div>

<?
$sqstr = $_SERVER['QUERY_STRING'];
$sort_field_value = str_f6($sqstr, "&sort_field=", "&");
$sqstr = preg_replace("/\&sort_field\=$sort_field_value/", "", $sqstr);
$sqstr = preg_replace("/\&sort\=ASC|\&sort\=DESC/i", "", $sqstr);
//echo $sqstr;
?>
	<script>
	function sortList(param) {
		if(document.getElementById('sort_field').value!='') {
			url = '/adm/repayment/invest_list.php'
					+ '?<?=$sqstr?>'
					+ '&sort_field=' + $('#sort_field').val()
					+ '&sort=' + param
			location.href= url;
		}
		else {
			alert('정렬필드를 선택하십시요.'); document.getElementById('sort_field').focus();
		}
	}
	</script>
	<!-- 검색영역 E N D -->
	<? } ?>

	<div style="width:100%;height:100%;padding:0">

<? } /* end if($display_mode!="excel") */ ?>

		<table id="dataList" class="table table-striped table-bordered table-hover" style="font-size:12px;" <?=($display_mode=="excel")?'border=1':'';?>>
			<thead style="font-size:13px">
			<tr align="center">
				<th style="background:#F8F8EF">NO</th>
				<th style="background:#F8F8EF">투자번호</th>
				<th style="background:#F8F8EF">회원번호</th>
				<th style="background:#F8F8EF">회원구분</th>
				<th style="background:#F8F8EF">투자자유형</th>
				<th style="background:#F8F8EF">아이디</th>
				<th style="background:#F8F8EF">성명.법인명</th>
				<? if($display_mode == "excel") { ?>
				<th style="background:#F8F8EF">실명번호</th>
				<th style="background:#F8F8EF">성별</th>
				<? } ?>
				<th style="background:#F8F8EF">상품번호</th>
				<th style="background:#F8F8EF">상품구분</th>
				<th style="background:#F8F8EF">상품명</th>
				<th style="background:#F8F8EF">대출기간</th>
				<th style="background:#F8F8EF">대출금리</th>
				<th style="background:#F8F8EF">상품현황</th>
				<th style="background:#F8F8EF">투자금액</th>
				<th style="background:#F8F8EF">상세투자수</th>
				<th style="background:#F8F8EF">상태</th>
				<th style="background:#F8F8EF">플랫폼</th>
				<th style="background:#F8F8EF">투자일시</th>
				<th style="background:#F8F8EF">취소일시</th>
				<th style="background:#F8F8EF">중앙기록관리</th>
			</tr>
			</thead>
			<tr>
				<td style="background:#EEEEFF;color:brown;" align="center">합계</td>
				<td style="background:#EEEEFF;color:brown;" colspan="13" align="right"><?=number_format($total_count)?>건 / <?=number_format($total_amount)?>원 </td>
				<td style="background:#EEEEFF;color:brown;" colspan="6"></td>
			</tr>
<?
for($i=0,$j=$num; $i<$list_count; $i++,$j--) {

	if($LIST[$i]['state']) {
		switch($LIST[$i]['state']) {
			case '1': $state='상환중'; break;
			case '2': $state='정상상환'; break;
			case '3': $state='모집실패'; break;
			case '4': $state='부실'; break;
			case '5': $state='중도상환'; break;
			case '6': $state='대출취소(기표전)'; break;
			case '7': $state='대출취소(기표후)'; break;
			case '8': $state='연체'; break;
			case '9': $state='부도(상환불가)'; break;
		}
	}
	else {
		if($LIST[$i]['invest_end_date']=='') {
			$state = ( $LIST[$i]['end_datetime'] >= date("Y-m-d") ) ? "모집중" : "모집실패";
		}
		else {
			$state = "모집완료";
		}
	}

	if($LIST[$i]['category'] == '2' && $LIST[$i]['mortgage_guarantees'] == '') {
		$product_category = "PF";
	} else if($LIST[$i]['category'] == '2' && $LIST[$i]['mortgage_guarantees'] == '1') {
		$product_category = "주택담보대출";
	} else if($LIST[$i]['category'] == '3') {
		$product_category = "매출채권";
	} else if($LIST[$i]['category'] == '1') {
		$product_category = "동산";
	}

	if($LIST[$i]['invest_state']=='Y') {
		$style_class = "";
		$invest_state = "정상";
	}
	else {
		$style_class = "cancel_row";
		if($LIST[$i]['invest_state']=='R') {
			$invest_state = "반환";
		}
		else {
			if($LIST[$i]['cancel_by']=='user')          $invest_state = "사용자 취소: 웹";
			else if($LIST[$i]['cancel_by']=='user-api') $invest_state = "사용자 취소: 앱";
			else if($LIST[$i]['cancel_by']=='system')   $invest_state = "시스템 취소: 입금지연";
			else if($LIST[$i]['cancel_by']=='admin')    $invest_state = "관리자 취소";
		}
	}

	if($LIST[$i]['mb_id']) {
		$print_member_type = ($LIST[$i]['member_type']=='2') ? '법인' : '개인';
		$print_member_type2 = "";

		if($LIST[$i]['member_type']=='2') {
			$print_name = $LIST[$i]['mb_co_name'];
			$type_num = $LIST[$i]['corp_num'];
			//$mb_sex = $LIST[$i]['mb_sex'];
		}
		else {
			$print_name = ($_SESSION['ss_accounting_admin']) ? $LIST[$i]['mb_name'] : hanStrMasking($LIST[$i]['mb_name']);
			$type_num_ori = $LIST[$i]['mb_birth'];
			$type_num = substr(str_replace('-', '', $type_num_ori), 2);

			if ($LIST[$i]['mb_sex'] == 'm') {
				$mb_sex = '남';
			} else if($LIST[$i]['mb_sex'] == 'w') {
				$mb_sex = '여';
			}


			if ($LIST[$i]['member_investor_type']=="1") $print_member_type2="일반투자자";
			else if ($LIST[$i]['member_investor_type']=="2") $print_member_type2="소득적격투자자";
			else if ($LIST[$i]['member_investor_type']=="3") $print_member_type2="전문투자자";

		}
	}
	else {
		$print_name = $print_member_type = "불명";
	}

	if(in_array($LIST[$i]['state'], array('1','2','5'))) {
		$invest_period = preg_replace("/-/", ".", $LIST[$i]['loan_start_date']) . " ~ " . preg_replace("/-/", ".", $LIST[$i]['loan_end_date']);
	}
	else {
		$invest_period = "";
	}

	$invest_count = $LIST[$i]['static_invest_count'] + $LIST[$i]['auto_invest_count'];

	$print_syndication = ($LIST[$i]['syndi_id']) ? $CONF['SYNDICATOR'][$LIST[$i]['syndi_id']]['name'] : '<span style="color:#BBB">헬로펀딩</span>';


	if($display_mode<>"excel") {
		$print_amount = "<a href=\"javascript:;\" onClick=\"balance_check(".$LIST[$i]['member_idx'].");\" style=\"color:blue\">" . number_format($LIST[$i]['amount']) . "원</a>";
	}
	else {
		$print_amount = number_format($LIST[$i]['amount']);
	}



?>

			<tr id="<?=$j?>" align="center" class="<?=$style_class?>">
				<td><?=$j?></td>
				<td><?=$LIST[$i]['invest_idx']?></td>
				<td><?=$LIST[$i]['member_idx']?></td>
				<td><?=$print_member_type?></td>
				<td><?=$print_member_type2?></td> <!-- 투자자유형 -->
				<td><div class="overhidden"><?=$LIST[$i]['mb_id']?></div></td>
				<td><div class="overhidden"><?=$print_name?></div></td>
				<? if($display_mode == "excel") { ?>
				<td style="mso-number-format:'\@'"><?=$type_num?></td> <!-- 실명번호 -->
				<td><?=$mb_sex?></td> <!-- 성별 -->
				<? } ?>
				<td><?=$LIST[$i]['product_idx']?></td>
				<td><?=$product_category?></td> <!-- 상품구분 -->
				<td><div class="overhidden" style="height:32px;text-align:left;"><?=$LIST[$i]['title']?></div></td>
				<td align='center'><?=$invest_period?></td>
				<td align='center'><?=$LIST[$i]['invest_return']?>%</td>
				<td align='center'><?=$state?></td>
				<td align='right'><?=$print_amount?></td>
				<td>
					<? if($LIST[$i]['auto_invest_count']>0) { ?>자동:<?=$LIST[$i]['auto_invest_count']?><? } ?>
					<? if($LIST[$i]['static_invest_count']>0) { ?>일반:<?=$LIST[$i]['static_invest_count']?><? } ?>
				</td>
				<td><?=$invest_state?></td>
				<td><?=$print_syndication?></td>
				<td><?=$LIST[$i]['insert_datetime']?></td>
				<td><?=($LIST[$i]['cancel_date'] > '0000-00-00 00:00:00') ? $LIST[$i]['cancel_date'] : ""; ?></td>
				<td><?=$LIST[$i]['contract_id']?$LIST[$i]['contract_id']:$LIST[$i]['investment_register_id']?>
				</td>
			</tr>
<?
	unset($LIST[$i]);
}
?>
		</table>

<? if($display_mode!="excel") { ?>
		<div id="paging_span" style="width:100%; margin:-10px 0 10px; text-align:center;"><? paging($total_count, $page, $page_rows, 10); ?></div>
	</div>

</div>
<? } ?>

<?
if($display_mode!="excel") {

	$qstr = preg_replace("/&page=([0-9]){1,10}/", "", $_SERVER['QUERY_STRING']);

?>

<script type="text/javascript">
$(document).on('click', '#paging_span span.btn_paging', function() {
		var url = '<?=$_SERVER['PHP_SELF']?>'
		        + '?<?=$qstr?>&page=' + $(this).attr('data-page');
		$(location).attr('href', url);
});

function go_excel() {
	var f = document.frmSearch;
	f.display_mode.value = "excel";
	f.submit();
	f.display_mode.value = "";
}

$(document).ready(function() {
	$('#dataList').floatThead();
});
</script>

<?

	include_once ('../admin.tail.php');

}

?>