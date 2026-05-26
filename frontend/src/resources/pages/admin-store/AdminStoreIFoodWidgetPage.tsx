import { useState } from 'react'
import {
  AlertTriangle,
  ExternalLink,
  Grid3x3,
  Maximize2,
  RefreshCw,
  Save,
  Settings,
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import {
  AdminPageHeader,
  AdminStorePageShell,
  showToast,
  useStoreContext,
} from '@/components/admin-store'

type Payload = {
  widget_url: string
  enabled: boolean
  integration_ok: boolean
  merchant_id: string
  environment: 'sandbox' | 'production'
  urls: {
    self: string
    save: string
    config: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_IFOOD_WIDGET__?: Payload
  }
}

/**
 * Central iFood — embute o widget oficial do iFood (a "mini central") dentro
 * do nosso admin. Esse widget é servido pelo próprio iFood numa URL que o
 * portal do desenvolvedor entrega após configuração.
 *
 * Funcionalidades que rodam DENTRO da iframe (são do iFood, não nossas):
 *   - Chat com cliente
 *   - Rastreio do entregador no mapa
 *   - Pausar loja
 *   - Negociação de atraso e acordos de cancelamento
 *   - Status dos pedidos em tempo real
 *   - Notificações da loja
 *
 * O que controlamos aqui:
 *   - URL do widget (admin cola uma vez)
 *   - Toggle ativar/desativar
 *   - Recarregar iframe
 *   - Abrir em janela cheia/nova aba
 */
export default function AdminStoreIFoodWidgetPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_IFOOD_WIDGET__) || ({} as Payload)
  const urls = payload.urls ?? { self: '', save: '', config: '' }

  const [enabled, setEnabled] = useState<boolean>(!!payload.enabled)
  const [widgetUrl, setWidgetUrl] = useState<string>(payload.widget_url ?? '')
  const [editing, setEditing] = useState<boolean>(!payload.widget_url)
  const [saving, setSaving] = useState<boolean>(false)
  const [iframeKey, setIframeKey] = useState<number>(0)

  const integrationOk = !!payload.integration_ok

  async function save() {
    if (widgetUrl.trim() !== '' && !widgetUrl.match(/^https:\/\//)) {
      showToast('A URL precisa começar com https://', 'error')
      return
    }
    setSaving(true)
    try {
      const res = await fetch(urls.save, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          enabled,
          merchant_url: widgetUrl.trim() || null,
          // Mantemos defaults dos outros campos pra não quebrar a row
          widget_type: 'embedded',
          tracking_enabled: true,
        }),
      })
      const j = (await res.json().catch(() => null)) as
        | { success?: boolean; message?: string }
        | null
      if (j?.success) {
        showToast('Configuração salva.', 'success')
        setEditing(false)
        setIframeKey((k) => k + 1)
      } else {
        showToast(j?.message || 'Falha ao salvar.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setSaving(false)
    }
  }

  function reloadIframe() {
    setIframeKey((k) => k + 1)
  }

  function openInNewTab() {
    if (widgetUrl) {
      window.open(widgetUrl, '_blank', 'noopener,noreferrer')
    }
  }

  const showIframe = enabled && widgetUrl && !editing

  return (
    <AdminStorePageShell section="ifood">
      <AdminPageHeader
        title="Central iFood"
        description="Chat com cliente, rastreio do entregador, pausar loja, status dos pedidos — tudo do iFood embutido aqui dentro."
        icon={<Grid3x3 className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap gap-2">
            {showIframe && (
              <>
                <Button type="button" size="sm" variant="outline" onClick={reloadIframe} className="gap-1.5">
                  <RefreshCw className="h-3.5 w-3.5" />
                  Recarregar
                </Button>
                <Button type="button" size="sm" variant="outline" onClick={openInNewTab} className="gap-1.5">
                  <Maximize2 className="h-3.5 w-3.5" />
                  Abrir em nova aba
                </Button>
              </>
            )}
            <Button
              type="button"
              size="sm"
              variant="outline"
              onClick={() => setEditing((v) => !v)}
              className="gap-1.5"
            >
              <Settings className="h-3.5 w-3.5" />
              {editing ? 'Fechar configuração' : 'Configurar'}
            </Button>
          </div>
        }
      />

      {/* Aviso de integração não configurada */}
      {!integrationOk && (
        <div className="rounded-lg border border-amber-300 bg-amber-50 p-4">
          <div className="flex items-start gap-3">
            <AlertTriangle className="mt-0.5 h-5 w-5 text-amber-700 shrink-0" />
            <div className="flex-1 text-sm text-amber-900">
              <p className="font-medium">Integração iFood ainda não configurada.</p>
              <p className="mt-1">
                Antes de usar a Central, configure as credenciais iFood (client_id, client_secret, merchant_id).
              </p>
            </div>
            <Button asChild size="sm" variant="outline">
              <a href={urls.config}>Configurar agora</a>
            </Button>
          </div>
        </div>
      )}

      {/* Drawer de config */}
      {editing && (
        <section className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm space-y-4">
          <h3 className="text-sm font-semibold text-zinc-700">Configurar a Central</h3>

          <div className="space-y-1">
            <label className="block text-sm font-medium text-zinc-700">Ativar a Central iFood</label>
            <label className="inline-flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={enabled}
                onChange={(e) => setEnabled(e.target.checked)}
                className="h-4 w-4"
              />
              {enabled ? 'Ativada' : 'Desativada'}
            </label>
          </div>

          <div className="space-y-1">
            <label className="block text-sm font-medium text-zinc-700">URL do widget iFood</label>
            <input
              type="url"
              value={widgetUrl}
              onChange={(e) => setWidgetUrl(e.target.value)}
              placeholder="https://widget.ifood.com.br/..."
              className="w-full rounded border border-zinc-300 px-3 py-2 text-sm font-mono"
            />
            <p className="text-xs text-zinc-500">
              Cole aqui a URL do widget que o iFood te entregou no Portal do Desenvolvedor.
              Se ainda não tem, fale com o suporte iFood pedindo o "widget de integração" para o seu merchant
              {payload.merchant_id ? ` (${payload.merchant_id})` : ''}.
            </p>
          </div>

          <div className="flex flex-wrap gap-2">
            <Button type="button" size="sm" onClick={save} disabled={saving} className="gap-1.5">
              <Save className="h-3.5 w-3.5" />
              {saving ? 'Salvando…' : 'Salvar'}
            </Button>
            {payload.widget_url && (
              <Button type="button" size="sm" variant="outline" onClick={() => setEditing(false)}>
                Cancelar
              </Button>
            )}
          </div>
        </section>
      )}

      {/* Iframe full-height */}
      {showIframe ? (
        <section className="rounded-2xl border border-zinc-200 bg-white shadow-sm overflow-hidden">
          <iframe
            key={iframeKey}
            src={widgetUrl}
            title="Central iFood"
            className="w-full"
            style={{ height: 'calc(100vh - 220px)', minHeight: '600px', border: 0 }}
            allow="clipboard-read; clipboard-write; geolocation"
            referrerPolicy="no-referrer-when-downgrade"
          />
        </section>
      ) : (
        !editing && (
          <section className="rounded-2xl border border-dashed border-zinc-300 bg-white p-12 text-center">
            <Grid3x3 className="mx-auto h-10 w-10 text-zinc-400" />
            <h3 className="mt-3 text-base font-semibold text-zinc-900">
              Central iFood não configurada
            </h3>
            <p className="mx-auto mt-1 max-w-md text-sm text-zinc-500">
              A Central permite que você gerencie chat, rastreio, pausa de loja e mais — direto daqui,
              sem abrir o gestor iFood numa outra aba. Clique em <strong>Configurar</strong> e cole a URL
              do widget que o iFood te entregou.
            </p>
            <Button type="button" size="sm" className="mt-4 gap-1.5" onClick={() => setEditing(true)}>
              <Settings className="h-3.5 w-3.5" />
              Configurar agora
            </Button>
            <p className="mt-6 text-xs text-zinc-400">
              Ainda não tem a URL? Acesse{' '}
              <a
                href="https://developer.ifood.com.br/"
                target="_blank"
                rel="noopener noreferrer"
                className="underline inline-flex items-center gap-0.5"
              >
                Portal do Desenvolvedor iFood
                <ExternalLink className="h-3 w-3" />
              </a>
              {' '}ou contate o suporte iFood pedindo o "widget de integração para parceiros".
            </p>
          </section>
        )
      )}
    </AdminStorePageShell>
  )
}
