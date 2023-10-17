<style>
.new_mark { display:inline-block; font-size:8pt; padding:0 2px; line-height:12px;color:#fff; background:red; border-radius:3px; }
input.radioarea {float:left;margin-top:7px;margin-left:10px;border:1px solid #ff0000;}
label {float:left;display:block;padding:0px 5px;}
.input01 {width:150px;}
</style>
<script>
	var allchk = false;
</script>
	<!-- 검색영역 START -->
	<div style="display:inline-block;line-height:28px;margin-bottom:8px;">
		<form id="frmSearch" method="get" class="form-horizontal">
		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px">
			<li>
				<?php ECHO fn_general_select($S1,"",fn_Search_withholding_S1(),":항목선택:","S1","class='form-control input-sm' style='width:150px'","");?>
			</li>
			<li>
				<input type="text" name="STXT" value="<?php ECHO $STXT;?>"  placeholder="검색어 입력" class="form-control input-sm" style="width:250px;" />
			</li>
			<li>
				<button type="submit" class="btn btn-sm btn-warning" onClick="form_change();">검색</button>
			</li>
		</ul>
		<ul class="col-sm-10 list-inline" style="width:100%;padding-left:0;margin-bottom:5px;margin-top:20px;">
		<li>
			<?php ECHO fn_general_select($S2,"radio",fn_widhholding_prcess_1(),":대상선택:","S2","class='radioarea'","");?>
		</li>
		<li>
			<?php ECHO fn_general_select($S3,"",fn_widhholding_recyn(),":상태선택:","S3","class='form-control input01'","");?>
		</li>
		<li>
			<button type="submit" class="btn btn-sm btn-warning" onClick="fn_member_recyn_att('regfm',event);">변경</button>
		</li>
		</ul>
		</form>
	</div>
	<!-- 검색영역 E N D -->


	<!-- 리스트 START -->

	<div style="float:right; display:inline-block; font-size:12px;line-height:20px;width:100%;">
		<span style="float:left">▣ 등록 : <?=number_format($rowList[1]);?>건</span>
		<span style="float:right"><?=$page?> / <?=$total_page?> Page<span>
	</div>
	<form name="regfm" id="regfm">
	<input type="hidden" name="S1" value="<?php ECHO $S1;?>" />
	<input type="hidden" name="STXT" value="<?php ECHO $STXT;?>" />
	<input type="hidden" name="page" value="<?php ECHO $page;?>" />
	<table class="table table-striped table-bordered table-hover" style="padding-top:0; font-size:12px;">
		<caption style="padding:0"><?=$g5['title']?> 목록</caption>
		<thead>
		<tr>
			<th scope="col" style="text-align:center;width:60px"><a href="#none" OnClick="fn_all_check();">[선택]</a></th>
			<th scope="col" style="text-align:center;width:60px">NO.</th>
			<th scope="col" style="text-align:center;">요청일</th>
			<th scope="col" style="text-align:center;">회원구분</th>
			<th scope="col" style="text-align:center;">상호명<br />(회원명)</th>
			<th scope="col" style="text-align:center;">사업자등록번호<br />(주민등록번호)</th>
			<th scope="col" style="text-align:center;">신청시작일</th>
			<th scope="col" style="text-align:center;">신청종료일</th>
			<th scope="col" style="text-align:center;">유형</th>
			<th scope="col" style="text-align:center;">이메일</th>
			<th scope="col" style="text-align:center;">메모</th>
			<th scope="col" style="text-align:center;">관리</th>
		</tr>
		</thead>
		<tbody>
<?php
	IF($rowList[1] > 0)
	{
		$bunho=($rowList[1])-(($page-1) * $num_per_page); //리스트의 넘버수
		FOR($i=0;$i<COUNT($rowList[2]);$i++)
		{
			unset($RowLink);

			FOR($j=0;$j<COUNT($strColumn);$j++)
			{
				${$strColumn[$j]} = $rowList[2][$i][$j];
			}
			$RowLink = $gstrPHPSELF."?KD=".$KD."&RD=2&idx=".$cwrseq."&page=".$page."&S1=".$S1."&STXT=".$STXT;

			$strMemoTxt = "";

			IF(strlen($content) > 0) { $strMemoTxt = "<a href=\"#none\" OnClick=\"check_view('div".$i."');\">보기</a>"; }

			IF($member_type == "1")
			{
				$mb_juminOr = masterDecrypt($mb_jumin, true);
				$mb_jumin	= SUBSTR($mb_juminOr,0,6)."*******";
			} else {
				$mb_juminOr = $mb_jumin;
			}
?>
		<tr>
			<td align="center"><input type="checkbox" name="chk[]" value="<?php ECHO $cwrseq;?>" /></td>
			<td align="center"><?=$bunho?></td>
			<td align="center"><a href="<?php ECHO $RowLink;?>"><?=$reg_date?></a></td>
			<td align="center"><?=fn_general_txt($member_type,fn_widhholding_member_type())?></td>
			<td align="center"><a href="/adm/member/member_list.php?key_search=A.mb_no&keyword=<?php ECHO $mb_no;?>"><?=$mb_name?></a></td>
			<td align="center"><span id="jumin_<?php ECHO $i;?>" onMouseOver="swapText('jumin_<?php ECHO $i;?>','<?php ECHO $mb_juminOr;?>');" onMouseOut="swapText('jumin_<?php ECHO $i;?>','<?php ECHO $mb_jumin;?>');" style="cursor:pointer" onClick="copy_trackback('<?php ECHO $mb_juminOr;?>');"><?php ECHO $mb_jumin;?></span></td>
			<td align="center"><?=$s_date?></td>
			<td align="center"><?=$e_date?></td>
			<td align="center"><?=fn_general_txt($rkind,fn_widhholding_rkind())?></td>
			<td align="center"><?=$mb_email?></td>
			<td align="center"><?=$strMemoTxt?></td>
			<td align="center"><?php ECHO fn_general_select($recyn,$strRadioText,fn_widhholding_recyn(),"관리 ▼","recyn","class='input02'","");?></td>
			<div id="div<?php ECHO $i;?>" style="display:none;"><?php ECHO $content;?></div>
		</tr>
<?php
			$bunho--;
		}
	} ELSE {
?>

		<tr>
			<td colspan="12" align="center">검색된 데이터가 없습니다.</td>
		</tr>

<?php
	}
?>
	</table>
	</form>
	<!-- 리스트 E N D -->

<?php
echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $qstr.'&amp;page=');
?>