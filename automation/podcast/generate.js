#!/usr/bin/env node

/**
 * Dijital Mentor Podcast Generator
 * Main orchestration script for automated podcast creation
 */

import 'dotenv/config';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import AnthropicClient from './clients/anthropic.js';
import ElevenLabsClient from './clients/elevenlabs.js';
import R2Client from './clients/r2.js';
import YouTubeClient from './clients/youtube.js';
import { mixAudio, downloadMusic, getAudioDuration } from './utils/ffmpeg.js';
import { updateEpisodeViaWebhook, logProgress } from './utils/helpers.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const TEMP_DIR = path.join(__dirname, 'temp');
const OUTPUT_DIR = path.join(__dirname, 'output');

// Ensure directories exist
if (!fs.existsSync(TEMP_DIR)) fs.mkdirSync(TEMP_DIR, { recursive: true });
if (!fs.existsSync(OUTPUT_DIR)) fs.mkdirSync(OUTPUT_DIR, { recursive: true });

async function generatePodcast(episodeId, topicPrompt, title = '', description = '') {
  const safeName = `episode-${episodeId}`;
  const startTime = Date.now();

  logProgress(episodeId, 'pending', '🚀 Podcast oluşturma başlıyor...');

  try {
    // Update status to 'generating'
    await updateEpisodeViaWebhook(episodeId, { status: 'generating' });

    // Step 1: Generate script with Anthropic Claude
    logProgress(episodeId, 'generating', '📝 Senaryo yazılıyor (Claude API)...');
    const anthropic = new AnthropicClient();
    const script = await anthropic.generatePodcastScript(topicPrompt, title, description);

    logProgress(episodeId, 'generating', `✅ Senaryo hazır (${script.length} karakter)`);

    // Step 2: Text-to-Speech with ElevenLabs
    logProgress(episodeId, 'generating', '🎙️ Seslendirme yapılıyor (ElevenLabs)...');
    const elevenlabs = new ElevenLabsClient();
    const rawAudioPath = path.join(TEMP_DIR, `${safeName}_raw.mp3`);
    await elevenlabs.textToSpeech(script, rawAudioPath);

    logProgress(episodeId, 'generating', '✅ Seslendirme tamamlandı');

    // Step 3: Download background music (if needed)
    logProgress(episodeId, 'generating', '🎵 Fon müziği hazırlanıyor...');
    const musicPath = await downloadMusic(TEMP_DIR);

    // Step 4: Mix audio with FFmpeg
    logProgress(episodeId, 'generating', '🎚️ Ses mixleniyor (FFmpeg)...');
    const finalAudioPath = path.join(OUTPUT_DIR, `${safeName}.mp3`);
    await mixAudio(rawAudioPath, musicPath, finalAudioPath);

    // Get audio duration
    const durationSeconds = await getAudioDuration(finalAudioPath);
    logProgress(episodeId, 'generating', `✅ Ses hazır (${Math.floor(durationSeconds / 60)}:${String(durationSeconds % 60).padStart(2, '0')})`);

    // Step 5: Generate thumbnail (simplified - solid color with text)
    logProgress(episodeId, 'generating', '🖼️ Thumbnail oluşturuluyor...');
    const thumbnailPath = path.join(OUTPUT_DIR, `${safeName}_thumb.jpg`);
    // For now, skip complex image generation - can add DALL-E later
    // await generateThumbnail(title || topicPrompt, thumbnailPath);

    // Step 6: Upload to Cloudflare R2
    logProgress(episodeId, 'generating', '☁️ Dosyalar R2\'ye yükleniyor...');
    const r2 = new R2Client();

    const audioUrl = await r2.uploadFile(finalAudioPath, `episodes/${safeName}.mp3`, 'audio/mpeg');
    logProgress(episodeId, 'generating', `✅ Audio yüklendi: ${audioUrl}`);

    let coverImageUrl = null;
    if (fs.existsSync(thumbnailPath)) {
      coverImageUrl = await r2.uploadFile(thumbnailPath, `covers/${safeName}.jpg`, 'image/jpeg');
      logProgress(episodeId, 'generating', `✅ Thumbnail yüklendi: ${coverImageUrl}`);
    }

    // Step 7: Upload to YouTube
    logProgress(episodeId, 'generating', '📺 YouTube\'a yükleniyor...');
    let youtubeVideoId = null;

    try {
      const youtube = new YouTubeClient();
      youtubeVideoId = await youtube.uploadPodcast({
        title: title || topicPrompt,
        description: description || script.substring(0, 500) + '...',
        audioPath: finalAudioPath,
        thumbnailPath: thumbnailPath
      });

      logProgress(episodeId, 'generating', `✅ YouTube video ID: ${youtubeVideoId}`);
    } catch (ytError) {
      console.error('YouTube upload hatası:', ytError.message);
      logProgress(episodeId, 'generating', `⚠️ YouTube yüklenemedi: ${ytError.message}`);
    }

    // Step 8: Update database via webhook
    logProgress(episodeId, 'generating', '💾 Veritabanı güncelleniyor...');
    await updateEpisodeViaWebhook(episodeId, {
      status: 'completed',
      audio_url: audioUrl,
      cover_image_url: coverImageUrl,
      duration_seconds: durationSeconds,
      script_markdown: script,
      youtube_video_id: youtubeVideoId,
      github_run_id: process.env.GITHUB_RUN_ID || null
    });

    const elapsedMinutes = Math.floor((Date.now() - startTime) / 60000);
    logProgress(episodeId, 'completed', `🎉 Podcast hazır! (${elapsedMinutes} dakika)`);

    // Cleanup temp files
    if (fs.existsSync(rawAudioPath)) fs.unlinkSync(rawAudioPath);

    return {
      success: true,
      episodeId,
      audioUrl,
      coverImageUrl,
      youtubeVideoId,
      durationSeconds
    };

  } catch (error) {
    console.error('❌ Podcast oluşturma hatası:', error);

    await updateEpisodeViaWebhook(episodeId, {
      status: 'failed',
      error_message: error.message
    });

    throw error;
  }
}

// CLI Usage
if (import.meta.url === `file://${process.argv[1]}`) {
  const episodeId = process.argv[2];
  const topicPrompt = process.argv[3];
  const title = process.argv[4] || '';
  const description = process.argv[5] || '';

  if (!episodeId || !topicPrompt) {
    console.error('Kullanım: node generate.js <episode_id> <topic_prompt> [title] [description]');
    console.error('Örnek: node generate.js 1 "Almanya eğitim sistemi" "Eğitim Rehberi" "Almanya\'daki okul türleri"');
    process.exit(1);
  }

  generatePodcast(episodeId, topicPrompt, title, description)
    .then((result) => {
      console.log('\n✅ BAŞARILI!');
      console.log(JSON.stringify(result, null, 2));
      process.exit(0);
    })
    .catch((error) => {
      console.error('\n❌ HATA:', error.message);
      process.exit(1);
    });
}

export default generatePodcast;
