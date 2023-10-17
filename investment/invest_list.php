<?
###############################################################################
## 대출상품리스트
###############################################################################
##	2018-04-05 개편
##	2019-06-27 상품 소트 방식 수정
##	2019-08-12 상품 소트 방식 수정 : 1순위-모집중, 2순위-대기중, 3순위-그외
##  2022-01-04 헬로페이 카테고리명 SCF로 변경
##  2022-05-12 리스트 출력방식 변경
##		투자대기중, 모집중 있을 경우
##			서치폼 기본값을 모집대기중, 모집중, 모집완료인 상품을 서치한 리스트를 출력.
##			상단 메뉴명 : 신규투자상품 출력
##		없으면
##			가장최근 투자자 모집이 완료된 일자를 추출하여 해당일자에 모집완료된 상품 출력
##      상단 메뉴명 : 최근투자상품 출력
###############################################################################

include_once('./_common.php');
include_once(G5_PATH . '/pid_check.inc.php');		// pid 유입체크 및 쿠키생성이 필요한 페이지에만 include

while( list($k, $v) = each($_REQUEST) ) { if(!is_array($k) ) ${$k} = addslashes(clean_xss_tags(trim($v))); }


$g5['title']      = '투자상품목록';
$g5['top_bn']     = "/images/investment/sub_investment.jpg";
$g5['top_bn_alt'] = "투자하기 투자자가 작은 금액들을 모아서 함께 투자하는 새로운 투자 방식입니다.";

if ($co['co_include_head']) {
	@include_once($co['co_include_head']);
}
else {
	include_once('./_head.php');
}


if($member['mb_no']) {
	$developer        = ( in_array($member['mb_id'], $CONF['DEVELOPER']) ) ? true : false;
	$operator         = ( in_array($member['mb_id'], $CONF['OPERATOR']) ) ? true : false;
	$goods_officer    = ( in_array($member['mb_id'], $CONF['GOODS_OFFICER']) ) ? true : false;
	$tmp_special_user = ( in_array($member['mb_id'], array('samo','samo001','samo002')) ) ? true : false;
	//$tmp_user         = ( in_array($member['mb_id'], array('cocktailfunding')) ) ? true : false;
}

//if(($operator || $developer) && count($_REQUEST)) { print_rr($_REQUEST, 'margin-top:100px;color:red'); }

$page = ($_REQUEST['page']) ? $_REQUEST['page'] : 1;
if($page) $page = sql_real_escape_string($page);

$first_memu_title = "전체";

// 서치 파라미터가 전달되지 않은 기본리스트 호출시 투자대기중이거나 모집중인 상품 카운트
//if( $_SERVER['REQUEST_URI']==$_SERVER['PHP_SELF'] ) {
	$sql0 = "
		SELECT
			COUNT(idx) AS cnt
		FROM
			cf_product A
		WHERE 1=1
			AND A.display = 'Y' AND A.isTest = '' AND A.recruit_amount > 10000 AND A.start_num > 0
			AND (
				(A.state='' AND A.open_datetime <= NOW() AND A.start_datetime > NOW() AND A.end_datetime > NOW()) OR
				(A.state='' AND A.start_datetime <= NOW() AND A.end_datetime > NOW() AND invest_end_date = '' AND A.recruit_amount > A.live_invest_amount)
			)";
	$active_product_count = sql_fetch($sql0)['cnt'];

	$first_memu_title = ($active_product_count) ? "신규투자상품" : "최근투자상품";
//}



$common_field = "A.idx, A.start_num, A.state, A.title,\n";
$common_field.= "A.gr_idx, A.ai_grp_idx, A.category,\n";
$common_field.= "A.invest_return, A.invest_period, A.invest_days, A.invest_end_date, A.recruit_period_start, A.recruit_period_end, A.recruit_amount, A.repay_type,\n";
$common_field.= "A.main_image, A.main_image_m, A.open_datetime, A.start_datetime, A.end_datetime, A.loan_start_date, A.loan_end_date,\n";
$common_field.= "A.purchase_guarantees, A.advanced_payment, A.mortgage_guarantees, A.success_example, A.popular_goods, A.display, A.only_vip, A.isConsor,\n";
$common_field.= "A.advance_invest, A.advance_invest_ratio, A.stream_url1, A.stream_url2,\n";
$common_field.= "A.live_invest_amount AS total_invest_amount,\n";
$common_field.= "( GREATEST(0, (CAST(A.recruit_amount AS SIGNED)-CAST(A.live_invest_amount AS SIGNED))) ) AS remain_recruit_amount,\n";		//*** 오버펀딩 발생시 unsigned randge 오류 발생에 대응하기 위하여 형변환 처리함. ***//
//$common_field.= "(A.recruit_amount - A.live_invest_amount) AS remain_recruit_amount,\n";
$common_field.= "((A.live_invest_amount/A.recruit_amount)*100) AS invest_percent,\n";
$common_field.= "(SELECT IFNULL(MAX(turn),0) FROM cf_product_success WHERE product_idx=A.idx AND invest_give_state='Y') AS repay_count,\n";
$common_field.= "A.name_tag";

if( $_SERVER['REQUEST_URI']==$_SERVER['PHP_SELF'] ) {

	if($active_product_count) {

		$psql = "
			SELECT
				$common_field
			FROM
				cf_product A
			WHERE 1
				AND A.display='Y' AND A.isTest='' AND A.recruit_amount>10000 AND A.start_num > 0
				AND (
					(A.state='' AND A.open_datetime <= NOW() AND A.start_datetime > NOW() AND A.end_datetime > NOW()) OR
					(A.state='' AND A.start_datetime <= NOW() AND A.end_datetime > NOW() AND invest_end_date = '' AND A.recruit_amount > A.live_invest_amount) OR
					(A.state='' AND A.start_datetime <= NOW() AND invest_end_date = CURDATE()) OR
					(A.state='1' AND invest_end_date = CURDATE())
				)
			ORDER BY
				CASE
					WHEN (A.state='' AND A.start_datetime <= NOW() AND A.end_datetime > NOW() AND invest_end_date = '' AND A.recruit_amount > A.live_invest_amount) THEN 1
					WHEN (A.state='' AND A.open_datetime <= NOW() AND A.start_datetime > NOW() AND A.end_datetime > NOW()) THEN 2
					ELSE 3
				END,
				-- invest_percent ASC,
				A.start_datetime ASC,
				A.start_num ASC";

	}
	else {

		$psql = "
			SELECT
				$common_field
			FROM
				cf_product A
			WHERE 1
				AND A.display='Y' AND A.isTest='' AND A.recruit_amount>10000 AND A.start_num > 0
				AND A.invest_end_date = (
						SELECT invest_end_date
						FROM cf_product
						WHERE display='Y' AND isTest='' AND recruit_amount > 10000 AND only_vip = '' AND isConsor = ''
						ORDER BY invest_end_date DESC LIMIT 1
					)
			ORDER BY
				A.start_num ASC";

	}

}
else {

	if($CA) $CA = sql_real_escape_string($CA);

	switch($CA) {
		case "A" : $category = '2'; $subtitle.= '부동산';						break;		//부동산
		case "A2": $category = '2'; $subtitle.= '주택담보';					break;		//주택담보
		case "B" : $category = '1'; $subtitle.= '동산';							break;		//동산
		case "C" : $category = '3'; $subtitle.= 'SCF';							break;		//SCF(확정매출채권)
		default  : $category = '';  $subtitle.= '전체';	 $CA = '';  break;		//전체
	}
	$search_div   = sql_real_escape_string($search_div);
	$search_title = get_search_string($search_title);
	$search_title = sql_real_escape_string($search_title);

	$YmdHis = preg_replace("/(-|:| )/", "", G5_TIME_YMDHIS);

	// 전체 상품
	$where = "";
	if($mode=="success") {
		$where.= " AND A.success_example='Y'";
	}
	else {
		if($operator || $goods_officer || $tmp_special_user || $tmp_user ) {
			if($operator || $goods_officer) {
				$where.= " AND (A.display='Y' OR (A.display='N' AND A.isTest='1'))";
			}
			else if($tmp_special_user) {
				if($member['mb_id']=='samo')    $where.= " AND A.idx=144";
				if($member['mb_id']=='samo001') $where.= " AND A.idx=139";
				if($member['mb_id']=='samo002') $where.= " AND A.idx=142";
			}
			else if($tmp_user) {
				if($member['mb_id']=='cocktailfunding') $where.= " AND A.idx=519";			// 컨소시엄 상품
			}
		}
		else {
			$where.= " AND A.display='Y' AND A.isTest='' AND A.recruit_amount>10000";
		}
	}

	if($category) {
		if($CA=='C') {
			$where.= " AND A.category IN('3','1') ";
		}
		else {
			$where.= " AND A.category='$category' ";
			$where.= ($CA=='A2') ? " AND A.mortgage_guarantees='1'" : " AND A.mortgage_guarantees=''";
		}
	}

	if($search_div) {
		if($search_div=='9') {		// 모집중
			$where.= " AND (\n";
			$where.= "	(A.state='' AND A.open_datetime <= NOW() AND A.start_datetime > NOW() AND A.end_datetime > NOW()) OR\n";
			$where.= "	(A.state='' AND A.start_datetime <= NOW() AND A.end_datetime > NOW() AND invest_end_date = '' AND A.recruit_amount > A.live_invest_amount)\n";
			$where.= ")\n";
		}
		else if($search_div=='1') $where.= " AND A.state='1'";						// 이자상환중
		else if($search_div=='2') $where.= " AND A.state IN('2','5')";		// 상환완료
		else if($search_div=='3') $where.= " AND A.state='8'";						// 상환지연,연체
	}
	if($search_title) {
		$search_title = $search_title;
		$where.= " AND A.title LIKE '%".$search_title."%' ";
	}
	//$where.= " AND A.kakaopay_only=''";


	if(G5_IS_MOBILE && $sl=='1') {
		$isFirstPageM = 1;
	}

	$sql0 = "SELECT COUNT(A.idx) AS cnt FROM cf_product A WHERE 1 " . $where;
	$product_count = sql_fetch($sql0)['cnt'];

	if(!$page) $page = 1;
	if(!$size) $size = 15;

	$total_page = ceil($product_count / $size);
	$start_num  = ($page - 1) * $size;


	$sql_order = "";
	if( in_array($search_div, array('','9')) ) {
		$sql_order.= "
			CASE
				WHEN (A.state='' AND A.start_datetime <= NOW() AND A.end_datetime > NOW() AND invest_end_date = '' AND A.recruit_amount > A.live_invest_amount) THEN 1
				WHEN (A.state='' AND A.open_datetime <= NOW() AND A.start_datetime > NOW() AND A.end_datetime > NOW()) THEN 2
				ELSE 3
			END,
			(CASE WHEN (A.state='' AND A.start_datetime <= NOW() AND A.end_datetime > NOW() AND invest_end_date = '' AND A.recruit_amount > A.live_invest_amount) THEN invest_percent END) ASC,
			(CASE WHEN (A.state='' AND A.start_datetime <= NOW() AND A.end_datetime > NOW() AND invest_end_date = '' AND A.recruit_amount > A.live_invest_amount) THEN start_num END) ASC,
			(CASE WHEN (A.state='' AND A.open_datetime <= NOW() AND A.start_datetime > NOW() AND A.end_datetime > NOW()) THEN start_num END) ASC,
		";
	}
	$sql_order.= "A.start_num DESC";

	$sql_limit = ($isFirstPageM) ? "10" : "$start_num, $size";


	///////////////
	// 전체 상품
	///////////////
	$psql = "
		SELECT
			$common_field
		FROM
			cf_product A
		WHERE 1
			$where
		ORDER BY
			$sql_order
		LIMIT
			$sql_limit";

}

//if($mode=='xxx') print_rr($psql);


$pres  = sql_query($psql);
for($i=0; $i<$pres->num_rows; ++$i) {
	$PLIST[$i] = sql_fetch_array($pres);
}
if($pres->num_rows > 0) sql_free_result($pres);

$plist_count = count($PLIST);

for($i=0; $i<$plist_count; ++$i) {
	// 투자모집진행률
	$product_invest_percent = 0;
	if($PLIST[$i]['total_invest_amount']) {
		if($PLIST[$i]['recruit_amount'] > 0) {
			$product_invest_percent = ($PLIST[$i]['total_invest_amount'] / $PLIST[$i]['recruit_amount']) * 100;
			$product_invest_percent = floatCutting($product_invest_percent, 2);
		}
	}
	$PLIST[$i]['invest_percent'] = $product_invest_percent;


	$TMP_AMT = getNumberArr($PLIST[$i]['recruit_amount']);
	$RECRUIT_AMT = array('amount'=>$TMP_AMT[0], 'unit'=>$TMP_AMT[1]);

	$PLIST[$i]['print_recruit_amount']      = $RECRUIT_AMT['amount'];
	$PLIST[$i]['print_recruit_amount_unit'] = $RECRUIT_AMT['unit'];

	$TMP_AMT = $RECRUIT_AMT = NULL;


	$PLIST[$i]['invest_return'] = floatRtrim($PLIST[$i]["invest_return"], 2);

	if($PLIST[$i]['invest_period']==1 && $PLIST[$i]['invest_days'] > 0) {
		$PLIST[$i]['print_invest_period'] = $PLIST[$i]['invest_days'];
		$PLIST[$i]['print_invest_period_unit'] = '일';
	}
	else {
		$PLIST[$i]['print_invest_period'] = $PLIST[$i]['invest_period'];
		$PLIST[$i]['print_invest_period_unit'] = '개월';
	}


	$PLIST[$i]['buttonAndCover'] = productStatusCheck($PLIST[$i]['idx']);

	$PLIST[$i]['detail_url'] = G5_URL."/investment/investment.php?prd_idx=".$PLIST[$i]['idx'];
	$PLIST[$i]['detail_url_script'] = "location.href='".$PLIST[$i]['detail_url']."'";

	// 지정투자상품 설정
	if( in_array($PLIST[$i]['idx'], array('148','157','171','644')) ) {
		if($PLIST[$i]['idx']=='148') {
			if( !$is_admin && !in_array($member['mb_id'], array('moreamc','uildnm2012','yr4msp','sori9th','master')) ) {
				$PLIST[$i]['detail_url_script'] = "alert('[본 투자상품 관련 공지]\\n\\n본 투자상품은 사전에 협의완료된 대출자와 투자자가 제3자에 의한 체계적 담보권리확보 및 자금관리를 목적으로 헬로펀딩을 통해 펀딩을 진행합니다.\\n따라서 지정된 투자자 외 분들의 상품열람 및 투자가 제한되는 점 양해부탁드립니다.');";
			}
		}
		else if($PLIST[$i]['idx']=='157') {
			if( !$is_admin && !in_array($member['mb_id'], array('fintech05','yr4msp','sori9th','master')) ) {
				$PLIST[$i]['detail_url_script'] = "alert('[본 투자상품 관련 공지]\\n\\n본 투자상품은 투자자와 사전에 협의가 완료된 지정투자상품입니다.\\n따라서 지정된 투자자 외 분들의 상품열람 및 투자가 제한되는 점 양해부탁드립니다.');";
			}
		}
		else if($PLIST[$i]['idx']=='171') {
			if( !$is_admin && !in_array($member['mb_id'], array('KJHInvest1019','GraceInvest1102','master')) ) {
				$PLIST[$i]['detail_url_script'] = "alert('[본 투자상품 관련 공지]\\n\\n본 투자상품은 투자자와 사전에 협의가 완료된 지정투자상품입니다.\\n따라서 지정된 투자자 외 분들의 상품열람 및 투자가 제한되는 점 양해부탁드립니다.');";
			}
		}
		else if($PLIST[$i]['idx']=='644') {
			if( !$is_admin && !in_array($member['mb_id'], array('nnsco1129')) ) {
				$PLIST[$i]['detail_url_script'] = "alert('[본 투자상품 관련 공지]\\n\\n본 투자상품은 투자자와 사전에 협의가 완료된 지정투자상품입니다.\\n따라서 지정된 투자자 외 분들의 상품열람 및 투자가 제한되는 점 양해부탁드립니다.');";
			}
		}
	}

	if($PLIST[$i]['main_image']) {
		if(file_exists(G5_DATA_PATH . "/product/".$PLIST[$i]['main_image'])) {
			$target_str	 = preg_replace("/\//", "\/", G5_DATA_PATH);
			$PLIST[$i]['main_image_url'] = preg_replace('/'.$target_str.'/', G5_DATA_URL, $PLIST[$i]['main_image']);
		}
		else {
			$PLIST[$i]['main_image_url'] = "";
		}
	}

	$loan_start_date_day = ($PLIST[$i]['loan_start_date']>'0000-00-00') ? (int)substr($PLIST[$i]['loan_start_date'], -2) : (int)date(d);
	if($PLIST[$i]['invest_period']==1 && $PLIST[$i]['invest_days'] > 0) {
		$PLIST[$i]['total_repay_count'] = 1;
	}
	else {
		$PLIST[$i]['total_repay_count'] = ($loan_start_date_day < 5) ? $PLIST[$i]['invest_period'] : $PLIST[$i]['invest_period'] + 1; //총 지급횟수
	}

	$PLIST[$i]['startDateTime'] = ($PLIST[$i]["start_datetime"]) ? date("Y년 m월 d일 H:i A", strtotime($PLIST[$i]["start_datetime"])) : date("Y년 m월 d일", strtotime($PLIST[$i]["recruit_period_start"]));

	$PLIST[$i]['new_flag'] = (G5_TIME_YMD <= date('Y-m-d', strtotime('+5day', strtotime($PLIST[$i]['open_datetime']))) && ($PLIST[$i]['recruit_amount'] > $PLIST[$i]['total_invest_amount'])) ? true : false;

}


$_qstr = "";
if($CA) $_qstr.= "&CA=$CA";
if($search_div) $_qstr.= "&search_div=$search_div";
if($search_title) $_qstr.= "&search_title=$search_title";
$qstr = preg_replace("/&page=([0-9]){1,10}/", "", $_qstr);
//$qstr = preg_replace("/&page=([0-9]){1,10}/", "", clean_xss_tags($_SERVER['QUERY_STRING']));

//print_rr($PLIST, 'text-align:left;font-size:12px');


if(G5_IS_MOBILE) {
	include_once("invest_list.m.php");
	return;
}


?>

<style>
.text2 { width:300px;height:33px;line-height:31px; padding:0 5px; border:1px solid #AAA; border-radius:3px; vertical-align:middle; }
#content {background-image: none;}
#content .top_title {font-size:30px; color:#333; letter-spacing:-1px; font-weight: 400; padding: 40px 0 30px; background-color: #fff;}
#content .top_text {font-size:15px; color:#999; padding-bottom: 36px; font-family:'SpoqaHanSans','sanserif'}

</style>

<!-- 본문내용 START -->
<div id="content">

	<div>
		<h2 class="top_title">헬로펀딩 투자상품</h2>
		<!--p class="top_text">헬로펀딩은 충분한 상환력을 갖춘 담보투자 상품만을 출시합니다.<br class="br"></p-->
	</div>

	<div class="location_top">
		<!--div class="location"><span><a href="<?=G5_URL?>/investment/invest_list.php">투자상품보기</a></span><b class="blue"><?=$subtitle;?></b></div-->
		<div id="list_start" class="content invest_list2">

<? if( in_array($member['mb_id'], array('test1111','test2222')) ) { ?>
			<div class="list_info">
				본 상품은 헬로펀딩 VIP투자자분만 확인 가능합니다.<br>
				<span>[주의] 정식 투자시작 전 상품의 정보가 외부로 유출되지 않도록 주의 부탁드립니다.</span>
			</div>
<? } ?>

			<!-- 탭메뉴 //-->
			<ul class="tab_type03">
				<li onClick="location.href='<?=$_SERVER['SCRIPT_NAME']?>'" <?=($category=='')?'class="on"':'';?>><?=$first_memu_title?></li> <li class="line">|</li>
				<li onClick="location.href='<?=$_SERVER['SCRIPT_NAME']?>?CA=C'" <?=($CA=='C')?'class="on"':'';?>>SCF</li> <li class="line">|</li>
				<li onClick="location.href='<?=$_SERVER['SCRIPT_NAME']?>?CA=A2'" <?=($CA=='A2')?'class="on"': '';?>>주택담보</li> <li class="line">|</li>
				<li onClick="location.href='<?=$_SERVER['SCRIPT_NAME']?>?CA=A'" <?=($CA=='A')?'class="on"':'';?>>부동산</li>
			</ul>
			<!-- 탭메뉴 //-->


			<!-- 카테고리 타이틀/설명 -->
			<div>
				<div id="<?=$CATEGORY['id']?>" title="<?=$CATEGORY['title']?>">
					<div>
				</div>
					<img src="<?=$CATEGORY['guide_image_a']?>">
				</div>
				<div class="clearfix"></div>
			</div>
			<!-- 카테고리 타이틀/설명 //-->

			<!-- 펼치기메뉴 시작 -->
			<script>
			$('#fold_button').on('click', function() {
				if($('#fold_button').hasClass('fold')) {
					// 펼쳐보기 클릭
					$('#fold_button').removeClass('fold').addClass('unfold');
					$('.hide').slideDown();
					set_cookie('tImgShow', '<?=base64_encode(time())?>', 1, g5_cookie_domain);
				}
				else {
					// 접기 클릭
					$('#fold_button').removeClass('unfold').addClass('fold');
					$('.hide').slideUp();
					set_cookie('tImgShow', '', -1, g5_cookie_domain);
				}
			});
			</script>
			<!-- 펼치기메뉴 끝 //-->

			<div style="width:97%; margin:40px 1.5% 0 1.5%; padding:0;">
				<form method="get">
					<input type="hidden" name="CA" value="<?=$CA?>">
					<ul style="float:right; margin:0 0 -5px 0;">
						<li style="float:left;margin-right:4px;">
							<select name="search_div" class="invest-search-list" style="height:35px;">
								<option value="">전체상품</option>
								<option <?=($search_div=="9")?"selected":""?> value="9">모집중</option>
								<option <?=($search_div=="1")?"selected":""?> value="1">이자상환중</option>
								<option <?=($search_div=="2")?"selected":""?> value="2">상환완료</option>
								<option <?=($search_div=="3")?"selected":""?> value="3">상환지연/연체</option>
							</select>
						</li>
						<li style="float:left;margin-right:4px;"><input type="text" name="search_title" value="<?=$search_title?>" class="text2" placeholder="상품명 검색"></li>
						<li style="float:left"><button type="submit" class="btn_blue">검색</button></li>
					</ul>
				</form>
			</div>

<?
if(!$plist_count) {
	echo '
			<div class="box product_count" style="text-align:center;">
				<p style="margin-top:100px">전체상품 투자모집 마감 되었습니다.</p>
			</div>' . PHP_EOL;
}
else {
?>
			<div class="container space-zero">
				<div class="category_list">
					<ul class="product_list">
<?
	//-- 투자상품리스트 시작 -------------------------
	for($i=0; $i<$plist_count; ++$i) {

		//print_rr($PLIST[$i], 'width:50%;font-size:12px');

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
		// $onlyVipFlag = ($PLIST[$i]['only_vip']=='1') ? '<li><span class="p_vip">법인전용</span></li>' : '';
		$onlyVipFlag = ($PLIST[$i]['only_vip']=='1' && $PLIST[$i]['start_datetime']<"2020-08-27 00:00:00") ? '<li><span class="p_vip">법인전용</span></li>' : '';

		$NAME_TAG = explode("|", $PLIST[$i]['name_tag']);
		$nameTagFlag1 = (in_array("권원보험", $NAME_TAG)) ? '<li><span class="p_ai" style="background:#ff9900">권원보험</span></li>' : '';
		$nameTagFlag2 = (in_array("매입확약", $NAME_TAG)) ? '<li><span class="p_ai" style="background:#cc00ff">매입확약</span></li>' : '';


		// 차주의 요쳉에 의한 과거 상품포함 이미지 교체 (보고플레이) 20220607
		if (preg_match("/온라인 쇼핑몰 확정매출채권/",$PLIST[$i]['title']) AND $PLIST[$i]['idx']<="9062") {  // 차주 이미지 변경 요청
			$main_image_tag = '<img src="/data/product/online_main.jpg" alt="'.$PLIST[$i]['title'].'">';

		} else if($PLIST[$i]['main_image_url']) {
			$main_image_tag = '<img src="/data/product/'.$PLIST[$i]['main_image_url'].'" alt="'.$PLIST[$i]['title'].'">';
		}
		else {
			$main_image_tag = '<img src="/shop/img/no_image.gif" alt="'.$PLIST[$i]['title'].'">';
		}


		$coverCaption = $buttonCaption = NULL;
		$coverCaptionBgClass = "s_cover";

		if($PLIST[$i]['display']=='Y') {

			$coverCaption = '<b>'.$PLIST[$i]['buttonAndCover']['coverCaption'].'</b>';

			// 투자대기중 또는 모집중일 경우(사전투자포함) 블링블링 이미지로 출력
			if($PLIST[$i]['buttonAndCover']['code']=='B01') {
				$coverCaption = '<img src="/theme/2018/img/main/pro_ready.jpg">';
				$coverCaptionBgClass = "s_cover2";
			}
			else if($PLIST[$i]['buttonAndCover']['code']=='B02') {
				$coverCaption = '<img src="/theme/2018/img/main/img_cover2.jpg">';
				$coverCaptionBgClass = "s_cover2";
			}

			$buttonCaption = $PLIST[$i]['buttonAndCover']['buttonCaption'];
			if($PLIST[$i]['buttonAndCover']['code']=='B01') {
				// 대기중일때
				//$buttonCaption.= ($PLIST[$i]['total_invest_amount'] > 0) ? ' <span style="font-size:13px">( 모집된 금액 :  '.price_cutting($PLIST[$i]['total_invest_amount']).'원 )</span>' : '';
			}
			else if($PLIST[$i]['buttonAndCover']['code']=='B02') {
				// 모집중일때
				$buttonCaption.= ($PLIST[$i]['total_invest_amount'] > 0) ? ' <span style="font-size:13px">( 투자가능금액 :  '.price_cutting($PLIST[$i]['remain_recruit_amount']).'원 )</span>' : '';
			//$buttonCaption.= ($PLIST[$i]['total_invest_amount'] > 0) ? ' <span style="font-size:13px">( 모집된 금액 :  '.price_cutting($PLIST[$i]['total_invest_amount']).'원 )</span>' : '';
			}
			else if($PLIST[$i]['buttonAndCover']['code']=='A01') {
				// 이자상환중일때
				$buttonCaption.= ($PLIST[$i]['repay_count']) ? ' <span style="font-size:13px">( 이자지급회차 '.$PLIST[$i]['repay_count'].' / '.$PLIST[$i]['total_repay_count'].' )</span>' : '';
			}

		}
		else {

			$coverCaption = '<b>준비상품</b>';
			$buttonCaption = '내용보기';

		}

?>

						<li <?=( $PLIST[$i]['display']=='N' && ($is_admin=='super' || $developer || $goods_officer || $tmp_special_user) ) ? 'style="opacity:0.5;"' : '';?>>
							<div class="p_img" onClick="<?=$PLIST[$i]['detail_url_script']?>">
								<div class="p_flags">
									<ul>
										<?=$newFlag?><?=$cFlag?><?=$aiFlag?><?=$conFlag?><?=$srmFlag?><?=$adiFlag?><?=$pgFlag?><?=$adpFlag?><?=$onlyVipFlag?><?=$nameTagFlag1?><?=$nameTagFlag2?>
									</ul>
								</div>
								<p class="p_img-cover"></p>
								<p class="<?=$coverCaptionBgClass?>"><?=$coverCaption?></p>
								<?=$main_image_tag?>
							</div>
							<div class="p_info">
								<p class="p_info_tit"><?=$PLIST[$i]['title']?></p>
								<p class="p_info_date">투자시작일 : <?=$PLIST[$i]['startDateTime']?></p>
								<div class="p_info_earn">
									<span><b>(연)</b><?=$PLIST[$i]['invest_return']?><b>%</b></span>
									<span><?=$PLIST[$i]['print_invest_period']?><b><?=$PLIST[$i]['print_invest_period_unit']?></b></span>
									<span><?=$PLIST[$i]['print_recruit_amount']?><b><?=$PLIST[$i]['print_recruit_amount_unit']?>원</b></span>
								</div>
							</div>
							<div class="percent_area">
								<div class="percent">
									<div class="title">
										<div class="pull-left">펀딩 진행률</div>
										<div class="pull-right blue"><?=$PLIST[$i]['invest_percent']?>%</div>
									</div>
									<div class="progressbar" style="width:<?=$PLIST[$i]['invest_percent']?>%">
										<div class="progress"></div>
									</div>
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
			</div>

			<div id="paging_start" style="display:inline-block; width:100%;height:55px;">
				<div id="paging_span" style="background-color:#fff">
					<? paging($product_count, $page, $size); ?>
				</div>
			</div>

<? if($total_page > 1) { ?>
			<style>
			#debug_pannel {position:fixed; display:inline-block; z-index:1002; top:200px;left:30px; width:250px; border:1px solid #bbb; padding:4px;background-color:#FFFF99;}
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

			<script>
			$(document).on('click', '#paging_span span.btn_paging', function() {
				var url = '<?=$_SERVER['SCRIPT_NAME']?>?<?=$qstr?>&page=' + $(this).attr('data-page');
				$(location).attr('href', url);
			});

			$(document).ready(function(){

				var d_count = <?=$plist_count?>;
				var d_unit = 360;
				var lh = d_unit * d_count;

				if(d_count > 2) {

					var fixed_flag = false;

					var	wst = $(window).scrollTop();
					var	wsb = $(document).height() - $(window).height() - $(window).scrollTop();
					var lsp = $('#list_start').offset().top;
					var	lep = $(document).height() - $('#footer').offset().top + 28;

					$('#print_wst').val(wst);
					$('#print_wsb').val(wsb);
					$('#print_lsp').val(lsp);
					$('#print_lep').val(lep);

					$(window).scroll(function() {

						wst = $(window).scrollTop();
						wsb = $(document).height() - $(window).height() - $(window).scrollTop();

						if(wst >= lsp && wsb >= lep) {
							if(fixed_flag == false) {
								$('#paging_span').css({'position':'fixed', 'opacity':'0.95', 'background-color':'#fff', 'border-top':'1px dotted #AAA', 'z-index':'20', 'left':'0', 'width':'100%', 'bottom':'0', 'padding':'10px 0'});
								fixed_flag = true;
							}
						}
						else {
							if(fixed_flag == true) {
								$('#paging_span').css({'position':'', 'opacity':'1', 'border-top':'0', 'padding':'0'});
								fixed_flag = false;
							}
						}

						$('#print_wst').val(wst);
						$('#print_wsb').val(wsb);
						$('#print_lsp').val(lsp);
						$('#print_lep').val(lep);

					});
				}

			});
			</script>
<? } ?>

<?
}
?>
		</div>
	</div>
</div>

<?
if($co['co_include_tail']) {
	@include_once($co['co_include_tail']);
}
else {
	include_once('./_tail.php');
}
?>