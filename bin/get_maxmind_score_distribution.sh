#!/bin/bash
#Returns something like:
#status,count,score_max,score_min,score_avg,score_std,score_var,risk_max,risk_min,risk_avg,risk_std,risk_var
#active,50311,9.99,0.00,3.902,2.384,5.682,99.00,0.1,24.587,32.207,1037.315
#locked,7859,9.99,0.00,8.782,2.479,6.146,99.00,0.10,75.161,33.317,1110.027
echo "
SELECT
  account_status AS status,
  COUNT(*) AS count,
  MAX(a1.account_value) AS score_max,
  MIN(a1.account_value) AS score_min,
  ROUND(AVG(a1.account_value), 3) AS score_avg,
  ROUND(STD(a1.account_value), 3) AS score_std,
  ROUND(VARIANCE(a1.account_value), 3) AS score_var,
  MAX(a2.account_value) AS risk_max,
  MIN(a2.account_value) AS risk_min,
  ROUND(AVG(a2.account_value), 3) AS risk_avg,
  ROUND(STD(a2.account_value), 3) AS risk_std,
  ROUND(VARIANCE(a2.account_value), 3) AS risk_var
FROM accounts
  LEFT JOIN accounts_ext AS a1 USING (account_id)
  LEFT JOIN accounts_ext AS a2 USING (account_id)
WHERE account_ima = 'client'
AND a1.account_key = 'maxmind_score'
AND a1.account_value != ''
AND a2.account_key = 'maxmind_riskscore'
AND a2.account_value != ''
GROUP BY account_status;
" | mysql my
