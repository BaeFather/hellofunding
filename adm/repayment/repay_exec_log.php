<?
###############################################################################
##  투자수익 지급내역 통계
###############################################################################

include_once('./_common.php');

$sub_menu = "700600";
$g5['title'] = $menu['menu700'][6][1];

include_once (G5_ADMIN_PATH.'/admin.head.php');

auth_check($auth[$sub_menu], 'w');
if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

foreach($_GET as $k=>$v) { ${$_GET[$k]} = trim($v); }

if($page < 1) $page = 1;

if($syear=='') $syear = date('Y');

$where = " 1=1 ";

if($sdate && $edate) {
	$_sdate = preg_replace('/-/', '', $sdate);
	$_edate = preg_replace('/-/', '', $edate);
	$where.= " AND SDATE >= '".$_sdate."' AND SDATE <= '".$_edate."' ";
}
else {
	if($sdate) {
		$_sdate = preg_replace('/-/', '', $_sdate);
		$where.= " AND SDATE >= '".$sdate."' ";
	}
	if($edate) {
		$_edate = preg_replace('/-/', '', $edate);
		$where.= " AND SDATE >= '".$edate."' ";
	}
}


$LIST = array();

if($page==1) {
	// 예약내역
	$sql0 = "
		SELECT
			*
		FROM
			IB_FB_P2P_REPAY_REQ_ready
		WHERE
			$where
		ORDER BY
			SDATE DESC,
			REG_SEQ DESC";
	$result0 = sql_query($sql0);
	$rcount0 = $result0->num_rows;
	for($i=0; $i<$rcount0; $i++) {
		$ROW = sql_fetch_array($result0);
		$ROW['interest'] = ($ROW['TOTAL_TR_AMT'] > 0) ? $ROW['TOTAL_TR_AMT'] - $ROW['TOTAL_TR_AMT_P'] : 0;
	//$ROW['interest'] = $ROW['TOTAL_TR_AMT'] - $ROW['TOTAL_TR_AMT_P'] - $ROW['TOTAL_CTAX_AMT'] - $ROW['TOTAL_FEE'];

		array_push($LIST, $ROW);
	}
	sql_free_result($result0);
	unset($ROW);
}

$sql = "SELECT COUNT(SDATE) AS cnt FROM IB_FB_P2P_REPAY_REQ WHERE $where";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 20;
$total_page  = ceil($total_count / $rows);
$from_record = ($page - 1) * $rows;

// 예약내역
$sql = "
	SELECT
		*
	FROM
		IB_FB_P2P_REPAY_REQ
	WHERE
		$where
	ORDER BY
		SDATE DESC,
		REG_SEQ DESC
	LIMIT
		$from_record, $rows";
//print_rr($sql,'font-size:9pt');
$result = sql_query($sql);
$rcount = $result->num_rows;

$SUM = array(
	'TOTAL_CNT'      => 0,
	'TOTAL_TR_AMT'   => 0,
	'TOTAL_TR_AMT_P' => 0,
	'interest'       => 0,
	'TOTAL_CTAX_AMT' => 0,
	'TOTAL_FEE'      => 0,
	'TOTAL_S_CNT'    => 0,
	'TOTAL_E_CNT'    => 0
);
for($i=0; $i<$rcount; $i++) {
	$ROW = sql_fetch_array($result);
	$ROW['interest'] = ($ROW['TOTAL_TR_AMT'] > 0) ? $ROW['TOTAL_TR_AMT'] - $ROW['TOTAL_TR_AMT_P'] : 0;
//$ROW['interest'] = $ROW['TOTAL_TR_AMT'] - $ROW['TOTAL_TR_AMT_P'] - $ROW['TOTAL_CTAX_AMT'] - $ROW['TOTAL_FEE'];

	array_push($LIST, $ROW);

	$SUM['TOTAL_CNT']      += $ROW['TOTAL_CNT'];
	$SUM['TOTAL_TR_AMT']   += $ROW['TOTAL_TR_AMT'];
	$SUM['TOTAL_TR_AMT_P'] += $ROW['TOTAL_TR_AMT_P'];
	$SUM['interest']       += $ROW['interest'];
	$SUM['TOTAL_CTAX_AMT'] += $ROW['TOTAL_CTAX_AMT'];
	$SUM['TOTAL_FEE']      += $ROW['TOTAL_FEE'];
	$SUM['TOTAL_S_CNT']    += $ROW['TOTAL_S_CNT'];
	$SUM['TOTAL_E_CNT']    += $ROW['TOTAL_E_CNT'];

}
sql_free_result($result);

$list_count = count($LIST);
$num = $total_count - $from_record;
//print_rr($LIST,'font-size:9pt');

?>

<div class="tbl_head02 tbl_wrap">

	<!-- 검색영역 START -->
	<div style="line-height:28px;">
		<form name="frmSearch" method="get" class="form-horizontal">
		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li><input type="text" name="sdate" value="<?=$sdate?>" class="form-control input-sm datepicker" style="width:120px" autocomplete="off"></li>
			<li>~</li>
			<li><input type="text" name="edate" value="<?=$edate?>" class="form-control input-sm datepicker" style="width:120px" autocomplete="off"></li>
			<li><button type="submit" class="btn btn-sm btn-warning">검색</button></li>
		</ul>
		</form>
	</div>
	<!-- 검색영역 E N D -->

	<table class="table-striped table-bordered table-hover" style="font-size:12px;">
		<colgroup>
			<col style="width:%">
			<col style="width:%">
			<col style="width:%">
			<col style="width:%">
			<col style="width:%">
			<col style="width:%">
			<col style="width:%">
			<col style="width:%">
			<col style="width:%">
			<col style="width:%">
			<col style="width:%">
			<col style="width:%">
			<col style="width:%">
			<col style="width:%">
			<col style="width:%">
			<col style="width:%">
		</colgroup>
		<tr>
			<th style="background:#F8F8EF">NO</th>
			<th style="background:#F8F8EF">발송요청일시</th>
			<th style="background:#F8F8EF">일별회차</th>
			<th style="background:#F8F8EF">요청건수</th>
			<th style="background:#F8F8EF">실지급총액</th>
			<th style="background:#F8F8EF">원금</th>
			<th style="background:#F8F8EF">이자(세후)</th>
			<th style="background:#F8F8EF">세액</th>
			<th style="background:#F8F8EF">수수료</th>
			<th style="background:#F8F8EF">처리일시</th>
			<th style="background:#F8F8EF">정상처리</th>
			<th style="background:#F8F8EF">에러처리</th>
			<th style="background:#F8F8EF">응답코드</th>
			<th style="background:#F8F8EF">응답메세지</th>
			<th style="background:#F8F8EF">처리상태</th>
		</tr>

<?
if($list_count > 1) {
?>
		<tr align="center" style="background:#EEEEFF;color:brown;">
			<td>합계</td>
			<td colspan="2"></td>
			<td align="right"><?=number_format($SUM['TOTAL_CNT'])?></td>
			<td align="right"><?=number_format($SUM['TOTAL_TR_AMT'])?></td>
			<td align="right"><?=number_format($SUM['TOTAL_TR_AMT_P'])?></td>
			<td align="right"><?=number_format($SUM['interest'])?></td>
			<td align="right" style="color:#AAA"><?=number_format($SUM['TOTAL_CTAX_AMT'])?></td>
			<td align="right" style="color:#AAA"><?=number_format($SUM['TOTAL_FEE'])?></td>
			<td></td>
			<td align="right"><?=@number_format($SUM['TOTAL_S_CNT'])?></td>
			<td align="right"><?=@number_format($SUM['TOTAL_E_CNT'])?></td>
			<td colspan="3"></td>
		</tr>
<?
}
?>

<?
if($list_count) {
	for($i=0,$j=1; $i<$list_count; $i++,$j++) {

		$print_sdatetime = date("Y-m-d H:i:s", strtotime($LIST[$i]['SDATE'].$LIST[$i]['STIME']));
		$print_tran_datetime = ($LIST[$i]['TRAN_DATE'] && $LIST[$i]['TRAN_TIME']) ? date("Y-m-d H:i", strtotime($LIST[$i]['TRAN_DATE'].$LIST[$i]['TRAN_TIME'])) : '';

		$print_resp_code = ($LIST[$i]['RESP_CODE']=='00000000') ? '정상' : '<font color="#FF2222">'.$LIST[$i]['RESP_CODE'].'</font>';

		$print_exec_status = "";
		if($LIST[$i]['EXEC_STATUS']=='00')      $print_exec_status = "<font color='green'>처리전</font>";
		else if($LIST[$i]['EXEC_STATUS']=='01') $print_exec_status = "<font color='#3366FF'>처리중</font>";
		else if($LIST[$i]['EXEC_STATUS']=='02') $print_exec_status = "<font color='#AAAAAA'>처리완료</font>";

		if($LIST[$i]['apply']=='C') $print_exec_status.= " <font color='brown'>취소</font>";

?>
		<tr align="center" onClick="viewDetail('<?=$LIST[$i]['SDATE']?>','<?=$LIST[$i]['REG_SEQ']?>');" style="cursor:pointer">
			<td><?=$num?></td>
			<td><?=$print_sdatetime?></td>
			<td><?=(int)$LIST[$i]['REG_SEQ']?>회차</td>
			<td align="right"><?=number_format($LIST[$i]['TOTAL_CNT'])?></td>
			<td align="right"><?=number_format($LIST[$i]['TOTAL_TR_AMT'])?></td>
			<td align="right"><?=number_format($LIST[$i]['TOTAL_TR_AMT_P'])?></td>
			<td align="right"><?=number_format($LIST[$i]['interest'])?></td>
			<td align="right" style="color:#AAA"><?=number_format($LIST[$i]['TOTAL_CTAX_AMT'])?></td>
			<td align="right" style="color:#AAA"><?=number_format($LIST[$i]['TOTAL_FEE'])?></td>
			<td><?=$print_tran_datetime?></td>
			<td align="right"><?=@number_format($LIST[$i]['TOTAL_S_CNT'])?></td>
			<td align="right"><?=@number_format($LIST[$i]['TOTAL_E_CNT'])?></td>
			<td><?=$print_resp_code?></td>
			<td><?=$LIST[$i]['RESP_MSG']?></td>
			<td><?=$print_exec_status?></td>
		</tr>
<?
		$num--;
	}
}
else {
	echo '
		<tr>
			<td colspan="15" align="center">데이터가 없습니다.</th>
		</tr>' . PHP_EOL;
}
?>
	</table>

</div>

<?
$qstr = preg_replace("/&page=([0-9]){1,10}/", "", $_SERVER['QUERY_STRING']);
echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page=');
?>

<style>
#popup { display:none; position:fixed; z-index:1000000; width:90%;max-height:800px; left:5%;top:5%; min-width:1000px;min-height:100px; }
#popup .closeArea { margin:0 4px 8px auto; width:30px; height:30px; text-align:right; cursor:pointer; }
#popup .viewArea { width:100%; height:100%;background:#FFF; }
</style>
<div id="popup" style="display:none;">
	<div class="closeArea"><img id="close_button" src="/images/cancel_w1.png" height="30" style="opacity:1; cursor:pointer" alt="취소"></div>
	<div id="titleBar" style="padding:8px; text-align:left;background:#FD0017;color:#fff">
		<b>:: <span id="typeName">상환배분 상세보기</span> ::</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		요청일차 : <span id="regSeq" style="color:#ffff66;font-weight:bold"></span> / <span id="sendDate" style="color:#ffff66;font-weight:bold"></span>
	</div>
	<div class="viewArea"></div>
</div>


<script>
fSubmit = function() {
	f = document.frmSearch;
	f.submit();
}


viewDetail = function(sdate, reg_seq) {

	$.blockUI({
		message: $('#popup'),css:{ 'border':'0', 'position':'fixed' }
	});

	//$('#titleBar').css('background', titleColor);
	$('#sendDate').text(sdate);
	$('#regSeq').text(Number(reg_seq) + '회차');

	$('.viewArea').empty();

	$.ajax({
		url : 'repay_exec_log_detail.php',
		type: 'POST',
		dataType: 'html',
		data: {
			sdate:sdate,
			reg_seq:reg_seq
		},
		success:function(data) {
			$('.viewArea').html(data);
		},
		beforeSend: function() { loading('on'); },
		complete: function() { loading('off'); },
		error: function () { alert("repay_proc.php\n통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
	});

	$('#popup').draggable();
}

$('#close_button').on('click', function() {
	$.unblockUI();
	return false;
});
</script>

<?

include_once ('../admin.tail.php');

?>