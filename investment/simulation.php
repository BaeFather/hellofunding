<?php
###############################################################################
## 투자시뮬레이션
###############################################################################

include_once('./_common.php');


$g5['title'] = '투자시뮬레이션';
$g5['top_bn'] = "/images/investment/sub_simulation.jpg";
$g5['top_bn_alt'] = "투자자가 작은 금액들을 모아서 함께 투자하는 새로운 투자 방식입니다.";


$developer        = ( in_array($member['mb_id'], $CONF['DEVELOPER']) ) ? true : false;
$goods_officer    = ( in_array($member['mb_id'], $CONF['GOODS_OFFICER']) ) ? true : false;
$tmp_special_user = ( in_array($member['mb_id'], array('samo','samo001','samo002')) ) ? true : false;
//$tmp_user         = ( in_array($member['mb_id'], array('cocktailfunding')) ) ? true : false;

$prd_idx = trim($_REQUEST['prd_idx']);

if($prd_idx=='') { goto_url('/'); exit; }
if(!preg_match('/^[0-9]{0,10}$/', $prd_idx)) { header('Location: /', true, 302); exit; }


$product_query = "SELECT * FROM cf_product WHERE idx = '".$prd_idx."'";
$product_query.= ($special_user || $tmp_special_user || $tmp_user) ? "" : " AND display='Y'";

$product_row   = sql_fetch($product_query);
if(!$product_row){ alert("올바른 경로가 아닙니다.","/"); exit; }


/* 투자 수익율 */
if($product_row["invest_return"]>0){
	$invest_return = $product_row["invest_return"];
}
else{
	alert("투자 수익율이 없습니다. 관리자에 문의해주세요.","./invest_list.php");
	exit;
}

/* 투자기간 */
if($product_row["invest_period"]>0){
	$invest_period = $product_row["invest_period"];
}
else{
	alert("투자기간이 없습니다. 관리자에 문의해주세요.","./invest_list.php");
	exit;
}

/* 투자자 플랫폼 이용료 */
if($product_row["invest_usefee"]>0){
	$invest_usefee = $product_row["invest_usefee"];
}
else{
	$product_row["invest_usefee"] =0;
//	alert("투자자 플랫폼 이용료가 없습니다. 관리자에 문의해주세요.","./invest_list.php");
//	echo "ERROR";
//	exit;
}

if ($co['co_include_head'])
    @include_once($co['co_include_head']);
else
    include_once('./_head.php');
?>
<!-- 본문내용 START -->

<?
/*
if(G5_IS_MOBILE){
	echo '<img src="'.G5_THEME_URL.'/img2/investment/sub_simulation.jpg" width="100%" alt="투자하기 투자자가 작은 금액들을 모아서 함께 투자하는 새로운 투자 방식입니다.">';
}
*/
?>

<style>
.f08 {font-size:0.8em}
</style>

<div id="content">
	<div class="location" style="padding: 25px 0 20px;"><?if(!G5_IS_MOBILE){?><a href="<?=G5_URL?>/investment/investment.php?prd_idx=<?=$prd_idx?>" style="color:#222; font-size: 24px;"><?=$product_row["title"]?></a><?}?></div>
	<form method="post" name="frm" id="frm">
		<input type="hidden" name="prd_idx" value="<?=$prd_idx?>">
		<input type="hidden" name="ajax_principal_value" id="ajax_principal_value" value="">
	</form>
	<div class="content">
		<div class="simulation">
			<div class="title">투자시뮬레이션 (<span class="blue" id="principal_value_text">0</span>원)</div>
			<span class="price"><input type="text" name="principal_value" id="principal_value" maxlength="8" class="text ip-price" style="width:70%;font-size:14px;text-align:center;" placeholder="투자금액입력">&nbsp;만원</span>
			<?php if(false) {?><!-- <span class="btn_blue" id="btn_simulation" style="display:none;">확인</span> //--><?php } ?>
			<br>
			<div style="margin-top:8px; font-size:0.8em; color:#555;" >※ 최소 투자금액은 <?=preg_replace('/일만/', '1만', number2korean($CONF['min_invest_limit']))?>원 입니다.</div>
		</div>

        <?php if(false){?>
		<!--
        <p align="center">
          <a href="/investment/investment.php?prd_idx=<?=$product_row["idx"]?>" class="btn_big_blue">상품상세보기</a>
        </p>
		//-->
        <?php } ?>

		<p style="height:30px"></p>

		<div id="no_data">
			<h3><?=$product_row["title"]?> </h3>
			<div style="height:16px;margin-bottom:4px;font-size:13px;line-height:16px;text-align:right;color:brown">
				&nbsp;
			</div>
			<div class="type04 mb30">
				<table>
					<colgroup>
						<col style="width:33.4%;">
						<col style="width:33.3%">
						<col style="width:33.3%">
					</colgroup>
					<tbody>
						<tr height="59">
							<td valign="top">투자원금</td>
							<td valign="top">수익률/투자기간</td>
							<td valign="top">수익(세전)</td>
						</tr>
						<tr height="59">
							<td valign="top">플랫폼이용료</td>
							<td valign="top">세금</td>
							<td valign="top">총수익(세후)</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="type03 profit mb40">
<? if (G5_IS_MOBILE) { ?>
				<table id="simul_table">
					<colgroup>
						<col style="width:33.4%">
						<col style="width:33.3%">
						<col style="width:33.3%">
					</colgroup>
					<tbody>
						<tr>
							<th style="text-align:center">지급일자(차수)</th>
							<th style="text-align:center">원금</th>
							<th style="text-align:center">수익</th>
						</tr>
						<tr>
							<th style="text-align:center">플랫폼이용료</th>
							<th style="text-align:center">세금</th>
							<th style="text-align:center">실입금액</th>
						</tr>
						<tr>
							<td colspan="3" style="text-align:center">투자 예정 금액을 입력해 보세요</td>
						</tr>
					</tbody>
				</table>
<? } else { ?>
				<table>
					<tbody>
						<colgroup>
							<col>
							<col style="width:14.28%">
							<col style="width:14.28%">
							<col style="width:14.28%">
							<col style="width:14.28%">
							<col style="width:14.28%">
							<col style="width:14.28%">
						</colgroup>
						<tr>
							<th>지급일자(차수)</th>
							<th>원금</th>
							<th>투자일수</th>
							<th>수익</th>
							<th>플랫폼이용료</th>
							<th>세금</th>
							<th>실입금액</th>
						</tr>
						<tr>
							<td colspan="7">투자 예정 금액을 입력해 보세요</td>
						</tr>
					</tbody>
				</table>
<? } ?>
			</div>

		</div>

		<div id="search_data"></div>

		<ul class="explain" style="margin-bottom:30px;font-size:<?=(G5_IS_MOBILE)?'0.8em':'13px';?>">
			<li><span class="green">＊</span> 수익소득에 대한 세금이 원천징수되어 차감후 금액이 입금 됩니다.</li>
			<li><span class="green">＊</span> 만기일시상환 방식은 만기일에 원금을 상환합니다.</li>
			<li><span class="green">＊</span> 법인투자자의 경우 세율은 27.5%로 적용되며, 투자시뮬레이션은 개인투자자의 세율(15.4%)을 기준으로 표시됩니다.</li>
		</ul>


		<h3>수익 계산 가이드</h3>
		<div class="profit_guide">
<? if(G5_IS_MOBILE) { ?>
			<table style="width:100%; text-align:left;">
				<tr>
					<td style="font-weight: bold;padding:15px;border-bottom:1px solid #e0e0e0;line-height:1.5">
						<span class="blue">수익</span> = {투자원금×(금리/100)/365일}×투자일수 (윤년에 귀속된 상환회차일 경우 <font color='brown'>366일</font>로 나눔)<br>
						<span class="blue">플랫폼이용료</span> = {투자원금×(플랫폼이용요율/100)/365일}×투자일수 (윤년에 귀속된 상환회차일 경우 <font color='brown'>366일</font>로 나눔)<br>
						<span class="blue">세금</span> = 수익×<? if($member['member_type']=='1') { echo '(15.4/100)'; } else if($member['member_type']=='2') { echo '(27.5/100)'; } else { echo '개인 : 수익×(15.4/100), 법인 : 수익×(27.5/100)'; } ?><br>
						<span class="blue">총수익</span> = 투자원금＋수익－세금 - 플랫폼이용료
					</td>
				</tr>
			</table>
<? } else { ?>
			<table style="width:100%; text-align:left; font-size:14px;font-family:NGB;">
				<tr>
					<td style="padding:10px 30px;line-height:2">
						<span class="blue">수익</span> = {투자원금×(금리/100)/365일}×투자일수 (윤년에 귀속된 상환회차일 경우 <font color='brown'>366일</font>로 나눔)<br>
						<span class="blue">플랫폼이용료</span> = {투자원금×(플랫폼이용요율/100)/365일}×투자일수 (윤년에 귀속된 상환회차일 경우 <font color='brown'>366일</font>로 나눔)<br>
						<span class="blue">세금</span> = <? if($member['member_type']=='1') { echo '수익×(15.4/100)'; } else if($member['member_type']=='2') { echo '수익×(27.5/100)'; } else { echo '개인 : 수익×(15.4/100), 법인 : 수익×(27.5/100)'; } ?><br>
						<span class="blue">총수익</span> = 투자원금＋수익－세금 - 플랫폼이용료
					</td>
				</tr>
			</table>
<? } ?>

		</div>

    <div class="explain">
			<div class="text" style="font-size:10pt">
				○ 투자수익 시뮬레이션<br/>
				<ul>
					<li style="list-style:disc;margin-left:24px;">투자수익 시뮬레이션은 예상수익을 표기해주는 것으로 펀딩완료 후 대출실행일과의 일수차이, 조기상환 및 기타 이유로 기재된 예상수익은 변동될 수 있습니다.</li>
				</ul>
				<br/>
				○ 플랫폼이용료<br>
				<ul>
					<li style="list-style:disc;margin-left:24px;">매월 투자원금의 0.1% (연기준환산 1.2%) 의 금액을 플랫폼이용료로 수취합니다. (단, 면제상품은 플랫폼 이용료를 수취하지 않습니다.)</li>
				</ul>
				<br>
				○ 이자소득세 원천징수<br>
				<ul>
					<? if($member['member_type']=='1') { ?>
					<li style="list-style:disc;margin-left:24px;">
					개인투자자의 투자수익은 '비영업대금의 이익'으로 소득세법 제 129조 제 1항 제 나호에 의해 14%의 소득세가 발생되며, 주민세 1.4%가 추가되어 총 15.4%의 세금을 납부해야 합니다. 
					</li>
					<? } else if($member['member_type']=='2') { ?>
					<li style="list-style:disc;margin-left:24px;">
					법인투자자의 투자수익은 '비영업대금의 이익'으로 법인세법 제 73조에 의해 25%의 소득세가 발생되며, 주민세 2.5%가 추가되어 총 27.5%의 세금을 납부해야 합니다. 
					</li>
					<? } else { ?>
					<li style="list-style:disc;margin-left:24px;">개인투자자의 투자수익은 '비영업대금의 이익'으로 소득세법 제 129조 제 1항 제 나호에 의해 14%의 소득세가 발생되며, 주민세 1.4%가 추가되어 총 15.4%의 세금을 납부해야 합니다.</li>
					<li style="list-style:disc;margin-left:24px;">법인투자자의 투자수익은 '비영업대금의 이익'으로 법인세법 제 73조에 의해 25%의 소득세가 발생되며, 주민세 2.5%가 추가되어 총 27.5%의 세금을 납부해야 합니다.</li>
					<? } ?>
				</ul>
			</div>
		</div>

    <p align="center">
      <a href="<?php echo G5_URL;?>/investment/investment.php?prd_idx=<?=$product_row["idx"]?>" class="btn_big_blue">상품상세보기</a>
    </p>

	</div>
</div>

<script type="text/javascript">
var ajax_file = g5_url + "/investment/ajax_simulation.php";
var pattern = /^[0-9]+$/;
$(document).ready(function(){

	$("input[name='principal_value']").focus(function(evt){
		if($(".price-label").text()=="투자금액입력"){
			$(".price-label").text("");
		}
	});

	$("input[name='principal_value']").keyup(function(evt){
		var unit_price           = 10000;
		var principal_value_str  = '';
		var million_value        = 0;
		var principal_real_value = 0;

		principal_value =	Number($(this).val());
		principal_value = principal_value * unit_price;

		if(principal_value > 0) {
			principal_value = parseInt((principal_value / unit_price) * unit_price);	//만원단위 처리

			if(principal_value >= 100000000) {
				million_value        = parseInt(principal_value / 100000000);
				principal_value_str  = String(million_value) + '억';
				principal_value      = principal_value - (million_value * 100000000);
				principal_real_value = (million_value * 100000000);
			}

			if(principal_value > 0) {
				principal_value      = Math.floor(principal_value / unit_price);
				principal_real_value = principal_real_value+ (principal_value * unit_price);
				principal_value_str  = principal_value_str + Number_Format(String(principal_value)) + '만';
			}
			else {
				if(principal_value_str == "") principal_value_str = '0';
			}
		}
		else {
			principal_value_str = '0';
		}
	    $("input[name='ajax_principal_value']").val(principal_real_value);
        $("#principal_value_text").text(principal_value_str);
	});

	$("#principal_value").keyup(function(){$(this).val( $(this).val().replace(/[^0-9]/g,"") );} );

	$("input[name='principal_value']").on('keyup', function(evt) {
		if($("input[name='ajax_principal_value']").val() >= <?=$CONF['min_invest_limit']?>) {
			ajax_data = $("#frm").serialize();
			$.ajax({
				url : ajax_file,
				type: "POST",
				data : ajax_data,
				success: function(data, textStatus, jqXHR){
					if(data=="ERROR"){
						alert("시스템 에러입니다. 관리자에 문의해주세요.");
					}
					else if(data=="ERROR-MIN-PRICE"){
						alert("최소 금액은 <?=number2korean($CONF['min_invest_limit'])?>원 이상 입니다.");
						$("input[name='principal_value']").focus();
						return;
					}
					else{
						$("#no_data").hide();
						$("#search_data").html(data);
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {

				}
			});
		}
	});


	$("#btn_simulation").click(function(evt){
		var  principal_value  = 0;
		var  min_invest_limit = 0;
		var  max_invest_limit = 0;

		if($("input[name='principal_value']").val()=="") {
			alert("투자 금액을 입력해주새요");
			$("input[name='principal_value']").focus();
			return;
		}
		if (! pattern.test($("input[name='principal_value']").val()) ) {
			alert("투자 금액에 사용할수 없는 문자가 있습니다. 숫자만  입력해주세요.");
			$("input[name='principal_value']").focus();
			return;
		}

		if($("input[name='ajax_principal_value']").val() < <?=$CONF['min_invest_limit']?>) {
			alert("최소 금액은 <?=number2korean($CONF['min_invest_limit'])?>원 이상 입니다.");
			$("input[name='principal_value']").focus();
			return;
		}

		ajax_data = $("#frm").serialize();
		$.ajax({
			url : ajax_file,
			type: "POST",
			data : ajax_data,
			success: function(data, textStatus, jqXHR){
				if(data=="ERROR"){
					alert("시스템 에러입니다. 관리자에 문의해주세요.");
				}
				else if(data=="ERROR-MIN-PRICE"){
					alert("최소 금액은 10만원 이상 입니다.");
					$("input[name='principal_value']").focus();
					return;
				}
				else{
					$("#no_data").hide();
					$("#search_data").html(data);
				}
			},
			error: function (jqXHR, textStatus, errorThrown)	{

			}
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

<!-- 본문내용 E N D -->
<?php
if ($co['co_include_tail'])
    @include_once($co['co_include_tail']);
else
    include_once('./_tail.php');
?>
