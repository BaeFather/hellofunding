<?
###############################################################################
## 투자방법안내
###############################################################################

include_once('./_common.php');

$g5['title'] = '투자방법안내';
$g5['top_bn'] = "/images/investment/sub_guide.jpg";
$g5['top_bn_alt'] = "헬로펀딩 안전성 투자자가 작은 금액들을 모아서 함께 투자하는 새로운 투자 방식입니다.";

if ($co['co_include_head']) {
    @include_once($co['co_include_head']);
}else {
    include_once('./_head.php');
}

?>



<style>
	#content {background-image: none;}
	#content .top_title {font-size:36px; color:#333; letter-spacing:-1px; font-weight: 400; padding: 60px 0 10px; background-color: #fff;}
	#content .top_title .sky {color:#33a5ed;}
	#content .top_text {font-size:18px; color:#777; padding-bottom: 20px; font-family:'SpoqaHanSans','sanserif'}

	
	@media all and (max-width: 900px){
	#content {background-image: none; width:100%; margin:0 auto;}
	#content .top_title {font-size:24px; color:#333; letter-spacing:-1px; font-weight: 400; padding: 20px 0 10px; background-color: #fff; text-align: center;}
	#content .top_title .sky {color:#33a5ed;}
	#content .top_text {font-size:14px; color:#777; font-family:'SpoqaHanSans','sanserif'; text-align: center; padding-bottom: 0px;}
	#content .top_text .del {display:none;}	
	
	
	
	}
</style>

<!-- 본문내용 START -->

<div id="content">
	<!--div class="location"><span></span><b class="blue"><? echo $g5['title'];?></b></div-->
	
	<div>
		<h2 class="top_title">헬로펀딩 <span class="sky">투자방법안내</span></h2>
		<p class="top_text"><span class="del">헬로펀딩이</span> 처음이신 고객님들을 위한 투자방법을 안내해드립니다.<br class="br"></p>
	</div>
	<div class="content">

		<!--<img src="/images/investment/guide.jpg" alt="헬로펀딩 안전성" />-->
		<!--img src="/images/investment/guide_1<?=(G5_IS_MOBILE)?'_m':'';?>.jpg" width="100%" alt="헬로펀딩 투자 가이드"/-->
		<img src="/images/investment/guide_1<?=(G5_IS_MOBILE)?'_m':'';?>.jpg" width="100%" alt="헬로펀딩 투자 가이드"/>
		<img src="/images/investment/guide_2<?=(G5_IS_MOBILE)?'_m':'';?>.jpg" width="100%" alt="Step01 회원가입"/>
		<img src="/images/investment/guide_3<?=(G5_IS_MOBILE)?'_m':'';?>.jpg" width="100%" alt="Step02 가상계좌 발급받기"/>
		<img src="/images/investment/guide_4<?=(G5_IS_MOBILE)?'_m':'';?>.jpg" width="100%" alt="Step03 예치금 입금하기"/>
		<img src="/images/investment/guide_5<?=(G5_IS_MOBILE)?'_m':'';?>.jpg" width="100%" alt="Step03 예치금 입금하기"/>


		<div style="text-align: center;margin-top:5%;">
			<a href="/investment/invest_list.php" class="btn_big_orange">투자상품보기</a>
		</div>
		<br/>
		<br/>
		<br/>
	</div>
</div>

<!-- 본문내용 E N D -->

<?
if ($co['co_include_tail']) {
    @include_once($co['co_include_tail']);
}else {
    include_once('./_tail.php');
}
?>