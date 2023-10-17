<?
###############################################################################
##  원리금 지급내역
###############################################################################

include_once('./_common.php');

$sub_menu = "700310";
$g5['title'] = $menu['menu700'][4][1];

foreach($_REQUEST as $k=>$v) { ${$_REQUEST[$k]} = trim($v); }

if ($display_mode=="excel") {
	header( "Content-type: application/vnd.ms-excel;" );
	header( "Content-Disposition: attachment; filename=repay_log.xls" );
	header( "Content-description: PHP5 Generated Data" );
} else include_once (G5_ADMIN_PATH.'/admin.head.php');

if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');


if(!$date_field && !$sdate && !$edate) {
	$date_field = 'A.banking_date';
	$sdate = $edate = date('Y-m-d');
}

$datetime_s = $sdate . ' 00:00:00';
$datetime_e = $edate . ' 23:59:59';


$where = "";
if($date_field) {
	if($date_field=='A.banking_date') {
		if($sdate && $edate) {
			$where.= " AND A.banking_date BETWEEN '".$datetime_s."' AND '".$datetime_e."'";
		}
		else {
			if($sdate) $where.= " AND A.banking_date >= '".$datetime_s."'";
			if($edate) $where.= " AND A.banking_date <= '".$datetime_e."'";
		}
	}
	else {
		if($sdate) $where.= " AND {$date_field} >= '".$sdate."'";
		if($edate) $where.= " AND {$date_field} <= '".$edate."'";
	}
}
if($category) {
	if($category=='2A') {
		$where.= " AND C.category = '2' AND C.mortgage_guarantees != '1'";
	}
	else if($category=='2B') {
		$where.= " AND C.category = '2' AND C.mortgage_guarantees = '1'";
	}
	else {
		$where.= " AND C.category='".$category."'";
	}
}
if($state) {
	$where.= " AND C.state='".$state."'";
}
if($product_field && $product_kwd) {
	if( in_array($product_field, array('C.idx','C.start_num')) ) {
		$where = ( preg_match("/\,/", $product_kwd) ) ?  " AND C.idx IN($product_kwd)" : " AND C.idx = '$product_kwd'";
	}
	else if($product_field=='C.title') {
		$where.= " AND C.title LIKE '%".$product_kwd."%'";
	}
	else {
		$where.= " AND $product_field = '".$product_kwd."'";
	}
}
if($investor_field && $investor_kwd) {
	if($investor_field=='investor_title') {
		$where.= " AND (B.mb_name = '".$investor_kwd."' OR B.mb_co_name = '".$investor_kwd."')";
	}
	else {
		if($investor_field=='B.mb_no') {
			if( $investor_field == 'B.mb_no' && preg_match("/\,/", $investor_kwd) ) {
				$where.= " AND $investor_field IN(".preg_replace("/( )/", "", $investor_kwd).") ";
			}
			else {
				$where.= " AND $investor_field='$investor_kwd' ";
			}
		}
		/*
		else if($investor_field=='B.mb_id') {
			$where.= " AND $investor_field LIKE '%".$investor_kwd."%'";
		}
		*/
		else {
			$where.= " AND $investor_field = '".$investor_kwd."'";
		}
	}
}

if($turn) {
	$where.= " AND A.turn = '".$turn."'";
}

if($special_turn) {
	if($special_turn=='principal') {
		$where.= " AND A.principal > 0";
	}
	else if($special_turn=='overdue_y') {
		$where.= " AND A.is_overdue = 'Y'";
	}
	else if($special_turn=='overdue_n') {
		$where.= " AND A.is_overdue != 'Y'";
	}
}

if($give_rcv_method) {
	$where.= " AND A.receive_method = '".$give_rcv_method."'";
}

if($give_field && $give_kwd) {
	$where.= " AND $give_field = '".$give_kwd."'";
}

if($member_type) {
	if($member_type=='1A') {
		$where.= " AND B.member_type = '1' AND (B.is_owner_operator = '1' AND B.mb_co_reg_num != '')";
	}
	else if($member_type=='1B') {
		$where.= " AND B.member_type = '1' AND B.is_creditor = 'Y'";
	}
	else if($member_type=='2A') {
		$where.= " AND B.member_type = '2' AND B.is_creditor = 'Y'";
	}
	else {
		$where.= " AND B.member_type='".$member_type."'";
	}
}

if($bill_result) {
	$where.=  ($bill_result=='ok') ? " AND A.mgtKey!=''" : " AND A.mgtKey=''";
}



$sql = "
	SELECT
		COUNT(A.idx) AS cnt,
		IFNULL(SUM(A.principal),0) AS principal,
		IFNULL(SUM(A.interest),0) AS interest,
		IFNULL(SUM(A.interest_tax),0) AS interest_tax,
		IFNULL(SUM(A.local_tax),0) AS local_tax,
		IFNULL(SUM(A.fee),0) AS fee
	FROM
		cf_product_give A
	LEFT JOIN
		g5_member B  ON A.member_idx=B.mb_no
	LEFT JOIN
		cf_product C  ON A.product_idx=C.idx
	WHERE (1)
		$where";
//print_rr($sql,'font-size:12px');
$row = sql_fetch($sql);
$total_count     = $row['cnt'];
$total_principal = $row['principal'];
$total_interest  = $row['interest'];
$total_repay_amount = $row['principal'] + $row['interest'];
$total_tax       = $row['interest_tax'] + $row['local_tax'];
$total_fee       = $row['fee'];


$page_rows = 50;
$total_page  = ceil($total_count / $page_rows);
if($page < 1) $page = 1;
$from_record = ($page - 1) * $page_rows;
$num = $total_count - $from_record;

$sql = "
	SELECT
		A.idx, A.member_idx, A.is_creditor, A.product_idx, A.invest_idx, A.turn, A.is_overdue,
		A.principal, A.interest, A.interest_tax, A.local_tax, A.fee, A.mgtKey, A.receive_method, A.bank_name, A.account_num, A.bank_private_name, A.GUAR_SEQ, A.banking_date,
		B.mb_id, B.mb_co_name, B.mb_name, B.mb_hp, B.mb_email, B.member_type, B.is_owner_operator, B.mb_co_reg_num,
		C.start_num,C.title,C.loan_mb_no
	FROM
		cf_product_give A
	LEFT JOIN
		g5_member B  ON A.member_idx=B.mb_no
	LEFT JOIN
		cf_product C  ON A.product_idx=C.idx
	WHERE (1)
		$where
	ORDER BY
		A.idx DESC";
if($display_mode<>"excel") $sql=$sql."	LIMIT $from_record, $page_rows";
if($mode=='debug') { print_rr($sql,'font-size:12px'); }
$res  = sql_query($sql);
$rows = sql_num_rows($res);

$LIST = array();
for($i=0; $i<$rows; $i++) {
	$LIST[$i] = sql_fetch_array($res);
	$LIST[$i]['repay_amount'] = $LIST[$i]['principal'] + $LIST[$i]['interest'];
}
sql_free_result($res);

$list_count = count($LIST);

?>

<style>
div.overhidden {width:100%;height:16px;text-align:center;line-height:16px;overflow:hidden;}
</style>

<div class="tbl_head02 tbl_wrap">

	<? if ($display_mode<>"excel") { ?>
	<!-- 검색영역 START -->
	<div style="line-height:28px;">
		<form id="frmSearch" name= "frmSearch" method="get" class="form-horizontal">
		<ul class="col col-md-* list-inline" style="padding:0;margin-bottom:5px">
			<li>
				<select name="date_field" class="form-control input-sm">
					<option value="">::데이트 필드선택::</option>
					<option value="A.banking_date" <?=($date_field=='A.banking_date')?'selected':'';?>>실지급일</option>
					<option value="A.date" <?=($date_field=='A.date')?'selected':'';?>>지급예정일</option>
					<option value="C.loan_start_date" <?=($date_field=='C.loan_start_date')?'selected':'';?>>대출시작일</option>
					<option value="C.loan_end_date" <?=($date_field=='C.loan_end_date')?'selected':'';?>>대출종료일</option>
				</select>
			</li>
			<li><input type="text" id="sdate" name="sdate" value="<?=$sdate?>" readonly class="form-control input-sm datepicker" placeholder="대상일자(시작)"></li>
			<li>~</li>
			<li><input type="text" id="edate" name="edate" value="<?=$edate?>" readonly class="form-control input-sm datepicker" placeholder="대상일자(종료)"></li>
			<li></li>
		</ul>
		<ul class="col col-md-* list-inline" style="padding:0;margin-bottom:5px">
			<li>
				<select id="category" name="category" class="form-control input-sm">
					<option value="">::카테고리::</option>
					<option value="1"  <?=($category=='1')?'selected':'';?>>동산</option>
					<option value="2"  <?=($category=='2')?'selected':'';?>>부동산</option>
					<option value="2A" <?=($category=='2A')?'selected':'';?>>- 부동산(PF대출)</option>
					<option value="2B" <?=($category=='2B')?'selected':'';?>>- 부동산(주택담보대출)</option>
					<option value="3"  <?=($category=='3')?'selected':'';?>>확정매출채권</option>
				</select>
			</li>
			<li>
				<select name="state" class="form-control input-sm">
					<option value="">진행현황 :</option>
					<option value="1" <?=($state=='1')?'selected':'';?>>상환중</option>
					<option value="5" <?=($state=='5')?'selected':'';?>>중도상환</option>
					<option value="2" <?=($state=='2')?'selected':'';?>>만기상환</option>
					<option value="7" <?=($state=='7')?'selected':'';?>>대출취소(기표후)</option>
				</select>
			</li>
			<li>
				<select name="product_field" class="form-control input-sm">
					<option value="">::상품정보선택::</option>
					<option value="C.idx" <?=($product_field=='C.idx')?'selected':'';?>>품번</option>
					<option value="C.start_num" <?=($product_field=='C.start_num')?'selected':'';?>>호번</option>
					<option value="C.title" <?=($product_field=='C.title')?'selected':'';?>>상품명</option>
				</select>
			</li>
			<li><input type="text" class="form-control input-sm" name="product_kwd" size="30" value="<?=$product_kwd?>" placeholder="상품정보검색키워드"></li>
		</ul>
		<ul class="col col-md-* list-inline" style="padding:0;margin-bottom:5px">
			<li>
				<select name="member_type" class="form-control input-sm">
					<option value="">::회원구분::</option>
					<option value="1" <?=($member_type=='1')?'selected':'';?>>개인</option>
					<option value="1A" <?=($member_type=='1A')?'selected':'';?>>- 개인(사업자)</option>
					<option value="1B" <?=($member_type=='1B')?'selected':'';?>>- 개인(대부업)</option>
					<option value="2" <?=($member_type=='2')?'selected':'';?>>법인</option>
					<option value="2A" <?=($member_type=='2A')?'selected':'';?>>- 법인(대부업)</option>
				</select>
			</li>
			<li>
				<select name="investor_field" class="form-control input-sm">
					<option value="">::투자자정보선택::</option>
					<option value="B.mb_no" <?=($investor_field=='B.mb_no')?'selected':'';?>>회원번호</option>
					<option value="B.mb_id" <?=($investor_field=='B.mb_id')?'selected':'';?>>아이디</option>
					<option value="investor_title" <?=($investor_field=='investor_title')?'selected':'';?>>회원명/법인명</option>
					<option value="B.account_num" <?=($investor_field=='B.account_num')?'selected':'';?>>환금계좌번호</option>
					<option value="B.virtual_account2" <?=($investor_field=='B.virtual_account2')?'selected':'';?>>가상계좌번호</option>
				</select>
			</li>
			<li><input type="text" class="form-control input-sm" name="investor_kwd" size="30" value="<?=$investor_kwd?>" placeholder="회원정보검색키워드"></li>
		</ul>
		<ul class="col col-md-* list-inline" style="padding:0;margin-bottom:5px">
			<li>
				<select name="turn" class="form-control input-sm">
					<option value="">::상환회차선택::</option>
<?
	$r = sql_fetch("SELECT IFNULL(MAX(turn),0) AS max_turn FROM cf_product_give");
	for($i=0,$j=1; $i<$r['max_turn']; $i++,$j++) {
		$selected = ($j==$turn) ? 'selected' : '';
		echo	"					<option value='".$j."' $selected>".$j."회차</option>\n";
	}
?>
				</select>
			</li>
			<li>
				<select name="special_turn" class="form-control input-sm">
					<option value="">::회차추가조건::</option>
					<option value='principal' <?=($special_turn=='principal')?'selected':''?>>원금상환</option>
					<option value='overdue_n' <?=($special_turn=='overdue_n')?'selected':''?>>정규상환</option>
					<option value='overdue_y' <?=($special_turn=='overdue_y')?'selected':''?>>연체상환</option>
				</select>
			</li>
			<li>
				<select name="give_rcv_method" class="form-control input-sm">
					<option value="">::지급방법선택::</option>
					<option value="1" <?=($give_rcv_method=='1')?'selected':'';?>>계좌이체</option>
					<option value="2" <?=($give_rcv_method=='2')?'selected':'';?>>예치금적립</option>
				</select>
			</li>
			<li>
				<select name="bill_result" class="form-control input-sm">
					<option value="">::계산서발행상태::</option>
					<option value="null" <?=($bill_result=='null')?'selected':'';?>>미발행</option>
					<option value="1" <?=($bill_result=='ok')?'selected':'';?>>발행</option>
				</select>
			</li>
			<li>
				<select name="give_field" class="form-control input-sm">
					<option value="">::지급정보선택::</option>
					<option value="A.account_num" <?=($give_field=='A.account_num')?'selected':'';?>>지급계좌번호</option>
					<option value="A.mgtKey" <?=($give_field=='A.mgtKey')?'selected':'';?>>계산서발급번호</option>
					<option value="A.GUAR_SEQ" <?=($give_field=='A.GUAR_SEQ')?'selected':'';?>>이체거래번호</option>
				</select>
			</li>
			<li><input type="text" class="form-control input-sm" name="give_kwd" size="30" value="<?=$give_kwd?>" placeholder="지급정보검색키워드"></li>
			<li><button type="submit" class="btn btn-sm btn-warning">검색</button></li>
			<li><button type="button" onClick="location.replace('<?=$_SERVER['PHP_SELF']?>');" class="btn btn-sm btn-default">초기화</button></li>
			<!--<li><button type="button" id="excelDownToProduct" class="btn btn-sm btn-success" style="width:150px">검색결과 다운로드</button></li>-->
			<li>
				<button type="button" onclick="go_excel();" class="btn btn-sm btn-success" style="width:150px">검색결과 다운로드</button>
				<input type=hidden name="display_mode" value=""/>
			</li>
		</ul>
		<div class="clearfix"></div>
		</form>
	</div>
	<!-- 검색영역 E N D -->
	<? } ?>

<?
//$print_total_principal = ($total_principal > 10000) ? price_cutting($total_principal).'원' : number_format($total_principal).'원';
//$print_total_interest  = ($total_interest > 10000) ? price_cutting($total_interest).'원' : number_format($total_interest).'원';
//$print_total_tax       = ($total_tax > 10000) ? price_cutting($total_tax).'원' : number_format($total_tax).'원';
//$print_total_fee       = ($total_fee > 10000) ? price_cutting($total_fee).'원' : number_format($total_fee).'원';

$print_total_principal = number_format($total_principal).'원';
$print_total_interest  = number_format($total_interest).'원';
$print_total_repay_amount = number_format($total_repay_amount).'원';
$print_total_tax       = number_format($total_tax).'원';
$print_total_fee       = number_format($total_fee).'원';
?>

	<div style="width:100%;height:100%;padding:0">
		<table class="table table-striped table-bordered table-hover" style="font-size:12px">
			<tr align="center">
				<th rowspan="2" style="background:#F8F8EF;min-width:70px">지급번호</th>
				<th rowspan="2" style="background:#F8F8EF;min-width:60px"">품번</th>
				<th rowspan="2" style="background:#F8F8EF">상품명</th>
				<th rowspan="2" style="background:#F8F8EF"><div class="overhidden">상환회차</div></th>
				<th rowspan="2" style="background:#F8F8EF"><div class="overhidden">회원번호</div></th>
				<th rowspan="2" style="background:#F8F8EF"><div class="overhidden">회원구분</div></th>
				<th rowspan="2" style="background:#F8F8EF">아이디</th>
				<th rowspan="2" style="background:#F8F8EF">성명.법인명</th>
				<th colspan="3" style="background:#F8F8EF">실지급액</th>
				<th rowspan="2" style="background:#F8F8EF">세금</th>
				<th rowspan="2" style="background:#F8F8EF">플랫폼이용료</th>
				<th rowspan="2" style="background:#F8F8EF">지급방법</th>
				<th rowspan="2" style="background:#F8F8EF">지급계좌</th>
				<th rowspan="2" style="background:#F8F8EF"><div class="overhidden">세금계산서<br>문서번호</div></th>
				<th rowspan="2" style="background:#F8F8EF">지급일시</th>
			</tr>
			<tr>
				<th style="background:#F8F8EF">원금</th>
				<th style="background:#F8F8EF">이자(세후)</th>
				<th style="background:#F8F8EF">합계</th>
			</tr>

			<tr align="center" style="background:#EEEEFF;color:brown;">
				<td colspan="8">합계: <?=number_format($total_count)?>건</td>
				<td><div class="overhidden" style="text-align:right;"><?=$print_total_principal?></div></td>
				<td><div class="overhidden" style="text-align:right;"><?=$print_total_interest?></div></td>
				<td><div class="overhidden" style="text-align:right;"><?=$print_total_repay_amount?></div></td>
				<td><div class="overhidden" style="text-align:right;"><?=$print_total_tax?></div></td>
				<td><div class="overhidden" style="text-align:right;"><?=$print_total_fee?></div></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>

<?
for($i=0,$j=$num; $i<$list_count; $i++,$j--) {

	$print_tax = $LIST[$i]['interest_tax'] + $LIST[$i]['local_tax'];


	if($LIST[$i]['member_type']=='2') {
		$print_name = $LIST[$i]['mb_co_name'];
	}
	else {
		$print_name = ($_SESSION['ss_accounting_admin']) ? $LIST[$i]['mb_name'] : hanStrMasking($LIST[$i]['mb_name']);
	}

	$print_member_type = ($LIST[$i]['member_type']=='2') ? '법인' : '개인';

	if($LIST[$i]['member_type']=='1') {
		if($LIST[$i]['is_owner_operator']=='1' && $LIST[$i]['mb_co_reg_num']!='') {
			$print_member_type.= '-사업자';
			$print_member_type.= ($LIST[$i]['is_creditor']=='Y') ? '(대부업)' : '';
		}
	}
	else {
		$print_member_type.= ($LIST[$i]['is_creditor']=='Y') ? '(대부업)' : '';
	}

	$fcolor = (substr($LIST[$i]['mgtKey'],0,1)=='C') ? '#FF2222' : '#3366FF';

	$product_link = "/adm/product/product_list.php?field=A.idx&keyword=".$LIST[$i]['product_idx'];
	$member_link  = "/adm/member/member_list.php?key_search=A.mb_no&keyword=".$LIST[$i]['member_idx'];

	$taxinvoice_vlink = "/LINKHUB/hellofunding/Taxinvoice/GetPopUpURL.php?mgtKey=".$LIST[$i]['mgtKey'];

	$print_receive_method = ($LIST[$i]['receive_method']=='2') ? '예치금적립' : '계좌이체';

	$LIST[$i]['account_num'] = ($_SESSION['ss_accounting_admin']) ? $LIST[$i]['account_num'] : substr($LIST[$i]['account_num'],0,strlen($LIST[$i]['account_num'])-4) . "****";

	$print_banking_info = $LIST[$i]['bank_name'] . " " . $LIST[$i]['account_num'];

?>
			<tr align="center" style="background:<?=$bgcolor?>;">
				<td><?=$LIST[$i]['idx']?></td>
				<td><a href="<?=$product_link?>"><?=$LIST[$i]['product_idx']?></a></td>
				<td <?=$display_mode=="excel"?"nowrap":""?>><div class="overhidden" style="text-align:left;"><?=$LIST[$i]['title']?></div></td>
				<td><?=$LIST[$i]['turn']?>회차</td>
				<td><a href="<?=$member_link?>"><?=$LIST[$i]['member_idx']?></a></td>
				<td><div class="overhidden"><?=$print_member_type?></div></td>
				<td><div class="overhidden"><?=$LIST[$i]['mb_id']?></div></td>
				<td><div class="overhidden"><?=$print_name?></div></td>
				<td align="right">
					<a href="javascript:;" onClick="balance_check(<?=$LIST[$i]['member_idx']?>)" style="color:blue">
					<?=number_format($LIST[$i]['principal'])?><?=$display_mode<>"excel"?"원":""?>
					</a>
				</td>
				<td align="right"><?=number_format($LIST[$i]['interest'])?><?=$display_mode<>"excel"?"원":""?></td>
				<td align="right"><?=number_format($LIST[$i]['repay_amount'])?><?=$display_mode<>"excel"?"원":""?></td>
				<td align="right"><?=number_format($print_tax)?><?=$display_mode<>"excel"?"원":""?></td>
				<td align="right"><?=number_format($LIST[$i]['fee'])?><?=$display_mode<>"excel"?"원":""?></td>
				<td><div class="overhidden"><?=$print_receive_method?></div></td>
				<td><div class="overhidden"><?=$print_banking_info?></div></td>
				<td><div class="overhidden"><?if($taxinvoice_vlink){?><a href="<?=$taxinvoice_vlink?>" target="_blank"><span style="color:<?=$fcolor?>"><?=$LIST[$i]['mgtKey']?></span></a><?}?></div></td>
				<td><div class="overhidden"><?=substr($LIST[$i]['banking_date'],0,16)?></div></td>
			</tr>
<?
	unset($LIST[$i]);
}
?>
		</table>
<? if ($display_mode<>"excel") { ?>
		<div id="paging_span" style="width:100%; margin:-10px 0 10px; text-align:center;"><? paging($total_count, $page, $page_rows, 10); ?></div>
<? } ?>
	</div>

</div>

<? $qstr = preg_replace("/&page=([0-9]){1,10}/", "", $_SERVER['QUERY_STRING']); ?>

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
</script>


<?
if ($display_mode<>"excel") include_once ('../admin.tail.php');
?>