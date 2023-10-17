<?
///////////////////////////////////////////////////////////////////////////////
// 회원등록 - 개인 대출 회원
///////////////////////////////////////////////////////////////////////////////

if($MB['mb_no']) {

	if( $MB['member_group'] != 'L' ) { msg_go("대출 회원이 아닙니다!"); }
	if( $MB['member_type'] != '1' )  { msg_go("개인 회원이 아닙니다!"); }

	$member_group = $MB['member_group'];
	$member_type  = $MB['member_type'];
	$regist_number = ($_SESSION['ss_accounting_admin']) ? getJumin($MB['mb_no']) : "";

	// 차주 테이블 SELECT
	$CHAJU = sql_fetch("SELECT * FROM cf_chaju WHERE mb_no = '".$MB['mb_no']."'");

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

				<form name="fmember" id="fmember" method="post" target="axFrame" action="/adm/member/member_form_indi.update.php" enctype="multipart/form-data">
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
									<li><label class="radio-inline"><input type="radio" name="member_group" id="member_group" value="L" checked> 대출회원</label></li>
								</ul>
							</td>
							<th scope="row" class="tit"><label for="member_type">회원구분</label></th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li style="float:left;"><label class="radio-inline"><input type="radio" name="member_type" id="member_type" value="1" checked> 개인회원</label></li>
									<? if($mode=='new'){ ?><li style="margin-left:10px;"><label class="checkbox-inline"><input type="checkbox" name="mb_level" id="mb_level" value="1"> 정회원 자격부여</label></li><? } ?>
								</ul>
							</td>
						</tr>

						<tr height="42">
							<th scope="row" class="tit"><label>아이디</label></th>
							<td>
								<ul style="width:100%;list-style:none;padding:0;clear:both;">
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
							<th scope="row" class="tit"><label for="mb_name">성명</label></th>
							<td colspan="3">
								<ul class="col-sm-10 list-inline" style="margin:0; padding:0; width:100%">
									<li style="float:left; padding:0;"><input type="text" name="mb_name" id="mb_name" size="20" value="<?=$MB['mb_name']?>" title="성명을 입력해주세요." class="form-control input-sm required" style="width:200px"></li>
									<li style="float:left; padding:0; margin-left:20px"><input type="text" name="eng_last_nm" id="eng_last_nm" placeholder="영문명:성" size="20" value="<?=$MB['eng_last_nm']?>" class="form-control input-sm" style="ime-mode:disabled;text-transform:uppercase; width:200px"></li>
									<li style="float:left; padding:0; margin-left:4px"><input type="text" name="eng_first_nm" id="eng_first_nm" placeholder="영문명:이름" size="20" value="<?=$MB['eng_first_nm']?>" class="form-control input-sm" style="ime-mode:disabled;text-transform:uppercase; width:200px"></li>
								</ul>
							</td>
						<tr>
						<tr>
							<th rowspan="2" scope="row" class="tit"><label for="regist_number">주민등록번호</label></th>
							<td rowspan="2">
								<input type="text" name="regist_number" id="regist_number" maxlength="13" value="<?=getJumin($MB['mb_no']);?>" onKeyup="onlyDigit(this);" class="form-control input-sm" style="color:#DDD;width:200px; display:inline;">
							<? if ($MB["mb_ci"]) { ?>
								<button type="button" id="ci_btn" onClick="get_ci_this('<?=$MB['mb_no']?>');" class="btn btn-md btn-default" style="width:100px; margin-left:10px;">CI 조회</button>
							<? } else { ?>
								<button type="button" id="ci_btn" onClick="get_ci_this('<?=$MB['mb_no']?>');" class="btn btn-md btn-warning" style="width:100px; margin-left:10px;">CI 요청</button>
							<? } ?>
								<ul class="roundbox" style="margin-top:10px;">
									<li style="float:left;width:120px"><label>신분증</label></li>
									<li style="float:left;">
										<input type="file" name="id_card" id="id_card" size="50">
										<? if($MB['id_card']) { ?>
										<div style="line-height:30px;">
											<a href="/data/member/id_card/<?=$MB['id_card']?>" alt="<?=$MB['id_card']?>" class="btn btn-md btn-warning" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>	&nbsp;&nbsp;
											<label class="checkbox-inline"><input type="checkbox" id="del_id_card" name="del_id_card" value="Y"> 삭제</label>
											<input type="hidden" id="org_id_card" name="org_id_card" value="<?=$MB["id_card"]?>">
										</div>
										<? } ?>
									</li>
								</ul>
							</td>
							<th scope="row" class="tit"><label for="mb_hp">휴대폰번호</label></th>
							<td><input type="text" name="mb_hp" id="mb_hp" size="20" value="<?=$MB['mb_hp']?>" title="핸드폰 번호를 입력해주세요." onKeyup="onlyDigit(this);" class="form-control input-sm required" style="color:#DDD;width:200px"></td>
						</tr>

						<tr>
							<th scope="row" class="tit"><label for="mb_email">이메일</label></th>
							<td><input type="text" name="mb_email" id="mb_email" size="20" value="<?=$MB['mb_email']?>" title="이메일을 입력해주세요." class="form-control input-sm email" style="width:200px"></td>
						</tr>

						<tr>
							<th rowspan="2" scope="row" class="tit"><label>주소</label></th>
							<td rowspan="2">
								<ul class="col-sm-10 list-inline" style="width:100%; margin:0;padding:0;">
									<li style="float:left;width:120px;">우편번호</li>
									<li style="float:left;width:75%;padding-bottom:2px;">
										<input type="text" name="zip_num" id="zip_num" value="<?=$MB['zip_num']?>" onClick="win_zip('fmember', 'zip_num', 'mb_addr1', 'mb_addr2', 'mb_addr3', 'mb_addr_jibeon');" maxlength="6" readonly class="frm_input input-sm">
										<button type="button" onClick="win_zip('fmember', 'zip_num', 'mb_addr1', 'mb_addr2', 'mb_addr3', 'mb_addr_jibeon');" class="btn btn-sm btn-default">주소검색</button>
									</li>
									<li style="float:left;width:120px">도로명주소</li>
									<li style="float:left;width:75%;padding-bottom:2px;"><input type="text" name="mb_addr1" id="mb_addr1" value="<?=$MB['mb_addr1']?>" readonly class="frm_input input-sm" style="width:100%"></li>
									<li style="float:left;width:120px"><span style="color:#AAA">지번주소</span></li>
									<li style="float:left;width:75%;padding-bottom:2px;"><input type="text" name="mb_addr_jibeon" id="mb_addr_jibeon" value="<?=$MB['mb_addr_jibeon']?>" readonly class="frm_input input-sm" style="width:100%"></li>
									<li style="float:left;width:120px">이하상세주소</li>
									<li style="float:left;width:75%;padding-bottom:2px;"><input type="text" name="mb_addr2" id="mb_addr2" value="<?=$MB['mb_addr2']?>" class="frm_input input-sm" style="width:100%"></li>
									<li style="float:left;width:120px"><span style="color:#AAA">메모</span></li>
									<li style="float:left;width:75%;"><input type="text" name="mb_addr3" id="mb_addr3" value="<?=$MB['mb_addr3']?>" class="frm_input input-sm" style="width:100%"></li>
								</ul>
							</td>

							<th scope="row" class="tit"><label>특별구분</label></th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><label class="checkbox-inline"><input type="checkbox" name="mb_10" value="1" <?=($MB['mb_10'])?'checked':''?>> 헬로소속</label></li><br/>
									<!--<li><label class="checkbox-inline"><input type="checkbox" name="is_invest_manager" value="1" <?=($MB['is_invest_manager'])?'checked':''?>> 자산운용사</label></li><br/>-->
									<!--<li><label class="checkbox-inline"><input type="checkbox" name="is_sbiz_owner" value="1" <?=($MB['is_sbiz_owner'])?'checked':''?>> 자동투자선순위대상자</label></li><br/>-->
									<!--<li><label class="checkbox-inline"><input type="checkbox" name="remit_fee" value="1" <?=($MB['remit_fee'])?'checked':''?>> 플랫폼수수료면제</label></li>-->
									<!--<li style="padding-left:10px;">수수료면제적용일</li>-->
									<!--<li><input type="text" class="form-control input-sm datepicker" id="remit_fee_sdate" name="remit_fee_sdate" value="<?=$MB['remit_fee_sdate']?>" style="width:100px; text-align:center;" autocomplete="off"></li>-->
								</ul>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit"><label for="mb_mailling">수신동의</label></th>
							<td>
								<ul class="list-inline" style="margin:0">
									<!--<li><label class="checkbox-inline"><input type="checkbox" name="invested_mailling" id="invested_mailling" value="1" <? if($MB['invested_mailling']=='1'){ echo 'checked'; }?>> 투자설명서 발급 동의 <span style="font-size:12px;">(정상투자 실행시 관련 내용을 전자우편으로 고지함)</span></label></li><br/>-->
									<li><label class="checkbox-inline"><input type="checkbox" name="mb_mailling" id="mb_mailling" value="1" <? if($MB['mb_mailling']=='1'){ echo 'checked'; }?>> 이메일 수신 동의</label></li><br/>
									<li><label class="checkbox-inline"><input type="checkbox" name="mb_sms" id="mb_sms" value="1" <? if($MB['mb_sms']=='1'){ echo 'checked'; }?>> SMS 수신 동의</label></li>
								</ul>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit"><label>본인계좌</label></th>
							<td>
								<input type="hidden" id="private_yn" name="private_yn" alt="계좌인증플래그">
								<input type="hidden" id="bank_name" name="bank_name" value="<?=$MB['bank_name']?>" alt="은행명">

								<ul class="col-sm-10 list-inline" style="width:100%; margin:0;padding:0;">
									<li style="float:left;width:120px">계좌구분</li>
									<li style="float:left;width:75%;padding-bottom:8px;">
										<label class="radio-inline"><input type="radio" name="strGbn" value="1" <?=($member_type=='1')?'checked':''?>> 개인계좌</label>
										<!--<label class="radio-inline"><input type="radio" name="strGbn" value="2" <?=($member_type=='2')?'checked':''?>> 법인계좌</label>-->
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
									<li style="float:left;width:75%;padding-bottom:2px;"><input type="text" name="account_num" id="account_num" value="<?=$MB['account_num']?>" title="계좌번호를 입력해주세요." onKeyup="onlyDigit(this);" class="form-control input-sm" style="color:#DDD;width:200px;"></li>

									<li style="float:left;width:120px">예금주</li>
									<li style="float:left;width:75%;padding-bottom:2px;"><input type="text" name="bank_private_name" id="bank_private_name" value="<?=$MB['bank_private_name']?>" title="예금주를 입력해주세요." class="form-control input-sm" style="width:200px;"></li>

									<li style="float:left;width:120px"><span style="color:#AAA">부기명</span></li>
									<li style="float:left;width:75%;padding-bottom:2px;"><input type="text" name="bank_private_name_sub" id="bank_private_name_sub" value="<?=$MB['bank_private_name_sub']?>" title="부기명을 입력해주세요." class="form-control input-sm" style="width:200px;"></li>

									<li style="float:left;width:75%;margin:2px 0 10px 120px;"><span style="color:#AAA;font-size:12px">※ 부기명 : 계좌예금주명상의 괄호내에 표기되는 보조표기명</span></li>
									<li style="float:left;width:75%;margin-left:120px;"><span id="btn_bank_account_auth" class="btn btn-danger" style="height:32px;line-height:32px;font-size:12px;padding-top:0; cursor:pointer;">계좌확인 (신한은행 전문)</span></li>
								</ul>

								<ul class="roundbox" style="margin-top:10px;">
									<li style="float:left;width:120px"><label>통장사본</label></li>
									<li style="float:left;">
										<input type="file" name="bankbook" id="bankbook" size="50">
										<? if($MB['bankbook']) { ?>
										<div style="line-height:30px;">
											<a href="/mypage/bankbook_download.php?mb_id=<?=$MB['mb_id']?>" alt="<?=$MB['bankbook']?>" class="btn btn-md btn-warning" style="height:22px;line-height:18px;font-size:12px;padding-top:0;">파일보기</a>	&nbsp;&nbsp;
											<label class="checkbox-inline"><input type="checkbox" id="del_bankbook" name="del_bankbook" value="Y"> 삭제</label>
											<input type="hidden" id="org_bankbook" name="org_bankbook" value="<?=$MB["bankbook"]?>">
										</div>
										<? } ?>
									</li>
								</ul>
							</td>

							<th scope="row" class="tit"><label>가상계좌<br>(대출상환용)</label></th>
							<td style="line-height:24px">
<?
if($mode=='edit') {
	// 대출자 상환용 가상계좌

	$sqlx = "
		SELECT
			A.bank_cd, A.acct_no, A.cmf_nm, A.acct_st, A.open_il,
			B.BANK_CODE, B.VR_ACCT_NO, B.CORP_NAME, B.USE_FLAG, B.REF_NO
		FROM
			IB_vact_hellocrowd A
		LEFT JOIN
			KSNET_VR_ACCOUNT B  ON A.acct_no = B.VR_ACCT_NO
		WHERE
			CUST_ID = '".$MB['mb_no']."' AND acct_st = '1'
		ORDER BY
			open_il ASC, acct_st ASC";
	$resx = sql_query($sqlx);
	$rowsx = $resx->num_rows;

	for($i=0,$j=1; $i<$rowsx; $i++,$j++) {

		$VALIST = sql_fetch_array($resx);
		$acct_st = ($VALIST['acct_st']=='9') ? '해지' : '정상';

		echo "<div style='font-size:12px;margin-bottom:8px;'>\n";
		echo "($j) " . $BANK[$VALIST['BANK_CODE']] . " " . $VALIST['VR_ACCT_NO'] . " &nbsp; " . $VALIST['CORP_NAME'] . " <br/>\n";
		echo " &nbsp;&nbsp;&nbsp;\n";
		echo "설정일: " . date('Y-m-d', strtotime($VALIST['open_il'])) . " &nbsp; \n";
		echo "상태: " . $acct_st ."\n";
		if($VALIST['REF_NO']) echo "&nbsp; 참조번호(상품): " . $VALIST['REF_NO'] ."\n";
		echo "</div>\n";

	}
?>
								<ul class="col-sm-10 list-inline" id="VAInfo" style="display:none">
									<li id="sh_va_info">발급결과출력</li>
								</ul>
								<ul class="col-sm-10 list-inline" id="VARequest" style="display:block">
									<li style="padding-left:0"><input type="text" id="loaner_va_name" placeholder="가상계좌예금주명" class="form-control input-sm"></li>
									<li><button type="button" id="btn_loaner_vacct_regist" class="btn btn-md btn-danger" style="height:32px;line-height:32px;font-size:12px;cursor:pointer;padding-top:0">상환용 가상계좌발급</button></li>
								</ul>
<?
}		// end if($mode=='edit')
?>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit"><label for="limit_amt">대출한도금액</label></th>
							<td><input type="text" name="limit_amt" id="limit_amt" value="<?=$CHAJU['limit_amt']?>" class="form-control input-sm chk_number" style="width:200px"></td>
							<th scope="row" class="tit"><label>신용정보</label></th>
							<td>
								<ul class="list-inline" style="width:350px;">
									<li style="float:left;width:20%">신용점수</li>
									<li style="float:left;width:80%;margin-bottom:4px;"><input type="text" name="credit_score" id="credit_score" value="<?=$CHAJU['credit_score']?>" class="form-control input-sm" style="width:200px"></li>
									<li style="float:left;width:20%;">평가회사</li>
									<li style="float:left;width:80%;"><input type="text" name="rating_cp" id="rating_cp" value="<?=$CHAJU['rating_cp']?>" class="form-control input-sm" style="width:200px"></li>
								</ul>
							</td>
						</tr>

					</tbody>
				</table>

				<div class="text-center" style="margin-top:10px;">
					<input type="submit" class="btn btn-md btn-warning" value="<?=($mode=='edit')?'수정':'등록';?>" onClick="if(!confirm('<?=($mode=='edit')?'수정':'등록';?> 하시겠습니까?')){ return false; }" style="width:150px">
					<button type="button" onClick="fmember_reset();" class="btn btn-md btn-default" style="width:150px">폼초기화</button>
					<? if($MB['member_group']=='F' && $MB['virtual_account2']){ ?><button type="button" id="shinhan_data_update" class="btn btn-md btn-danger" style="width:150px">기관정보수정</button><? } ?>
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
	include('member_form_aml_indi.php');
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
<script>
function get_ci_this(member_idx) {
	var ci = get_ci(member_idx);
	if (ci) {
				alert("ci 요청,조회 성공");
				$("#ci_btn").prop('onclick', null);
				$("#ci_btn").html('CI 조회');
				$("#ci_btn").removeClass("btn-warning");
				$("#ci_btn").addClass("btn-default");
	} else {
	}
}
function get_ci(member_idx) {

	var ci="";

	$.ajax({
		url : "/adm/member/get_nice_ci.php",
		type : 'post',
		data : {'member_idx': member_idx},
		dataType : "json",
		async: false,
		success: function(data, textStatus, jqXHR){
			console.log(data);	
			ci = data["ci"];
	
		},
		error: function (jqXHR, textStatus, errorThrown)	{

		}
	});
	return ci;
}
</script>