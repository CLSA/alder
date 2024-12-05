SELECT review.user_id, CONCAT( scan_type.name, scan_type.side ), interview.id, study_phase_id, uid
INTO @user_id, @scan_type, @interview_id, @study_phase_id, @uid
FROM review
JOIN exam ON review.exam_id = exam.id
JOIN scan_type ON exam.scan_type_id = scan_type.id
JOIN interview ON exam.interview_id = interview.id
JOIN patrick_cenozo.participant ON interview.participant_id = participant.id
WHERE review.id = @review_id;


SET @prev_interview_id = NULL;
select interview.id INTO @prev_interview_id
FROM patrick_cenozo.participant
JOIN interview ON participant.id = interview.participant_id
JOIN exam ON interview.id = exam.interview_id
JOIN review ON exam.id = review.exam_id
WHERE interview.study_phase_id = @study_phase_id
AND review.user_id = @user_id
AND participant.uid < @uid
ORDER BY participant.uid
LIMIT 1;

SET @prev_interview_review_id = NULL;
SELECT review.id INTO @prev_interview_review_id
FROM interview
JOIN exam ON interview.id = exam.interview_id
JOIN scan_type ON exam.scan_type_id = scan_type.id
JOIN review ON exam.id = review.exam_id
WHERE interview.id = @prev_interview_id
AND review.user_id = @user_id
ORDER BY scan_type.name, scan_type.side
LIMIT 1;


SET @prev_exam_id = NULL;
select exam.id INTO @prev_exam_id
FROM exam
JOIN scan_type ON exam.scan_type_id = scan_type.id
JOIN review ON exam.id = review.exam_id
WHERE exam.interview_id = @interview_id
AND review.user_id = @user_id
AND CONCAT( scan_type.name, scan_type.side ) < @scan_type
ORDER BY scan_type.name, scan_type.side
LIMIT 1;

SET @prev_exam_review_id = NULL;
SELECT review.id INTO @prev_exam_review_id
FROM exam
JOIN scan_type ON exam.scan_type_id = scan_type.id
JOIN review ON exam.id = review.exam_id
WHERE exam.id = @prev_exam_id
AND review.user_id = @user_id
ORDER BY scan_type.name, scan_type.side
LIMIT 1;


SET @next_exam_id = NULL;
select exam.id INTO @next_exam_id
FROM exam
JOIN scan_type ON exam.scan_type_id = scan_type.id
JOIN review ON exam.id = review.exam_id
WHERE exam.interview_id = @interview_id
AND review.user_id = @user_id
AND CONCAT( scan_type.name, scan_type.side ) > @scan_type
ORDER BY scan_type.name, scan_type.side
LIMIT 1;

SET @next_exam_review_id = NULL;
SELECT review.id INTO @next_exam_review_id
FROM exam
JOIN scan_type ON exam.scan_type_id = scan_type.id
JOIN review ON exam.id = review.exam_id
WHERE exam.id = @next_exam_id
AND review.user_id = @user_id
ORDER BY scan_type.name, scan_type.side
LIMIT 1;


SET @next_interview_id = NULL;
select participant.id INTO @next_interview_id
FROM patrick_cenozo.participant
JOIN interview ON participant.id = interview.participant_id
JOIN exam ON interview.id = exam.interview_id
JOIN review ON exam.id = review.exam_id
WHERE interview.study_phase_id = @study_phase_id
AND review.user_id = @user_id
AND participant.uid > @uid
ORDER BY participant.uid
LIMIT 1;

SET @next_interview_review_id = NULL;
SELECT review.id INTO @next_interview_review_id
FROM interview
JOIN exam ON interview.id = exam.interview_id
JOIN scan_type ON exam.scan_type_id = scan_type.id
JOIN review ON exam.id = review.exam_id
WHERE interview.participant_id = @next_interview_id
AND review.user_id = @user_id
ORDER BY scan_type.name, scan_type.side
LIMIT 1;
