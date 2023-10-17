<style>
input.radioarea {float:left;margin-top:7px;margin-left:10px;}
.selectarea {width:180px;padding:5px 0;}
label {float:left;display:block;padding:5px 5px;}
.fred {color:#ff0000;}
.circleArea {position:absolute;margin-left:0px;background-color:#0000ff;border-radius:30px;color:#FFF;font-weight:bold;width:20px;height:20px;border:0px;cursor:pointer;}
.input01 {width:100%;border-radius:3px;line-height:24px;font-size:14px;text-align:left;border:1px solid #333;}
.input02 {width:95%;line-height:24px;font-size:15px;text-align:left;border:1px solid #CCC;margin:0 auto;}
.input02::placeholder {text-align:center;}
.input03 {width:30%;line-height:24px;font-size:15px;text-align:left;border:1px solid #CCC;margin:0 auto;}
.input03::placeholder {text-align:center;}
.input04 {width:98%;line-height:24px;font-size:15px;text-align:left;border:1px solid #CCC;margin:0 auto;}
.text01 {width:98%;line-height:24px;height:100px;font-size:15px;text-align:left;border:1px solid #CCC;margin:0 auto;}
.ui-datepicker-calendar { display: none; }
</style>

<script>
	$(function() {
		$('.dateym').datepicker( {
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true,
            dateFormat: 'yy-mm',
			monthNamesShort: ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'],
			dayNamesShort: ['일' ,'월', '화', '수', '목', '금', '토'],
			closeText: "적용",
			currentText: "오늘",
            onClose: function(dateText, inst) {
                var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
                var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
                $(this).datepicker('setDate', new Date(year, month, 1));
            },
            beforeShow : function(input, inst) {
                var datestr;
                if ((datestr = $(this).val()).length > 0) {
                    year = datestr.substring(datestr.length-4, datestr.length);
                    month = jQuery.inArray(datestr.substring(0, datestr.length-5), $(this).datepicker('option', 'monthNamesShort'));
                    $(this).datepicker('option', 'defaultDate', new Date(year, month, 1));
                    $(this).datepicker('setDate', new Date(year, month, 1));
                }
            }
        });
	});
</script>


	<div style="max-width:1000px;text-align:center;">
		<h3><?=$print_gubun?></h3>
	</div>
	<form name="regfm" id="regfm">
	<input type="hidden" name="kind" value="<?php ECHO $strKind;?>" />
	<input type="hidden" name="idx" value="<?php ECHO $idx;?>" />
	<input type="hidden" name="S1" value="<?php ECHO $S1;?>" />
	<input type="hidden" name="STXT" value="<?php ECHO $STXT;?>" />
	<input type="hidden" name="page" value="<?php ECHO $page;?>" />
	<table class="table table-bordered" style="max-width:1000px;">
		<colgroup>
			<col width="15%">
			<col width="35%">
			<col width="15%">
			<col width="35%">
		</colgroup>
		<tr>
			<th>구분</th>
			<td colspan="3" class="tdtop">
				<?php ECHO fn_general_select($member_type,$strRadioText,fn_widhholding_member_type(),"구분 ▼","member_type","class='radioarea'","");?>
			</td>
		</tr>
		<tr>
			<th class="tdtop">상호명(이름)</th>
			<td><?php ECHO INPUT_FORM($strInputText2,"mb_name","input02","","",$mb_name);?></td>
			<th class="tdtop">사업자등록번호<br />(주민등록번호)</th>
			<td>
			<?php// IF($RD == "2") { ?>
			<span id="jumin_<?php ECHO $i;?>" onMouseOver="swapText('jumin_<?php ECHO $i;?>','<?php ECHO $mb_juminOr;?>');" onMouseOut="swapText('jumin_<?php ECHO $i;?>','<?php ECHO $mb_jumin;?>');" style="cursor:pointer" onClick="copy_trackback('<?php ECHO $mb_juminOr;?>');"><?php ECHO $mb_jumin;?></span>
			<?php// } ELSE { ?>
			<?php// ECHO INPUT_FORM($strInputText2,"mb_jumin","input02","","",$mb_jumin);?>
			<?php// } ?>
			</td>
		</tr>
		<tr>
			<th>신청년월</th>
			<td><?php ECHO INPUT_FORM($strInputText1,"s_date","input03 dateym","","required itemname='신청년월'",$s_date);?> 월 ~ <?php ECHO INPUT_FORM($strInputText1,"e_date","input03 dateym","","required itemname='신청년월'",$e_date);?> 월</td>
			<th>유형</th>
			<td><?php ECHO fn_general_select($rkind,$strRadioText,fn_widhholding_rkind(),"유형 ▼","rkind","class='radioarea'","");?></td>
		</tr>
		<tr>
			<th>이메일</th>
			<td colspan="3"><?php ECHO INPUT_FORM($strInputText1,"mb_email","input02","","required itemname='이메일'",$mb_email);?></td>
		</tr>
		<tr>
			<th>메모</th>
			<td colspan="3"><?php ECHO INPUT_FORM($strInputTextarea,"content","text01","","required itemname='메모' ",$content);?></td>
		</tr>
		<tr>
			<th>관리</th>
			<td colspan="3" class="tdL"><?php ECHO fn_general_select($recyn,$strRadioText,fn_widhholding_recyn(),"구분 ▼","recyn","class='radioarea'","");?></td>
		</tr>
	</table>


	<?php IF($RD == "2") { ?>
	<div style="max-width:1000px;text-align:right;">
		<button type="button" id="list_button" onClick="location.href='<?php ECHO $_SERVER["PHP_SELF"].$strListUrl?>&RD=3&idx=<?php ECHO $idx;?>';" class="btn btn-default">수정하기</button>
		&nbsp;&nbsp;
		<button type="button" id="list_button" onClick="location.href='<?php ECHO $_SERVER["PHP_SELF"].$strListUrl?>';" class="btn btn-default">목록보기</button>
	</div>
	<?php } ?>
	<?php IF($RD == "3") { ?>
	<div style="max-width:1000px;text-align:right;">
		<button type="button" id="list_button" onClick="check_w_form('regfm',event);return false;" class="btn btn-default"><?php ECHO $strBtnTxt;?></button>
		&nbsp;&nbsp;
		<button type="button" id="list_button" onClick="location.href='<?php ECHO $_SERVER["PHP_SELF"].$strListUrl?>';" class="btn btn-default">목록보기</button>
	</div>
	<?php } ?>
	</form>

	</div>
	<!-- 코멘트 //-->

	<div style='width:100%;margin-top:50px;border-bottom:1px dashed #ccc'></div>