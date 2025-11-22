-- Add last_message tracking fields to conversations table if they don't exist

-- Add last_message_text column
ALTER TABLE `conversations`
ADD COLUMN IF NOT EXISTS `last_message_text` text DEFAULT NULL AFTER `parent_id`;

-- Add last_message_at column
ALTER TABLE `conversations`
ADD COLUMN IF NOT EXISTS `last_message_at` timestamp NULL DEFAULT NULL AFTER `last_message_text`;

-- Add unread count columns if they don't exist
ALTER TABLE `conversations`
ADD COLUMN IF NOT EXISTS `teacher_unread_count` int(11) DEFAULT 0 AFTER `last_message_at`;

ALTER TABLE `conversations`
ADD COLUMN IF NOT EXISTS `parent_unread_count` int(11) DEFAULT 0 AFTER `teacher_unread_count`;
