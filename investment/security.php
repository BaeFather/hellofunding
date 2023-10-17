<?php

include_once('./_common.php');


$g5['title'] = '투자하기';
//$g5['top_bn'] = "/images/investment/sub_security.jpg";
$g5['top_bn_alt'] = "헬로펀딩 안전성 투자자가 작은 금액들을 모아서 함께 투자하는 새로운 투자 방식입니다.";

if ($co['co_include_head'])
    @include_once($co['co_include_head']);
else
    include_once('./_head.php');

?>
<!-- 본문내용 START -->

<div id="content">
	<div class="location"><span><a href="<?=G5_URL?>/investment/invest_list.php">투자하기</a></span><b class="blue">헬로펀딩 안전성</b></div>
	<div class="content">
		<!--<img src="../images/investment/security.jpg" alt="헬로펀딩 안전성" />-->
		<img src="../images/investment/security_1<?=(getDevice()=='MOBILE')?'_m':''?>.jpg"  width="100%" alt="P2P대출 플랫폼의 최우선 컨셉인 '안전성 확보'를 위한 자사의 노력"/ class="mt20">
		<? if(G5_IS_MOBILE) { ?>
		<img src="../images/investment/security_2<?=(G5_IS_MOBILE)?'_m':''?>.jpg" width="100%">
		  <div id="investment_link" style="bottom:18px; z-index:10;text-align:center;" >
			  <a href="/investment/invest_list.php" ><img src="../images/company_m_button.jpg" width="80%"></a>
		  </div>
		<? } else { ?>
		<img src="../images/investment/security_2.jpg" width="100%" class="mt80 mb100">
		<p style="text-align:center;">
			<span class="btn_big_blue"><a href="/investment/invest_list.php">투자상품보기</a></span>
		</p>
		<? } ?>
	</div>
</div>

<!-- 본문내용 E N D -->
<?php

if ($co['co_include_tail'])
    @include_once($co['co_include_tail']);
else
    include_once('./_tail.php');
?>