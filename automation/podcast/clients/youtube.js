/**
 * YouTube Data API v3 Client
 * Uploads podcast as video (audio + static image)
 */

import { google } from 'googleapis';
import fs from 'fs';
import { exec } from 'child_process';
import { promisify } from 'util';
import path from 'path';

const execAsync = promisify(exec);

export default class YouTubeClient {
  constructor() {
    this.oauth2Client = new google.auth.OAuth2(
      process.env.YOUTUBE_CLIENT_ID,
      process.env.YOUTUBE_CLIENT_SECRET,
      'urn:ietf:wg:oauth:2.0:oob' // Desktop app redirect
    );

    // Set refresh token (obtained during initial setup)
    this.oauth2Client.setCredentials({
      refresh_token: process.env.YOUTUBE_REFRESH_TOKEN
    });

    this.youtube = google.youtube({
      version: 'v3',
      auth: this.oauth2Client
    });
  }

  /**
   * Upload podcast as YouTube video
   * @param {Object} options
   * @param {string} options.title - Video title
   * @param {string} options.description - Video description
   * @param {string} options.audioPath - Path to MP3 file
   * @param {string} options.thumbnailPath - Path to thumbnail image (optional)
   * @returns {string} Video ID
   */
  async uploadPodcast({ title, description, audioPath, thumbnailPath }) {
    console.log('📺 YouTube video oluşturuluyor...');

    // Step 1: Convert audio to video (static image + audio)
    const videoPath = await this.audioToVideo(audioPath, thumbnailPath);

    // Step 2: Upload video
    console.log('📺 YouTube\'a yükleniyor...');

    const response = await this.youtube.videos.insert({
      part: ['snippet', 'status'],
      requestBody: {
        snippet: {
          title: title,
          description: description + '\n\n🎙️ Dijital Mentor Podcast\n🌐 https://dijitalmentor.de',
          categoryId: '27', // Education
          tags: ['eğitim', 'almanya', 'türk', 'veli', 'podcast', 'dijital mentor'],
          defaultLanguage: 'tr',
          defaultAudioLanguage: 'tr'
        },
        status: {
          privacyStatus: 'public', // or 'unlisted' or 'private'
          selfDeclaredMadeForKids: false
        }
      },
      media: {
        body: fs.createReadStream(videoPath)
      }
    });

    const videoId = response.data.id;
    console.log(`✅ YouTube video ID: ${videoId}`);
    console.log(`🔗 https://www.youtube.com/watch?v=${videoId}`);

    // Clean up temp video file
    if (fs.existsSync(videoPath)) {
      fs.unlinkSync(videoPath);
    }

    return videoId;
  }

  /**
   * Convert audio to video with static image
   * @private
   */
  async audioToVideo(audioPath, imagePath) {
    const outputPath = audioPath.replace('.mp3', '_video.mp4');

    // Use solid color if no image provided
    const imageInput = imagePath && fs.existsSync(imagePath)
      ? `-loop 1 -i "${imagePath}"`
      : `-f lavfi -i color=c=0x1a1a2e:s=1280x720`;

    const command = `ffmpeg -y ${imageInput} -i "${audioPath}" -c:v libx264 -tune stillimage -c:a aac -b:a 192k -pix_fmt yuv420p -shortest -t 3600 "${outputPath}"`;

    console.log('🎬 Video render ediliyor (FFmpeg)...');

    try {
      await execAsync(command);
      console.log(`✅ Video hazır: ${outputPath}`);
      return outputPath;
    } catch (error) {
      console.error('FFmpeg hatası:', error.stderr);
      throw new Error(`Video oluşturulamadı: ${error.message}`);
    }
  }

  /**
   * Add video to playlist
   */
  async addToPlaylist(videoId, playlistId) {
    await this.youtube.playlistItems.insert({
      part: ['snippet'],
      requestBody: {
        snippet: {
          playlistId: playlistId,
          resourceId: {
            kind: 'youtube#video',
            videoId: videoId
          }
        }
      }
    });

    console.log(`✅ Video playlist'e eklendi: ${playlistId}`);
  }

  /**
   * Update video thumbnail
   */
  async setThumbnail(videoId, thumbnailPath) {
    if (!fs.existsSync(thumbnailPath)) {
      throw new Error(`Thumbnail bulunamadı: ${thumbnailPath}`);
    }

    await this.youtube.thumbnails.set({
      videoId: videoId,
      media: {
        body: fs.createReadStream(thumbnailPath)
      }
    });

    console.log(`✅ Thumbnail güncellendi: ${videoId}`);
  }

  /**
   * Generate OAuth URL (for initial setup)
   */
  static getAuthUrl() {
    const oauth2Client = new google.auth.OAuth2(
      process.env.YOUTUBE_CLIENT_ID,
      process.env.YOUTUBE_CLIENT_SECRET,
      'urn:ietf:wg:oauth:2.0:oob'
    );

    return oauth2Client.generateAuthUrl({
      access_type: 'offline',
      scope: [
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/youtube'
      ]
    });
  }

  /**
   * Exchange authorization code for tokens (for initial setup)
   */
  static async getTokens(authCode) {
    const oauth2Client = new google.auth.OAuth2(
      process.env.YOUTUBE_CLIENT_ID,
      process.env.YOUTUBE_CLIENT_SECRET,
      'urn:ietf:wg:oauth:2.0:oob'
    );

    const { tokens } = await oauth2Client.getToken(authCode);
    return tokens;
  }
}
