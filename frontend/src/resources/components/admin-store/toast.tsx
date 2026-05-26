import { useCallback, useEffect, useState } from 'react'
import { CheckCircle2, XCircle, AlertCircle } from 'lucide-react'
import { cn } from '@/js/lib/utils'

type Toast = {
  id: number
  message: string
  variant: 'success' | 'error' | 'info'
}

const listeners = new Set<(toasts: Toast[]) => void>()
let toasts: Toast[] = []
let nextId = 1

function notify() {
  listeners.forEach((fn) => fn([...toasts]))
}

export function showToast(message: string, variant: 'success' | 'error' | 'info' = 'success') {
  const id = nextId++
  toasts = [...toasts, { id, message, variant }]
  notify()
  setTimeout(() => {
    toasts = toasts.filter((t) => t.id !== id)
    notify()
  }, 4000)
}

export function ToastContainer() {
  const [items, setItems] = useState<Toast[]>([])

  useEffect(() => {
    const fn = (next: Toast[]) => setItems(next)
    listeners.add(fn)
    return () => {
      listeners.delete(fn)
    }
  }, [])

  const dismiss = useCallback((id: number) => {
    toasts = toasts.filter((t) => t.id !== id)
    notify()
  }, [])

  if (items.length === 0) return null

  return (
    <div className="fixed bottom-4 right-4 z-[100] flex flex-col gap-2 pointer-events-none">
      {items.map((toast) => {
        const Icon = toast.variant === 'success' ? CheckCircle2 : toast.variant === 'error' ? XCircle : AlertCircle
        const variantClass =
          toast.variant === 'success'
            ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
            : toast.variant === 'error'
              ? 'border-red-200 bg-red-50 text-red-800'
              : 'border-zinc-200 bg-white text-zinc-800'
        return (
          <button
            type="button"
            key={toast.id}
            onClick={() => dismiss(toast.id)}
            className={cn(
              'pointer-events-auto flex max-w-sm items-start gap-2 rounded-lg border px-3 py-2 text-sm shadow-md transition hover:opacity-90 text-left',
              variantClass,
            )}
          >
            <Icon className="h-4 w-4 mt-0.5 shrink-0" />
            <span className="flex-1">{toast.message}</span>
          </button>
        )
      })}
    </div>
  )
}
