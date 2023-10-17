<?php
include_once('./_common.php');
include $_SERVER["DOCUMENT_ROOT"]."/lib/function_prc.php";


$strPost =	ARRAY("phone");

FOR($i=0;$i<COUNT($strPost);$i++)
{
	${$strPost[$i]} = $_POST[$strPost[$i]];
}

IF(!is_numeric($phone) || strlen($phone) < 11)
{
	$objval = ARRAY("retcode"=>"X1","retalert"=>STR_REPLACE("+"," ",urlencode("접근이 올바르지 않습니다. 다시 시도하여 주십시오")));
	ECHO json_encode($objval);

} ELSE {

	$strTable	    = "g5_member";
	$SeqName		= "mb_no";

	$strColumn		=	ARRAY($SeqName);

	FOR($i=0;$i<COUNT($strColumn);$i++)
	{
		${$strColumn[$i]} = "";
	}

	$strWhere		=	" WHERE mb_hp='".masterEncrypt($phone, false)."' AND mb_level IN ('1','2')";
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
	sql_close($connect_for);

	$mb_no = "";
	IF($mb_no)
	{
		$retCode = "NON";
		$retalert = STR_REPLACE("+"," ",urlencode("이미 가입된 연락처 입니다. 연락처를 다시 입력하여 주십시오."));
	} ELSE {
		$retCode = "OK";
		$retalert = "";
	}

	$objval = ARRAY("retcode"=>$retCode,"retalert"=>$retalert);
	ECHO json_encode($objval);
}
?>