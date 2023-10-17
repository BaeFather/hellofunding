<?
/**
 * 주담대 문자 스케줄링
 */
include_once('./_common.php');

if ($is_admin != 'super' && $w == '') {
	alert('최고관리자만 접근 가능합니다.');
}

$path = '/home/crowdfund/public_html';
include_once($path . "/lib/sms.lib.php");

while(list($key, $value) = each($_GET)) {
	if(!is_array(${$key})) ${$key} = trim($value);
}

//include_once('../admin.head.nomenu.php');
$g5['title'] = $menu['menu920'][3][1];
include_once('../admin.head.php');
?>
<?
$today = date("Y-m-d");
if (!$sdate) $sdate = $today;

$sql = "SELECT count(A.idx) cnt
		  FROM cf_loaner_push_schedule A
     LEFT JOIN cf_product B ON(A.product_idx=B.idx)
		 WHERE A.send_date='$sdate'
		   AND A.send_yn='Y'
		   AND B.state='1'
		 ";
$res = sql_query($sql);
$row = sql_fetch_array($res);

$total_count = $row['cnt'];
$rows = 200;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함
$num = $total_count - $from_record; // 리스트에 표시할 순차 번호

?>

<style>
.imgSelect {
	cursor: pointer;
}

.popupLayer {
	width : 550px;
	position: absolute;
	display: none;
	background-color: #ffffff;
	border: solid 2px #d0d0d0;
	padding: 10px;
}
.popupLayer #ax {
	position: absolute;
	top: 5px;
	right: 5px
}
</style>

<!-- 폼 레이어  -->
<div class="popupLayer" style="z-index:1;">
	<div id="ax">
		<span onClick="closeLayer(this)" style="cursor:pointer;font-size:1.5em" title="닫기">X</span>
	</div>
	<pre id="sms_msg" style="margin-top:20px;">
	</pre>
</div>
<!-- //폼 레이어  -->

<div class="row" style="margin-top:30px;">
	<div class="col-lg-12">
		<div class="panel-body" style="padding:0 1% 0 1%;">

<?
$sql1 = "SELECT DISTINCT send_date AS schd_date FROM cf_loaner_push_schedule ORDER BY send_date DESC";
$res1 = sql_query($sql1);
$cnt1 = $res1->num_rows;
?>

			<!-- 검색영역 START -->
			<div style="line-height:28px;">
				<form id="frmSearch" name="frmSearch" method="post" class="form-horizontal">
				<ul class="col col-md-* list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
					<li>
						<input list="tdate" id="sdate" name="sdate" value="<?=$sdate?>"  classa="form-control input-sm datepicker" placeholder="대상일자" onkeypressa="JavaScript:press(this.form);">
						<datalist id="tdate" name="tdate" >
						<?
						for ($i=0 ; $i<$cnt1 ; $i++) {
							$row1 = sql_fetch_array($res1);
							?>
							<option value="<?=$row1['schd_date']?>">
							<?
						}
						?>
						</datalist>
					</li>
					<li><button type="button" id="search_button" class="btn btn-sm btn-warning">검색</button></li>
					<li style="float:right;"><button type="button" id="sms_send_button" class="btn btn-sm btn-warning" onclick="go_sms_send();">문자 발송</button></li>
				</ul>
				<form>
			</div>


			<div class="dataTable_wrapper">

<table class="table table-striped table-bordered table-hover" style="font-size:15px;">
	<thead>
		<tr>
			<th class="text-center" style="background-color:#F8F8EF">NO.</th>
			<th class="text-center" style="background-color:#F8F8EF">순번</th>
			<th class="text-center" style="background-color:#F8F8EF">품번</th>
			<th class="text-center" style="background-color:#F8F8EF">상 품 명</th>
			<th class="text-center" style="background-color:#F8F8EF">차주명</th>
			<th class="text-center" style="background-color:#F8F8EF">전화번호</th>
			<th class="text-center" style="background-color:#F8F8EF">적용월</th>
			<th class="text-center" style="background-color:#F8F8EF">회 차</th>
			<th class="text-center" style="background-color:#F8F8EF">SMS구분</th>
			<th class="text-center" style="background-color:#F8F8EF">발송시각</th>
			<th class="text-center" style="background-color:#F8F8EF">결 과</th>
			<th class="text-center" style="background-color:#F8F8EF">내 용</th>
		</tr>
	<tbody>
<?
$sql = "SELECT A.* ,
			   B.title ,
			   C.mb_name
		  FROM cf_loaner_push_schedule A
	 LEFT JOIN cf_product B ON(A.product_idx=B.idx)
	 LEFT JOIN g5_member  C ON(A.mb_no=C.mb_no)
		 WHERE A.send_date='$sdate'
		   AND A.send_yn='Y'
		   AND B.state='1'
 	  ORDER BY A.send_time ASC";
$res = sql_query($sql);
$cnt = $res->num_rows;

$chk_s = "SELECT COUNT(*) chk_s FROM cf_loaner_push_schedule WHERE send_date='$sdate' AND send_yn='Y' AND msg_id<>0";
$chk_r = sql_query($chk_s);
$chk_rr= sql_fetch_array($chk_r);
$chk_count = $chk_rr["chk_s"];

for ($i=0 ; $i<$cnt ; $i++) {
	$row = sql_fetch_array($res);

	$sms_res11 = "";
	if ($row["msg_id"]) {
		if ($row['send_res'] == "") {
			$sms_res_arr = get_sms_res($row['msg_id'],'Y') ;
			$sms_res11 =  $sms_res_arr["Result"];
			$up_sql = "UPDATE cf_loaner_push_schedule SET send_res='$sms_res11' , send_datetime='$sms_res_arr[Send_Time]', sended_msg='".$sms_res_arr["Message"]."' WHERE idx='$row[idx]'";
			sql_query($up_sql);
		} else {
			$sms_res11 = $row['send_res'];
		}
	}

	$sms_res = "";
	if ($sms_res11=="0") {
		$sms_res = "ok";
	} else if ($sms_res11>0) {
		$sms_res = "<font color=red><b>에러</b></font>";
	} else {
		if ($chk_count) $sms_res = "<font color=gray><b>발송안함</b></font>";
		else $sms_res = "<font color=blue><b>대기중</b></font>";
	}


	$hp = masterDecrypt($row["mb_hp"], false);

		?>
	<tr class="odd" style="background-color:<?=$bgcolor?>">
		<td align="center"><?=$num--?></td>
		<td><?=$row['idx']?></td>
		<td><?=$row['product_idx']?></td>
		<td><?=$row['title']?></td>
		<td align="center"><?=$row['mb_name']?></td>
		<td align="center"><?=$hp?></td>
		<td align="center"><?=$row['tg_ym']?></td>
		<td align="center"><?=$row['turn']?></td>
		<td align="center"><?=$row['msg_gubun']?></td>
		<td align="center"><?=$row['send_date']?> <?=$row['send_time']?></td>
		<td align="center"><?=$sms_res?></td>
	<?
	if ($row["sended_msg"]) {
		$sended_msg = $row['sended_msg'];
		?>
		<td align="center"><button type=button class="btn btn-sm btn-default" onclick="show_msg2(<?=$row['idx']?>);">view</button></td>
		<?
	} else {
		?>
		<td align="center"><button type=button class="btn btn-sm btn-default" onclick="show_msg(<?=$row['idx']?>);">보 기</button></td>
		<?
	}
	?>
	</tr>
	<?
}
?>
</table>

			</div>

			<div id="paging_span" style="width:100%; margin:10px 0 0 0; text-align:center;"><? paging($total_count, $page, $rows, 10); ?></div>

		</div>
	</div>
</div>

<?

?>

<script>

function press(f) {
	console.log(event.keyCode);
	if(event.keyCode == 13){    // 13이 enter키를 의미함
		$("#search_button").click();
	}
}

$('#search_button').click(function() {
		var f = document.frmSearch;
		f.method = 'get';
		f.target = '_self';
		f.action = '<?=$_SERVER['PHP_SELF']?>';
		f.submit();
});

<? if ($cnt==0) { $tmp = date( 'Y-m', strtotime( date('Y-m') . ' +1 month' ) ); ?>
//$("#sdate").val("<?=$tmp?>");
$("#sdate").val("<?=date('Y')?>-");
$("#sdate").focus();
<? } ?>

function show_msg(idx) {

	$.ajax({
		url : "ajax_get_sms.php",
		type: "POST",
		dataType: 'json',
		data:{ idx: idx },
		success:function(data){
			$('#sms_msg').text(data['msg']);
		},
		error: function () {
			$('#sms_msg').html("통신 에러입니다.<br/><br/>잠시 후 다시 시도하여 주십시요.");
		}
	});

}

function show_msg2(idx) {
	$.ajax({
		url : "ajax_get_sms2.php",
		type: "POST",
		dataType: 'json',
		data:{ idx: idx },
		success:function(data){
			$('#sms_msg').text(data['msg']);
		},
		error: function () {
			$('#sms_msg').html("통신 에러입니다.<br/><br/>잠시 후 다시 시도하여 주십시요.");
		}
	});
}

$(function(){

	/* 클릭 클릭시 클릭을 클릭한 위치 근처에 레이어가 나타난다. */
	$('.btn-default').click(function(e)
	{
	/*
		var sWidth = window.innerWidth;
		var oWidth = $('.popupLayer').width();
		var oHeight = $('.popupLayer').height();

		// 레이어가 나타날 위치를 셋팅한다.
		var divLeft = e.clientX + 10;
		var divTop = e.clientY + 5 -104;

		// 레이어가 화면 크기를 벗어나면 위치를 바꾸어 배치한다.
		if( divLeft + oWidth > sWidth ) divLeft -= oWidth;
		if( divTop + oHeight > sHeight ) divTop -= oHeight;

		// 레이어 위치를 바꾸었더니 상단기준점(0,0) 밖으로 벗어난다면 상단기준점(0,0)에 배치하자.
		if( divLeft < 0 ) divLeft = 0;
		if( divTop < 0 ) divTop = 0;

		$('.popupLayer').css({
			"top": divTop,
			"left": divLeft,
			"position": "absolute"
		}).show();
	*/

	console.log("event handler");

	var x = e.clientX + (document.documentElement.scrollLeft?document.documentElement.scrollLeft:document.body.scrollLeft);
	var y = e.clientY + (document.documentElement.scrollTop?document.documentElement.scrollTop:document.body.scrollTop);
	//x = x - 330;
	//x = x - 500;
	var realw = $('.popupLayer').outerWidth();
	console.log(realw);
	x = x - realw + 15;
	y = y - 100;

		$('.popupLayer').css({
			"top": y,
			"left": x,
			"position": "absolute"
		}).show();
	});
});

function closeLayer( obj ) {
	$(obj).parent().parent().hide();
}

function go_sms_send() {

	var yn = confirm($("#sdate").val()+" 일분 문자를 발송하시겠습니까?");
	if (yn) window.open("./send_sms_web.php?ymd="+$("#sdate").val(), "send_sms_web", "width=700,height=500");
}
</script>

<? // echo masterEncrypt("01090624560", false); ?>
<? include_once ('../admin.tail.php'); ?>
