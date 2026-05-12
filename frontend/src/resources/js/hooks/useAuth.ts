import { useEffect } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { toast } from 'sonner'
import { post, get } from '@/js/lib/api'
import { useAuthStore } from '@/js/stores/authStore'
import type { LoginCredentials, AuthResponse, AuthUser } from '@/js/types/auth'

function isAuthUser(value: unknown): value is AuthUser {
  return typeof value === 'object' && value !== null && 'id' in value && 'email' in value
}

function normalizeAuthUserResponse(value: unknown): AuthUser {
  if (isAuthUser(value)) {
    return value
  }

  if (
    typeof value === 'object' &&
    value !== null &&
    'data' in value &&
    isAuthUser((value as { data?: unknown }).data)
  ) {
    return (value as { data: AuthUser }).data
  }

  throw new Error('Resposta inválida de /me')
}

export function useAuth() {
  const { token, user, isAuthenticated, setAuth, setUser, logout } = useAuthStore()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const loginMutation = useMutation({
    mutationFn: (credentials: LoginCredentials) =>
      post<AuthResponse>('/auth', credentials),
    onSuccess: (data) => {
      setAuth(data.token, data.user)
      toast.success(`Bem-vindo, ${data.user.name}!`)
      navigate('/superadmin/platform/stores')
    },
    onError: (error: { response?: { data?: { message?: string } } }) => {
      const message = error.response?.data?.message ?? 'Credenciais inválidas'
      toast.error(message)
    },
  })

  const logoutMutation = useMutation({
    mutationFn: () => post('/logout'),
    onSettled: () => {
      logout()
      queryClient.clear()
      navigate('/superadmin/login')
    },
  })

  // Refresh current user from server (on tab focus, etc.)
  const { data: currentUser } = useQuery({
    queryKey: ['auth', 'me'],
    queryFn: async () => {
      const response = await get<unknown>('/me')
      return normalizeAuthUserResponse(response)
    },
    enabled: isAuthenticated && !!token,
    staleTime: 5 * 60 * 1000,
    retry: false,
    refetchOnWindowFocus: true,
  })

  // Sync /me result to Zustand after render — never inside select (causes infinite re-render loop)
  useEffect(() => {
    if (currentUser) {
      setUser(currentUser)
    }
  }, [currentUser, setUser])

  return {
    user: currentUser ?? user,
    token,
    isAuthenticated,
    login: loginMutation.mutate,
    loginAsync: loginMutation.mutateAsync,
    logout: logoutMutation.mutate,
    isLoggingIn: loginMutation.isPending,
    isLoggingOut: logoutMutation.isPending,
  }
}
