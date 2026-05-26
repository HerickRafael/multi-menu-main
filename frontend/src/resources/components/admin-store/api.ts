import { getCsrfToken } from './use-store-context'

export type ApiOptions = {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  body?: Record<string, unknown> | FormData
  signal?: AbortSignal
}

export class ApiError extends Error {
  status: number
  data: unknown
  constructor(message: string, status: number, data: unknown) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.data = data
  }
}

/**
 * Wrapper around fetch() that:
 *  - prepends the admin store slug path
 *  - adds CSRF token for non-GET requests
 *  - parses JSON responses
 *  - throws ApiError on non-2xx
 */
export async function adminApi<T = unknown>(path: string, options: ApiOptions = {}): Promise<T> {
  const { method = 'GET', body, signal } = options
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  }

  let payload: BodyInit | undefined

  if (body instanceof FormData) {
    payload = body
  } else if (body) {
    headers['Content-Type'] = 'application/json; charset=utf-8'
    payload = JSON.stringify(body)
  }

  if (method !== 'GET') {
    const csrf = getCsrfToken()
    if (csrf) headers['X-CSRF-Token'] = csrf
  }

  const response = await fetch(path, {
    method,
    headers,
    body: payload,
    credentials: 'same-origin',
    signal,
  })

  const contentType = response.headers.get('content-type') || ''
  const isJson = contentType.includes('application/json')
  const data = isJson ? await response.json().catch(() => null) : await response.text().catch(() => null)

  if (!response.ok) {
    const message = (isJson && data && typeof data === 'object' && 'message' in data && typeof data.message === 'string')
      ? data.message
      : `Request failed (${response.status})`
    throw new ApiError(message, response.status, data)
  }

  return data as T
}

/**
 * Build a slug-scoped admin URL.
 */
export function adminUrl(slug: string, path: string): string {
  const cleanSlug = slug.replace(/^\/|\/$/g, '')
  const cleanPath = path.startsWith('/') ? path : `/${path}`
  return `/admin/${cleanSlug}${cleanPath}`
}
