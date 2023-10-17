<?php
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
//아이디 처리
?>
<?php
include_once('./_common.php');
include_once('../admin.loan.function.php');
?>
<?php
	$kind =& $_POST["kind"];

	IF($kind == "save")
	{
		$strPost = ARRAY(
							ARRAY("idx","",""),ARRAY("page","","Y"),ARRAY("S1","",""),ARRAY("STXT","",""),
							ARRAY("member_type","","Y"),ARRAY("mb_no","",""),ARRAY("mb_name","",""),ARRAY("mb_jumin","",""),ARRAY("mb_email","","Y"),
							ARRAY("s_date","","Y"),ARRAY("e_date","","Y"),ARRAY("rkind","","Y"),ARRAY("content","","Y"),
							ARRAY("recyn","","Y")
					);
	} ELSEIF($kind == "update") {
		$strPost = ARRAY(
							ARRAY("idx","","Y"),ARRAY("page","","Y"),ARRAY("S1","",""),ARRAY("STXT","",""),
							ARRAY("member_type","","Y"),ARRAY("mb_no","",""),ARRAY("mb_name","",""),ARRAY("mb_jumin","",""),ARRAY("mb_email","","Y"),
							ARRAY("s_date","","Y"),ARRAY("e_date","","Y"),ARRAY("rkind","","Y"),ARRAY("content","",""),
							ARRAY("recyn","","Y")
					);
	} ELSE {
		$objval = ARRAY("retcode"=>"X","retalert"=>STR_REPLACE("+"," ",urlencode("접근이 올바르지 않습니다. 다시 시도하여 주십시오")),"retval"=>"");
	}

	FOR($i=0;$i<COUNT($strPost);$i++)
	{
		IF($strPost[$i][1] > 0)
		{
			$strPostTarget = "";
			FOR($j=0;$j<COUNT($_POST[$strPost[$i][0]]);$j++)
			{
				$strPostVal = "";
				IF($j > 0)
				{
					$strPostTarget .=  ":";
					//${$strPost[$i][0]} .=  ",";
				}
				$strPostVal		 =& $_POST[$strPost[$i][0]][$j];
				$strPostTarget	.= replace_integer($strPostVal);
				//${$strPost[$i][0]} .= $_POST[$strPost[$i][0]][$j];
			}
			${$strPost[$i][0]} = $strPostTarget;

		} ELSE {
			IF($strPost[$i][2] == "Y")
			{
				IF($_POST[$strPost[$i][0]]<>"")
				{
					${$strPost[$i][0]} = $_POST[$strPost[$i][0]];
				} ELSE {
					$objval = ARRAY("retcode"=>"X","retalert"=>STR_REPLACE("+"," ",urlencode("값이 올바르지 않습니다. 다시 시도하여 주십시오")),"retval"=>"");
					ECHO json_encode($objval);
					EXIT;
				}
			} ELSE {
				${$strPost[$i][0]} = $_POST[$strPost[$i][0]];
			}
		}
	}
	$gstrNdate	=	DATE("Y-m-d H:i:s");

	IF($kind == "save" || $kind == "update")
	{

		$strColumn	= ARRAY(
							"member_type","mb_no",
							"mb_email","s_date","e_date","rkind","content",
							"recyn"
						);

		$strValues = ARRAY(
						$member_type, $mb_no,
						$mb_email, $s_date, $e_date, $rkind, $content,
						$recyn
					);

		IF($kind == "save")
		{
			$strColumn[] = "reg_date";
			$strValues[] = $gstrNdate;
		}
		IF($mb_name)
		{
			$strColumn[] = "mb_name";
			$strValues[] = $mb_name;
		}
		IF($mb_jumin)
		{
			$strColumn[] = "mb_jumin";
			$strValues[] = $mb_jumin;
		}
		$strTable		=	"cf_withholding_request";
		$SeqName	=	"cwrseq";

		$INSERT_ID = fn_general_query_update($kind,$strColumn,$strValues,$strTable,$SeqName,replace_integer($idx),"",$connect_db);
		sql_close($connect_db);

		$strlink = "&S1=".$S1."&STXT=".$STXT."&page=".$page;	// 추가 리턴변수

		SWITCH($kind)
		{
			CASE "save" : $strRet = fn_general_process_link($kind, "2", $strlink); BREAK;
			CASE "update" : $strRet = fn_general_process_link($kind, "2", $strlink); BREAK;
			CASE "del" : $strRet = fn_general_process_link($kind, "1", $strlink); BREAK;
		}
		$objval = ARRAY("retcode"=>"OK","retalert"=>STR_REPLACE("+"," ",urlencode("글이 정상 ".$strRet[0]." 되었습니다")),"retval"=>"/adm/withholding/?".$strRet[1]);
		ECHO json_encode($objval);
		EXIT;
	}
?>