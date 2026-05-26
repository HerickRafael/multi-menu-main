import { useEffect, useState } from 'react'
import { Bell, Calendar, ExternalLink, Eye, Phone, X } from 'lucide-react'
import { cn } from '@/js/lib/utils'

export type NewOrderToastData = {
  uid: string
  orderNumber: string
  customerName: string
  customerPhone?: string
  total: number
  mountedAt: number
  orderUrl?: string
  kdsUrl?: string
}

type Palette = {
  primaryColor: string
  primaryGradient: string
  primarySoft: string
  primaryForeground: string
}

function fmtCurrency(value: number): string {
  try {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
  } catch {
    return `R$ ${value.toFixed(2).replace('.', ',')}`
  }
}

function fmtElapsed(ms: number): string {
  const s = Math.floor(ms / 1000)
  if (s < 60) return `${s}s`
  return `${Math.floor(s / 60)}m ${s % 60}s`
}

const AUTO_DISMISS_MS = 15000

function SingleToast({ toast, palette, onDismiss }: { toast: NewOrderToastData; palette: Palette; onDismiss: (uid: string) => void }) {
  const [visible, setVisible] = useState(false)
  const [elapsed, setElapsed] = useState('0s')

  useEffect(() => {
    const id = requestAnimationFrame(() => setVisible(true))
    return () => cancelAnimationFrame(id)
  }, [])

  useEffect(() => {
    const id = setInterval(() => setElapsed(fmtElapsed(Date.now() - toast.mountedAt)), 1000)
    return () => clearInterval(id)
  }, [toast.mountedAt])

  function handleDismiss() {
    setVisible(false)
    setTimeout(() => onDismiss(toast.uid), 300)
  }

  return (
    <article
      className={cn(
        'pointer-events-auto w-[340px] max-w-full overflow-hidden rounded-xl border border-slate-200 bg-white text-slate-800 shadow-[0_10px_25px_-5px_rgba(0,0,0,0.1),0_4px_6px_-2px_rgba(0,0,0,0.05)] transition-all duration-300',
        visible ? 'translate-y-0 opacity-100' : '-translate-y-3 opacity-0',
      )}
    >
      {/* Urgency bar */}
      <div className="h-[3px]" style={{ background: palette.primaryGradient }} />

      {/* Header */}
      <div className="relative flex items-start gap-3 px-4 pb-2.5 pt-3.5">
        <div
          className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
          style={{ background: palette.primaryGradient }}
        >
          <Bell className="h-5 w-5 text-white" />
        </div>
        <div className="min-w-0 flex-1">
          <div className="mb-0.5 flex items-center gap-2">
            <h3 className="m-0 text-sm font-semibold text-slate-900">Pedido {toast.orderNumber}</h3>
            <span
              className="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
              style={{ background: palette.primarySoft, color: palette.primaryColor }}
            >
              <span className="h-1.5 w-1.5 rounded-full" style={{ background: palette.primaryColor }} />
              NOVO
            </span>
          </div>
          <p className="m-0 flex items-center gap-1.5 text-[13px] text-slate-500">
            <Phone className="h-3 w-3 opacity-60" />
            <span className="truncate">{toast.customerName}{toast.customerPhone ? ` · ${toast.customerPhone}` : ''}</span>
          </p>
        </div>
        <button
          type="button"
          onClick={handleDismiss}
          aria-label="Fechar"
          className="absolute right-2.5 top-2.5 flex h-6 w-6 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
        >
          <X className="h-3.5 w-3.5" />
        </button>
      </div>

      {/* Divider */}
      <div className="mx-4 h-px bg-slate-200" />

      {/* Info row */}
      <div className="flex items-center justify-between bg-slate-50 px-4 py-2.5">
        <div>
          <div className="text-[11px] uppercase tracking-wide text-slate-400">Total</div>
          <div className="text-lg font-bold leading-tight" style={{ color: palette.primaryColor }}>
            {fmtCurrency(toast.total)}
          </div>
        </div>
        <div className="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-1 text-xs text-slate-600">
          <Calendar className="h-3 w-3" style={{ color: palette.primaryColor }} />
          {elapsed}
        </div>
      </div>

      {/* Actions */}
      <div className="flex gap-2 px-4 py-3">
        {toast.kdsUrl && (
          <a
            href={toast.kdsUrl}
            className="inline-flex flex-1 items-center justify-center gap-1 rounded-lg border px-3 py-2 text-xs font-medium transition hover:text-white"
            style={{
              background: palette.primarySoft,
              color: palette.primaryColor,
              borderColor: palette.primaryColor,
            }}
            onMouseEnter={(e) => { e.currentTarget.style.background = palette.primaryColor; e.currentTarget.style.color = '#fff' }}
            onMouseLeave={(e) => { e.currentTarget.style.background = palette.primarySoft; e.currentTarget.style.color = palette.primaryColor }}
          >
            <ExternalLink className="h-3.5 w-3.5" />
            Abrir KDS
          </a>
        )}
        {toast.orderUrl && (
          <a
            href={toast.orderUrl}
            className="inline-flex flex-1 items-center justify-center gap-1 rounded-lg px-3 py-2 text-xs font-medium text-white transition hover:opacity-90"
            style={{ background: palette.primaryGradient }}
          >
            <Eye className="h-3.5 w-3.5" />
            Ver Pedido
          </a>
        )}
      </div>

      {/* Progress bar */}
      <div className="relative h-0.5 overflow-hidden bg-slate-200">
        <div
          className="absolute left-0 top-0 h-full animate-[orderToastProgress_15s_linear_forwards]"
          style={{ background: palette.primaryColor }}
        />
      </div>
    </article>
  )
}

export function NewOrderToastContainer({ toasts, palette, onDismiss }: { toasts: NewOrderToastData[]; palette: Palette; onDismiss: (uid: string) => void }) {
  if (toasts.length === 0) return null
  return (
    <>
      <style>{`@keyframes orderToastProgress { from { width: 100% } to { width: 0% } }`}</style>
      <div className="pointer-events-none fixed right-4 top-4 z-[9999] flex max-h-[calc(100vh-2rem)] max-w-[calc(100vw-2rem)] flex-col gap-3 overflow-y-auto [&::-webkit-scrollbar]:hidden">
        {toasts.map((t) => (
          <SingleToast key={t.uid} toast={t} palette={palette} onDismiss={onDismiss} />
        ))}
      </div>
    </>
  )
}

export const ORDER_TOAST_AUTO_DISMISS_MS = AUTO_DISMISS_MS
