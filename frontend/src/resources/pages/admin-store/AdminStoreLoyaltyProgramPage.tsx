import { useEffect, useState, type FormEvent } from 'react'
import {
  Award,
  CheckCircle2,
  Gift,
  PercentCircle,
  Save,
  Star,
  Trophy,
  Truck,
  Users,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  AdminStorePageShell,
  AdminPageHeader,
  FormField,
  FormSection,
  showToast,
  getCsrfToken,
  formatCurrency,
  useStoreContext,
} from '@/components/admin-store'

type Program = {
  id: number
  name: string
  required_orders: number
  reward_type: 'discount_percentage' | 'discount_fixed' | 'free_delivery' | 'free_item'
  reward_value: number
  reward_description: string
  is_active: boolean
  created_at: string
}

type Stats = {
  total_participants: number
  active_participants: number
  avg_progress: number
  total_completions: number
}

type LoyaltyProgramPayload = {
  program: Program | null
  stats: Stats | null
  flash: { error: string | null; success: string | null }
  urls: {
    submit: string
    toggle_base: string
    stats: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_LOYALTY_PROGRAM__?: LoyaltyProgramPayload
  }
}

const REWARD_TYPES: Array<{
  value: Program['reward_type']
  label: string
  description: string
  icon: typeof PercentCircle
  needsValue: boolean
  valueLabel?: string
  valueSuffix?: string
}> = [
  {
    value: 'discount_percentage',
    label: 'Desconto percentual',
    description: 'Aplica % de desconto no próximo pedido.',
    icon: PercentCircle,
    needsValue: true,
    valueLabel: 'Percentual',
    valueSuffix: '%',
  },
  {
    value: 'discount_fixed',
    label: 'Desconto fixo',
    description: 'Valor em R$ descontado do próximo pedido.',
    icon: Gift,
    needsValue: true,
    valueLabel: 'Valor (R$)',
    valueSuffix: 'R$',
  },
  {
    value: 'free_delivery',
    label: 'Entrega grátis',
    description: 'Cliente ganha entrega grátis no pedido de recompensa.',
    icon: Truck,
    needsValue: false,
  },
  {
    value: 'free_item',
    label: 'Item grátis',
    description: 'Cliente ganha um item específico do cardápio.',
    icon: Star,
    needsValue: false,
  },
]

function moneyMask(raw: string): string {
  const digits = raw.replace(/[^\d,]/g, '')
  if (!digits) return ''
  const parts = digits.split(',')
  const intPart = parts[0].replace(/^0+(?=\d)/, '') || '0'
  if (parts.length === 1) return intPart
  return `${intPart},${parts.slice(1).join('').slice(0, 2)}`
}

function formatBR(n: number, decimals = 2): string {
  return n.toFixed(decimals).replace('.', ',')
}

export default function AdminStoreLoyaltyProgramPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_LOYALTY_PROGRAM__) ||
    ({} as LoyaltyProgramPayload)
  const urls = payload.urls
  const existing = payload.program
  const stats = payload.stats

  const [name, setName] = useState(existing?.name ?? 'Programa de Fidelidade')
  const [requiredOrders, setRequiredOrders] = useState<number>(existing?.required_orders ?? 5)
  const [rewardType, setRewardType] = useState<Program['reward_type']>(
    existing?.reward_type ?? 'discount_percentage',
  )
  const [rewardValue, setRewardValue] = useState(
    existing?.reward_value != null ? formatBR(existing.reward_value) : '10',
  )
  const [rewardDescription, setRewardDescription] = useState(
    existing?.reward_description ?? 'Pedidos acumulados rendem desconto na próxima compra.',
  )
  const [isActive, setIsActive] = useState<boolean>(existing?.is_active ?? true)
  const [errors, setErrors] = useState<Record<string, string>>({})

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    if (payload.flash?.success) showToast(payload.flash.success, 'success')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  const selectedReward = REWARD_TYPES.find((r) => r.value === rewardType)!

  function validate(): boolean {
    const next: Record<string, string> = {}
    if (!name.trim()) next.name = 'Informe o nome do programa.'
    if (requiredOrders < 2) next.required_orders = 'O mínimo de pedidos deve ser 2 ou mais.'
    if (requiredOrders > 100) next.required_orders = 'O máximo é 100 pedidos.'
    if (selectedReward.needsValue) {
      const v = Number.parseFloat((rewardValue || '0').replace(',', '.'))
      if (!Number.isFinite(v) || v <= 0) {
        next.reward_value = 'Informe um valor maior que zero.'
      } else if (rewardType === 'discount_percentage' && v > 100) {
        next.reward_value = 'Percentual não pode ser maior que 100%.'
      }
    }
    if (!rewardDescription.trim()) {
      next.reward_description = 'Descreva a recompensa para os clientes.'
    }
    setErrors(next)
    return Object.keys(next).length === 0
  }

  function handleSubmit(e: FormEvent) {
    if (!validate()) {
      e.preventDefault()
      showToast('Corrija os campos em vermelho.', 'error')
    }
  }

  async function handleToggle() {
    if (!existing) return
    const formData = new FormData()
    const csrf = getCsrfToken()
    if (csrf) formData.append('csrf_token', csrf)
    try {
      const res = await fetch(`${urls.toggle_base}${existing.id}/toggle`, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        // Re-fetch by reloading to get fresh state from server
        window.location.href = window.location.pathname + (existing.is_active ? '?success=disabled' : '?success=enabled')
      } else {
        showToast('Falha ao alternar status.', 'error')
      }
    } catch {
      window.location.href = window.location.pathname
    }
  }

  const completionRate =
    stats && stats.total_participants > 0
      ? Math.round((stats.total_completions / stats.total_participants) * 100)
      : 0

  return (
    <AdminStorePageShell section="loyalty">
      <AdminPageHeader
        title="Programa de Fidelidade"
        description="Recompense clientes recorrentes — quando atingirem o número de pedidos, ganham um benefício."
        icon={<Trophy className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          existing && (
            <div className="flex items-center gap-2">
              <Badge
                className={`gap-1 ${
                  existing.is_active
                    ? 'bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-100'
                    : 'bg-zinc-100 text-zinc-700 border border-zinc-200 hover:bg-zinc-100'
                }`}
              >
                {existing.is_active ? <CheckCircle2 className="h-3 w-3" /> : null}
                {existing.is_active ? 'Programa ativo' : 'Inativo'}
              </Badge>
              <Button type="button" variant="outline" size="sm" onClick={handleToggle}>
                {existing.is_active ? 'Desativar' : 'Reativar'}
              </Button>
            </div>
          )
        }
      />

      {stats && existing && (
        <section className="grid gap-3 sm:grid-cols-4">
          <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <div className="flex items-center gap-2">
              <Users className="h-4 w-4 text-zinc-500" />
              <p className="text-xs text-zinc-500">Participantes</p>
            </div>
            <p className="mt-1 text-2xl font-semibold text-zinc-800">{stats.total_participants}</p>
            <p className="mt-1 text-[11px] text-zinc-500">{stats.active_participants} ativos no programa</p>
          </div>
          <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <div className="flex items-center gap-2">
              <Award className="h-4 w-4 text-amber-500" />
              <p className="text-xs text-zinc-500">Resgates totais</p>
            </div>
            <p className="mt-1 text-2xl font-semibold text-amber-600">{stats.total_completions}</p>
            <p className="mt-1 text-[11px] text-zinc-500">prêmios entregues</p>
          </div>
          <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p className="text-xs text-zinc-500">Progresso médio</p>
            <p className="mt-1 text-2xl font-semibold text-zinc-800">
              {stats.avg_progress.toFixed(1)}
              <span className="text-sm text-zinc-500"> / {existing.required_orders}</span>
            </p>
            <p className="mt-1 text-[11px] text-zinc-500">pedidos por cliente</p>
          </div>
          <div className="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p className="text-xs text-zinc-500">Taxa de conversão</p>
            <p className="mt-1 text-2xl font-semibold text-emerald-600">{completionRate}%</p>
            <p className="mt-1 text-[11px] text-zinc-500">de participantes resgataram</p>
          </div>
        </section>
      )}

      <form action={urls.submit} method="POST" onSubmit={handleSubmit} className="space-y-5 max-w-4xl">
        <input type="hidden" name="csrf_token" value={getCsrfToken()} />
        {existing && <input type="hidden" name="program_id" value={existing.id} />}

        <FormSection
          title="Configurações do programa"
          description="Defina como os clientes acumulam pedidos e o que ganham ao completar."
        >
          <FormField label="Nome do programa" htmlFor="lp-name" required error={errors.name}>
            <Input
              id="lp-name"
              name="name"
              value={name}
              onChange={(e) => setName(e.target.value)}
              maxLength={150}
              placeholder="Ex.: Programa Fidelidade Premium"
            />
          </FormField>

          <FormField
            label="Pedidos para ganhar a recompensa"
            htmlFor="lp-orders"
            required
            error={errors.required_orders}
            hint="Quantos pedidos o cliente precisa fazer para resgatar o prêmio. Mínimo: 2."
          >
            <div className="flex items-center gap-3">
              <Input
                id="lp-orders"
                name="required_orders"
                type="number"
                min={2}
                max={100}
                value={requiredOrders}
                onChange={(e) => setRequiredOrders(Math.max(2, Number(e.target.value) || 2))}
                className="w-32"
              />
              <span className="text-sm text-zinc-500">pedidos completos</span>
            </div>
          </FormField>
        </FormSection>

        <FormSection title="Recompensa" description="Escolha o que o cliente ganha quando completar o programa.">
          <input type="hidden" name="reward_type" value={rewardType} />
          <div className="grid gap-2 sm:grid-cols-2">
            {REWARD_TYPES.map((r) => {
              const Icon = r.icon
              const selected = r.value === rewardType
              return (
                <button
                  type="button"
                  key={r.value}
                  onClick={() => setRewardType(r.value)}
                  className={`flex items-start gap-3 rounded-xl border p-3 text-left transition ${
                    selected
                      ? 'border-zinc-900 bg-zinc-50 ring-1 ring-zinc-900'
                      : 'border-zinc-200 bg-white hover:border-zinc-400'
                  }`}
                >
                  <span
                    className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${
                      selected ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-600'
                    }`}
                  >
                    <Icon className="h-5 w-5" />
                  </span>
                  <div className="min-w-0 flex-1">
                    <p className="text-sm font-semibold text-zinc-800">{r.label}</p>
                    <p className="text-xs text-zinc-500">{r.description}</p>
                  </div>
                </button>
              )
            })}
          </div>

          {selectedReward.needsValue && (
            <FormField
              label={selectedReward.valueLabel || 'Valor'}
              htmlFor="lp-rval"
              required
              error={errors.reward_value}
            >
              <div className="relative max-w-xs">
                {selectedReward.valueSuffix === 'R$' && (
                  <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">
                    R$
                  </span>
                )}
                <Input
                  id="lp-rval"
                  name="reward_value"
                  value={rewardValue}
                  onChange={(e) => setRewardValue(moneyMask(e.target.value))}
                  inputMode="decimal"
                  className={`${selectedReward.valueSuffix === 'R$' ? 'pl-9' : ''} ${selectedReward.valueSuffix === '%' ? 'pr-10' : ''}`}
                />
                {selectedReward.valueSuffix === '%' && (
                  <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">
                    %
                  </span>
                )}
              </div>
            </FormField>
          )}
          {!selectedReward.needsValue && <input type="hidden" name="reward_value" value="0" />}

          <FormField
            label="Descrição da recompensa"
            htmlFor="lp-rdesc"
            required
            error={errors.reward_description}
            hint="Texto exibido ao cliente no cardápio (ex.: 'Faça 5 pedidos e ganhe 20% de desconto!')."
          >
            <textarea
              id="lp-rdesc"
              name="reward_description"
              value={rewardDescription}
              onChange={(e) => setRewardDescription(e.target.value)}
              rows={2}
              maxLength={300}
              className="w-full rounded-md border border-zinc-200 bg-white p-3 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
              placeholder="Ex.: Faça 5 pedidos e ganhe 20% de desconto no próximo!"
            />
          </FormField>
        </FormSection>

        <FormSection title="Status">
          <label className="flex items-start gap-3 cursor-pointer rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50">
            <input
              type="checkbox"
              name="is_active"
              value="1"
              checked={isActive}
              onChange={(e) => setIsActive(e.target.checked)}
              className="peer sr-only"
            />
            <span className="relative mt-0.5 inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-zinc-200 transition-colors peer-checked:bg-emerald-500">
              <span className="ml-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
            </span>
            <span className="flex-1 text-sm">
              <span className="font-medium text-zinc-800">
                {isActive ? 'Programa ativo' : 'Programa desativado'}
              </span>
              <span className="block text-xs text-zinc-500 mt-0.5">
                Quando ativo, os pedidos dos clientes contam para o programa. Desativar pausa o acúmulo (não apaga o progresso atual).
              </span>
            </span>
          </label>
        </FormSection>

        {/* Preview card */}
        <FormSection title="Pré-visualização" description="Como o programa aparece para os clientes:">
          <div
            className="rounded-2xl border-2 border-dashed border-zinc-300 p-5"
            style={{ background: ctx.palette.primarySoft }}
          >
            <div className="flex items-start gap-4">
              <span
                className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-white"
                style={{ background: ctx.palette.primaryGradient }}
              >
                <Trophy className="h-6 w-6" />
              </span>
              <div className="min-w-0 flex-1">
                <p className="text-base font-bold text-zinc-900">{name || 'Programa de Fidelidade'}</p>
                <p className="text-sm text-zinc-700">{rewardDescription || 'Descrição da recompensa...'}</p>
                <div className="mt-2 flex items-center gap-2">
                  <Badge
                    className="border text-xs"
                    style={{
                      background: ctx.palette.primarySoftStrong,
                      color: ctx.palette.accent,
                      borderColor: ctx.palette.accent,
                    }}
                  >
                    {requiredOrders} pedidos
                  </Badge>
                  {selectedReward.needsValue && rewardValue && (
                    <Badge className="bg-amber-100 text-amber-800 border border-amber-200 hover:bg-amber-100 text-xs">
                      {rewardType === 'discount_percentage'
                        ? `${rewardValue}% off`
                        : formatCurrency(Number.parseFloat(rewardValue.replace(',', '.')) || 0)}
                    </Badge>
                  )}
                  {!selectedReward.needsValue && (
                    <Badge className="bg-amber-100 text-amber-800 border border-amber-200 hover:bg-amber-100 text-xs">
                      {selectedReward.label}
                    </Badge>
                  )}
                </div>
              </div>
            </div>
          </div>
        </FormSection>

        <div className="sticky bottom-0 -mx-5 mt-6 border-t border-zinc-200 bg-white/95 backdrop-blur px-5 py-3">
          <div className="mx-auto flex max-w-[1292px] flex-wrap items-center justify-end gap-2">
            <Button type="submit" className="gap-2">
              <Save className="h-4 w-4" />
              {existing ? 'Salvar alterações' : 'Criar programa'}
            </Button>
          </div>
        </div>
      </form>
    </AdminStorePageShell>
  )
}
