-- Grant access to poll 2 for user 1
INSERT INTO poll_results_access (user_id, poll_id, amount_paid) 
VALUES (1, 2, 50.00)
ON DUPLICATE KEY UPDATE amount_paid = 50.00;

-- Check if it was inserted
SELECT * FROM poll_results_access WHERE user_id = 1 AND poll_id = 2;
