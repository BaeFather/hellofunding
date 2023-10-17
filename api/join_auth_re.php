<?php
include_once('./_common.php');
include $_SERVER["DOCUMENT_ROOT"]."/lib/function_prc.php";

$strPost =	ARRAY(
				ARRAY("sval","","Y")
			 );

/// 초기 변수 선언
FOR($i=0;$i<COUNT($strPost);$i++)
{
	${$strPost[$i]} = $_POST[$strPost[$i][0]];
}

//변수값 확인
FOR($i=0;$i<COUNT($strPost);$i++)
{
	IF($strPost[$i][1] > 0)
	{
		FOR($j=0;$j<COUNT($_POST[$strPost[$i][0]]);$j++)
		{
			IF($j > 0)
			{
				${$strPost[$i][0]} .=  ",";
			}
			${$strPost[$i][0]} .= $_POST[$strPost[$i][0]][$j];
		}

	} ELSE {
		IF($strPost[$i][2] == "Y")
		{
			IF($_POST[$strPost[$i][0]]<>"")
			{
				${$strPost[$i][0]} = $_POST[$strPost[$i][0]];
			} ELSE {
				${$strPost[$i][0]} = $_POST[$strPost[$i][0]];
				$objval = ARRAY("retcode"=>"X");
				ECHO json_encode($objval);
				EXIT;
			}
		} ELSE {
			${$strPost[$i][0]} = $_POST[$strPost[$i][0]];
		}
	}
}

$strArr			=	EXPLODE("|",$sval);

$app_user_id		=	$strArr[0];
$tfName				=	masterDecrypt($strArr[1],false);
$tfResidentNumber	=	masterDecrypt($strArr[2],false);
$tfEmail			=	masterDecrypt($strArr[3],false);
$tfPhoneOffice		=	masterDecrypt($strArr[4],false);
$tfPhoneNumber		=	masterDecrypt($strArr[5],false);

$strTable	    = "g5_member";
$SeqName		= "mb_no";

/*임시*/
$app_user_id	=	"1111";

$strColumn		=	ARRAY($SeqName);

FOR($i=0;$i<COUNT($strColumn);$i++)
{
	${$strColumn[$i]} = "";
}

$strWhere		=	" WHERE kakaopay_userid='".add_str($app_user_id)."' AND mb_level = '1' ";
$strOrder		=	$SeqName." DESC";
$intLimit1		=	0;
$intLimit2		=	1;
$intStrlen		=	100;

$rowView = fr_board_view($strColumn,$strTable,"",$strWhere,$strOrder,$intLimit1,$intLimit2,$intStrlen,$connect_for);

IF(@$rowView[0][$SeqName])
{
	FOR($i=0;$i<COUNT($strColumn);$i++)
	{
		${$strColumn[$i]} = @$rowView[0][$strColumn[$i]];
	}
}

IF($mb_no)
{
	/* 이미 가입 */
	$retCode = "NON";
	$retalert = STR_REPLACE("+"," ",urlencode("이미 가입된 이력이 있습니다."));
	$retval = "";

} ELSE {
	/* 실명인증 및 문자전송 */
	include_once('./ajax.auth1.php');

	IF($strReturnCode == "0000")
	{
		$retCode = "OK";
		$retalert = "";
		$retval = ARRAY("res"=>$strResSeq, "rep"=>$strRepSeq);
	} ELSE {
		$retCode = "NON";
		$retalert = STR_REPLACE("+"," ",urlencode("문자 인증이 실패하였습니다. 다시 시도하여 주십시오."));
		$retval = "";
	}
}
sql_close($connect_for);

$objval = ARRAY("retcode"=>$retCode,"retalert"=>$retalert,"retval"=>$retval);
ECHO json_encode($objval);
?>