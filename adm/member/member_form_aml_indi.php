<?
###############################################################################
## 개인용 AML 정보 입력폼 (회원정보입력/수정페이지에서 로드)
###############################################################################

include_once("_common.php");

include_once(G5_PATH . '/data/aml_inc/kofiu_code.inc.php');
include_once(G5_PATH . '/data/aml_inc/aml_array.inc.php');

$AML_TABLE = "g5_member_aml_indi";

$AML = sql_fetch("SELECT * FROM {$AML_TABLE} WHERE mb_no = '".$MB['mb_no']."' ORDER BY reg_dt DESC LIMIT 1");

// AML 정보가 없을 경우 회원데이터 일부 추출
if(!$AML['mb_no']) {

	$AML['CUSTOMER_TP_CD']   = '08';																				// 고객유형코드 01:비영리단체 02:고액자산가 03:신용불량자 04:금융기관 05:국가.지방자치단체 06:UN산하 국제자선기구 07:상장회사 08:기타
	$AML['TMS_CUSTOMER_DIV'] = ($MB['is_owner_operator']) ? '03' : '01';		// 01:개인 02:법인 03:개인사업자

	$AML['CUSTOMER_NM']      = $MB['mb_name'];
	$AML['CUSTOMER_ENG_NM']  = strtoupper(trim($MB['eng_last_nm'].' '.$MB['eng_first_nm']));

	$AML['BIRTH_DD'] = preg_replace("/(-| )/", "", $MB['mb_birth']);
	$AML['SEX_CD']   = ($MB['mb_sex']=='w') ? '2' : '1';


	if($MB['foreigner']=='1') {
		$AML['FOREIGNER_DIV'] = 'B';		// A:내국인 B:외국인
	}
	else {
		$AML['COUNTRY_CD'] = $AML['LIVE_COUNTRY_CD'] = 'KR';									// 국적코드, 거주국가코드
		$AML['FOREIGNER_DIV'] = 'A';
	}


	$AML['HOME_ADDR_COUNTRY_CD'] = ($MB['zip_num'] && $MB['mb_addr1'] && $MB['mb_addr_jibeon']) ? 'KR' : '';
	$AML['HOME_POST_NO']         = ($MB['zip_num']) ? $MB['zip_num'] : '';
	$AML['HOME_ADDR']            = ($MB['mb_addr1']) ? $MB['mb_addr1'] : '';
	$AML['HOME_ADDR_jibeon']     = ($MB['mb_addr_jibeon']) ? $MB['mb_addr_jibeon'] : '';
	$AML['HOME_DTL_ADDR']        = ($MB['mb_addr2']) ? $MB['mb_addr2'] : '';

	$AML['HOME_ADDR_DISPLAY_DIV '] = '';
	if($MB['HOME_ADDR']) {
		$AML['HOME_ADDR_DISPLAY_DIV '] = 'KR';
	}
	else if($MB['HOME_ADDR_jibeon']) {
		$AML['HOME_ADDR_DISPLAY_DIV '] = 'KS';
	}

	$MB['mb_hp'] = preg_replace("/(-| )/", "", $MB['mb_hp']);
	$AML['CELL_PHONE_NO'] = $MB['mb_hp'];
	if($AML['CELL_PHONE_NO']) {
		$HP[0] = substr($AML['CELL_PHONE_NO'], 0, 3);
		if(strlen($AML['CELL_PHONE_NO']) > 10) {
			$HP[1] = substr($AML['CELL_PHONE_NO'], 3, 4);
			$HP[2] = substr($AML['CELL_PHONE_NO'], 7);
		}
		else {
			$HP[1] = substr($AML['CELL_PHONE_NO'], 3, 3);
			$HP[2] = substr($AML['CELL_PHONE_NO'], 6);
		}
	}

}
else {

	$AML['RNM_NO']            = ($AML['RNM_NO']) ? masterDecrypt($AML['RNM_NO'], false) : '';
	$AML['REAL_OWNER_RNM_NO'] = ($AML['REAL_OWNER_RNM_NO']) ? masterDecrypt($AML['REAL_OWNER_RNM_NO'], false) : '';
	$AML['CELL_PHONE_NO']     = ($AML['CELL_PHONE_NO']) ? masterDecrypt($AML['CELL_PHONE_NO'], false) : '';
	$AML['REAL_OWNR_RNM_NO']  = ($AML['REAL_OWNR_RNM_NO']) ? masterDecrypt($AML['REAL_OWNR_RNM_NO'], false) : '';

	if(!$AML['CUSTOMER_ENG_NM']) {
		$AML['CUSTOMER_ENG_NM'] = trim($MB['eng_last_nm'] . " " . $MB['eng_first_nm']);
	}

	if($AML['CELL_PHONE_NO']) {
		$HP[0] = substr($AML['CELL_PHONE_NO'], 0, 3);
		if(strlen($AML['CELL_PHONE_NO']) > 10) {
			$HP[1] = substr($AML['CELL_PHONE_NO'], 3, 4);
			$HP[2] = substr($AML['CELL_PHONE_NO'], 7);
		}
		else {
			$HP[1] = substr($AML['CELL_PHONE_NO'], 3, 3);
			$HP[2] = substr($AML['CELL_PHONE_NO'], 6);
		}
	}

}

?>
				<form id="fkyc" name="fkyc" method="post" target="axFrame" action="member_form_aml_indi.update.php">
				<input type="hidden" id="mb_no" name="mb_no" value="<?=$MB['mb_no']?>">
				<input type="hidden" id="mb_f_no" name="mb_f_no" value="<?=$MB['mb_f_no']?>">
				<input type="hidden" id="TMS_CUSTOMER_DIV" name="TMS_CUSTOMER_DIV" value="<?=$AML['TMS_CUSTOMER_DIV']?>">

				<h3>AML(자금세탁방지) 정보</h3>
				<table style="border-top:2px solid <?=($AML['mb_no'])?'#3333FF':'#FF3333'?>">
					<colgroup>
						<col style="width:13%">
						<col style="width:37%">
						<col style="width:13%">
						<col style="width:37%">
					</colgroup>
					<tbody>
						<tr>
							<td colspan="4" style="background:#EEE;border-bottom:1px solid #CCC">
								<ul class="list-inline" style="margin:0">
									<li style="float:left;"><strong style="margin-left:10px;">공통등록</strong></li>
									<? if( (int)preg_replace("/(-|:| )/", "", $AML['reg_dt']) ) { ?><li style="float:right;margin-right:20px;font-size:12px;">등록: <?=substr($AML['reg_dt'],0,16);?></li><? } ?>
									<? if( (int)preg_replace("/(-|:| )/", "", $AML['edit_dt']) ) { ?><li style="float:right;margin-right:20px;font-size:12px;">수정: <?=substr($AML['edit_dt'],0,16);?></li><? } ?>
								</ul>
							</td>
						</tr>
						<tr>
							<th scope="row" class="tit">고객유형</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li style="float:left;">
										<select name="CUSTOMER_TP_CD" id="CUSTOMER_TP_CD" class="form-control input-sm" style="max-width:180px" alt="고객유형코드">
											<option value="">:: 고객유형 선택 ::</option>
<?
	for($i=0; $i<count($ARR_CUSTOMER_TP_CD); $i++) {
		$selected = ($ARR_CUSTOMER_TP_CD[$ACTC_KEY[$i]]['CD']==$AML['CUSTOMER_TP_CD']) ? 'selected' : '';
		echo "<option value='".$ARR_CUSTOMER_TP_CD[$ACTC_KEY[$i]]['CD']."' $selected>".$ARR_CUSTOMER_TP_CD[$ACTC_KEY[$i]]['NM']."</option>\n";
	}
?>
										</select>
									</li>
<? if($MB['is_owner_operator']=='1') { ?>
									<li style="float:left;margin-left:10px;">
										<label class="checkbox-inline"><input type="checkbox" name="VIRTUAL_MONEY_BUSINESS_YN" id="VIRTUAL_MONEY_BUSINESS_YN" value="Y" <?=($AML['VIRTUAL_MONEY_BUSINESS_YN']=='Y')?'checked':'';?>> 가상통화취급사업자</label></li>
									</li>
<? } ?>
								</ul>
							</td>
							<th scope="row" class="tit">접근경로</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><label class="radio-inline"><input type="radio" name="AML_RA_CHANNEL_CD" id="AML_RA_CHANNEL_CD" value="03" <?if(in_array($AML['AML_RA_CHANNEL_CD'], array('','03'))){ echo 'checked'; }?>> 모바일/인터넷</label></li>
									<li><label class="radio-inline"><input type="radio" name="AML_RA_CHANNEL_CD" id="AML_RA_CHANNEL_CD" value="01" <?if($AML['AML_RA_CHANNEL_CD']=='01'){ echo 'checked'; }?>> 대면</label></li>
									<li><label class="radio-inline"><input type="radio" name="AML_RA_CHANNEL_CD" id="AML_RA_CHANNEL_CD" value="02" <?if($AML['AML_RA_CHANNEL_CD']=='02'){ echo 'checked'; }?>> 전화</label></li>
								</ul>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit">고객명</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><input type="text" id="CUSTOMER_NM" name="CUSTOMER_NM" value="<?=$AML['CUSTOMER_NM']?>" class="frm_input input-sm" onKeyup="get_owner_info();"></li>
								</ul>
							</td>
							<th scope="row" class="tit">고객영문명</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><input type="text" id="CUSTOMER_ENG_NM" name="CUSTOMER_ENG_NM" value="<?=$AML['CUSTOMER_ENG_NM']?>" class="frm_input input-sm" style="ime-mode:disabled;text-transform:uppercase;"></li>
								</ul>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit">대리인여부</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><label class="radio-inline"><input type="radio" name="AGENT_YN" id="AGENT_YN_N" value="N" <?if( in_array($AML['AGENT_YN'], array('','N')) ){ echo 'checked'; }?> onClick="agentRelayChoice();"> 본인</label></li>
									<li><label class="radio-inline"><input type="radio" name="AGENT_YN" id="AGENT_YN_Y" value="Y" <?if($AML['AGENT_YN']=='Y'){ echo 'checked'; }?> onClick="agentRelayChoice();"> 대리인</label></li>
									<li>
										<select name="AGENT_RELA_DIV" id="AGENT_RELA_DIV" class="form-control input-sm" style="max-width:180px" alt="회원과의관계">
											<option value="">:: 회원과의 관계 선택 ::</option>
											<option value="01" <?=($AML['AGENT_RELA_DIV']=='01')?'selected':'';?>>배우자</option>
											<option value="02" <?=($AML['AGENT_RELA_DIV']=='02')?'selected':'';?>>부모</option>
											<option value="03" <?=($AML['AGENT_RELA_DIV']=='03')?'selected':'';?>>자녀</option>
											<option value="04" <?=($AML['AGENT_RELA_DIV']=='04')?'selected':'';?>>형제자매</option>
											<option value="05" <?=($AML['AGENT_RELA_DIV']=='05')?'selected':'';?>>친척</option>
											<option value="06" <?=($AML['AGENT_RELA_DIV']=='06')?'selected':'';?>>상사</option>
											<option value="07" <?=($AML['AGENT_RELA_DIV']=='07')?'selected':'';?>>동료,친구</option>
											<option value="08" <?=($AML['AGENT_RELA_DIV']=='08')?'selected':'';?>>기타</option>
										</select>
									</li>
								</ul>
								<script>
								function agentRelayChoice() {
									if( $("input:radio[name='AGENT_YN']:checked").val()=='Y' ) {
										$('#AGENT_RELA_DIV').attr('disabled',false);
									}
									else {
										$('#AGENT_RELA_DIV').attr('disabled',true);
									}
								}
								$(document).ready(function(){ agentRelayChoice(); });
								</script>
<? if(!$AML['mb_no']) { ?>
								<script>
								function getOwnerInfo() {
									if( $("input:radio[name='AGENT_YN']:checked").val()=='N' ) {
										$("#REAL_OWNER_NM").val($("#CUSTOMER_NM").val());
										if($("#CUSTOMER_ENG_NM").val()!='') { $("#REAL_OWNER_ENG_NM").val($("#CUSTOMER_ENG_NM").val()); }
										$("#REAL_OWNER_RNM_NO_DIV option:eq(1)").prop('selected', true);
										$("#REAL_OWNER_RNM_NO").val($('#regist_number').val());
									}
									else {
										$("#REAL_OWNER_NM").val(''); $("#REAL_OWNER_ENG_NM").val('');
										$("#REAL_OWNER_RNM_NO_DIV option:eq(0)").prop('selected', true);
										$("#REAL_OWNER_RNM_NO").val('');
									}
								}
								$(document).ready(function(){ getOwnerInfo(); });
								</script>
<? } ?>
							</td>
							<th scope="row" class="tit">대리인 회원ID</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><input type="text" id="AGENT_mb_id" name="AGENT_mb_id" value="<?=$AML['AGENT_mb_id']?>" class="form-control input-sm" style="width:180px;">
										<input type="hidden" id="AGENT_CUSTOMER_NO" name="AGENT_CUSTOMER_NO" value="<?=$AML['AGENT_CUSTOMER_NO']?>" style="width:180px;">
									</li>
									<li><button type="button" class="btn btn-sm btn-primary" onClick="get_mbno();">조회</button></li>
								</ul>
							</td>
						</tr>
						<script>
						// 대리인ID 회원번호 확인
						function get_mbno() {
							if( $.trim($('#AGENT_mb_id').val())!='' && $.trim($('#AGENT_mb_id').val().length)>=4 ) {
								if( $("input:radio[name='AGENT_YN']:checked").val()=='N' ) {
									alert('대리인인 경우에만 대리인회원ID 조회 가능합니다.');
									$('#AGENT_mb_id, #AGENT_CUSTOMER_NO').val('');
									return;
								}
								$.ajax({
									url : "/adm/member/ajax_get_mb_no.php",
									type: "POST",
									data : {
										'my_mb_id' : $('#mb_id').val(),
										'agent_mb_id' : $('#AGENT_mb_id').val()
									},
									dataType : 'json',
									success: function(data) {
										if(data.result=='SUCCESS') {
											alert('정상존재회원');
											$('#AGENT_CUSTOMER_NO').val(data.msg);
										}
										else {
											$("#AGENT_YN_N").prop('checked', true);
											$("#AGENT_RELA_DIV").val('');
											$('#AGENT_mb_id').val('');
											$('#AGENT_mb_id').focus();
											alert(data.msg);
										}
									},
									error: function(e) { return; }
								});
							}
						}
						</script>

						<tr>
							<th scope="row" class="tit">국적</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li>
										<select name="COUNTRY_CD" id="COUNTRY_CD" class="form-control input-sm" style="max-width:180px" alt="국적">
											<option value="">:: 국적선택 ::</option>
<?
	for($i=0; $i<$KOFIU_COUNTRY_COUNT; $i++) {
		$selected = ($KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['CD']==$AML['COUNTRY_CD']) ? 'selected' : '';
		echo "<option value='".$KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['CD']."' $selected>".$KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['NM']."</option>\n";
	}
?>
										</select>
										<input type="hidden" id="FOREIGNER_DIV" name="FOREIGNER_DIV" value="<?=$AML['FOREIGNER_DIV']?>" alt="내.외국 구분">
										<input type="hidden" id="LIVE_YN" name="LIVE_YN" style="max-width:180px" alt="국내거주여부">
									</li>
								</ul>
							</td>

							<th scope="row" class="tit">직업코드</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li>
										<select name="JOB_DIV_CD" id="JOB_DIV_CD" class="form-control input-sm" style="max-width:180px">
											<option value="">:: 직업구분 선택 ::</option>
<?
	for($i=0; $i<$KOFIU_JOB_DIV_CD_COUNT; $i++) {
		$selected = ($KOFIU_JOB_DIV_CD[$KJDC_KEY[$i]]['CD']==$AML['KOFIU_JOB_DIV_CD']) ? 'selected' : '';
		echo "<option value='".$KOFIU_JOB_DIV_CD[$KJDC_KEY[$i]]['CD']."' $selected>" . $KOFIU_JOB_DIV_CD[$KJDC_KEY[$i]]['NM']."</option>\n";
	}
?>
										</select>
									</li>
<? if($MB['is_owner_operator']=='1') { ?>
									<li>
										<select name="INDV_INDUSTRY_CD" id="INDV_INDUSTRY_CD" class="form-control input-sm" style="max-width:200px;">
											<option value="">:: 사업자 업종선택 ::</option>
<?
	for($i=0; $i<$KOFIU_INDUSTRY_COUNT; $i++) {
		$selected = ($KOFIU_INDUSTRY_CODE[$KICD_ARRKEY[$i]]['P_CD']==$AML['INDV_INDUSTRY_CD']) ? 'selected' : '';
		echo "<option value='".$KOFIU_INDUSTRY_CODE[$KICD_ARRKEY[$i]]['P_CD']."' $selected>" . $KOFIU_INDUSTRY_CODE[$KICD_ARRKEY[$i]]['C_NM'] ." &gt; ". $KOFIU_INDUSTRY_CODE[$KICD_ARRKEY[$i]]['P_NM'] . "</option>\n";
	}
?>
										</select>
									</li>
<? } ?>
								</ul>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit">생년월일</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><input type="text" id="BIRTH_DD" name="BIRTH_DD" value="<?=$AML['BIRTH_DD']?>" placeholder="yyyymmdd" class="form-control input-sm" style="width:180px;" onKeyup="onlyDigit(this);" onBlur="onlyDigit(this);"></li>
								</ul>
							</td>
							<th scope="row" class="tit">성별</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><label class="radio-inline"><input type="radio" name="SEX_CD" id="SEX_CD" value="1" <?if($AML['SEX_CD']=='1'){ echo 'checked'; }?>> 남</label></li>
									<li><label class="radio-inline"><input type="radio" name="SEX_CD" id="SEX_CD" value="2" <?if($AML['SEX_CD']=='2'){ echo 'checked'; }?>> 여</label></li>
								</ul>
							</td>
						</tr>

						<tr>
							<td colspan="4" style="background:#EEE; border-top:2px solid #FFF; border-bottom:1px solid #CCC"><strong style="margin-left:10px;">CDD(고객확인의무)</strong></td>
						</tr>
						<tr>
							<th scope="row" class="tit">실소유자명</th>
							<td>
								<input type="hidden" id="REAL_OWNER_YN" name="REAL_OWNER_YN" value="Y" title="실소유자여부">
								<ul class="list-inline" style="margin:0">
									<li><input type="text" id="REAL_OWNER_NM" name="REAL_OWNER_NM" value="<?=$AML['REAL_OWNER_NM']?>" placeholder="한글명" class="form-control input-sm" style="width:180px"></li>
									<li><input type="text" id="REAL_OWNER_ENG_NM" name="REAL_OWNER_ENG_NM" value="<?=$AML['REAL_OWNER_ENG_NM']?>" placeholder="영문명" class="form-control input-sm" style="ime-mode:disabled;text-transform:uppercase; width:250px"></li>
								</ul>
							</td>
							<th scope="row" class="tit"><label>계정 실소유자 실명번호</label></th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li>
										<select name="REAL_OWNER_RNM_NO_DIV" id="REAL_OWNER_RNM_NO_DIV" class="form-control input-sm" style="max-width:180px">
											<option value="">:: 실명번호구분선택 ::</option>
											<option value="01" <?=($AML['REAL_OWNER_RNM_NO_DIV']=='01')?'selected':''?>>주민등록번호</option>
											<option value="03" <?=($AML['REAL_OWNER_RNM_NO_DIV']=='03')?'selected':''?>>사업자등록번호</option>
											<option value="06" <?=($AML['REAL_OWNER_RNM_NO_DIV']=='06')?'selected':''?>>외국인등록번호</option>
										</select>
									</li>
									<li><input type="text" name="REAL_OWNER_RNM_NO" id="REAL_OWNER_RNM_NO" value="<?=$AML['REAL_OWNER_RNM_NO']?>" placeholder="실명번호입력" class="form-control input-sm" style="color:#EEE;width:180px"></li>
								</ul>
							</td>
						</tr>

						<tr>
							<th rowspan="2" scope="row" class="tit"><label>자택주소</label></th>
							<td rowspan="2">
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="width:100px;">주소지국가</li>
									<li>
										<select name="HOME_ADDR_COUNTRY_CD" id="HOME_ADDR_COUNTRY_CD" class="form-control input-sm" style="width:180px">
											<option value="">:: 국가 선택::</option>
											<?
											for($i=0; $i<$KOFIU_COUNTRY_COUNT; $i++) {
												$selected = ($KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['CD']==$AML['HOME_ADDR_COUNTRY_CD']) ? 'selected' : '';
												echo "<option value='".$KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['CD']."' $selected>".$KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['NM']."</option>\n";
											}
											?>
										</select>
									</li>
								</ul>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="width:100px;">우편번호</li>
									<li><input type="text" name="HOME_POST_NO" id="HOME_POST_NO" value="<?=$AML['HOME_POST_NO']?>" onClick="win_zip('fkyc', 'HOME_POST_NO', 'HOME_ADDR', 'HOME_DTL_ADDR', '', 'HOME_ADDR_jibeon');" maxlength="6" readonly class="frm_input input-sm"></li>
									<li><button type="button" onClick="win_zip('fkyc', 'HOME_POST_NO', 'HOME_ADDR', 'HOME_DTL_ADDR', '', 'HOME_ADDR_jibeon');" class="btn btn-sm btn-default">주소검색</button></li>
								</ul>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="width:100px;">도로명주소</li>
									<li><input type="text" name="HOME_ADDR" id="HOME_ADDR" value="<?=$AML['HOME_ADDR']?>" readonly class="frm_input input-sm" style="width:350px;"></li>
								</ul>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="width:100px;"><span style="color:#AAA">지번주소</span></li>
									<li><input type="text" name="HOME_ADDR_jibeon" id="HOME_ADDR_jibeon" value="<?=$AML['HOME_ADDR_jibeon']?>" class="frm_input input-sm" style="width:350px;"></li>
								</ul>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="width:100px;">상세주소</li>
									<li><input type="text" name="HOME_DTL_ADDR" id="HOME_DTL_ADDR" value="<?=$AML['HOME_DTL_ADDR']?>" class="frm_input input-sm" style="width:350px;"></li>
								</ul>
							</td>

							<th scope="row" class="tit"><label>자택전화번호</label></th>
							<td>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="padding:0">
										<select id="phone0" class="frm_input input-sm" onChange="fillPNO();">
											<option value="">:: 선택 ::</option>
											<?
											for($i=0; $i<count($PHONE_AREA_NO); $i++) {
												echo "<option value='".$PHONE_AREA_NO[$PAN_KEY[$i]]['NO']."'>".$PHONE_AREA_NO[$PAN_KEY[$i]]['NO'];
												if(!in_array($PHONE_AREA_NO[$PAN_KEY[$i]]['NO'], array('070','010','011','016','017','018','019'))) echo "(".$PHONE_AREA_NO[$PAN_KEY[$i]]['AREA'].")";
												echo "</option>\n";
											}
											?>
										</select> -
										<input type="text" id="phone1" class="frm_input input-sm" maxlength="4" style="width:60px" onKeyup="onlyDigit(this);fillPNO();"> -
										<input type="text" id="phone2" class="frm_input input-sm" maxlength="4" style="width:60px" onKeyup="onlyDigit(this);fillPNO();">
									</li>
								</ul>
								<input type="text" id="HOME_PHONE_NO" name="HOME_PHONE_NO" value="<?=$AML['HOME_PHONE_NO']?>" readonly onFocus="blur()" class="frm_input input-sm" style="color:#DDD">
								<script>
								function fillPNO() {
									if($('#phone0').val()!='' && $('#phone1').val()!='' && $('#phone2').val()!='') {
										var fullNo = $('#phone0').val() + $('#phone1').val() + $('#phone2').val();
										$('#HOME_PHONE_NO').val(fullNo);
									}
								}
								$(document).ready(function(){ fillPNO(); });
								</script>
							</td>
						</tr>
						<tr>
							<th scope="row" class="tit"><label>휴대폰번호</label></th>
							<td>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="padding:0">
										<select id="hp0" class="frm_input input-sm" onChange="fillHPNO();">
											<option value="">:: 선택 ::</option>
											<?
											for($i=0; $i<count($HP_AREA_NO); $i++) {
												$selected = ($HP_AREA_NO[$HAN_KEY[$i]]['NO']==$HP[0]) ? 'selected' : '';
												echo "<option value='".$HP_AREA_NO[$HAN_KEY[$i]]['NO']."' $selected>".$HP_AREA_NO[$HAN_KEY[$i]]['NO']."</option>\n";
											}
											?>
										</select> -
										<input type="text" id="hp1" value="<?=$HP[1]?>" class="frm_input input-sm" maxlength="4" style="width:60px" onKeyup="onlyDigit(this);fillHPNO();"> -
										<input type="text" id="hp2" value="<?=$HP[2]?>" class="frm_input input-sm" maxlength="4" style="width:60px" onKeyup="onlyDigit(this);fillHPNO();">
									</li>
								</ul>
								<input type="text" id="CELL_PHONE_NO" name="CELL_PHONE_NO" value="<?=$AML['CELL_PHONE_NO']?>" readonly onFocus="blur()" class="frm_input input-sm" style="color:#DDD">
								<script>
								function fillHPNO() {
									if($('#hp0').val()!='' && $('#hp1').val()!='' && $('#hp2').val()!='') {
										var fullNo = $('#hp0').val() + $('#hp1').val() + $('#hp2').val();
										$('#CELL_PHONE_NO').val(fullNo);
									}
								}
								$(document).ready(function(){ fillHPNO(); });
								</script>
							</td>
						</tr>

						<tr>
							<td colspan="4" style="background:#EEE; border-top:2px solid #FFF; border-bottom:1px solid #CCC"><strong style="margin-left:10px;">EDD(강화된 고객확인의무)</strong></td>
						</tr>
						<tr>
							<th rowspan="2" scope="row" class="tit"><label>직장주소</label></th>
							<td rowspan="2">
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="width:100px;">주소지국가</li>
									<li>
										<select name="WORK_ADDR_COUNTRY_CD" id="WORK_ADDR_COUNTRY_CD" class="form-control input-sm" style="width:180px" alt="직장소재지국가">
											<option value="">:: 국가 선택::</option>
											<?
											for($i=0; $i<$KOFIU_COUNTRY_COUNT; $i++) {
												$selected = ($KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['CD']==$AML['WORK_ADDR_COUNTRY_CD']) ? 'selected' : '';
												echo "<option value='".$KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['CD']."' $selected>".$KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['NM']."</option>\n";
											}
											?>
										</select>
									</li>
								</ul>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="width:100px;">우편번호</li>
									<li><input type="text" name="WORK_POST_NO" id="WORK_POST_NO" value="<?=$AML['WORK_POST_NO']?>" onClick="win_zip('fkyc', 'WORK_POST_NO', 'WORK_ADDR', 'WORK_DTL_ADDR', '', 'WORK_ADDR_jibeon');" maxlength="6" readonly class="frm_input input-sm"></li>
									<li><button type="button" onClick="win_zip('fkyc', 'WORK_POST_NO', 'WORK_ADDR', 'WORK_DTL_ADDR', '', 'WORK_ADDR_jibeon');" class="btn btn-sm btn-default">주소검색</button></li>
								</ul>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="width:100px;">도로명주소</li>
									<li><input type="text" name="WORK_ADDR" id="WORK_ADDR" value="<?=$AML['WORK_ADDR']?>" readonly class="frm_input input-sm" style="width:350px;"></li>
								</ul>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="width:100px;"><span style="color:#AAA">지번주소</span></li>
									<li><input type="text" name="WORK_ADDR_jibeon" id="WORK_ADDR_jibeon" value="<?=$AML['WORK_ADDR_jibeon']?>" class="frm_input input-sm" style="width:350px;"></li>
								</ul>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="width:100px;">상세주소</li>
									<li><input type="text" name="WORK_DTL_ADDR" id="WORK_DTL_ADDR" value="<?=$AML['WORK_DTL_ADDR']?>" class="frm_input input-sm" style="width:350px;"></li>
								</ul>
							</td>

							<th scope="row" class="tit"><label>직장전화번호</label></th>
							<td>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="padding:0">
										<select id="wphone0" class="frm_input input-sm" onChange="fillWPNO();">
											<option value="">:: 선택 ::</option>
											<?
											for($i=0; $i<count($PHONE_AREA_NO); $i++) {
												echo "<option value='".$PHONE_AREA_NO[$PAN_KEY[$i]]['NO']."'>".$PHONE_AREA_NO[$PAN_KEY[$i]]['NO'];
												if(!in_array($PHONE_AREA_NO[$PAN_KEY[$i]]['NO'], array('070','010','011','016','017','018','019'))) echo "(".$PHONE_AREA_NO[$PAN_KEY[$i]]['AREA'].")";
												echo "</option>\n";
											}
											?>
										</select> -
										<input type="text" id="wphone1" class="frm_input input-sm" maxlength="4" style="width:60px" onKeyup="onlyDigit(this);fillWPNO();"> -
										<input type="text" id="wphone2" class="frm_input input-sm" maxlength="4" style="width:60px" onKeyup="onlyDigit(this);fillWPNO();">
									</li>
								</ul>
								<input type="text" id="WORK_AREA_PHONE_NO" name="WORK_AREA_PHONE_NO" value="<?=$AML['WORK_AREA_PHONE_NO']?>" readonly onFocus="blur()" class="frm_input input-sm" style="color:#DDD">
								<script>
								function fillWPNO() {
									if($('#wphone0').val()!='' && $('#wphone1').val()!='' && $('#wphone2').val()!='') {
										var fullNo = $('#wphone0').val() + $('#wphone1').val() + $('#wphone2').val();
										$('#WORK_AREA_PHONE_NO').val(fullNo);
									}
								}
								$(document).ready(function(){ fillWPNO(); });
								</script>
							</td>
						</tr>
						<tr>
							<th scope="row" class="tit"><label>팩스번호</label></th>
							<td>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="padding:0">
										<select id="wfax0" class="frm_input input-sm" onChange="fillWFAXNO();">
											<option value="">:: 선택 ::</option>
											<?
											for($i=0; $i<count($PHONE_AREA_NO); $i++) {
												echo "<option value='".$PHONE_AREA_NO[$PAN_KEY[$i]]['NO']."'>".$PHONE_AREA_NO[$PAN_KEY[$i]]['NO'];
												if(!in_array($PHONE_AREA_NO[$PAN_KEY[$i]]['NO'], array('070','010','011','016','017','018','019'))) echo "(".$PHONE_AREA_NO[$PAN_KEY[$i]]['AREA'].")";
												echo "</option>\n";
											}
											?>
										</select> -
										<input type="text" id="wfax1" class="frm_input input-sm" maxlength="4" style="width:60px" onKeyup="onlyDigit(this);fillWFAXNO();"> -
										<input type="text" id="wfax2" class="frm_input input-sm" maxlength="4" style="width:60px" onKeyup="onlyDigit(this);fillWFAXNO();">
									</li>
								</ul>
								<input type="text" id="WORK_FAX_NO" name="WORK_FAX_NO" value="<?=$AML['WORK_FAX_NO']?>" readonly onFocus="blur()" class="frm_input input-sm" style="color:#AAA">
								<script>
								function fillWFAXNO() {
									if($('#wfax0').val()!='' && $('#wfax1').val()!='' && $('#wfax2').val()!='') {
										var fullNo = $('#wfax0').val() + $('#wfax1').val() + $('#wfax2').val();
										$('#WORK_FAX_NO').val(fullNo);
									}
								}
								$(document).ready(function(){ fillWFAXNO(); });
								</script>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit"><label>거래자금출처</label></th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li>
										<select name="TRAN_FUND_SOURCE_DIV" id="TRAN_FUND_SOURCE_DIV" onChange="changeToVal001();" class="form-control input-sm" style="max-width:180px">
											<option value="">:: 거래자금출처 선택 ::</option>
											<?
												for($i=0; $i<count($INDI_TRAN_FUND_SOURCE_DIV); $i++) {
													$selected = ($INDI_TRAN_FUND_SOURCE_DIV[$ITFSD_KEY[$i]]['CD'] == $AML['TRAN_FUND_SOURCE_DIV']) ? 'selected' : '';
													echo "<option value='".$INDI_TRAN_FUND_SOURCE_DIV[$ITFSD_KEY[$i]]['CD']."' $selected>".$INDI_TRAN_FUND_SOURCE_DIV[$ITFSD_KEY[$i]]['NM']."</option>\n";
												}
											?>
										</select>
										<input type="hidden" id="TRAN_FUND_SOURCE_NM" name="TRAN_FUND_SOURCE_NM">
									</li>
									<li><input type="text" id="TRAN_FUND_SOURCE_OTHER" name="TRAN_FUND_SOURCE_OTHER" value="<?=$AML['TRAN_FUND_SOURCE_OTHER']?>" placeholder="자금출처 직접입력" class="form-control input-sm" style="width:180px"></li>
								</ul>
								<script>
								function changeToVal001() {
									if( $('#TRAN_FUND_SOURCE_DIV').val() ) {
										var str = $('#TRAN_FUND_SOURCE_DIV option:selected').text();
										$('#TRAN_FUND_SOURCE_NM').val(str);
									}
									else {
										$('#TRAN_FUND_SOURCE_NM').val('');
									}
									($('#TRAN_FUND_SOURCE_DIV').val()=='A99') ? $('#TRAN_FUND_SOURCE_OTHER').attr('disabled',false) : $('#TRAN_FUND_SOURCE_OTHER').attr('disabled',true);
								}
								$('document').ready(function() { changeToVal001(); });
								</script>
							</td>
							<th scope="row" class="tit"><label>거래목적</label></th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li>
										<select name="ACCOUNT_NEW_PURPOSE_CD" id="ACCOUNT_NEW_PURPOSE_CD" onChange="changeToVal002();" class="form-control input-sm" style="max-width:180px">
											<option value="">:: 거래목적 선택 ::</option>
											<?
											for($i=0; $i<count($INDI_ACCOUNT_NEW_PURPOSE_CD); $i++) {
												$selected = ($INDI_ACCOUNT_NEW_PURPOSE_CD[$IANPC_KEY[$i]]['CD'] == $AML['ACCOUNT_NEW_PURPOSE_CD']) ? 'selected' : '';
												echo "<option value='".$INDI_ACCOUNT_NEW_PURPOSE_CD[$IANPC_KEY[$i]]['CD']."' $selected>".$INDI_ACCOUNT_NEW_PURPOSE_CD[$IANPC_KEY[$i]]['NM']."</option>\n";
											}
											?>
										</select>
										<input type="hidden" id="ACCOUNT_NEW_PURPOSE_NM" name="ACCOUNT_NEW_PURPOSE_NM">
									</li>
									<li><input type="text" id="ACCOUNT_NEW_PURPOSE_OTHER" name="ACCOUNT_NEW_PURPOSE_OTHER" value="<?=$AML['ACCOUNT_NEW_PURPOSE_OTHER']?>" placeholder="거래목적 직접입력" class="form-control input-sm" style="width:180px"></li>
								</ul>
								<script>
								function changeToVal002() {
									if( $('#ACCOUNT_NEW_PURPOSE_CD').val() ) {
										var str = $('#ACCOUNT_NEW_PURPOSE_CD option:selected').text();
										$('#ACCOUNT_NEW_PURPOSE_NM').val(str);
									}
									else {
										$('#ACCOUNT_NEW_PURPOSE_NM').val('');
									}
									($('#ACCOUNT_NEW_PURPOSE_CD').val()=='A07') ? $('#ACCOUNT_NEW_PURPOSE_OTHER').attr('disabled',false) : $('#ACCOUNT_NEW_PURPOSE_OTHER').attr('disabled',true);
								}
								$('document').ready(function() { changeToVal002(); });
								</script>
							</td>
						</tr>

					</tbody>
				</table>

				<div class="text-center" style="margin-top:10px;">
					<button type="button" id="aml_submit_button" class="btn btn-md btn-warning" style="width:150px"><?=($AML['mb_no'])?'수정':'등록';?></button>
					<button type="button" onClick="this.form.reset();" class="btn btn-md btn-default" style="width:150px">폼초기화</button>
				</div>

				</form><!-- end kycform //-->

				<script>
				$('#aml_submit_button').on('click', function() {
					if( confirm('AML정보를 등록/수정 하시겠습니까?') ) {
						$('#fkyc').submit();
					}
				});
				</script>

<?
// 회원 KYC심사 페이지
include_once("member_form_aml_judge.php");
?>

				<div class="text-left" style="margin-top:50px;">
					<a href="/adm/member/member_list.test.php?<?=$query_str?>" class="btn btn-md btn-default">목록</a>
				</div>