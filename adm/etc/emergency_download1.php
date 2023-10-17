<?
###############################################################################
## 1. 각 상품별 법인투자 투자 내역
##  1) 상품명
##  2) 법인투자자명 및 사업자번호
##  3) 이자지급 금액 및 일자
##  4) 상환금액 및 일자
###############################################################################


SELECT
	C.title, A.turn, A.is_overdue,
	B.mb_id, B.mb_co_name, B.mb_co_reg_num,
	A.interest, A.interest_tax, A.local_tax, A.fee,
	A.principal,
	A.date, A.banking_date
FROM
	cf_product_give A
LEFT JOIN
	g5_member B  ON A.member_idx=B.mb_no
LEFT JOIN
	cf_product C  ON A.product_idx=C.idx
WHERE 1
	AND C.display='Y'
	AND B.member_type='2'
ORDER BY
	C.start_num,
	A.turn,
	A.is_overdue,
	A.idx;


?>