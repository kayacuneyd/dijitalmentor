import { PUBLIC_API_URL, PUBLIC_MOCK_MODE } from '$env/static/public';
import { authStore } from '$lib/stores/auth.js';
import { get } from 'svelte/store';
import { mockData } from './mockData.js';

const API_URL = PUBLIC_API_URL || 'http://localhost:8000/api';
const MOCK_MODE = String(PUBLIC_MOCK_MODE).trim() === 'true';

console.log('API Config:', { API_URL, MOCK_MODE });

class APIClient {
  async request(endpoint, options = {}) {
    // Mock Mode Interceptor
    if (MOCK_MODE) {
      console.log(`[MOCK API] ${options.method || 'GET'} ${endpoint}`);
      await new Promise(r => setTimeout(r, 500)); // Simulate latency

      if (endpoint.includes('/subjects/list.php')) {
        return { success: true, data: mockData.subjects };
      }
      
      if (endpoint.includes('/teachers/list.php')) {
        const url = new URL(`http://dummy${endpoint}`);
        const city = url.searchParams.get('city') || (options.params && options.params.city);
        const subject = url.searchParams.get('subject') || (options.params && options.params.subject);
        let max_rate = url.searchParams.get('max_rate') || (options.params && options.params.max_rate);
        
        // Handle "null" string from URLSearchParams
        if (max_rate === 'null') max_rate = null;

        let filteredTeachers = mockData.teachers;

        if (city && city !== 'undefined') {
          filteredTeachers = filteredTeachers.filter(t => t.city === city);
        }

        if (subject && subject !== 'undefined') {
          filteredTeachers = filteredTeachers.filter(t => 
            Array.isArray(t.subjects) 
              ? t.subjects.some(s => s.slug === subject || s.name.toLowerCase() === subject.toLowerCase())
              : t.subjects.toLowerCase().includes(subject.toLowerCase())
          );
        }

        if (max_rate) {
          const rate = parseFloat(max_rate);
          if (!isNaN(rate)) {
            filteredTeachers = filteredTeachers.filter(t => t.hourly_rate <= rate);
          }
        }

        return { 
          success: true, 
          data: { 
            teachers: filteredTeachers,
            pagination: { total: filteredTeachers.length, page: 1, pages: 1 }
          } 
        };
      }
      
      if (endpoint.includes('/teachers/detail.php')) {
        // Extract ID from query params if passed as object in GET
        // But here endpoint string might contain query params
        const url = new URL(`http://dummy${endpoint}`);
        const id = url.searchParams.get('id') || (options.body && JSON.parse(options.body).id);
        const teacher = mockData.teachers.find(t => t.id == id) || mockData.teachers[0];
        return { success: true, data: teacher };
      }
      
      if (endpoint.includes('/auth/login.php')) {
        return { 
          success: true, 
          data: { 
            token: mockData.user.token, 
            user: mockData.user 
          } 
        };
      }
      
      if (endpoint.includes('/auth/register.php')) {
        return { 
          success: true, 
          data: { 
            token: mockData.user.token, 
            user: mockData.user 
          } 
        };
      }

      // Lesson Requests Mocking
      if (endpoint.includes('/requests/list.php')) {
        return { success: true, data: mockData.lessonRequests };
      }

      if (endpoint.includes('/requests/my_requests.php')) {
        return { success: true, data: mockData.lessonRequests.filter(r => r.parent_id === 201) };
      }

      if (endpoint.includes('/requests/detail.php')) {
        const url = new URL(`http://dummy${endpoint}`);
        const id = url.searchParams.get('id') || (options.body && JSON.parse(options.body).id);
        const request = mockData.lessonRequests.find(r => r.id == id);
        return { success: true, data: request };
      }

      if (endpoint.includes('/requests/create.php')) {
        const body = JSON.parse(options.body);
        const newRequest = {
          id: Math.floor(Math.random() * 1000),
          parent_id: 201,
          parent_name: 'Demo Veli',
          ...body,
          status: 'active',
          created_at: new Date().toISOString().split('T')[0]
        };
        mockData.lessonRequests.unshift(newRequest);
        return { success: true, message: 'Talep oluşturuldu', data: newRequest };
      }

      // Default success for other endpoints
      // Blog Endpoints
      if (endpoint === '/blog/list.php') {
        return { success: true, data: mockData.posts };
      }

      if (endpoint.includes('/blog/detail.php')) {
        const url = new URL(`http://dummy${endpoint}`); // Define url here for blog endpoints
        const slug = url.searchParams.get('slug');
        const post = mockData.posts.find(p => p.slug === slug);
        return post ? { success: true, data: post } : { success: false, message: 'Yazı bulunamadı' };
      }

      if (endpoint === '/blog/comment.php') {
        return { success: true, message: 'Yorum eklendi' };
      }

      if (endpoint === '/blog/like.php') {
        return { success: true, message: 'Beğenildi' };
      }

      return { success: false, message: 'Endpoint not found' };
    }

    const { token } = get(authStore);
    
    const config = {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...(token && { Authorization: `Bearer ${token}` }),
        ...options.headers
      }
    };
    
    try {
      const response = await fetch(`${API_URL}${endpoint}`, config);
      const data = await response.json();
      
      if (!response.ok) throw new Error(data.error || 'Request failed');
      return data;
    } catch (error) {
      console.error('API Error:', error);
      throw error;
    }
  }
  
  get(endpoint, params = {}) {
    const query = new URLSearchParams(params).toString();
    return this.request(query ? `${endpoint}?${query}` : endpoint, { method: 'GET' });
  }
  
  post(endpoint, data) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }
}

export const api = new APIClient();
