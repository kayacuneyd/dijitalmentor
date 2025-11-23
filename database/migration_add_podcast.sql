-- ============================================
-- Dijital Mentor Podcast System Migration
-- Created: 2025-01-23
-- Description: Creates podcast_episodes table and related indexes
-- ============================================

-- Create podcast_episodes table
CREATE TABLE IF NOT EXISTS `podcast_episodes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(191) UNIQUE NOT NULL COMMENT 'URL-friendly identifier',
  `title` VARCHAR(255) NOT NULL COMMENT 'Episode title',
  `description` TEXT COMMENT 'Episode description/summary',
  `topic_prompt` TEXT COMMENT 'Original topic input from admin',

  -- Media files
  `audio_url` VARCHAR(500) COMMENT 'Cloudflare R2 audio file URL',
  `cover_image_url` VARCHAR(500) COMMENT 'Cloudflare R2 cover image URL',
  `duration_seconds` INT UNSIGNED COMMENT 'Audio duration in seconds',

  -- Content
  `transcript` LONGTEXT COMMENT 'Full episode transcript',
  `script_markdown` LONGTEXT COMMENT 'Original generated script (before TTS)',

  -- External platforms
  `youtube_video_id` VARCHAR(50) COMMENT 'YouTube video ID',
  `spotify_episode_id` VARCHAR(100) COMMENT 'Spotify episode ID (if available)',

  -- Processing status
  `processing_status` ENUM('pending', 'generating', 'completed', 'failed') DEFAULT 'pending' COMMENT 'Pipeline processing status',
  `github_run_id` VARCHAR(50) COMMENT 'GitHub Actions workflow run ID',
  `error_message` TEXT COMMENT 'Error details if processing failed',

  -- Publishing
  `publish_date` DATE COMMENT 'Scheduled/actual publish date',
  `is_published` TINYINT(1) DEFAULT 0 COMMENT '0=draft, 1=published',

  -- Metadata
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Indexes for performance
  INDEX `idx_status` (`processing_status`),
  INDEX `idx_published` (`is_published`, `publish_date`),
  INDEX `idx_created` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Podcast episodes with automation metadata';

-- ============================================
-- Sample data for testing (optional - remove for production)
-- ============================================
-- INSERT INTO `podcast_episodes` (
--   `slug`,
--   `title`,
--   `description`,
--   `topic_prompt`,
--   `processing_status`,
--   `is_published`,
--   `publish_date`
-- ) VALUES (
--   'test-episode-almanya-egitim-sistemi',
--   'Almanya Eğitim Sistemine Giriş',
--   'Almanya\'daki eğitim sisteminin temellerini ve Türk ailelerin dikkat etmesi gereken noktaları ele alıyoruz.',
--   'Almanya eğitim sistemini ve Türk ailelerin dikkat etmesi gereken noktaları anlat',
--   'pending',
--   0,
--   CURDATE()
-- );

-- ============================================
-- Verification queries
-- ============================================
-- SELECT COUNT(*) as total_episodes FROM podcast_episodes;
-- SELECT * FROM podcast_episodes WHERE processing_status = 'pending' LIMIT 5;
-- DESCRIBE podcast_episodes;
