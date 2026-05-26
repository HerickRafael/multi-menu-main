import { useEffect, useMemo, useState } from 'react'
import {
  BarChart3,
  BookOpen,
  Carrot,
  ClipboardList,
  CreditCard,
  Heart,
  Layers,
  Menu as MenuIcon,
  MessageSquare,
  Package,
  Settings as SettingsIcon,
  Sliders,
  Ticket,
  Truck,
  Utensils,
  X,
  type LucideIcon,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import {
  AdminPageHeader,
  AdminStorePageShell,
  useStoreContext,
} from '@/components/admin-store'

type GuideTopic = {
  key: string
  label: string
  icon: string
  url: string
}

type Payload = {
  topic: string
  topic_label: string
  topic_icon: string
  html: string
  topics: GuideTopic[]
  urls: {
    dashboard: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_GUIDE__?: Payload
  }
}

const ICONS: Record<string, LucideIcon> = {
  Package,
  Carrot,
  Sliders,
  Ticket,
  Layers,
  Heart,
  CreditCard,
  Truck,
  BarChart3,
  Settings: SettingsIcon,
  ClipboardList,
  MessageSquare,
  Utensils,
}

function GuideIcon({ name, className }: { name: string; className?: string }) {
  const Cmp = ICONS[name] || BookOpen
  return <Cmp className={className} />
}

const GUIDE_CSS_FILES = [
  '/assets/css/guide-layout.css',
  '/assets/css/guide-base.css',
  '/assets/css/guide-components.css',
]

export default function AdminStoreGuidePage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_GUIDE__) || ({} as Payload)
  const [sidebarOpen, setSidebarOpen] = useState(false)

  // Inject guide CSS files once on mount (idempotent).
  useEffect(() => {
    const inserted: HTMLLinkElement[] = []
    for (const href of GUIDE_CSS_FILES) {
      if (document.querySelector(`link[data-guide-css="${href}"]`)) continue
      const link = document.createElement('link')
      link.rel = 'stylesheet'
      link.href = href
      link.setAttribute('data-guide-css', href)
      document.head.appendChild(link)
      inserted.push(link)
    }
    return () => {
      // Keep them across navigations — guide CSS is harmless on other pages.
      // No cleanup needed.
    }
  }, [])

  const topics = useMemo(() => payload.topics || [], [payload.topics])
  const currentTopic = payload.topic

  if (!payload.html) {
    return (
      <AdminStorePageShell section="settings">
        <AdminPageHeader title="Guia" />
        <div className="rounded-2xl border border-zinc-200 bg-white p-12 text-center">
          <BookOpen className="mx-auto h-10 w-10 text-zinc-300 mb-2" />
          <p className="text-sm text-zinc-500">Conteúdo do guia indisponível.</p>
        </div>
      </AdminStorePageShell>
    )
  }

  return (
    <AdminStorePageShell section="settings">
      <AdminPageHeader
        title={`Guia · ${payload.topic_label}`}
        description="Documentação e instruções de uso do painel administrativo."
        icon={<GuideIcon name={payload.topic_icon} className="h-5 w-5" />}
        actions={
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => setSidebarOpen((v) => !v)}
            className="gap-1.5 lg:hidden"
          >
            {sidebarOpen ? <X className="h-3.5 w-3.5" /> : <MenuIcon className="h-3.5 w-3.5" />}
            Tópicos
          </Button>
        }
      />

      <div className="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-4">
        {/* Sidebar nav */}
        <aside
          className={`rounded-2xl border border-zinc-200 bg-white p-3 shadow-sm self-start lg:sticky lg:top-20 ${
            sidebarOpen ? 'block' : 'hidden lg:block'
          }`}
        >
          <p className="px-3 py-2 text-[10px] font-semibold uppercase tracking-wide text-zinc-400">
            Tópicos do guia
          </p>
          <nav className="space-y-0.5">
            {topics.map((t) => {
              const active = t.key === currentTopic
              return (
                <a
                  key={t.key}
                  href={t.url}
                  onClick={() => setSidebarOpen(false)}
                  className={`flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                    active
                      ? 'bg-zinc-900 text-white'
                      : 'text-zinc-700 hover:bg-zinc-50'
                  }`}
                  style={
                    active
                      ? { backgroundColor: ctx.palette.primaryColor, color: '#fff' }
                      : undefined
                  }
                >
                  <GuideIcon name={t.icon} className="h-3.5 w-3.5 shrink-0" />
                  <span className="truncate">{t.label}</span>
                </a>
              )
            })}
          </nav>
        </aside>

        {/* HTML content from captured guide view */}
        <article className="admin-guide-content min-w-0">
          <div dangerouslySetInnerHTML={{ __html: payload.html }} />
        </article>
      </div>
    </AdminStorePageShell>
  )
}
