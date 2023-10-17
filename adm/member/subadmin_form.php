<?

$sub_menu = '200800';
include_once('./_common.php');

auth_check($auth[$sub_menu], "w");

$html_title = "관리자 설정";
$html_title.= " > 관리자정보 ";

$submit_btn_text = ($_GET['mode'] == 'modi') ? '수정' : '등록';

$g5['title'] = $html_title.' '.$submit_btn_text;

include_once (G5_ADMIN_PATH.'/admin.head.php');

$WORK_PART = array('운영','정산','개발','디자인','법무','영업','제휴','마케팅','헬로페이','물건심사','상품관리','여신관리','총괄','대표','부대표');

foreach($_REQUEST as $k=>$v) { $$_REQUEST[$k] = trim($v); }


if($mode == 'modi') {

	$sql2 = "
			SELECT
				A.idx, A.is_inspecter, A.is_editor, A.auth_info, A.privacy_auth, A.hp_auth, A.account_view_auth, A.member_control_auth, A.product_control_auth, A.account_auth, A.allow_location, A.regdate,
				A.withdrawal, A.withdrawal_date, A.edit_mb_no, A.edit_datetime, A.approve_mb_no, A.approve_datetime,
				B.*
			FROM
				g5_sub_admin A
			LEFT JOIN
				g5_member B  ON	A.mb_no = B.mb_no
			WHERE
				A.mb_no = {$_GET['mb_no']}";
	$mb = sql_fetch($sql2);
	if (!$mb['mb_id']) alert('존재하지 않는 회원자료입니다.');

	$mb_no = $mb['mb_no'];

	if($mb['mb_hp'] || $mb['mb_hp_ineb']) {
		$mb['mb_hp'] = ($mb['mb_hp_ineb']) ? DGuardDecrypt($mb['mb_hp_ineb']) : masterDecrypt($mb['mb_hp'], false);
	}

}

?>

<script>
// 폼체크
function fmember_submit(obj) {
	if(!confirm('등록하시겠습니까?')) {
		return false;
	}
}

// 폼 리셋
function fmember_reset() {
	$("form")[0].reset();
}
</script>
<style>
.w100 {width:100px;}
.w150 {width:150px;}
.w200 {width:200px;}
</style>

<div class="tbl_frm01 tbl_wrap" style="width:1500px;">

	<form name="fmember" id="fmember" action="/adm/member/subamdin_form_update.php" onsubmit="return fmember_submit(this);" method="post">
		<input type="hidden" name="mode"         value="<?=$mode?>">
		<input type="hidden" name="mb_no"        value="<?=$mb_no?>">
		<input type="hidden" name="is_inspecter" value="<?=$mb['is_inspecter']?>">
		<input type="hidden" name="is_editor"    value="<?=$mb['is_editor']?>">

	<table class="table table-bordered" style="font-size:13px;">
		<caption><?=$g5['title']; ?></caption>
		<colgroup>
			<col width="15%">
			<col width="35%">
			<col width="15%">
			<col width="35%">
		</colgroup>
		<tbody>
			<tr>
				<th style="background:#F8F8EF">직무/성명</th>
				<td>
					<select name="work_part" id="work_part" class="form-control input-sm w200 required" value="<?=explode('-',$mb['mb_name'])[0]?>" title="직무를 선택하십시요."/>
						<option value=''>:: 직무선택 ::</option>
					<?
					for($i=0,$j=1; $i<count($WORK_PART); $i++,$j++) {
						$selected = ( $WORK_PART[$i] == explode('-',$mb['mb_name'])[0] ) ? 'selected' : '';
						echo "<option value='".$WORK_PART[$i]."' $selected>".$WORK_PART[$i]."</option>\n";
					}
					?>
					<select>
				</td>
				<th style="background:#F8F8EF">성명</th>
				<td><input type="text" class="form-control input-sm w200 required" name="mb_name" id="mb_name" size="20" value="<?=explode('-',$mb['mb_name'])[1]?>" title="성명을 선택하십시요."/></td>
			</tr>

			<tr>
				<th style="background:#F8F8EF"><label for="mb_id">아이디</label></th>
				<td><?=($_GET['mode'] == 'modi') ? $mb['mb_id'] : '<input type="text" class="frm_input required alnum_" name="mb_id" id="mb_id" size="20" value="" title="아이디를 입력해주세요."/>';?></td>
				<th style="background:#F8F8EF"><label for="mb_password">비밀번호</label></th>
				<td>
					<ul style="list-style:none;display:inline-block; margin:0;padding:0;">
						<li style="float:left;"><input type="password" class="form-control input-sm w200" name="mb_password" id="mb_password" size="30" value="" title="비밀번호를 입력해주세요."/></li>
						<li style="float:left;">&nbsp;</li>
						<li style="float:left;"><span style="color:brown"><?if($_GET['mode'] == 'modi') {?>입력할 경우, 입력된 패스워드로 변경 됩니다.<? } ?></span></li>
					</ul>
				</td>
			</tr>

			<tr>
				<th style="background:#F8F8EF"><label for="mb_hp">연락처</label></th>
				<td colspan="3"><input type="text" class="form-control input-sm w200" name="mb_hp" id="mb_hp" size="20" value="<?=$mb['mb_hp']?>" style="color:#AAA" title="핸드폰 번호를 입력해주세요."/></td>
			</tr>

			<tr>
				<th style="background:#F8F8EF"><label for="member_group">메뉴사용권한</label></th>
				<td colspan="3">
				<?
				foreach($top_meny_arr as $k => $v) {
					$auth_info_arr = explode(',',$mb['auth_info']);
					$checked = (in_array($k, $auth_info_arr)) ? "checked" : "";
				?>
				<label><input type="checkbox" name="auth[]" id="auth_<?=$k?>" value="<?=$k?>" <?=$checked?>> <?=$v?></label> &nbsp;
				<?
				}
				?>
				</td>
			</tr>

			<tr>
				<th style="background:#F8F8EF">개인정보열람권한</label></th>
				<td  colspan="3" style='padding;0'>
					<table class="table-bordered" style="width:250px;">
						<tr>
							<td style="background:#EFEFEF">주민등록번호</td>
							<td>
								<label><input type="radio" name="privacy_auth" id="privacy_auth_y" value="Y" <?=($mb['privacy_auth']=='Y')?'checked':''?>> 허용</label> &nbsp;
								<label><input type="radio" name="privacy_auth" id="privacy_auth_n" value="N" <?=($_REQUEST['mode']=='inst' || $mb['privacy_auth']=='N')?'checked':''?>> 불가</label>
							</td>
						</tr>
						<tr>
							<td style="background:#EFEFEF">연락처</td>
							<td>
								<label><input type="radio" name="hp_auth" id="hp_auth_y" value="Y" <?=($mb['hp_auth']=='Y')?'checked':''?>> 허용</label> &nbsp;
								<label><input type="radio" name="hp_auth" id="hp_auth_n" value="N" <?=($_REQUEST['mode']=='inst' || in_array($mb['hp_auth'], array('','N')))?'checked':''?>> 불가</label>
							</td>
						</tr>
						<tr>
							<td style="background:#EFEFEF">계좌번호</td>
							<td>
								<label><input type="radio" name="account_view_auth" id="account_view_auth_y" value="Y" <?=($mb['account_view_auth']=='Y')?'checked':''?>> 허용</label> &nbsp;
								<label><input type="radio" name="account_view_auth" id="account_view_auth_n" value="N" <?=($_REQUEST['mode']=='inst' || in_array($mb['account_view_auth'], array('','N')))?'checked':''?>> 불가</label>
							</td>
						</tr>
					</table>
				</td>
			</tr>

			<tr>
				<th style="background:#F8F8EF">회원정보 업데이트 권한</th>
				<td>
					<label><input type="radio" name="member_control_auth" id="member_control_auth_y" value="Y" <?=($mb['member_control_auth']=='Y')?'checked':''?>> 허용</label> &nbsp;
					<label><input type="radio" name="member_control_auth" id="member_control_auth_n" value="N" <?=($_REQUEST['mode']=='inst' || $mb['member_control_auth']=='N')?'checked':''?>> 불가</label>
				</td>
				<th style="background:#F8F8EF">상품 업데이트 권한</th>
				<td>
					<label><input type="radio" name="product_control_auth" id="product_control_auth_y" value="Y" <?=($mb['product_control_auth']=='Y')?'checked':''?>> 허용</label> &nbsp;
					<label><input type="radio" name="product_control_auth" id="product_control_auth_n" value="N" <?=($_REQUEST['mode']=='inst' || $mb['product_control_auth']=='N')?'checked':''?>> 불가</label>
				</td>
			</tr>

			<tr>
				<th style="background:#F8F8EF">정산처리 권한</th>
				<td>
					<label><input type="radio" name="account_auth" id="account_auth_y" value="Y" <?=($mb['account_auth']=='Y')?'checked':''?>> 허용</label> &nbsp;
					<label><input type="radio" name="account_auth" id="account_auth_n" value="N" <?=($_REQUEST['mode']=='inst' || $mb['account_auth']=='N')?'checked':''?>> 불가</label>
				</td>
				<th style="background:#F8F8EF">접속허용IP 설정</th>
				<td>
					<label><input type="radio" name="allow_location" id="allow_location_all" value="all" <?=($mb['allow_location']=='all')?'checked':''?> disabled> 전체</label> &nbsp;
					<label><input type="radio" name="allow_location" id="allow_location_local" value="local" checked> 사내업무망</label>
				</td>
			</tr>
		</tbody>
	</table>

	<div class="text-center" style="margin-top:25px;">
		<input type="submit" class="btn btn-md btn-danger" value="<?=$submit_btn_text;?>" style="width:100px;">
		<a href="/adm/member/subadmin_list.php" class="btn btn-md btn-success" style="width:100px;">취소</a>
		<? if($_GET['mode'] == 'modi') { ?><!--<input type="button" class="btn btn-md btn-default" value="원래대로" onclick="fmember_reset();">--><? } ?>
	</div>
	</form>

	<br/><br/>

</div>

<?
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>