<?
///////////////////////////////////////////////////////////////////////////////
// 회원등록 - 법인 투자 회원
///////////////////////////////////////////////////////////////////////////////

if($MB['mb_no']) {

	if( $MB['member_group'] != 'F' ) { msg_go("투자 회원이 아닙니다!"); }
	if( $MB['member_type'] != '2' )  { msg_go("법인 회원이 아닙니다!"); }

	$member_group = $MB['member_group'];
	$member_type  = $MB['member_type'];


	$va_info1 = "";
	$va_info1.= ($MB['va_bank_code'] && $MB['virtual_account']) ? $BANK[$MB['va_bank_code']].' '.$MB['virtual_account'] : '';

	$va_info2 = "";
	$va_info2.= ($MB['va_bank_code2'] && $MB['virtual_account2']) ? $BANK[$MB['va_bank_code2']].' '.$MB['virtual_account2'] : '';

	if($MB['va_bank_code2'] && $MB['virtual_account2']) {
		$mb_co_name_x = preg_replace("/( )/", "", $MB['mb_co_name']);
		if(!preg_match("/".$mb_co_name_x."/", $MB['va_private_name2'])) $bank_update_target = 1;
	}

	//$MB['auto_inv_conf'] = get_auto_inv_conf($MB['mb_no']);

	$print_mb_co_reg_num = ($MB['mb_co_reg_num']) ? substr($MB['mb_co_reg_num'],0,3) . '-' . substr($MB['mb_co_reg_num'],3,2) . '-' . substr($MB['mb_co_reg_num'],5) : '';
	$print_corp_num      = ($MB['corp_num']) ? substr($MB['corp_num'],0,6) . '-' . substr($MB['corp_num'],6) : '';

	$print_corp_phone    = $MB['corp_phone'];
	$print_account_num   = $MB['account_num'];

/*
	echo "corp_phone : " . $MB['corp_phone'] . "(".masterDecrypt($MB['corp_phone']).")<br>\n";
	echo "corp_phone_ineb : " . $MB['corp_phone_ineb'] . "(".DGuardDecrypt($MB['corp_phone_ineb']).")<br>\n";
	echo "account_num : " . $MB['account_num'] . "(".masterDecrypt($MB['account_num']).")<br>\n";
	echo "account_num_ineb : " . $MB['account_num_ineb'] . "(".DGuardDecrypt($MB['account_num_ineb']).")<br>\n";
*/

}


add_javascript(G5_POSTCODE_JS, 0);    //다음 주소 js

?>

<script>
// 폼 리셋
function fmember_reset() {
	$('#fmember').reset();
}
</script>

<style>
.roundbox { width:100%;list-style:none;padding:9px;clear:both; display:inline-block; border:1px dotted #555; border-radius:5px;background-color:#FDFECB; }

th.tit { text-align:center; background-color:#F8F8EF; }

ul.filezone { float:left; display:inline-block; margin-left:10px; padding-top:4px; width:373px; height:58px; border:1px solid #DDD; border-radius:3px; }
ul.filezone li:nth-child(1) { float:left; width:120px; height:52px; text-align:center; }
ul.filezone li:nth-child(2) { float:left; width:250px; }
ul.filezone li:nth-child(3) { float:left; width:250px; margin:4px 0; font-size:12px; }
</style>

<div class="tbl_frm01 tbl_wrap">
	<table style="width:1620px;">
		<caption><?=$html_title?></caption>
		<colgroup>
			<col style="width:%;">
			<col style="width:250px">
		</colgroup>
		<tr>
			<td>

				<form name="fmember" id="fmember" method="post" target="axFrame" action="/adm/member/member_form_corp.update.php" enctype="multipart/form-data">
				<input type="hidden" name="mode"  value="<?=$mode?>">
				<input type="hidden" name="mb_no" value="<?=$MB['mb_no']?>">

				<table style="border-top:2px solid #3c5b9b;">
					<colgroup>
						<col style="width:13%">
						<col style="width:37%">
						<col style="width:13%">
						<col style="width:37%">
					</colgroup>
					<tbody>
						<tr>
							<th scope="row" class="tit"><label for="member_group">회원그룹</label></th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><label class="radio-inline"><input type="radio" name="member_group" id="member_group" value="F" checked> 투자회원</label></li>
								</ul>
							</td>
							<th scope="row" class="tit"><label for="member_type">회원구분</label></th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><label class="radio-inline"><input type="radio" name="member_type" id="member_type" value="2" checked> 법인회원</label></li>
								</ul>
							</td>
						</tr>

						<tr height="42">
							<th scope="row" class="tit"><label>아이디</label></th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li style="float:left;"><input type="text" name="mb_id" id="mb_id" value="<?=$MB['mb_id']?>" title="아이디를 입력해주세요." <?=($MB['mb_id'])?'disabled':''?> class="form-control input-sm required" style="width:200px"></li>
									<? if($mode=='new') { ?>
									<li style="float:left;margin-left:8px;"><a id="confirm_id" class="btn btn-default" class="btn btn-default">중복체크</a></li>
									<li style="float:left;margin-left:8px;"><span id="mb_id_error" style="margin-left:30px;font-size:12px;"></span></li>
									<? } ?>
								</ul>
							</td>
							<th scope="row" class="tit"><label for="mb_password">패스워드</label></th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><input type="password" name="mb_password" id="mb_password" size="20" value="" title="비밀번호를 입력해주세요." class="form-control input-sm <?=(!$MB['mb_no'])?' required' : ''?>" style="width:200px"></li>
									<? if($mode=='edit'){ ?><li><span class="sms_error" style='font-size:12px;'><font color="#000000">※ 입력할 경우, 입력된 패스워드로 변경 됩니다.</font></span></li><? } ?>
								</ul>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit"><label for="mb_co_reg_num">사업자정보</label></th>
							<td colspan="3">
								<div class="roundbox" style="width:100%">

									<ul class="col-sm-10 list-inline" style="width:100%;">
										<li style="float:left;width:120px"><label>법인명</label></li>
										<li style="float:left;"><input type="text" class="frm_input input-sm required" name="mb_co_name" id="mb_co_name" size="20" value="<?=$MB['mb_co_name']?>"><li>
										<li style="float:left;margin-left:100px;width:120px"><label>법인영문명</label></li>
										<li style="float:left;"><input type="text" class="frm_input input-sm" name="mb_co_name_eng" id="mb_co_name_eng" size="20" value="<?=$MB['mb_co_name_eng']?>"></li>
										<li style="float:left;margin-left:30px;padding:0;">
											<label><input type="checkbox" id="corp_noneprofit" name="corp_noneprofit" value="1" <?=($MB['corp_noneprofit']=='1')?'checked':'';?>> 비영리법인</label> &nbsp;
											<label><input type="checkbox" id="corp_forigner" name="corp_forigner" value="1" <?=($MB['corp_forigner']=='1')?'checked':'';?>> 해외법인</label>
										</li>
									</ul>

									<ul class="col-sm-10 list-inline" style="width:100%;">
										<li style="float:left;width:120px"><label>사업자등록번호</label></li>
										<li style="float:left;"><input type="text" class="frm_input input-sm required" name="mb_co_reg_num" id="mb_co_reg_num" size="20" maxlength="12" value="<?=$print_mb_co_reg_num?>"></li>
										<li style="float:left;margin-left:100px;width:120px"><label>대표자명</label></li>
										<li style="float:left;"><input type="text" class="frm_input input-sm required" id="mb_co_owner" name="mb_co_owner" size="20" value="<?=$MB['mb_co_owner']?>"></li>
									</ul>

									<ul class="col-sm-10 list-inline" style="width:100%;">
										<li style="float:left;width:120px"><label>법인등록번호</label></li>
										<li style="float:left;"><input type="text" class="frm_input input-sm required" id="corp_num" name="corp_num" size="20" maxlength="14" value="<?=$print_corp_num?>"></li>
										<li style="float:left;margin-left:100px;width:120px;"><label>설립일</label></li>
										<li style="float:left;"><input type="text" class="frm_input input-sm datepicker required" id="corp_rdate" name="corp_rdate" size="20" value="<?=$MB['corp_rdate']?>" autocomplete="off"></li>
									</ul>

									<ul class="col-sm-10 list-inline" style="width:100%;">
										<li style="float:left;width:120px;"><label>법인연락처</label></li>
										<li style="float:left;"><input type="text" class="frm_input input-sm" id="corp_phone" name="corp_phone"  size="20" value="<?=$MB['corp_phone']?>" style="color:#DDD" onKeyup="onlyDigit(this);"></li>
									</ul>

									<ul class="col-sm-10 list-inline" style="width:100%;">
										<li style="float:left;width:120px;height:120px;"><label>사업장소재지</label></li>

										<li style="float:left;width:100px;">우편번호</li>
										<li style="float:left;width:80%;padding-bottom:2px;">
											<input type="text" name="zip_num" id="zip_num" value="<?=$MB['zip_num']?>" onClick="win_zip('fmember', 'zip_num', 'mb_addr1', 'mb_addr2', 'mb_addr3', 'mb_addr_jibeon');" maxlength="6" readonly class="frm_input input-sm">
											<button type="button" onClick="win_zip('fmember', 'zip_num', 'mb_addr1', 'mb_addr2', 'mb_addr3', 'mb_addr_jibeon');" class="btn btn-sm btn-default">주소검색</button>
										</li>

										<li style="float:left;width:100px">도로명주소</li>
										<li style="float:left;width:80%;padding-bottom:2px;"><input type="text" name="mb_addr1" id="mb_addr1" value="<?=$MB['mb_addr1']?>" readonly class="frm_input input-sm" style="width:350px;"></li>

										<li style="float:left;width:100px;"><span style="color:#AAA">지번주소</span></li>
										<li style="float:left;width:80%;padding-bottom:2px;"><input type="text" name="mb_addr_jibeon" id="mb_addr_jibeon" value="<?=$MB['mb_addr_jibeon']?>" readonly class="frm_input input-sm" style="width:350px;"></li>

										<li style="float:left;width:100px">이하상세주소</li>
										<li style="float:left;"><input type="text" name="mb_addr2" id="mb_addr2" value="<?=$MB['mb_addr2']?>" class="frm_input input-sm" style="width:350px;"></li>

										<li style="float:left;margin-left:20px; width:50px;"><span style="color:#AAA">메모</span></li>
										<li style="float:left;"><input type="text" name="mb_addr3" id="mb_addr3" value="<?=$MB['mb_addr3']?>" class="frm_input input-sm" style="width:350px;"></li>
									</ul>

<!-- ▼ 첨부파일 시작 //-->
									<ul class="list-inline filezone">
										<li><label>사업자등록증</label></li>
										<li><input type="file" name="business_license" id="business_license" style="font-size:12px"></li>
										<li>
											<? if($MB['business_license']) { ?>
											<a href="/adm/member/fileView.php?mb_no=<?=$MB['mb_no']?>&gbn=business_license" target="_blank" class="btn btn-success" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>
											<label class="checkbox-inline"><input type="checkbox" id="del_business_license" name="del_business_license" value="Y"> 삭제</label>
											<input type="hidden" id="org_business_license" name="org_business_license" value="<?=$MB['business_license']?>">
											<? } ?>
										</li>
									</ul>

									<ul class="list-inline filezone">
										<li><label>법인통장사본</label></li>
										<li><input type="file" name="bankbook" id="bankbook" style="font-size:12px"></li>
										<li>
											<? if($MB['bankbook']){ ?>
											<a href="/adm/member/fileView.php?mb_no=<?=$MB['mb_no']?>&gbn=bankbook" target="_blank" class="btn btn-md btn-success" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>
											<label class="checkbox-inline"><input type="checkbox" id="del_bankbook" name="del_bankbook" value="Y"> 삭제</label>
											<input type="hidden" id="org_bankbook" name="org_bankbook" value="<?=$MB['bankbook']?>">
											<? } ?>
										</li>
									</ul>

									<ul class="list-inline filezone">
										<li><label>법인등기부등본</label></li>
										<li><input type="file" name="corp_deungibu_doc" id="corp_deungibu_doc" style="font-size:12px"></li>
										<li>
											<? if($MB['corp_deungibu_doc']){ ?>
											<a href="/adm/member/fileView.php?mb_no=<?=$MB['mb_no']?>&gbn=corp_deungibu_doc" target="_blank" class="btn btn-md btn-success" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>
											<label class="checkbox-inline"><input type="checkbox" id="del_corp_deungibu_doc" name="del_corp_deungibu_doc" value="Y"> 삭제</label>
											<input type="hidden" id="org_corp_deungibu_doc" name="org_corp_deungibu_doc" value="<?=$MB['corp_deungibu_doc']?>">
											<? } ?>
										</li>
									</ul>

									<ul class="list-inline filezone">
										<li><label>대표자신분증</label></li>
										<li><input type="file" name="corp_owner_id_card_doc" id="corp_owner_id_card_doc" style="font-size:12px"></li>
										<li>
											<? if($MB['corp_owner_id_card_doc']){ ?>
											<a href="/adm/member/fileView.php?mb_no=<?=$MB['mb_no']?>&gbn=corp_owner_id_card_doc" target="_blank" class="btn btn-md btn-success" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>
											<label class="checkbox-inline"><input type="checkbox" id="del_corp_owner_id_card_doc" name="del_corp_owner_id_card_doc" value="Y"> 삭제</label>
											<input type="hidden" id="org_corp_owner_id_card_doc" name="org_corp_owner_id_card_doc" value="<?=$MB['corp_owner_id_card_doc']?>">
											<? } ?>
										</li>
									</ul>

									<ul class="list-inline filezone">
										<li><label>실소유자<br/>정보양식</label></li>
										<li><input type="file" name="corp_owner_quest_doc" id="corp_owner_quest_doc" style="font-size:12px"></li>
										<li>
											<? if($MB['corp_owner_quest_doc']){ ?>
											<a href="/adm/member/fileView.php?mb_no=<?=$MB['mb_no']?>&gbn=corp_owner_quest_doc" target="_blank" class="btn btn-md btn-success" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>
											<label class="checkbox-inline"><input type="checkbox" id="del_corp_owner_quest_doc" name="del_corp_owner_quest_doc" value="Y"> 삭제</label>
											<input type="hidden" id="org_corp_owner_quest_doc" name="org_corp_owner_quest_doc" value="<?=$MB['corp_owner_quest_doc']?>">
											<? } ?>
										</li>
									</ul>

									<ul class="list-inline filezone">
										<li><label>주주명부</label></li>
										<li><input type="file" name="corp_stockholders_doc" id="corp_stockholders_doc" style="font-size:12px"></li>
										<li>
											<? if($MB['corp_stockholders_doc']){ ?>
											<a href="/adm/member/fileView.php?mb_no=<?=$MB['mb_no']?>&gbn=corp_stockholders_doc" target="_blank" class="btn btn-md btn-success" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>
											<label class="checkbox-inline"><input type="checkbox" id="del_corp_stockholders_doc" name="del_corp_stockholders_doc" value="Y"> 삭제</label>
											<input type="hidden" id="org_corp_stockholders_doc" name="org_corp_stockholders_doc" value="<?=$MB['corp_stockholders_doc']?>">
											<? } ?>
										</li>
									</ul>

									<ul class="list-inline filezone">
										<li><label>법인인감증명서</label></li>
										<li><input type="file" name="corp_ingam_doc" id="corp_ingam_doc" style="font-size:12px"></li>
										<li>
											<? if($MB['corp_ingam_doc']){ ?>
											<a href="/adm/member/fileView.php?mb_no=<?=$MB['mb_no']?>&gbn=corp_ingam_doc" target="_blank" class="btn btn-md btn-success" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>
											<label class="checkbox-inline"><input type="checkbox" id="del_corp_ingam_doc" name="del_corp_ingam_doc" value="Y"> 삭제</label>
											<input type="hidden" id="org_corp_ingam_doc" name="org_corp_ingam_doc" value="<?=$MB['corp_ingam_doc']?>">
											<? } ?>
										</li>
									</ul>

									<ul class="list-inline filezone">
										<li><label>비영리단체정관</label></li>
										<li><input type="file" name="corp_noneprofit_policy_doc" id="corp_noneprofit_policy_doc" style="font-size:12px"></li>
										<li>
											<? if($MB['corp_noneprofit_policy_doc']){ ?>
											<a href="/adm/member/fileView.php?mb_no=<?=$MB['mb_no']?>&gbn=corp_noneprofit_policy_doc" target="_blank" class="btn btn-md btn-success" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>
											<label class="checkbox-inline"><input type="checkbox" id="del_corp_noneprofit_policy_doc" name="del_corp_noneprofit_policy_doc" value="Y"> 삭제</label>
											<input type="hidden" id="org_corp_noneprofit_policy_doc" name="org_corp_noneprofit_policy_doc" value="<?=$MB['corp_noneprofit_policy_doc']?>">
											<? } ?>
										</li>
									</ul>

									<ul class="list-inline filezone">
										<li><label>기타사용자<br/>첨부파일</label></li>
										<li><input type="file" name="identify_zip_file" id="identify_zip_file" style="font-size:12px"></li>
										<li>
											<? if($MB['identify_zip_file']){ ?>
											<a href="/adm/member/fileView.php?mb_no=<?=$MB['mb_no']?>&gbn=identify_zip_file" target="_blank" class="btn btn-md btn-success" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>
											<label class="checkbox-inline"><input type="checkbox" id="del_identify_zip_file" name="del_identify_zip_file" value="Y"> 삭제</label>
											<input type="hidden" id="org_identify_zip_file" name="org_identify_zip_file" value="<?=$MB['identify_zip_file']?>">
											<? } ?>
										</li>
									</ul>

									<ul class="list-inline filezone" style="border:1px solid #FFCC99">
										<li><label class="checkbox-inline"><input type="checkbox" name="is_creditor" id="is_creditor" value="Y" <?=($MB['is_creditor']=='Y')?'checked':''?>> <strong>대부업<br/>대부업등록증</strong></label></li>
										<li><input type="file" name="loan_co_license" id="loan_co_license" style="font-size:12px"></li>
										<li>
											<? if($MB['loan_co_license']) {	?>
											<a href="/adm/member/fileView.php?mb_no=<?=$MB['mb_no']?>&gbn=loan_co_license" target="_blank" class="btn btn-md btn-success" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>
											<label class="checkbox-inline"><input type="checkbox" id="del_loan_co_license" name="del_loan_co_license" value="Y"> 삭제</label>
											<input type="hidden" id="org_loan_co_license" name="org_loan_co_license" value="<?=$MB['loan_co_license']?>">
											<? } ?>
										</li>
									</ul>
<!-- ▼ 첨부파일 종료 //-->

									<ul class="list-inline filezone">
										<li style="width:180px;"><label class="checkbox-inline"><input type="checkbox" id="all_doc_check_yn" name="all_doc_check_yn" value="Y" <?=($MB['all_doc_check_yn']=='Y')?'checked':''?>>제출서류검수완료</label></li>
									</ul>
								</div>

								<div style="text-align:center;">
									<button type="button" class="btn btn-sm btn-warning" onClick="alert('준비중');">법인정보 별도저장</button>
								</div>

							</td>
						</tr>

						<tr>
							<th scope="row" class="tit"><label for="mb_name">담당자명</label></th>
							<td colspan="3">
								<ul class="list-inline" style="margin:0">
									<li><input type="text" name="mb_name" id="mb_name" size="20" value="<?=$MB['mb_name']?>" title="담당자명을 입력해주세요." class="form-control input-sm required" style="width:200px"></li>
									<li>
										<select id="corp_officer_div" name="corp_officer_div" class="form-control input-sm required">
											<option value="">::법인과의 관계::</option>
											<option value="1" <?=($MB['corp_officer_div']=='1')?'selected':'';?>>대표자</option>
											<option value="2" <?=($MB['corp_officer_div']=='2')?'selected':'';?>>소속직원</option>
										</select>
									</li>
								</ul>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit"><label for="mb_hp">휴대폰 번호</label></th>
							<td><input type="text" name="mb_hp" id="mb_hp" size="20" value="<?=$MB['mb_hp']?>" title="핸드폰 번호를 입력해주세요." onKeyup="onlyDigit(this);" class="form-control input-sm required" style="color:#DDD;width:200px"></td>
							<th scope="row" class="tit"><label for="mb_email">이메일</label></th>
							<td><input type="text" name="mb_email" id="mb_email" size="20" value="<?=$MB['mb_email']?>" title="이메일을 입력해주세요." class="form-control input-sm email" style="width:200px"></td>
						</tr>

						<tr>
							<th scope="row" class="tit"><label>특별구분</label></th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><label class="checkbox-inline"><input type="checkbox" name="mb_10" value="1" <?=($MB['mb_10'])?'checked':''?>> 헬로핀테크 임직원</label></li><br/>
									<li><label class="checkbox-inline"><input type="checkbox" name="is_invest_manager" value="1" <?=($MB['is_invest_manager'])?'checked':''?>> 자산운용사</label></li><br/>
									<li><label class="checkbox-inline"><input type="checkbox" name="is_sbiz_owner" value="1" <?=($MB['is_sbiz_owner'])?'checked':''?>> 자동투자선순위대상자</label></li><br/>
									<li><label class="checkbox-inline"><input type="checkbox" name="remit_fee" value="1" <?=($MB['remit_fee'])?'checked':''?>> 플랫폼수수료면제</label></li>
									<li style="padding-left:10px;">수수료면제적용일</li>
									<li><input type="text" class="form-control input-sm datepicker" id="remit_fee_sdate" name="remit_fee_sdate" value="<?=$MB['remit_fee_sdate']?>" style="width:100px; text-align:center;" autocomplete="off"></li>
								</ul>
							</td>

							<th scope="row" class="tit"><label for="mb_mailling">수신동의</label></th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><label class="checkbox-inline"><input type="checkbox" name="invested_mailling" id="invested_mailling" value="1" <? if($MB['invested_mailling']=='1'){ echo 'checked'; }?>> 투자설명서 발급 동의 <span style="font-size:12px;">(정상투자 실행시 관련 내용을 전자우편으로 고지함)</span></label></li><br/>
									<li><label class="checkbox-inline"><input type="checkbox" name="mb_mailling" id="mb_mailling" value="1" <? if($MB['mb_mailling']=='1'){ echo 'checked'; }?>> 이메일 수신 동의</label></li><br/>
									<li><label class="checkbox-inline"><input type="checkbox" name="mb_sms" id="mb_sms" value="1" <? if($MB['mb_sms']=='1'){ echo 'checked'; }?>> SMS 수신 동의</label></li>
								</ul>
							</td>
						</tr>

						<tr>
							<th rowspan="2" scope="row" class="tit"><label>본인계좌</label></th>
							<td rowspan="2">
								<input type="hidden" id="private_yn" name="private_yn" alt="계좌인증플래그">
								<input type="hidden" id="bank_name" name="bank_name" value="<?=$MB['bank_name']?>" alt="은행명">

								<ul class="col-sm-10 list-inline" style="width:100%; margin:0;padding:0;">
									<li style="float:left;width:120px">계좌구분</li>
									<li style="float:left;width:75%;padding-bottom:8px;">
										<!--<label class="radio-inline"><input type="radio" name="strGbn" value="1" <?=($member_type=='1')?'checked':''?>> 개인계좌</label>-->
										<label class="radio-inline"><input type="radio" name="strGbn" value="2" <?=($member_type=='2')?'checked':''?>> 법인계좌</label>
									</li>

									<li style="float:left;width:120px">은행</li>
									<li style="float:left;width:75%;padding-bottom:2px;">
										<select name="bank_code" id="bank_code" class="form-control input-sm" style="width:200px;">
											<option value="">:: 은행선택 ::</option>
											<?
											$BANK_KEYS = array_keys($BANK);
											for($i=0; $i<count($BANK); $i++) {
												$selected = ($BANK_KEYS[$i]==sprintf("%03d", $MB["bank_code"])) ? 'selected' : '';
												echo "<option value='".$BANK_KEYS[$i]."' $selected>".$BANK[$BANK_KEYS[$i]]."</option>\n";
											}
											?>
										</select>
									</li>

									<li style="float:left;width:120px">계좌번호</li>
									<li style="float:left;width:75%;padding-bottom:2px;"><input type="text" name="account_num" id="account_num" value="<?=$print_account_num?>" title="계좌번호를 입력해주세요." onKeyup="onlyDigit(this);" class="form-control input-sm" style="color:#DDD;width:200px;"></li>

									<li style="float:left;width:120px">예금주</li>
									<li style="float:left;width:75%;padding-bottom:2px;"><input type="text" name="bank_private_name" id="bank_private_name" value="<?=$MB['bank_private_name']?>" title="예금주를 입력해주세요." class="form-control input-sm" style="width:200px;"></li>

									<li style="float:left;width:120px"><span style="color:#AAA">부기명</span></li>
									<li style="float:left;width:75%;padding-bottom:2px;"><input type="text" name="bank_private_name_sub" id="bank_private_name_sub" value="<?=$MB['bank_private_name_sub']?>" title="부기명을 입력해주세요." class="form-control input-sm" style="width:200px;"></li>

									<li style="float:left;width:75%;margin:2px 0 10px 120px;"><span style="color:#AAA;font-size:12px">※ 부기명 : 계좌예금주명상의 괄호내에 표기되는 보조표기명</span></li>
									<li style="float:left;width:75%;margin:0 0 4px 120px;"><span id="btn_bank_account_auth" class="btn btn-sm btn-danger" style="cursor:pointer;">계좌확인 (신한은행 전문)</span></li>

									<? if($bank_update_target) { ?>
									<!--<li style="float:left;width:75%;margin-left:120px;"><span id="btn_bankname_change" data-idx="<?=$MB['mb_no']?>" class="btn btn-sm btn-danger" style="cursor:pointer;">신한은행 등록정보 변경</span></li>-->
									<? } ?>

								</ul>
							</td>

							<th scope="row" class="tit"><label>가상계좌</label></th>
							<td style="line-height:24px">
								<ul class="col-sm-10 list-inline">
									<li style="float:left;width:80px"><label>신한가상</label></li>
									<li style="float:left;width:300px" id="sh_va_info"><?=$va_info2;?></li>
									<? if(!$va_info2) { ?><li style="float:left;"><button type="button" id="btn_sh_va_regist" class="btn btn-md btn-danger" style="height:22px;line-height:18px;font-size:12px;cursor:pointer;padding-top:0">발급받기</button></li><? } ?>
								</ul>
								<? if($va_info1) { ?>
								<ul class="col-sm-10 list-inline" style="color:#ddd">
									<li style="float:left;width:80px"><label>세틀뱅크</label></li>
									<li style="float:left;width:300px"><?=$va_info1?></li>
								</ul>
								<? } ?>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit"><label>환급계좌선택</label></th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><label class="radio-inline"><input type="radio" name="receive_method" value="1" <?=($MB['receive_method']=='1')?'checked':''?> <?=($MB['bank_code'] && $MB['bank_private_name'] && $MB['account_num'])?'checked':'disabled'?>> 환급계좌</label></li>
									<li><label class="radio-inline"><input type="radio" name="receive_method" value="2" <?=($MB['receive_method']=='2')?'checked':''?>> 예치금</label></li>
								</ul>
							</td>
						</tr>

						<!--
						<tr>
							<th scope="row" class="tit"><label>자동투자 설정</label></th>
							<td>
								<? if (count($MB['auto_inv_conf'])>0) { ?>
								<ul class="col-sm-10 list-inline">
									<li style="float:left;width:100px;text-align:right"><?=number_format($MB['auto_inv_conf'][0]["setup_amount"])?> 원</li>
									<li style="float:left;width:250px;text-align:left;width:560px;"><label for="mb_addr1">
									<?
									for ($mm=0 ; $mm<count($MB['auto_inv_conf']) ; $mm++) {
									//echo $MB['auto_inv_conf'][$mm]["grp_title"]." ".number_format($MB['auto_inv_conf'][$mm]["setup_amount"])." 원<br/>";
										if ($mm<>0) echo " , ";
										echo $MB['auto_inv_conf'][$mm]["grp_title"];
									}
									?>
									</label></li>
								</ul>
								<? } ?>
							</td>
						</tr>
						-->
					</tbody>
				</table>

				<div class="text-center" style="margin-top:10px;">
					<button type="button" onClick="fmember_reset();" class="btn btn-md btn-default" style="width:150px">폼초기화</button>
					<input type="submit" class="btn btn-md btn-warning" value="<?=($mode=='edit')?'수정':'등록';?>" onClick="if(!confirm('<?=($mode=='edit')?'수정':'등록';?> 하시겠습니까?')){ return false; }" style="width:150px">
					<? if($MB['member_group']=='F' && $MB['virtual_account2']){ ?><button type="button" id="shinhan_data_update" class="btn btn-md btn-danger" style="width:150px">기관정보수정</button><? } ?>
					<? if($MB['mb_no']) { ?><button type="button" onClick="bankRegistInfoPrint(<?=$MB['mb_no']?>)" class="btn btn-md btn-default" style="width:150px">기관등록정보</button><? } ?>
				</div>
				</form> <!-- form end 'fmember' //-->

				<br/>

<? if($mode=='edit') { ?>
				<h3>관리자 메모</h3>
				<table style="border-top:2px solid #3c5b9b;">
					<tbody>
						<tr>
							<td>
								<textarea id="mb_memo" class="form-control input-sm" style="height:150px;"><?=$MB['mb_memo']?></textarea>
							</td>
						</tr>
					</tbody>
				</table>
				<div class="text-center" style="margin-top:10px;">
					<button type="button" id="memo_resist_button" class="btn btn-md btn-warning" style="width:150px">메모등록</button>
				</div>
				<script>
				$('#memo_resist_button').click(function() {
					if( confirm('메모를 등록 하시겠습니까?') ) {
						var memo = ( $('#mb_memo').val()=='' ) ? '' : $('#mb_memo').val();

						$.ajax({
							url : "/adm/member/ajax_member_memo_update.php",
							type: "POST",
							data : {
								'mb_no' : '<?=$MB['mb_no']?>',
								'mb_memo' : memo
							},
							dataType : 'json',
							success: function(data) { alert(data.msg); },
							beforeSend: function() { loading('on'); },
							complete: function() { loading('off'); },
							error: function(e) { return; }
						});
					}
				});
				</script>
				<br/>
<? } ?>

<?
if($mode=='edit') {
	include('member_form_aml_corp.php');
}
?>

			</td>
			<td valign="top">

				<!--// SMS문자발송 //-------------------------------------->
				<div style="display:inline-block;position:fixed;top;10px;width:245px;height:550px;border:1px solid #aaa;background:#fafafa">
					<iframe id="sms_frame" name="sms_frame" src="/adm/sms_sender/sms.form.php?to_hp=<?=$MB['mb_hp']?>" frameborder="0" scrolling="no" style="width:100%;height:100%;"></iframe>
				</div>
				<!--// SMS문자발송 //-------------------------------------->

			</td>
		</tr>
	</table>

</div>

<script>
// 기관정보수정 버튼액션
$('#shinhan_data_update').click(function() {
	if( confirm('금융기관측 회원정보를 업데이트 하시겠습니까?\n\n'
	          + '금융기관측 등록항목:\n'
						+ '  성명, 업체명, 대표자명, 생년월일, 사업자번호, 휴대폰번호,\n'
						+ '  상환계좌번호, 예치금입금용 가상계좌번호') )
	{
		$.ajax({
			url : "ajax_shinhan_update.php",
			type: "POST",
			data : {'mb_no':'<?=$MB['mb_no']?>'},
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


$('#bank_code').on('change', function() {
	var frm = document.fmember;
	var i = frm.bank_code.options.selectedIndex
	frm.bank_name.value = frm.bank_code.options[i].text;
});


// 계좌확인 (신한은행 전문) 버튼액션
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
				alert('정상계좌 확인완료');
				str_account = $('#bank_private_name').val() + ',' + $('#regist_number').val() + ',' + $('#regist_number').val() + ',' + $('#account_num').val();
			}
			else {
				alert('계좌확인 실패!\n\n정확한 계좌번호로 다시 인증해 주세요\n\n결과코드: ' + data.RCODE + '\n\n에러내용: ' + data.ERRMSG + '\n\n전문번호: ' + data.FB_SEQ);
			}
		}
	});
});

// 신한가상계좌발급 버튼액션
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
				alert('투자자 예치금 입금용 가상계좌가 발급되었습니다.');
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

function bankRegistInfoPrint(mb_no) {
	if(mb_no) {
		$.ajax({
			type: "POST",
			url: "/adm/member/ajax_bankRegistInfo.php",
			dataType: "json",
			data: {mb_no:mb_no},
			success:function(data) {
				if(data.result=='SUCCESS') {
					alert(data.message);
				}
				else {
					console.log();
				}
			},
			beforeSend: function() { loading('on'); },
			complete: function() { loading('off'); },
			error: function () { alert("통신 에러입니다. 잠시 후 다시 시도하여 주십시요."); return; }
		});
	}
}
</script>

<? if($bank_update_target) { ?>
<script>
// 신한은행 등록정보 변경 버튼액션
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