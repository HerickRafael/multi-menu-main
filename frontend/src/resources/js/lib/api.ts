import axios from 'axios'
import { useAuthStore } from '@/js/stores/authStore'

const BASE_URL = import.meta.env.VITE_API_URL || '/api/superadmin'

export interface RequestOptions {
  signal?: AbortSignal
  timeout?: number
}

export const api = axios.create({
  baseURL: BASE_URL,
  timeout: 30_000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request interceptor — inject JWT token
api.interceptors.request.use(
  (config) => {
    const token = useAuthStore.getState().token
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error),
)

// Response interceptor — handle 401 (expired token)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore.getState().logout()
      window.location.href = '/superadmin/login'
    }
    return Promise.reject(error)
  },
)

// Type-safe request helpers
export async function get<T>(
  url: string,
  params?: Record<string, unknown>,
  options?: RequestOptions,
): Promise<T> {
  const { data } = await api.get<T>(url, {
    params,
    signal: options?.signal,
    timeout: options?.timeout,
  })
  return data
}

export async function post<T>(url: string, body?: unknown): Promise<T> {
  const { data } = await api.post<T>(url, body)
  return data
}

export async function put<T>(url: string, body?: unknown): Promise<T> {
  const { data } = await api.put<T>(url, body)
  return data
}

export async function del<T>(url: string): Promise<T> {
  const { data } = await api.delete<T>(url)
  return data
}
