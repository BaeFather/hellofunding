<?
###############################################################################
## 2. 상품별 이자지급액
##  1) 상품명
##  2) 투자자 이자 지급액
###############################################################################


SELECT
  B.idx,
	B.title,
	B.recruit_amount,
	B.loan_interest_rate,
	B.loan_start_date,
	B.loan_end_date,
	IFNULL(SUM(A.interest_tax),0) AS interest_tax,
	IFNULL(SUM(A.local_tax),0) AS local_tax,
	IFNULL(SUM(A.fee),0) AS fee,
	IFNULL(SUM(A.interest),0) AS interest
FROM
	cf_product_give A
LEFT JOIN
	cf_product B  ON A.product_idx=B.idx
WHERE 1
	AND B.display='Y'
GROUP BY
	A.product_idx
ORDER BY
	start_num ASC;


?>