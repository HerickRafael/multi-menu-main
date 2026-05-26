import type { ReactNode } from 'react'
import { AlertTriangle, Inbox, Loader2 } from 'lucide-react'
import { cn } from '@/js/lib/utils'

type EmptyStateProps = {
  title: string
  description?: string
  icon?: ReactNode
  action?: ReactNode
  className?: string
}

export function EmptyState({ title, description, icon, action, className }: EmptyStateProps) {
  return (
    <div className={cn('flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-zinc-200 bg-white px-6 py-12 text-center', className)}>
      <div className="inline-flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-500">
        {icon ?? <Inbox className="h-5 w-5" />}
      </div>
      <div>
        <p className="text-sm font-semibold text-zinc-800">{title}</p>
        {description && <p className="mt-1 max-w-md text-sm text-zinc-500">{description}</p>}
      </div>
      {action}
    </div>
  )
}

type ErrorStateProps = {
  title?: string
  description?: string
  action?: ReactNode
  className?: string
}

export function ErrorState({
  title = 'Algo deu errado',
  description = 'Ocorreu um erro ao carregar esta página. Tente novamente.',
  action,
  className,
}: ErrorStateProps) {
  return (
    <div className={cn('flex flex-col items-center justify-center gap-3 rounded-xl border border-red-200 bg-red-50/50 px-6 py-12 text-center', className)}>
      <div className="inline-flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600">
        <AlertTriangle className="h-5 w-5" />
      </div>
      <div>
        <p className="text-sm font-semibold text-red-900">{title}</p>
        <p className="mt-1 max-w-md text-sm text-red-700">{description}</p>
      </div>
      {action}
    </div>
  )
}

type LoadingStateProps = {
  label?: string
  className?: string
}

export function LoadingState({ label = 'Carregando...', className }: LoadingStateProps) {
  return (
    <div className={cn('flex flex-col items-center justify-center gap-3 rounded-xl border border-zinc-200 bg-white px-6 py-12 text-center', className)}>
      <Loader2 className="h-5 w-5 animate-spin text-zinc-500" />
      <p className="text-sm text-zinc-500">{label}</p>
    </div>
  )
}
