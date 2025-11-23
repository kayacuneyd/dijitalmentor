/**
 * Anthropic Claude API Client
 * Generates podcast scripts in natural Turkish
 */

import Anthropic from '@anthropic-ai/sdk';

export default class AnthropicClient {
  constructor() {
    this.client = new Anthropic({
      apiKey: process.env.ANTHROPIC_API_KEY
    });
  }

  async generatePodcastScript(topicPrompt, title = '', description = '') {
    const systemPrompt = `Sen 'Dijital Mentor' platformunun kurucususun. Almanya'daki Türk velilere hitap eden, samimi, güven veren bir podcast yapıyorsun.

## Ses Tonu ve Üslup:
- "Siz" değil "sen" diye hitap et (samimi)
- Konuşma dilinde yaz (örn: "yapacağız" değil "yapıcaz" gibi doğal)
- Almanya eğitim terimlerini doğru kullan (Gymnasium, Realschule, Abitur, Grundschule vb.)
- Çok uzun cümleler kurma. Nefes alacak yerler bırak (virgüllerle)
- Ara sıra "Hani derler ya", "Bakın çok önemli", "İşte burada dikkat" gibi bağlaçlar kullan

## İçerik Yapısı:
1. Giriş: "${process.env.PODCAST_INTRO || 'Merhaba Dijital Mentor ailesi'}"
2. Ana içerik: Konuyu 3-4 ana başlıkta ele al
3. Pratik öneriler: Velilerin hemen uygulayabileceği ipuçları
4. Kapanış: "${process.env.PODCAST_OUTRO || 'Gelecek bizim, hoşçakalın'}"

## Teknik Kurallar:
- Uzunluk: 600-800 kelime (4-5 dakika konuşma)
- Doğal duraklamalar için virgül kullan (ElevenLabs v2 bunu anlıyor)
- [nefes] veya ... gibi işaretler KULLANMA
- Emoji KULLANMA
- Her paragraf 2-3 cümle olsun

## Örnek Ton:
"Çocuğunuz lise seçimi yaparken, Almanya'da üç ana yol var. İlki Gymnasium, yani lise eğitimi. Burada Abitur sınavına hazırlanır, üniversiteye giden yol açılır. İkincisi Realschule, daha pratik bir eğitim. Çocuk meslek öğrenmeye yöneliyorsa, bu iyi bir seçim olabilir. Üçüncüsü ise Hauptschule, meslek eğitimine odaklanır."

Şimdi verilen konu hakkında podcast metni yaz.`;

    const userPrompt = `Konu: ${topicPrompt}${title ? `\nBaşlık: ${title}` : ''}${description ? `\nAçıklama: ${description}` : ''}`;

    const message = await this.client.messages.create({
      model: 'claude-sonnet-4-20250514',
      max_tokens: 2000,
      messages: [
        {
          role: 'user',
          content: `${systemPrompt}\n\n${userPrompt}`
        }
      ]
    });

    const script = message.content[0].text;

    // Validate length
    const wordCount = script.split(/\s+/).length;
    if (wordCount < 400) {
      console.warn(`⚠️ Script kısa (${wordCount} kelime), ideal: 600-800`);
    }

    return script;
  }

  /**
   * Alternative: Generate structured script with sections
   */
  async generateStructuredScript(topicPrompt) {
    const message = await this.client.messages.create({
      model: 'claude-sonnet-4-20250514',
      max_tokens: 2500,
      messages: [
        {
          role: 'user',
          content: `Almanya'daki Türk veliler için bir podcast bölümü yaz.

Konu: ${topicPrompt}

JSON formatında döndür:
{
  "intro": "Giriş metni",
  "sections": [
    {"heading": "Başlık 1", "content": "İçerik 1"},
    {"heading": "Başlık 2", "content": "İçerik 2"}
  ],
  "tips": ["İpucu 1", "İpucu 2"],
  "outro": "Kapanış metni"
}`
        }
      ]
    });

    const jsonResponse = message.content[0].text;
    const parsed = JSON.parse(jsonResponse);

    // Convert to linear script
    let script = parsed.intro + '\n\n';
    parsed.sections.forEach(section => {
      script += `${section.heading}.\n${section.content}\n\n`;
    });

    if (parsed.tips && parsed.tips.length > 0) {
      script += 'Pratik öneriler:\n';
      parsed.tips.forEach((tip, i) => {
        script += `${i + 1}. ${tip}\n`;
      });
      script += '\n';
    }

    script += parsed.outro;

    return script;
  }
}
