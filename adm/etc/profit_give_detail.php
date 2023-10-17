<?
###############################################################################
##  투자수익 지급내역 통계
###############################################################################

include_once('./_common.php');

$sub_menu = "700300";
$g5['title'] = $menu['menu700'][3][1] . " &gt; " . "지급상세내역";

include_once(G5_ADMIN_PATH.'/admin.head.php');
include_once(G5_LIB_PATH.'/crypt.lib.php');

auth_check($auth[$sub_menu], 'w');
if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');


foreach($_GET as $k=>$v) { ${$_GET[$k]} = trim($v); }

if($syear=='') $syear = date('Y');

$sdate = $syear;
$sdate.= ($smonth) ?  "-" . $smonth : "";
$sdate.= ($sday) ?  "-" . $sday : "";

if($type) {
	$add_where = ($type=='long') ? " AND B.invest_days = 0" : " AND B.invest_days > 0";
}


$sql = "
	SELECT
		A.product_idx, A.turn,
		B.start_num, B.title, B.invest_return,
		B.loan_start_date, B.loan_end_date, B.invest_period, B.invest_days
	FROM
		cf_product_give A
	LEFT JOIN
		cf_product B  ON A.product_idx=B.idx
	WHERE 1
		AND banking_date BETWEEN '".$sdate." 00:00:00' AND '".$sdate." 23:59:59'
		$add_where
	GROUP BY
		A.product_idx, A.turn
	ORDER BY
		B.start_num,
		A.turn,
		B.open_datetime,
		B.idx";
//echo "<pre>".$sql."</pre>";
$result = sql_query($sql);
$rcount = $result->num_rows;

$LIST  = array();

$LIST_B = array(
	'1N' => array(),
	'2N' => array(),
	'1C' => array(),
	'2C' => array()
);

for($i=0; $i<$rcount; $i++) {

	$LIST[$i] = sql_fetch_array($result);

	$TMP = sql_fetch("SELECT COUNT(idx) AS give_count FROM cf_product_give WHERE product_idx='".$LIST[$i]['product_idx']."' AND turn='".$LIST[$i]['turn']."'");
	$LIST[$i]['give_count'] = $TMP['give_count'];

	$LIST[$i]['start_num_title'] = "헬로펀딩 상품 " . $LIST[$i]['start_num'] . "호";

	$shortTermProduct = ($LIST[$i]['invest_period']==1 && $LIST[$i]['invest_days'] > 0) ? true : false;
	$LIST[$i]['max_turn'] = repayTurnCount($LIST[$i]['loan_start_date'], $LIST[$i]['loan_end_date'], false, $shortTermProduct);

	$sql2 = "
		SELECT
			A.idx, A.member_idx, A.interest, A.principal, A.interest_tax, A.local_tax, A.fee, A.is_creditor, A.receive_method, A.bank_name, A.bank_private_name, A.account_num,
			B.mb_id, B.member_type, B.mb_co_name, B.mb_co_reg_num
		FROM
			cf_product_give A
		LEFT JOIN
			g5_member B  ON A.member_idx=B.mb_no
		WHERE 1
			AND A.product_idx='".$LIST[$i]['product_idx']."'
			AND A.turn='".$LIST[$i]['turn']."'
		ORDER BY
			A.product_idx, A.turn ASC, A.turn_sno ASC";
	$res2 = sql_query($sql2);


	while( $ROW = sql_fetch_array($res2) ) {

		$invest_interest    = $ROW['interest'] + $ROW['interest_tax'] + $ROW['local_tax'] + $ROW['fee'];
		$after_tax_interest = $ROW['interest'] + $ROW['fee'];

		$interest_tax       = $ROW['interest_tax'];
		$local_tax          = $ROW['local_tax'];
		$tax                = $ROW['interest_tax'] + $ROW['local_tax'];

		$fee                = $ROW['fee'];
		$fee_supply         = ceil($fee / 1.1);											// 공급가액
		$fee_vat            = $fee - $fee_supply;									// 부가세

		$last_interest      = $ROW['interest'];
		$last_amount        = $ROW['interest'] + $ROW['principal'];


		$LIST[$i]['principal']          += $ROW['principal'];
		$LIST[$i]['invest_interest']    += $invest_interest;
		$LIST[$i]['after_tax_interest'] += $after_tax_interest;
		$LIST[$i]['interest_tax']       += $interest_tax;
		$LIST[$i]['local_tax']          += $local_tax;
		$LIST[$i]['tax']                += $tax;
		$LIST[$i]['fee_supply']         += $fee_supply;
		$LIST[$i]['fee_vat']            += $fee_vat;
		$LIST[$i]['fee']                += $fee;
		$LIST[$i]['last_interest']      += $last_interest;
		$LIST[$i]['last_amount']        += $last_amount;


		if($ROW['is_creditor']=='Y') {
			if($ROW['member_type']=='2') {
				$LIST_B['2C']['give_count']         += 1;
				$LIST_B['2C']['principal']          += $ROW['principal'];
				$LIST_B['2C']['invest_interest']    += $invest_interest;
				$LIST_B['2C']['after_tax_interest'] += $after_tax_interest;
				$LIST_B['2C']['interest_tax']       += $interest_tax;
				$LIST_B['2C']['local_tax']          += $local_tax;
				$LIST_B['2C']['tax']                += $tax;
				$LIST_B['2C']['fee_supply']         += $fee_supply;
				$LIST_B['2C']['fee_vat']            += $fee_vat;
				$LIST_B['2C']['fee']                += $fee;
				$LIST_B['2C']['last_interest']      += $last_interest;
				$LIST_B['2C']['last_amount']        += $last_amount;
			}
			else {
				$LIST_B['1C']['give_count']         += 1;
				$LIST_B['1C']['principal']          += $ROW['principal'];
				$LIST_B['1C']['invest_interest']    += $invest_interest;
				$LIST_B['1C']['after_tax_interest'] += $after_tax_interest;
				$LIST_B['1C']['interest_tax']       += $interest_tax;
				$LIST_B['1C']['local_tax']          += $local_tax;
				$LIST_B['1C']['tax']                += $tax;
				$LIST_B['1C']['fee_supply']         += $fee_supply;
				$LIST_B['1C']['fee_vat']            += $fee_vat;
				$LIST_B['1C']['fee']                += $fee;
				$LIST_B['1C']['last_interest']      += $last_interest;
				$LIST_B['1C']['last_amount']        += $last_amount;
			}
		}
		else {
			if($ROW['member_type']=='2') {
				$LIST_B['2N']['give_count']         += 1;
				$LIST_B['2N']['principal']          += $ROW['principal'];
				$LIST_B['2N']['invest_interest']    += $invest_interest;
				$LIST_B['2N']['after_tax_interest'] += $after_tax_interest;
				$LIST_B['2N']['interest_tax']       += $interest_tax;
				$LIST_B['2N']['local_tax']          += $local_tax;
				$LIST_B['2N']['tax']                += $tax;
				$LIST_B['2N']['fee_supply']         += $fee_supply;
				$LIST_B['2N']['fee_vat']            += $fee_vat;
				$LIST_B['2N']['fee']                += $fee;
				$LIST_B['2N']['last_interest']      += $last_interest;
				$LIST_B['2N']['last_amount']        += $last_amount;
			}
			else {
				$LIST_B['1N']['give_count']         += 1;
				$LIST_B['1N']['principal']          += $ROW['principal'];
				$LIST_B['1N']['invest_interest']    += $invest_interest;
				$LIST_B['1N']['after_tax_interest'] += $after_tax_interest;
				$LIST_B['1N']['interest_tax']       += $interest_tax;
				$LIST_B['1N']['local_tax']          += $local_tax;
				$LIST_B['1N']['tax']                += $tax;
				$LIST_B['1N']['fee_supply']         += $fee_supply;
				$LIST_B['1N']['fee_vat']            += $fee_vat;
				$LIST_B['1N']['fee']                += $fee;
				$LIST_B['1N']['last_interest']      += $last_interest;
				$LIST_B['1N']['last_amount']        += $last_amount;
			}
		}

	}

	sql_free_result($res2);

	$LIST_SUM['give_count']         += $LIST[$i]['give_count'];
	$LIST_SUM['principal']          += $LIST[$i]['principal'];
	$LIST_SUM['invest_interest']    += $LIST[$i]['invest_interest'];
	$LIST_SUM['after_tax_interest'] += $LIST[$i]['after_tax_interest'];
	$LIST_SUM['interest_tax']       += $LIST[$i]['interest_tax'];
	$LIST_SUM['local_tax']          += $LIST[$i]['local_tax'];
	$LIST_SUM['tax']                += $LIST[$i]['tax'];
	$LIST_SUM['fee_supply']         += $LIST[$i]['fee_supply'];
	$LIST_SUM['fee_vat']            += $LIST[$i]['fee_vat'];
	$LIST_SUM['fee']                += $LIST[$i]['fee'];
	$LIST_SUM['last_interest']      += $LIST[$i]['last_interest'];
	$LIST_SUM['last_amount']        += $LIST[$i]['last_amount'];


}
sql_free_result($result);

$list_count = count($LIST);

//print_rr($LIST);

?>

<style>
#detailDiv { position:relative; z-index:10; display:none; margin-top:20px; width:100%; }
</style>

<div class="tbl_head02 tbl_wrap">

	<!-- 검색영역 START -->
	<div style="line-height:28px;">
		<form id="frmSearch" name= "frmSearch" method="get" class="form-horizontal">
		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>
				<select id="syear" name="syear" class="form-control input-sm">
<?
for($i=2016; $i<=date(Y); $i++) {
	$selected = ($i==$syear) ? 'selected' : '';
	echo "<option value='".$i."' $selected>".$i."년</option>\n";
}
?>
				</select>
			</li>
			<li>
				<select id="smonth" name="smonth" class="form-control input-sm">
<?
for($i=1; $i<=12; $i++) {
	$i = sprintf("%02d", $i);
	$selected = ($i==$smonth) ? 'selected' : '';
	echo "<option value='".$i."' $selected>".$i."월</option>\n";
}
?>
				</select>
			</li>
			<li>
				<select id="sday" name="sday" class="form-control input-sm" onChange="fSubmit();">
					<option value="">전체</option>
<?
for($i=1; $i<=31; $i++) {
	$i = sprintf("%02d", $i);
	$selected = ($i==$sday) ? 'selected' : '';
	echo "<option value='".$i."' $selected>".$i."일</option>\n";
}
?>
				</select>
			</li>
			<li>
				<select id="type" name="type" class="form-control input-sm">
					<option value="">전체상품</option>
					<option value="short" <? if($type=='short') echo "selected"; ?>>1개월 미만 상품</option>
					<option value="long" <? if($type=='long') echo "selected"; ?>>1개월 이상 상품</option>
				</select>
			</li>
			<li><button type="button" id="submit_button" onClick="fSubmit();" class="btn btn-sm btn-warning">검색</button></li>
			<li><button type="button" id="excelDownButton" class="btn btn-sm btn-success" style="width:150px">검색결과 다운로드</button></li>
		</ul>
		</form>
	</div>
	<!-- 검색영역 E N D -->

	<table class="table-bordered table-hover">
		<colgroup>
			<col style="width:3%">
			<col style="width:%">
			<col style="width:4%">
			<col style="width:5%">
			<col style="width:5%">
			<col style="width:%">
			<col style="width:6.8%">
			<col style="width:6.8%">
			<col style="width:6.8%">
			<col style="width:6.8%">
			<col style="width:6.8%">
			<col style="width:6.8%">
			<col style="width:6.8%">
			<col style="width:6.8%">
			<col style="width:6.8%">
			<col style="width:6.8%">
		</colgroup>
		<tr>
			<th style="background:#DCE6F1"><input type="checkbox" id="chkall" style="padding:0;margin:0"></th>
			<th style="background:#DCE6F1">NO</th>
			<th style="background:#DCE6F1">펀딩상품</th>
			<th style="background:#DCE6F1">회차</th>
			<th style="background:#DCE6F1">이자율</th>
			<th style="background:#DCE6F1">지급건수</th>
			<th style="background:#DCE6F1">원금</th>
			<th style="background:#DCE6F1">투자수익</th>
			<th style="background:#DCE6F1">이자소득세</th>
			<th style="background:#DCE6F1">지방소득세</th>
			<th style="background:#DCE6F1">원천세계</th>
			<th style="background:#DCE6F1">차감지급액</th>
			<th style="background:#DCE6F1">플랫폼이용료</th>
			<th style="background:#DCE6F1">부가세</th>
			<th style="background:#DCE6F1">플랫폼이용료계</th>
			<th style="background:#DCE6F1">세후금액</th>
			<th style="background:#DCE6F1">실지급액</th>
		</tr>
		<form id="frmX01" name="frmX01">
<?
if($list_count) {
	for($i=0,$j=1; $i<$list_count; $i++,$j++) {

?>
		<tr align="right" style="font-size:12px">
			<td align="center"><input type="checkbox" id="chk<?=$j?>" name="chk[]" value="<?=$LIST[$i]['product_idx']?>"></td>
			<td align="center"><?=$j?> </td>
			<td align="center"><a href="javascript:;" onClick="detailView('<?=$LIST[$i]['product_idx']?>','<?=$LIST[$i]['turn']?>');" title="<?=$LIST[$i]['title']?>"><?=$LIST[$i]['start_num_title']?></a></td>
			<td align="center"><?=$LIST[$i]['turn']?> / <?=$LIST[$i]['max_turn']?></td>
			<td><?=floatRtrim($LIST[$i]['invest_return'])?>%</td>
			<td><?=number_format($LIST[$i]['give_count'])?></td>
			<td><?=number_format($LIST[$i]['principal'])?></td>
			<td><?=number_format($LIST[$i]['invest_interest'])?></td>
			<td><?=number_format($LIST[$i]['interest_tax'])?></td>
			<td><?=number_format($LIST[$i]['local_tax'])?></td>
			<td><?=number_format($LIST[$i]['tax'])?></td>
			<td><?=number_format($LIST[$i]['after_tax_interest'])?></td>
			<td><?=number_format($LIST[$i]['fee_supply'])?></td>
			<td><?=number_format($LIST[$i]['fee_vat'])?></td>
			<td><?=number_format($LIST[$i]['fee'])?></td>
			<td><?=number_format($LIST[$i]['last_interest'])?></td>
			<td><?=number_format($LIST[$i]['last_amount'])?></td>
		</tr>
<?
	}
?>
		<tr id="listSumDiv" align="right" style="font-size:12px;background:#F6F6F6;">
			<td colspan="5" align="center">합계</td>
			<td><?=number_format($LIST_SUM['give_count'])?></td>
			<td><?=number_format($LIST_SUM['principal'])?></td>
			<td><?=number_format($LIST_SUM['invest_interest'])?></td>
			<td><?=number_format($LIST_SUM['interest_tax'])?></td>
			<td><?=number_format($LIST_SUM['local_tax'])?></td>
			<td><?=number_format($LIST_SUM['tax'])?></td>
			<td><?=number_format($LIST_SUM['after_tax_interest'])?></td>
			<td><?=number_format($LIST_SUM['fee_supply'])?></td>
			<td><?=number_format($LIST_SUM['fee_vat'])?></td>
			<td><?=number_format($LIST_SUM['fee'])?></td>
			<td><?=number_format($LIST_SUM['last_interest'])?></td>
			<td><?=number_format($LIST_SUM['last_amount'])?></td>
		</tr>

		<tr align="center">
			<th colspan="4" style="background:#DDD;"></th>
			<th style="background:#DCE6F1">구분</th>
			<th style="background:#DCE6F1">지급건수</th>
			<th style="background:#DCE6F1">원금</th>
			<th style="background:#DCE6F1">투자수익</th>
			<th style="background:#DCE6F1">이자소득세</th>
			<th style="background:#DCE6F1">지방소득세</th>
			<th style="background:#DCE6F1">원천세계</th>
			<th style="background:#DCE6F1">차감지급액</th>
			<th style="background:#DCE6F1">플랫폼이용료</th>
			<th style="background:#DCE6F1">부가세</th>
			<th style="background:#DCE6F1">플랫폼이용료계</th>
			<th style="background:#DCE6F1">세후금액</th>
			<th style="background:#DCE6F1">실지급액</th>
		</tr>
<?

	$list_b_count = count($LIST_B);
	$LIST_B_KEY = array_keys($LIST_B);

	for($i=0; $i<$list_b_count; $i++) {

		if($LIST_B_KEY[$i]=='1N')      $gubun = "개인-일반";
		else if($LIST_B_KEY[$i]=='2N') $gubun = "기업-일반";
		else if($LIST_B_KEY[$i]=='1C') $gubun = "개인-대부";
		else if($LIST_B_KEY[$i]=='2C') $gubun = "기업-대부";

?>
		<tr align="right" style="font-size:12px">
			<td colspan="4" style="background:#ddd;"></td>
			<td align="center"><?=$gubun?></td>
			<td><?=number_format($LIST_B[$LIST_B_KEY[$i]]['give_count'])?></td>
			<td><?=number_format($LIST_B[$LIST_B_KEY[$i]]['principal'])?></td>
			<td><?=number_format($LIST_B[$LIST_B_KEY[$i]]['invest_interest'])?></td>
			<td><?=number_format($LIST_B[$LIST_B_KEY[$i]]['interest_tax'])?></td>
			<td><?=number_format($LIST_B[$LIST_B_KEY[$i]]['local_tax'])?></td>
			<td><?=number_format($LIST_B[$LIST_B_KEY[$i]]['tax'])?></td>
			<td><?=number_format($LIST_B[$LIST_B_KEY[$i]]['after_tax_interest'])?></td>
			<td><?=number_format($LIST_B[$LIST_B_KEY[$i]]['fee_supply'])?></td>
			<td><?=number_format($LIST_B[$LIST_B_KEY[$i]]['fee_vat'])?></td>
			<td><?=number_format($LIST_B[$LIST_B_KEY[$i]]['fee'])?></td>
			<td><?=number_format($LIST_B[$LIST_B_KEY[$i]]['last_interest'])?></td>
			<td><?=number_format($LIST_B[$LIST_B_KEY[$i]]['last_amount'])?></td>
		</tr>
<?
		$PRINTSUM['give_count']         += $LIST_B[$LIST_B_KEY[$i]]['give_count'];
		$PRINTSUM['principal']          += $LIST_B[$LIST_B_KEY[$i]]['principal'];
		$PRINTSUM['invest_interest']    += $LIST_B[$LIST_B_KEY[$i]]['invest_interest'];
		$PRINTSUM['interest_tax']       += $LIST_B[$LIST_B_KEY[$i]]['interest_tax'];
		$PRINTSUM['local_tax']          += $LIST_B[$LIST_B_KEY[$i]]['local_tax'];
		$PRINTSUM['tax']                += $LIST_B[$LIST_B_KEY[$i]]['tax'];
		$PRINTSUM['after_tax_interest'] += $LIST_B[$LIST_B_KEY[$i]]['after_tax_interest'];
		$PRINTSUM['fee_supply']         += $LIST_B[$LIST_B_KEY[$i]]['fee_supply'];
		$PRINTSUM['fee_vat']            += $LIST_B[$LIST_B_KEY[$i]]['fee_vat'];
		$PRINTSUM['fee']                += $LIST_B[$LIST_B_KEY[$i]]['fee'];
		$PRINTSUM['last_interest']      += $LIST_B[$LIST_B_KEY[$i]]['last_interest'];
		$PRINTSUM['last_amount']        += $LIST_B[$LIST_B_KEY[$i]]['last_amount'];
	}
?>
		<tr align="right" style="font-size:12px;background:#F6F6F6;">
			<td colspan="4" style="font-size:12px;background:#ddd;"></td>
			<td align="center">합계</td>
			<td><?=number_format($PRINTSUM['give_count'])?></td>
			<td><?=number_format($PRINTSUM['principal'])?></td>
			<td><?=number_format($PRINTSUM['invest_interest'])?></td>
			<td><?=number_format($PRINTSUM['interest_tax'])?></td>
			<td><?=number_format($PRINTSUM['local_tax'])?></td>
			<td><?=number_format($PRINTSUM['tax'])?></td>
			<td><?=number_format($PRINTSUM['after_tax_interest'])?></td>
			<td><?=number_format($PRINTSUM['fee_supply'])?></td>
			<td><?=number_format($PRINTSUM['fee_vat'])?></td>
			<td><?=number_format($PRINTSUM['fee'])?></td>
			<td><?=number_format($PRINTSUM['last_interest'])?></td>
			<td><?=number_format($PRINTSUM['last_amount'])?></td>
		</tr>
<?
}
else {
?>
		<tr>
			<td colspan="20" align="center">데이터가 없습니다.</th>
		</tr>
<?
}
?>
		</form>
	</table>

<?

	$SMS_LOG = sql_fetch("SELECT COUNT(idx) AS cnt FROM batch_sms_send_log WHERE repay_schedule_date='".$sdate."'");
	$btnClassX3 = ($SMS_LOG['cnt'] > 0) ? 'btn-success' : 'btn-danger';

?>
	<div style="margin:10px 0 10px; text-align:right;">
		<button type="button" class="btn btn-sm <?=$btnClassX3?>" onClick="fSubmitX01('repaySmsSend');">원리금지급문자전송</button>
	</div>

	<div id="detailDiv"></div>

</div>

<script>
fSubmitX01 = function(arg) {
	if(arg=='repaySmsSend') {
		<? if($SMS_LOG['cnt'] > 0) echo ' if(!confirm("해당 지급예정일 상품의 투자자 문자발송 실행 이력이 '.$SMS_LOG['cnt'].'건 존재함.\n다시 발송하시겠습니까?")) { return; } '; ?>
		caption = "상품에 투자한 투자자에게 상환완료 문자를 발송합니다.\n(여러상품 투자자도 1건만 발송)\n\n기발송 체크 없이 실행되므로 발송현황을 확인한 후 신중이 실행 하십시요.";
		action_url = "/adm/repayment/sms_batch_proc.php?schedule_date=<?=$sdate?>";
	}

	checked_count = $("input[name='chk[]']:checked").length;
	if( checked_count > 0 ) {
		if(confirm('선택된 ' + checked_count + '개 ' + caption)) {
			var params = $("#frmX01").serialize();
			$.ajax({
				url: action_url,
				type: "post",
				data: params,
				dataType: "json",
				success: function(data) {
					alert(data.msg);
				},
				beforeSend: function() { loading('on'); },
				complete: function() { loading('off'); },
				error:function(e) {
					console.log(e);
					alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.");
				}
			});
		}
	}
	else {
		alert('선택된 데이터가 없습니다.');
	}
}

fSubmit = function() {
	f= document.frmSearch;
	if(f.sday.value=='') {
		f.action = "profit_give_stats.php";
	}
	f.submit();
}

moveToPos = function(divID) {
	var scrollPosition = $(divID).offset().top;
	$('html, body').animate({
		scrollTop: scrollPosition
	}, 300);
	return false;
}

detailView = function(arg1,arg2) {
	$('#detailDiv').css('display','none');
	$('#detailDiv').empty();
	$.ajax({
		url : "/adm/etc/ajax.profit_give_detail.php",
		type: "GET",
		data:{
			prd_idx: arg1,
			turn: arg2
		},
		success:function(data) {
			if(data!='') {
				$('#detailDiv').html(data);
				$('#detailDiv').slideDown();
				moveToPos('#listSumDiv');
			}
			else {
				alert('상세 내역이 없습니다.');
			}
		},
		beforeSend:function() { loading('on'); },
		complete:function() { loading('off'); },
		error:function (e) { console.log(e); alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
	});
}


$(document).ready(function() {
	$("input[id=chkall]").on('click', function() {
		$("input[name='chk[]']").prop('checked', this.checked);
	});
});


$('#excelDownButton').click(function() {
	if( confirm("Excel 문서로 다운로드 받으시겠습니까?") ) {
		var url = 'profit_give_download.php?syear=' + $('#syear').val() + '&smonth=' + $('#smonth').val() + '&sday=' + $('#sday').val();
		axFrame.location.replace(url);
	}
});

</script>

<?

include_once ('../admin.tail.php');

?>