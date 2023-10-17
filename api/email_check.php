<?php
include_once('./_common.php');
include $_SERVER["DOCUMENT_ROOT"]."/lib/function_prc.php";

$strPost =	ARRAY("email");

FOR($i=0;$i<COUNT($strPost);$i++)
{
	${$strPost[$i]} = $_POST[$strPost[$i]];
}

IF(!$email)
{
	$objval = ARRAY("retcode"=>"X","retalert"=>STR_REPLACE("+"," ",urlencode("접근이 올바르지 않습니다. 다시 시도하여 주십시오")));
	ECHO json_encode($objval);

} ELSE {

	$strTable	    = "g5_member";
	$SeqName		= "mb_no";

	$strColumn		=	ARRAY($SeqName);

	FOR($i=0;$i<COUNT($strColumn);$i++)
	{
		${$strColumn[$i]} = "";
	}

	$strWhere		=	" WHERE mb_email='".add_str($email)."' AND mb_level IN ('1','2')";
	$strOrder		=	$SeqName." DESC";
	$intLimit1		=	0;
	$intLimit2		=	1;
	$intStrlen		=	100;

	$rowView = fr_board_view($strColumn,$strTable,"",$strWhere,$strOrder,$intLimit1,$intLimit2,$intStrlen,$connect_for);

	IF(@$rowView[0][$SeqName])
	{
		FOR($i=0;$i<COUNT($strColumn);$i++)
		{
			${$strColumn[$i]} = $rowView[0][$strColumn[$i]];
		}
	}
	sql_close($connect_for);

	IF($mb_no)
	{
		$retCode = "NON";
		$retalert = STR_REPLACE("+"," ",urlencode("이미 가입된 이메일 입니다. 이메일을 다시 입력하여 주십시오."));
	} ELSE {
		$retCode = "OK";
		$retalert = "";
	}

	$objval = ARRAY("retcode"=>$retCode,"retalert"=>$retalert);
	ECHO json_encode($objval);
}
?>