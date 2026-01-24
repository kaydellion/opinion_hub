-- Migration: Add Question Description and Image Fields
-- Date: 2025-01-15
-- Description: Add description and image fields to poll_questions table

-- Add question description field
ALTER TABLE poll_questions ADD COLUMN question_description TEXT NULL AFTER question_text;

-- Add question image field
ALTER TABLE poll_questions ADD COLUMN question_image VARCHAR(255) NULL AFTER question_description;

-- Add index for question_image for faster queries
ALTER TABLE poll_questions ADD INDEX idx_question_image (question_image);




