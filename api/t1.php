<?php
include_once('./_common.php');
include $_SERVER["DOCUMENT_ROOT"]."/lib/function_prc.php";
//테스트페이지

$mb_hp = "01023334749";
$phone = masterEncrypt($mb_hp, false);

echo $phone."<BR>";

$phone2 = masterDecrypt($phone, false);
echo $phone2;
?>