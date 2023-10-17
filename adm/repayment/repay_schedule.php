<?

if( in_array(date('w'), array('0','6')) && (date('Hi') >= '1055' && date('Hi') <= '1200') ) { echo "<script>alert('임시접근불가');history.back();</script>"; exit; }

include_once('./_common.php');

$sub_menu = "700500";
$g5['title'] = $menu['menu700'][5][1];

include_once (G5_ADMIN_PATH.'/admin.head.php');

auth_check($auth[$sub_menu], 'w');
if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');

while(list($k, $v)=each($_GET)) { ${$k} = trim($v); }

if(!$syear) $syear = date('Y');
if(!$smonth) $smonth = date('m');
$sym = $syear . "-" . $smonth;

$month_sdate = $sym."-01";

$first_yoil_num = date('w', strtotime($month_sdate));
$last_day = date('t', strtotime($month_sdate));

$month_edate = $sym."-".sprintf("%02d", $last_day);

$static_repay_date = $sym."-05";

//고정휴일 재설정
$CONF['STATIC_HOLYDAY'] = array(
	$syear.'-01-01', $syear.'-03-01', $syear.'-05-05', $syear.'-06-06', $syear.'-08-15', $syear.'-10-03', $syear.'-10-09', $syear.'-12-25'
);

?>

<style>
#detailDiv { position:relative; z-index:10; display:none; margin-top:20px; width:100%; }

.dayno { display:block; width:100%;height:100%; text-align:center;line-height:80px; font-size:50px;font-weight:bold;opacity:0.15; }
.holyday { color:#FF2222; }
.daymemo { position:absolute;z-index:2; width:13.3%;height:88px; text-align:left; font-size:13px; }
</style>

<div class="tbl_head02 tbl_wrap">

	<div style="line-height:28px;">
		<ul class="list-inline" style="display:inline-block;margin:1px 0 0;">
			<li style="float:left"><button type="button" class="btn btn-sm <?=($type=='')?'btn-gray':'btn-default'?>" onClick="location.href='?type=&syear=<?=$syear?>&smonth=<?=$smonth?>'">전체 상품 스케쥴</button></li>
			<li style="float:left"><button type="button" class="btn btn-sm <?=($type=='long')?'btn-gray':'btn-default'?>" onClick="location.href='?type=long&syear=<?=$syear?>&smonth=<?=$smonth?>'">1개월 이상 상품 스케쥴</button></li>
			<li style="float:left"><button type="button" class="btn btn-sm <?=($type=='short')?'btn-gray':'btn-default'?>" onClick="location.href='?type=short&syear=<?=$syear?>&smonth=<?=$smonth?>'">1개월 미만 상품 스케쥴</button></li>
		</ul>
	</div>
	<div style="line-height:28px;text-align:center;">
		<ul class="list-inline" style="display:inline-block;margin:20px 0 0;">
			<li style="float:left"><button type="button" onClick="location.href='?type=<?=$type?>&syear=<?=$syear-1?>&smonth=<?=$smonth?>';" class="btn btn-sm btn-default">이전년도</button></li>
			<li style="float:left"><button type="button" onClick="location.href='?type=<?=$type?>&syear=<?=date('Y', strtotime($month_sdate . " -1 month"))?>&smonth=<?=date('m', strtotime($month_sdate . " -1 month"))?>';" class="btn btn-sm btn-default">이전달</button></li>
			<li style="float:left; margin:0 20px; line-height:30px"><span style="font-size:16px;font-weight:bold;"><?=$syear?>년 <?=$smonth?>월</span></li>
			<li style="float:left"><button type="button" onClick="location.href='?type=<?=$type?>&syear=<?=date('Y', strtotime($month_sdate . " +1 month"))?>&smonth=<?=date('m', strtotime($month_sdate . " +1 month"))?>';" class="btn btn-sm btn-default">다음달</button></li>
			<li style="float:left"><button type="button" onClick="location.href='?type=<?=$type?>&syear=<?=$syear+1?>&smonth=<?=$smonth?>';" class="btn btn-sm btn-default">다음년도</button></li>
		</ul>
	</div>

	<table align='center' class="table-bordered">
		<colgroup>
			<col style="width:14.28" />
			<col style="width:14.28" />
			<col style="width:14.28" />
			<col style="width:14.28" />
			<col style="width:14.28" />
			<col style="width:14.28" />
			<col style="width:14.28" />
		</colgroup>
		<tr>
			<th style="background:#F8F8EF" id="movehere">일요일</th>
			<th style="background:#F8F8EF">월요일</th>
			<th style="background:#F8F8EF">화요일</th>
			<th style="background:#F8F8EF">수요일</th>
			<th style="background:#F8F8EF">목요일</th>
			<th style="background:#F8F8EF">금요일</th>
			<th style="background:#F8F8EF">토요일</th>
		</tr>
		<tr>
<?
$day = 1;
$x = 0;
$loop = true;
while($loop > 0) {
	$y = $x + 1;

	$tmp_num = $y - $first_yoil_num;

	$fcol1 = (($x%7)==0) ? "holyday" : "";

	if($x>=$first_yoil_num && $day <= $last_day) {

		$date = $sym."-".sprintf('%02d', $day);

		$fcol2 = (in_array($date, $CONF['STATIC_HOLYDAY']) || in_array($date, $CONF['DYNAMIC_HOLYDAY']) && $fcol1=='') ? "holyday" : "";

		echo "
			<td height='100'; align='center'>
				<div id='memo{$day}' class='daymemo'>".$print_str."</div>
				<span class='dayno $fcol1 $fcol2'>".$day."</span>
			</td>\n";


		$day++;
	}
	else {
		echo "			<td style='background:#EFEFEF'></td>\n";
	}

	if(($y%7)==0) {

		echo "	</tr>\n";
		if($last_day > $tmp_num) {
			echo "	<tr style='height:100px'>\n";
		}
		else {
			break;
		}

		if($y==42) break;

	}

	$x++;

}
?>
	</table>


	<div id="detailDiv"></div>

</div>

<script>
loadSchedule = function(targetDate, fid) {
	$.ajax({
		url : "repay_day_state.ajax.php",
		type: "get",
		dataType: "json",
		data: { type:'<?=$type?>', date: targetDate },
		success:function(data) {
			str = '';
			if(data.schedule_count > 0) {
				str += "<a href='javascript:;' onClick=\"getDetail('" + targetDate + "', '" + data.schedule_product + "', '" + data.schedule_turn + "');\" style='color:#FF2211;'>지급예정건 <strong>" + number_format(data.schedule_count) + "</strong></a>";
			}
			if(data.paid_product_count > 0) {
				paid_detail_url = "/adm/etc/profit_give_detail.php?type=<?=$type?>&syear=" + data.paid_year + "&smonth=" + data.paid_month + "&sday=" + data.paid_day;
				str += "<br>\n<a href='" + paid_detail_url + "' target='_blank' style='color:#3366FF;'>지급완료건 <strong>" + number_format(data.paid_product_count) + "</strong></a>";
			}

			$(fid).html(str);

		}
	});
}
$(document).ready(function() {
<?
for($i=0,$d=1; $i<$last_day; $i++,$d++) {
	$targetDate = $sym."-".sprintf("%02d", $d);
	echo "setTimeout(function() { loadSchedule('{$targetDate}', '#memo{$d}'); }, {$i}*300);\n";
}
?>
});

moveToPos = function(divID) {
	var scrollPosition = $(divID).offset().top;
	$('html, body').animate({
		scrollTop: scrollPosition
	}, 300);
	return false;
}

getDetail = function(sdate, pIdx, turn) {
	$.ajax({
		url : "repay_schedule.ajax.php",
		type: "post",
		data:{
			schedule_date: sdate,
			prd_idx: pIdx,
			turn: turn
		},
		success:function(data) {
			if(data!='') {
				$('#detailDiv').html(data);
				$('#detailDiv').slideDown();
				moveToPos('#movehere');
			}
			else {
				alert('상세 내역이 없습니다.');
			}
		},
		error:function (e) {
			console.log(e);
			alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.");
		}
	});
}
</script>

<?

include_once ('../admin.tail.php');

?>