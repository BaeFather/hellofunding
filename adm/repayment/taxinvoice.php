<?
###############################################################################
##  세금계산서,현금영수증 발행 일별 내역통계
###############################################################################

include_once('./_common.php');

$sub_menu = "700600";
$g5['title'] = $menu['menu700'][7][1];

include_once (G5_ADMIN_PATH.'/admin.head.php');

auth_check($auth[$sub_menu], 'w');
if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

while( list($k, $v)=each($_REQUEST) ) { if(!is_array($$k)) $$k = trim($v); }
//foreach($_GET as $k=>$v) { ${$_GET[$k]} = trim($v); }


$syear = ($syear) ? $syear : date('Y');
$sdate = $syear;

if($smonth!='all') {
	$smonth = ($smonth) ? $smonth : date('m');
	$sdate.= "-" . $smonth;
}


$date_length = strlen($sdate);

$sdatetime_s = $sdate . '-01 00:00:00';
$sdatetime_e = $sdate . '-' . date('t', strtotime($sdatetime_s)) . ' 23:59:59';


$sql = "
	SELECT
		DISTINCT(LEFT(A.banking_date, 10)) AS `date`
	FROM
		cf_product_give A
	WHERE (1)
		AND A.banking_date BETWEEN '".$sdatetime_s."' AND '".$sdatetime_e."'
		AND A.turn_sno = '0'
	ORDER BY
		`date` DESC";
//print_rr($sql);
$res = sql_query($sql);
while( $R = sql_fetch_array($res) ) { $LIST[] = $R; }

$TOTAL['product_count'] = 0;
$TOTAL['repay_count']   = 0;
$TOTAL['repay_count_c'] = 0;
$TOTAL['repay_count_p'] = 0;
$TOTAL['target']        = 0;
$TOTAL['target_c']      = 0;
$TOTAL['target_p']      = 0;
$TOTAL['recorded']      = 0;
$TOTAL['recorded_c']    = 0;
$TOTAL['recorded_p']    = 0;

$list_count = count($LIST);

for($i=0; $i<$list_count; $i++) {

	$RANGE['sdate'] = $LIST[$i]['date'] . " 00:00:00";
	$RANGE['edate'] = $LIST[$i]['date'] . " 23:59:59";

	// 해당일자의 정상처리 상품수
	$sqlx = "
		SELECT
			COUNT(DISTINCT(product_idx)) AS product_count
		FROM
			cf_product_give
		WHERE (1)
			AND banking_date BETWEEN '".$RANGE['sdate']."' AND '".$RANGE['edate']."'
			AND turn_sno = '0'
			-- AND fee > 0";
	//echo $sqlx."<br>\n";
	$LIST[$i]['product_count'] = sql_fetch($sqlx)['product_count'];

	// 해당일자의 정산내역 루프
	$sqlx2 = "
		SELECT
			A.idx, A.fee, B.remit_fee, A.mgtKey, A.is_creditor,
			B.member_type, B.is_owner_operator, B.mb_co_reg_num
		FROM
			cf_product_give A
		LEFT JOIN
			g5_member B  ON A.member_idx=B.mb_no
		WHERE (1)
			AND A.banking_date BETWEEN '".$RANGE['sdate']."' AND '".$RANGE['edate']."'
			AND A.turn_sno = '0'
			-- AND A.fee > 0 AND B.remit_fee = ''
		ORDER BY
			A.idx";
	if($_SERVER['REMOTE_ADDR']=='220.117.134.164') {
		//print_rr($sqlx2,'font-size:12px;line-height:14px;');
	}

	$resx2 = sql_query($sqlx2);

	$LIST[$i]['repay_count'] = 0;
	$LIST[$i]['repay_count_c'] = 0;
	$LIST[$i]['repay_count_p'] = 0;
	$LIST[$i]['target'] = 0;
	$LIST[$i]['target_c'] = 0;
	$LIST[$i]['target_p'] = 0;
	$LIST[$i]['recorded'] = 0;
	$LIST[$i]['recorded_c'] = 0;
	$LIST[$i]['recorded_p'] = 0;

	while( $R = sql_fetch_array($resx2) ) {

		// 정산내역
		$LIST[$i]['repay_count'] += 1;
		if( $R['member_type']=='2' ) {
			$LIST[$i]['repay_count_c'] += 1;
		}
		else {
			$LIST[$i]['repay_count_p'] += 1;
		}

		$isTarget = ($R['fee'] > 0 && $R['remit_fee']=='') ? true : false;		//발행 대상 구분

		if($isTarget) {

			// 발행대상 카운팅
			$LIST[$i]['target'] += 1;
			if($R['member_type']=='2') {
				$LIST[$i]['target_c'] += 1;
			}
			else {
				if($R['is_owner_operator']=='1' && $R['mb_co_reg_num']!='') {
					$LIST[$i]['target_c'] += 1;
				}
				else {
					$LIST[$i]['target_p'] += 1;
				}
			}

			// 발행완료 카운팅
			if($R['mgtKey']) {
				$LIST[$i]['recorded'] += 1;
				//if(substr($R['mgtKey'], 0, 1)=='C')      $LIST[$i]['recorded_c'] += 1;
				//else if(substr($R['mgtKey'], 0, 1)=='P') $LIST[$i]['recorded_p'] += 1;
				if($R['member_type']=='2') {
					$LIST[$i]['recorded_c'] += 1;
				}
				else {
					if($R['is_owner_operator']=='1' && $R['mb_co_reg_num']!='') {
						$LIST[$i]['recorded_c'] += 1;
					}
					else {
						$LIST[$i]['recorded_p'] += 1;
					}
				}
			}

		}
	}

	$TOTAL['product_count'] += $LIST[$i]['product_count'];

	$TOTAL['repay_count']   += $LIST[$i]['repay_count'];
	$TOTAL['repay_count_c'] += $LIST[$i]['repay_count_c'];
	$TOTAL['repay_count_p'] += $LIST[$i]['repay_count_p'];

	$TOTAL['target']        += $LIST[$i]['target'];
	$TOTAL['target_c']      += $LIST[$i]['target_c'];
	$TOTAL['target_p']      += $LIST[$i]['target_p'];

	$TOTAL['recorded']      += $LIST[$i]['recorded'];
	$TOTAL['recorded_c']    += $LIST[$i]['recorded_c'];
	$TOTAL['recorded_p']    += $LIST[$i]['recorded_p'];

}

?>

<style>
#dataList th,td { padding:2px 8px; }
#dataList th { background:#F8F8EF; }
#dataListth.border_r { border-right:1px solid #999; }
#dataListtd.border_r { border-right:1px solid #999; }

button.btn_active {line-height:20px;padding:1px 10px;}
button.btn_disabled {line-height:20px;padding:1px 10px;color:#AAA;}
</style>

<div class="tbl_head02 tbl_wrap">

	<!-- 검색영역 START -->
	<div style="line-height:28px;">
		<form name="frmSearch" method="get" class="form-horizontal">
		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>
				<select id="syear" name="syear" class="form-control input-sm" onChange="fSubmit();">
<?
for($i=2016; $i<=date(Y); $i++) {
	$selected = ($i==$syear) ? 'selected' : '';
	echo "<option value='".$i."' $selected>".$i."년</option>\n";
}
?>
				</select>
			</li>
			<li>
				<select id="smonth" name="smonth" class="form-control input-sm" onChange="fSubmit();">
					<option value='all' <?=($smonth=='all')?'selected':'';?>>전체</option>
<?
for($i=1; $i<=12; $i++) {
	$i = sprintf("%02d", $i);
	$selected = ($i==$smonth) ? 'selected' : '';
	echo "<option value='".$i."' $selected>".$i."월</option>\n";
}
?>
				</select>
			</li>
			<li><button type="submit" class="btn btn-sm btn-warning">검색</button></li>
<? if( $syear==date('Y') && $smonth==date('m') ) { ?>
			<li><button type='button' id='button_a' class='btn btn-sm btn-danger' onClick="requestBill('all','<?=G5_TIME_YMD?>','button_a');">금일정산내역 세금계산서.현금영수증 일괄발행</button></li>
<? } ?>
		</ul>
		</form>
	</div>
	<!-- 검색영역 E N D -->

	<table id="dataList" class="table table-striped table-bordered table-hover" style="font-size:13px">
		<colgroup>
			<col style="">
			<col style="width:7.6%">
			<col style="width:7.6%">
			<col style="width:7.6%">
			<col style="width:7.6%">
			<col style="width:7.6%">
			<col style="width:7.6%">
			<col style="width:7.6%">
			<col style="width:7.6%">
			<col style="width:7.6%">
			<col style="width:7.6%">
			<col style="width:7.6%">
			<col style="width:7.6%">
		</colgroup>
		<thead>
			<tr>
				<th rowspan="2" class="border_r">정산처리일</th>
				<th rowspan="2" class="border_r">상품수</th>
				<th colspan="3" class="border_r">정산내역</th>
				<th colspan="3" class="border_r">발행대상</th>
				<th colspan="3" class="border_r">발행완료</th>
				<th colspan="2">개별발행</th>
			</tr>
			<tr>
				<th>법인회원</th>
				<th>개인회원</th>
				<th class="border_r">합계</th>
				<th>세금계산서</th>
				<th>현금영수증</th>
				<th class="border_r">합계</th>
				<th>세금계산서</th>
				<th>현금영수증</th>
				<th class="border_r">합계</th>
				<th>세금계산서</th>
				<th>현금영수증</th>
			</tr>
		</thead>
		<tbody>
			<tr align="center" style="background:#EEEEFF;color:brown;">
				<td class="border_r">합계</td>
				<td style="text-align:right;" class="border_r"><?=number_format($TOTAL['product_count'])?></td>
				<td style="text-align:right;"><?=number_format($TOTAL['repay_count_c'])?></td>
				<td style="text-align:right;"><?=number_format($TOTAL['repay_count_p'])?></td>
				<td style="text-align:right;" class="border_r"><?=number_format($TOTAL['repay_count'])?></td>
				<td style="text-align:right;"><?=number_format($TOTAL['target_c'])?></td>
				<td style="text-align:right;"><?=number_format($TOTAL['target_p'])?></td>
				<td style="text-align:right;" class="border_r"><?=number_format($TOTAL['target'])?></td>
				<td style="text-align:right;"><?=number_format($TOTAL['recorded_c'])?></td>
				<td style="text-align:right;"><?=number_format($TOTAL['recorded_p'])?></td>
				<td style="text-align:right;" class="border_r"><?=number_format($TOTAL['recorded'])?></td>
				<td></td>
				<td></td>
			</tr>
<?
if($list_count) {

	for($i=0,$j=1; $i<$list_count; $i++,$j++) {

		$DATE = explode("-", $LIST[$i]['date']);
		$product_link       = "/adm/etc/profit_give_detail.php?syear=" . $DATE[0] . "&smonth=" . $DATE[1] . "&sday=" . $DATE[2];
		$give_detail_link   = "/adm/repayment/repay_log.php?date_field=A.banking_date&sdate=".$LIST[$i]['date']."&edate=".$LIST[$i]['date'];
		$give_detail_link_c = "/adm/repayment/repay_log.php?date_field=A.banking_date&sdate=".$LIST[$i]['date']."&edate=".$LIST[$i]['date']."&member_type=2";
		$give_detail_link_p = "/adm/repayment/repay_log.php?date_field=A.banking_date&sdate=".$LIST[$i]['date']."&edate=".$LIST[$i]['date']."&member_type=1";

		$taxinvoice_req_btn = $cashbill_req_btn = "";

		if($LIST[$i]['target_c'] > $LIST[$i]['recorded_c']) {
			$taxinvoice_req_btn = "<button type='button' id='button_c_{$j}' class='btn btn-sm btn-primary btn_active' onClick=\"requestBill('c','".$LIST[$i]['date']."','button_c_{$j}');\">세금계산서</button>";
		}
		else {
			$taxinvoice_req_btn = "<button type='button' class='btn btn-sm btn-gray btn_disabled'>발행</button>";
		}

		if($LIST[$i]['target_p'] > $LIST[$i]['recorded_p']) {
			$cashbill_req_btn = "<button type='button' id='button_p_{$j}' class='btn btn-sm btn-primary btn_active' onClick=\"requestBill('p','".$LIST[$i]['date']."', 'button_p_{$j}');\">현금영수증</button>";
		}
		else {
			$cashbill_req_btn = "<button type='button' class='btn btn-sm btn-gray btn_disabled'>발행</button>";
		}

?>
			<tr align="center">
				<td class="border_r"><?=$LIST[$i]['date']?></td>
				<td style="text-align:right;" class="border_r"><a href="<?=$product_link?>"><?=number_format($LIST[$i]['product_count'])?></a></td>
				<td style="text-align:right;"><a href="<?=$give_detail_link_c?>"><?=number_format($LIST[$i]['repay_count_c'])?></a></td>
				<td style="text-align:right;"><a href="<?=$give_detail_link_p?>"><?=number_format($LIST[$i]['repay_count_p'])?></a></td>
				<td style="text-align:right;" class="border_r"><a href="<?=$give_detail_link?>"><?=number_format($LIST[$i]['repay_count'])?></a></td>
				<td style="text-align:right;"><a href="javascript:;" onClick="viewDetail('c','<?=$LIST[$i]['date']?>');" style="color:#FF2222"><?=number_format($LIST[$i]['target_c'])?></a></td>
				<td style="text-align:right;"><a href="javascript:;" onClick="viewDetail('p','<?=$LIST[$i]['date']?>');" style="color:#3366FF"><?=number_format($LIST[$i]['target_p'])?></a></td>
				<td style="text-align:right;"><a href="javascript:;" onClick="viewDetail('','<?=$LIST[$i]['date']?>');" style="color:purple"><?=number_format($LIST[$i]['target'])?></a></td>
				<td style="text-align:right;"><a href="javascript:;" onClick="viewDetail('c','<?=$LIST[$i]['date']?>');" style="color:#FF2222"><?=number_format($LIST[$i]['recorded_c'])?></a></td>
				<td style="text-align:right;"><a href="javascript:;" onClick="viewDetail('p','<?=$LIST[$i]['date']?>');" style="color:#3366FF"><?=number_format($LIST[$i]['recorded_p'])?></a></td>
				<td style="text-align:right;" class="border_r"><a href="javascript:;" onClick="viewDetail('','<?=$LIST[$i]['date']?>');" style="color:purple"><?=number_format($LIST[$i]['recorded'])?></a></td>
				<td><?=$taxinvoice_req_btn?></td>
				<td><?=$cashbill_req_btn?></td>
			</tr>
<?
	}

}
else {
	echo "			<tr><td colspan='20' align='center'>데이터가 없습니다.</td></tr>\n";
}
?>
		</tbody>
	</table>

</div>

<style>
#popup { display:none; position:fixed; z-index:1000000; width:90%;height:84%; left:5%; top:2%; min-width:1000px; min-height:500px; }
#popup .closeArea { margin:0 4px 8px auto; width:30px; height:30px; text-align:right; cursor:pointer; }
#popup .viewArea { width:100%; height:100%;background:#FFF; }
</style>
<div id="popup" style="display:none;">
	<div class="closeArea"><img id="close_button" src="/images/cancel_w1.png" height="30" style="opacity:1; cursor:pointer" alt="취소"></div>
	<div id="titleBar" style="padding:8px; text-align:left;background:#FD0017;color:#fff">
		<b>:: <span id="typeName">발행서 종류</span> ::</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		발행대상일 : <span id="bankingDate" style="color:#ffff66;font-weight:bold"><?=$banking_date?></span>
		<span style="margin-left:20px;"><a href="javascript:;" onClick="popup_content.location.reload();"><img src="/images/refresh20191031.png" width="24"></a></span>
	</div>
	<div class="viewArea">
		<iframe id="popup_content" name="popup_content" style="width:100%;height:100%;border:1px solid #FFF;"></iframe>
	</div>
</div>

<script>
fSubmit = function() {
	f= document.frmSearch;
	f.submit();
}

requestBill = function(_type, _date, button_id) {
	if(_type=='c') type_name = '세금계산서';
	else if(_type=='p') type_name = '현금영수증';
	else type_name = '세금계산서.현금영수증';

	if(confirm('[' + type_name + ']\n일괄발행을 시작합니다.\n실행 후에는 본 페이지를 이탈하여도 발급요청이 실행 됩니다.\n진행 하시겠습니까?')) {

		$.ajax({
			url : 'taxinvoice_proc.php',
			type: 'post',
			dataType: 'json',
			data:{type:_type, request_date: _date},
			success:function(result) {
				if(result.code=='SUCCESS') {
					return;
				}
				else {
					alert(result.message);
				}
			},
			error:function (e) { console.log(e); alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
		});

		$('#'+button_id).attr('disabled','true');

	}
}

viewDetail = function(_type, _date) {
	if(_type=='c') {
		titleColor = '#CC3333';
		_type_name = '세금계산서';
	}
	else if(_type=='p') {
		titleColor = '#3333CC';
		_type_name = '현금영수증';
	}
	else {
		titleColor = 'purple';
		_type_name = '세금계산서.현금영수증';
	}

	$.blockUI({
		message: $('#popup'),css:{ 'border':'0', 'position':'fixed' }
	});
	$('#titleBar').css('background', titleColor);
	$('#typeName').text(_type_name);
	$('#bankingDate').text(_date);
	popup_content.location.replace('taxinvoice_detail_list.php?type=' + _type + '&banking_date=' + _date);
	$('#popup').draggable();
}

$('#close_button').on('click', function() {
	$.unblockUI();
	return false;
});
</script>

<?

include_once ('../admin.tail.php');

?>