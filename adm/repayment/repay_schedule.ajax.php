<?

set_time_limit(0);

include_once($_SERVER['DOCUMENT_ROOT'] . '/common.cli.php');


while(list($k, $v)=each($_REQUEST)) { ${$k} = @trim($v); }
//foreach($_REQUEST as $k=>$v) { ${$_REQUEST[$k]} = trim($v); }

//print_rr($_POST);


$where = "";
if(preg_match("/\,/", $prd_idx)) {
	$where = " AND idx IN(".$prd_idx.")";
}
else {
	$where = " AND idx = '".$prd_idx."'";
}

$PRD_IDX = explode(",", $prd_idx);
$product_count = count($PRD_IDX);

$TURN = explode(",", $turn);

?>

<label>지급예정 : <?=$schedule_date?>,  <?=number_format($product_count)?>개 상품</label>

<form id="frmX01" name="frmX01">
<table class="table-bordered table-striped table-hover" style="font-size:12px">
	<colgroup>
		<col style="width:2%">
		<col style="width:4%">
		<col style="width:4%">
		<col style="width:%">
		<col style="width:5%">
		<col style="width:7%">
		<col style="width:5%">
		<col style="width:7%">
		<col style="width:6%">
		<col style="width:6%">
		<col style="width:7%">
		<col style="width:6%">
		<col style="width:6%">
		<col style="width:7%">
		<col style="width:7%">
		<col style="width:7%">
		<col style="width:7%">
	</colgroup>
	<tr>
		<th rowspan="2"><input type="checkbox" id="chkall" style="padding:0;margin:0"></th>
		<th rowspan="2">NO</th>
		<th rowspan="2">품번</th>
		<th rowspan="2">상품명</th>
		<th rowspan="2">회차</th>
		<th rowspan="2">진행현황</th>
		<th rowspan="2">투자자수</th>
		<th rowspan="2">이자총액</th>
		<th colspan="3">원천징수</th>
		<th colspan="3">플랫폼이용료</th>
		<th colspan="3">실지급액</th>
	</tr>
	<tr>
		<th>소득세</th>
		<th>지방세</th>
		<th>합계</th>
		<th>공급가</th>
		<th>부가세</th>
		<th>합계</th>
		<th>이자</th>
		<th>원금</th>
		<th>합계</th>
	</tr>
	<tr align="right" style="background:#DDDDFF">
		<td align="center">-</td>
		<td align="center">합계</td>
		<td align="center" id="prdt_count"></td>
		<td colspan="3"></td>
		<td id="invest_count"></td>
		<td id="interest"></td>
		<td id="interest_tax" style="color:#AAA"></td>
		<td id="local_tax" style="color:#AAA"></td>
		<td id="tax"></td>
		<td id="fee_supply" style="color:#AAA"></td>
		<td id="fee_vat" style="color:#AAA"></td>
		<td id="fee"></td>
		<td id="last_interest"></td>
		<td id="principal"></td>
		<td id="last_amount"></td>
	</tr>

<?
for($i=0,$j=1; $i<$product_count; $i++,$j++) {
?>
	<tr align="right">
		<td id="zone<?=$j?>" align="center"></td>
		<td align="center"><?=$j?></td>
		<td id="idx<?=$j?>" align="center"></td>
		<td><div id="title<?=$j?>" align="left" style="padding:0;width:100%;height:14px;line-height:14px; overflow:hidden;"></div></td>
		<td id="turn<?=$j?>" align="center"></td>
		<td id="repay_state<?=$j?>" align="center"></td>
		<td id="invest_count<?=$j?>"></td>
		<td id="interest<?=$j?>"></td>
		<td id="interest_tax<?=$j?>" style="color:#999"></td>
		<td id="local_tax<?=$j?>" style="color:#999"></td>
		<td id="tax<?=$j?>"></td>
		<td id="fee_supply<?=$j?>" style="color:#999"></td>
		<td id="fee_vat<?=$j?>" style="color:#999"></td>
		<td id="fee<?=$j?>"></td>
		<td id="last_interest<?=$j?>"></td>
		<td id="principal<?=$j?>"></td>
		<td id="last_amount<?=$j?>"></td>
	</tr>
<?
}
?>
</table>
</form>

<?
if($product_count) {

	$INTEREST_LOG  = sql_fetch("SELECT COUNT(idx) AS cnt FROM batch_success_flag_log WHERE mode='receive_interest' AND repay_schedule_date='".$schedule_date."'");
	$btnClassX1 = ($INTEREST_LOG['cnt'] > 0) ? 'btn-success' : 'btn-danger';

	$PRINCIPAL_LOG = sql_fetch("SELECT COUNT(idx) AS cnt FROM batch_success_flag_log WHERE mode='receive_principal' AND repay_schedule_date='".$schedule_date."'");
	$btnClassX2 = ($PRINCIPAL_LOG['cnt'] > 0) ? 'btn-success' : 'btn-danger';

	$SMS_LOG = sql_fetch("SELECT COUNT(idx) AS cnt FROM batch_sms_send_log WHERE repay_schedule_date='".$schedule_date."'");
	$btnClassX3 = ($SMS_LOG['cnt'] > 0) ? 'btn-success' : 'btn-danger';

?>
<div style="margin-top:10px;text-align:right;">
	<button type="button" class="btn btn-sm btn-success" onClick="exelDownload();">엑셀 다운로드</button>
	<button type="button" class="btn btn-sm <?=$btnClassX1?>" onClick="fSubmitX01('receive_interest');">이자수급완료처리</button>
	<button type="button" class="btn btn-sm <?=$btnClassX2?>" onClick="fSubmitX01('receive_principal');">원금수급완료처리</button>
	<!--<button type="button" class="btn btn-sm <?=$btnClassX3?>" onClick="fSubmitX01('repaySmsSend');">원리금지급문자전송</button>-->
</div>

<script>
fSubmitX01 = function(arg) {

	if(arg=='receive_interest') {
		caption = "상품의 이자수급현황을 「완료」상태로 변경합니다. \n상환이자의 수급완료 처리되지 않은 상품만 변경 됩니다.";
		action_url = "repay_batch_proc.php?mode=" + arg + "&schedule_date=<?=$schedule_date?>";
	}
	else if(arg=='receive_principal') {
		caption = "상품의 원금수급현황을 「완료」상태로 변경합니다. \n상환원금의 수급완료 처리되지 않았으며, \n마지막 상환회차인 상품만 변경 됩니다.";
		action_url = "repay_batch_proc.php?mode=" + arg + "&schedule_date=<?=$schedule_date?>";
	}
	else if(arg=='repaySmsSend') {
		<? if($SMS_LOG['cnt'] > 0) echo ' if(!confirm("해당 지급예정일 상품의 투자자 문자발송 실행 이력이 '.$SMS_LOG['cnt'].'건 존재함.\n다시 발송하시겠습니까?")) { return; } '; ?>
		caption = "상품에 투자한 투자자에게 상환완료 문자를 발송합니다.\n(여러상품 투자자도 1건만 발송)\n\n기발송 체크 없이 실행되므로 발송현황을 확인한 후 신중이 실행 하십시요.";
		action_url = "sms_batch_proc.php?schedule_date=<?=$schedule_date?>";
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
</script>
<?
} //end if($product_count)
?>

<script>
loadRepayData = function(prd_idx, schedule_turn, schedule_date, no) {

	loading('on');

	$.ajax({
		url : "repay_schedule_detail.ajax.php?no=" + no,
		type: "post",
		dataType: "json",
		data: {
			prd_idx: prd_idx,
			turn: schedule_turn,
			date: schedule_date
		},
		success:function(data) {
			if(data.idx) {

				sVal = data.idx + '-' + data.taret_turn;

				checkbox_tag = '<input type="checkbox" id="chk'+ no + '" name="chk[]" value="' + sVal + '">';
				product_link = '<a href="/adm/product/product_form.php?idx='  + data.idx + '" target="_blank">'  + data.idx + '</a>';
				repay_link   = '<a href="/adm/repayment/repay_calculate.php?idx='  + data.idx + '" target="_blank">'  + data.turn + '</a>';

				$('#zone'+no).html(checkbox_tag);
				$('#idx'+no).html(product_link);
				$('#title'+no).html(data.title);
				$('#title'+no).attr('title', data.alt_data);

				$('#turn'+no).html(repay_link);
				$('#repay_state'+no).html(data.repay_state);
			//$('#invest_return'+no).html(data.invest_return);
				$('#invest_count'+no).html(number_format(data.invest_count));
				$('#interest'+no).html(number_format(data.interest));
				$('#interest_tax'+no).html(number_format(data.interest_tax));
				$('#local_tax'+no).html(number_format(data.local_tax));
				$('#tax'+no).html(number_format(data.tax));
				$('#fee_supply'+no).html(number_format(data.fee_supply));
				$('#fee_vat'+no).html(number_format(data.fee_vat));
				$('#fee'+no).html(number_format(data.fee));
				$('#last_interest'+no).html(number_format(data.last_interest));
				$('#principal'+no).html(number_format(data.principal));
				$('#last_amount'+no).html(number_format(data.last_amount));

				prdt_count    = Number($('#prdt_count').html().replace(/\,/gi, "")) + 1;
				invest_count  = Number($('#invest_count').html().replace(/\,/gi, "")) + Number(data.invest_count);
				interest      = Number($('#interest').html().replace(/\,/gi, "")) + Number(data.interest);
				interest_tax  = Number($('#interest_tax').html().replace(/\,/gi, "")) + Number(data.interest_tax);
				local_tax     = Number($('#local_tax').html().replace(/\,/gi, "")) + Number(data.local_tax);
				tax           = Number($('#tax').html().replace(/\,/gi, "")) + Number(data.tax);
				fee_supply    = Number($('#fee_supply').html().replace(/\,/gi, "")) + Number(data.fee_supply);
				fee_vat       = Number($('#fee_vat').html().replace(/\,/gi, "")) + Number(data.fee_vat);
				fee           = Number($('#fee').html().replace(/\,/gi, "")) + Number(data.fee);
				last_interest = Number($('#last_interest').html().replace(/\,/gi, "")) + Number(data.last_interest);
				principal     = Number($('#principal').html().replace(/\,/gi, "")) + Number(data.principal);
				last_amount   = Number($('#last_amount').html().replace(/\,/gi, "")) + Number(data.last_amount);

				$('#prdt_count').html(number_format(prdt_count));
				$('#invest_count').html(number_format(invest_count));
				$('#interest').html(number_format(interest));
				$('#interest_tax').html(number_format(interest_tax));
				$('#local_tax').html(number_format(local_tax));
				$('#tax').html(number_format(tax));
				$('#fee_supply').html(number_format(fee_supply));
				$('#fee_vat').html(number_format(fee_vat));
				$('#fee').html(number_format(fee));
				$('#last_interest').html(number_format(last_interest));
				$('#principal').html(number_format(principal));
				$('#last_amount').html(number_format(last_amount));

				delete(data);

			}
		},
		error:function (e) {
			console.log(e);
			alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.");
		}
	});

	if(no == <?=$product_count?>) { loading('off'); }

}

function exelDownload() {

	var ht =$('#frmX01').html();
	console.log(ht);
	ht2 = ht.replace(/checkbox/gi, 'checkboxa');
	ht2 = ht2.replace(/ \/ /gi, '//');
	window.open('data:application/vnd.ms-excel; charset=utf-8;,'+encodeURIComponent(ht2));
	return;

}

$(document).ready(function() {
<? for($i=0,$j=1; $i<$product_count; $i++,$j++) { ?>
	setTimeout(function() { loadRepayData('<?=$PRD_IDX[$i]?>', '<?=$TURN[$i]?>', '<?=$schedule_date?>', <?=$j?>); }, <?=($i*500)?>);
<? } ?>
});

$(document).ready(function() {
	$("input[id=chkall]").on('click', function() {
		$("input[name='chk[]']").prop('checked', this.checked);
	});
});
</script>

<?

sql_close();
exit;

?>