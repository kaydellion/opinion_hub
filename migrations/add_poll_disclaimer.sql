-- Migration: Add Disclaimer Field to Polls Table
-- Date: 2025-01-16
-- Description: Add disclaimer column to polls table for optional disclaimers/terms

ALTER TABLE polls
ADD COLUMN disclaimer TEXT NULL AFTER description;



