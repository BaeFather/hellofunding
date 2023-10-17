<?
switch($PRDT['category']) {
	case '1' : $cFlag = '<li class="p_ca-B">동산</li>'; break;
	case '2' : $cFlag = ($PRDT['mortgage_guarantees']=='1') ? '<li class="p_ca-A2">주택담보</li>' : '<li class="p_ca-A">부동산</li>'; break;
	case '3' : $cFlag = '<li class="p_ca-C">SCF</li>'; break;
	default  : $cFlag = ''; break;
}

$aiFlag  = ($PRDT['ai_grp_idx']>0) ? '<li class="p_ai">자동투자</li>' : '';
$newFlag = ($PRDT['new_flag']=='Y') ? '<li class="p_new">N</li>' : '';
$srmFlag = ($PRDT["stream_url1"] OR $PRDT["stream_url2"]) ? '<li class="p_live_tv"  onClick="'.$live_link.'"><i class="fa fa-tv"></i> LIVE TV</li>' : '';
$adiFlag = ($PRDT['advance_invest']=='Y') ? '<li class="p_adir">사전투자 ' . floatRtrim($PRDT['advance_invest_ratio']).'% <i class="fa fa-question-circle" id="question_1"></i></li>' : '';
$pgFlag  = ($PRDT['purchase_guarantees']=='Y' && preg_match("/dev\.hello/", G5_URL)) ? '<li class="p_pg">채권매입계약</li>' : '';
$adpFlag = ($PRDT['advanced_payment']=='Y') ? '<li class="p_adpy">이자 선지급</li>' : '';
$conFlag = ($PRDT['isConsor']=='1') ? '<li class="p_con">컨소시엄</li>' : '';

$NAME_TAG = explode("|", $PRDT['name_tag']);
$nameTagFlag1 = (in_array("권원보험", $NAME_TAG)) ? '<li class="p_ai" style="background:#ff9900">권원보험</li>' : '';
$nameTagFlag2 = (in_array("매입확약", $NAME_TAG)) ? '<li class="p_ai" style="background:#cc00ff">매입확약</li>' : '';
?>

<!-- 본문내용 START -->

<script src="<?=G5_URL?>/js/jquery.blink.js"></script>
<? add_stylesheet('<link rel="stylesheet" type="text/css" href="/investment/css/investment_info_m.css?ver=20220620">', 0); ?>

<input type="hidden" name="prd_idx" value="<?=$prd_idx;?>">
<input type="hidden" name="invest_finished" id="invest_finished" value="<? echo ($invest_finished) ? 'true' : 'false'; ?>">

<!-- 상품 슬라이드 시작 -->
<div id="p_info">
	<div class="p_info_b" <? if($PRDT['title_image_url_m']) { ?> style="background:url('<?=$PRDT['title_image_url_m']?>') repeat center;" <? } ?>>
		<div class="p_info_bb">
			<div class="p_flags">
				<ul>
					<?=$cFlag?><?=$aiFlag?><?=$conFlag?><?=$newFlag?><?=$srmFlag?><?=$adiFlag?><?=$pgFlag?><?=$adpFlag?><?=$nameTagFlag1?><?=$nameTagFlag2?>
				</ul>
			</div>

			<div class="p_tit"><?=$PRDT['title']?></div>
			<div class="p_date">모집기간 : <?=$print_sdate;?> ~</div>

			<div class="p_info_total">
				<div>
					<span>투자수익률(연)</span>
					<?=$invest_return?><b>%</b>
				</div>
				<div>
					<span>투자기간</span>
					<?=$invest_period?><b><?=$invest_period_unit?></b>
				</div>
				<div>
					<span>모집금액</span>
					<?=$print_recruit_amount?><b>원</b>
				</div>
			</div>

			<div class="process_wrap">
				<div class="process">

					<div class="process_tag">
						<div class="process_tag_c">
							<span>투자모집률 / 투자가능금액</span>
							<strong class="p_t_n" id="progressData"><?=$product_invest_percent?>%</strong> / <strong class="p_t_t" id="totalRecruitValue"><?=price_cutting($PRDT['need_recruit_amount']);?>원</strong>
<? if(false) { ?>
<!--
							<span>투자모집률 / 모집된 금액</span>
							<strong class="p_t_n" id="progressData"><?=$product_invest_percent?>%</strong> / <strong class="p_t_t" id="totalRecruitValue"><?=price_cutting($PRDT["total_invest_amount"]+0);?>원</strong>
//-->
<? } ?>
						</div>
					</div>
					<div id="progressBar" class="process_bar" style="width:<?=(($product_invest_percent <= 100)?$product_invest_percent:100).'%';?>"></div>
				</div>
			</div>
			<ul id="processBtn" class="btn_all">

				<? if($invest_finished == false) { ?>
				<!--<li class="simulation"><a href="/investment/simulation.php?prd_idx=<?=$PRDT['idx']?>" class="btn_big_link">투자시뮬레이션</a></li>-->
				<? } ?>

				<? if($invest_button) {?>
				<li class="invest"><?=$invest_button?></li>
				<? } ?>

				<? if($advance_invest_button) { ?>
				<li class="reser_invest"><?=$advance_invest_button?></li>
				<? } ?>

				<? if($PRDT['ai_grp_idx']) { ?>
				<!--li class="auto_invest"><a href="/deposit/deposit.php?tab=5" class="btn_big_orange">자동투자설정</a></li-->
				<? } ?>

				<? if(!$is_member && $invest_finished) { ?>
				<li><a id="reqsms_btn2" class="btn_big_blue">다음 상품 알림받기</a></li>
				<? } ?>

			</ul>


			<? if($PRDT['product_summary']) echo $PRDT['product_summary']; ?>

			<div class="sns_share">
				<ul>
					<li>
						<a href="#" data-toggle="sns_share" data-service="facebook" data-title="페이스북 SNS공유">
							<img src="<?=G5_THEME_IMG_URL?>/sub/sns_f_btn01.png" alt="facebook" width="30">
						</a>
					</li>
					<li>
						<a href="#" data-toggle="sns_share" data-service="naver" data-title="네이버 SNS공유">
							<img src="<?=G5_THEME_IMG_URL?>/sub/sns_b_btn01.png" alt="naver" width="30">
						</a>
					</li>
					<li>
						<a href="#" data-toggle="sns_share" data-service="kakaostory" data-title="카카오스토리 SNS공유">
							<img src="<?=G5_THEME_IMG_URL?>/sub/sns_k_btn01.png" alt="kakao" width="30">
						</a>
					</li>
					<!--
					<li>
						<a href="#" data-toggle="sns_share" data-service="instagram" data-title="인스타그램 SNS공유">
							<img src="<?=G5_THEME_IMG_URL?>/sub/sns_i_btn01.png" alt="instagram">
						</a>
					</li>
					//-->
					<li>
						<a href="#" data-toggle="sns_share" data-service="url_copy" data-title="주소복사하기">
							<img src="<?=G5_THEME_IMG_URL?>/investment/url_icon.png" alt="url_copy" width="30">
						</a>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>

<!-- 예상수익금 -->
<div class="pre_earn clearfix">
	<!--div class="pre_earn_tit">
		<p>예상수익금</p>
		<span></span>
	</div-->

	<ul class="pre_earn_c" style="padding-bottom:-52px;">
		<li>지금 이 상품에</li>
		<li><input type="text" name="principal_value" value="<?=number_format(5000000)?>" maxlength="11" placeholderaa="투자금액입력. 예)1,000,000원" onkeyup="formatNumber(this);simulation();"></li>
		<li>원을 투자시</li>
		<!--<li class="equal"><a href="javascript:;" onclick="simulation();">계산하기</a></li>-->
	</ul>
	<div class="earn_info">
		<p class="earn_btn" style="padding:10px 0; margin-top:10;">
			<span style="text-align:left;width:55%;display:inline-block;">예상 총 실수익금 (세후) <span id="earninfo1-claim-mark" class="claim-mark" >?</span></span><!--br/-->
			<span style="text-align:right;width:41%;display:inline-block;"><strong id="ajxTotalInterestPrice">0</strong>원</span>
		</p>
<? if($PRDT['invest_period'] > 1 and 1>2) { /* 2018-12-07 이정환 차장 요청으로 블락처리 */ ?>
		<p class="earn_btn <?=($PRDT['open_datetime'] < '2018-08-31 09:00:00')?'blind':'';?>" style="width:1px;height:1px;overflow:hidden;">
			월 평균 예상 수익금 지급액 <span id="earninfo2-claim-mark" class="claim-mark">?</span><br/>
			<strong id="ajxInvestMonth">0</strong>개월 동안 매월 <strong id="ajxMonthAvrPrice">0</strong>원
		</p>
<? } ?>
		<p class="earn_btn <?=($PRDT['open_datetime'] < '2018-08-31 09:00:00')?'blind':'';?>" style="padding:10px 0;">
			<span style="text-align:left;width:53%;display:inline-block;">은행예금 대비 수익 <span id="earninfo3-claim-mark" class="claim-mark">?</span></span><!--br/-->
			<span style="text-align:right;width:43%;display:inline-block;"><strong id="ajaDiffEarning">0</strong>배</span>
		</p>
	</div>
	<div class="simulation_detail_btn" onClick="location.href='simulation.php?prd_idx=<?=$prd_idx?>';" style="padding:20px 0;">투자시뮬레이션 자세히보기 > </div>
</div>
<script type="text/javascript">
	var msg = "본 상품의 투자금액에 따른 수익금에서 세금과 플랫폼 이용료를 제외한 금액이며, 조기상환 등 투자기간 변동에 의해 실제와 다를 수 있습니다.";
	$('#earninfo1-claim-mark').webuiPopover({ title: "예상 총 실수익금(세후)", content: msg, closeable: true, width: 180, height: 90, trigger: "click", placement: 'bottom', backdrop: false});
	var msg = "투자기간 중 헬로펀딩이 매월 지급해 드리는 세후 수익금으로, 이자산정일에 따라 변동될 수 있습니다.";
	$('#earninfo2-claim-mark').webuiPopover({ title: "월 평균 지급수익금 ", content: msg, closeable: true, width: 160, height: 90, trigger: "click", placement: 'bottom', backdrop: false});
	var msg = "1금융권 정기예금 평균 금리 1.7% 대비 본 투자상품의 수익률입니다. (각 세후 실수익 기준)";
	$('#earninfo3-claim-mark').webuiPopover({ title: "은행에 예금시보다 ", content: msg, closeable: true, width: 160, height: 75, trigger: "click", placement: 'bottom', backdrop: false});
</script>


<!-- 헬로펀딩 이벤트(220501 임시 주석처리) -->
<div class="product_info">
<!-- **
<?php
$gstrHelloBanner = NEW Hello_Banner();
$gstrHelloBanner->CODE = "0004";
$strVal = $gstrHelloBanner->RsContent();
?>
	<div class="hello_event"><a href="<?php ECHO unique_un_replace($strVal[0]["targeturl"])?>"><img src="<?php ECHO "/data/event/".$strVal[0]["mrepimg"]?>" style="width:100%;"/></a></div><br/>
-->

<!--?php
$gstrHelloBanner->CODE = "0006";
$strVal2 = $gstrHelloBanner->RsContent();
?>
	<div class="hello_event"><a href="<?php ECHO unique_un_replace($strVal2[0]["targeturl"])?>"><img src="<?php ECHO "/data/event/".$strVal2[0]["mrepimg"]?>" style="width:100%;"/></a></div><br/>
	<!--<div class="hello_event"><a href="/event/nhcma_event.php"><img src="/evnt/nhCMA/event_banner_m.jpg?aa=1"></a></div>//-->

	<div style="margin:30px 0;">
		<div style="margin:5px 2%;"><a href="/review/review_event/review_2205.php"><img src="/theme/2018/img/new_m/new_main_banner_01.jpg" width="100%"></a></div>
		<div style="margin:5px 2%;"><a href="/event/2205/"><img src="/theme/2018/img/new_m/new_main_banner_02.jpg" width="100%"></a></div>
	</div>

</div>


<?
if(trim($PRDT['core_invest_point'])) {

		$core_invest_point = $PRDT['core_invest_point'];

		// 이미지를 프래임 내부에서 호출되도록 하기 위한 작업
		$str = preg_replace("/<p>/i", "", $core_invest_point);
		$_ARR = explode("</p>", $str);
		for($i=0; $i<count($_ARR); $i++) {
			$target_string = trim( str_f6($_ARR[$i], "<a href=\"", "\" rel=\"noopener noreferrer\" target=\"_blank\">") );
			$target_string = preg_replace("/(\\r|\\n)/", "", $target_string);

			if($target_string) {
				$change_string = "/hello/image/" . preg_replace("/\=/", "hello", base64_encode($target_string)).".html\n";
			//$change_string = "/hello/image/" . preg_replace("/\=/", "hello", base64_encode($target_string))."&".time()."\n";

				$arg0 = "/".preg_replace("/\//", "\/", $target_string)."/i";
				$core_invest_point = preg_replace($arg0, $change_string, $core_invest_point);
			}
		}
		$core_invest_point = preg_replace("/_blank/i", "_self", $core_invest_point);
		$core_invest_point = preg_replace("/(href=\"#\"|href='#')/i", "href='javascript:;'", $core_invest_point);

		echo $core_invest_point."\n";

}
?>


<!-- 상품개요 -->
<div class="product_info">
	<div class="product_info_tit">상품 개요</div>
	<div>
		<? if($PRDT['open_datetime'] >= '2021-10-27 00:00:00') { // 지정된 날짜 이후부터 출력 ?>
		<ul class="p_i_t2">
			<li>
				<p>투자모집액</p>
				<p><? echo price_cutting($PRDT['recruit_amount']);?>원</p>
			</li>
			<li>
				<p>투자수익률</p>
				<p>연 <?=$invest_return?>%</p>
			</li>

			<li>
				<p>예상 순수익률 </p>
				<p><?=$profit_perc;?>%</p>
			</li>

			<li>
				<p>투자기간</p>
				<p><?=$invest_period?><?=$invest_period_unit?></p>
			</li>
			<li>
				<p>상환방법</p>
				<p><?=$repay_pay_title?></p>
			</li>
		</ul>
		<? } else { ?>
		<ul class="p_i_t">
			<li>
				<p>투자모집액</p>
				<p><? echo price_cutting($PRDT['recruit_amount']);?>원</p>
			</li>
			<li>
				<p>투자수익률</p>
				<p>연 <?=$invest_return?>%</p>
			</li>
			<li>
				<p>투자기간</p>
				<p><?=$invest_period?><?=$invest_period_unit?></p>
			</li>
			<li>
				<p>상환방법</p>
				<p><?=$repay_pay_title?></p>
			</li>
		</ul>
		<? } ?>

		<div class="clearfix"></div>
		<? if($description = nl2br($PRDT['product_description'])) { // 상품설명 ?>
			<div class="p_i_t_i">
				<?=$description?>
			</div>
		<? } ?>
	</div>
</div>

<!-- 실시간 현장 라이브 -->
<? if($live_link) { ?>
<div class="hello_tv">
	<img src="<?=G5_THEME_IMG_URL?>/sub_m/live_banner.jpg" alt="실시간 현장 방송" onClick="<?=$live_link?>">
</div>
<? } ?>

<!-- 예상수익금 원래 있던 자리 -->


<!-- 안전장치 업데이트 -->
<?
	if(trim($PRDT['extend_8'])) {

		$extend_8 = $PRDT['extend_8'];

		// 이미지를 프래임 내부에서 호출되도록 하기 위한 작업
		$str = preg_replace("/<p>/i", "", $extend_8);
		$_ARR = explode("</p>", $str);
		for($i=0; $i<count($_ARR); $i++) {
			$target_string = trim( str_f6($_ARR[$i], "<a class=\"fr-file\" href=\"", "\" target=\"_blank\">") );
			$target_string = preg_replace("/(\\r|\\n)/", "", $target_string);

			if($target_string) {
				$change_string = "/hello/image/" . preg_replace("/\=/", "hello", base64_encode($target_string)).".html\n";
			//$change_string = "/hello/image/" . preg_replace("/\=/", "hello", base64_encode($target_string))."&".time()."\n";

				$arg0 = "/".preg_replace("/\//", "\/", $target_string)."/i";
				$extend_8 = preg_replace($arg0, $change_string, $extend_8);
			}
		}
		$extend_8 = preg_replace("/_blank/i", "_self", $extend_8);
		$extend_8 = preg_replace("/(href=\"#\"|href='#')/i", "href='javascript:;'", $extend_8);

		echo $extend_8."\n";

	}
?>


<!-- 신한은행 배너 -->
<? if($prd_idx < '10801') { ?>
<div class="shinhan_ban"><img src="/theme/2018/img/sub_m/shinhan_ban01_m.jpg" width="100%"></div>
<? } ?>

<div id="detail_box" class="detail_box" >

<?
	if(trim($PRDT['invest_summary_m'])) {

		$invest_summary_m = $PRDT['invest_summary_m'];

		// 이미지를 프래임 내부에서 호출되도록 하기 위한 작업
		$str = preg_replace("/<p>/i", "", $invest_summary_m);
		$_ARR = explode("</p>", $str);
		for($i=0; $i<count($_ARR); $i++) {
			$target_string = trim( str_f6($_ARR[$i], "<a class=\"fr-file\" href=\"", "\" target=\"_blank\">") );
			$target_string = preg_replace("/(\\r|\\n)/", "", $target_string);

			if($target_string) {
				$change_string = "/hello/image/" . preg_replace("/\=/", "hello", base64_encode($target_string)).".html\n";
			//$change_string = "/hello/image/" . preg_replace("/\=/", "hello", base64_encode($target_string))."&".time()."\n";

				$arg0 = "/".preg_replace("/\//", "\/", $target_string)."/i";
				$invest_summary_m = preg_replace($arg0, $change_string, $invest_summary_m);
			}
		}
		$invest_summary_m = preg_replace("/_blank/i", "_self", $invest_summary_m);
		$invest_summary_m = preg_replace("/(href=\"#\"|href='#')/i", "href='javascript:;'", $invest_summary_m);

		echo $invest_summary_m."\n";

	}
?>

	<!-- 증빙 서류 -->
<?
	if(trim($PRDT['extend_9'])) {

		$extend_9 = $PRDT['extend_9'];

		// 이미지를 프래임 내부에서 호출되도록 하기 위한 작업
		$str = preg_replace("/<p>/i", "", $extend_9);
		$_ARR = explode("</p>", $str);
		for($i=0; $i<count($_ARR); $i++) {
			//$target_string = trim( str_f6($_ARR[$i], "<a class=\"fr-file\" href=\"", "\" target=\"_blank\">") );
			$target_string = trim( str_f6($_ARR[$i], "href=\"", "\" target=\"_blank\">") );
			$target_string = preg_replace("/(\\r|\\n)/", "", $target_string);

			if($target_string) {
				$change_string = "/hello/image/" . preg_replace("/\=/", "hello", base64_encode($target_string)).".html\n";
			//$change_string = "/hello/image/" . preg_replace("/\=/", "hello", base64_encode($target_string))."&".time()."\n";

				$arg0 = "/".preg_replace("/\//", "\/", $target_string)."/i";
				$extend_9 = preg_replace($arg0, $change_string, $extend_9);
			}
		}

		$extend_9 = preg_replace("/_blank/i", "_self", $extend_9);
		$extend_9 = preg_replace("/(href=\"#\"|href='#')/i", "href='javascript:;'", $extend_9);

		echo $extend_9."\n";

	}
?>

	<?=($PRDT['extend_7']) ? $PRDT['extend_7'] : '';?>
</div>

<?
if ($PRDT['address']) {
	?>
	<script src="//dapi.kakao.com/v2/maps/sdk.js?appkey=a1a12feb2e53aac7f2424691b4532110&libraries=services"></script>
	<script>
	if ($('#area_kakaomap').length) {
		$("#area_kakaomap").append("<div id='kakao_map' style='width:100%; height:300px; border:1px solid #ADADAD;margin:0 auto 15px;'></div>");
		var container = document.getElementById('kakao_map');
		var options = {
			center: new kakao.maps.LatLng(33.450701, 126.570667),
			level: 3
		};
		var map = new kakao.maps.Map(container, options);

		// 주소-좌표 변환 객체를 생성합니다
		var geocoder = new kakao.maps.services.Geocoder();

		// 주소로 좌표를 검색합니다.
		geocoder.addressSearch('<?=$PRDT[address]?>', function(result,status) {

			// 정상적으로 검색이 완료됐으면
			if (status === kakao.maps.services.Status.OK) {

				var coords = new kakao.maps.LatLng(result[0].y , result[0].x);

				// 결과값으로 받은 위치를 마커로 표시합니다.
				var marker = new kakao.maps.Marker({
					map: map,
					position: coords
				});

				// 인포윈도우로 장소에 대한 설명을 표시합니다
				var infowindow = new kakao.maps.InfoWindow({
					content: '<div style="width:150px;text-align:center;padding:6px 0;">우리회사</div>'
				});
				//infowindow.open(map, marker);

				// 지도의 중심을 결과값으로 받은 위치로 이동시킵니다
				map.setCenter(coords);
			}

		});
	}
	</script>
	<?
}
?>

<? // 네이버 파노라마(로드뷰) 2018-07-18 전승찬 추가
/*
if ($PRDT['loadview_url']) {
	$tmp1 = explode("?",$PRDT['loadview_url']);
	parse_str($tmp1[1]);
	?>


	<script type="text/javascript" src="https://openapi.map.naver.com/openapi/v3/maps.js?clientId=wgdMUaKHdFdJ8M6hMFJ_&submodules=panorama"></script>
	<script type="text/javascript">
		$(".prdt_summ").append("<div id='pano' style='width:100%; height:600px;border:1px solid black;margin:10px auto 5px;'></div><div style='width:320px;margin:5px auto 30px;text-align:center;'>화면을 클릭한 후 상하좌우로 움직여서 현장을 확인하세요!<br/><a onclick='load_naver_map();' style='cursor:pointer;font-weight:bold;'>[로드뷰 초기화는 <span style='text-decoration:underline;'>여기</span>를 클릭해 주세요.]</a></div>");

		function load_naver_map() {
			var pano = new naver.maps.Panorama(document.getElementById("pano"), {
				size               : new naver.maps.Size(320, 300),
				panoId             : "<?=$vrpanoid?>",
				pov                : {pan : <?=$vrpanopan?>, tilt : <?=$vrpanotilt?>, fov : <?=$vrpanofov?> },
				aroundControl      : true,
				MapDataControl     : false,
				zoomControl        : true,
				zoomControlOptions : {position: naver.maps.Position.TOP_RIGHT}
			});
		}

		load_naver_map();
	</script>

	<?
}
*/
?>

<?
if ($PRDT['loadview_url'] and preg_match('/\bkakao\b/i', $PRDT['loadview_url'] ,$matches)) {
	$tmp1 = explode("?",$PRDT['loadview_url']);
	parse_str($tmp1[1]);
	//echo "panoid (고유값) = > $panoid<br/>";
	//echo "pan (수평각) => $pan<br/>";
	//echo "tile (수직각) => $tilt<br/>";
	//echo "zoom (확대) => $zoom<br/>";
	?>
	<script>
	function isFlashEnabled()
	{
		var hasFlash = false;
		try
		{
			var fo = new ActiveXObject('ShockwaveFlash.ShockwaveFlash');
			if(fo) hasFlash = true;
		}
		catch(e)
		{
			if(navigator.mimeTypes ["application/x-shockwave-flash"] != undefined) hasFlash = true;
		}
		return hasFlash;
	}
	var flash_yn = isFlashEnabled();
	</script>
	<script src="//dapi.kakao.com/v2/maps/sdk.js?appkey=a1a12feb2e53aac7f2424691b4532110"></script>
	<script>
		$(".prdt_summ").append("<div id='kakao_roadview' style='width:90%; height:300px;border:1px solid black;margin:10px auto 5px;'></div><div style='width:320px;margin:5px auto 30px;text-align:center;'>화면을 클릭한 후 상하좌우로 움직여서 현장을 확인하세요 !</div>");
		//로드뷰를 표시할 div
		var roadviewContainer = document.getElementById('kakao_roadview');
		flash_yn=true;
		if (flash_yn) {
			//로드뷰 객체를 생성한다
			var roadview = new daum.maps.Roadview(roadviewContainer, {
				panoId : <?=$panoid?>, // 로드뷰 시작 지역의 고유 아이디 값
				pan: <?=$pan?>, // 로드뷰 처음 실행시에 바라봐야 할 수평 각
				tilt: <?=$tilt?>, // 로드뷰 처음 실행시에 바라봐야 할 수직 각
				zoom: <?=$zoom?> // 로드뷰 줌 초기값
			});
		} else {
			$("#kakao_roadview").css('background-image','url("/images/bg_pattern.jpg")');
			$("#kakao_roadview").html("<div style='text-align:center;width:90%;height:86px;margin:20% auto;'><b>로드뷰 서비스를 이용하시려면<br/>Adobe Flash Player 설치 및 허용이 필요합니다.<br/><br/><a href='http://get.adobe.com/flashplayer/' target='_blank'>[최신버전 다운로드]</a></b></div>");
		}
	</script>
	<?
} else if ($PRDT['loadview_url'] and preg_match('/\bnaver\b/i', $PRDT['loadview_url'] ,$matches)) {
	// 네이버 파노라마(로드뷰) 2018-07-18 전승찬 추가
	/*
	$tmp1 = explode("?",$PRDT['loadview_url']);
	parse_str($tmp1[1]);
	?>
	<script type="text/javascript" src="https://openapi.map.naver.com/openapi/v3/maps.js?clientId=wgdMUaKHdFdJ8M6hMFJ_&submodules=panorama"></script>
	<script type="text/javascript">
		$(".prdt_summ").append("<div id='pano' style='width:100%; height:600px;border:1px solid black;margin:0 auto 5px;'></div><div style='width:888px;margin:5px auto 30px;text-align:center;'>화면을 클릭한 후 상하좌우로 움직여서 현장을 확인하세요! &nbsp;&nbsp;&nbsp;<a onclick='load_naver_map();' style='cursor:pointer;font-weight:bold;'>[로드뷰 초기화는 <span style='text-decoration:underline;'>여기</span>를 클릭해 주세요.]</a></div>");

		function load_naver_map() {
			var pano = new naver.maps.Panorama(document.getElementById("pano"), {
				size               : new naver.maps.Size(888, 600),
				panoId             : "<?=$vrpanoid?>",
				pov                : {pan : <?=$vrpanopan?>, tilt : <?=$vrpanotilt?>, fov : <?=$vrpanofov?> },
				aroundControl      : true,
				MapDataControl     : true,
				zoomControl        : true,
				zoomControlOptions : {position: naver.maps.Position.TOP_RIGHT}
			});
		}
		load_naver_map();
	</script>
	<?
	*/
}
?>


<?
	// 이전 월 말일
	$date = date("Y.m");
	$d = mktime(0,0,0, date("m"), 1, date("Y"));
	$prev_date = strtotime("-1 month", $d);
	$tmp_date = date('Y-m-t', $prev_date);
	$ym = str_replace('-', '.', substr($tmp_date, 0, 7));
?>

<!--  공시정보 추가 -->
<div class="gongsi_info_box container">
	<div class="inner-wrap">
		<h3>헬로펀딩 법정공시정보 <span class="date-txt"><?=$ym." 말일 기준"?></span></h3>

		<div class="data-wrap">
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
		</div>

		<a href="/company/status/status.php" class="gongsi-view">법정공시정보 전체 보기</a>
		<p>온라인투자연계금융업 및 이용자 보호에 관한 법률에 따라 이용자 권리 보호를 위해 필수적으로 공시하는 정보입니다.</p>
	</div>
</div>


<div class="review">
	<div class="review_t">
		<p>
			헬로펀딩 투자후기
			<span></span>
		</p>
	</div>
	<? echo review('theme/basic', 6, 70); ?>
</div>

<? /*
<!--div class="partner_logo">
	<p>
		제휴사<span></span>
	</p>
	<p style="height:30px;"></p>
	<ul>
		<li><a href="https://www.shinhan.com/index.jsp" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo01_m.jpg" alt="신한은행" ></a></li>
		<li><a href="http://p2plending.or.kr/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo02_m.jpg" alt="한국P2P금융협회" ></a></li>
		<li><a href="http://www.hanatrust.com/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo03_m.jpg" alt="하나자산신탁" ></a></li>
	</ul>
	<ul>
		<li><a href="http://www.scri.co.kr/index.jsp" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo04_m.jpg" alt="SCR서울신용평가" ></a></li>
		<li><a href="http://www.karic.or.kr/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo05_m.jpg" alt="한국부동산리츠투자자문협회" ></a></li>
		<!--<li><a href="http://hyunlaw.co.kr/renew/kor/main/main.asp" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo06_m.jpg" alt="법무법인현" ></a></li>-->
		<!--li><a href="https://www.kyoborealco.co.kr/realco/indexRealco.html" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo07_m.jpg" alt="교보리얼코" ></a></li>
	</ul>
	<ul>

		<!--<li><a href="http://seinacct.co.kr/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo08_m.jpg" alt="세인세무법인" ></a></li>-->
		<!--li><a href="http://www.apexlaw.co.kr/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo09_m.jpg" alt="법무법인 에이펙스" ></a></li>
		<li><a href="http://www.fidelisam.co.kr/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo11_m.jpg" alt="피델리스" ></a></li>
		<li><a href="http://www.kyungilnet.co.kr/main/main.php" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo12_m.jpg" alt="경일감정평가법인" ></a></li>
	</ul>
	<ul>
		<!--<li><a href="http://wowtv.co.kr/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo10_m.jpg" alt="한국경제티브이" ></a></li>-->
		<!--li><a href="http://korfin.kr/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo14_m.jpg" alt="한국핀테크산업협회"></a></li>
		<li><a href="https://www.finnq.com/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo13_m.jpg" alt="핀크"></a></li>
		<li><a href="https://www.sci.co.kr/" target="_blank"><img src="<?=G5_THEME_IMG_URL?>/main/client_logo15_m.jpg" alt="sci평가정보"></a></li>
	</ul>
	<ul>

	<p style="height:30px;clear:both;"></p>
</div-->
*/ ?>


	<!-- 법정공시정보 필수 확인 - 220620 주석처리 완료 -->
<!-- 	<?
		include_once('gongsi_popup.php');
	?> -->

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
simulation(5000000);

// faq
$(".faq a").click(function() {
	$(this).next().slideToggle("fast").parent().siblings().children("dd").hide();
	return false;
});

// 라이브 티비
function popupOpen() {
	var popUrl = "live.html"; //팝업창에 출력될 페이지 URL
	var popOption = "width=640, height=494, top=250, left=600, resizable=no, scrollbars=no, status=no;"; //팝업창 옵션(optoin)
	window.open(popUrl,"",popOption);
}

var galleryTop;
var galleryThumbs;
$(document).ready(function() {
	// 사전투자 설명
	var msg = "펀딩오픈 시간에 투자참여가 어려운 회원분들을 위하여 사전에 투자할 수 있는 서비스입니다. <br><br> <strong>사전 투자 유의사항</strong> <br><br>본 상품은 사전 투자가 가능한 상품으로 목표금액의 <? echo (int)$PRDT['advance_invest_ratio']?>%까지 사전 투자가 진행됩니다. \
				<p>1. 사전 투자는 가상계좌의 예치금으로 투자 가능합니다.</p> \
				<p>2. 사전 투자는 신청순으로 적용됩니다.</p>";

	$('#question_1').webuiPopover({
		title: "사전 투자 서비스란?",
		content: msg,
		closeable: true,
		width: 330,
		trigger: "click",
		placement: 'bottom',
		backdrop: false
	});

	galleryTop = new Swiper('#gallery', {
		spaceBetween: 10,
		onSlideChangeEnd: function() {
			$(document).trigger("slide-change");
		},
		loopedSlides: $("#gallery .swiper-wrapper .swiper-slide").length,
		effect: "fade",
		observer: true,
		observeParents: true,
	});

	galleryThumbs = new Swiper('#gallery-thumbs', {
		spaceBetween: 10,
		centeredSlides: false,
		slidesPerView: 3,
		observer: true,
		observeParents: true,
	});

	// galleryTop.controller.control = galleryThumbs;
	// galleryThumbs.controller.control = galleryTop;

	$(document).on("click", "#gallery-thumbs .swiper-slide", function(e) {
		var index = $(this).index();
		galleryTop.slideTo(index);
	});

	var tmr = setInterval(function() {
		var mb_id_js = "";
		if($('#invest_finished').val() == 'false') {
			$.ajax({
				type: "POST",
				url: "/sscheck.php",
				dataType: "json",
				success: function(data) {
					if (data.mb_id) mb_id_js = data.mb_id;
					ajax_investment(mb_id_js);
				},
				error: function(e) { ajax_investment(mb_id_js); }
			});
		} else {
			clearInterval(tmr);
		}

	}, 5 * 1000);

	function ajax_investment(mb_id_js) {

		$.ajax({
			type: "POST",
			url: "<?=$CONF['api_server_url']?>/investment/ajax_investment.php",
			dataType: "json",
			data: {"prd_idx":<?=$prd_idx;?>, "mb_id": mb_id_js},
			success: function(json) {

				//console.log(json);
				// 3초간 데이터 조회
				// 바뀌는 값들 모집금액, 투자모집률, 남은 모집금액, 버튼들
				$('#invest_finished').val(json.data.invest_finished); // 현재진행상태
				//$('#progressLayer').attr('style', "left:" + (json.data.progress.replace('%', '') <= 100 ? (json.data.progress.replace('%', '') - 11.7) : 88.2)+'%');  // 진행률 및 잔액출력 레이어
				$('#progressBar').attr('style', "width:" + json.data.progress_width); // 진행률 표시

				$('#processBtn').html(json.data.button_data1);
				//$('#progressBtn').html("<li>"+json.data.button_data1+"</li>");

				// progressData, totalRecruitValue
				$('#progressData').text(json.data.progress); // 진행률
				$('#totalRecruitValue').text(json.data.total_invest_amount_k); // 현재 모집금액
				$('#totalRecruitValue').text(json.data.need_recruit_amount_k); // 남은 모집금액

			},
			error: function(e) {

			}
		});

	}

/*
	setInterval(function() {
		if($('#invest_finished').val() == 'false') {
			$.ajax({
				type: "GET",
				url: "/investment/ajax_investment.php",
				dataType: "json",
				data: {prd_idx:<?=$prd_idx;?>},
				success: function(json) {
					// 3초간 데이터 조회
					// 바뀌는 값들 모집금액, 투자모집률, 남은 모집금액, 버튼들
					$('#invest_finished').val(json.data.invest_finished); // 현재진행상태
				//$('#progressLayer').attr('style', "left:" + (json.data.progress.replace('%', '') <= 100 ? (json.data.progress.replace('%', '') - 11.7) : 88.2)+'%');  // 진행률 및 잔액출력 레이어
					$('#progressBar').attr('style', "width:" + json.data.progress_width); // 진행률 표시

					$('#processBtn').html(json.data.button_data1);
				//$('#progressBtn').html("<li>"+json.data.button_data1+"</li>");

					// progressData, totalRecruitValue
					$('#progressData').text(json.data.progress); // 진행률
					$('#totalRecruitValue').text(json.data.total_invest_amount_k); // 현재 모집금액
					$('#totalRecruitValue').text(json.data.need_recruit_amount_k); // 남은 모집금액

				},
				error: function(e) { }
			});
		}
	}, 3 * 1000);
*/
});

$("a[data-toggle='sns_share']").click(function(e) {
	e.preventDefault();
	var current_url = window.location.href;
	var _this       = $(this);
	var sns_type    = _this.attr('data-service');
	var href        = current_url;
	var title       = _this.attr('data-title');
	var img         = $("meta[name='og:image']").attr('content');
	var loc         = "";

	if( ! sns_type || !href || !title) return;

	if(sns_type == 'facebook') { loc = '//www.facebook.com/sharer/sharer.php?u='+href+'&t='+title; }
	else if(sns_type == 'twitter') { loc = '//twitter.com/home?status='+encodeURIComponent(title)+' '+href; }
	else if(sns_type == 'google') { loc = '//plus.google.com/share?url='+href; }
	else if(sns_type == 'pinterest') { loc = '//www.pinterest.com/pin/create/button/?url='+href+'&media='+img+'&description='+encodeURIComponent(title); }
	else if(sns_type == 'kakaostory') { loc = 'https://story.kakao.com/share?url='+encodeURIComponent(href); }
	else if(sns_type == 'band') { loc = 'http://www.band.us/plugin/share?body='+encodeURIComponent(title)+'%0A'+encodeURIComponent(href); }
	else if(sns_type == 'naver') { loc = "http://share.naver.com/web/shareView.nhn?url="+encodeURIComponent(href)+"&title="+encodeURIComponent(title); }
	else if(sns_type == 'url_copy') { copy_trackback(href); }
	else if(sns_type == 'instagram') { alert("현재 지원하지 않는 기능입니다."); loc = ""; return false; }
	else { return false; }

	if(sns_type != 'url_copy') { window.open(loc); }

	return false;
});

function copy_trackback(trb) {
	var IE=(document.all)?true:false;
	if(IE) {
		if(confirm("이 글의 트랙백 주소를 클립보드에 복사하시겠습니까?"))
			window.clipboardData.setData("Text", trb);
	} else {
		temp = prompt("이 글의 트랙백 주소입니다. Ctrl+C를 눌러 클립보드로 복사하세요", trb);
	}
}

$(document).on("keyup", 'input:text[name="principal_value"]', function() {
	var earn_btn = $("p.earn_btn");
	if(earn_btn.css("display") == "block") {
		earn_btn.hide();
	}
});

// 예상수익금 계산
function simulation(price) {
	var price = (price || '0');
	var pattern = /^[0-9]+$/;
	var prd_idx = ($("input:hidden[name='prd_idx']").val() || 0);
	var principal_value = ($("input:text[name='principal_value']").val() || price).replace(/[\D\s\._\-]+/g, "");
	var min_invest_limit = (<?=$CONF['min_invest_limit']?> || 0);

	if(principal_value == "") {
		alert("투자 금액을 입력해주새요");
		$("input:text[name='principal_value']").focus();
		return;
	}
	if(!pattern.test(principal_value) ) {
		alert("투자 금액에 사용할수 없는 문자가 있습니다. 숫자만  입력해주세요.");
		$("input:text[name='principal_value']").focus();
		return;
	}

/*
	if(principal_value < min_invest_limit) {
		alert("최소 금액은 " + number_format(min_invest_limit) + "원 이상 입니다.");
		$("input:text[name='principal_value']").focus();
		return;
	}
*/

	if(principal_value >= <?=$CONF['min_invest_limit']?>) {
		$.ajax({
			url : g5_url + "/investment/ajax_simulation.php",
			type: "POST",
			data : {prd_idx: prd_idx, ajax_principal_value: principal_value, onlyInterest: 'Y'},
			success: function(data, textStatus, jqXHR)
			{
				if(data == "ERROR") {
					alert("시스템 오류입니다. 관리자에 문의해주세요.");
				}
				else if(data == "ERROR-MIN-PRICE") {
					alert("최소 금액은 " + number_format(min_invest_limit) + "원 이상 입니다.");
					$("input[name='principal_value']").focus();
					return;
				}
				else{
					var data = JSON.parse(data);
					if(data.success) {
						$("p.earn_btn").show();
						$("#ajxTotalInterestPrice").text(data.totalInterestPrice);
						$("#ajxInvestMonth").text(data.investMonth);
						$("#ajxMonthAvrPrice").text(data.monthAvrPrice);
						$("#ajaDiffEarning").text(data.diffEarning);
					}
				}
			},
			error: function (jqXHR, textStatus, errorThrown) {

			}
		});
	}
}

function formatNumber(numberString) {
	var selection = window.getSelection().toString();
	if(selection !== '') {
		return;
	}

	if( $.inArray( event.keyCode, [38,40,37,39] ) !== -1 ) {
		return;
	}
	var input = numberString.value;
	var input = input.replace(/[\D\s\._\-]+/g, "");
	input = input ? parseInt( input, 10 ) : 0;
	numberString.value = (input === 0 ) ? "" : input.toLocaleString('ko-KR', {maximumSignificantDigits : 21});
}
</script>

<?

// 투자위험고지 팝업
include_once(G5_PATH."/popup/inc_invest_warning_agree_form.php");

if($prd_idx == '518') {
	include_once(G5_PATH.'/popup/inc_product_358_notice.php');
}

if($prd_idx == '1038') {
	include_once(G5_PATH.'/popup/inc_product_1038_notice.php');
}

// 라이브스트림 준비중 팝업
if($PRDT['stream_url1'] == 'ready') {
	include_once(G5_PATH.'/popup/inc_stream_ready.php');
}

if($co['co_include_tail']) {
	@include_once($co['co_include_tail']);
}
else {
	include_once('./_tail.php');
}


// 실행시간 로깅 종료
if($log_idx) {
	$thrSec  = get_microtime() - $sdt;
	@shell_exec("/usr/local/php/bin/php -q /home/crowdfund/public_html/investment/test_log_finish.exec.php {$log_idx} {$thrSec}");
}


?>