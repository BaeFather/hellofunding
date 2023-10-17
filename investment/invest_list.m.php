<style>
#list_start select.invest-search-list {border: 1px solid #aaa; border-radius: 3px; color: #000;}
.text2 { width:200px;height:38px;line-height:36px; font-size:14pt; padding:0 5px; border:1px solid #AAA; border-radius:3px; vertical-align:middle; }
</style>

<div id="content">

	<div id="list_start" class="content invest_list2">

		<!-- 탭메뉴 //-->
		<ul class="tab_type03">
			<li onClick="location.href='<?=$_SERVER['SCRIPT_NAME']?>'" <?=($category=='')?'class="on"':'';?>><?=$first_memu_title?></li>
			<li onClick="location.href='<?=$_SERVER['SCRIPT_NAME']?>?CA=C'" <?=($CA=='C')?'class="on"':'';?>>SCF</li>
			<li onClick="location.href='<?=$_SERVER['SCRIPT_NAME']?>?CA=A2'" <?=($CA=='A2')?'class="on"':'';?>>주택담보</li>
			<li onClick="location.href='<?=$_SERVER['SCRIPT_NAME']?>?CA=A'" <?=($CA=='A')?'class="on"':'';?>>부동산</li>
			<!--<li onClick="location.href='<?=$_SERVER['SCRIPT_NAME']?>?CA=B'" <?=($CA=='B')?'class="on"':'';?>>동산</li>-->
		</ul>
		<!-- 탭메뉴 //-->

		<div style="width:97%; margin:20px 1.5% 0 1.5%; padding:0;">
			<form method="get">
				<input type="hidden" name="CA" value="<?=$CA?>">
				<ul style="width:100%; margin:0 0 -5px 0;">
					<li style="float:left;width:29%;margin-right:1%;">
						<select name="search_div" class="invest-search-list" style="height:38px;width:99%;">
							<option value="">전체상품</option>
							<option <?=$search_div=="9"?"selected":""?> value="9">모집중</option>
							<option <?=$search_div=="1"?"selected":""?> value="1">이자상환중</option>
							<option <?=$search_div=="2"?"selected":""?> value="2">상환완료</option>
							<option <?=$search_div=="3"?"selected":""?> value="3">상환지연/연체</option>
						</select>
					</li>
					<li style="float:left;width:49%;margin-right:1%;"><input type="text" name="search_title" value="<?=$search_title?>" class="text2" style="width:99%" placeholder="상품명 검색"></li>
					<li style="float:left;width:20%;"><button type="submit" class="btn_blue" style="width:99%">검색</button></li>
				</ul>
			</form>
		</div>

<?
if(!$plist_count) {
	echo '
		<div class="box product_count" style="padding:150px 0;background:#FAFAFA;text-align:center;">
			<p>등록된 상품이 없습니다.</p>
		</div>' . PHP_EOL;
}
else {
?>
		<div class="p_list">
			<ul>
<?
	//-- 투자상품리스트 시작 -------------------------
	for($i=0; $i<$plist_count; ++$i) {

		switch($PLIST[$i]['category']) {
			case '1' : $cFlag = '<li><span class="p_ca-B">동산</span></li>'; break;
			case '2' : $cFlag = ($PLIST[$i]['mortgage_guarantees']=='1') ? '<li><span class="p_ca-A2">주택담보</span></li>' : '<li><span class="p_ca-A">부동산</span></li>'; break;
			case '3' : $cFlag = '<li><span class="p_ca-C">SCF</span></li>'; break;
			default  : $cFlag = ''; break;
		}

		$aiFlag  = ($PLIST[$i]['ai_grp_idx']>0) ? '<li><span class="p_ai">자동투자</span></li>' : '';
		$newFlag = ($PLIST[$i]['new_flag']=='Y') ? '<li><span class="p_new">N</span></li>' : '';
		$srmFlag = ($PLIST[$i]["stream_url1"] OR $PLIST[$i]["stream_url2"]) ? '<li><span class="p_live_tv"><i class="fa fa-tv"></i> LIVE TV</span></li>' : '';
		$adiFlag = ($PLIST[$i]['advance_invest']=='Y') ? '<li><span class="p_adir">사전투자 ' . floatRtrim($PLIST[$i]['advance_invest_ratio']).'% <i class="fa fa-question-circle" id="question_1"></i></span></li>' : '';
		$pgFlag  = ($PLIST[$i]['purchase_guarantees']=='Y' && preg_match("/dev\.hello/", G5_URL)) ? '<li><span class="p_pg">채권매입계약</span></li>' : '';
		$adpFlag = ($PLIST[$i]['advanced_payment']=='Y') ? '<li><span class="p_adpy">이자 선지급</span></li>' : '';
		$conFlag = ($PLIST[$i]['isConsor']=='1') ? '<li><span class="p_con">컨소시엄</span></li>' : '';
		// 2020년 10월 7일 이상규 과장의 요청으로 전승찬 처리 2020년 8월 27일 이후의 법인전용 상품에 대해서는 리스트에서만 법인 전용 표시를 안함
		//$onlyVipFlag = ($PLIST[$i]['only_vip']=='1') ? '<li><span class="p_vip">법인전용</span></li>' : '';
		$onlyVipFlag = ($PLIST[$i]['only_vip']=='1' AND $PLIST[$i]['start_datetime']<"2020-08-27 00:00:00") ? '<li><span class="p_vip">법인전용</span></li>' : '';

		$NAME_TAG = explode("|", $PLIST[$i]['name_tag']);
		$nameTagFlag1 = (in_array("권원보험", $NAME_TAG)) ? '<li><span class="p_ai" style="background:#ff9900">권원보험</span></li>' : '';
		$nameTagFlag2 = (in_array("매입확약", $NAME_TAG)) ? '<li><span class="p_ai" style="background:#cc00ff">매입확약</span></li>' : '';

		if (preg_match("/온라인 쇼핑몰 확정매출채권/",$PLIST[$i]['title']) AND $PLIST[$i]['idx']<="9062") {  // 차주 이미지 변경 요청
			// 차주의 요쳉에 의한 과거 상품포함 이미지 교체 (보고플레이) 20220607
			$main_image_tag = '<img src="/data/product/online_main.jpg" alt="'.$PLIST[$i]['title'].'" width="100%" height="230px">';

		} else if($PLIST[$i]['main_image_url']) {
			$main_image_tag = '<img src="/data/product/'.$PLIST[$i]['main_image_url'].'" alt="'.$PLIST[$i]['title'].'" width="100%" height="230px">';
		}
		else {
			$main_image_tag = '<img src="/shop/img/no_image.gif" alt="'.$PLIST[$i]['title'].'" width="100%" height="230px">';
		}


		$coverCaption = $buttonCaption = NULL;
		$coverCaptionBgClass = "s_cover";

		if($PLIST[$i]['display']=='Y') {

			// 모집중일 경우(사전투자포함) 모집중 블링블링 이미지로 출력
			$coverCaption = '<b>'.$PLIST[$i]['buttonAndCover']['coverCaption'].'</b>';
			if($PLIST[$i]['buttonAndCover']['code']=='B01') {
				$coverCaption = '<img src="/theme/2018/img/main_m/pro_ready_m.png" style="width:100%;height:100%;">';
				$coverCaptionBgClass = "s_cover2";
			}
			else if($PLIST[$i]['buttonAndCover']['code']=='B02') {
				$coverCaption = '<img src="/theme/2018/img/main_m/img_cover2_m.png" style="width:100%;height:100%;">';
				$coverCaptionBgClass = "s_cover2";
			}

			$buttonCaption = $PLIST[$i]['buttonAndCover']['buttonCaption'];
			if($PLIST[$i]['buttonAndCover']['code']=='B01') {
				// 대기중일때
				//$buttonCaption.= ($PLIST[$i]['total_invest_amount'] > 0) ? ' <span style="font-size:12px">( 모집된 금액 :  '.price_cutting($PLIST[$i]['total_invest_amount']).'원 )</span>' : '';
			}
			else if($PLIST[$i]['buttonAndCover']['code']=='B02') {
				// 모집중일때
				$buttonCaption.= ($PLIST[$i]['total_invest_amount'] > 0) ? ' <span style="font-size:13px">( 투자가능금액 :  '.price_cutting($PLIST[$i]['remain_recruit_amount']).'원 )</span>' : '';
			//$buttonCaption.= ($PLIST[$i]['total_invest_amount'] > 0) ? ' <span style="font-size:12px">( 모집된 금액 :  '.price_cutting($PLIST[$i]['total_invest_amount']).'원 )</span>' : '';
			}
			else if($PLIST[$i]['buttonAndCover']['code']=='A01') {
				// 이자상환중일때
				$buttonCaption.= ($PLIST[$i]['repay_count']) ? ' <span style="font-size:12px">( 지급회차 '.$PLIST[$i]['repay_count'].' / '.$PLIST[$i]['total_repay_count'].' )</span>' : '';
			}

		}
		else {

			$coverCaption = '<b>준비상품</b>';
			$buttonCaption = '내용보기';

		}

?>

				<li <?=($PLIST[$i]['display']=='N' && ($is_admin=='super' || $developer || $goods_officer || $tmp_special_user)) ? 'style="opacity:0.5;"' : '';?>>
					<div class="p_flags">
						<ul>
							<?=$newFlag?><?=$cFlag?><?=$aiFlag?><?=$conFlag?><?=$srmFlag?><?=$adiFlag?><?=$pgFlag?><?=$adpFlag?><?=$onlyVipFlag?><?=$nameTagFlag1?><?=$nameTagFlag2?>
						</ul>
					</div>
					<p class="p_img_cover" onClick="<?=$PLIST[$i]['detail_url_script']?>"></p>
					<p class="<?=$coverCaptionBgClass?>" onClick="<?=$PLIST[$i]['detail_url_script']?>"><?=$coverCaption?></p>
					<div class="main_image" onClick="<?=$PLIST[$i]['detail_url_script']?>"><?=$main_image_tag?></div>
					<div class="p_info">
						<p class="p_title"><?=$PLIST[$i]['title']?></p>
						<p class="p_date">투자시작일 : <?=$PLIST[$i]['startDateTime']?></p>
						<ul class="p_total">
							<li><span><b>(연)</b><?=$PLIST[$i]['invest_return']?></span> <b>%</b></li>
							<li><span><?=$PLIST[$i]['print_invest_period']?></span> <b><?=$PLIST[$i]['print_invest_period_unit']?></b></li>
							<li><span><?=$PLIST[$i]['print_recruit_amount']?></span> <b><?=$PLIST[$i]['print_recruit_amount_unit']?>원</b></li>
						</ul>
					</div>
					<div class="percent">
						<div class="title">
							<div class="pull-left">펀딩 진행률</div>
							<div class="pull-right blue"><?=$PLIST[$i]['invest_percent']?>%</div>
						</div>
						<div class="progressbar" style="width:<?=$PLIST[$i]['invest_percent']?>%">
							<div class="progress"></div>
						</div>
					</div>
					<div class="p_btn">
						<a href="<?=$PLIST[$i]['detail_url']?>"><?=$buttonCaption?></a>
					</div>
				</li>

<?
	}
?>
			</ul>
		</div>

		<div id="paging_start" style="display:inline-block; width:100%;height:45px;">
			<div id="paging_span" style="width:100%:border:1px solid blue">
				<? paging($product_count, $page, $size); ?>
			</div>
		</div>

<?		if($total_page > 1) { ?>
		<style>
		#debug_pannel {position:fixed; z-index:1002; top:200px;left:30px; width:250px; border:1px solid #bbb; padding:4px;background-color:#FFFF99;}
		#debug_pannel ul {display:inline-block;}
		#debug_pannel ul > li {height:22px;float:left;}
		#debug_pannel input {width:80px;text-align:right;}
		</style>
		<div id="debug_pannel" style="display:<?=($_COOKIE['debug_mode'])?'block':'none';?>">
			<ul>
				<li style="width:150px">window scroll top</li>
				<li style="width:90px"><input type="text" id="print_wst"></li>
			</ul>
			<ul>
				<li style="width:150px">window scroll bottom</li>
				<li style="width:90px"><input type="text" id="print_wsb"></li>
			</ul>
			<ul>
				<li style="width:150px">layer on point</li>
				<li style="width:90px"><input type="text" id="print_lsp"></li>
			</ul>
			<ul>
				<li style="width:150px">layer off point</li>
				<li style="width:90px"><input type="text" id="print_lep"></li>
			</ul>
		</div>

		<script type="text/javascript">
		$(document).on('click', '#paging_span span.btn_paging', function() {
			var url = '<?=$_SERVER['SCRIPT_NAME']?>?<?=$qstr?>&page=' + $(this).attr('data-page');
			$(location).attr('href', url);
		});
		</script>
<?		} ?>

<?
}
?>
	</div>

<? if($isFirstPageM) { ?>
	<style>
	.more_zone {display:inline-block;width:92%;margin:0 4% 20px;text-align:center;}
	.addstyle {width:100%;color:#3366FF;border:1px solid #000;border-raduis:3px;font-size:1.2em;font-weight:bold;}
	</style>
	<div id="more" class="more_zone">
		<button id="more_button" type="button" class="btn_link addstyle">더보기</button>
	</div>
	<script>
	$('#more_button').click(function() {
		location.replace('/investment/invest_list.php');
	});
	</script>
<? } ?>

</div>

<?
if($co['co_include_tail']){
	@include_once($co['co_include_tail']);
}
else {
	include_once('./_tail.php');
}
?>