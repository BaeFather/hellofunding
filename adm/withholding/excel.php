<?php
include_once('./_common.php');
include_once('../admin.loan.function.php');
IF($idx)
{
	header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
	header("Content-Disposition: attachment; filename=헬로펀딩".DATE("YmdHis").".xls");  //File name extension was wrong
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private",false);

	$strInputText1		= "txt1";
	$strRadioText		= "txt";
	$strSelectBox		= "txt";
	$strPassword		= "txt";
	$strInputTextarea	= "txt2";

	$intSeqName	=	"hcseq";
	$strColumn	=	ARRAY(
							$intSeqName, "laddr","pname","crating","comday",
							"hholds","ddmoney","bsmoney","mdate","kbarea",
							"kbprice","kbllimit","kbcharter","examount","maxbond",
							"ltvmoney","ltvkind","rowner","tenant","content",
							"reg_date","ipaddr","recyn","hideyn","hmseq","cname","hname","hphone","mb_no","productyn"
					);

	FOR($i=0;$i<COUNT($strColumn);$i++)
	{
		${$strColumn[$i]} = "";
	}

	$strTable	=	"
	(
		SELECT st1.*, st2.cname,st2.hname,st2.hphone FROM
		(SELECT ".$intSeqName.",laddr,pname,crating,comday,hholds,ddmoney,bsmoney,mdate,kbarea,kbprice,kbllimit,kbcharter,examount,maxbond,ltvmoney,ltvkind,rowner,tenant,content,reg_date,ipaddr,recyn,hideyn,hmseq,mb_no,productyn FROM hloan_content) st1 JOIN hloan_member st2 ON st1.hmseq=st2.hmseq
	) t1";

	$strWhere	=	" WHERE hcseq='".add_str($idx)."'";
	$strOrder	=	$intSeqName;
	$intLimit1	=	0;
	$intLimit2	=	1;
	$intStrlen	=	100;

	$rowView = fr_board_view($strColumn,$strTable,"",$strWhere,$strOrder,$intLimit1,$intLimit2,$intStrlen);

	IF($rowView[0][$intSeqName])
	{
		FOR($i=0;$i<COUNT($strColumn);$i++)
		{
			${$strColumn[$i]} = $rowView[0][$strColumn[$i]];
		}
		$examountArr	=	EXPLODE(":",$examount);
		$maxbondArr		=	EXPLODE(":",$maxbond);
	} ELSE {
		alert_back("접근이 올바르지 않습니다","-1");
		EXIT;
	}
}
?>
	<table style="max-width:1000px;border-collapse:collapse;">
		<tr>
			<th style="width:150px;border:1px solid #AAA;">상태</th>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px"><?php ECHO fn_general_select($recyn,$strSelectBox,fn_recyn(),"상태 ▼","recyn","class='input02'","");?></td>
			<th style="width:150px;border:1px solid #AAA;">여신회사</th>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px"><?php ECHO INPUT_FORM("txt1","cname","input02","","",$cname);?></td>
		</tr>
		<tr>
			<th style="width:150px;border:1px solid #AAA;">담당자</th>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px"><?php ECHO INPUT_FORM("txt1","hname","input02","","",$hname);?></td>
			<th style="width:150px;border:1px solid #AAA;">담당자 연락처</th>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px"><?php ECHO INPUT_FORM("txt1","hphone","input02","","",$hphone);?></td>
		</tr>
		<tr>
			<th style="width:150px;border:1px solid #AAA;">담보물 주소</th>
			<td colspan="3" style="width:350px;border:1px solid #AAA;padding-left:15px">
				<?php ECHO INPUT_FORM($strInputText1,"laddr","input04","","required itemname='담보물 주소'",$laddr);?>
			</td>
		</tr>
		<tr>
			<th style="width:150px;border:1px solid #AAA;">원차주명</th>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px"><?php ECHO INPUT_FORM($strInputText1,"pname","input02","","required itemname='원차주명'",$pname);?></td>
			<th style="width:150px;border:1px solid #AAA;">신용등급</th>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px">
				<?php ECHO fn_general_select($crating,$strSelectBox,fn_cratring(),"등급선택 ▼","crating","class='input02' required itemname='등급선택'","");
				?>
			</td>
		</tr>
		<tr>
			<th style="width:150px;border:1px solid #AAA;">준공일</th>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px"><?php ECHO INPUT_FORM($strInputText1,"comday","input02","","required itemname='준공일'",$comday);?></td>
			<th style="width:150px;border:1px solid #AAA;">세대수</th>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px"><?php ECHO INPUT_FORM($strInputText1,"hholds","input02","","required itemname='세대수'",$hholds);?></td>
		</tr>
		<tr>
			<th style="width:150px;border:1px solid #AAA;">희망대출금액 (원)</th>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px"><?php ECHO INPUT_FORM($strInputText1,"ddmoney","input02","","required itemname='희망대출금액' OnKeyUp=\"fn_number_coma('ddmoney',this.value, $(this).index());\"",f_number($ddmoney));?></td>
			<th style="width:150px;border:1px solid #AAA;">채권설정금액 (원)</th>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px"><?php ECHO INPUT_FORM($strInputText1,"bsmoney","input02","","required itemname='채권설정금액' OnKeyUp=\"fn_number_coma('bsmoney',this.value, $(this).index());\"",f_number($bsmoney));?></td>
		</tr>
		<tr>
			<th style="width:150px;border:1px solid #AAA;">희망대출기간 (월)</th>
			<td colspan="3"  style="width:350px;border:1px solid #AAA;padding-left:15px"><?php ECHO INPUT_FORM($strInputText1,"mdate","input03","","required itemname='희망대출기간' itematt='int^1' placeholder='개월'",$mdate);?></td>
		</tr>
		<tr>
			<th style="text-align:center;border:1px solid #AAA" colspan="4">KB 시세</th>
		</tr>
		<tr>
			<th style="width:150px;border:1px solid #AAA;">전용면적 (㎡)</th>
			<th style="width:350px;border:1px solid #AAA;padding-left:15px">일반가 (원)</th>
			<th style="width:150px;border:1px solid #AAA;">하한가 (원)</th>
			<th style="width:350px;border:1px solid #AAA;padding-left:15px">전세가 (원)</th>
		</tr>
		<tr>
			<td style="width:150px;border:1px solid #AAA;text-align:center;"><?php ECHO INPUT_FORM($strInputText1,"kbarea","input02","","required itemname='전용면적' placeholder='㎡'",$kbarea);?></td>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px;text-align:center;"><?php ECHO INPUT_FORM($strInputText1,"kbprice","input02","","required itemname='일반가' OnKeyUp=\"fn_number_coma('kbprice',this.value, $(this).index());\"",f_number($kbprice));?></td>
			<td style="width:150px;border:1px solid #AAA;;text-align:center;"><?php ECHO INPUT_FORM($strInputText1,"kbllimit","input02","","required itemname='하한가' OnKeyUp=\"fn_number_coma('kbllimit',this.value, $(this).index());\"",f_number($kbllimit));?></td>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px;text-align:center;"><?php ECHO INPUT_FORM($strInputText1,"kbcharter","input02","","required itemname='전세가' OnKeyUp=\"fn_number_coma('kbcharter',this.value, $(this).index());\"",f_number($kbcharter));?></td>
		</tr>
		<tr>
			<th style="width:150px;border:1px solid #AAA;"><?php IF($RD=="3") { ?><div class="circleArea" OnClick="fn_additem_examount('plus');">+</div><?php } ?> 기대출금액 (원)</th>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px">
			<?php IF($idx) { ?>
			<?php
				FOR($i=0;$i<COUNT($examountArr);$i++)
				{
					ECHO ($i+1).")";
			?>
			<?php ECHO INPUT_FORM($strInputText1,"examount[]","input02","","required itemname='기대출금액' OnKeyUp=\"fn_number_coma('examount[]',this.value, $(this).index());\"",f_number($examountArr[$i]));?><br />
			<?php
				}
			?>
			<?php } ELSE { ?>
			<?php ECHO INPUT_FORM($strInputText1,"examount[]","input02","","required itemname='기대출금액' OnKeyUp=\"fn_number_coma('examount[]',this.value, $(this).index());\"","");?>
			<?php } ?>
			<div id="examountarea"></div>
			</td>
			<th style="width:150px;border:1px solid #AAA;">채권최고액 (원)</th>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px">
			<?php IF($idx) { ?>
			<?php
				FOR($i=0;$i<COUNT($maxbondArr);$i++)
				{
					ECHO ($i+1).")";
			?>
			<?php ECHO INPUT_FORM($strInputText1,"maxbond[]","input02","","required itemname='채권최고액' OnKeyUp=\"fn_number_coma('maxbond[]',this.value, $(this).index());\"",f_number($maxbondArr[$i]));?><br />
			<?php
				}
			?>
			<?php } ELSE { ?>
			<?php ECHO INPUT_FORM($strInputText1,"maxbond[]","input02","","required itemname='채권최고액' OnKeyUp=\"fn_number_coma('maxbond[]',this.value, $(this).index());\"","");?>
			<?php } ?>



			<div id="maxbondarea"></div>
			</td>
		</tr>
		<tr>
			<th style="width:150px;border:1px solid #AAA;">LTV</th>
			<td colspan="3" style="width:350px;border:1px solid #AAA;padding-left:15px">
				<?php ECHO INPUT_FORM($strInputText1,"ltvmoney","input03","","required itemname='LTV'",$ltvmoney);?>
				<?php IF($RD == "3") { // 등록,수정?>
				<?php ECHO fn_general_select($ltvkind,$strRadioText,fn_ltvkind(),"","ltvkind","class='radioarea'","");
				?>

				<input type="button" name="calcbtn" value="계산" class="btnCalc" OnClick="fn_calc_ltv();" />
				<?php } ?>
			</td>
		</tr>
		<script>

		</script>
		<tr>
			<th style="width:150px;border:1px solid #AAA;">소유주와의 관계</th>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px"><?php ECHO fn_general_select($rowner,$strSelectBox,fn_rowner(),"선택 ▼","rowner","class='input02'","");
				?></td>
			<th style="width:150px;border:1px solid #AAA;">세입자 유무</th>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px"><?php ECHO fn_general_select($tenant,$strRadioText,fn_tenant(),"","tenant","class='radioarea'","");
				?></td>
		</tr>
		<tr>
			<th style="width:150px;border:1px solid #AAA;">메모</th>
			<td colspan="3" style="width:350px;border:1px solid #AAA;padding-left:15px">
				<?php ECHO INPUT_FORM($strInputTextarea,"content","text01","","",$content);?>
			</td>
		</tr>
		<tr>
			<th style="width:150px;border:1px solid #AAA;">물건담당자</th>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px"><?php ECHO fn_general_select($mb_no,$strSelectBox,fn_product_manager($connect_db),":물건담당자:","mb_no","class='input02'","");
				?></td>
			<th style="width:150px;border:1px solid #AAA;">헬로펀딩 상품</th>
			<td style="width:350px;border:1px solid #AAA;padding-left:15px"><?php ECHO fn_general_select($productyn,$strSelectBox,fn_product_hello(),":헬로펀딩 상품:","productyn","class='input02'","");
				?></td>
		</tr>
	</table>

	<!-- 코멘트 //-->
	<div style="margin-top:30px; max-width:1000px;">
		<h3>COMMENT & LOG</h3>
<?php
$cres  = sql_query("SELECT idx, writer, mb_id , comment, regdate FROM hloan_comment WHERE req_idx='".add_str($idx)."' ORDER BY idx DESC");
$crows = sql_num_rows($cres);
if($crows)
{
	for($c=0,$cno=1; $c<$crows; $c++,$cno++)
	{
		$CROW = sql_fetch_array($cres);
		$delete_tag = "";
		if(($CROW['mb_id']==$member['mb_id']) || $member['mb_level']=='10') {
			$delete_tag = "<span onClick='dropComment(".$CROW['idx'].")' style='cursor:pointer;color:red'>×</span>";
		}

		$comm = nl2br(htmlSpecialChars($CROW['comment']));

?>
		<table style="font-size:12px">
			<colgroup>
				<col width="200">
				<col width="">
				<col width="30">
			</colgroup>
			<tr style='background:#FAFAFA'>
				<td align="left"><?=$CROW['writer']?> (<?php ECHO $CROW["mb_id"]?>)</td>
				<td align="right"><span style="color:#aaa"><?=$CROW['regdate']?></span></td>
				<td align="center"><?=$delete_tag?></td>
			</tr>
			<tr>
				<td colspan="3" style="padding:8px 20px"><?=$comm?></td>
			</tr>
		</table>
		<div style="height:10px;"></div>
<?php
		}
	}
?>
	</div>