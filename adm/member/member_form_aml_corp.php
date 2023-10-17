<?
###############################################################################
## 법인용 AML 정보 입력폼 (회원정보입력/수정페이지에서 로드)
###############################################################################

include_once("_common.php");

include_once(G5_PATH . '/data/aml_inc/kofiu_code.inc.php');
include_once(G5_PATH . '/data/aml_inc/aml_array.inc.php');

$AML_TABLE = "g5_member_aml_corp";

$AML = sql_fetch("SELECT * FROM {$AML_TABLE} WHERE 1 AND mb_no = '".$MB['mb_no']."'");

if($AML['mb_no']) {

	if(!$AML['CUSTOMER_ENG_NM']) {
		$AML['CUSTOMER_ENG_NM'] = $MB['mb_co_name_eng'];
	}

	// 법인소재국가
	if($MB['zip_num'] && $MB['mb_addr1'] && $MB['mb_addr_jibeon']) {
		$AML['COUNTRY_CD'] = $AML['LIVE_COUNTRY_CD'] = 'KR';
	}

}
else {

	$AML['CUSTOMER_TP_CD']   = ($MB['corp_noneprofit']) ? '01' : '08';		// 고객유형코드 01:비영리단체 02:고액자산가 03:신용불량자 04:금융기관 05:국가.지방자치단체 06:UN산하 국제자선기구 07:상장회사 08:기타
	$AML['TMS_CUSTOMER_DIV'] = '02';																			// 01:개인 02:법인 03:개인사업자

	$AML['CUSTOMER_NM']      = $MB['mb_co_name'];															// 법인명
	$AML['CUSTOMER_ENG_NM']  = $MB['mb_co_name_eng'];													// 법인명(영문)

	$AML['NONPROFIT_CORP_YN']     = ($MB['corp_noneprofit']) ? 'Y' : 'N';									// 비영리단체여부
	$AML['NONPROFIT_CORP_REG_YN'] = ($MB['corp_noneprofit_policy_doc']) ? 'Y' : 'N';			// 비영리법인등록여부

	$AML['RNM_NO']      = preg_replace("/(-| )/", "", $MB['mb_co_reg_num']);				// 실명번호 -> 사업자등록번호
	$AML['CORP_REG_NO'] = preg_replace("/(-| )/", "", $MB['corp_num']);							// 법인등록번호
	$AML['PERMIT_NO']   = preg_replace("/(-| )/", "", $MB['mb_co_reg_num']);				// 사업자등록번호

	$AML['CEO_RNM_NO_DIV'] = '03';		// 법인대표자실명구분
	$AML['CEO_RNM_NO']     = preg_replace("/(-| )/", "", $MB['mb_co_reg_num']);		// 대표자 실명번호 -> 사업자등록번호

	if($MB['corp_forigner']=='1') {
		$AML['FOREIGNER_DIV'] = 'B';		// A:내국인 B:외국인
	}
	else {
		$AML['COUNTRY_CD'] = $AML['LIVE_COUNTRY_CD'] = $AML['CEO_COUNTRY_CD'] = $AML['CREATE_COUNTRY_CD'] = 'KR';
		$AML['FOREIGNER_DIV'] = 'A';
	}

	$AML['CREATE_DD'] = preg_replace("/-/", "", $MB['corp_rdate']);			// 법인설립일
	$AML['CEO_NM'] = $MB['mb_co_owner'];			// 법인 대표자명

}

?>
				<form id="fkyc" name="fkyc" method="post" target="axFrame" action="member_form_aml_corp.update.php">
				<input type="hidden" id="mb_no" name="mb_no" value="<?=$MB['mb_no']?>">
				<input type="hidden" id="mb_f_no" name="mb_f_no" value="<?=$MB['mb_f_no']?>">
				<input type="hidden" id="TMS_CUSTOMER_DIV" name="TMS_CUSTOMER_DIV" value="<?=$AML['TMS_CUSTOMER_DIV']?>">
				<input type="hidden" id="CORP_REG_NO" name="CORP_REG_NO" value="<?=$AML['CORP_REG_NO']?>">
				<input type="hidden" id="PERMIT_NO" name="PERMIT_NO" value="<?=$AML['PERMIT_NO']?>">
				<input type="hidden" id="CEO_NM" name="CEO_NM" value="<?=$AML['CEO_NM']?>">
				<input type="hidden" id="CREATE_DD" name="CREATE_DD" value="<?=$AML['CREATE_DD']?>">


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
							<th scope="row" class="tit">법인명</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><input type="text" id="CUSTOMER_NM" name="CUSTOMER_NM" value="<?=$AML['CUSTOMER_NM']?>" class="frm_input input-sm" onKeyup="get_owner_info();"></li>
								</ul>
							</td>
							<th scope="row" class="tit">법인영문명</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><input type="text" id="CUSTOMER_ENG_NM" name="CUSTOMER_ENG_NM" value="<?=$AML['CUSTOMER_ENG_NM']?>" class="frm_input input-sm"></li>
								</ul>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit">가상통화취급사업자</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><label class="radio-inline"><input type="radio" name="VIRTUAL_MONEY_BUSINESS_YN" id="VIRTUAL_MONEY_BUSINESS_YN" value="Y" <?if($AML['VIRTUAL_MONEY_BUSINESS_YN']=='Y'){ echo 'checked'; }?>> 예</label></li>
									<li><label class="radio-inline"><input type="radio" name="VIRTUAL_MONEY_BUSINESS_YN" id="VIRTUAL_MONEY_BUSINESS_YN" value="N" <?if($AML['VIRTUAL_MONEY_BUSINESS_YN']=='N'){ echo 'checked'; }?>> 아니오</label></li>
									<li><label class="radio-inline"><input type="radio" name="VIRTUAL_MONEY_BUSINESS_YN" id="VIRTUAL_MONEY_BUSINESS_YN" value=""  <?if($AML['VIRTUAL_MONEY_BUSINESS_YN']==''){ echo 'checked'; }?>> 정보없음</label></li>
								</ul>
							</td>
							<th scope="row" class="tit">가상통화취급여부</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><label class="radio-inline"><input type="radio" name="VIRTUAL_MONEY_HANDLE_CD" id="VIRTUAL_MONEY_HANDLE_CD" value="Y" <?=($AML['VIRTUAL_MONEY_HANDLE_CD']=='Y')?'checked':''?>> 예</label></li>
									<li><label class="radio-inline"><input type="radio" name="VIRTUAL_MONEY_HANDLE_CD" id="VIRTUAL_MONEY_HANDLE_CD" value="N" <?=($AML['VIRTUAL_MONEY_HANDLE_CD']=='N')?'checked':''?>> 아니오</label></li>
									<li><label class="radio-inline"><input type="radio" name="VIRTUAL_MONEY_HANDLE_CD" id="VIRTUAL_MONEY_HANDLE_CD" value=""  <?=($AML['VIRTUAL_MONEY_HANDLE_CD']=='')?'checked':''?>> 정보없음</label></li>
								</ul>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit">비영리법인여부</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><label class="radio-inline"><input type="radio" name="NONPROFIT_CORP_YN" id="NONPROFIT_CORP_YN" value="Y" <?=($AML['NONPROFIT_CORP_YN']=='Y')?'checked':''?>> 예</label></li>
									<li><label class="radio-inline"><input type="radio" name="NONPROFIT_CORP_YN" id="NONPROFIT_CORP_YN" value="N" <?=($AML['NONPROFIT_CORP_YN']=='N')?'checked':''?>> 아니오</label></li>
									<li><label class="radio-inline"><input type="radio" name="NONPROFIT_CORP_YN" id="NONPROFIT_CORP_YN" value=""  <?=($AML['NONPROFIT_CORP_YN']=='')?'checked':''?>> 정보없음</label></li>
								</ul>
							</td>
							<th scope="row" class="tit">비영리법인등록여부</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><label class="radio-inline"><input type="radio" name="NONPROFIT_CORP_REG_YN" id="NONPROFIT_CORP_REG_YN" value="Y" <?=($AML['NONPROFIT_CORP_REG_YN']=='Y')?'checked':''?>> 예</label></li>
									<li><label class="radio-inline"><input type="radio" name="NONPROFIT_CORP_REG_YN" id="NONPROFIT_CORP_REG_YN" value="N" <?=($AML['NONPROFIT_CORP_REG_YN']=='N')?'checked':''?>> 아니오</label></li>
									<li><label class="radio-inline"><input type="radio" name="NONPROFIT_CORP_REG_YN" id="NONPROFIT_CORP_REG_YN" value=""  <?=($AML['NONPROFIT_CORP_REG_YN']=='')?'checked':''?>> 정보없음</label></li>
								</ul>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit">법인국적/소재국가</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li>
										<select name="COUNTRY_CD" id="COUNTRY_CD" class="form-control input-sm" style="max-width:180px" alt="법인국적">
											<option value="">:: 법인국적선택 ::</option>
<?
	for($i=0; $i<$KOFIU_COUNTRY_COUNT; $i++) {
		$selected = ($KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['CD']==$AML['COUNTRY_CD']) ? 'selected' : '';
		echo "<option value='".$KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['CD']."' $selected>".$KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['NM']."</option>\n";
	}
?>
										</select>
									</li>
									<li>
										<select name="LIVE_COUNTRY_CD" id="LIVE_COUNTRY_CD" class="form-control input-sm" style="max-width:180px" alt="소재국가">
											<option value="">:: 소재국가선택 ::</option>
<?
	for($i=0; $i<$KOFIU_COUNTRY_COUNT; $i++) {
		$selected = ($KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['CD']==$AML['LIVE_COUNTRY_CD']) ? 'selected' : '';
		echo "<option value='".$KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['CD']."' $selected>".$KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['NM']."</option>\n";
	}
?>
										</select>
										<input type="hidden" id="FOREIGNER_DIV" name="FOREIGNER_DIV" value="<?=$AML['FOREIGNER_DIV']?>" alt="법인속성(내.외국 구분)">
										<input type="hidden" id="LIVE_YN" name="LIVE_YN" value="<?=$AML['LIVE_YN']?>" alt="국내거주여부">
									</li>
								</ul>
							</td>

							<th scope="row" class="tit">업종코드</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li>
										<select name="INDUSTRY_CD" id="INDUSTRY_CD" class="form-control input-sm" style="width:100%">
											<option value="">:: 업종선택 ::</option>
<?
	for($i=0; $i<$KOFIU_INDUSTRY_COUNT; $i++) {
		$selected = ($KOFIU_INDUSTRY_CODE[$KICD_ARRKEY[$i]]['P_CD']==$AML['INDUSTRY_CD']) ? 'selected' : '';
		echo "<option value='".$KOFIU_INDUSTRY_CODE[$KICD_ARRKEY[$i]]['P_CD']."' $selected>" . $KOFIU_INDUSTRY_CODE[$KICD_ARRKEY[$i]]['C_NM'] ." &gt; ". $KOFIU_INDUSTRY_CODE[$KICD_ARRKEY[$i]]['P_NM'] . "</option>\n";
	}
?>
										</select>
									</li>
								</ul>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit">상장여부</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><label class="radio-inline"><input type="radio" name="LSTNG_YN" id="LSTNG_YN" value="Y" <?=($AML['LSTNG_YN']=='Y')?'checked':''?>> 상장</label></li>
									<li><label class="radio-inline"><input type="radio" name="LSTNG_YN" id="LSTNG_YN" value="N" <?=($AML['LSTNG_YN']=='N')?'checked':''?>> 비상장</label></li>
									<li><label class="radio-inline"><input type="radio" name="LSTNG_YN" id="LSTNG_YN" value=""  <?=($AML['LSTNG_YN']=='')?'checked':''?>> 정보없음</label></li>
									<li>
										<select name="LSTNG_DIV" id="LSTNG_DIV" class="form-control input-sm" style="max-width:180px">
											<option value="">:: 상장구분 선택 ::</option>
											<?
											for($i=0; $i<count($AML_LSTNG_DIV); $i++) {
												$selected = ($AML_LSTNG_DIV[$ALD_KEY[$i]]['CD']==$AML['LSTNG_DIV']) ? 'selected' : '';
												echo "<option value='".$AML_LSTNG_DIV[$ALD_KEY[$i]]['CD']."' $selected>".$AML_LSTNG_DIV[$ALD_KEY[$i]]['NM']."</option>\n";
											}
											?>
										</select>
									</li>
								</ul>
							</td>
							<th scope="row" class="tit">실명번호</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><label class="radio-inline"><input type="radio" name="RNM_NO_DIV" id="RNM_NO_DIV" value="03" checked> 사업자등록번호</label></li>
									<li><input type="text" name="RNM_NO" id="RNM_NO" value="<?=$AML['RNM_NO']?>" placeholder="실명번호입력" class="form-control input-sm"></li>
								</ul>
							</td>
						</tr>

						<tr>
							<th scope="row" class="tit">법인대표자 영문명</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><input type="text" name="CEO_ENG_LAST_NM" id="CEO_ENG_LAST_NM" value="<?=$AML['CEO_ENG_LAST_NM']?>" placeholder="영문명:성" onkeyup="this.value=this.value.replace(/[#ㄱ-ㅎ|ㅏ-ㅣ|가-힣]/g, '')" class="form-control input-sm" style="ime-mode:disabled;text-transform:uppercase; width:180px;"></li>
									<li><input type="text" name="CEO_ENG_FIRST_NM" id="CEO_ENG_FIRST_NM" value="<?=$AML['CEO_ENG_FIRST_NM']?>" placeholder="영문명:이름" onkeyup="this.value=this.value.replace(/[#ㄱ-ㅎ|ㅏ-ㅣ|가-힣]/g, '')" class="form-control input-sm" style="ime-mode:disabled;text-transform:uppercase; width:180px;"></li>
								</ul>
							</td>
							<th scope="row" class="tit">법인대표자 국적</th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li>
										<select name="CEO_COUNTRY_CD" id="CEO_COUNTRY_CD" class="form-control input-sm" style="max-width:180px">
											<option value="">:: 법인대표자 국적 선택 ::</option>
											<?
											for($i=0; $i<$KOFIU_COUNTRY_COUNT; $i++) {
												$selected = ($KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['CD']==$AML['CEO_COUNTRY_CD']) ? 'selected' : '';
												echo "<option value='".$KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['CD']."' $selected>".$KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['NM']."</option>\n";
											}
											?>
										</select>
									</li>
								</ul>
							</td>
						</tr>

						<tr>
							<td colspan="4" style="background:#EEE; border-top:2px solid #FFF; border-bottom:1px solid #CCC"><strong style="margin-left:10px;">CDD(고객확인의무)</strong></td>
						</tr>
						<tr>
							<th scope="row" class="tit"><label>법인대표자 실명번호</label></th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li>
										<select name="CEO_RNM_NO_DIV" id="CEO_RNM_NO_DIV" class="form-control input-sm" style="max-width:180px">
											<option value="">:: 실명번호구분선택 ::</option>
											<?
											for($i=0; $i<count($AML_RNM_NO_DIV); $i++) {
												$selected = ($AML_RNM_NO_DIV[$ARND_KEY[$i]]['CD'] == $AML['CEO_RNM_NO_DIV']) ? 'selected' : '';
												echo "<option value='".$AML_RNM_NO_DIV[$ARND_KEY[$i]]['CD']."' $selected>".$AML_RNM_NO_DIV[$ARND_KEY[$i]]['NM']."</option>\n";
											}
											?>
										</select>
									</li>
									<li><input type="text" name="CEO_RNM_NO" id="CEO_RNM_NO" value="<?=$AML['CEO_RNM_NO']?>" placeholder="실명번호입력" class="form-control input-sm" style="width:180px"></li>
								</ul>
							</td>
							<th scope="row" class="tit"><label>법인대표자주소</label></th>
							<td>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="width:100px;">주소지국가</li>
									<li>
										<select name="CEO_ADDR_COUNTRY_CD" id="CEO_ADDR_COUNTRY_CD" class="form-control input-sm" style="max-width:180px">
											<option value="">:: 주소지국가 선택::</option>
											<?
											for($i=0; $i<$KOFIU_COUNTRY_COUNT; $i++) {
												$selected = ($KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['CD']==$AML['CEO_ADDR_COUNTRY_CD']) ? 'selected' : '';
												echo "<option value='".$KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['CD']."' $selected>".$KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['NM']."</option>\n";
											}
											?>
										</select>
									</li>
								</ul>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="width:100px;">우편번호</li>
									<li><input type="text" name="CEO_POST_NO" id="CEO_POST_NO" value="<?=$AML['CEO_POST_NO']?>" onClick="win_zip('fkyc', 'CEO_POST_NO', 'CEO_ADDR', 'CEO_DTL_ADDR', '', 'CEO_ADDR_jibeon');" maxlength="6" readonly class="frm_input input-sm"></li>
									<li><button type="button" onClick="win_zip('fkyc', 'CEO_POST_NO', 'CEO_ADDR', 'CEO_DTL_ADDR', '', 'CEO_ADDR_jibeon');" class="btn btn-sm btn-default">주소검색</button></li>
								</ul>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="width:100px;">도로명주소</li>
									<li><input type="text" name="CEO_ADDR" id="CEO_ADDR" value="<?=$AML['CEO_ADDR']?>" readonly class="frm_input input-sm" style="width:350px;"></li>
								</ul>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="width:100px;"><span style="color:#AAA">지번주소</span></li>
									<li><input type="text" name="CEO_ADDR_jibeon" id="CEO_ADDR_jibeon" value="<?=$AML['CEO_ADDR_jibeon']?>" class="frm_input input-sm" style="width:350px;"></li>
								</ul>
								<ul class="list-inline" style="margin:0 0 2px 0">
									<li style="width:100px;">상세주소</li>
									<li><input type="text" name="CEO_DTL_ADDR" id="CEO_DTL_ADDR" value="<?=$AML['CEO_DTL_ADDR']?>" class="frm_input input-sm" style="width:350px;"></li>
								</ul>
							</td>
						</tr>

						<tr>
							<td colspan="4" style="background:#EEE; border-top:2px solid #FFF; border-bottom:1px solid #CCC"><strong style="margin-left:10px;">EDD(강화된 고객확인의무)</strong></td>
						</tr>
						<tr>
							<th scope="row" class="tit"><label>기업규모</label></th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li><label class="radio-inline"><input type="radio" name="COMPANY_SIZE_DIV" id="COMPANY_SIZE_DIV" value="01" <?=($AML['COMPANY_SIZE_DIV']=='01')?'checked':''?>> 대기업</label></li>
									<li><label class="radio-inline"><input type="radio" name="COMPANY_SIZE_DIV" id="COMPANY_SIZE_DIV" value="02" <?=($AML['COMPANY_SIZE_DIV']=='02')?'checked':''?>> 중소기업</label></li>
									<li><label class="radio-inline"><input type="radio" name="COMPANY_SIZE_DIV" id="COMPANY_SIZE_DIV" value=""   <?=($AML['COMPANY_SIZE_DIV']=='')?'checked':''?>> 정보없음</label></li>
								</ul>
							</td>
							<th scope="row" class="tit"><label>법인설립지국가</label></th>
							<td>
								<ul class="list-inline" style="margin:0">
									<li>
										<select name="CREATE_COUNTRY_CD" id="CREATE_COUNTRY_CD" class="form-control input-sm" style="max-width:180px">
											<option value="">:: 법인설립지국가 선택 ::</option>
											<?
											for($i=0; $i<$KOFIU_COUNTRY_COUNT; $i++) {
												$selected = ($KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['CD'] == $AML['CREATE_COUNTRY_CD']) ? 'selected' : '';
												echo "<option value='".$KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['CD']."' $selected>".$KOFIU_COUNTRY_CODE[$KCCD_ARRKEY[$i]]['NM']."</option>\n";
											}
											?>
										</select>
									</li>
								</ul>
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
												for($i=0; $i<count($CORP_TRAN_FUND_SOURCE_DIV); $i++) {
													$selected = ($CORP_TRAN_FUND_SOURCE_DIV[$CTFSD_KEY[$i]]['CD'] == $AML['TRAN_FUND_SOURCE_DIV']) ? 'selected' : '';
													echo "<option value='".$CORP_TRAN_FUND_SOURCE_DIV[$CTFSD_KEY[$i]]['CD']."' $selected>".$CORP_TRAN_FUND_SOURCE_DIV[$CTFSD_KEY[$i]]['NM']."</option>\n";
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
									($('#TRAN_FUND_SOURCE_DIV').val()=='B99') ? $('#TRAN_FUND_SOURCE_OTHER').attr('disabled',false) : $('#TRAN_FUND_SOURCE_OTHER').attr('disabled',true);
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
											for($i=0; $i<count($CORP_ACCOUNT_NEW_PURPOSE_CD); $i++) {
												$selected = ($CORP_ACCOUNT_NEW_PURPOSE_CD[$CANPC_KEY[$i]]['CD'] == $AML['ACCOUNT_NEW_PURPOSE_CD']) ? 'selected' : '';
												echo "<option value='".$CORP_ACCOUNT_NEW_PURPOSE_CD[$CANPC_KEY[$i]]['CD']."' $selected>".$CORP_ACCOUNT_NEW_PURPOSE_CD[$CANPC_KEY[$i]]['NM']."</option>\n";
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
									($('#ACCOUNT_NEW_PURPOSE_CD').val()=='B99') ? $('#ACCOUNT_NEW_PURPOSE_OTHER').attr('disabled',false) : $('#ACCOUNT_NEW_PURPOSE_OTHER').attr('disabled',true);
								}
								$('document').ready(function() { changeToVal002(); });
								</script>
							</td>
						</tr>
						<tr>
							<th scope="row" class="tit"><label>법인실소유자구분</label></th>
							<td colspan="3">
								<ul class="list-inline" style="margin:0">
									<li>
										<select name="REAL_OWNR_CHK_CD" id="REAL_OWNR_CHK_CD" class="form-control input-sm" style="max-width:180px">
											<option value="">:: 법인실소유자구분 선택 ::</option>
											<?
											for($i=0; $i<count($AML_REAL_OWNR_CHK_CD); $i++) {
												$selected = ($AML_REAL_OWNR_CHK_CD[$AROCC_KEY[$i]]['CD'] == $AML['REAL_OWNR_CHK_CD']) ? 'selected' : '';
												echo "<option value='".$AML_REAL_OWNR_CHK_CD[$AROCC_KEY[$i]]['CD']."' $selected>".$AML_REAL_OWNR_CHK_CD[$AROCC_KEY[$i]]['NM']."</option>\n";
											}
											?>
										</select>
									</li>
								</ul>
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