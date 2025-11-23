/**
 * Cloudflare R2 Storage Client
 * S3-compatible object storage
 */

import { S3Client, PutObjectCommand } from '@aws-sdk/client-s3';
import { Upload } from '@aws-sdk/lib-storage';
import fs from 'fs';
import path from 'path';

export default class R2Client {
  constructor() {
    this.accountId = process.env.CLOUDFLARE_R2_ACCOUNT_ID;
    this.bucketName = process.env.CLOUDFLARE_R2_BUCKET_NAME;
    this.publicUrl = process.env.CLOUDFLARE_R2_PUBLIC_URL;

    this.client = new S3Client({
      region: 'auto',
      endpoint: `https://${this.accountId}.r2.cloudflarestorage.com`,
      credentials: {
        accessKeyId: process.env.CLOUDFLARE_R2_ACCESS_KEY_ID,
        secretAccessKey: process.env.CLOUDFLARE_R2_SECRET_ACCESS_KEY
      }
    });
  }

  /**
   * Upload file to R2
   * @param {string} filePath - Local file path
   * @param {string} key - Object key (path in bucket)
   * @param {string} contentType - MIME type
   * @returns {string} Public URL
   */
  async uploadFile(filePath, key, contentType = 'application/octet-stream') {
    console.log(`☁️ Yükleniyor: ${path.basename(filePath)} → ${key}`);

    const fileStream = fs.createReadStream(filePath);
    const fileSize = fs.statSync(filePath).size;

    const upload = new Upload({
      client: this.client,
      params: {
        Bucket: this.bucketName,
        Key: key,
        Body: fileStream,
        ContentType: contentType,
        CacheControl: 'public, max-age=31536000' // 1 year cache
      }
    });

    await upload.done();

    const publicUrl = `${this.publicUrl}/${key}`;
    console.log(`✅ Yüklendi: ${publicUrl} (${(fileSize / 1024 / 1024).toFixed(2)} MB)`);

    return publicUrl;
  }

  /**
   * Upload buffer directly
   */
  async uploadBuffer(buffer, key, contentType = 'application/octet-stream') {
    const command = new PutObjectCommand({
      Bucket: this.bucketName,
      Key: key,
      Body: buffer,
      ContentType: contentType
    });

    await this.client.send(command);

    return `${this.publicUrl}/${key}`;
  }

  /**
   * Generate signed URL (for private files)
   */
  async getSignedUrl(key, expiresIn = 3600) {
    // R2 currently doesn't support presigned URLs directly
    // Use custom domain with access control instead
    throw new Error('R2 presigned URLs not implemented yet');
  }

  /**
   * Delete file
   */
  async deleteFile(key) {
    const { DeleteObjectCommand } = await import('@aws-sdk/client-s3');

    const command = new DeleteObjectCommand({
      Bucket: this.bucketName,
      Key: key
    });

    await this.client.send(command);
    console.log(`🗑️ Silindi: ${key}`);
  }
}
