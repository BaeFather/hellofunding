<?
$sub_menu = "700900";
include_once('./_common.php');
include_once(G5_EDITOR_LIB);

$sdate = '2016-09-26';
$edate = '2017-10-12';

$g5['title'] = '회원별 정산정리 - 대상기간: '.$sdate.' ~ '.$edate;
include_once('../admin.head.php');

auth_check($auth[$sub_menu], 'w');
if($is_admin != 'super' && $w == '') alert('최고관리자만 접근 가능합니다.');
?>

<style>
table {border-collapse:collapse}
.content .tabX { height:42px; background:url('/images/tab_bg.gif') repeat-x left bottom; }
.content .tabX li { float:left; width:200px; margin-right:3px; line-height:40px; text-align:center; font-size:16px; color:#202020; background-color:#f7f7f7; border:1px solid #e5e5e5; border-bottom:0; cursor:pointer; }
.content .tabX li.on { border:1px solid #ccc; background-color:#fff; border-bottom-color:#fff; }
.content .tabX li:last-child { margin:0; display:inline-block; }
.content .tabXarea { display:block;margin:0; padding:20px; min-height:400px;border-left:1px solid #ccc; border-right:1px solid #ccc; border-bottom:1px solid #ccc; }
</style>

<div class="tbl_head02 tbl_wrap">
	<div class="content" style="margin:30px auto">
		<ul class="tabX" style="width:100%;list-style:none;padding-left:20px;;margin:0;">
			<li data-url="money_stats_ajax1.php" class="on">입금총액</li>
			<li data-url="money_stats_ajax2.php">출금총액</li>
			<li data-url="money_stats_ajax4.php">원리금상환액</li>
			<li data-url="money_stats_ajax3.php">투자금액</li>
			<li data-url="money_stats_ajax5.php">이벤트투자지급액</li>
		</ul>
		<div class="tabXarea"></div>
	</div>
</div>

<script>
$('.tabX li').click(function() {
	var cur = $(this).index();
	var url = $(this).data('url');

	//$('.tabXarea').empty();

	$.ajax({
		url : url,
		type: 'POST',
		data: {sdate:'<?=$sdate?>', edate:'<?=$edate?>'},
		success:function(data, textStatus, jqXHR) {
			$('.tabXarea').html(data);
		},
		beforeSend: function() { loading('on'); },
		complete: function() { loading('off'); },
		error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
	});

	$(this).addClass('on').siblings().removeClass('on');
});

$(document).ready(function() {
	$.ajax({
		url : "./money_stats_ajax1.php",
		type: 'POST',
		data: {sdate:'<?=$sdate?>', edate:'<?=$edate?>'},
		success:function(data, textStatus, jqXHR){
			$('.tabXarea').html(data);
		},
		beforeSend: function() { loading('on'); },
		complete: function() { loading('off'); },
		error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); }
	})
});
</script>

<?

include_once ('../admin.tail.php');

?>