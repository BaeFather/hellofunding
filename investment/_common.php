<?php
include_once('../common.php');

if($member['mb_id']) {
	if(in_array($member['mb_id'], $CONF['BLOCKOUT_ID'])) {
		msg_replace("관리자에 의해 서비스 이용이 제한 되었습니다.", "/bbs/logout.php");
	}
}

// 커뮤니티 사용여부
if(G5_COMMUNITY_USE === false) {
    if (!defined('G5_USE_SHOP') || !G5_USE_SHOP)
        die('<p>쇼핑몰 설치 후 이용해 주십시오.</p>');

    define('_SHOP_', true);
}
?>
