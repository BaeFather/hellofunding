<?
/*
2017-04-24 : 개인회원 상품별 금액 제한 관련 내용 추가
2017-07-19 : 사전투자 설정
*/
?>
<!-- 본문내용 START -->

<div id="content">
	<!--div class="location"><span><a href="<?=G5_URL?>/investment/invest_list.php">투자하기</a></span><b class="blue"><?=($is_advance_invest=='Y')?'사전':'';?>투자설정</b></div-->

	<div class="content invest_detail">

		<form method="post" name="frm" id="frm">
			<input type="<?=($_COOKIE['debug_mode'])?'text':'hidden';?>" name="prd_idx"                id="prd_idx"                 value="<?=$prd_idx?>">
			<input type="<?=($_COOKIE['debug_mode'])?'text':'hidden';?>" name="advance"                id="advance"                 value="<?=$advance?>">
			<input type="<?=($_COOKIE['debug_mode'])?'text':'hidden';?>" name="ajax_invest_value"      id="ajax_invest_value"       value="">
			<input type="<?=($_COOKIE['debug_mode'])?'text':'hidden';?>" name="need_recruit_amount"    id="need_recruit_amount"     value="<?=$need_recruit_amount?>">
			<input type="<?=($_COOKIE['debug_mode'])?'text':'hidden';?>" name="invest_possible_amount" id="invest_possible_amount"  value="0"><!--<?=$invest_possible_amount?>//-->
			<input type="<?=($_COOKIE['debug_mode'])?'text':'hidden';?>" name="balance_value"          id="balance_value"           value="<?=$member["mb_point"]?>">
			<input type="<?=($_COOKIE['debug_mode'])?'text':'hidden';?>" name="min_invest_limit"       id="min_invest_limit"        value="<?=$min_invest_limit?>">
			<input type="<?=($_COOKIE['debug_mode'])?'text':'hidden';?>" name="max_invest_limit"       id="max_invest_limit"        value="<?=$max_invest_limit?>">
		</form>

		<h2 class="big"><?=($is_advance_invest=='Y')?'<span class="red">[사전투자]</span> ':'';?><?=$PRDT["title"]?></h2>

		<h3><!--<span class="normal">(<?=$PRDT["total_invest_count"]?>명)</span>--></h3>
		<div class="rate">
			<img id="progress_bar" src="/images/investment/rate_blue.gif" alt="진행률" style="width:<?=$product_invest_percent?>%">
			<b class="percent">0%</b>
			<b class="percent02" id="progress_data"><?=$product_invest_percent?>%</b>
		</div>
		<p>&nbsp;</p>
		<div class="my_invest">
			<? if($member['va_bank_code'] && $member['va_private_name'] && $member['virtual_account']) { ?>
			▣ 나의 가상계좌 : <span style="font-weight:bold; color:#4A6FE2"><?=$VBANK[$member['va_bank_code2']]." &nbsp ".$member['virtual_account2']?></span><br>
			&nbsp;&nbsp; <span style="color:#FF2222;font-size:12px">&gt;&gt;&gt; 가상계좌에 예치금을 입금한 후 투자해주세요.</span>
			<? } ?>
			<table border="0" style="width:98%">
				<tr style="border-top:1px solid #aaa;border-bottom:1px solid #ddd;">
					<th style="width:100px;background:#fafafa">나의 예치금</th>
					<td align="right" style="padding-right:10px;font-weight:bold;color:#EE1D1D"><span id="realtime_point" class="price"><?=number_format($member["mb_point"])?></span>원</td>
				</tr>
				<tr style="border-top:1px solid #eee; border-bottom:1px solid #ddd;">
					<th style="background:#fafafa">목표금액</th>
					<td align="right" style="padding-right:10px"><span style="font-weight:bold;color:#284893"><?=$print_recruit_amount?></span></td>
				</tr>
				<tr style="border-top:1px solid #eee; border-bottom:1px solid #ddd;">
					<th style="background:#fafafa">모집금액</th>
					<td align="right" style="padding-right:10px"><span id="total_invest_amount_k" style="font-weight:bold;color:#284893"><?=price_cutting($PRDT['total_invest_amount'])?>원</span></td>
				</tr>
<?
if(G5_TIME_YMD >= $CONF['online_invest_policy_sdate']) {
	if( $member['member_type']=='2' || ($member['member_type']=='1' && $member['member_investor_type']=='3') ) {
?>
				<tr style="border-top:1px solid #eee; border-bottom:1px solid #ddd;">
					<th style="background:#fafafa">상품투자한도</th>
					<td align="right" style="padding-right:10px"><span style="font-weight:bold;color:#284893"><?=price_cutting((floor($PRDT['recruit_amount']*$INDI_INVESTOR['3']['invest_able_perc'])/10000)*10000)?>원</span></td>
				</tr>
<?
	}
}
?>
				<tr style="border-top:1px solid #eee; border-bottom:1px solid #ddd;">
					<th style="background:#fafafa">잔여 모집금액</th>
					<td align="right" style="padding-right:10px"><span id="need_recruit_amount_k" style="font-weight:bold;color:#284893"><?=price_cutting($need_recruit_amount)?>원</span></td>
				</tr>
				<tr style="border-top:1px solid #eee; border-bottom:1px solid #ddd;">
					<th style="background:#fafafa">투자가능금액</th>
					<td align="right" style="padding-right:10px"><span id="invest_possible_amount_k" style="font-weight:bold;color:green">>>> 로딩중</span></td><!-- <?=price_cutting($invest_possible_amount)?>원 -->
				</tr>
			</table>
			<p align="center" style="padding-top:14px">
			<b>투자금액</b>
			<input type="text" class="text" id="invest_value" name="invest_value" DISABLED placeholder="0" maxlength="9" onKeyUp="NumberFormat(this);" style="width:150px;border:4px solid #284893;text-align:right;"> 만원<br>

			(<span class="blue" id="invest_value_text">0</span>원)
			</p>

<? if( $member['member_type']=='1' && in_array($member['member_investor_type'], array('1','2')) ) { ?>
			<ul style="clear:both;display:inline-block;padding:10px 0 0; font-size:14px;">
				<li style="float:left;">▣ 동일 대출자상품 투자내역</li>
				<li style="float:left; margin-left:10px; color:brown;font-size:11px">* 개인(<?=$INDI_INVESTOR[$member['member_investor_type']]['title']?>)의 경우 동일 대출자에게는 <strong><?=price_cutting($INDI_INVESTOR[$member['member_investor_type']]['group_product_limit'])?>원</strong>까지만 투자가 가능합니다.</li>
			</ul>
			<table align="center" style="width:98%">
				<colgroup>
					<col style="width:70%">
					<col style="width:30%">
				</colgroup>
				<tr>
					<th style="background:#EFEFEF;border-top:2px solid #284893">상품명</th>
					<th style="background:#EFEFEF;border-top:2px solid #284893">투자금액</th>
				</tr>
<?
	if($g_invest_count) {
		for($i=0; $i<$g_invest_count; $i++) {
?>
				<tr>
					<td style="padding:0 5px;border-bottom:1px solid #ccc"><?=$GINVEST[$i]['title']?></td>
					<td style="padding:0 5px;border-bottom:1px solid #ccc" align='right'><?=number_format($GINVEST[$i]['amount'])?>원</td>
				</tr>
<?
		}
	}
	else {
?>
				<tr>
					<td colspan="2" align="center" style="border-bottom:1px dotted #ccc">투자내역이 없습니다.</td>
				</td>
<?
	}
?>
			</table>
<?
}
?>

		</div>

		<!--<h3>이용약관</h3>-->
		<div class="textarea">
			<div class="agree">
				<label style="padding:5px 10px 5px 0;">
				  <a href="<?=G5_URL?>/bbs/content.php?co_id=provision2" target="_blank" class="blue2">투자이용약관</a>에 동의합니다. &nbsp;
					<input type="checkbox" id="guide" value="Y" checked='checked'>
				</label>
			</div>
		</div>

		<div class="btnArea mt40">
			<?=$invest_button?>
		</div>

	</div>
</div>

<div id="complete" class="detail">
	<img src="../images/btn_close.gif" alt="close" class="close">
	<div class="title">예치금 투자 진행</div>
	<div class="text">예치금으로 투자를 진행하시겠습니까?</div>
	<span id="yes" class="btn_big_blue" style="width:40%">확인</span> &nbsp;
	<span id="no" class="btn_big_link" style="width:40%">취소</span>
</div>

<div id="complete2" class="detail">
	<div class="title">투자완료</div>
	<div class="text">
		<span class="blue"><?=$member["mb_name"]?></span>고객님<br>
		<span class="blue"><?=$PRDT["title"]?></span>에<br>
		<span class="blue" id="value_text_show">{투자금액}</span>원 투자가 완료되었습니다.<br>
	</div>
	<a href="/deposit/deposit.php"><span class="btn_big_blue" style="width:90%">투자내역확인</span></a>
</div>

<div id="complete3" class="detail">
	<img src="../images/btn_close.gif" alt="close" class="close">
	<div class="title">예치금 계좌 발급</div>
	<div class="text">
		예치금 계좌를 발급 받지 않았습니다.<br>
		예치금 계좌 발급 후 투자를 진행해 주세요.
	</div>
	<a href="/deposit/deposit.php" id="main" class="btn_big_blue">발급받기</a> &nbsp;
	<span id="no" class="btn_big_link">취소</span>
</div>

<script>
$(document).ready(function() {

	setTimeout(function(){ajax_investment('<?=$member['mb_id']?>');}, Math.random()*1000);

	var tmr = setInterval(function() {
		var mb_id_js = "";

		$.ajax({
			type: "POST",
			url: "/sscheck.php",
			dataType: "json",
			success: function(data) {
				if (data.mb_id) mb_id_js = data.mb_id;
				ajax_investment(mb_id_js);
				ajax_point_check(mb_id_js);
			},
			error: function(e) { ajax_investment(mb_id_js); }
		});

	}, 5*1000);

	function ajax_investment(mb_id_js) {

		$.ajax({
			type: "POST",
			url: "<?=API_URL?>/investment/ajax_investment.php",
			dataType: "json",
			data: {"prd_idx":<?=$prd_idx;?>, "mb_id": mb_id_js, advance:'<?=$advance?>'},
			success: function(json) {
				//console.log(json);
				// 투자금입력란 활성화
				if( $("#invest_value").attr("disabled") ) {
					$('#invest_value').attr('DISABLED', false);
					$('#invest_value').focus();
				}
				$('#need_recruit_amount').val(json.data.need_recruit_amount);
				$('#invest_possible_amount').val(json.data.invest_possible_amount);
				$('#progress_data').html(json.data.progress);
				$('#progress_bar').attr('style', "width:" + json.data.progress_width);
				$('#invest_possible_amount_k').html(json.data.invest_possible_amount_k);
				$('#need_recruit_amount_k').html(json.data.need_recruit_amount_k);
				$('#total_invest_amount_k').html(json.data.total_invest_amount_k);
			},
			error: function(e) {

			}
		});

	}

	function ajax_point_check(mb_id_js) {

		$.ajax({
			type: "POST",
			data: {"mb_id": mb_id_js},
			url : "<?=API_URL?>/deposit/ajax_point_check.php",
			success: function(data) {
				//console.log(data);
				if ($('#ajax_return_txt').length) $('#ajax_return_txt').val(data);
				// 단순출력항목
				if ($('#realtime_point').length) {
					$('#realtime_point').empty();
					$('#realtime_point').append(number_format(data));
				}
				// 변환불가항목
				if ($('#now_point').length) {
					$('#now_point').empty();
					$('#now_point').val(data);
				}
				// 실보유예치금 갱신
				if ($('#balance_value').length) {
					$('#balance_value').empty();
					$('#balance_value').val(data);
				}
			},
			error: function(e) {

			}
		});

	}

/*
	setInterval(function() {
		$.ajax({
			type: "GET",
			url: "/investment/ajax_investment.php",
			dataType: "json",
			data: {prd_idx:'<?=$prd_idx?>', advance:'<?=$advance?>'},
			success: function(json) {

				// 투자금입력란 활성화
				if( $("#invest_value").attr("disabled") ) {
					$('#invest_value').attr('DISABLED', false);
					$('#invest_value').focus();
				}

				$('#need_recruit_amount').val(json.data.need_recruit_amount);
				$('#invest_possible_amount').val(json.data.invest_possible_amount);
				$('#progress_data').html(json.data.progress);
				$('#progress_bar').attr('style', "width:" + json.data.progress_width);
				$('#invest_possible_amount_k').html(json.data.invest_possible_amount_k);
				$('#need_recruit_amount_k').html(json.data.need_recruit_amount_k);
				$('#total_invest_amount_k').html(json.data.total_invest_amount_k);
			},
			error: function(e) { }
		});
	}, 3*1000);
*/
});

// 팝업 닫기
$('#complete #no, #complete .close, #complete2 .close, #complete3 .close, #complete3 #no').click(function() {
	$.unblockUI();
	return false;
});

btn_event = function(arg) {
	if(arg=='send') {
		$('#yes').removeClass('btn_big_blue').addClass('btn_big_gray');
		$('#yes').text('전송중 >>>');
		$('#yes').attr('disabled', 'disabled');
	}
	else if(arg=='exit') {
		$('#yes').removeAttr('disabled');
		$('#yes').text('확인');
		$('#yes').removeClass('btn_big_gray').addClass('btn_big_blue');
	}
	else {
		return;
	}
}

// 레이어 팝업 = 확인 클릭시
$('#yes').on('click', function() {

	ajax_data = $("#frm").serialize();
	$.ajax({
		url : "./investment_proc.php",
		type: "POST",
		data : ajax_data,
		beforeSend: function() { btn_event('send'); },
		success: function(data) {
			$('#ajax_return_txt').val(data);
			if(data=="SUCCESS" || data=="SUCCESS:ADVANCE_INVEST") {
				$.unblockUI();
				setTimeout(
					function() {
						alert('투자가 성공 하였습니다.');
						window.location.replace('/deposit/deposit.php');
					}, 0.2*1000
				);
				/*
				// 투자 성공 메세지창 출력
				$.blockUI({
					message: $('#complete2'),
					css: { border:'0', cursor:'default', top:'6%', left:'1%', width:'98%' }
				});
				*/
			}
			else {
				if(data=="ERROR:DATA")                       { alert("전송데이터에 오류가 있습니다. 잠시 후 다시 실행하여 주십시요."); window.location.replace('/investment/invest_list.php'); }
				else if(data=="ERROR:LOGIN")                 { window.location.replace('/bbs/login.php'); }
				else if(data=="ERROR:DATE")                  { alert("투자 기간이 아닙니다."); }
				else if(data=="ERROR:INVEST_END")            { alert("본상품의 투자모집이 완료되어 투자를 진행 하실 수 없습니다."); }
				else if(data=="ERROR:BALANCE")               { if(confirm('예치금 계좌를 발급받지 않았습니다.\n예치금 계좌를 발급 받으시겠습니까?')) { window.location.href="/deposit/deposit.php"; } }
				else if(data=="ERROR:INVEST")                { alert("투자 가능한 금액을 초과 입력 하셨습니다."); }
				else if(data=="ERROR:MIN_PRICE")             { alert("투자 최소 금액 미만 입니다. 투자금액을 확인해 주세요."); $("input[name='invest_value']").focus(); }
				else if(data=="ERROR:MAX_PRICE")             { alert("투자 최대 금액을 초과 하였습니다. 투자금액을 확인해 주세요. "); $("input[name='invest_value']").focus(); }
				else if(data=="ERROR:MAX_INVEST_AMOUNT_OVER"){ alert("투자 최대가능 한도액을 초과 하였습니다.\n전문투자자 최대투자가능 한도액 : <?=$pro_max_invest_amount?>원"); $("input[name='invest_value']").focus(); }
				else if(data=="ERROR:GROUP_INVEST_AMOUNT_LIMITED") { alert("동일차주상품 투자한도금액을 초과 하였습니다."); $("input[name='invest_value']").focus(); }
				else if(data=="ERROR:INVEST_AMOUNT_LIMITED") { alert("전체 상품에 대한 투자가능금액이 초과되어 투자를 진행 하실 수 없습니다."); }
				else if(data=="ERROR:ADVANCE_INVEST_AMOUNT") { alert("사전 투자 가능한 금액을 초과 입력 하셨습니다."); }
				else if(data=="ERROR:MONEY_UNIT")            { alert("투자금액중 만원 미만 단위 금액이 확인되어 투자를 진행 하실 수 없습니다."); }
				else if(data=="ERROR:ADVANCE_INVEST_DATE")   { alert("사전 투자 가능 기간이 종료 되었습니다."); window.location.replace('/investment/investment.php?prd_idx=<?=$prd_idx?>'); }
				else if(data=="ERROR:ADVANCE_INVEST_END")    { alert("사전투자가 마감 되었습니다."); window.location.replace('/investment/investment.php?prd_idx=<?=$prd_idx?>'); }
				else if(data=="ERROR:CHECKED_LIMIT_MEMBER")  { alert("헬로펀딩과 고객님간의 협의에 의해 투자가 제한된 계정 입니다."); window.location.replace('/investment/investment.php?prd_idx=<?=$prd_idx?>'); }
			//else if(data=="ERROR:INVESTEDFROM_FINNQ")    { alert("타사 앱(핀크)을 통한 투자내역이 존재하여 추가 투자가 불가합니다."); }
			//else if(data=="ERROR:INVESTEDFROM_OLIGO")    { alert("타사 앱(올리고)을 통한 투자내역이 존재하여 추가 투자가 불가합니다."); }
			//else if(data=="ERROR:INVESTEDFROM_KAKAOPAY") { alert("타사 앱(카카오페이)을 통한 투자내역이 존재하여 추가 투자가 불가합니다."); }
				else if(data=="ERROR:P2PCTR_PAUSE")          { alert("투자가능시간이 아닙니다.\n중앙기록관리기관 점검 시간(23:20~00:40)에는 투자 신청 및 취소, 한도 조회가 불가능합니다."); window.location.replace('/investment/investment.php?prd_idx=<?=$prd_idx?>'); }
				else if(data=="ERROR:P2PCTR_UPDATE_FAIL")    { alert("중앙기록관리 투자한도 업데이트 오류로 투자가 되지 않습니다.\n잠시 후 다시 시도하십시요."); window.location.replace('/investment/investment.php?prd_idx=<?=$prd_idx?>'); }
				else if(data=="ERROR:P2PCTR_FAIL_CANCEL")    { alert("중앙기록관리 전송 오류!\n잠시 후 다시 시도하십시요."); }
				else if(data=="ERROR:BANK_PAUSE")            { alert("금융기관(신한은행) 점검 시간입니다. 투자 신청 및 취소, 한도 조회가 불가능합니다."); window.location.replace('/investment/investment.php?prd_idx=<?=$prd_idx?>'); }
				else if(data=="ERROR:DUPLICATE_INVEST")      { alert("동일한 금액의 중복 투자건으로 의심되는 투자요청 입니다.\n잠시 후 다시 시도하십시요."); }
				else if(data=="ERROR:INVEST_AMOUNT_LIMITED_PRPT") { alert("부동산 상품에 대한 투자가능금액이 초과되어 투자를 진행 하실 수 없습니다."); }
				else if(data=="KYC_START" || data=="KYC_ING" )	{ KYCPopup(); }
				else if(data=="ERROR:ING_PROCESS")           { alert("이전 투자건에 대한 처리가 진행중입니다."); }
				else { alert(data); }

				btn_event('exit');
				$.unblockUI();
				return;
			}
		},
		complete: function() { btn_event('exit'); },
		error: function(e) { alert('네트워크 에러 입니다. 잠시 후 다시 시도 하십시요.'); return; }
	});

});


function price_cutting(val) {

	var unit_price        = 10000;
	var invest_value_str  = '';
	var million_value     = 0;
	var invest_real_value = 0;

	invest_value = parseInt(parseInt(val) / unit_price) * unit_price;

	if(invest_value >= 100000000) {
		million_value     = parseInt(invest_value / 100000000);
		invest_value_str  = String(million_value) + '억';
		invest_value      = invest_value - (million_value * 100000000);
		invest_real_value = (million_value * 100000000);
	}

	if(invest_value > 0) {
		invest_value      = Math.floor(invest_value / unit_price);
		invest_real_value = invest_real_value+ (invest_value * unit_price);
		invest_value_str  = invest_value_str + Number_Format(String(invest_value)) + '만';
	}
	else{
		if(invest_value_str == '') invest_value_str = '0';
	}

	return invest_value_str;

}

var pattern = /^[0-9]+$/;
$(document).ready(function(){
	$("input[name='invest_value']").keyup(function(evt){

		// 숫자단위 쉽표 제거
		var invest_value = $("input[name='invest_value']").val();
		var invest_value_len = invest_value.length;
		for (i=0; i<invest_value_len; i++) {
			invest_value = invest_value.replace(',', '');
		}

		var unit_price        = 10000;
		var invest_value_str  = '';
		var million_value     = 0;
		var invest_real_value = 0;

		invest_value = Number(invest_value) * unit_price;

		if(invest_value > 0) {
			invest_value = parseInt(parseInt(invest_value) / unit_price) * unit_price;

			if(invest_value >= 100000000) {
				million_value     = parseInt(invest_value / 100000000);
				invest_value_str  = String(million_value) + '억';
				invest_value      = invest_value - (million_value * 100000000);
				invest_real_value = (million_value * 100000000);
			}

			if(invest_value > 0) {
				invest_value      = Math.floor(invest_value / unit_price);
				invest_real_value = invest_real_value+ (invest_value * unit_price);
				invest_value_str  = invest_value_str + Number_Format(String(invest_value)) + '만';
			}
			else {
				if(invest_value_str == '') invest_value_str = '0';
			}
		}
		else {
			invest_value_str = '0';
		}

	  $("input[name='ajax_invest_value']").val(invest_real_value);
		$("#invest_value_text").text(invest_value_str);
	});

	$("#invest_value").keyup(function(){ $(this).val( $(this).val().replace(/[^0-9]/g,"") ); });


	$("#btn_vacs").click(function(evt){
		$.blockUI({
			message: $('#complete3'),
			css: { border:0, cursor:'default', top:'6%', left:'1%', width:'98%' }
		});
	});

	$("#btn_invest").click(function(evt){
		var unit_price             = 10000;
		var invest_value           = 0;
		var min_invest_limit       = 0;
		var max_invest_limit       = 0;
		var invest_possible_amount = 0;
		var balance_value          = 0;

		var min_invest_limit = "";
		var max_invest_limit = "";

		min_invest_limit = Number($("input[name='min_invest_limit']").val());
		max_invest_limit = Number($("input[name='max_invest_limit']").val());

		// 숫자단위 쉽표 제거
		invest_value = $("input[name='invest_value']").val();
		invest_value_len = invest_value.length;
		for (i=0; i<invest_value_len; i++) {
			invest_value = invest_value.replace(',', '');
		}

		if(invest_value=="" || invest_value=="0") { alert("투자금액을 입력해주세요"); $("input[name='invest_value']").focus(); return; }

		invest_possible_amount = Number($("input[name='invest_possible_amount']").val());
		balance_value = Number($("input[name='balance_value']").val());
		invest_value  = Number(invest_value);
		invest_value  = invest_value * unit_price;

		if(invest_possible_amount==0) { alert("투자가능금액이 없습니다."); return; }
		if(invest_possible_amount < invest_value) { alert('투자가능금액을 초과 입력 하셨습니다.'); return; }
	//if(invest_possible_amount < invest_value) { alert('투자가능금액을 초과 입력 하셨습니다.\n\n - 현재 투자가능금액:    ' + number_format(invest_possible_amount) + '원'); return; }
		if($("input[name='ajax_invest_value']").val() < min_invest_limit) { alert("최소투자금액은 " + min_invest_limit + "원 입니다."); $("input[name='invest_value']").focus(); return; }
		if(max_invest_limit != '') { if($("input[name='ajax_invest_value']").val() > max_invest_limit) { alert("투자 최대 금액은 " + max_invest_limit + "원 입니다."); $("input[name='invest_value']").focus(); return; } }

		// 예치금-투자금액 비교
		if(balance_value < invest_value) {
			alert('예치금이 부족합니다. \n\n' +
			      ' - 투자 하실 금액 :   ' + price_cutting(invest_value) + '원\n' +
			      ' - 예치금 잔액 :   ' + price_cutting(balance_value) + '원\n\n' +
			      '가상계좌에 예치금을 입금한 뒤 투자해주세요');
			return;
		}

		if( $("input:checkbox[id='guide']").is(":checked")==false ) { $("input:checkbox[id='guide']").focus(); alert("이용약관에 동의해주세요"); return; }

		$("#value_text_show").text($("#invest_value_text").text());

		$.blockUI({
			message: $('#complete'),
			css: { border:0, cursor:'default', top:'6%',left:'1%', width:'98%' }
		});

	});
});

function Number_Format(fn){
	var str = fn;
	var Re = /[^0-9]/g;
	var ReN = /(-?[0-9]+)([0-9]{3})/;
	str = str.replace(Re,'');
	while (ReN.test(str)) {
		str = str.replace(ReN, "$1,$2");
	}
	return str;
}
</script>

<script type="text/javascript">
//실시간 포인트 갱신
/*
$(document).ready(function(){
	setInterval(function() {
		$.ajax({
			url : "/deposit/ajax_point_check.php",
			success: function(data) {
				$('#ajax_return_txt').val(data);
				// 단순출력항목
				$('#realtime_point').empty();
				$('#realtime_point').append(number_format(data));
        // 변환불가항목
				$('#now_point').empty();
				$('#now_point').val(data);
				// 실보유예치금 갱신
				$('#balance_value').empty();
				$('#balance_value').val(data);
			}
		});
	}, 3 * 1000);
});
*/
</script>

<!-- 본문내용 E N D -->
<?
if ($co['co_include_tail'])
    @include_once($co['co_include_tail']);
else
    include_once('./_tail.php');
?>