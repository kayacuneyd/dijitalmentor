import { error } from '@sveltejs/kit';

export async function GET({ fetch }) {
  try {
    // Backend RSS feed URL - update this to match your actual backend URL
    const backendRssUrl = 'https://api.dijitalmentor.de/server/api/podcast/rss.php';
    
    const response = await fetch(backendRssUrl);
    
    if (!response.ok) {
      throw error(response.status, 'Failed to fetch RSS feed');
    }

    const xml = await response.text();

    return new Response(xml, {
      headers: {
        'Content-Type': 'application/xml; charset=utf-8',
        'Cache-Control': 'max-age=3600'
      }
    });
  } catch (err) {
    console.error('RSS Proxy Error:', err);
    throw error(500, 'Internal Server Error');
  }
}
