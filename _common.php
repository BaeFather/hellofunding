<?php
include_once('./common.php');

if($member['mb_id']) {
	if(in_array($member['mb_id'], $CONF['BLOCKOUT_ID'])) {
		msg_replace("관리자에 의해 서비스 이용이 제한 되었습니다.", "/bbs/logout.php");
	}
}
