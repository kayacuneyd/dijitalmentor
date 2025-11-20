import { browser } from '$app/environment';
import { goto } from '$app/navigation';
import { writable } from 'svelte/store';

function createAuthStore() {
  const { subscribe, set } = writable({
    user: null,
    token: null,
    isAuthenticated: false,
    loading: true
  });

  return {
    subscribe,
    
    checkAuth: () => {
      if (!browser) return;
      
      const token = localStorage.getItem('bezmidar_token');
      const userStr = localStorage.getItem('bezmidar_user');
      
      if (token && userStr) {
        const user = JSON.parse(userStr);
        set({ user, token, isAuthenticated: true, loading: false });
      } else {
        set({ user: null, token: null, isAuthenticated: false, loading: false });
      }
    },
    
    login: (user, token) => {
      if (browser) {
        localStorage.setItem('bezmidar_token', token);
        localStorage.setItem('bezmidar_user', JSON.stringify(user));
      }
      set({ user, token, isAuthenticated: true, loading: false });
    },
    
    logout: () => {
      if (browser) {
        localStorage.removeItem('bezmidar_token');
        localStorage.removeItem('bezmidar_user');
      }
      set({ user: null, token: null, isAuthenticated: false, loading: false });
      goto('/');
    }
  };
}

export const authStore = createAuthStore();
