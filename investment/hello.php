<?php
include_once('./_common.php');


$g5['title'] = '투자하기';
// $g5['top_bn'] = "/images/investment/sub_hello.jpg";
$g5['top_bn_alt'] = "헬로펀딩 안정성 투자자가 작은 금액들을 모아서 함께 투자하는 새로운 투자 방식입니다.";

if ($co['co_include_head'])
    @include_once($co['co_include_head']);
else
    include_once('./_head.php');

?>
<!-- 본문내용 START -->

<div id="content">
	<div class="location"><b class="blue">헬로펀딩</b></div>
	<img src="../images/investment/hello.jpg" alt="헬로펀딩 안정성" />
</div>


<!-- 본문내용 E N D -->
<?php

if ($co['co_include_tail'])
    @include_once($co['co_include_tail']);
else
    include_once('./_tail.php');
?>