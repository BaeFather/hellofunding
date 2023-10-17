<?
$sub_menu = "200100";
include_once('./_common.php');

while(list($k, $v) = each($_GET)) { ${$k} = trim($v); }

auth_check($auth[$sub_menu], "w");


$g5['title'] = '회원정보';
$g5['title'].= ($mb_id) ? ' 수정' : ' 등록';
$html_title = $g5['title'];


include_once (G5_ADMIN_PATH.'/admin.head.php');


if($mb_id) {

	$mode = 'edit';

	$mb = get_member($mb_id);

	$member_type  = $mb['member_type'];
	$member_group = $mb['member_group'];

	if(!$mb['mb_id']) alert('존재하지 않는 회원자료입니다.');

	switch($member_type) {
		case '1' : $member_type_text = '개인회원'; break;
		case '2' : $member_type_text = '기업회원'; break;
		case '3' : $member_type_text = 'SNS회원';  break;
		default  : $member_type_text = '개인회원'; break;
	}

	if($member_type=='2') {
		$regist_number = $mb['mb_co_reg_num'];
	}
	else {
		$regist_number = ($_SESSION['ss_accounting_admin']) ? getJumin($mb['mb_no']) : "";
	}

	$va_info1 = "";
	$va_info1.= ($mb['va_bank_code'] && $mb['virtual_account']) ? $BANK[$mb['va_bank_code']].' '.$mb['virtual_account'] : '';

	$va_info2 = "";
	$va_info2.= ($mb['va_bank_code2'] && $mb['virtual_account2']) ? $BANK[$mb['va_bank_code2']].' '.$mb['virtual_account2'] : '';

	if($mb['va_bank_code2'] && $mb['virtual_account2']) {
		if($mb['member_type']=='1') {
			$mb_name_x = preg_replace("/( )/", "", $mb['mb_name']);
			if(!preg_match("/".$mb_name_x."/", $mb['va_private_name2'])) $bank_update_target = 1;
		}
	}

	$query_str = str_replace("&mb_id={$_GET['mb_id']}", "", $_SERVER["QUERY_STRING"]);

	$ad_member = get_member('admin');

	//$mb['auto_inv_conf'] = get_auto_inv_conf($mb['mb_no']);

	// 차주 테이블 select
	$csql = "
		SELECT
			*
		FROM
			cf_chaju
		WHERE
			mb_no = '$mb[mb_no]'";

	$crow = sql_fetch($csql);


}
else {
	$mode = 'new';
}

add_javascript(G5_POSTCODE_JS, 0);    //다음 주소 js

?>

<style>
.roundbox { width:100%;list-style:none;padding:9px;clear:both; display:inline-block; border:1px dotted #555; border-radius:5px;background-color:#FDFECB; }
</style>

<script>
$(function() {
	$(".datepicker").datepicker({
		dateFormat: 'yy-mm-dd'
	});
});

// 폼 리셋
function fmember_reset() {
	$("form")[0].reset();
}
</script>

<style>
th.tit { text-align:center; background-color:#F8F8EF; }
</style>

<div class="tbl_frm01 tbl_wrap">
	<table>
		<caption><?=$html_title?></caption>
		<colgroup>
			<col style="width:1000px">
			<col>
		</colgroup>
		<tr>
			<td>

				<form name="fmember" id="fmember" action="/adm/member/member_form_update.old.php" method="post" enctype="multipart/form-data">
				<input type="hidden" name="mode"  value="<?=$mode?>">
				<input type="hidden" name="mb_no" value="<?=$mb['mb_no']?>">

				<table style="border-top:2px solid #3c5b9b;">
					<colgroup>
						<col style="width:150px">
						<col>
					</colgroup>
					<tbody>

						<tr>
							<th scope="row" class="tit"><label for="member_group">회원그룹</label></th>
							<td>
								<label class="radio-inline"><input type="radio" name="member_group" id="member_group_f" value="F" <?=($member_group=='' || $member_group=='F')?'checked':'disabled'?>> 투자회원</label> &nbsp;
								<label class="radio-inline"><input type="radio" name="member_group" id="member_group_l" value="L" <?=($member_group=='L')?'checked':'disabled'?>> 대출회원</label>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit"><label for="member_type">회원구분</label></th>
							<td>
								<ul style="width:100%;list-style:none;padding:0;clear:both;">
									<li style="float:left;">
										<select name="member_type" id="member_type" class="form-control required" style="width:200px">
											<option value="">:: 선택 ::</option>
											<option value="1" <? if($member_type=='1'){echo 'selected';} ?>>개인회원</option>
											<option value="2" <? if($member_type=='2'){echo 'selected';} ?>>법인회원</option>
											<option value="3" <? if($member_type=='3'){echo 'selected';} ?>>SNS회원</option>
										</select>
										<?
										/*
										<select name="member_type" id="member_type" class="form-control input-sm required" style="width:200px">
											<? if($member_type=='1') { ?><option value="1" <? if($member_type=='1'){echo 'selected';} ?>>개인회원</option><? } ?>
											<? if($member_type=='2') { ?><option value="2" <? if($member_type=='2'){echo 'selected';} ?>>법인회원</option><? } ?>
											<!--<option value="3" <? if($member_type=='3'){echo 'selected';} ?>>SNS회원</option>-->
										</select>
										*/
										?>
									</li>
									<li style="float:left; padding-left:9px">
<? if($member_group=='F' && $member_type=='1') { ?>
										<select name="member_investor_type" id="member_investor_type" title="투자자 유형을 선택해주세요." class="form-control input-sm required" style="width:250px">
											<option value="">:: 투자자 유형 선택 ::</option>
											<?
											$ARR_KEYS = array_keys($INDI_INVESTOR);
											for($i=0,$j=1; $i<count($INDI_INVESTOR); $i++,$j++) {
												$selected = ($mb['member_investor_type']==$ARR_KEYS[$i]) ? 'selected' : '';
											?>
											<option value="<?=$j?>" <?=$selected?>><?=$INDI_INVESTOR[$ARR_KEYS[$i]]['title']?> (투자한도:<?=price_cutting($INDI_INVESTOR[$ARR_KEYS[$i]]['site_limit']);?>원)</option>
											<?
											}
											?>
										</select>
<? } ?>
									</li>
								</ul>
							</td>
						</tr>

						<tr height="42">
							<th scope="row" class="tit"><label>아이디</label></th>
							<td>
								<ul style="width:100%;list-style:none;padding:0;clear:both;">
									<li style="float:left;"><input type="text" name="mb_id" id="mb_id" value="<?=$mb['mb_id']?>" title="아이디를 입력해주세요." <?=($mb['mb_id'])?'disabled':''?> class="form-control input-sm required" style="width:200px"></li>
									<? if($mode=='new') { ?>
									<li style="float:left;margin-left:8px;"><a id="confirm_id" class="btn btn-default" class="btn btn-default">중복체크</a></li>
									<li style="float:left;margin-left:8px;"><span id="mb_id_error" style="margin-left:30px;font-size:12px;"></span></li>
									<? } ?>
								</ul>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit"><label for="mb_password">패스워드</label></th>
							<td>
								<input type="password" name="mb_password" id="mb_password" size="20" value="" title="비밀번호를 입력해주세요." class="form-control input-sm <?=(!$mb['mb_no'])?' required' : ''?>" style="width:200px">
								<? if($mode=='edit'){ ?>&nbsp; <span class="sms_error" style='font-size:12px;'><font color="#000000">※ 입력할 경우, 입력된 패스워드로 변경 됩니다.</font></span><? } ?>
							</td>
						</tr>

<? if($member_type=='2') { ?>
						<tr>
							<th scope="row" class="tit"><label for="mb_co_reg_num">사업자정보</label></th>
							<td>
								<ul class="roundbox">
									<li style="float:left;width:150px"><label>사업자등록증 첨부</label></li>
									<li>
										<input type="file" name="business_license" id="business_license" size="50">
										<? if($mb["business_license"]) { ?>
										<div style="line-height:30px;">
											<a href="<?=G5_URL?>/mypage/license_download.php?mb_id=<?=$mb['mb_id']?>" alt="<?=$mb["business_license"]?>"class="btn btn-warning" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>	&nbsp;&nbsp;
											<label class="checkbox-inline"><input type="checkbox" id="del_business_license" name="del_business_license" value="Y"> 삭제</label>
											<input type="hidden" id="org_business_license" name="org_business_license" value="<?=$mb["business_license"]?>">
										</div>
										<? } ?>
									</li><br/>

									<li style="float:left;width:120px"><label>법인명</label></li>
									<li style="margin-bottom:6px;"><input type="text" class="frm_input input-sm" name="mb_co_name" id="mb_co_name" size="20" value="<?=$mb['mb_co_name']?>"> &nbsp; <label><input type="checkbox" id="corp_noneprofit" name="corp_noneprofit" value="1" <?=($mb['corp_noneprofit']=='1')?'checked':'';?>> 비영리법인</label></li>

									<li style="float:left;width:120px"><label>사업자등록번호</label></li>
									<li style="margin-bottom:6px;"><input type="text" class="frm_input input-sm" name="mb_co_reg_num" id="mb_co_reg_num" size="20" value="<?=$mb['mb_co_reg_num']?>" onKeyup="onlyDigit(this);"></li>

									<li style="float:left;width:120px;"><label>대표자명</label></li>
									<li style="margin-bottom:6px;"><input type="text" class="frm_input input-sm" id="mb_co_owner" name="mb_co_owner" size="20" value="<?=$mb['mb_co_owner']?>"></li>

									<li style="float:left;width:120px"><label>법인등록번호</label></li>
									<li style="margin-bottom:6px;"><input type="text" class="frm_input input-sm" id="corp_num" name="corp_num"  size="20" value="<?=($mb['corp_num'])?$mb['corp_num']:$crow['mb_legal_num']?>" onKeyup="onlyDigit(this);"></li>

									<li style="float:left;width:120px"><label>설립일</label></li>
									<li style="margin-bottom:6px;"><input type="text" class="frm_input input-sm datepicker" id="corp_rdate" name="corp_rdate" size="20" value="<?=$mb['corp_rdate']?>"></li>

									<li style="float:left;width:120px"><label>전화번호</label></li>
									<li style="margin-bottom:6px;"><input type="text" class="frm_input input-sm" id="corp_phone" name="corp_phone"  size="20" value="<?=$mb['corp_phone']?>" onKeyup="onlyDigit(this);"></li>

								</ul>

								<label class="checkbox-inline" style="width:100%"><input type="checkbox" name="is_creditor" id="is_creditor" value="Y" <?=($mb['is_creditor']=='Y')?'checked':''?>> 대부업</label>
								<div id="loan_co_license_zone" style="padding-top:12px;display:<?=($mb['is_creditor']=='Y')?'':'none'?>">
									<ul class="roundbox">
										<li style="float:left;width:120px"><label>대부업등록증</label></li>
										<li style="float:left;">
											<input type="file" name="loan_co_license" id="loan_co_license" size="50">
											<? if($mb["loan_co_license"]) {	?>
											<div style="line-height:30px;">
												<a href="<?=G5_URL?>/mypage/loan_co_license_download.php?mb_id=<?=$mb['mb_id']?>" alt="<?=$mb["bankbook"]?>" class="btn btn-md btn-warning" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>	&nbsp;&nbsp;
												<label class="checkbox-inline"><input type="checkbox" id="del_loan_co_license" name="del_loan_co_license" value="Y"> 삭제</label>
												<input type="hidden" id="org_loan_co_license" name="org_loan_co_license" value="<?=$mb["loan_co_license"]?>">
											</div>
											<? } ?>
										</li>
									</ul>
								</div>

							</td>
						</tr>

						<tr>
							<th scope="row" class="tit"><label for="mb_name">담당자명</label></th>
							<td>
								<input type="text" name="mb_name" id="mb_name" size="20" value="<?=$mb['mb_name']?>" title="담당자명을 입력해주세요." class="form-control input-sm required" style="width:200px">
							</td>
						</tr>

<? }else{ ?>

						<tr>
							<th scope="row" class="tit"><label for="mb_name">성명</label></th>
							<td>
								<ul class="col-sm-10 list-inline" style="margin:0;padding:0;">
									<li style="padding:0"><input type="text" name="mb_name" id="mb_name" size="20" value="<?=$mb['mb_name']?>" title="성명을 입력해주세요." class="form-control input-sm required" style="width:200px"></li>
									<? if($bank_update_target) { ?>
									<li><span id="btn_bankname_change" data-idx="<?=$mb['mb_no']?>" class="btn btn-md btn-danger" style="cursor:pointer;">신한은행 등록정보 변경</span></li>
									<? } ?>
								</ul>
							</td>
						</tr>
<? } ?>

<? if($member_group=='L' && $member_type=='1') { ?>
						<tr>
							<th scope="row" class="tit"><label>주민등록번호</label></th>
							<td>
								<input type="text" name="psnl_num1" id="psnl_num1" size="6" maxlength="6" value="<?=$crow['psnl_num1']?>" onkeyup="autoCursor(this.form)" class="form-control input-sm" style="width:100px;float:left"/>
								<span style="float:left;padding:5px 10px">-</span>
								<input type="text" name="psnl_num2" id="psnl_num2" size="6" maxlength="1" value="<?=$crow['psnl_num2']?>" class="form-control input-sm" style="width:38px;float:left"/>
								<span style="float:left;padding:10px 5px">******</span>
							</td>
						</tr>
<? } ?>

<? if($member_group=='L') { ?>
						<tr>
							<th scope="row" class="tit"><label>한도금액</label></th>
							<td>
								<input type="text" name="limit_amt" id="limit_amt" value="<?=$crow['limit_amt']?>" class="form-control input-sm chk_number" style="width:200px">
							</td>
						</tr>
						<tr>
							<th scope="row" class="tit"><label>신용정보</label></th>
							<td style="padding-bottom:0">
								<ul style="padding:5px 8px 0;width:100%;list-style:none;clear:both;display:inline-block">
									<li style="float:left;width:120px"><label>신용점수</label></li>
									<li style="float:left;"><input type="text" name="credit_score" id="credit_score" value="<?=$crow['credit_score']?>" size="20" class="form-control input-sm"/></li>
									<li style="float:left;width:110px;margin-left:90px"><label>평가회사</label></li>
									<li style="float:left;"><input type="text" name="rating_cp" id="rating_cp" value="<?=$crow['rating_cp']?>" size="20" class="form-control input-sm" style="width:210px"/></li>
								</ul>
							</td>
						</tr>

<? } ?>

<?
if($member_group=='F' || ($member_group=='L' && $member_type=='1')) {
?>
						<tr>
							<th scope="row" class="tit"><label for="mb_email">이메일</label></th>
							<td>
								<input type="text" name="mb_email" id="mb_email" size="20" value="<?=$mb['mb_email']?>" title="이메일을 입력해주세요." class="form-control input-sm email" style="width:200px">
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit"><label for="mb_hp">핸드폰 번호</label></th>
							<td>
								<input type="text" name="mb_hp" id="mb_hp" size="20" value="<?=$mb['mb_hp']?>" title="핸드폰 번호를 입력해주세요." onKeyup="onlyDigit(this);" class="form-control input-sm required" style="width:200px">
							</td>
						</tr>
<?
}
?>

						<tr>
							<th scope="row" class="tit"><label>특별구분</label></th>
							<td>
								<ul class="col-sm-10 list-inline">
									<li><label class="checkbox-inline"><input type="checkbox" name="mb_10" value="1" <?=($mb['mb_10'])?'checked':''?>> 자사직원</label></li>
									<li><label class="checkbox-inline"><input type="checkbox" name="is_invest_manager" value="1" <?=($mb['is_invest_manager'])?'checked':''?>> 자산운용사</label></li>
									<li><label class="checkbox-inline"><input type="checkbox" name="is_sbiz_owner" value="1" <?=($mb['is_sbiz_owner'])?'checked':''?>> 자동투자선순위대상자</label></li>
								</ul><br />
								<ul class="col-sm-10 list-inline">
									<li><label class="checkbox-inline"><input type="checkbox" name="remit_fee" value="1" <?=($mb['remit_fee'])?'checked':''?>> 플랫폼수수료면제</label></li>
									<li style="padding-left:10px;">수수료면제적용일</li>
									<li><input type="text" class="form-control input-sm datepicker" id="remit_fee_sdate" name="remit_fee_sdate" value="<?=$mb['remit_fee_sdate']?>" style="width:100px; text-align:center;"></li>
								</ul>
							</td>
						</tr>

<? if($member_type=='1') { ?>
						<tr>
							<th scope="row" class="tit"><label>사업자정보</label></th>
							<td>

								<label class="checkbox-inline" style="width:100%"><input type="checkbox" name="is_owner_operator" id="is_owner_operator" value="1" <?=($mb['is_owner_operator'])?'checked':''?>> 개인사업자</label>
								<div id="business_license_zone" style="padding-top:12px;display:<?=($mb['is_owner_operator']=='1')?'':'none'?>">
									<ul class="roundbox">
										<li style="float:left;width:150px"><label>사업자등록증 첨부</label></li>
										<li>
											<input type="file" name="business_license" id="business_license" size="50">
											<? if($mb["business_license"]) { ?>
											<div style="line-height:30px;">
												<a href="<?=G5_URL?>/mypage/license_download.php?mb_id=<?=$mb['mb_id']?>" alt="<?=$mb["business_license"]?>" class="btn btn-md btn-warning" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>	&nbsp;&nbsp;
												<label class="checkbox-inline"><input type="checkbox" id="del_business_license" name="del_business_license" value="Y"> 삭제</label>
												<input type="hidden" id="org_business_license" name="org_business_license" value="<?=$mb["business_license"]?>">
											</div>
											<? } ?>
										</li><br/>

										<li style="float:left;width:120px"><label>상호명</label></li>
										<li style="margin-bottom:6px;"><input type="text" class="frm_input input-sm" name="mb_co_name" id="mb_co_name" size="20" value="<?=$mb['mb_co_name']?>"></li>

										<li style="float:left;width:120px"><label>대표자명</label></li>
										<li style="margin-bottom:6px;"><input type="text" class="frm_input input-sm" name="mb_co_owner" id="mb_co_owner" size="20" value="<?=$mb['mb_co_owner']?>"></li>

										<li style="float:left;width:120px"><label>사업자등록번호</label></li>
										<li style="margin-bottom:6px;"><input type="text" class="frm_input input-sm" name="mb_co_reg_num" id="mb_co_reg_num" size="20" maxlength="12" value="<?=$mb['mb_co_reg_num']?>" onKeyup="onlyDigit(this);"></li>

										<li style="float:left;width:120px"><label>개업일</label></li>
										<li style="margin-bottom:6px;"><input type="text" class="frm_input input-sm datepicker" id="corp_rdate" name="corp_rdate" size="20" value="<?=$mb['corp_rdate']?>"></li>

										<li style="float:left;width:120px"><label>전화번호</label></li>
										<li style="margin-bottom:6px;"><input type="text" class="frm_input input-sm" id="corp_phone" name="corp_phone"  size="20" value="<?=$mb['corp_phone']?>" onKeyup="onlyDigit(this);"></li>
									</ul>
								</div>

								<label class="checkbox-inline" style="width:100%"><input type="checkbox" name="is_creditor" id="is_creditor" value="Y" <?=($mb['is_creditor']=='Y')?'checked':''?>> 대부업</label>
								<div id="loan_co_license_zone" style="padding-top:12px;display:<?=($mb['is_creditor']=='Y')?'':'none'?>">
									<ul class="roundbox">
										<li style="float:left;width:120px"><label>대부업등록증</label></li>
										<li style="float:left;">
											<input type="file" name="loan_co_license" id="loan_co_license" size="50">
											<? if($mb["loan_co_license"]) {	?>
											<div style="line-height:30px;">
												<a href="<?=G5_URL?>/mypage/loan_co_license_download.php?mb_id=<?=$mb['mb_id']?>" alt="<?=$mb["bankbook"]?>" class="btn btn-md btn-warning" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>	&nbsp;&nbsp;
												<label class="checkbox-inline"><input type="checkbox" id="del_loan_co_license" name="del_loan_co_license" value="Y"> 삭제</label>
												<input type="hidden" id="org_loan_co_license" name="org_loan_co_license" value="<?=$mb["loan_co_license"]?>">
											</div>
											<? } ?>
										</li>
									</ul>
								</div>
							</td>
						</tr>
<? } ?>

						<tr>
							<th scope="row" class="tit"><label for="zip_num">주소</label></th>
							<td class="td_addr_line">
								<ul class="col-sm-10 list-inline">
									<li style="float:left;width:120px"><label for="zip_num">우편번호</label></li>
									<li style="float:left;"><input type="text" name="zip_num" value="<?=$mb['zip_num']?>" id="zip_num" onClick="win_zip('fmember', 'zip_num', 'mb_addr1', 'mb_addr2', 'mb_addr3', 'mb_addr_jibeon');" maxlength="6" readonly class="form-control input-sm"></li>
									<li style="float:left;padding-left:4px"><button type="button" onClick="win_zip('fmember', 'zip_num', 'mb_addr1', 'mb_addr2', 'mb_addr3', 'mb_addr_jibeon');" class="btn btn-sm btn-default">주소검색</button></li>
								</ul>
								<ul class="col-sm-10 list-inline">
									<li style="float:left;width:120px"><label for="mb_addr1">도로명주소(기본)</label></li>
									<li style="float:left;"><input type="text" name="mb_addr1" value="<?=$mb['mb_addr1']?>" id="mb_addr1" readonly class="form-control input-sm" style="width:400px;"></li>
								</ul>
								<ul class="col-sm-10 list-inline">
									<li style="float:left;width:120px"><label for="mb_addr_jibeon">지번주소</label></li>
									<li style="float:left;"><input type="text" name="mb_addr_jibeon" value="<?=$mb['mb_addr_jibeon']?>" id="mb_addr_jibeon" readonly class="form-control input-sm" style="width:400px;"></li>
								</ul>
								<ul class="col-sm-10 list-inline">
									<li style="float:left;width:120px"><label for="mb_addr2">이하상세주소</label></li>
									<li style="float:left;"><input type="text" name="mb_addr2" value="<?=$mb['mb_addr2']?>" id="mb_addr2" class="form-control input-sm" style="width:400px;"></li>
								</ul>
								<ul class="col-sm-10 list-inline">
									<li style="float:left;width:120px"><label for="mb_addr3">참고항목</label></li>
									<li style="float:left;"><input type="text" name="mb_addr3" value="<?=$mb['mb_addr3']?>" id="mb_addr3" class="form-control input-sm" style="width:400px;"></li>
								</ul>
							</td>
						</tr>

						<? if($member_group=='F') { ?>
						<tr>
							<th scope="row" class="tit"><label for="mb_mailling">투자설명서</label></th>
							<td>
								<label class="checkbox-inline"><input type="checkbox" name="invested_mailling" id="invested_mailling" value="1" <? if($mb['invested_mailling']==1){ echo 'checked'; }?>> 투자설명서 발급 동의</label>
								<span style="margin-left:16px;font-size:12px;">(정상투자 실행시 관련 내용을 전자우편으로 고지함)</span>
							</td>
						</tr>
						<? } ?>

						<tr>
							<th scope="row" class="tit"><label for="mb_mailling">마케팅 수신동의</label></th>
							<td>
								<label class="checkbox-inline"><input type="checkbox" name="mb_mailling" id="mb_mailling" value="1" <? if($mb['mb_mailling']==1){ echo 'checked'; }?>> 이메일 수신동의</label> &nbsp;
								<label class="checkbox-inline"><input type="checkbox" name="mb_sms" id="mb_sms" value="1" <? if($mb['mb_sms']==1){ echo 'checked'; }?>> SMS 수신동의</label>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit"><label>본인계좌</label></th>
							<td style="line-height:30px">
								<input type="hidden" id="private_yn" name="private_yn" alt="계좌인증플래그">
								<input type="hidden" id="bank_name" name="bank_name" value="<?=$mb['bank_name']?>" alt="은행명">

								<ul class="col-sm-10 list-inline">
									<li style="float:left;width:120px"><label for="strGbn">소유자구분</label></li>
									<li style="float:left;width:170px">
										<select name="strGbn" id="strGbn" class="form-control input-sm">
											<option value="1" <?=($member_type=='1')?'selected':''?>>개인</option>
											<?
											if($member_type=='2') {
												$selected = 'selected';
											?>
											  <option value="2" <?=$selected?>>사업자</option>
											<?
											}
											?>
										</select>
									</li>
								</ul>

								<ul class="col-sm-10 list-inline">
									<li style="float:left;width:120px"><label for="mb_co_reg_num">주민등록번호</label></li>
									<li style="float:left;width:170px"><input type="text" name="regist_number" id="regist_number" maxlength="13" value="<?=getJumin($mb['mb_no']);?>" onKeyup="onlyDigit(this);" class="form-control input-sm" style="width:200px;"></li>
								</ul>

								<ul class="col-sm-10 list-inline">
									<li style="float:left;width:120px"><label for="bank_code">은행</label></li>
									<li style="float:left;width:170px">
										<select name="bank_code" id="bank_code" class="form-control input-sm" style="width:200px;">
											<option value="">:: 은행선택 ::</option>
										<?
										$BANK_KEYS = array_keys($BANK);
										for($i=0; $i<count($BANK); $i++) {
											$selected = ($BANK_KEYS[$i]==sprintf("%03d", $mb["bank_code"])) ? 'selected' : '';
											echo "	<option value='".$BANK_KEYS[$i]."' $selected>".$BANK[$BANK_KEYS[$i]]."</option>\n";
										}
										?>
										</select>
									</li>
									<li style="float:left;width:120px;margin-left:50px"><label for="account_num">계좌번호</label></li>
									<li style="float:left;width:170px"><input type="text" name="account_num" id="account_num" value="<?=$mb['account_num']?>" title="계좌번호를 입력해주세요." onKeyup="onlyDigit(this);" class="form-control input-sm" style="width:200px;"></li>
								</ul>
								<ul class="col-sm-10 list-inline">
									<li style="float:left;width:120px"><label for="bank_code">예금주</label></li>
									<li style="float:left;width:170px"><input type="text" name="bank_private_name" id="bank_private_name" value="<?=$mb['bank_private_name']?>" title="예금주를 입력해주세요." class="form-control input-sm" style="width:200px;"></li>
									<li style="float:left;width:120px;margin-left:50px"><label for="bank_private_name_sub">부기명</li>
									<li style="float:left;width:170px"><input type="text" name="bank_private_name_sub" id="bank_private_name_sub" value="<?=$mb['bank_private_name_sub']?>" title="부기명을 입력해주세요." class="form-control input-sm" style="width:200px;"></li>
								</ul>
								<ul class="col-sm-10 list-inline">
									<li style="text-align:center;"><span style="font-size:12px">※ 부기명 : 계좌예금주명상의 괄호내에 표기되는 보조표기명</span></li>
									<? if($member_type=='1') { ?><li style="text-align:center;"><span id="btn_bank_account_auth" class="btn btn-md btn-danger" style="cursor:pointer;">계좌인증</span></li><? } ?>
								</ul>

								<ul class="roundbox">
									<li style="float:left;width:120px"><label>통장사본</label></li>
									<li style="float:left;">
										<input type="file" name="bankbook" id="bankbook" size="50">
										<? if($mb["bankbook"] != ""){ ?>
										<div style="line-height:30px;">
											<a href="<?=G5_URL?>/mypage/bankbook_download.php?mb_id=<?=$mb['mb_id']?>" alt="<?=$mb["bankbook"]?>" class="btn btn-md btn-warning" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>	&nbsp;&nbsp;
											<label class="checkbox-inline"><input type="checkbox" id="del_bankbook" name="del_bankbook" value="Y"> 삭제</label>
											<input type="hidden" id="org_bankbook" name="org_bankbook" value="<?=$mb["bankbook"]?>">
										</div>
										<? } ?>
									</li>
								</ul>

							</td>
						</tr>

<?
if($mode=='edit') {
	if($mb['member_group']=='F') {			// 투자자 예치금 입금용 가상계좌
?>
						<tr height="42">
							<th scope="row" class="tit"><label>가상계좌</label></th>
							<td style="line-height:24px">
								<ul class="col-sm-10 list-inline">
									<li style="float:left;width:80px"><label>신한가상</label></li>
									<li style="float:left;width:300px" id="sh_va_info"><?=($va_info2) ? $va_info2 : '<button type="button" id="btn_sh_va_regist" class="btn btn-md btn-danger" style="height:22px;line-height:18px;font-size:12px;cursor:pointer;padding-top:0">발급받기</button>';?></li>
								</ul>
								<? if($va_info1) { ?>
								<ul class="col-sm-10 list-inline">
									<li style="float:left;width:80px"><label>세틀뱅크</label></li>
									<li style="float:left;width:300px"><?=($va_info1) ? $va_info1 : '<button type="button" id="btn_va_regist" class="btn btn-md btn-danger" style="height:22px;line-height:18px;font-size:12px;cursor:pointer;padding-top:0">발급받기</button>';?></li>
								</ul>
								<? } ?>
							</td>
						</tr>
						<tr>
							<th scope="row" class="tit"><label>환급계좌선택</label></th>
							<td>
								<label class="radio-inline"><input type="radio" name="receive_method" value="1" <?=($mb['receive_method']=='1')?'checked':''?> <?=($mb['bank_code'] && $mb['bank_private_name'] && $mb['account_num'])?'checked':'disabled'?>> 환급계좌</label> &nbsp;&nbsp;
								<label class="radio-inline"><input type="radio" name="receive_method" value="2" <?=($mb['receive_method']=='2')?'checked':''?>> 예치금</label>
							</td>
						</tr>
						<!--
						<tr>
							<th scope="row" class="tit"><label>자동투자 설정</label></th>
							<td>
								<?
								if (count($mb['auto_inv_conf'])>0) {
									?>
								<ul class="col-sm-10 list-inline">
									<li style="float:left;width:100px;text-align:right"><?=number_format($mb['auto_inv_conf'][0]["setup_amount"])?> 원</li>
									<li style="float:left;width:250px;text-align:left;width:560px;"><label for="mb_addr1">
									<?
									for ($mm=0 ; $mm<count($mb['auto_inv_conf']) ; $mm++) {
									//echo $mb['auto_inv_conf'][$mm]["grp_title"]." ".number_format($mb['auto_inv_conf'][$mm]["setup_amount"])." 둰<br/>";
										if ($mm<>0) echo " , ";
										echo $mb['auto_inv_conf'][$mm]["grp_title"];
									}
									?>
									</label></li>
								</ul>
									<?
								}
								?>
							</td>
						</tr>
						-->
<?
	}
	else if($mb['member_group']=='L') {			// 대출자 상환용 가상계좌
?>
						<tr height="42">
							<th scope="row" class="tit"><label>가상계좌<br>(대출상환용)</label></th>
							<td style="line-height:24px">
<?
		$sqlx = "
			SELECT
				A.bank_cd, A.acct_no, A.cmf_nm, A.acct_st, A.open_il,
				B.BANK_CODE, B.VR_ACCT_NO, B.CORP_NAME, B.USE_FLAG
			FROM
				IB_vact_hellocrowd A
			LEFT JOIN
				KSNET_VR_ACCOUNT B
			ON
				A.acct_no=B.VR_ACCT_NO
			WHERE
				CUST_ID='".$mb['mb_no']."'
			ORDER BY
				acct_st, acct_no DESC";
		$resx = sql_query($sqlx);
		while($VALIST = sql_fetch_array($resx)) {
			$acct_st = ($VALIST['acct_st']==9) ? '해지' : '정상';
			echo "<div>" . $BANK[$VALIST['BANK_CODE']] . " &nbsp; " . $VALIST['VR_ACCT_NO'] . " &nbsp; " . $VALIST['CORP_NAME'] . " &nbsp; 설정일." . date('Y-m-d', strtotime($VALIST['open_il'])) . " &nbsp; " . $acct_st ."</div>\n";
		}
?>
								<ul class="col-sm-10 list-inline" id="VAInfo" style="display:none">
									<li id="sh_va_info"></li>
								</ul>
								<ul class="col-sm-10 list-inline" id="VARequest" style="display:block">
									<li style="padding-left:0"><input type="text" id="loaner_va_name" class="form-control input-sm" style="width:200px"></li>
									<li><button type="button" id="btn_loaner_vacct_regist" class="btn btn-md btn-danger" style="height:32px;line-height:32px;font-size:12px;cursor:pointer;padding-top:0">신한은행 가상계좌 발급받기</button></li>
								</ul>
							</td>
						</tr>
<?
	}
}
?>

					</tbody>
				</table>

				<br><br>

				<table style="border-top:2px solid #3c5b9b;">
					<colgroup>
						<col style="width:150px">
						<col>
					</colgroup>
					<tbody>
						<tr>
							<th scope="row" class="tit"><label for="member_group">관리자메모</label></th>
							<td>
								<textarea id="mb_memo" name="mb_memo" class="form-control input-sm" style="height:150px;"><?=stripSlashes($mb['mb_memo'])?></textarea>
							</td>
						</tr>
					</tbody>
				</table>


				<div class="text-center" style="margin-top:10px;">
					<input type="submit" class="btn btn-md btn-success" value="<?=($mode=='edit')?'수정':'등록';?>" onClick="if(!confirm('<?=($mode=='edit')?'수정':'등록';?> 하시겠습니까?')){ return false; }" style="width:100px">
					<button type="button" onClick="fmember_reset();" class="btn btn-md btn-default" style="width:100px">폼초기화</button>
					<? if($mb['member_group']=='F' && $mb['virtual_account2']){ ?><button type="button" id="shinhan_data_update" class="btn btn-md btn-danger" style="width:150px">기관정보수정</button><? } ?>
				</div>
				</form>

				<div class="text-left" style="margin-top:20px;">
					<a href="/adm/member/member_list.php?<?=$query_str?>" class="btn btn-md btn-default">목록</a>
				</div>

			</td>
			<td valign="top">

				<!--// SMS문자발송 //-------------------------------------->
				<div style="display:inline-block;position:fixed;top;10px;width:245px;height:550px;border:1px solid #aaa;background:#fafafa">
					<iframe id="sms_frame" name="sms_frame" src="/adm/sms_sender/sms.form.php?to_hp=<?=$mb['mb_hp']?>" frameborder="0" scrolling="no" style="width:100%;height:100%;"></iframe>
				</div>
				<!--// SMS문자발송 //-------------------------------------->

			</td>
		</tr>
	</table>

</div>

<script>
$('#shinhan_data_update').click(function() {
	if( confirm('금융기관측 회원정보를 업데이트 하시겠습니까?\n\n'
	          + '금융기관측 등록항목:\n'
						+ '  성명, 업체명, 대표자명, 생년월일, 사업자번호, 휴대폰번호,\n'
						+ '  상환계좌번호, 예치금입금용 가상계좌번호') )
	{
		$.ajax({
			url : "ajax_shinhan_update.php",
			type: "POST",
			data : {'mb_no':'<?=$mb['mb_no']?>'},
			dataType : 'json',
			success: function(data) {
				if(data.result=='success') {
					alert('금융기관 정보변경이 완료되었습니다.');
				}
				else {
					alert('변경 실패 : ' + data.message);
				}
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function(e) { return; }
		});
	}
});
</script>

<script>
//아이디 체크
var auth_mb_id = '';
$('#confirm_id').click(function() {
	var f = document.fmember;
	var mb_id = trim(f.mb_id.value);

	$('#mb_id_error').empty();

	if(mb_id=='') { alert('ID를 입력 하십시요.'); f.mb_id.value = ''; f.mb_id.focus(); }
	else {
		$.ajax({
			url : "/member/confirm_id.php",
			type: "POST",
			data : {'prm1':mb_id},
			success: function(data) {
				if(data=='o')      { $('#mb_id_error').html('<font color="green">사용 가능한 아이디 입니다.</font>'); /*auth_mb_id = mb_id;*/ }
				else if(data=='x') { $('#mb_id_error').html('<font color="red">사용 할 수 없는 아이디 입니다!</font>'); }
				else               { alert('시스템 오류 입니다.'); }
			},
			error: function () {
				alert('네트워크 오류 입니다. 잠시 후 다시 시도하십시요.');
			}
		})
	}
});
</script>

<script>
$('input:checkbox[name=is_creditor]').click(function(){
	cval = $('input:checkbox[name=is_creditor]:checked').val();
	if(cval=='Y') {
		$('#loan_co_license_zone').slideDown();
	}
	else {
		$('#loan_co_license_zone').slideUp();
	}
});

$('input:checkbox[name=is_owner_operator]').click(function(){
	cval = $('input:checkbox[name=is_owner_operator]:checked').val();
	if(cval=='1') {
		$('#business_license_zone').slideDown();
	}
	else {
		$('#business_license_zone').slideUp();
	}
});

$('#member_type').change(function() {
<? if($mode=='edit') { ?>
	if($('#member_type').val()!='1') {
		$('#member_investor_type').attr('disabled', true);
		$('#member_investor_type').css('display', 'none');
	}
	else {
		$('#member_investor_type').attr('disabled', false);
		$('#member_investor_type').css('display', 'block');
	}
<? } else { ?>
	$(location).attr('href', 'member_form.php?member_type=' + $('#member_type').val() + '&member_group=' + $('input:radio[name=member_group]:checked').val());
<? } ?>
});

$('#member_group_f').on('click', function() {
	$('#member_investor_type').attr('disabled', false);
	$('#member_investor_type').css('display', 'block');
});
$('#member_group_l').on('click', function() {
	$('#member_investor_type').attr('disabled', true);
	$('#member_investor_type').css('display', 'none');
});




$('#bank_code').on('change', function() {
	var frm = document.fmember;
	var i = frm.bank_code.options.selectedIndex
	frm.bank_name.value = frm.bank_code.options[i].text;
});

$('#btn_bank_account_auth').click(function() {

	var frm = document.fmember;
	var juminno = (frm.strGbn.value=='2') ? frm.mb_co_reg_num.value : frm.regist_number.value;
	var tmp_val = frm.strGbn.value + ' : ' + juminno + ' : ' + frm.bank_code.value + ' : ' + frm.bank_name.value + ' : ' + frm.account_num.value + ' : ' + frm.bank_private_name.value;
	tmp_val += ' : (' + frm.bank_private_name_sub.value + ')';
	alert(tmp_val);

	if(frm.bank_name.value=='') { alert("은행을 선택하십시요."); frm.bank_code.focus(); return false; }
	if(frm.account_num.value=='') { alert("계좌번호를 입력하십시요."); frm.account_num.focus(); return false; }
//if(frm.bank_private_name.value=='') { alert("예금주명을 입력하십시요."); frm.bank_private_name.focus(); return false; }
	if(frm.bank_private_name.value != frm.mb_name.value) {
		if( !confirm("예금주명이 회원성명과 일치하지 않습니다.\n그래도 진행하시겠습니까?") ) { frm.bank_private_name.focus(); return false; }
	}
	if(frm.strGbn.value=='1') {
		if(frm.regist_number.value=='') { alert("주민등록번호를 입력하십시요."); frm.regist_number.focus(); return false; }
	}


	$.ajax({
		type: "POST",
		url: "/mypage/check_count_proc_shinhan.php",
		dataType: 'JSON',
		data: {
			strBankCode : $('#bank_code').val(),
			strAccountNo : $('#account_num').val()
			//JUMINNO : juminno
		},
		success:function(data) {
			if(data.RCODE == '00000000') {
				frm.private_yn.value = 'Y';
				$('#bank_private_name').val(data.ACCT_OWNER_NM);
				alert('인증되었습니다');
				str_account = $('#bank_private_name').val() + ',' + $('#regist_number').val() + ',' + $('#regist_number').val() + ',' + $('#account_num').val();
			}
			else {
				alert('인증에 실패 하였습니다\n\n정확한 계좌번호로 다시 인증해 주세요\n\n결과코드: ' + data.RCODE + '\n\n에러내용: ' + data.ERRMSG + '\n\n전문번호: ' + data.FB_SEQ);
			}
		}
	});

});

$('#btn_sh_va_regist').click(function() {

	$('#btn_sh_va_regist').attr('disabled', 'disabled');

	var mb_no = document.fmember.mb_no.value;

	$.ajax({
		type: "POST",
		url: "/adm/member/ajax_virtual_account_proc.php",
		data: {
			mb_no:mb_no,
			mode:'new'
		},
		success:function(result) {
			array_result = result.split(':');		// 결과값 배열화

			if(array_result[0]=='SUCCESS') {
				$('#sh_va_info').html(array_result[1]);
			}
			else if(array_result[0]=='ERROR') {
				if(array_result[1]=='LOGIN')                    { $(location).attr('href', '/'); }
				else if(array_result[1]=='NONE_MEMBER')         { $(location).attr('href', '/'); }
				else if(array_result[1]=='EMPTY_COMPANY_INFO')  { alert('사업자 정보(업체명, 사업자등록번호, 대표자명)가 등록 되지 않아, 가상계좌 발급이 불가합니다.'); }
				else if(array_result[1]=='EMPTY_BANK_INFO')     { alert('환급계좌 정보가 등록 되지 않아, 가상계좌 발급이 불가합니다..'); }
				else if(array_result[1]=='EMPTY_JUMINNO')       { alert('주민등록번호가 등록 되지 않아, 가상계좌 발급이 불가합니다.'); }
				else if(array_result[1]=='DUPLICATE_REQUEST')   { alert('이미 신한 가상계좌가 등록되어 있습니다. 재발급은 허용하지 않습니다.'); }
				else if(array_result[1]=='SH_VA_INSUFFICIENCY') { alert('배정 가능한 가상계좌(헬로핀테크용)가 없습니다. 여유 가상계좌를 확보하십시요.'); }
				else { alert(array_result[1]); }
			}
			else { alert(result); }
		},
		error: function () {
			alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.");
		}
	});

	$('#btn_sh_va_regist').removeAttr('disabled');

});

// 주민등록번호 앞자리 다 입력 후 자동으로 뒷자리로 커서 이동
function autoCursor(form) {
	if(fmember.psnl_num1.value.length > 5) {
		fmember.psnl_num2.focus();
		fmember.psnl_num2.select();
	}
}

$('#btn_loaner_vacct_regist').on('click', function() {

	$('#btn_loaner_vacct_regist').attr('disabled', 'disabled');

	var mb_no = document.fmember.mb_no.value;
	var loaner_va_name = $('#loaner_va_name').val();

	$.ajax({
		type: "POST",
		url: "/adm/member/ajax_virtual_account_loaner_proc.php",
		data: {
			mb_no:mb_no,
			loaner_va_name:loaner_va_name,
			mode:'new'
		},
		success:function(result) {
			$('#ajax_return_txt').val(result);
			array_result = result.split(':');		// 결과값 배열화

			if(array_result[0]=='SUCCESS') {
				$('#VAInfo').css('display', 'block');
				$('#sh_va_info').html(array_result[1]);
				alert('대출상환용 가상계좌가 발급되었습니다.');
			}
			else if(array_result[0]=='ERROR') {
				if(array_result[1]=='LOGIN')                    { $(location).attr('href', '/'); }
				else if(array_result[1]=='NONE_MEMBER')         { $(location).attr('href', '/'); }
				else if(array_result[1]=='SH_VA_INSUFFICIENCY') { alert('배정 가능한 가상계좌(헬로크라우드대부용)가 없습니다. 여유 가상계좌를 확보하십시요.'); }
				else { alert(array_result[1]); }
			}
			else { alert(result); }

		},
		error: function () {
			alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요.");
		}
	});

	$('#btn_loaner_vacct_regist').removeAttr('disabled');

});
</script>

<? if($bank_update_target) { ?>
<script>
$('#btn_bankname_change').on('click', function() {
	if( confirm('금융기관에 등록된 고객의 성명과 가상계좌의 표기 예금주명이 변경됩니다.') ) {

		$.ajax({
			type: "POST",
			url: "/adm/member/ajax_shinhan_member_update.php",
			dataType: "json",
			data:{ mb_no:$(this).data('idx') },
			success:function(data) {
				if(data.result=='SUCCESS') {
					alert('금융기관 등록정보 변경 완료.\n\n페이지를 다시 읽어들입니다.');
					location.reload();
				}
				else {
					alert(data.msg); return;
				}
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); return; }
		});

	}
});
</script>
<? } ?>

<?
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>