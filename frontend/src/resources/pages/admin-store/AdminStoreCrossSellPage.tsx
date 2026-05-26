import { useEffect, useMemo, useState, type FormEvent } from 'react'
import {
  ArrowRight,
  CheckCircle2,
  Layers,
  Pencil,
  Plus,
  Save,
  Sparkles,
  Tag,
  Trash2,
  X,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  AdminStorePageShell,
  AdminPageHeader,
  ConfirmDialog,
  EmptyState,
  FormField,
  FormSection,
  showToast,
  getCsrfToken,
  useStoreContext,
} from '@/components/admin-store'

type Recommendation = {
  category_id: number
  category_name: string
  section_title: string
}

type CrossSellGroup = {
  id: number
  trigger_category_id: number
  trigger_category_name: string
  active: boolean
  recommendations: Recommendation[]
  updated_at: string
}

type Category = { id: number; name: string }

type CrossSellPayload = {
  groups: CrossSellGroup[]
  categories: Category[]
  flash: { error: string | null; success: string | null }
  urls: {
    submit: string
    edit_base: string
    toggle_base: string
    destroy_base: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_CROSS_SELL__?: CrossSellPayload
  }
}

type RecommendationDraft = {
  category_id: number
  selected: boolean
  title: string
}

export default function AdminStoreCrossSellPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_CROSS_SELL__) ||
    ({} as CrossSellPayload)
  const urls = payload.urls
  const categories = payload.categories ?? []

  const [groups, setGroups] = useState<CrossSellGroup[]>(payload.groups ?? [])
  const [showForm, setShowForm] = useState(false)
  const [editing, setEditing] = useState<CrossSellGroup | null>(null)
  const [triggerCatId, setTriggerCatId] = useState<number | ''>('')
  const [recs, setRecs] = useState<Record<number, RecommendationDraft>>({})
  const [confirmDelete, setConfirmDelete] = useState<CrossSellGroup | null>(null)
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    if (payload.flash?.success) showToast(payload.flash.success, 'success')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  // Categories already used as triggers (so we don't allow duplicate triggers when creating new)
  const usedTriggerIds = useMemo(() => {
    const set = new Set<number>()
    for (const g of groups) {
      if (!editing || g.id !== editing.id) set.add(g.trigger_category_id)
    }
    return set
  }, [groups, editing])

  const availableTriggers = useMemo(
    () => categories.filter((c) => !usedTriggerIds.has(c.id)),
    [categories, usedTriggerIds],
  )

  function openNewForm() {
    setEditing(null)
    setTriggerCatId('')
    setRecs({})
    setShowForm(true)
  }

  function openEditForm(group: CrossSellGroup) {
    setEditing(group)
    setTriggerCatId(group.trigger_category_id)
    const next: Record<number, RecommendationDraft> = {}
    for (const r of group.recommendations) {
      next[r.category_id] = {
        category_id: r.category_id,
        selected: true,
        title: r.section_title,
      }
    }
    setRecs(next)
    setShowForm(true)
  }

  function closeForm() {
    setShowForm(false)
    setEditing(null)
    setTriggerCatId('')
    setRecs({})
  }

  function toggleRec(catId: number, selected: boolean) {
    setRecs((prev) => ({
      ...prev,
      [catId]: {
        category_id: catId,
        selected,
        title: prev[catId]?.title ?? '',
      },
    }))
  }

  function setRecTitle(catId: number, title: string) {
    setRecs((prev) => ({
      ...prev,
      [catId]: {
        category_id: catId,
        selected: prev[catId]?.selected ?? true,
        title,
      },
    }))
  }

  function handleSubmit(e: FormEvent) {
    if (!triggerCatId) {
      e.preventDefault()
      showToast('Selecione a categoria disparadora.', 'error')
      return
    }
    // Validate at least one selected with title
    const selected = Object.values(recs).filter((r) => r.selected && r.category_id !== triggerCatId)
    if (selected.length === 0) {
      e.preventDefault()
      showToast('Selecione pelo menos uma categoria recomendada.', 'error')
      return
    }
    for (const r of selected) {
      if (!r.title.trim()) {
        e.preventDefault()
        showToast('Todas as categorias selecionadas precisam de um título.', 'error')
        return
      }
    }
    // Continue with native form submit — PHP redirects back here
  }

  async function handleToggle(group: CrossSellGroup) {
    setBusy(true)
    try {
      const res = await fetch(`${urls.toggle_base}${group.id}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
        },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; error?: string } | null
      if (data?.success) {
        setGroups((prev) =>
          prev.map((g) => (g.id === group.id ? { ...g, active: !g.active } : g)),
        )
        showToast(`Grupo ${group.active ? 'desativado' : 'ativado'}.`, 'success')
      } else {
        showToast(data?.error || 'Falha ao alterar status.', 'error')
      }
    } catch {
      showToast('Falha de rede.', 'error')
    } finally {
      setBusy(false)
    }
  }

  async function handleDelete() {
    if (!confirmDelete) return
    try {
      const res = await fetch(`${urls.destroy_base}${confirmDelete.id}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
        },
      })
      const data = (await res.json().catch(() => null)) as { success?: boolean; error?: string } | null
      if (data?.success) {
        setGroups((prev) => prev.filter((g) => g.id !== confirmDelete.id))
        showToast('Grupo removido.', 'success')
      } else {
        showToast(data?.error || 'Falha ao remover grupo.', 'error')
      }
    } catch {
      showToast('Falha de rede ao remover.', 'error')
    }
  }

  return (
    <AdminStorePageShell section="catalog">
      <AdminPageHeader
        title="Cross-sell por categoria"
        description="Quando o cliente adiciona produtos de uma categoria ao carrinho, mostre recomendações de outras categorias."
        icon={<Sparkles className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <Button type="button" onClick={openNewForm} className="gap-2" disabled={availableTriggers.length === 0}>
            <Plus className="h-4 w-4" />
            Novo grupo
          </Button>
        }
      />

      {/* Form panel */}
      {showForm && (
        <FormSection
          title={editing ? `Editar grupo: ${editing.trigger_category_name}` : 'Novo grupo de cross-sell'}
          description="A categoria disparadora dispara recomendações quando algum produto dela está no carrinho."
        >
          <form action={urls.submit} method="POST" onSubmit={handleSubmit} className="space-y-4">
            <input type="hidden" name="csrf_token" value={getCsrfToken()} />

            <FormField label="Categoria disparadora" htmlFor="cs-trigger" required>
              <select
                id="cs-trigger"
                name="trigger_category_id"
                value={triggerCatId}
                onChange={(e) => setTriggerCatId(e.target.value ? Number(e.target.value) : '')}
                disabled={!!editing}
                className="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400 max-w-md disabled:bg-zinc-50 disabled:text-zinc-600"
              >
                <option value="">Selecione…</option>
                {editing && (
                  <option value={editing.trigger_category_id}>{editing.trigger_category_name}</option>
                )}
                {!editing &&
                  availableTriggers.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name}
                    </option>
                  ))}
              </select>
              {editing && (
                <p className="text-xs text-zinc-500 mt-1">
                  A categoria disparadora não pode ser alterada após criar o grupo.
                </p>
              )}
            </FormField>

            {triggerCatId && (
              <FormField
                label="Categorias recomendadas"
                hint="Marque as categorias que devem aparecer como sugestão e dê um título para cada seção (ex.: 'Combine com bebida')."
              >
                <div className="space-y-2 max-h-[400px] overflow-y-auto pr-1">
                  {categories
                    .filter((c) => c.id !== triggerCatId)
                    .map((c) => {
                      const draft = recs[c.id]
                      const selected = !!draft?.selected
                      return (
                        <div
                          key={c.id}
                          className={`rounded-lg border p-3 transition ${
                            selected ? 'border-emerald-300 bg-emerald-50/30' : 'border-zinc-200 bg-white'
                          }`}
                        >
                          <label className="flex items-start gap-3 cursor-pointer">
                            <input
                              type="checkbox"
                              name={`recommended_categories[${c.id}][selected]`}
                              value="1"
                              checked={selected}
                              onChange={(e) => toggleRec(c.id, e.target.checked)}
                              className="mt-1 h-4 w-4 rounded border-zinc-300"
                            />
                            <div className="flex-1 min-w-0 space-y-2">
                              <p className="text-sm font-medium text-zinc-800">{c.name}</p>
                              {selected && (
                                <div className="space-y-1">
                                  <Input
                                    name={`recommended_categories[${c.id}][title]`}
                                    value={draft?.title ?? ''}
                                    onChange={(e) => setRecTitle(c.id, e.target.value)}
                                    placeholder="Título da seção (ex.: 'Que tal uma bebida gelada?')"
                                    maxLength={200}
                                    className="text-sm"
                                  />
                                  <p className="text-[11px] text-zinc-500">
                                    Mostra produtos de "{c.name}" com esse título no carrinho.
                                  </p>
                                </div>
                              )}
                            </div>
                          </label>
                        </div>
                      )
                    })}
                </div>
              </FormField>
            )}

            <div className="flex items-center gap-2 pt-2 border-t border-zinc-100">
              <Button type="submit" className="gap-2">
                <Save className="h-4 w-4" />
                {editing ? 'Salvar alterações' : 'Criar grupo'}
              </Button>
              <Button type="button" variant="outline" onClick={closeForm}>
                Cancelar
              </Button>
            </div>
          </form>
        </FormSection>
      )}

      {/* Groups list */}
      {groups.length === 0 ? (
        <EmptyState
          title="Sem grupos de cross-sell"
          description="Crie grupos para recomendar categorias específicas quando o cliente adicionar produtos ao carrinho."
          icon={<Sparkles className="h-5 w-5" />}
          action={
            availableTriggers.length > 0 ? (
              <Button onClick={openNewForm} className="gap-2">
                <Plus className="h-4 w-4" />
                Criar primeiro grupo
              </Button>
            ) : null
          }
        />
      ) : (
        <section className="grid gap-3 lg:grid-cols-2">
          {groups.map((group) => (
            <div
              key={group.id}
              className={`rounded-2xl border bg-white p-4 shadow-sm transition ${
                group.active ? 'border-zinc-200' : 'border-zinc-200 opacity-60'
              }`}
            >
              <header className="mb-3 flex items-center justify-between gap-2">
                <div className="flex items-center gap-2 min-w-0">
                  <span
                    className="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white shrink-0"
                    style={{ background: ctx.palette.primaryGradient }}
                  >
                    <Tag className="h-4 w-4" />
                  </span>
                  <div className="min-w-0">
                    <p className="text-xs text-zinc-500 uppercase tracking-wide">Quando adicionar</p>
                    <p className="font-semibold text-zinc-800 truncate">{group.trigger_category_name}</p>
                  </div>
                </div>
                <Badge
                  className={`gap-1 ${
                    group.active
                      ? 'bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-100'
                      : 'bg-zinc-100 text-zinc-700 border border-zinc-200 hover:bg-zinc-100'
                  }`}
                >
                  {group.active && <CheckCircle2 className="h-3 w-3" />}
                  {group.active ? 'Ativo' : 'Inativo'}
                </Badge>
              </header>

              <div className="mb-3 ml-1 flex items-center gap-1.5 text-xs text-zinc-500">
                <ArrowRight className="h-3 w-3" />
                Recomenda {group.recommendations.length} categoria{group.recommendations.length === 1 ? '' : 's'}:
              </div>

              <ul className="space-y-1.5 mb-3">
                {group.recommendations.map((rec) => (
                  <li
                    key={rec.category_id}
                    className="flex items-start gap-2 rounded-md bg-zinc-50 px-2.5 py-1.5"
                  >
                    <Layers className="h-3.5 w-3.5 text-zinc-400 mt-0.5 shrink-0" />
                    <div className="min-w-0 flex-1">
                      <p className="text-sm font-medium text-zinc-800 truncate">{rec.category_name}</p>
                      <p className="text-[11px] text-zinc-600 italic truncate">"{rec.section_title}"</p>
                    </div>
                  </li>
                ))}
              </ul>

              <footer className="flex items-center justify-between gap-1 border-t border-zinc-100 pt-2">
                <Button
                  size="sm"
                  variant="ghost"
                  className="h-7 text-xs gap-1"
                  onClick={() => handleToggle(group)}
                  disabled={busy}
                >
                  {group.active ? 'Desativar' : 'Ativar'}
                </Button>
                <div className="flex items-center gap-1">
                  <Button
                    size="sm"
                    variant="ghost"
                    className="h-7 px-2 text-zinc-600 hover:text-zinc-900"
                    onClick={() => openEditForm(group)}
                  >
                    <Pencil className="h-3.5 w-3.5" />
                  </Button>
                  <Button
                    size="sm"
                    variant="ghost"
                    className="h-7 px-2 text-red-600 hover:text-red-700 hover:bg-red-50"
                    onClick={() => setConfirmDelete(group)}
                  >
                    <Trash2 className="h-3.5 w-3.5" />
                  </Button>
                </div>
              </footer>
            </div>
          ))}
        </section>
      )}

      <ConfirmDialog
        open={!!confirmDelete}
        onOpenChange={(open) => !open && setConfirmDelete(null)}
        title="Remover grupo de cross-sell?"
        description={
          confirmDelete
            ? `O grupo da categoria "${confirmDelete.trigger_category_name}" será removido. Esta ação não pode ser desfeita.`
            : null
        }
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
