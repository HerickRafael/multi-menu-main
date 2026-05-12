export interface ApiResponse<T = unknown> {
  data: T
  message?: string
  success: boolean
}

export interface PaginatedResponse<T = unknown> {
  data: T[]
  pagination: {
    page: number
    per_page: number
    total: number
    total_pages: number
    has_next: boolean
    has_prev: boolean
  }
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
  code?: string
}

export interface SortParams {
  sort_by?: string
  sort_dir?: 'asc' | 'desc'
}

export interface PaginationParams {
  page?: number
  per_page?: number
}

export interface SearchParams {
  search?: string
}

export type TableParams = SortParams & PaginationParams & SearchParams & Record<string, unknown>
