<?
///////////////////////////////////////////////////////
//	/adm/product_sales.php 를 본 파일로 대체한다.
///////////////////////////////////////////////////////

$sub_menu = '700100';
include_once('./_common.php');


auth_check($auth[$sub_menu], 'w');
//if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

$g5['title'] = $menu['menu700'][1][1];

include_once(G5_ADMIN_PATH.'/admin.head.php');

while( list($k, $v) = each($_REQUEST) ) { if(!is_array($k)) ${$k} = trim($v); }


$STATE['1A']	= " AND A.end_datetime > NOW() AND A.invest_end_date='' AND display='Y'";								// 투자금 모집중
$STATE['1B']  = " AND A.state='' AND A.invest_end_date!=''";																					// 투자금 모집완료
$STATE['3']   = " AND (A.state='3' OR (A.end_datetime < NOW() AND A.invest_end_date=''))";						// 투자금 모집실패
$STATE['1']   = " AND A.state='1'";																																		// 이자상환중
$STATE['2']   = " AND A.state='2'";																																		// 상품마감(정상상환)
$STATE['5']   = " AND A.state='5'";																																		// 상품마감(중도상환)
$STATE['4']   = " AND A.state='4'";																																		// 부실
$STATE['6']   = " AND A.state='6'";																																		// 대출취소(기표전)
$STATE['7']   = " AND A.state='7'";																																		// 대출취소(기표후)
$STATE['2+5'] = " AND A.state IN('2','5')";																														// 상품마감(전체)
$STATE['6+7'] = " AND A.state IN('6','7')";																														// 대출취소(전체)
$STATE['8']   = " AND A.state='8'";																																		// 연체
$STATE['9']   = " AND A.state='9'";																																		// 부도(상환불가)

$CATEGORY['1']  = " AND A.category='1'";
$CATEGORY['2']  = " AND A.category='2'";
$CATEGORY['2A'] = " AND A.category='2' AND A.mortgage_guarantees=''";
$CATEGORY['2B'] = " AND A.category='2' AND A.mortgage_guarantees='1'";
$CATEGORY['3']  = " AND A.category='3'";
$CATEGORY['3A'] = " AND A.category='3' AND A.category2='1'";
$CATEGORY['3B'] = " AND A.category='3' AND A.category2='2'";


$where = "1=1 AND A.isTest='' AND start_datetime < NOW()";
if($state) $where.= $STATE[$state];
if($category) $where.= $CATEGORY[$category];
if($prdtFld && $prdtKwd) {
	if( in_array($prdtFld, array('idx','start_num')) ) {
		if( preg_match("/\,/", $prdtKwd) ) {
			$where.= " AND A.{$prdtFld} IN(".preg_replace("/( )/", "", $prdtKwd).") ";
		}
		else {
			$where.= " AND A.{$prdtFld} = '$prdtKwd' ";
		}
	}
	else if($prdtFld == 'title') $where.= " AND A.{$prdtFld} LIKE '%".$prdtKwd."%'";
	else if($prdtFld == 'insert_date') $where.= " AND A.{$prdtFld} = '".$prdtKwd."') ";
}

if($mbFld && $mbKwd) {
	if($mbFld == 'i_mb_no') {
		$where.= "
			AND ( SELECT COUNT(idx) FROM cf_product_invest WHERE product_idx=A.idx AND member_idx='".$mbKwd."' AND invest_state IN('Y','R') ) > 0";
	}
	else if($mbFld == 'i_mb_id') {
		$where.= "
			AND (
				SELECT
					COUNT(AA.idx)
				FROM
					cf_product_invest AA
				LEFT JOIN
					g5_member BB  ON AA.member_idx=BB.mb_no
				WHERE 1
					AND AA.product_idx = A.idx
					AND BB.mb_id = '".$mbKwd."'
					AND AA.invest_state IN('Y','R')
			) > 0";
	}
	else if($mbFld == 'l_mb_name') {
		$where.= " AND (B.mb_name  = '".$mbKwd."')";
	}
	else if($mbFld == 'l_mb_co_name') {
		$where.= " AND (B.mb_co_name  = '".$mbKwd."')";
	}

}

//echo $where;


//투자자수 검색
if($_REQUEST['invest_capa']) {
	if($_REQUEST['invest_capa']=='_99') {
		$where.= " AND ( SELECT COUNT(idx) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state IN('Y','R') ) < 100 ";
	}
	else if($_REQUEST['invest_capa']=='100-199') {
		$where.= " AND ( SELECT COUNT(idx) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state IN('Y','R') ) BETWEEN 100 AND 199 ";
	}
	else if($_REQUEST['invest_capa']=='200-500') {
		$where.= " AND ( SELECT COUNT(idx) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state IN('Y','R') ) BETWEEN 200 AND 499 ";
	}
	else if($_REQUEST['invest_capa']=='500-1000') {
		$where.= " AND ( SELECT COUNT(idx) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state IN('Y','R') ) BETWEEN 300 AND 999 ";
	}
	else if($_REQUEST['invest_capa']=='1000_') {
		$where.= " AND ( SELECT COUNT(idx) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state IN('Y','R') ) >= 1000 ";
	}
}

//투자기간 검색
if($invest_period) {
	if($invest_period=='short') {
		$where.= " AND (A.invest_period = '1' AND A.invest_days > 0) ";
	}
	else if($invest_period=='long') {
		$where.= " AND A.invest_period > '24'";
	}
	else {
		$where.= " AND A.invest_period = '".$invest_period."'";
	}
}

if($date_field) {
	if($sdate) $where.= " AND $date_field >= '$sdate' ";
	if($edate) $where.= " AND $date_field <= '$edate' ";
}


$sql = "
	SELECT
		COUNT(A.idx) AS cnt
	FROM
		cf_product A
	LEFT JOIN
		g5_member B  ON A.loan_mb_no=B.mb_no
	WHERE
		$where";

$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows        = ($_REQUEST['rows']) ? $_REQUEST['rows'] : 10;		// 페이지당 나열 수
$total_page  = ceil($total_count / $rows);							// 전체페이지
$page        = ($page > 0) ? $page : 1;
$from_record = ($page - 1) * $rows;											// 시작 열
$num         = $total_count - $from_record;


$PCNT = array();

$sqlx_common = "SELECT COUNT(A.idx) AS cnt FROM cf_product A WHERE 1 AND A.isTest='' AND A.start_datetime < NOW() ";

// 상품수: 전체
$sqlx = $sqlx_common;
$DATA = sql_fetch($sqlx);
$PCNT['0'] = $DATA['cnt'];

// 상품수 : 투자금 모집중
$sqlx = $sqlx_common . $STATE['1A'];
$DATA = sql_fetch($sqlx);
$PCNT['1A'] = $DATA['cnt'];

// 상품수 : 투자금 모집완료
$sqlx = $sqlx_common . $STATE['1B'];
$DATA = sql_fetch($sqlx);
$PCNT['1B'] = $DATA['cnt'];

// 상품수: 이자상환중
$sqlx = $sqlx_common . $STATE['1'];
$DATA = sql_fetch($sqlx);
$PCNT['1'] = $DATA['cnt'];

// 상품수: 이자상환중
$sqlx = $sqlx_common . $STATE['8'];
$DATA = sql_fetch($sqlx);
$PCNT['8'] = $DATA['cnt'];

// 상품수: 정상상환
$sqlx = $sqlx_common . $STATE['2'];
$DATA = sql_fetch($sqlx);
$PCNT['2'] = $DATA['cnt'];

// 상품수: 투자금 모집실패
$sqlx = $sqlx_common . $STATE['3'];
$DATA = sql_fetch($sqlx);
$PCNT['3'] = $DATA['cnt'];

// 상품수 : 부실
$sqlx = $sqlx_common . $STATE['4'];
$DATA = sql_fetch($sqlx);
$PCNT['4'] = $DATA['cnt'];

// 상품수 : 중도상환
$sqlx = $sqlx_common . $STATE['5'];
$DATA = sql_fetch($sqlx);
$PCNT['5'] = $DATA['cnt'];

// 상품수 : 대출취소(기표전)
$sqlx = $sqlx_common . $STATE['6'];
$DATA = sql_fetch($sqlx);
$PCNT['6'] = $DATA['cnt'];

// 상품수 : 대출취소(기표후)
$sqlx = $sqlx_common . $STATE['7'];
$DATA = sql_fetch($sqlx);
$PCNT['7'] = $DATA['cnt'];

// 상품수 : 대출취소(기표후)
$sqlx = $sqlx_common . $WHERE['6+7'];
$DATA = sql_fetch($sqlx);
$PCNT['6+7'] = $DATA['cnt'];


$date = date('Y-m-d H:i:s');
$static_repay_day = 5;


// 정렬우선순위 : 모집중 > 모집완료 > 이후
$sql = "
	SELECT
		A.idx, A.gr_idx, A.state, A.category, A.mortgage_guarantees, A.title, A.recruit_amount, A.invest_period, A.invest_days,
		A.invest_return, A.loan_interest_rate, A.withhold_tax_rate, A.loan_interest_type, A.loan_advanced_count, A.loan_usefee, A.invest_usefee, A.invest_usefee_type,
		A.recruit_period_start, A.recruit_period_end, A.repay_type, A.start_datetime, A.start_date, A.end_datetime, A.end_date, A.invest_end_date, A.loan_start_date, A.loan_end_date, A.cancel_date,
		A.display, A.purchase_guarantees, A.advanced_payment, A.success_example, A.popular_goods, A.advance_invest, A.advance_invest_ratio, A.ib_trust, A.insert_date,
		A.loan_mb_no, A.loan_mb_f_no, A.repay_acct_no, ib_product_regist, B.mb_no, B.mb_f_no,
		(SELECT COUNT(idx) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state IN('Y','R')) AS invest_count,
		(SELECT COUNT(idx) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state IN('Y','R') AND mb_type='1') AS indi_invest_count,
		(SELECT COUNT(idx) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state IN('Y','R') AND mb_type='2') AS corp_invest_count,
		(SELECT IFNULL(SUM(amount),0) FROM cf_product_invest WHERE product_idx=A.idx AND invest_state IN('Y','R')) AS invest_amount
	FROM
		cf_product A
	LEFT JOIN
		g5_member B  ON A.loan_mb_no=B.mb_no
	WHERE
		$where
	ORDER BY
		(CASE
			WHEN A.start_datetime <= NOW() AND A.end_datetime >= NOW() AND A.invest_end_date='' THEN 1
			WHEN A.state='' AND A.start_datetime <= NOW() AND A.end_datetime >= NOW() AND A.invest_end_date!='' THEN 2 ELSE 3
		END),
		A.open_datetime DESC,
		A.idx DESC
	LIMIT
		$from_record, $rows";
//if($member['mb_id']=='admin_sori9th') print_rr($sql);
$result = sql_query($sql);
$rcount = $result->num_rows;

for($i=0; $i<$rcount; $i++) {

	$LIST[$i] = sql_fetch_array($result);

	$LIST[$i]['loan_started'] = ( ($LIST[$i]['loan_start_date'] > '0000-00-00' && $LIST[$i]['loan_end_date'] > '0000-00-00') && !in_array($LIST[$i]['state'], array('','3','6','7')) ) ? 1 : 0;

	// 특별처리상품 플래그
	$exceptionProduct = ($LIST[$i]['idx'] <= 172  && substr($LIST[$i]['loan_end_date'],-2) <= '05') ? 1 : 0;
	$shortTermProduct = ($LIST[$i]['invest_days'] > 0) ? 1 : 0;

	$LIST[$i]['repay_count'] = ($LIST[$i]['loan_started']) ? repayTurnCount($LIST[$i]['loan_start_date'], $LIST[$i]['loan_end_date'], $exception_product, $shortTermProduct) : '';
	$LIST[$i]['total_days'] = ($LIST[$i]['loan_started']) ? repayDayCount($LIST[$i]['loan_start_date'], $LIST[$i]['loan_end_date']) : '';

	$LIST[$i]['investor_id'] = sql_fetch("SELECT B.mb_id FROM cf_product_invest A LEFT JOIN g5_member B  ON A.member_idx=B.mb_no WHERE A.product_idx = '".$LIST[$i]['idx']."' ORDER BY A.amount DESC, A.idx DESC LIMIT 1")['mb_id'];

}

sql_free_result($result);

?>

<style>
.border-t0 { border-top:0 }
.border-b0 { border-bottom:0 }
.border-l0 { border-left:0 }
.border-r0 { border-right:0 }
.fGray { color:#AAAAAA; }
.fBlue { color:#3366FF; }

.tblx { font-size:12px; }
.tblx td { padding:4px 6px 3px; }
</style>

<div class="tbl_head02 tbl_wrap" style="min-width:1500px;">

	<table class="table-bordered" style="font-size:14px">
		<colgroup>
			<col style="width:%">
			<col style="width:9%">
			<col style="width:9%">
			<col style="width:9%">
			<col style="width:9%">
			<col style="width:9%">
			<col style="width:9%">
			<col style="width:9%">
			<col style="width:9%">
			<col style="width:9%">
			<col style="width:9%">
		</colgroup>
		<tr>
			<th rowspan="2" style="background:#F0F8FF">전체</th>
			<th rowspan="2" style="background:#F0F8FF">투자금 모집중</th>
			<th rowspan="2" style="background:#F0F8FF">투자금 모집완료</th>
			<th rowspan="2" style="background:#F0F8FF">이자상환중</th>
			<th rowspan="2" style="background:#F0F8FF">연체</th>
			<th colspan="2" style="background:#F0F8FF">상품마감</th>
			<th rowspan="2" style="background:#F0F8FF">투자금 모집실패</th>
			<th colspan="2" style="background:#F0F8FF">대출취소</th>
			<th rowspan="2" style="background:#F0F8FF">부실</th>
		</tr>
		<tr>
			<th style="background:#F0F8FF">정상상환</th>
			<th style="background:#F0F8FF">중도상환</th>
			<th style="background:#F0F8FF">기표전</th>
			<th style="background:#F0F8FF">기표후</th>
		</tr>
		<tr class="odd">
			<td align="center" alt="전체"><a href="?page=1"><?=number_format($PCNT['0'])?></a></td>
			<td align="center" alt="투자금 모집중"><a href="?state=1A" style="color:<?=($state=='1A')?'#FF2222':''?>"><?=number_format($PCNT['1A'])?></a></td>
			<td align="center" alt="투자금 모집완료"><a href="?state=1B" style="color:<?=($state=='1B')?'#FF2222':''?>"><?=number_format($PCNT['1B'])?></a></td>
			<td align="center" alt="이자상환중"><a href="?state=1" style="color:<?=($state=='1')?'#FF2222':''?>"><?=number_format($PCNT['1'])?></a></td>
			<td align="center" alt="연체"><a href="?state=8" style="color:<?=($state=='8')?'#FF2222':''?>"><?=number_format($PCNT['8'])?></a></td>

			<td align="center" alt="정상상환"><a href="?state=2" style="color:<?=($state=='2')?'#FF2222':''?>"><?=number_format($PCNT['2'])?></a></td>
			<td align="center" alt="중도상환"><a href="?state=5" style="color:<?=($state=='5')?'#FF2222':''?>"><?=number_format($PCNT['5'])?></a></td>

			<td align="center" alt="투자금 모집실패"><a href="?state=3" style="color:<?=($state=='3')?'#FF2222':''?>"><?=number_format($PCNT['3'])?></a></td>
			<td align="center" alt="대출취소(기표전)"><a href="?state=6" style="color:<?=($state=='6')?'#FF2222':''?>"><?=number_format($PCNT['6'])?></a></td>
			<td align="center" alt="대출취소(기표후)"><a href="?state=7" style="color:<?=($state=='7')?'#FF2222':''?>"><?=number_format($PCNT['7'])?></a></td>
			<td align="center" alt="부실"><a href="?state=4" style="color:<?=($state=='4')?'#FF2222':''?>"><?=number_format($PCNT['4'])?></a></td>
		</tr>
	</table>

	<form method="get" class="form-horizontal">
		<ul style="list-style:none;display:inline-block; padding:0; margin:8px 0 4px 0;">
			<li style="float:left;">
				<select name="state" class="form-control input-sm">
					<option value="">::진행상태::</option>
					<option value="1A" <?=($_REQUEST['state']=='1A') ? 'selected' : ''; ?>>투자금 모집중</option>
					<option value="1B" <?=($_REQUEST['state']=='1B') ? 'selected' : ''; ?>>투자금 모집완료</option>
					<option value="3" <?=($_REQUEST['state']=='3') ? 'selected' : ''; ?>>투자금 모집실패</option>
					<option value="1" <?=($_REQUEST['state']=='1') ? 'selected' : ''; ?>>이자상환중</option>
					<option value="2+5" <?=($_REQUEST['state']=='2+5') ? 'selected' : ''; ?>>상품마감(전체)</option>
					<option value="2" <?=($_REQUEST['state']=='2') ? 'selected' : ''; ?>>- 정상상환</option>
					<option value="5" <?=($_REQUEST['state']=='5') ? 'selected' : ''; ?>>- 중도상환</option>
					<option value="8" <?=($_REQUEST['state']=='8') ? 'selected' : ''; ?>>연체</option>
					<option value="4" <?=($_REQUEST['state']=='4') ? 'selected' : ''; ?>>부실</option>
					<option value="6+7" <?=($_REQUEST['state']=='6+7') ? 'selected' : ''; ?>>대출취소(전체)</option>
					<option value="6" <?=($_REQUEST['state']=='6') ? 'selected' : ''; ?>>- 기표전 취소</option>
					<option value="7" <?=($_REQUEST['state']=='7') ? 'selected' : ''; ?>>- 기표후 취소</option>
				</select>
			</li>
			<li style="float:left;margin-left:4px;">
				<select name="category" class="form-control input-sm">
					<option value="">::카테고리::</option>
					<option value="2" <?=($category=='2') ? 'selected' : ''; ?>>부동산</option>
					<option value="2A" <?=($category=='2A') ? 'selected' : ''; ?>> - PF</option>
					<option value="2B" <?=($category=='2B') ? 'selected' : ''; ?>> - 주택담보</option>
					<option value="1" <?=($category=='1') ? 'selected' : ''; ?>>동산</option>
					<option value="3" <?=($category=='3') ? 'selected' : ''; ?>>헬로페이</option>
					<option value="3A" <?=($category=='3A') ? 'selected' : ''; ?>> - 소상공인 확정매출채권</option>
					<option value="3B" <?=($category=='3B') ? 'selected' : ''; ?>> - 면세점 확정매출채권</option>
				</select>
			</li>
			<li style="float:left;margin-left:4px;">
				<select name="invest_period" class="form-control input-sm">
					<option value=''>::대출설정기간::</option>";
					<option value='short' <? if($invest_period=='short')echo 'selected'; ?>>1개월 미만</option>";
				<?
				for($i=1; $i<=24; $i++) {
					$selected = ($i==$invest_period) ? 'selected' : '';
					echo "<option value='".$i."' $selected>".$i."개월</option>\n";
				}
				?>
					<option value='long' <? if($invest_period=='long')echo 'selected'; ?>>24개월 초과</option>";
				</select>
			</li>
			<li style="float:left;margin-left:4px;">
				<select name="invest_capa" class="form-control input-sm">
					<option value="">::투자자수::</option>
					<option value="_99"      <?=($_REQUEST['invest_capa']=='_99') ? 'selected' : ''; ?>>100 미만</option>
					<option value="100-199"  <?=($_REQUEST['invest_capa']=='100-199') ? 'selected' : ''; ?>>100 이상 200 미만</option>
					<option value="200-500"  <?=($_REQUEST['invest_capa']=='200-500') ? 'selected' : ''; ?>>200 이상 500 미만</option>
					<option value="500-1000" <?=($_REQUEST['invest_capa']=='500-1000') ? 'selected' : ''; ?>>500 이상 1000 미만</option>
					<option value="1000_"    <?=($_REQUEST['invest_capa']=='1000_') ? 'selected' : ''; ?>>1000 이상</option>
				</select>
			</li>
			<li style="float:left;margin-left:8px;">
				<select name="date_field" class="form-control input-sm">
					<option value="">::데이트 필드선택::</option>
					<option value="A.loan_start_date" <?=($date_field=='A.loan_start_date')?'selected':'';?>>대출시작일</option>
					<option value="A.loan_end_date" <?=($date_field=='A.loan_end_date')?'selected':'';?>>대출종료일</option>
				</select>
			</li>
			<li style="float:left;margin-left:4px;">
				<input type="text" id="sdate" name="sdate" value="<?=$sdate?>" readonly class="form-control input-sm datepicker" placeholder="대상일자(시작)">
			</li>
			<li style="float:left;margin-left:4px;">~</li>
			<li style="float:left;margin-left:4px;">
				<input type="text" id="edate" name="edate" value="<?=$edate?>" readonly class="form-control input-sm datepicker" placeholder="대상일자(종료)">
			</li>
		</ul>
		<ul style="list-style:none;display:inline-block; width:100%; padding:0; margin:0 0 8px 0">
			<li style="float:left;">
				<select name="prdtFld" class="form-control input-sm">
					<option value="">::상품필드선택::</option>
					<option value="idx" <?=($prdtFld=='idx') ? 'selected' : ''; ?>>품번</option>
					<option value="start_num" <?=($prdtFld=='start_num') ? 'selected' : ''; ?>>호번</option>
					<option value="title" <?=($prdtFld=='title') ? 'selected' : ''; ?>>상품명</option>
				</select>
			</li>
			<li style="float:left;margin-left:4px;"><input type="text" name="prdtKwd" value="<?=($prdtKwd) ? $prdtKwd : '';?>" placeholder="상품검색어" class="form-control input-sm"></li>
			<li style="float:left;margin-left:16px;">
				<select name="mbFld" class="form-control input-sm">
					<option value="">::회원필드선택::</option>
					<option value="i_mb_no" <?=($mbFld=='i_mb_no') ? 'selected' : ''; ?>>투자회원번호</option>
					<option value="i_mb_id" <?=($mbFld=='i_mb_id') ? 'selected' : ''; ?>>투자회원아이디</option>
					<option value="l_mb_name" <?=($mbFld=='l_mb_name') ? 'selected' : ''; ?>>대출자명(개인)</option>
					<option value="l_mb_co_name" <?=($mbFld=='l_mb_co_name') ? 'selected' : ''; ?>>대출자명(법인)</option>
				</select>
			</li>
			<li style="float:left;margin-left:4px;"><input type="text" name="mbKwd" value="<?=($mbKwd) ? $mbKwd : '';?>" placeholder="회원검색어" class="form-control input-sm"></li>
			<li style="float:left;margin-left:4px;"><button type="submit" class="btn btn-primary btn-sm">검색</button></li>
			<li style="float:left;margin-left:8px;">
				<select name="rows" class="form-control input-sm">
					<option value="10" <?=($_REQUEST['rows']=="" || $_REQUEST['rows']=='10') ? 'selected' : ''; ?>>10개씩</option>
					<option value="20" <?=($_REQUEST['rows']=='20') ? 'selected' : ''; ?>>20개씩</option>
					<option value="50" <?=($_REQUEST['rows']=='50') ? 'selected' : ''; ?>>50개씩</option>
					<option value="100" <?=($_REQUEST['rows']=='100') ? 'selected' : ''; ?>>100개씩</option>
				</select>
			</li>
			<li style="float:right"><button type="button" onClick="startLoadStatusData();" class="btn btn-sm btn-warning">정산내역 가져오기</button></li>
		</ul>
		<!-- 확인서 관련 버튼 추가 -->
		<ul style="list-style:none;display:inline-block; width:100%; padding:0; margin:0 0 8px 0">
			<li style="float:left;"><button class="btn_form_kinds btn btn-sm btn-success" value="1">완납확인서 작성</button></li>
			<li style="float:left;margin-left:10px;"><button class="btn_form_kinds btn btn-sm btn-success" value="2">금융거래확인서 작성</button></li>
			<li style="float:left;margin-left:10px;"><button class="btn_form_kinds btn btn-sm btn-success" value="3">이자납입내역서 작성</button></li>
			<li style="float:left;margin-left:10px;"><button class="btn_form_kinds btn btn-sm btn-success" value="4">이자내역서 작성</button></li>
		</ul>
	</form>

<?
//==================== ▼ 삭제금지 ▼ ====================//
for($i=0,$j=1; $i<$rcount; $i++,$j++) {
	if($LIST[$i]['ib_product_regist']=='Y' && $LIST[$i]['repay_acct_no']) {
		echo "<input type='hidden' id='get_loaner_balance{$j}' name='get_loaner_balance' data-idx='{$LIST[$i]['idx']}' data-vacct='{$LIST[$i]['repay_acct_no']}', data-order_value='sum' data-field_id='loaner_balance{$j}' data-title='".stripSlashes($LIST[$i]['title'])."'>\n";
	}
}
//==================== ▲ 삭제금지 ▲ ====================//
?>

	<table id="productListTable" class="tblx table-striped table-bordered">
		<form id="kindDataFrm" name="kindDataFrm" method="post" class="form-horizontal">
			<colgroup>
				<col style="width:5%">
				<col style="width:%">

				<col style="width:6.25%">
				<col style="width:5%">
				<col style="width:6.25%">
				<col style="width:6.25%">

				<col style="width:6.25%">

				<col style="width:6.25%">
				<col style="width:6.25%">
				<col style="width:6.25%">
				<col style="width:6.25%">
				<col style="width:6.25%">
				<col style="width:6.25%">
				<col style="width:6.25%">

				<col style="width:6.25%">
			</colgroup>
			<thead style="font-size:13px">
				<tr align="center" style="background:#F8F8EF">
					<td rowspan="2">NO</td>
					<td rowspan="2">상품명</td>
					<td colspan="4">투자현황</td>
					<td rowspan="2">상환계좌잔액</td>
					<td colspan="7">정산현황</td>
					<td rowspan="2">관리</td>
				</tr>
				<tr align="center" style="background:#F8F8EF">
					<td>진행현황</td>
					<td>투자자수</td>
					<td>참여금액</td>
					<td>모집율</td>
					<td>구분</td>
					<td>전체정산</td>
					<td>지급누적</td>
					<td>지급예정<br>(연체포함)</td>
					<td>추가납입<br>필요금액</td>
					<td>이자상환율</td>
					<td>원금상환율</td>
				</tr>
			</thead>
			<tbody>
<?
if($rcount) {
	for($i=0,$j=1; $i<$rcount; $i++,$j++) {

		$state_str = "";
		$bgcolor = "";
		$state_fcolor = "";

		if($LIST[$i]['state']) {
			if($LIST[$i]['state']=='1')      { $state_str = '이자상환중'; $state_code = '2'; }
			else if($LIST[$i]['state']=='2') { $state_str = '상품마감<br>(정상상환)'; }
			else if($LIST[$i]['state']=='3') { $state_str = '투자금<br>모집실패'; $bgcolor = "#FFF0F5"; }
			else if($LIST[$i]['state']=='4') { $state_str = '부실'; $bgcolor = "#FFF0F5"; }
			else if($LIST[$i]['state']=='5') { $state_str = '상품마감<br><span style="color:blue">(중도상환)</span>'; $state_code = '2'; }
			else if($LIST[$i]['state']=='6') { $state_str = '대출계약취소<br>(기표전)'; $state_code = '8'; $bgcolor = "#FFF0F5"; }
			else if($LIST[$i]['state']=='7') { $state_str = '대출계약취소<br>(기표후)'; $state_code = '8'; $bgcolor = "#FFF0F5"; }
			else if($LIST[$i]['state']=='8') { $state_str = '<font color="#FF2222">연체</font>'; $state_code = '8'; $bgcolor = "#FFF0D5"; }
		}
		else {
			if($LIST[$i]['open_datetime'] > $date) {
				$state_str = '상품준비중';
			}
			else {
				if($LIST[$i]['invest_end_date']=='') {
					if($LIST[$i]['end_datetime'] < $date) { $state_str = '투자금<br>모집실패'; $bgcolor = "#FFF0F5"; }
					else { $state_str = '대기중'; $state_code = '1'; }
				}
				if($LIST[$i]['start_datetime'] < $date && $LIST[$i]['end_datetime'] > $date) {
					if($LIST[$i]['recruit_amount']==$LIST[$i]['invest_amount']) { $state_str = '투자금<br>모집완료'; $bgcolor = "#FFEFD5"; }
					else {
						if($LIST[$i]['display']=='Y') {
							$state_str = '<span class="blinkEle" style="color:#FF2222">투자금<br>모집중</span>'; $bgcolor = "#FFFACD";
						}
						else {
							$state_str = '비노출상품'; $bgcolor = "#EFEFEF";
						}
					}
				}
			}
		}


		$invest_return = '(연) ' . floatRtrim($LIST[$i]['invest_return']) . '%';
		$invest_usefee = ($LIST[$i]['invest_usefee']>'0') ? '(월) '.floatRtrim($LIST[$i]['invest_usefee']/12).'%' : '면제';


		$print_recruit_amount = ($LIST[$i]['loan_started']) ? number_format($LIST[$i]['recruit_amount']).'원' : '';

		$loan_date_range = ($LIST[$i]['loan_started']) ? preg_replace("/-/", ".", $LIST[$i]['loan_start_date'])." ~ ".preg_replace("/-/", ".", $LIST[$i]['loan_end_date']) : '';
		$recrute_perc    = floatRtrim(@sprintf("%.2f",($LIST[$i]['invest_amount'] / $LIST[$i]['recruit_amount']) * 100)) . "%";

?>

				<tr class="odd" style="font-size:9pt;background-color:<?=$bgcolor?>">
					<td align="center"><?=$num?>
						<br />
						<!-- 확인서 관련 submit할 항목 추가 -->
						<input type="checkbox" name="prdListChk[]" id="prdListChk" value="<?=$LIST[$i]['idx']?>" />
						<input type="hidden" name="mb_no" value="<?=$LIST[$i]['mb_no']?>"/>
						<input type="hidden" name="mb_f_no" value="<?=$LIST[$i]['loan_mb_f_no']?>"/>
						<input type="hidden" name="category" value="<?=$LIST[$i]['category']?>" />
						<input type="hidden" name="mortgage_guarantees" value="<?=$LIST[$i]['mortgage_guarantees']?>" />
						<input type="hidden" name="state" value="<?=$LIST[$i]['state']?>" />
					</td>
					<td align="left">
						<dl style="margin:0">
	<!--
							<dd>
								<? if($LIST[$i]['ib_trust']=='Y') { ?><span style="margnin-left:2px;padding:2px 6px;font-size:11px;border-radius:10px;color:#fff;background-color:blue">예치금신탁</span><? } ?>
								<? if($LIST[$i]['advance_invest']=='Y') { ?><span style="margin-left:2px;padding:2px 6px;font-size:11px;border-radius:10px;color:#fff;background-color:green">사전투자</span><? } ?>
								<? if($LIST[$i]['advanced_payment']=='Y') { ?><span style="margin-left:2px;padding:2px 6px;font-size:11px;border-radius:10px;color:#fff;background-color:#ff6633">이자선지급</span><? } ?>
								<? if($LIST[$i]['purchase_guarantees']=='Y') { ?><span style="margin-left:2px;padding:2px 6px;font-size:11px;border-radius:10px;color:#fff;background-color:red">채권매입보증</span><? } ?>
							</dd>
	//-->
							<dd style='margin:4px 0 4px 0'><strong style="color:#3333cc;font-size:14px"><?=$LIST[$i]['title']?></strong></dd>
							<dd style='margin:0 8px'>품번. <?=$LIST[$i]['idx']?></span></dd>
							<dd style='margin:0 8px'>모집금액. <?=number_format($LIST[$i]['recruit_amount'])?>원</dd>
							<dd style='margin:0 8px'>대출이자. <?=$invest_return?></dd>
							<dd style='margin:0 8px'>플랫폼이용료. <?=$invest_usefee?></dd>
							<dd style='margin:0 8px'>대출기간. <?=$loan_date_range?> :: &nbsp; <?=($LIST[$i]['invest_days'])?$LIST[$i]['invest_days'].'일' : $LIST[$i]['invest_period'].'개월';?> <?if($loan_date_range && $LIST[$i]['invest_days']==0){?>(<?=$LIST[$i]['total_days']?>일)<?}?></dd>
							<dd style='margin:0 8px'>최대투자자. <a href="/adm/repayment/repay_calculate.php?&idx=<?=$LIST[$i]['idx']?>&mb_id=<?=$LIST[$i]['investor_id']?>"><?=$LIST[$i]['investor_id']?></a></dd>
						</dl>
					</td>
					<td align="center" style="color:<?=$state_fcolor?>"><?=$state_str?></td>
					<td align="right" style="color:<?=$state_fcolor?>">
						<?=number_format($LIST[$i]['invest_count'])?>명
						<? if($LIST[$i]['indi_invest_count']) { echo "<br>\n개인: " . number_format($LIST[$i]['indi_invest_count']) . "명"; } ?>
						<? if($LIST[$i]['corp_invest_count']) { echo "<br>\n법인: " . number_format($LIST[$i]['corp_invest_count']) . "명"; } ?>
					</td>
					<td align="right" style="color:<?=$state_fcolor?>"><?=number_format($LIST[$i]['invest_amount'])?>원</td>
					<td align="right" style="color:<?=$state_fcolor?>"><?=$recrute_perc?></td>
					<td align="right" id="loaner_balance<?=$j?>"></td> <!-- 대출자상환입금 잔액 -->
					<td align="center" colspan="7" style="padding:0 0">
						<div id="loading<?=$j?>" style="position:absolute; z-index:1000; width:42%; height:186px; display:none;">
							<div align="center" style="margin:60px auto;">
								<img src="/images/loading/ani_load.gif" width="24"><br/>
								<span style="display:inline-block;background:#888;color:#FFF;margin-top:8px; padding:0 10px; border-radius:12px;">loading</span>
							</div>
						</div>

						<table style="font-size:12px">
							<colgroup>
								<col style='width:<?=(100/7)-0.05?>%'>
								<col style='width:<?=(100/7)?>%'>
								<col style='width:<?=(100/7)?>%'>
								<col style='width:<?=(100/7)?>%'>
								<col style='width:<?=(100/7)?>%'>
								<col style='width:<?=(100/7)?>%'>
								<col style='width:<?=(100/7)?>%'>
							</colgroup>
							<tr align='right'>
								<td class="border-t0 border-l0" align='center'>회차수</td>
								<td id="turn<?=$j?>" class="border-t0"></td>
								<td id="nujukPaidTurn<?=$j?>" class="border-t0 fGray"></td>
								<td id="nextTurn<?=$j?>" class="border-t0 border-r0 fBlue"></td>
								<td id="repayMoneyDiff<?=$j?>" rowspan="6" class="border-t0 border-r0 border-b0" align="center"></td>
								<td id="interestPaidPerc<?=$j?>" rowspan="6" class="border-t0 border-r0 border-b0" align="center"></td>
								<td id="principalPaidPerc<?=$j?>" rowspan="6" class="border-t0 border-r0 border-b0" align="center"></td>
							</tr>
							<tr align='right'>
								<td class="border-l0" align='center'>원금</td>
								<td id="principal<?=$j?>" class=""></td>
								<td id="nujukPaidPrincipal<?=$j?>" class="fGray"></td>
								<td id="nextPrincipal<?=$j?>" class="border-r0 fBlue"></td>
							</tr>
							<tr align='right'>
								<td class="border-l0" align='center'>이자</td>
								<td id="interest<?=$j?>" class=""></td>
								<td id="nujukPaidInterest<?=$j?>" class="fGray"></td>
								<td id="nextInterest<?=$j?>" class="border-r0 fBlue"></td>
							</tr>
							<tr align='right'>
								<td class="border-l0" align='center'>원천징수</td>
								<td id="tax<?=$j?>" class=""></td>
								<td id="nujukPaidTax<?=$j?>" class="fGray"></td>
								<td id="nextTax<?=$j?>" class="border-r0 fBlue"></td>
							</tr>
							<tr align='right'>
								<td class="border-l0" align='center'>수수료</td>
								<td id="fee<?=$j?>" class=""></td>
								<td id="nujukPaidFee<?=$j?>" class="fGray"></td>
								<td id="nextFee<?=$j?>" class="border-r0 fBlue"></td>
							</tr>
							<tr align='right'>
								<td class="border-b0 border-l0" align='center'>실지급액</td>
								<td id="repayAmount<?=$j?>" class="border-b0"></td>
								<td id="nujukPaidAmount<?=$j?>" class="border-b0 fGray"></td>
								<td id="nextRepayAmount<?=$j?>" class="border-b0 border-r0 fBlue"></td>
							</tr>
						</table>

					</td>
					<td align="center">
						<a href="/adm/repayment/repay_calculate.php?<?=$_SERVER['QUERY_STRING']?>&idx=<?=$LIST[$i]['idx']?>" class="btn btn-sm btn-primary" style="width:100px;margin-bottom:4px;">정산</a><br/>
						<!--<a href="/adm/repayment/repay_calculate_test.php?<?=$_SERVER['QUERY_STRING']?>&idx=<?=$LIST[$i]['idx']?>" target="_blank" class="btn btn-sm btn-success" style="width:100px;margin-bottom:4px;">(신)정산</a><br/>-->
						<a href="/adm/product_calculate.php?<?=$_SERVER['QUERY_STRING']?>&idx=<?=$LIST[$i]['idx']?>" target="_blank" class="btn btn-sm btn-success" style="width:100px;margin-bottom:4px;">(구)정산</a><br/>
						<a href="/adm/product_investment_status.php?idx=<?=$LIST[$i]['idx']?>" target="_blank" class="btn btn-sm btn-default" style="width:100px;margin-bottom:4px;">투자자 통계</a><br/>
						<a href="/adm/product/product_form.php?idx=<?=$LIST[$i]['idx']?>" target="_blank" class="btn  btn-sm btn-default" style="width:100px;">상품정보</a>
					</td>
				</tr>
<?
		$num--;
	}
}
else {
	echo "			<tr><td align='center' colspan='20'>데이터가 없습니다.</td></tr>\n";
}
?>
			</tbody>
		</form>
	</table>

	<div id="paging_span" style="width:100%; margin:10px 0 10px 0; text-align:center;"><? paging($total_count, $page, $rows, 10); ?></div>

</div>

<style>
#loanerMoneyLog { display:none; position:fixed; z-index:1000000; width:100%;height:100%; left:0; top:0; min-width:1000px; min-height:500px; }
</style>
<div id="loanerMoneyLog">
	<div style="width:1200px; margin:6% auto; padding:0; border:1px solid #000; background:#fff;">
		<div style="width:100%; margin:0; padding:6px 0 7px 8px; background:#cc3300;text-align:left">
			<span id="popuptitle" style="font-size:12px;color:#fff;"></span>
			<span onClick="popupClose();" style="float:right;margin-right:8px;cursor:pointer;color:#FFF">X</span>
		</div>
		<div id="loanerMoneyLogDetail" style="width:100%; margin:0; padding:10px 10px 20px; max-height:590px; overflow-y:scroll;"></div>
	</div>
</div>

<?
$qstr = preg_replace("/&page=([0-9]){1,10}/", "", $_SERVER['QUERY_STRING']);
?>

<script type="text/javascript">

// 확인서 작성 버튼 LIST
$('.btn_form_kinds').click(function(e) {
	e.preventDefault();

	var kind_val = $(this).val();
	var chk_kinds = $('input[id=prdListChk]:checked');
	var add_chk = $('input[id=prdListChk]:checked').attr('checked','checked');
	var chk_fno = $('input[id=prdListChk]:checked').next('input[name=mb_f_no]').val();

	var idx_arr = new Array();
	var idxfnoArr = new Array();


	if(chk_kinds.length > 0) {
		$("input[id=prdListChk]:checked").each(function() {
			idx_arr.push($("input[id=prdListChk]").index(this));
		});
		for (var i=0; i<idx_arr.length; i++) {
			var obj = new Object();
			var len = idx_arr.length - 1;

			// 동일차주번호, 카테고리, 투자상태
			obj.mb_no		 = $("#productListTable tbody").children().eq(idx_arr[i]).children().find('input[name=mb_no]').val();
			obj.fno			 = $("#productListTable tbody").children().eq(idx_arr[i]).children().find('input[name=mb_f_no]').val();
			obj.category = $("#productListTable tbody").children().eq(idx_arr[i]).children().find('input[name=category]').val();
			obj.mortgage = $("#productListTable tbody").children().eq(idx_arr[i]).children().find('input[name=mortgage_guarantees]').val();
			obj.state		 = $("#productListTable tbody").children().eq(idx_arr[i]).children().find('input[name=state]').val();

			idxfnoArr.push(obj);

			var category_val = idxfnoArr[i]['category'];
			var state_val		 = idxfnoArr[i]['state'];
			var mortgage_val = idxfnoArr[i]['mortgage'];

			// 동일 차주 체크
			if(idxfnoArr[0]['fno']!=idxfnoArr[i]['fno']) {
				alert('동일 차주의 상품이 아니므로 출력할 수 없습니다.');  // 우선 chk
				return;
			}

			// 카테고리, 투자상태 체크
			if(category_val!='2' || state_val!='1' && state_val!='2' && state_val!='5' && state_val!='8') {
				alert('확인서 작성이 불가능한 상품이 포함되어 있습니다.');
				return false;
			} else if(kind_val=='4' && category_val=='2' && mortgage_val!='') {
				alert('PF상품만 작성 가능합니다.');
				return false;
			}

		}  // for문 end

		console.log(idxfnoArr);

		// form submit
		var frm = document.kindDataFrm;

		if(kind_val == '1') {  // 완납확인서
			frm.action = "../etc2/confirm_page/type1.php?kinds="+kind_val+"&fno="+idxfnoArr[0]['fno']+"&mbno="+idxfnoArr[0]['mb_no'];
			frm.method = 'POST';
			frm.target = "_self";
			frm.submit();
		} else if(kind_val == '2') {  // 금융거래확인서
			frm.action = "../etc2/confirm_page/type2.php?kinds="+kind_val+"&fno="+idxfnoArr[0]['fno']+"&mbno="+idxfnoArr[0]['mb_no'];
			frm.target = "_self";
			frm.submit();
		} else if(kind_val == '4') {  // 이자내역서
			frm.action = "../etc2/confirm_page/type4.php?kinds="+kind_val+"&fno="+idxfnoArr[0]['fno'];
			frm.target = "_self";
			frm.submit();
		} else if(kind_val == '3' && chk_kinds.length == 1) {  // 이자납입내역서
			frm.action = "../etc2/confirm_page/type3.php?kinds="+kind_val+"&mbno="+idxfnoArr[0]['mb_no'];
			frm.target = "_self";
			frm.submit();
		} else {
			alert('이자납입내역서는 복수 선택할 수 없습니다.');
		}


	} else {
		alert('최소 1개 이상 상품을 선택해주세요.');
	}
});

</script>

<script type="text/javascript">
$(document).on('click', '#paging_span span.btn_paging', function() {
		var url = '<?=$_SERVER['PHP_SELF']?>?<?=$qstr?>&page=' + $(this).attr('data-page');
		$(location).attr('href', url);
});

// 팝업 닫기
popupClose = function() {
	$.unblockUI();
	return false;
}
</script>

<script type="text/javascript">
$(document).ready(function() {
	$('#productListTable').floatThead();
});

loadStatusData = function(prd_idx, no) {
	$.ajax({
		url : "invest_status_data.ajax.php",
		type: "post",
		dataType: "json",
		data: {prd_idx: prd_idx},
		success:function(data) {
			if(data.result=='SUCCESS') {

				if(data.total_turn > 0) {
					$('#turn' + no).html(data.total_turn + '회차');
					$('#principal' + no).html(number_format(data.principal) + '원');
					$('#interest' + no).html(number_format(data.interest) + '원');
					$('#tax' + no).html(number_format(data.tax) + '원');
					$('#fee' + no).html(number_format(data.fee) + '원');
					$('#repayAmount' + no).html(number_format(data.repay_amount) + '원');
				}
				if(data.last_paid_turn > 0) {
					$('#nujukPaidTurn' + no).html(data.last_paid_turn + '회차');
					$('#nujukPaidPrincipal' + no).html(number_format(data.nujuk_paid_principal) + '원');
					$('#nujukPaidInterest' + no).html(number_format(data.nujuk_paid_interest) + '원');
					$('#nujukPaidTax' + no).html(number_format(data.nujuk_paid_tax) + '원');
					$('#nujukPaidFee' + no).html(number_format(data.nujuk_paid_fee) + '원');
					$('#nujukPaidAmount' + no).html(number_format(data.nujuk_paid_amount) + '원');
				}
				if(data.next_turn > 0) {
					$('#nextTurn' + no).html(data.next_turn + '회차');
					$('#nextPrincipal' + no).html(number_format(data.next_principal) + '원');
					$('#nextInterest' + no).html(number_format(data.next_interest) + '원');
					$('#nextTax' + no).html(number_format(data.next_tax) + '원');
					$('#nextFee' + no).html(number_format(data.next_fee) + '원');
					$('#nextRepayAmount' + no).html(number_format(data.next_repay_amount) + '원');


					repayMoneyDiff = Number( Number($('#get_loaner_balance' + no).val()) - ( Number(data.next_principal) + Number(data.next_interest) ) );

					if(repayMoneyDiff < 0) {
						$('#repayMoneyDiff' + no).html('<div style="color:#CC3300">' + number_format(repayMoneyDiff) + '원</div>');
					}
					else {
						str = '<div style="color:#AAA">0원</div>';
						if(repayMoneyDiff > 0) str+= '<div style="margin-top:10px;">잔액 ' + number_format(repayMoneyDiff) + '원</div>';
						$('#repayMoneyDiff' + no).html(str);
					}

				}

				if(data.interest_paid_perc) $('#interestPaidPerc' + no).html(data.interest_paid_perc);
				if(data.principal_paid_perc) $('#principalPaidPerc' + no).html(data.principal_paid_perc);
			}
		},
		beforeSend:function() { $('#loading' + no).css('display','block'); },
		complete:function() { $('#loading' + no).css('display','none'); },
		error:function(e) { console.log(e); }
	});
}

startLoadStatusData = function() {
<?
	for($i=0,$j=1; $i<$rcount; $i++,$j++) {
		if($LIST[$i]['loan_started']) { echo "	setTimeout(function() { loadStatusData('".$LIST[$i]['idx']."', $j); }, ".($i*300).");\n"; }
	}
?>
}

$(document).ready(function() {
	var loop_count = $('input[name=get_loaner_balance]').length;
	for(var i=0; i<loop_count; i++) {

		(function(i) {
			idx_val   = $('input[name=get_loaner_balance]').eq(i).data('idx');
			vacct_val = $('input[name=get_loaner_balance]').eq(i).data('vacct');
			order_val = $('input[name=get_loaner_balance]').eq(i).data('order_value');
			title     = $('input[name=get_loaner_balance]').eq(i).data('title');

			$.ajax({
				url : "ajax_loaner_money_log.php?" + idx_val,
				type: "POST",
				dataType: "JSON",
				async: false, // <-- 비동기 해제 금지
				data: {
					idx : idx_val,
					vacct : vacct_val,
					order_value : order_val
				},
				success:function(data) {
					var balance = (data.balance) ? data.balance : '0';
					$('input[name=get_loaner_balance]').eq(i).val(balance);

					var print_str = number_format(balance) + '원';
					if(data.list_count > 0) {
						print_str += "<button type='button' class='btn btn-xs btn-default' onClick=\"loadLoanerMoneyLog('"+idx_val+"', '"+vacct_val+"', '', '"+title+"');\" style='width:100%;margin-top:10px;'>상세보기</button>";
					}

					$('#' + $('input[name=get_loaner_balance]').eq(i).data('field_id')).html(print_str);
				},
				 error: function(xhr,status,error){
					console.log(xhr+status+error);
				}
			});
		})(i);

	}
});

loadLoanerMoneyLog = function(prd_idx, vacct, print_form, title) {
	$.blockUI({
		message: $('#loanerMoneyLog'),css:{ 'border':'0', 'position':'fixed' },
	});
	$.ajax({
		url : "./ajax_loaner_money_log.php",
		type: "POST",
		data:{
			idx:prd_idx,
			vacct:vacct,
			print_form:print_form
		},
		success:function(data) {
			$('#popuptitle').html(':: 상환금 입출금 내역 - ' + title);
			$('#loanerMoneyLogDetail').html(data);
		},
		error: function () {
			alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.");
		}
	});
}
</script>

<?
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>