<?
exit;

########################################################
## 영화표 배정 2017-12-21
## 헬로펀딩 가입 이벤트
## 무료영화권 1매 지급
########################################################

include_once("_common.php");
include_once('../../lib/sms.lib.php');

$target_date = '2017-12-29';
if(date("Y-m-d")!=$target_date) exit;

$action_key = date('YmdHi');


/*
$ID = array('hoho3gg','noblejm','parkanhyun','dong2024','cheon8523','novice1993','suyeon6305','eddie96','storyfield','danny_70@naver.com','ykj97842484','sunks79','kyeoktooga','maniapop','umeo07','gusandktl','skybbk','cp1440','net100423','crisp925','jingoo79','goddo11','jms184926','bky001@hanmail.net','podosoul','swchoi64','kim1158','gigum2','gmatter2015','syoon5','rokmcjee','twotom','ahs2420','oldboybe','psw1827','guava48','nang419','skyrla','hatsal0709','kkamdol823','najjyoung','lancer92');		// 2017-12-04 가입자
$ID = array('jsth8391','jheun000','mahria','qlllllp0804','leeet2002','bbaboya','test1515','peacean1','wjdgusdlwlq','schtroumpf','crystalcha13','rlaaltns96','h12345777','ping6688','lojoo12','mi0090','gangnangkong','dirt97','pjs100jum','pluto68','bombyou','girls1470','yeejin22','wjdtjd000','psyche0807');		// 2017-12-05 가입자
$ID = array('comman79','aticus00','leetaesuk631','kty3899','euninoh','zzang3220','ysb0825','dbwlssla52','dnlzhs','joongk','zigum85','gotnrgkdud59','gotnrgkdud','fairy234611','meangel','thswhdtn38@naver.com','sun151','kjuny98','feelck','cult0209','bpkin2','honor217','niceparadise','dptmyang','yosozu','ladychoi','sionyong','gkstlekf','gkrry11','hideking','i5on9i','hyan102','dlwls26','mathsea','ansu66','namju0832','jwek12','hwan0806');		// 2017-12-06 가입자
$ID = array('surfleeds','ppjk3322','zzang9440','lim10241024','hai7605','havanas','syh9712','jeremy45','violet1015','beloved','heaven4866','jung3315','fokfoong','yhsa3852','aminobrain','kms7452','miyu00','ooooio','hol599','guswn55211','dmswn3787','cowper21','naishyo1','kjo1006','hongyun0623','myskyzero','realssamzang','min5242dol');		// 2017-12-07 가입자
$ID = array('net8051','kooooonz','tallguy','minculture17','0304@hanmail.net','ck4617','nsk7023','indivisi','yghamily2','tialdaka18','ahsprl');	// 2017-12-08 가입자
$ID = array('ok123123ok','lan914','jsk1234','fj4110','yhj1426','byeonnarae','tictactou','aspic60','roradora','jubi7777','mjsr2710','ihlee1021','rlaskfkd1','eugeneye','augustin','boriking','ones1stlove','hheejeong','soncki','lee2131','rpy1005','csw9493','dudwh123123','xxx1012','csh2030','bird3325','iami020','gkdud577','neo9080','hotsolove','eroom010','smkim630417');		// 2017-12-11 가입자
$ID = array('k3045484','eneh33','sodian','beremo12','goodnews0707','biddan','duddl1190','air103','kills111','eagle10001','guava48');		// 2017-12-12 가입자
$ID = array('siajy123','kangmincho','alrud0080@hanmail.net','cmg0312','wochon12','duddl1128','thereps','chicap','tngi15963','crazytta','lje3525','mornsea1987','cool0331','age1432','na59mj','oranre','jskim@wowtv.co.kr','s701x0','hst44144','hsh8312','lee45141','flower9908','lsy7998');		// 2017-12-13 가입자
$ID = array('happily29','yinshiku','ksykkk67','ksmarth77','hong426','huy9513','anzse3322','koracdad135','gree13','karmast','wlsdl19','wonjung2','hoon2go@naver.com','jmg90jmg','khj7503','peachtex@gmail.com','leejjkk','dicecasts','betrigger94');		// 2017-12-14 가입자
$ID = array('ohjooseuk','k8581599','jeomgi','norooway@naver.com','hye0819','ssmashing','qhtm10','seals20','pooh1600','jks2493','alstjr123c','wedidd','Zksh220','kjh.com@gmail.com','dew0902','white5775@naver.com','jdesign@wowtv.co.kr','jisun9295@daum.net','jftrej123@naver.com','kes6906','gunners12','siselia102','snipu2','virtuousje','choin0625','heesuk7578','leekunhyuk','damysalang','nabassm','fleur315','khjun94','pk2134','davidlean','superman26','shpark5512','kalskso','rntjrdud','hnh0413','rkdxovnd41','kmd5111','abitor97','oak9191','mik061','da0365','kimlih1021','syveloper','forthjunior','gidgork','nuriduri85','anyway1102','kills222','hak6717','jm8803','ksmarth77');	// 2017-12-18 가입자 (15일 가입자 포함)
$ID = array('leebora1219','dudtjs424','b681212','qkreotjs3','bhjeona525','aoue2323','myyoujh','consw12','peter6022','gift1star');		// 2017-12-20 가입자
$ID = array('nadozal','seizesun','felix86','namukun','haru94','soledat','normal2','minjoo','applefund','sikim1220','hl2obb','kse0804','jisele');		// 2017-12-21 가입자
$ID = array('jujuvv21','jbs34000','dooyoo1','darongika7','ads2821','ads0865','mozaga','highenough9');
$ID = array('sondonggun','mys6944','kgb1217','dy8803','kdy8803','tae747','jm7389','hhhh5025','ksy0243','kkndfock','milhouse77','hanjae925','rmawoals7');
$ID = array('tjsdud6918','tjrehs1','dmsrn325','dalhankim','loveleelma','choonnan','apekf7','tear99','Kch3595','tarot8','lee7397','k2749508','arcanemania','kane9463',
            'taehun77','psh5870','woopshun@naver.com','hallo71','lmk721217','mangphos','tydgml88','lovebloom','jw10137','sw10137','gksdusgh','kje1213','whiteowner',
						'yujin0412','pacisjune','dschoi4080','gunogun','penzals','kor910809','y2k74747','kjhin2','ho8030','choiku','jmw0255','nam3005@naver.com','bohwang','ni394419@gmail.com',
						'nakpower','lkjin3@hanmail.net','dream07202','auh263','louoscho','mstmst54');
$ID = array('jsuook','bjh1st','dentchoi','grayth','aalborg95','curious8504','sht0033','hotlsr703','pinefor','mationt1','howwonn','choihs5916','ej951013','codingc','dlwhdtjq0307','dully2000');		//2017-12-28
*/

$ID = array('alice5','dudtjrl79','sssfk1230','hojh12','everbreeze','sjs741003','enifsosd','enifsosc','enifsosb','enifsosa','leeoy418','hka774','hangzzz','dawonlending','highest','migukman','qweruiop369');		//2017-12-29





$EVENT['no']      = '171129';
$EVENT['title']   = '헬로펀딩 신규회원가입 이벤트 영화티켓 지급';
$EVENT['caption'] = '신규가입자 무료영화권 1매 지급';

$send_datetime =  $target_date . " 12:30:00";		// 메세지 발송일시

$give_count = 1;

$sms_msg = "[헬로펀딩 신규회원가입 이벤트 영화티켓 지급안내]\n"
         . "당신의 설레는 내일, 헬로펀딩입니다.\n"
         . "\n"
         . "헬로펀딩 신규회원가입해주신 회원님을 진심으로 환영합니다.\n"
         . "\n"
         . "영화티켓 : {TICKET1}\n"
         . "\n"
         . "◆ 영화티켓 사용방법 ◆\n"
         . "1. www.ecomovie.co.kr에서 영화티켓 등록 후 사용 가능합니다.\n"
         . "(영화티켓 등록은 2017년 12월 31일까지 가능하며, 등록 후 30일 이내 사용 가능합니다.)\n"
         . "2. 예매 시 전국 CGV, 메가박스, 롯데시네마, 기타 지역 영화관 선택이 가능합니다.\n"
         . "3. 일부 특수관의 경우 관람이 제한됩니다.\n"
         . "(아이맥스, 디지털3D, M2관, 샤롯데 등)\n"
         . "4. 당일 예매 시 영화 시작 3시간 전에는 예매를 해주세요.\n"
         . "\n"
         . "※ 유의사항\n"
         . "\n"
         . "1. 해당 티켓은 양도 및 매매가 불가합니다. (양도 및 매매티켓 사용시 취소처리됩니다.)\n"
				 . "\n"
         . "[헬로펀딩]\n"
         . "고객센터 : 1588-6760\n"
         . "홈페이지 : www.hellofunding.co.kr\n";



for($i=0; $i<count($ID); $i++) {

	$ID[$i] = trim($ID[$i]);
	$MEM = sql_fetch("SELECT mb_no, mb_hp, mb_name FROM g5_member WHERE mb_id='".$ID[$i]."' AND mb_leave_date=''");
	$MEM['mb_hp'] = masterDecrypt($MEM['mb_hp'], false);

	$USED = sql_fetch("SELECT COUNT(idx) AS cnt_idx FROM event_reward_coupon WHERE event_no='".$EVENT['no']."' AND mb_no='".$MEM['mb_no']."'");

	if($USED['cnt_idx']) {
		echo $ID[$i]." (". $MEM['mb_name'] .") 회원에게 당 이벤트로 배정된 쿠폰내역이 ".$USED['cnt_idx']."건 존재함<br>\n";
	}
	else {

		$res  = sql_query("SELECT idx, coupon_no FROM event_reward_coupon WHERE event_no IS NULL AND mb_no='' AND valid_date='2017-12-31' ORDER BY idx ASC LIMIT $give_count");
		$rows = $res->num_rows;

		$coupon_idx_arr = "";
		for($j=0,$k=1; $j<$rows; $j++,$k++) {
			$COUPON[$j] = sql_fetch_array($res);
			$coupon_idx_arr.= "'".$COUPON[$j]['idx']."'";
			$coupon_idx_arr.= ($k<$rows) ? "," : "";
		}

		if(count($COUPON)==$give_count) {

			$sql = "
				UPDATE
					event_reward_coupon
				SET
					event_no = '".$EVENT['no']."',
					event_title = '".addSlashes($EVENT['title'])."',
					event_caption = '".addSlashes($EVENT['caption'])."',
					mb_no = '".$MEM['mb_no']."',
					give_date = '".$send_datetime."'
				WHERE
					idx IN($coupon_idx_arr)";
			//echo $sql."<br>\n";
			if($_REQUEST['action']==$action_key) {
				sql_query($sql);
			}

			$send_msg = $sms_msg;
			$send_msg = preg_replace("/{TICKET1}/is", $COUPON[0]['coupon_no'], $send_msg);

			debug_flush($send_msg."<br><br>\n");
			if($_REQUEST['action']==$action_key) {
				unit_sms_send($_admin_sms_number, $MEM['mb_hp'], $send_msg, $send_datetime);
			}

		}

		unset($COUPON);

	}

}

?>