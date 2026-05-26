import { useEffect, useState, type FormEvent } from 'react'
import { ArrowLeft, CheckCircle2, Pencil, Plus, Save, Sparkles, Tag, Trash2, X } from 'lucide-react'
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
  getCsrfToken,
  showToast,
  useStoreContext,
} from '@/components/admin-store'

type Category = {
  id: number
  name: string
  type: 'fixed' | 'variable'
  description: string
  active: boolean
}

type Payload = {
  categories: Category[]
  flash: { success: string | null; error: string | null }
  urls: {
    list: string
    expenses: string
    store: string
    update_base: string
    destroy_base: string
    seed: string
  }
}

declare global {
  interface Window {
    __ADMIN_STORE_EXPENSE_CATEGORIES__?: Payload
  }
}

export default function AdminStoreExpenseCategoriesPage() {
  const ctx = useStoreContext()
  const payload =
    (typeof window !== 'undefined' && window.__ADMIN_STORE_EXPENSE_CATEGORIES__) || ({} as Payload)
  const urls = payload.urls

  const [categories, setCategories] = useState<Category[]>(payload.categories ?? [])
  const [formOpen, setFormOpen] = useState(false)
  const [editing, setEditing] = useState<Category | null>(null)
  const [form, setForm] = useState({ name: '', type: 'fixed' as 'fixed' | 'variable', description: '' })
  const [confirmDelete, setConfirmDelete] = useState<Category | null>(null)

  useEffect(() => {
    if (payload.flash?.error) showToast(payload.flash.error, 'error')
    if (payload.flash?.success) {
      const msg =
        payload.flash.success === 'created'
          ? 'Categoria criada!'
          : payload.flash.success === 'updated'
            ? 'Categoria atualizada!'
            : payload.flash.success === 'deleted'
              ? 'Categoria removida!'
              : payload.flash.success === 'seeded'
                ? 'Categorias padrão criadas.'
                : 'Operação realizada.'
      showToast(msg, 'success')
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  function openNew() {
    setEditing(null)
    setForm({ name: '', type: 'fixed', description: '' })
    setFormOpen(true)
  }

  function openEdit(c: Category) {
    setEditing(c)
    setForm({ name: c.name, type: c.type, description: c.description })
    setFormOpen(true)
  }

  function handleSubmit(e: FormEvent) {
    if (!form.name.trim()) {
      e.preventDefault()
      showToast('Informe o nome.', 'error')
    }
  }

  async function handleDelete() {
    if (!confirmDelete) return
    try {
      const res = await fetch(`${urls.destroy_base}${confirmDelete.id}/delete`, {
        method: 'GET',
        credentials: 'same-origin',
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        setCategories((prev) => prev.filter((c) => c.id !== confirmDelete.id))
        showToast(`Categoria "${confirmDelete.name}" removida.`, 'success')
      } else {
        showToast('Falha ao remover.', 'error')
      }
    } catch {
      setCategories((prev) => prev.filter((c) => c.id !== confirmDelete.id))
      showToast(`Categoria "${confirmDelete.name}" removida.`, 'success')
    }
  }

  async function seedCategories() {
    if (!window.confirm('Criar categorias padrão? (Aluguel, Energia, Água, Funcionários, etc.)')) return
    try {
      const res = await fetch(urls.seed, {
        method: 'GET',
        credentials: 'same-origin',
        redirect: 'manual',
      })
      if (res.type === 'opaqueredirect' || res.ok || res.status === 302 || res.status === 0) {
        showToast('Categorias padrão criadas — recarregando…', 'success')
        window.location.href = urls.list + '?success=seeded'
      } else {
        showToast('Falha ao criar categorias padrão.', 'error')
      }
    } catch {
      window.location.href = urls.list
    }
  }

  const fixed = categories.filter((c) => c.type === 'fixed')
  const variable = categories.filter((c) => c.type === 'variable')

  return (
    <AdminStorePageShell section="financial">
      <AdminPageHeader
        title="Categorias de despesas"
        description={`${categories.length} categoria${categories.length === 1 ? '' : 's'} cadastrada${categories.length === 1 ? '' : 's'}.`}
        icon={<Tag className="h-5 w-5" style={{ color: ctx.palette.primaryColor }} />}
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <Button asChild variant="outline" size="sm" className="gap-1.5">
              <a href={urls.expenses}>
                <ArrowLeft className="h-3.5 w-3.5" />
                Voltar
              </a>
            </Button>
            {categories.length === 0 && (
              <Button type="button" variant="outline" onClick={seedCategories} className="gap-2">
                <Sparkles className="h-4 w-4" />
                Categorias padrão
              </Button>
            )}
            <Button onClick={openNew} className="gap-2">
              <Plus className="h-4 w-4" />
              Nova categoria
            </Button>
          </div>
        }
      />

      {categories.length === 0 ? (
        <EmptyState
          title="Sem categorias cadastradas"
          description="Categorize suas despesas em fixas (aluguel, energia, etc.) e variáveis (matéria-prima, manutenção). Use 'Categorias padrão' para criar um conjunto pré-definido."
          icon={<Tag className="h-5 w-5" />}
          action={
            <div className="flex gap-2">
              <Button variant="outline" onClick={seedCategories} className="gap-2">
                <Sparkles className="h-4 w-4" />
                Criar padrão
              </Button>
              <Button onClick={openNew} className="gap-2">
                <Plus className="h-4 w-4" />
                Criar primeira
              </Button>
            </div>
          }
        />
      ) : (
        <section className="grid gap-5 lg:grid-cols-2">
          {/* Fixed */}
          <div>
            <h2 className="mb-2 flex items-center gap-2 text-sm font-semibold text-zinc-700">
              <Badge className="bg-indigo-100 text-indigo-700 border border-indigo-200 hover:bg-indigo-100">
                {fixed.length}
              </Badge>
              Despesas fixas
            </h2>
            {fixed.length === 0 ? (
              <p className="rounded-xl border border-dashed border-zinc-300 p-4 text-center text-xs text-zinc-500">
                Nenhuma categoria fixa
              </p>
            ) : (
              <ul className="space-y-2">
                {fixed.map((c) => (
                  <li
                    key={c.id}
                    className="group flex items-start justify-between gap-2 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm"
                  >
                    <div className="min-w-0 flex-1">
                      <p className="font-medium text-zinc-800 truncate">{c.name}</p>
                      {c.description && <p className="text-xs text-zinc-500 truncate">{c.description}</p>}
                    </div>
                    <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                      <Button
                        size="sm"
                        variant="ghost"
                        className="h-7 px-2 text-zinc-600"
                        onClick={() => openEdit(c)}
                        aria-label="Editar"
                      >
                        <Pencil className="h-3 w-3" />
                      </Button>
                      <Button
                        size="sm"
                        variant="ghost"
                        className="h-7 px-2 text-red-600 hover:bg-red-50"
                        onClick={() => setConfirmDelete(c)}
                        aria-label="Remover"
                      >
                        <Trash2 className="h-3 w-3" />
                      </Button>
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </div>

          {/* Variable */}
          <div>
            <h2 className="mb-2 flex items-center gap-2 text-sm font-semibold text-zinc-700">
              <Badge className="bg-amber-100 text-amber-800 border border-amber-200 hover:bg-amber-100">
                {variable.length}
              </Badge>
              Despesas variáveis
            </h2>
            {variable.length === 0 ? (
              <p className="rounded-xl border border-dashed border-zinc-300 p-4 text-center text-xs text-zinc-500">
                Nenhuma categoria variável
              </p>
            ) : (
              <ul className="space-y-2">
                {variable.map((c) => (
                  <li
                    key={c.id}
                    className="group flex items-start justify-between gap-2 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm"
                  >
                    <div className="min-w-0 flex-1">
                      <p className="font-medium text-zinc-800 truncate">{c.name}</p>
                      {c.description && <p className="text-xs text-zinc-500 truncate">{c.description}</p>}
                    </div>
                    <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                      <Button
                        size="sm"
                        variant="ghost"
                        className="h-7 px-2 text-zinc-600"
                        onClick={() => openEdit(c)}
                        aria-label="Editar"
                      >
                        <Pencil className="h-3 w-3" />
                      </Button>
                      <Button
                        size="sm"
                        variant="ghost"
                        className="h-7 px-2 text-red-600 hover:bg-red-50"
                        onClick={() => setConfirmDelete(c)}
                        aria-label="Remover"
                      >
                        <Trash2 className="h-3 w-3" />
                      </Button>
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </section>
      )}

      {/* Form modal */}
      {formOpen && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
          onClick={() => setFormOpen(false)}
        >
          <div
            className="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="mb-4 flex items-center justify-between gap-2">
              <h3 className="text-lg font-semibold text-zinc-800">
                {editing ? `Editar: ${editing.name}` : 'Nova categoria'}
              </h3>
              <Button variant="ghost" size="sm" onClick={() => setFormOpen(false)}>
                <X className="h-4 w-4" />
              </Button>
            </div>
            <form
              action={editing ? `${urls.update_base}${editing.id}/update` : urls.store}
              method="POST"
              onSubmit={handleSubmit}
              className="space-y-3"
            >
              <input type="hidden" name="csrf_token" value={getCsrfToken()} />

              <FormField label="Nome" htmlFor="cat-name" required>
                <Input
                  id="cat-name"
                  name="name"
                  value={form.name}
                  onChange={(e) => setForm({ ...form, name: e.target.value })}
                  maxLength={120}
                  placeholder="Ex.: Aluguel, Energia, Matéria-prima..."
                  autoFocus
                />
              </FormField>

              <FormField label="Tipo">
                <div className="grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    onClick={() => setForm({ ...form, type: 'fixed' })}
                    className={`rounded-lg border p-2.5 text-sm transition ${
                      form.type === 'fixed'
                        ? 'border-indigo-400 bg-indigo-50 ring-1 ring-indigo-400'
                        : 'border-zinc-200 hover:border-zinc-400'
                    }`}
                  >
                    <p className="font-medium text-zinc-800">Fixa</p>
                    <p className="text-[10px] text-zinc-500">Valor mensal estável</p>
                  </button>
                  <button
                    type="button"
                    onClick={() => setForm({ ...form, type: 'variable' })}
                    className={`rounded-lg border p-2.5 text-sm transition ${
                      form.type === 'variable'
                        ? 'border-amber-400 bg-amber-50 ring-1 ring-amber-400'
                        : 'border-zinc-200 hover:border-zinc-400'
                    }`}
                  >
                    <p className="font-medium text-zinc-800">Variável</p>
                    <p className="text-[10px] text-zinc-500">Valor que oscila no mês</p>
                  </button>
                </div>
                <input type="hidden" name="type" value={form.type} />
              </FormField>

              <FormField label="Descrição" htmlFor="cat-desc" hint="Opcional">
                <textarea
                  id="cat-desc"
                  name="description"
                  value={form.description}
                  onChange={(e) => setForm({ ...form, description: e.target.value })}
                  rows={2}
                  maxLength={300}
                  className="w-full rounded-md border border-zinc-200 bg-white p-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-zinc-400"
                />
              </FormField>

              <div className="flex justify-end gap-2 pt-2">
                <Button type="button" variant="outline" onClick={() => setFormOpen(false)}>
                  Cancelar
                </Button>
                <Button type="submit" className="gap-2">
                  <CheckCircle2 className="h-4 w-4" />
                  {editing ? 'Salvar' : 'Criar'}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      <ConfirmDialog
        open={!!confirmDelete}
        onOpenChange={(open) => !open && setConfirmDelete(null)}
        title="Remover categoria?"
        description={
          confirmDelete
            ? `A categoria "${confirmDelete.name}" será removida. Despesas vinculadas ficam sem categoria.`
            : null
        }
        confirmLabel="Remover"
        destructive
        onConfirm={handleDelete}
      />
    </AdminStorePageShell>
  )
}
