-- Add slug column to polls table
ALTER TABLE polls 
ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title,
ADD INDEX idx_slug (slug);

-- Generate slugs for existing polls
UPDATE polls 
SET slug = LOWER(CONCAT(
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
        SUBSTRING(title, 1, 50), 
        ' ', '-'), 
        '?', ''), 
        '!', ''), 
        '.', ''), 
        ',', ''),
    '-',
    id
))
WHERE slug IS NULL OR slug = '';
