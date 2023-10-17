<?
###############################################################################
##  세금계산서,현금영수증 발행 일별 상세내역
###############################################################################

include_once('./_common.php');

if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');


foreach($_GET as $k=>$v) { ${$_REQUEST[$k]} = trim($v); }

$datetime_s = $banking_date . ' 00:00:00';
$datetime_e = $banking_date . ' 23:59:59';

$where = "";
$where.= " AND A.banking_date BETWEEN '".$datetime_s."' AND '".$datetime_e."'";
$where.= " AND A.fee > 0";
$where.= " AND B.remit_fee = ''";
if($type) {
	if($type=='c') {		// 세금계산서 대상자 (법인, 개인대부업 또는 개인사업자)
		$where.= " AND (";
		$where.= "   B.member_type = '2'";
		$where.= "   OR (B.member_type = '1' AND B.is_owner_operator='1' AND B.mb_co_reg_num!='')";
		$where.= " )";
	}
	else {			// 현금영수증 대상자 (개인일반)
		$where.= " AND B.member_type = '1'";
		$where.= " AND B.mb_co_reg_num=''";
	}
}

$sql = "
	SELECT
		COUNT(A.idx) AS cnt
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
$total_count = $row['cnt'];

$page_rows = 100;
$total_page  = ceil($total_count / $page_rows);
if($page < 1) $page = 1;
$from_record = ($page - 1) * $page_rows;
$num = $total_count - $from_record;

$sql = "
	SELECT
		A.idx, A.member_idx, A.is_creditor, A.product_idx, A.invest_idx, A.turn, A.is_overdue, A.fee, A.mgtKey,
		B.mb_id, B.mb_co_name, B.mb_name, B.mb_hp, B.mb_email, B.member_type, B.is_owner_operator, B.mb_co_reg_num,
		(SELECT start_num FROM cf_product WHERE idx=A.product_idx) AS start_num,
		C.req_date
	FROM
		cf_product_give A
	LEFT JOIN
		g5_member B  ON A.member_idx=B.mb_no
	LEFT JOIN
		TaxinvoiceLog C  ON A.mgtKey=C.mgtKey
	WHERE (1)
		$where
	ORDER BY
		CASE A.mgtKey WHEN '' THEN 0 ELSE 1 END,
		C.req_date DESC,
		A.idx DESC
	LIMIT
		$from_record, $page_rows";
//print_rr($sql,'font-size:12px');
$res  = sql_query($sql);
$rows = sql_num_rows($res);

for($i=0; $i<$rows; $i++) {
	$LIST[$i] = sql_fetch_array($res);

	$R = sql_fetch("SELECT exec_dt,req_type,req_date FROM TaxinvoiceLog WHERE mgtKey='".$LIST[$i]['mgtKey']."'");
	$LIST[$i]['exec_dt']  = $R['exec_dt'];
	$LIST[$i]['req_type'] = $R['req_type'];
	$LIST[$i]['req_date'] = $R['req_date'];
}
sql_free_result($res);

$list_count = count($LIST);

?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta http-equiv="imagetoolbar" content="no">
<meta http-equiv="X-UA-Compatible" content="IE=10,chrome=1">
<title>세금계산서 발행 상세내역</title>
<link rel="stylesheet" type="text/css" href="/adm/css/admin.css">
<link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
<link rel="stylesheet" type="text/css" href="/adm/css/bootstrap.min.css">
<!--[if lte IE 8]>
<script src="/js/html5.js"></script>
<![endif]-->
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script type="text/javascript" src="/js/common.js"></script>
<script type="text/javascript" src="/adm/js/jquery.form.js"></script>

<style>
#dataList th,td { padding:2px 8px; }
</style>
</head>

<body style="background:#FFF;">

<div style="width:100%;height:100%;padding:0">
	<!--
	<div style="padding:8px; text-align:left;background:#000080;color:#fff">
		<b>:: 세금계산서.현금영수증 발행내역 ::</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		발행대상일 : <font style="color:#ffff66;font-weight:bold"><?=$banking_date?></font>
	</div>
	//-->
	<table id="dataList" class="table table-striped table-bordered table-hover" style="font-size:12px">
		<tr align="center" style="background:#F8F8EF">
			<th>NO</th>
			<th>일괄처리번호</th>
			<th>지급번호</th>
			<th>품번</th>
			<th>호번</th>
			<th>상환회차</th>
			<th>거래금액<br/>(플랫폼이용료)</th>
			<th>회원번호</th>
			<th>아이디</th>
			<th>성명.법인명</th>
			<th>회원구분</th>
			<th>계산서구분</th>
			<th>문서번호</th>
			<th>발행일시</th>
		</tr>
<?
for($i=0,$j=$num; $i<$list_count; $i++,$j--) {

	if($LIST[$i]['mgtKey']) {
		$print_exec_dt = ($LIST[$i]['exec_dt']) ? $LIST[$i]['exec_dt'] : '<span style="color:#AAA">개별발행</span>';
		$bgcolor = "";
	}
	else {
		$print_exec_dt = '<span style="color:brown">미발행</span>';
		$bgcolor = "#FFDDDD";
	}

	$print_member_type = ($LIST[$i]['member_type']=='2') ? '법인' : '개인';

	if($LIST[$i]['member_type']=='2') {
		$print_name = $LIST[$i]['mb_co_name'];
	}
	else {
		$print_name = ($_SESSION['ss_accounting_admin']) ? $LIST[$i]['mb_name'] : hanStrMasking($LIST[$i]['mb_name']);
	}

	if($LIST[$i]['member_type']=='1') {
		if($LIST[$i]['is_owner_operator']=='1' && $LIST[$i]['mb_co_reg_num']!='') {
			$print_member_type.= '-사업자';
			$print_member_type.= ($LIST[$i]['is_creditor']=='Y') ? '(대부)' : '';
		}
	}
	else {
		$print_member_type.= ($LIST[$i]['is_creditor']=='Y') ? '(대부)' : '';
	}

	$fcolor = (substr($LIST[$i]['mgtKey'],0,1)=='C') ? '#FF2222' : '#3366FF';

	$taxinvoice_vlink = "/LINKHUB/hellofunding/Taxinvoice/GetPopUpURL.php?mgtKey=".$LIST[$i]['mgtKey'];

?>
		<tr align="center" style="background:<?=$bgcolor?>;">
			<td><?=$j?></td>
			<td><?=$print_exec_dt?></td>
			<td><?=$LIST[$i]['idx']?></td>
			<td><?=$LIST[$i]['product_idx']?></td>
			<td><?=$LIST[$i]['start_num']?>호</td>
			<td><?=$LIST[$i]['turn']?>회차</td>
			<td align="right"><?=number_format($LIST[$i]['fee'])?>원</td>
			<td><?=$LIST[$i]['member_idx']?></td>
			<td><div style="width:100%;max-width:120px;text-align:center;line-height:14px;overflow:hidden;"><?=$LIST[$i]['mb_id']?></div></td>
			<td><?=$print_name?></td>
			<td><?=$print_member_type?></td>
			<td><?=$LIST[$i]['req_type']?></td>
			<td><?if($taxinvoice_vlink){?><a href="<?=$taxinvoice_vlink?>" target="_blank"><span style="color:<?=$fcolor?>"><?=$LIST[$i]['mgtKey']?></span></a><?}?></td>
			<td><?=preg_replace("/( )/", "<br/>", $LIST[$i]['req_date'])?></td>
		</tr>
<?
	unset($LIST[$i]);
}
?>
	</table>

	<div id="paging_span" style="width:100%; margin:-10px 0 10px; text-align:center;"><? paging($total_count, $page, $page_rows, 10); ?></div>

</div>

<? $qstr = preg_replace("/&page=([0-9]){1,10}/", "", $_SERVER['QUERY_STRING']); ?>

<script type="text/javascript">
$(document).on('click', '#paging_span span.btn_paging', function() {
		var url = '<?=$_SERVER['PHP_SELF']?>'
		        + '?<?=$qstr?>&page=' + $(this).attr('data-page');
		$(location).attr('href', url);
});
</script>

</body>
</html>

<?
sql_close();
exit;

?>