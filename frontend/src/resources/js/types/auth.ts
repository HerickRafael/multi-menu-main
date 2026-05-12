export interface AuthUser {
  id: number
  name: string
  email: string
  role: string
  avatar_url?: string
  permissions: string[]
  company_id?: number | null
  is_super_admin: boolean
  last_login_at?: string
}

export interface AuthResponse {
  token: string
  user: AuthUser
  expires_at: string
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface AuthState {
  token: string | null
  user: AuthUser | null
  isAuthenticated: boolean
  isLoading: boolean
}
