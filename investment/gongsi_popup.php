<?
	// 이전 월 말일
	$date = date("Y.m");
	$d = mktime(0,0,0, date("m"), 1, date("Y"));
	$prev_date = strtotime("-1 month", $d);
	$tmp_date = date('Y-m-t', $prev_date);
	$ym = str_replace('-', '.', substr($tmp_date, 0, 7));
?>

<div id="gongsiPop">
	<div class="inner-wrap">
	<h3>법정공시정보 필수 확인</h3>
	<p class="date-txt"><?=$ym." 말일 기준"?></p>
	<h4>투자현황</h4>
	<div class="tb-wrap">
		<table class="gongsi-data">
			<colgroup>
				<col style="width:33.3%">
				<col style="width:33.3%">
				<col style="width:33.3%">
			</colgroup>
			<thead>
				<tr>
					<th>누적 대출금액</th>
					<th>대출잔액</th>
					<th>연체율 <span>(%)</span></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><div id="tot_amt" class="tot_amt"></div></td>
					<td><div id="tot_remain" class="tot_remain"></div></td>
					<td><div id="overdue_rate" class="overdue_rate"></div></td>
				</tr>
			</tbody>
		</table>
		<table class="gongsi-data">
			<colgroup>
				<col style="width:33.3%">
				<col style="width:33.3%">
				<col style="width:33.3%">
			</colgroup>
			<thead>
				<tr>
					<th>연체건수 <span>(건)</span></th>
					<th>누적 자기계산 투자현황</th>
					<th>자기계산 투자현황</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><div id="overdue_cnt" class="overdue_cnt"></div></td>
					<td><div id="hello_nujuk_invest_amt" class="hello_nujuk_invest_amt"></div></td>
					<td><div id="hello_live_invest_amt" class="hello_live_invest_amt"></div></td>
				</tr>
			</tbody>
		</table>
	</div>

	<h4>유형별 투자현황</h4>
	<div class="tb-wrap">
		<table class="type-invest">
			<thead>
				<tr>
					<th>상품 유형</th>
					<th style="text-align: right; width: 20%;">누적 대출금액 <span>(원)</span></th>
					<th style="text-align: right; width: 27%;">대출잔액 <span>(원)</span></th>
					<th>연체율 <span>(%)</span></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th>부동산 PF</th>
					<td><div id="pf_tot_amt" class="pf_tot_amt"></div></td>
					<td><div id="pf_tot_remain" class="pf_tot_remain"></div></td>
					<td><div id="pf_overdue_rate" class="pf_overdue_rate"></div></td>
				</tr>
				<tr>
					<th>주택 담보</th>
					<td><div id="mgg_tot_amt" class="mgg_tot_amt"></div></td>
					<td><div id="mgg_tot_remain" class="mgg_tot_remain"></div></td>
					<td><div id="mgg_overdue_rate" class="mgg_overdue_rate"></div></td>
				</tr>
				<tr>
					<th>매출채권</th>
					<td><div id="hp_tot_amt" class="hp_tot_amt"></div></td>
					<td><div id="hp_tot_remain" class="hp_tot_remain"></div></td>
					<td><div id="hp_overdue_rate" class="hp_overdue_rate"></div></td>
				</tr>
				<tr>
					<th>동산</th>
					<td><div id="mvb_tot_amt" class="mvb_tot_amt"></div></td>
					<td><div id="mvb_tot_remain" class="mvb_tot_remain"></div></td>
					<td><div id="mvb_overdue_rate" class="mvb_overdue_rate"></div></td>
				</tr>
				<tr class="total">
					<th>합계</th>
					<td><div id="all_tot_amt" class="all_tot_amt"></div></td>
					<td><div id="all_tot_remain" class="all_tot_remain"></div></td>
					<td><div id="all_overdue_rate" class="all_overdue_rate"></div></td>
				</tr>
			<tbody>
		</table>
	</div>

	<a href="/company/status/status.php" class="gongsi-view">법정공시정보 전체 보기</a>
	<p>법정공시정보의 확인은 이용자의 권리보호에 도움이 됩니다.</p>
	<div class="day-close-box">
		<input type="checkbox" id="dayClose" name="dayClose" /><label for="dayClose">오늘 하루 보지 않기</label>
	</div>
	<button id="gongsiChk">확인</button>
	</div>
</div>


<script type="text/javascript">


function numberFormat(x) {
	return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function numberToKorean(number){
    var inputNumber  = number < 0 ? false : number;
    var unitWords    = ['', '만', '억', '조', '경'];
    var splitUnit    = 10000;
    var splitCount   = unitWords.length;
    var resultArray  = [];
    var resultString = '';

    for (var i = 0; i < splitCount; i++){
        var unitResult = (inputNumber % Math.pow(splitUnit, i + 1)) / Math.pow(splitUnit, i);
        unitResult = Math.floor(unitResult);
        if (unitResult > 0){
            resultArray[i] = unitResult;
        }
    }

    for (var i = 0; i < resultArray.length; i++){
        if(!resultArray[i]) continue;
        resultString = " " + String(numberFormat(resultArray[i])) + unitWords[i] + resultString;
    }

    return resultString;
}

$(function() {
	
	$.ajax({
		url : "zip_ajax_gosi.php",
		type : 'post',
		data : {'gb': 'a1', 'ym': <?=$date?>},
		dataType : "json",
		success: function(data) {
			console.log(data);

			$(".tot_amt").text(numberToKorean(data.loan_amt)+'원');
			$(".tot_remain").text(numberToKorean(data.tot_remain_amt)+'원');
			$(".overdue_rate").text(data.overdue_rate);
			$(".overdue_cnt").text(data.overdue_cnt);
			$(".hello_nujuk_invest_amt").text(numberToKorean(data.nujuk_invest_amt)+'원');
			$(".hello_live_invest_amt").text(numberToKorean(data.remain_amt)+'원');

		},
		error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
	});
	
	$.ajax({
		url : "zip_ajax_gosi2.php",
		type : 'post',
		data : {'gb': 'a1', 'ym': <?=$date?>},
		dataType : "json",
		success: function(data) {
			console.log(data);

			// 부동산 PF
			$(".pf_tot_amt").text(number_format(data["1"].loan_amt));
			$(".pf_tot_remain").text(number_format(data["1"].remain_amt));
			$(".pf_overdue_rate").text(data["1"].overdue_rate);

			// 주택담보
			$(".mgg_tot_amt").text(number_format(data["2"].loan_amt));
			$(".mgg_tot_remain").text(number_format(data["2"].remain_amt));
			$(".mgg_overdue_rate").text(data["2"].overdue_rate);

			// 매출채권
			$(".hp_tot_amt").text(number_format(data["3"].loan_amt));
			$(".hp_tot_remain").text(number_format(data["3"].remain_amt));
			$(".hp_overdue_rate").text(data["3"].overdue_rate);

			// 동산
			$(".mvb_tot_amt").text(number_format(data["4"].loan_amt));
			$(".mvb_tot_remain").text(number_format(data["4"].remain_amt));
			$(".mvb_overdue_rate").text(data["4"].overdue_rate);

			// 합계
			$(".all_tot_amt").text(number_format(data["tot"].loan_amt));
			$(".all_tot_remain").text(number_format(data["tot"].remain_amt));
			$(".all_overdue_rate").text(data["tot"].overdue_rate);

		},
		error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
	});

});
</script>

<script type="text/javascript">
// 사업정보공시 레이어 팝업
function layerPop() {
	var $layer = $('#gongsiPop');

	// 화면 중앙에 위치
	var left = ( $(window).scrollLeft() + ($(window).width() - $layer.width()) / 2 );
	var top = ( $(window).scrollTop() + ($(window).height() - $layer.height()) / 2 );

	$layer.css({"left":left, "top":top, "z-index":'999'}).addClass("gongsi-popup");

	$("body").prepend("<div id='layerMask' style='display:none;position:absolute;background:black;z-index:998;background:rgba(0,0,0,.5);'></div>");
	$("body").append($layer);

	wrapWindowByMask(); 
	$layer.show(); 


	function wrapWindowByMask() {
		var $mask = $('#layerMask');

		// 화면의 높이와 너비
		var maskHeight = $(document).height();
		var maskWidth = $(window).width();

		$mask.css({'width':maskWidth,'height':maskHeight});
		$mask.show();
	}

	// 사이즈 리사이징
	function ResizingLayer() {
		if($("#layerMask").css("display") == "block") {

			var maskHeight = $(document).height();
			var maskWidth = $(window).width();

			// 마스크의 높이와 너비를 전체 화면 채움
			$("#layerMask").css({'width':maskWidth,'height':maskHeight});  
		}
	}

	window.onresize = ResizingLayer;


	// 오늘 하루 보지 않기 쿠키 설정
	$('#gongsiChk').on('click', function() {
		if( $("input[name='dayClose']").is(":checked") == true ) {
			set_cookie('gongsiChk', true, 24, g5_cookie_domain);
			$('#layerMask').hide();
			$layer.hide();
		} else {
			$('#layerMask').hide();
			$layer.hide();
		}
		
	});
	
}

// 해당 쿠키 없을 경우 레이어 팝업 실행
if (!get_cookie('gongsiChk'))
{
	 layerPop();
}

</script>