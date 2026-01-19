-- Add total_contacts column to contact_lists table if it doesn't exist
ALTER TABLE contact_lists ADD COLUMN total_contacts INT DEFAULT 0;

-- Update existing records to have correct count
UPDATE contact_lists SET total_contacts = (
    SELECT COUNT(*) FROM contacts WHERE contacts.list_id = contact_lists.id
);


