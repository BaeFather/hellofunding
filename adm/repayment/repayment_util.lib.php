<?

function newGetMember($mb_no, $date='') {

	if(!$mb_no) { return false; }

	global $g5;
	global $link;

	if(@$g5['connect_db']) $link = $g5['connect_db'];

	$field = "mb_no, mb_id, mb_level, member_type, member_investor_type, is_creditor, is_owner_operator, mb_name, mb_co_name, receive_method, remit_fee, remit_fee_sdate,";
	$field.= "bank_code, account_num, bank_private_name, ";
	$field.= "va_bank_code2, virtual_account2, va_private_name2, ";
	$field.= "va_bank_code, virtual_account, va_private_name, ";
	$field.= "edit_datetime";


	$sql = "
		SELECT
			{$field}
		FROM
			g5_member
		WHERE 1
			AND mb_no='".$mb_no."'
			AND mb_level BETWEEN 1 AND 5";
	$DATA = sql_fetch($sql, true, $link);

	if($date=='' || $date==date('Y-m-d')) {

		$MB = $DATA;
		$table = 'g5_member';

	}
	else {
		if($DATA['mb_no']) {
			if($DATA['edit_datetime']) {
				$sql = "
					SELECT
						{$field}
					FROM
						g5_member_history
					WHERE 1
						AND mb_no='".$mb_no."'
						AND mb_level BETWEEN 1 AND 5 AND LEFT(edit_datetime,10) <= '".$date."'
					ORDER BY
						edit_datetime DESC
					LIMIT 1";
				if($MB_LOG = sql_fetch($sql, true, $link)) {
					$MB = $MB_LOG;
					$table = 'g5_member_history';
				}
				else {
					$MB = $DATA;
					$table = 'g5_member';
				}
			}
			else {
				$MB = $DATA;
				$table = 'g5_member';
			}
		}
		else {
			$sql = "
				SELECT
					{$field}, mb_leave_date
				FROM
					g5_member_drop WHERE mb_no='".$mb_no."'";
			if($DROP_MB = sql_fetch($sql, true, $link)) {
				$MB = $DROP_MB;
				$MB['is_drop'] = '1';
				$table = "g5_member_drop";
			}
		}
	}

	if( $MB['account_num'] ) {
		$MB['account_num'] = masterDecrypt($MB['account_num'], false);
	}

/*
	if(preg_match("/g5_member_history/", $table)) {
		$fcolor = '#2222FF';
	}
	else if(preg_match("/g5_member_drop/", $table)) {
		$fcolor = '#FF2222';
	}

	echo "<div style='color:{$fcolor};font-size:12px'>".$mb_no." : " . $table . "</div><br/>\n";
*/

	return $MB;

}

?>