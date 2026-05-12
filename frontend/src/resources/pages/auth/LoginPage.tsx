import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Eye, EyeOff, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useAuth } from '@/js/hooks/useAuth'
import { useAuthStore } from '@/js/stores/authStore'
import { useEffect } from 'react'

export default function LoginPage() {
  const { login, isLoggingIn } = useAuth()
  const { isAuthenticated } = useAuthStore()
  const navigate = useNavigate()
  const [showPassword, setShowPassword] = useState(false)
  const [form, setForm] = useState({ email: '', password: '' })

  // Redirect if already authenticated
  useEffect(() => {
    if (isAuthenticated) {
      navigate('/superadmin/dashboard', { replace: true })
    }
  }, [isAuthenticated, navigate])

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    login(form)
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-4">
      {/* Background grid pattern */}
      <div className="absolute inset-0 bg-grid-pattern opacity-30" />

      <Card className="relative w-full max-w-sm shadow-xl">
        <CardHeader className="space-y-1 text-center">
          <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-primary">
            <span className="text-primary-foreground font-bold text-lg">SA</span>
          </div>
          <CardTitle className="text-xl">Super Admin</CardTitle>
          <CardDescription>Multi Menu Platform — Acesso restrito</CardDescription>
        </CardHeader>

        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="email">E-mail</Label>
              <Input
                id="email"
                type="email"
                placeholder="admin@multimenu.com.br"
                autoComplete="email"
                autoFocus
                required
                value={form.email}
                onChange={e => setForm(f => ({ ...f, email: e.target.value }))}
                disabled={isLoggingIn}
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="password">Senha</Label>
              <div className="relative">
                <Input
                  id="password"
                  type={showPassword ? 'text' : 'password'}
                  placeholder="••••••••"
                  autoComplete="current-password"
                  required
                  value={form.password}
                  onChange={e => setForm(f => ({ ...f, password: e.target.value }))}
                  disabled={isLoggingIn}
                  className="pr-10"
                />
                <button
                  type="button"
                  tabIndex={-1}
                  onClick={() => setShowPassword(s => !s)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                  aria-label={showPassword ? 'Ocultar senha' : 'Mostrar senha'}
                >
                  {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
            </div>

            <Button type="submit" className="w-full" disabled={isLoggingIn}>
              {isLoggingIn && <Loader2 className="h-4 w-4 animate-spin" />}
              {isLoggingIn ? 'Autenticando…' : 'Entrar'}
            </Button>
          </form>

          <p className="mt-4 text-center text-xs text-muted-foreground">
            Acesso exclusivo para administradores da plataforma
          </p>
        </CardContent>
      </Card>
    </div>
  )
}
