<?

$sub_menu = '200800';
include_once('./_common.php');

auth_check($auth[$sub_menu], "w");

$html_title = "관리자 설정";
if($_REQUEST['view']=='loginLog') $html_title.= ' > 로그인 기록';
if($_REQUEST['view']=='accessLog') $html_title.= ' > 페이지 열람  기록';
if($_REQUEST['view']=='privacyLog') $html_title.= ' > 개인정보 열람 내역';

$g5['title'] = $html_title;

include_once (G5_ADMIN_PATH.'/admin.head.php');


if(!$ADM['mb_id']) {

	$sql = "
		SELECT
			A.mb_id, A.mb_name,
			B.is_inspecter, B.is_editor, B.auth_info, B.privacy_auth, B.hp_auth, B.account_view_auth
		FROM
			g5_member A
		LEFT JOIN
			g5_sub_admin B  ON A.mb_no=B.mb_no
		WHERE
			A.mb_no='".trim($_REQUEST['mb_no'])."' AND A.mb_level >= 9";
	//print_rr($sql);
	$ADM = sql_fetch($sql);

}
else {
	alert('존재하지 않는 회원자료입니다.');
}

$view = (trim($_REQUEST['view'])) ? trim($_REQUEST['view']) : 'loginLog';

$sdate = ($_REQUEST['sdate']) ? trim($_REQUEST['sdate']) : date('Y-m-d');
$edate = ($_REQUEST['edate']) ? trim($_REQUEST['edate']) : date('Y-m-d');
$mb_no = trim($_REQUEST['mb_no']);

?>


<style>
table {border-collapse:collapse; font-size:13px}
.content .tabX { height:42px; background:url('/images/tab_bg.gif') repeat-x left bottom; }
.content .tabX li { float:left; width:200px; margin-right:3px; line-height:40px; text-align:center; font-size:16px; color:#202020; background-color:#f7f7f7; border:1px solid #e5e5e5; border-bottom:0; cursor:pointer; }
.content .tabX li.on { border:1px solid #ccc; background-color:#fff; border-bottom-color:#fff; }
.content .tabX li:last-child { margin:0; display:inline-block; }
.content .tabXarea { display:block;margin:0; padding:20px; min-height:400px;border-left:1px solid #ccc; border-right:1px solid #ccc; border-bottom:1px solid #ccc; }
#cont > div     { line-height:16px; padding:0; font-size:12px; }
#cont > div.off { height:17px; overflow:hidden; color:'' }
#cont > div.on  { color:#3366FF }
</style>
<style>
#paging_span { margin:0; padding:0; text-align:center; }
#paging_span span.arrow { padding:0; border:0; line-height:0; }
#paging_span span { display:inline-block; min-width:36px; color:#585657; line-height:33px; border:1px solid #D0D0D0; cursor:pointer }
#paging_span span.now { color:#fff; background-color:#000; border:1px solid #000; cursor:default }
</style>

<div class="tbl_head02 tbl_wrap">

	<div class="content" style="margin:30px auto">
		<ul class="tabX" style="width:100%;list-style:none;padding-left:20px;margin:0;">
			<li onClick="location.href='?view=loginLog&mb_no=<?=$mb_no?>&sdate=<?=$sdate?>&edate=<?=$edate?>'" class="<?=($view=='loginLog')?'on':''?>">로그인 기록</li>
			<li onClick="location.href='?view=accessLog&mb_no=<?=$mb_no?>&sdate=<?=$sdate?>&edate=<?=$edate?>'" class="<?=($view=='accessLog')?'on':''?>">페이지 열람 기록</li>
			<li onClick="location.href='?view=privacyLog&mb_no=<?=$mb_no?>&sdate=<?=$sdate?>&edate=<?=$edate?>'" class="<?=($view=='privacyLog')?'on':''?>">개인정보 열람 내역</li>
		</ul>
		<div class="tabXarea">

			<form name="f_srch">
				<input type="hidden" name="mode" value="<?=$mb_no?>">
				<input type="hidden" name="mb_no" value="<?=$mb_no?>">
				<ul class="col-sm-10 list-inline" style="margin-top:10px">
					<li>기록일 </li>
					<li><input type="text" id="sdate" name="sdate" value="<?=$sdate?>" class="form-control input-sm datepicker"></li>
					<li>~</li>
					<li><input type="text" id="edate" name="edate" value="<?=$edate?>" class="form-control input-sm datepicker"></li>
					<li><button type="submit" class="btn btn-sm btn-warning">검색</button></li>
				</ul>
			</form>

<?
include_once("subadmin.{$view}.php");
?>
		</div>
	</div>

<div>

<script>
$(document).ready(function() {
	$('#logList').floatThead();
});
</script>

<?
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>
