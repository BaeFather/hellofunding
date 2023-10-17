<?
///////////////////////////////////////////////////////////////////////////////
// 기타비용배분 등록리스트
//   IB_FB_P2P_REPAY_REQ_DETAIL etc_cost_idx int(10) 추가
//
//		1. 대출회원 생성 - 대출자등록 - 상환용가상계좌발급
//		2. 1원 상품 생성, 1원 투자자 등록
//		3. 기관등록, 대출 실행<br/>
//		4. 배분자료 등록<br/><br/>
///////////////////////////////////////////////////////////////////////////////
include_once('./_common.php');

$sub_menu = "700910";
auth_check($auth[$sub_menu], 'w');		// 권한체크

if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

while( list($k, $v) = each($_REQUEST) ) { if(!is_array(${$k})) ${$k} = trim($v); }


$sdatetime = $sdate . ' 00:00:00';
$edatetime = $edate . ' 23:59:59';


$where = "";
$where.= " AND is_drop = ''";

if($dateFld=='rdatetime') {
	if($sdate) $where.= " AND rdatetime >= '".$sdatetime."'";
	if($edate) $where.= " AND rdatetime <= '".$edatetime."'";
}
else if($dateFld=='edatetime') {
	if($sdate) $where.= " AND edit_datetime >= '".$sdatetime."'";
	if($edate) $where.= " AND edit_datetime >= '".$edatetime."'";
}

if($searchField && $keyword) {
	if($searchField=='title') {
		$where.= " AND (A.title LIKE '%".$keyword."%' OR A.memo LIKE '%".$keyword."%') ";
	}
	else if($searchField=='product_idx') {
		$where.= ( preg_match("/\,/", $keyword) ) ? " AND A.product_idx IN(".$keyword.")" : " AND A.product_idx='".$keyword."'";
	}
	else if($searchField=='start_num') {
		$where.= ( preg_match("/\,/", $keyword) ) ? " AND B.start_num IN(".$keyword.")" : " AND B.start_num='".$keyword."'";
	}
	else if($searchField=='product_title') {
		$where.= " AND B.title LIKE '%".$keyword."%'";
	}
	else {
		//
	}
}


$sql = "
	SELECT
		COUNT(A.idx) AS cnt,
		IFNULL(SUM(interest),0) AS sum_interest,
		IFNULL(SUM(fee),0) AS sum_fee
	FROM
		cf_etc_cost A
	WHERE 1
		$where";
$row = sql_fetch($sql);
$total_count = $row['cnt'];
$sum_interest = $row['sum_interest'];
$sum_fee = $row['sum_fee'];

$page_rows = 10;
$total_page  = ceil($total_count / $page_rows);
if($page < 1) $page = 1;
$from_record = ($page - 1) * $page_rows;
$num = $total_count - $from_record;

$sql = "
	SELECT
		A.*,
		B.title AS product_title
	FROM
		cf_etc_cost A
	LEFT JOIN
		cf_product B  ON A.product_idx = B.idx
	WHERE 1
		$where
	ORDER BY
		A.idx DESC
	LIMIT
		$from_record, $page_rows";
//print_rr($sql);
$res  = sql_query($sql);
$rows = $res->num_rows;
for($i=0; $i<$rows; $i++) {
	$LIST[$i] = sql_fetch_array($res);

	$invest_sql = "
		SELECT
			B.mb_no,
			B.mb_id,
			IF(B.member_type='2', B.mb_co_name, B.mb_name) AS mb_title
		FROM
			cf_product_invest A
		LEFT JOIN
			g5_member B  ON A.member_idx=B.mb_no
		WHERE 1
			AND A.product_idx='".$LIST[$i]['product_idx']."' AND invest_state='Y'
		ORDER BY
			amount DESC, idx ASC
		LIMIT 1";
	$INVEST = sql_fetch($invest_sql);

	$LIST[$i]['invest_mb_no']   = $INVEST['mb_no'];
	$LIST[$i]['invest_mb_id']   = $INVEST['mb_id'];
	$LIST[$i]['invest_mb_title']= $INVEST['mb_title'];


	$ib_req_sql = "
		SELECT
			A.*,
			B.RESP_CODE, B.EXEC_STATUS
		FROM
			IB_FB_P2P_REPAY_REQ_DETAIL A
		LEFT JOIN
			IB_FB_P2P_REPAY_REQ B  ON (A.req_idx = B.idx AND A.SDATE = B.SDATE AND A.REG_SEQ = B.REG_SEQ)
		WHERE 1
			AND A.etc_cost_idx = '".$LIST[$i]['idx']."'";
	//print_rr($ib_req_sql);
	$LIST[$i]['IB_REQ'] = sql_fetch($ib_req_sql);

}

//print_rr($LIST);
$list_count = count($LIST);

$g5['title'] = $menu['menu700'][11][1];

include_once(G5_ADMIN_PATH . '/admin.head.php');

/*
SCF 업체 수수료
2443호 차주 이자 반환
*/

?>

<style>
.table0 th.border_r { border-right:1px solid #999; }
.table0 td.border_r { border-right:1px solid #999; }
.table0 th.border_l { border-left:1px solid #999; }
</style>

<div class="tbl_head02 tbl_wrap">
	<div class="content" style="margin:30px auto">

		<!-- 검색영역 START -->
		<div style="line-height:28px;">
			<form name="frmSearch" method="get" class="form-horizontal">
			<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
				<li>
					<select name="dateFld" class="form-control input-sm">
						<option value="">::데이트필드선택::</option>
						<option value="rdatetime" <?=($dateFld=='rdatetime') ? 'selected' : '';?>>자료등록일</option>
						<option value="edatetime" <?=($dateFld=='edatetime') ? 'selected' : '';?>>자료수정일</option>
						<option value="order_rdate" <?=($dateFld=='order_rdate') ? 'selected' : '';?>>배분요청대기처리일</option>
					</select>
				</li>
				<li><input type="text" id="sdate" name="sdate" value="<?=$sdate?>" class="form-control input-sm datepicker"></li>
				<li>~</li>
				<li><input type="text" id="edate" name="edate" value="<?=$edate?>" class="form-control input-sm datepicker"></li>
			</ul>
			<ul class="col-sm-10 list-inline" style="width:100%;padding:0;margin-bottom:5px">
				<li>
					<select id="searchField" name="searchField" class="form-control input-sm">
						<option value="">::상품필드선택::</option>
						<option value="title" <?=($searchField=='title') ? 'selected' : '';?>>명목 및 내용</option>
						<option value="product_idx" <?=($searchField=='product_idx') ? 'selected' : '';?>>품번</option>
						<option value="start_num" <?=($searchField=='start_num') ? 'selected' : '';?>>호번</option>
						<option value="product_title" <?=($searchField=='product_title') ? 'selected' : '';?>>상품명</option>
					</select>
				</li>
				<li><input type="text" id="keyword" name="keyword" value="<?=$keyword?>" class="form-control input-sm"></li>
				<li><button type="submit" class="btn btn-sm btn-warning">검색</button></li>
				<li></li>

				<li style="float:right"><button type="button" onClick="requestPopup('R');" class="btn btn-sm btn-primary" style="width:120px;">기관전송예약</button></li>
				<li style="float:right"><button id="formOpen" type="button" class="btn btn-sm btn-primary" style="width:120px;">배분자료등록</button></li>
				<li style="float:right"><button id="ProductFormOpen" type="button" class="btn btn-sm btn-gray" style="width:160px;">기타비용 회수용 상품생성</button></li>
			</ul>
			</form>
		</div>
		<!-- 검색영역 E N D -->

		<script>
		$('#ProductFormOpen').on('click', function() {
			alert("상품생성 \n\n" +
				"1. 대출회원 생성, 상환용가상계좌발급\n" +
				"2. 1원 상품 생성, 기관등록\n" +
				"3. 투자자등록\n" +
				"4. 대출실행");
		});
		</script>

		<table class="table0 table-bordered">
			<colgroup>
				<col width="5%">
				<col width="6%">
				<col width="6%">
				<col width="%">

				<col width="5%">
				<col width="14%">

				<col width="5%">
				<col width="8%">

				<col width="6%">
				<col width="5%">

				<col width="6%">
				<col width="6%">
				<col width="6%">
				<col width="6%">
			</colgroup>
			<thead>
				<tr>
					<th rowspan="2" style="background:#F8F8EF">NO</th>
					<th rowspan="2" style="background:#F8F8EF">이자(세후)</th>
					<th rowspan="2" style="background:#F8F8EF">수수료</th>
					<th rowspan="2" style="background:#F8F8EF">명목</th>
					<th colspan="2" style="background:#F8F8EF">기타비용 회수용 상품</th>
					<th colspan="2" style="background:#F8F8EF">대표투자자</th>
					<th rowspan="2" style="background:#F8F8EF">등록일시</th>
					<th rowspan="2" style="background:#F8F8EF" class="border_r">수정</th>
					<th colspan="4" style="background:#F8F8EF">배분요청대기</th>
				</tr>
				<tr>
					<th style="background:#F8F8EF">품번</th>
					<th style="background:#F8F8EF">상품명</th>

					<th style="background:#F8F8EF">회원번호</th>
					<th style="background:#F8F8EF">ID</th>

					<th style="background:#F8F8EF">등록</th>
					<th style="background:#F8F8EF">요청일</th>
					<th style="background:#F8F8EF">발송회차</th>
					<th style="background:#F8F8EF">전문발송결과</th>
				</tr>
			</thead>

			<tbody>

				<tr style="background:#EEEEFF;color:brown;font-size:12px">
					<td style="text-align:center;">합계</td>
					<td style="text-align:right;"><?=number_format($sum_interest);?>원</td>
					<td style="text-align:right;"><?=number_format($sum_fee);?>원</td>
					<td colspan="11"></td>
				</tr>

<?
if($list_count) {
	for($i=0,$j=$num; $i<$list_count; $i++,$j--) {

		$print_ib_send_reg = "<button type='button' class='btn btn-sm btn-danger' onClick=\"etcCostDivideReady('{$LIST[$i]['idx']}','{$LIST[$i]['product_idx']}');\">등록</button>";
		$print_exec_status = "";

		if($LIST[$i]['IB_REQ']['SEQ']) {

			$print_ib_send_reg = substr($LIST[$i]['IB_REQ']['rdate'], 0, 16);

			if($LIST[$i]['IB_REQ']['RESP_CODE']=='C') {
				$print_exec_status = "<font color='#AAAAAA'>기관처리취소</font>"; // 출력내용 임의설정값임.
			}
			else {
				switch($LIST[$i]['IB_REQ']['EXEC_STATUS']) {
					case '00' : $print_exec_status = "<font color='green'>기관처리전</font>"; break;
					case '01' : $print_exec_status = "<font color='#3366FF'>기관처리중</font>"; break;
					case '02' : $print_exec_status = "<font color='#AAAAAA'>기관처리완료</font>"; break;
					default   : $print_exec_status = "<font color='green'>발송전</font>"; break;
				}
			}
		}



?>
				<tr align="center" style="font-size:12px">
					<td><?=$j?></td>
					<td align="right"><?=number_format($LIST[$i]['interest'])."원"?></td>
					<td align="right"><?=number_format($LIST[$i]['fee'])."원"?></td>
					<td><?=$LIST[$i]['title']?></td>
					<td><?=$LIST[$i]['product_idx']?></td>
					<td align="left"><?=$LIST[$i]['product_title']?></td>
					<td><?=$LIST[$i]['invest_mb_no']?></td>
					<td><?=$LIST[$i]['invest_mb_id']?></td>
					<td><?=substr($LIST[$i]['rdatetime'], 0, 16)?></td>
					<td class="border_r"><button type="button" class="btn btn-sm btn-danger" onClick="dataEdit('<?=$LIST[$i]['idx']?>');">수정</button></td>
					<td><?=$print_ib_send_reg?></td>
					<td><?=$LIST[$i]['IB_REQ']['SDATE']?></td>
					<td><?=$LIST[$i]['IB_REQ']['REG_SEQ']?></td>
					<td><?=$print_exec_status?></td>
				</tr>
<?
	}
}
else {
?>
				<tr align="center" style="font-size:12px">
					<td colspan="13">등록된 데이터가 없습니다.</td>
				</tr>
<?
}
?>
			</tbody>
		</table>

	</div>

	<div id="paging_span" style="width:100%; margin:-10px 0 10px; text-align:center;"><? paging($total_count, $page, $page_rows, 10); ?></div>

</div>

<? $qstr = preg_replace("/&page=([0-9]){1,10}/", "", $_SERVER['QUERY_STRING']); ?>

<style>
#registDiv { display:none; position:fixed; z-index:9999; margin-left:-42px; width:100%; height:100%; top:0; }
#registDiv .formCoverDiv { margin:200px auto; width:700px; padding:8px; background:#FFF; border:2px solid #222; }
#registDiv .textbox { height:24px; font-size:12px; line-height:24px; padding-left:4px; padding-right:4px; width:120px; border:1px solid #CCC; background:#FFF; }
#registDiv .align_right { text-align:right; }
</style>
<div id="registDiv">
	<div class="formCoverDiv">
		<form name="registForm" id="registForm">
			<input type="hidden" name="idx" id="idx">
		<h3 id="formTitle">배분요청자료 등록</h3>
		<table class="table table-bordered">
			<colgroup>
				<col width="18%">
				<col width="32%">
				<col width="18%">
				<col width="32%">
			</colgroup>
			<tr>
				<td align="center" style="background:#F8F8EF">대상상품</td>
				<td colspan="3">
					<select name="product_idx" id="product_idx" class="form-control input-sm" style="width:85%">
						<option value="">::선택::</option>
<?
$sql = "SELECT idx, title, recruit_amount, loan_start_date, loan_end_date FROM cf_product WHERE isEtcCost='1' ORDER BY idx DESC";
$res = sql_query($sql);
while($R = sql_fetch_array($res)) {
	$selected = ($LIST[$i]['product_idx']==$R['idx']) ? 'selected' : '';

	$print_str = "품번:".$R['idx'] . " / " . $R['title'] . " / 대출기간: " . $R['loan_start_date'] . "~" . $R['loan_end_date'];
	echo "<option value='".$R['idx']."' >".$print_str."</option>\n";
}
?>
					</select>
				</td>
			</tr>
			<tr>
				<td align="center" style="background:#F8F8EF">등록제목</td>
				<td colspan="3">
					<input type="text" name="title" id="title" autocomplete="off" class="form-control input-sm">
				</td>
			</tr>
			<tr>
				<td align="center" style="background:#F8F8EF">메모</td>
				<td colspan="3"><textarea name="memo" id="memo" class="textbox" style="font-size:12px;line-height:18px;width:100%;height:48px"></textarea></td>
			</tr>
			<tr>
				<td align="center" style="background:#F8F8EF">배분금액</td>
				<td colspan="3">
					<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
						<li style='width:80px;'>원금</li>
						<li><input type="text" name="principal" id="principal" value="0" autocomplete="off" class="form-control input-sm" style="width:150px;text-align:right" onKeyUp="onlyDigit(this);NumberFormat(this);" readonly></li><li>원</li>
					</ul>
					<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
						<li style='width:80px;'>이자(세후)</li>
						<li><input type="text" name="interest" id="interest" value="1" autocomplete="off" class="form-control input-sm" style="width:150px;text-align:right" onKeyUp="onlyDigit(this);NumberFormat(this);" readonly></li><li>원</li>
					</ul>
					<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
						<li style='width:80px;'>이자소득세</li>
						<li><input type="text" name="interest_tex" id="interest_tex" value="0" autocomplete="off" class="form-control input-sm" style="width:150px;text-align:right" onKeyUp="onlyDigit(this);NumberFormat(this);" readonly></li><li>원</li>
					</ul>
					<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
						<li style='width:80px;'>지방소득세</li>
						<li><input type="text" name="local_tax" id="local_tax" value="0" autocomplete="off" class="form-control input-sm" style="width:150px;text-align:right" onKeyUp="onlyDigit(this);NumberFormat(this);" readonly></li><li>원</li>
					</ul>
					<ul class="list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
						<li style='width:80px;'>수수료</li>
						<li><input type="text" name="fee" id="fee" value="1" autocomplete="off" class="form-control input-sm" style="width:150px;text-align:right" onKeyUp="onlyDigit(this);NumberFormat(this);"></li><li>원</li>
					</ul>
				</td>
			</tr>
		</table>
		<div style="margin:10px; text-align:center;">
			<button id="formSubmit" type="button" class="btn btn-sm btn-primary" style="width:100px">등 록</button>
			<button id="formClose" type="button" class="btn btn-sm btn-default" style="width:100px">닫 기</button>
		</div>
		</form>
	</div>
</div>

<!-- 인사이드뱅크 데이터 전송요청 창 //-->
<div id="repay_request_div" style="position:fixed; z-index:9999; top:80px; left:1px; width:100%; height:100%; display:none;"></div>
<!-- 인사이드뱅크 데이터 전송요청 창 //-->

<script type="text/javascript">
$(document).on('click', '#paging_span span.btn_paging', function() {
		var url = '<?=$_SERVER['PHP_SELF']?>'
		        + '?<?=$qstr?>&page=' + $(this).attr('data-page');
		$(location).attr('href', url);
});

$('#registForm #formSubmit').on('click',function() {

	if( $('#registForm #product_idx').val()=='' ) { alert('대상상품을 선택 하십시요.'); $('#registForm #product_idx').focus(); return; }
	if( $('#registForm #title').val()=='' ) { alert('등록제목을 입력 하십시요.'); $('#registForm #title').focus(); return; }
	if( $('#registForm #vat').val()=='' ) { alert('기타비용(수수료) 금액을 입력 하십시요.'); $('#registForm #vat').focus(); return; }

	if( $('#registForm #idx').val()=='' ) {
		action = 'new';
		action_str = '등록';
	}
	else {
		action = 'edit';
		action_str = '수정';
	}

	if( confirm('배분자료를 ' + action_str + ' 하시겠습니까?') ) {
		form_data = $('#registForm').serialize();
		form_data+= '&action=' + action;

		$.ajax({
			url : 'etc_cost_devide.proc.php',
			type: 'POST',
			dataType: 'JSON',
			data: form_data,
			success:function(data) {
				if(data.result=='success') {
					alert(action_str + '완료');window.location.reload();
				}
				else {
					alert(data.message);
					$('#registDiv').fadeOut();
				}
			},
			error: function () {
				alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.");
			}
		});
	}
});

$('#formOpen').on('click',function() {
	$('#formTitle').text('배분자료 등록');
	$('#registDiv').fadeIn();
});

$('#formClose').on('click',function() {
	$('#registDiv').fadeOut();
	document.registForm.reset();
});

function dataEdit(idx) {

	$('#formTitle').text('배분자료 수정');
	$('#registDiv').fadeIn();

	$.ajax({
		url : 'etc_cost_devide.proc.php',
		type: 'POST',
		dataType: 'json',
		data:{'action':'get', 'idx':idx},
		success:function(data) {
			if(data.result=='success') {
				$('#registForm #idx').val(data.data_arr.idx);
				$('#registForm #product_idx').val(data.data_arr.product_idx);
				$('#registForm #title').val(data.data_arr.title);
				$('#registForm #memo').val(data.data_arr.memo);
				$('#registForm #princiapl').val(data.data_arr.princiapl);
				$('#registForm #interest').val(data.data_arr.interest);

				$('#registForm #fee').val(data.data_arr.fee);
			}
			else {
				alert(data.message);
			}
		},
		error: function (jqXHR, textStatus, errorThrown)	{
			console.log(jqXHR);
		}
	});
}


/////////////////////////////////////////////////
// 배분요청대기
/////////////////////////////////////////////////
etcCostDivideReady = function(etc_cost_idx, product_idx) {
	if(confirm("배분요청대기 처리 하시겠습니까?")) {
		$.ajax({
			url: 'etc_cost_devide.proc.php',
			type: 'post',
			dataType: 'json',
			data: {
				action: 'ib_devide_ready',
				etc_cost_idx: etc_cost_idx,
				product_idx: product_idx
			},
			success: function(data) {
				if(data.result=='success') { alert('정상 처리 완료되었습니다.'); window.location.reload(); }
				else { alert(data.message); }
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
		});
	}
}


/////////////////////////////////////////////////
// 원리금 배분전문 등록 팝업호출
/////////////////////////////////////////////////
requestPopup = function(arg) {
	if(typeof arg=='undefined' || arg==null || arg=='') {
		if( $('#repay_request_div').css('display')=='block' ) $('#repay_request_div').fadeOut();
	}
	else {
		if( $('#repay_request_div').css('display')=='none' ) {
			$('#repay_request_div').fadeIn();
			$.ajax({
				url:'./ajax_ib_repay_request.php',
				data:{repay_type:arg},
				type:'get',
				success: function(result) {
					$('#repay_request_div').html(result);
					$.ajax({
						url:'./ajax_ib_send_wait_list.php',
						data:{
							repay_type:arg,
							now_prd_idx:'<?=$prd_idx?>'
						},
						type:'get',
						success: function(result) {
							$('#ib_wait_list').html(result);
						}
					});
				},
				error: function() { alert('통신 에러입니다.'); }
			});
		}
	}
}
</script>

<?

include_once (G5_ADMIN_PATH . '/admin.tail.php');

?>