<?
///////////////////////////////////////////////////////////////////////////////
// 환급계좌 등록폼 (법인회원용)
// 법인은 통장사본 확인 후 등록하므로 본 계좌인증은 생략하고 가상계좌 발급으로 넘어감
///////////////////////////////////////////////////////////////////////////////

include_once('./_common.php');

$mb_co_reg_num = preg_replace("/-| /", "", $member['mb_co_reg_num']);

?>

<!-- 본문내용 START -->
<script type="text/javascript" src="/js/mypage.js?ver=20190226"></script>
<style>
.address_field{width:120px; height:30px; border-color:#CFCFCF; border-width:1px; border-style:solid; background-color:#FBFBFB;text-indent:10px;}
.con h3 { position:relative; margin-bottom:10px; font-size:18px; color:#202020; font-family:'NGB'; text-align:left; }
.con .type01 th { border-right:1px solid #ddd; }
</style>
<? if(G5_IS_MOBILE) { ?>
<style>
.content .type01 input.text { width:98%; }
</style>
<? } ?>

<img src="/images/btn_close.gif" alt="close" class="close">
<div class="title">가상계좌번호 받기</div>
<div class="con">
	<div id="step1" style="display:block">
		<form name="frm" id="frm" method="post">
			<input type="hidden" name="mb_no"                  value="<?=$member['mb_no']?>">
			<input type="hidden" name="mb_co_owner"            value="<?=$member['mb_co_owner']?>">
			<input type="hidden" name="member_type"            value="<?=$member['member_type']?>">
			<input type="hidden" name="receive_method"         value="2"> <!-- 원리금 수취방식 -->

		<h3>환급계좌 및 원천징수정보 확인</h3>
		<div style="margin:8px auto 8px;color:#FF3333;text-align:left;font-size:12px;padding:4px 10px 4px 10px; border:1px solid #aaa; border-radius:3px;background-color:#F8F8EF">
			- 환급계좌 내용을 확인하시고 아래 가상계좌발급 버튼을 클릭하십시오.
		</div>
		<div class="type01">
			<!-- 법인회원 정보 -->
			<table id="m_type1">
				<tbody>
					<tr>
						<th>* 환급계좌</th>
						<td <?=(G5_IS_MOBILE)?'style="padding-left:1%"':''?>>
							<input type="text"class="text" name="bank_private_name" id="bank_private_name" value="<?=($member['bank_private_name'])?$member['bank_private_name']:$member['mb_co_name']?>" placeholder="예금주" style="width:95%;color:#afafaf;text-indent:5px;" readonly>
							<input type="text"class="text" name="bank_private_name_sub" id="bank_private_name_sub" value="<?=$member["bank_private_name_sub"]?>" placeholder="(부기명)" style="width:95%;height:20px;margin-top:6px;text-indent:3px;">

							<select name="strBankCode" id="strBankCode" style="width:98%;margin-top:4px;">
								<option value="">:: 은행을 선택하세요 ::</option>
								<?
								$BANK_KEYS = array_keys($BANK);
								for($i=0; $i<count($BANK); $i++) {
									$selected = ($BANK_KEYS[$i]==sprintf("%03d", $member["bank_code"])) ? 'selected' : '';
									echo "<option value='".$BANK_KEYS[$i]."' $selected>".$BANK[$BANK_KEYS[$i]]."</option>\n";
								}
								?>
							</select>

							<input type="text" class="text small" name="strAccountNo" id="strAccountNo" value="<?=$member["account_num"]?>" onKeyup="onlyDigit(this);" placeholder="계좌번호" style="width:95%;margin-top:4px;text-indent:4px;">
						</td>
					</tr>
					<tr>
						<th>* 사업자번호</th>
						<td <?=(G5_IS_MOBILE)?'style="padding-left:1%"':''?>>
							<input type="text" class="text" name="mb_co_reg_num" id="mb_co_reg_num" value="<?=$mb_co_reg_num?>" maxlength="13" onKeyup="onlyDigit(this)" style="width:95%;color:#afafaf;text-indent:5px;" readonly>
						</td>
					</tr>
					<tr>
						<th>* 주소</th>
						<td <?=(G5_IS_MOBILE)?'style="padding-left:1%"':''?>>
							<input type="text" name="zip_num" id="zip_num" value="<?=$member['zip_num']?>" class="address_field" placeholder=" 우편번호" onClick="search_address('zip_num','address_road','address_dong')" readonly>
							<input type="button" id='zip_btn' class="btn_green" onClick="execDaumPostcode();" value="주소찾기"><br>
							<input type="text" name="address_road" id="address_road" class="text" onFocus="focus_out('zip_btn')" value="<?=$member['mb_addr1']?>" placeholder="도로명주소" style="width:98%;margin-top:8px;text-indent:3px;">
							<input type="text" name="address_dong" id="address_dong" class="text" onFocus="focus_out('zip_btn')" value="<?=$member['mb_addr_jibeon']?>" placeholder="지번주소" style="width:98%;margin-top:2px;text-indent:3px;">
							<input type="text" class="text" name="mb_addr2" id="mb_addr2" value="<?=$member['mb_addr2']?>" placeholder="번지 이하 상세주소" style="width:98%;margin-top:8px;text-indent:3px;">
							<span id="guide"></span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="btnArea" style="margin:10px auto;"><button type="button" id="btn_bank_account_regist" class="btn_big_blue">가상계좌발급</button></div>
		</form>
	</div>

	<div id="step2" style="display:none">
		<h3>헬로펀딩 가상계좌 발급 안내</h3>
		<div style="margin:8px auto 8px;color:#3333FF;text-align:left;font-size:12px;padding:4px 10px 4px 10px; border:1px solid #aaa; border-radius:3px;background-color:#F8F8EF">
		* 헬로펀딩의 투자전용 예치금 계좌(가상계좌)입니다.<br>
		* 발급 받으신 가상계좌로 예치금을 충전하신 후 투자가능합니다.<br>
		* 헬로펀딩의 예치금/투자금/상환금 일체를 신한은행이 직접 관리하므로 안전합니다.
		</div>

		<h3>가상계좌정보</h3>
		<div class="type01">
			<table>
				<tbody>
					<tr>
						<th>은행명</th>
						<td><div id="prnt_v_bank_name" style="color:#3366FF"></div></td>
					</tr>
					<tr>
						<th>계좌번호</th>
						<td><div id="prnt_v_acc_num" style="color:#3366FF"></div></td>
					</tr>
					<tr>
						<th>예금주</th>
						<td><div id="prnt_v_private_name" style="color:#3366FF"></div></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="btnArea" style="margin:20px auto 0;"><button type="button" onClick="window.location.reload();" class="btn_big_blue">확 인</button></div>
	</div>

</div>

<script>
function btn_event(arg) {
	if(arg=='send') {
		$('#btn_bank_account_regist').removeClass('btn_big_blue').addClass('btn_big_gray');
		$('#btn_bank_account_regist').text('전송중 >>>');
		$('#btn_bank_account_regist').attr('disabled', 'disabled');
	}
	else if(arg=='exit') {
		$('#btn_bank_account_regist').removeAttr('disabled');
		$('#btn_bank_account_regist').text('가상계좌발급');
		$('#btn_bank_account_regist').removeClass('btn_big_gray').addClass('btn_big_blue');
	}
	else {
		return;
	}
}

$('#btn_bank_account_regist').click(function() {

	var f = document.frm;

	if(!input_check(f.zip_num.value)) { alert('우편번호를 입력해 주세요.'); $('#zip_btn').focus(); }
	else if(!input_check(f.address_road.value) || !input_check(f.address_dong.value)) { alert('도로명, 또는 지번 주소를 입력하세요.'); $('#zip_btn').focus(); }
	else if(!input_check(f.mb_addr2.value)) { alert('번지 이하 상세주소를 입력하세요.'); $('#mb_addr2').focus(); }
	else {

		btn_event('send');

		var fdata = $('#frm').serialize();
		$.ajax({
			type: 'post',
			url: '/bank_account/account_proc_c.php',
			cache: false,
			data: fdata,
			success: function(result) {

				$('#ajax_return_txt').val(result);

				var array_result = result.split(':');		// 결과값 배열화

				if(array_result[0]=='SUCCESS') {

					bank_arr = array_result[1].split('^');  // 0:은행명, 1:계좌번호, 2:예금주

					$('#prnt_v_bank_name').html(bank_arr[0]);
					$('#prnt_v_acc_num').html(bank_arr[1]);
					$('#prnt_v_private_name').html(bank_arr[2]);
					$('#step1').css('display','none');
					$('#step2').css('display','block');

				}
				else if(array_result[0]=='ERROR') {
					if(array_result[1]=='LOGIN')                      { $(location).attr('href', '/'); }
					else if(array_result[1]=='NONE_MEMBER')           { $(location).attr('href', '/'); }
					else if(array_result[1]=='DATA_CHECK')						{ alert('필수데이터가 누락되었습니다.'); }
					else if(array_result[1]=='NAME_MISMATCH')         { alert('예금주명이 회원명과 일치하지 않습니다.'); }
					else if(array_result[1]=='PRIVATE_SAVE_FAILED')   { alert('개인정보 암호화 및 저장 에러 입니다. 관리자에게 문의하십시요.'); }
					else if(array_result[1]=='PRIVATE_UPDATE_FAILED') { alert('개인정보 암호화 및 저장 에러 입니다. 관리자에게 문의하십시요.'); }
					else if(array_result[1]=='EMPTY_BANK_INFO')       { alert('환급계좌 정보가 등록 되지 않아, 가상계좌 발급이 불가합니다..'); }
					else if(array_result[1]=='EMPTY_JUMINNO')         { alert('주민등록번호가 등록 되지 않아, 가상계좌 발급이 불가합니다.'); }
					else if(array_result[1]=='ACCOUNT_MISMATCH')      { alert('환급계좌정보가 기등록된 정보와 일치 하지 않아, 가상계좌 발급이 불가합니다.'); }
					else if(array_result[1]=='DUPLICATE_REQUEST')     { alert('이미 신한 가상계좌가 등록되어 있습니다. 재발급은 허용하지 않습니다.'); }
					else if(array_result[1]=='SH_VA_INSUFFICIENCY')   { alert('배정 가능한 가상계좌가 없습니다.'); }
					else { alert('다음 사유로 계좌 발급이 되지 않았습니다.\n\n' + array_result[1] + '\n\n관리자에게 문의 하십시요.' ); }
				}
				else {
					alert('다음 사유로 계좌 발급이 되지 않았습니다.\n\n' + result + '\n\n관리자에게 문의 하십시요.' );
				}

				if(array_result[0]!='SUCCESS') {
					btn_event('exit');
				}

			},
			error: function(e) {
				alert("금융기관 통신오류 입니다. 잠시 후 다시 시도하여 주십시요.");
				btn_event('exit');
			}
		});

	}

});
</script>