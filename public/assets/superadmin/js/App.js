import { createElement as h, useState, useMemo, useEffect } from 'react';
import htm from 'htm';
import {
  Store,
  Network,
  Sparkles,
  Search,
  Plus,
  Pencil,
  Trash2,
  Power,
  LogOut,
  Menu,
  ChevronRight,
  X,
} from 'lucide-react';
import { INITIAL_STORES, MOCK_NETWORKS, SEGMENT_OPTIONS, UFS } from './mock.js';
import { Button as Btn } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/components/ui/utils';

const html = htm.bind(h);

function getCsrf() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function getAdminName() {
  return document.querySelector('meta[name="superadmin-name"]')?.getAttribute('content') || '';
}

function getLogoutUrl() {
  return document.querySelector('meta[name="superadmin-logout"]')?.getAttribute('content') || '/superadmin/logout';
}

export function App() {
  const [tab, setTab] = useState('stores');
  const [stores, setStores] = useState(() => [...INITIAL_STORES]);
  const [networks, setNetworks] = useState(() => [...MOCK_NETWORKS]);

  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [tipoFilter, setTipoFilter] = useState('all');
  const [networkFilter, setNetworkFilter] = useState('');

  const [sheetOpen, setSheetOpen] = useState(false);
  const [selected, setSelected] = useState(null);

  const [dialogStore, setDialogStore] = useState(false);
  const [dialogNet, setDialogNet] = useState(false);
  const [newNetName, setNewNetName] = useState('');

  const [form, setForm] = useState({
    name: '',
    cnpj: '',
    responsible: '',
    email: '',
    phone: '',
    segment: 'Restaurante',
    uf: 'SP',
    city: '',
    addressFull: '',
    kind: 'standalone',
    networkId: '',
    status: 'active',
  });

  const [toast, setToast] = useState(null);

  const [chatMessages, setChatMessages] = useState([]);
  const [chatInput, setChatInput] = useState('');
  const [chatLoading, setChatLoading] = useState(false);

  useEffect(() => {
    if (!toast) return;
    const t = setTimeout(() => setToast(null), 3200);
    return () => clearTimeout(t);
  }, [toast]);

  const filtered = useMemo(() => {
    return stores.filter((s) => {
      if (statusFilter === 'active' && s.status !== 'active') return false;
      if (statusFilter === 'inactive' && s.status !== 'inactive') return false;
      if (tipoFilter === 'standalone' && s.kind !== 'standalone') return false;
      if (tipoFilter === 'network' && s.kind !== 'network') return false;
      if (networkFilter && s.networkId !== networkFilter) return false;
      const q = search.trim().toLowerCase();
      if (q) {
        const blob = [s.name, s.city, s.cnpj, s.responsible, s.email || '', s.phone || '']
          .join(' ')
          .toLowerCase();
        if (!blob.includes(q)) return false;
      }
      return true;
    });
  }, [stores, search, statusFilter, tipoFilter, networkFilter]);

  const networkStats = useMemo(() => {
    return networks.map((n) => {
      const linked = stores.filter((s) => s.networkId === n.id);
      const active = linked.filter((s) => s.status === 'active').length;
      return { ...n, total: linked.length, activeCount: active };
    });
  }, [networks, stores]);

  function showToast(message, type = 'ok') {
    setToast({ message, type });
  }

  function openRow(s) {
    setSelected(s);
    setSheetOpen(true);
  }

  function toggleStore(st) {
    setStores((prev) =>
      prev.map((x) => (x.id === st.id ? { ...x, status: x.status === 'active' ? 'inactive' : 'active' } : x)),
    );
    showToast(`Status de "${st.name}" atualizado.`);
    if (selected?.id === st.id) {
      setSelected((cur) => (cur ? { ...cur, status: cur.status === 'active' ? 'inactive' : 'active' } : null));
    }
  }

  function deleteStore(st) {
    if (!window.confirm(`Excluir permanentemente "${st.name}"? (simulação — remove da lista local)`)) return;
    setStores((prev) => prev.filter((x) => x.id !== st.id));
    setSheetOpen(false);
    showToast('Loja removida da lista.', 'warn');
  }

  function submitNewStore(e) {
    e.preventDefault();
    if (!form.name.trim() || !form.cnpj.trim() || !form.email.trim()) {
      showToast('Preencha nome, CNPJ e e-mail.', 'err');
      return;
    }
    const net =
      form.kind === 'network' && form.networkId
        ? networks.find((n) => n.id === form.networkId)
        : null;
    const row = {
      id: 'n' + Date.now(),
      name: form.name.trim(),
      cnpj: form.cnpj.trim(),
      responsible: form.responsible.trim(),
      email: form.email.trim(),
      phone: form.phone.replace(/\D/g, ''),
      segment: form.segment,
      kind: form.kind === 'network' ? 'network' : 'standalone',
      networkId: net ? net.id : null,
      networkName: net ? net.name : null,
      status: form.status === 'inactive' ? 'inactive' : 'active',
      city: form.city.trim(),
      uf: form.uf,
      addressFull: form.addressFull.trim(),
      registeredAt: new Date().toISOString().slice(0, 10),
    };
    setStores((p) => [row, ...p]);
    setDialogStore(false);
    setForm({
      name: '',
      cnpj: '',
      responsible: '',
      email: '',
      phone: '',
      segment: 'Restaurante',
      uf: 'SP',
      city: '',
      addressFull: '',
      kind: 'standalone',
      networkId: '',
      status: 'active',
    });
    showToast('Loja cadastrada (mock local).');
  }

  function submitNewNetwork(e) {
    e.preventDefault();
    if (!newNetName.trim()) {
      showToast('Informe o nome da rede.', 'err');
      return;
    }
    const id = 'net_' + Date.now();
    setNetworks((p) => [...p, { id, name: newNetName.trim(), color: 'slate', accentClass: 'bg-slate-600' }]);
    setNewNetName('');
    setDialogNet(false);
    showToast('Rede criada (mock local).');
  }

  async function sendChat(forcedText) {
    const text = (forcedText != null ? forcedText : chatInput).trim();
    if (!text || chatLoading) return;
    setChatInput('');
    const next = [...chatMessages, { role: 'user', content: text }];
    setChatMessages(next);
    setChatLoading(true);
    try {
      const base =
        (typeof document !== 'undefined' &&
          document.querySelector('meta[name="app-base-path"]')?.getAttribute('content')) ||
        '';
      const url = (base ? base.replace(/\/$/, '') : '') + '/superadmin/api/chat';

      const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrf(),
          Accept: 'application/json',
        },
        body: JSON.stringify({
          messages: next.map((m) => ({ role: m.role, content: m.content })),
        }),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) {
        setChatMessages((m) => [
          ...m,
          {
            role: 'assistant',
            content: data.error || 'Não foi possível obter resposta. Configure ANTHROPIC_API_KEY e tente novamente.',
          },
        ]);
      } else {
        setChatMessages((m) => [...m, { role: 'assistant', content: data.content || '' }]);
      }
    } catch {
      setChatMessages((m) => [
        ...m,
        { role: 'assistant', content: 'Erro de rede ao falar com o assistente.' },
      ]);
    } finally {
      setChatLoading(false);
    }
  }

  const chips = [
    'Cadastrar nova loja',
    'Listar lojas inativas',
    'Relatório por segmento',
    'Como configurar cardápio?',
    'Lojas por estado',
  ];

  const sidebarItem = (id, Icon, label) => html`
    <button
      type="button"
      onClick=${() => setTab(id)}
      class=${cn(
        'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors',
        tab === id ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
      )}
    >
      ${h(Icon, { size: 18 })} ${label}
    </button>
  `;

  return html`<div class="flex min-h-screen flex-col md:flex-row">
    <aside class="flex w-full flex-shrink-0 flex-col border-r border-slate-800 bg-sidebar md:fixed md:left-0 md:top-0 md:h-screen md:w-56">
      <div class="flex items-center gap-2 border-b border-slate-800 px-4 py-4">
        ${h(Store, { className: 'text-indigo-400', size: 22 })}
        <div>
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">MultiMenu</div>
          <div class="text-sm font-bold text-white">Super Admin</div>
        </div>
      </div>
      <nav class="flex flex-1 flex-col gap-1 p-3">${sidebarItem('stores', Store, 'Lojas')} ${sidebarItem('networks', Network, 'Redes & Franquias')}
        ${sidebarItem('ai', Sparkles, 'Assistente IA')}</nav>
      <div class="border-t border-slate-800 p-3">
        <form method="post" action=${getLogoutUrl()} class="contents">
          <input type="hidden" name="csrf_token" value=${getCsrf()} />
          <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-700 py-2 text-sm text-slate-300 hover:bg-slate-800"
          >
            ${h(LogOut, { size: 16 })} Sair
          </button>
        </form>
      </div>
    </aside>

    <div class="flex min-h-screen flex-1 flex-col md:pl-56">
      <header class="sticky top-0 z-10 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white/95 px-4 py-3 backdrop-blur">
        <div class="flex items-center gap-2 text-slate-600 md:hidden">${h(Menu, { size: 20 })} <span class="text-sm font-medium">Painel</span></div>
        <div class="hidden md:block">
          <h1 class="text-lg font-semibold text-slate-900">
            ${tab === 'stores' ? 'Lojas' : tab === 'networks' ? 'Redes & Franquias' : 'Assistente IA'}
          </h1>
          <p class="text-xs text-slate-500">Gestão operacional · ${getAdminName()}</p>
        </div>
        ${tab === 'stores'
          ? html`<${Btn} onClick=${() => setDialogStore(true)} className="gap-2">${h(Plus, { size: 16 })} Nova Loja</${Btn}>`
          : tab === 'networks'
            ? html`<${Btn} onClick=${() => setDialogNet(true)} variant="outline" className="gap-2">${h(Plus, { size: 16 })} Nova Rede</${Btn}>`
            : html`<div />`}
      </header>

      <main class="flex-1 overflow-auto p-4">
        ${toast &&
        html`<div
          class=${cn(
            'fixed bottom-4 right-4 z-50 rounded-lg px-4 py-2 text-sm shadow-lg',
            toast.type === 'err' ? 'bg-red-600 text-white' : 'bg-slate-900 text-white',
          )}
        >
          ${toast.message}
        </div>`}

        ${tab === 'stores' &&
        html`<div class="space-y-4">
          <${Card} className="p-4">
            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
              <div>
                <label class="text-xs font-medium text-slate-500">Busca</label>
                <div class="relative mt-1">
                  ${h(Search, { className: 'absolute left-2.5 top-2.5 h-4 w-4 text-slate-400', size: 16 })}
                  <input
                    class="h-9 w-full rounded-md border border-slate-200 bg-white pl-9 pr-3 text-sm"
                    placeholder="Nome, cidade, CNPJ..."
                    value=${search}
                    onInput=${(e) => setSearch(e.target.value)}
                  />
                </div>
              </div>
              <div>
                <label class="text-xs font-medium text-slate-500">Status</label>
                <select
                  class="mt-1 h-9 w-full rounded-md border border-slate-200 bg-white px-2 text-sm"
                  value=${statusFilter}
                  onChange=${(e) => setStatusFilter(e.target.value)}
                >
                  <option value="all">Todos</option>
                  <option value="active">Ativo</option>
                  <option value="inactive">Inativo</option>
                </select>
              </div>
              <div>
                <label class="text-xs font-medium text-slate-500">Tipo</label>
                <select
                  class="mt-1 h-9 w-full rounded-md border border-slate-200 bg-white px-2 text-sm"
                  value=${tipoFilter}
                  onChange=${(e) => setTipoFilter(e.target.value)}
                >
                  <option value="all">Todos</option>
                  <option value="standalone">Avulsa</option>
                  <option value="network">Em rede</option>
                </select>
              </div>
              <div>
                <label class="text-xs font-medium text-slate-500">Rede</label>
                <select
                  class="mt-1 h-9 w-full rounded-md border border-slate-200 bg-white px-2 text-sm"
                  value=${networkFilter}
                  onChange=${(e) => setNetworkFilter(e.target.value)}
                >
                  <option value="">Todas</option>
                  ${networks.map((n) => html`<option value=${n.id} key=${n.id}>${n.name}</option>`)}
                </select>
              </div>
            </div>
          </${Card}>

          <${Card} className="overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                  <tr>
                    <th class="px-3 py-2">Estabelecimento</th>
                    <th class="px-3 py-2">Segmento</th>
                    <th class="px-3 py-2">Tipo / Rede</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2">Cidade / UF</th>
                    <th class="px-3 py-2">Cadastro</th>
                    <th class="px-3 py-2 text-right">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  ${filtered.map(
                    (s) => html`<tr
                      key=${s.id}
                      class="cursor-pointer border-b border-slate-100 hover:bg-slate-50"
                      onClick=${() => openRow(s)}
                    >
                      <td class="px-3 py-3">
                        <div class="font-medium text-slate-900">${s.name}</div>
                        <div class="text-xs text-slate-500">${s.cnpj}</div>
                      </td>
                      <td class="px-3 py-3">${s.segment}</td>
                      <td class="px-3 py-3">
                        ${s.kind === 'network'
                          ? html`<span class="text-slate-700">${s.networkName}</span>`
                          : html`<span class="text-slate-500">Avulsa</span>`}
                      </td>
                      <td class="px-3 py-3">
                        ${s.status === 'active'
                          ? html`<${Badge} variant="success">Ativa</${Badge}>`
                          : html`<${Badge} variant="neutral">Inativa</${Badge}>`}
                      </td>
                      <td class="px-3 py-3">${s.city} — ${s.uf}</td>
                      <td class="px-3 py-3 text-slate-600">${s.registeredAt}</td>
                      <td class="px-3 py-3 text-right" onClick=${(e) => e.stopPropagation()}>
                        <${Btn} size="sm" variant="ghost" onClick=${() => openRow(s)}>${h(Pencil, { size: 14 })}</${Btn}>
                      </td>
                    </tr>`,
                  )}
                </tbody>
              </table>
            </div>
          </${Card}>
        </div>`}

        ${tab === 'networks' &&
        html`<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          ${networkStats.map(
            (n) => html`<${Card} key=${n.id} className="p-4">
              <div class="mb-3 flex items-start justify-between gap-2">
                <div class="flex items-center gap-2">
                  <div class=${cn('h-2 w-2 rounded-full', n.accentClass)} />
                  <h3 class="font-semibold text-slate-900">${n.name}</h3>
                </div>
                ${h(ChevronRight, { className: 'text-slate-300', size: 18 })}
              </div>
              <p class="text-sm text-slate-600">
                <strong>${n.total}</strong> loja(s) · <strong class="text-emerald-600">${n.activeCount}</strong> ativa(s)
              </p>
              <ul class="mt-3 space-y-1 border-t border-slate-100 pt-3">
                ${stores
                  .filter((s) => s.networkId === n.id)
                  .map(
                    (s) => html`<li class="flex items-center justify-between text-sm" key=${s.id}>
                      <span>${s.name}</span>
                      ${s.status === 'active'
                        ? html`<${Badge} variant="success">Ativa</${Badge}>`
                        : html`<${Badge} variant="neutral">Inativa</${Badge}>`}
                    </li>`,
                  )}
                ${stores.filter((s) => s.networkId === n.id).length === 0 &&
                html`<li class="text-sm text-slate-400">Nenhuma loja vinculada</li>`}
              </ul>
            </${Card}>`,
          )}
        </div>`}

        ${tab === 'ai' &&
        html`<div class="flex h-[calc(100vh-8rem)] flex-col gap-3">
          <div class="flex flex-wrap gap-2">
            ${chips.map(
              (c) => html`<button
                type="button"
                key=${c}
                class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                onClick=${() => sendChat(c)}
              >
                ${c}
              </button>`,
            )}
          </div>
          <${Card} className="flex min-h-0 flex-1 flex-col">
            <div class="flex-1 space-y-3 overflow-y-auto p-4">
              ${chatMessages.length === 0 &&
              html`<p class="text-center text-sm text-slate-500">Envie uma mensagem ao assistente operacional.</p>`}
              ${chatMessages.map(
                (m, i) => html`<div key=${i} class=${cn('flex', m.role === 'user' ? 'justify-end' : 'justify-start')}>
                  <div
                    class=${cn(
                      'max-w-[85%] rounded-2xl px-4 py-2 text-sm leading-relaxed shadow-sm',
                      m.role === 'user' ? 'rounded-br-sm bg-slate-900 text-white' : 'rounded-bl-sm border border-slate-200 bg-white text-slate-800',
                    )}
                  >
                    ${m.content}
                  </div>
                </div>`,
              )}
              ${chatLoading &&
              html`<div class="flex justify-start">
                <div class="flex gap-1 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                  <span class="h-2 w-2 animate-bounce rounded-full bg-slate-400" style=${{ animationDelay: '0ms' }} />
                  <span class="h-2 w-2 animate-bounce rounded-full bg-slate-400" style=${{ animationDelay: '150ms' }} />
                  <span class="h-2 w-2 animate-bounce rounded-full bg-slate-400" style=${{ animationDelay: '300ms' }} />
                </div>
              </div>`}
            </div>
            <div class="border-t border-slate-100 p-3">
              <form
                class="flex gap-2"
                onSubmit=${(e) => {
                  e.preventDefault();
                  sendChat();
                }}
              >
                <input
                  class="h-10 flex-1 rounded-md border border-slate-200 px-3 text-sm"
                  placeholder="Digite sua pergunta..."
                  value=${chatInput}
                  onInput=${(e) => setChatInput(e.target.value)}
                  disabled=${chatLoading}
                />
                <${Btn} type="submit" disabled=${chatLoading}>Enviar</${Btn}>
              </form>
            </div>
          </${Card}>
        </div>`}
      </main>
    </div>

    ${sheetOpen &&
    selected &&
    html`<div class="fixed inset-0 z-40">
      <div class="absolute inset-0 bg-black/40" onClick=${() => setSheetOpen(false)} />
      <aside class="absolute right-0 top-0 z-50 flex h-full w-full max-w-md flex-col border-l border-slate-200 bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
          <h2 class="font-semibold text-slate-900">${selected.name}</h2>
          <button type="button" class="rounded-md p-1 hover:bg-slate-100" onClick=${() => setSheetOpen(false)}>${h(X, { size: 20 })}</button>
        </div>
        <div class="flex-1 space-y-3 overflow-y-auto p-4 text-sm">
          <div><span class="text-slate-500">CNPJ</span><div class="font-medium">${selected.cnpj}</div></div>
          <div><span class="text-slate-500">Responsável</span><div>${selected.responsible || '—'}</div></div>
          <div><span class="text-slate-500">E-mail</span><div>${selected.email || '—'}</div></div>
          <div><span class="text-slate-500">Telefone</span><div>${selected.phone || '—'}</div></div>
          <div><span class="text-slate-500">Segmento</span><div>${selected.segment}</div></div>
          <div><span class="text-slate-500">Endereço</span><div>${selected.addressFull || '—'}</div></div>
          <div><span class="text-slate-500">Tipo</span><div>${selected.kind === 'network' ? selected.networkName : 'Avulsa'}</div></div>
          <div><span class="text-slate-500">Status</span><div>${selected.status === 'active' ? 'Ativa' : 'Inativa'}</div></div>
        </div>
        <div class="flex flex-wrap gap-2 border-t border-slate-100 p-4">
          <${Btn} variant="outline" onClick=${() => showToast('Edição completa em breve — use listagem.')}>${h(Pencil, { size: 14 })} Editar</${Btn}>
          <${Btn} variant="outline" onClick=${() => toggleStore(selected)}>${h(Power, { size: 14 })} ${selected.status === 'active' ? 'Desativar' : 'Ativar'}</${Btn}>
          <${Btn} variant="destructive" onClick=${() => deleteStore(selected)}>${h(Trash2, { size: 14 })} Excluir</${Btn}>
        </div>
      </aside>
    </div>`}

    ${dialogStore &&
    html`<div class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" onClick=${() => setDialogStore(false)} />
      <${Card} className="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto p-6">
        <h3 class="mb-4 text-lg font-semibold">Nova loja</h3>
        <form class="space-y-3" onSubmit=${submitNewStore}>
          <div><label class="text-xs font-medium text-slate-600">Nome *</label>
            <input required class="mt-1 h-9 w-full rounded-md border border-slate-200 px-2 text-sm" value=${form.name} onInput=${(e) => setForm((f) => ({ ...f, name: e.target.value }))} /></div>
          <div><label class="text-xs font-medium text-slate-600">CNPJ *</label>
            <input required class="mt-1 h-9 w-full rounded-md border border-slate-200 px-2 text-sm" value=${form.cnpj} onInput=${(e) => setForm((f) => ({ ...f, cnpj: e.target.value }))} /></div>
          <div><label class="text-xs font-medium text-slate-600">Responsável</label>
            <input class="mt-1 h-9 w-full rounded-md border border-slate-200 px-2 text-sm" value=${form.responsible} onInput=${(e) => setForm((f) => ({ ...f, responsible: e.target.value }))} /></div>
          <div><label class="text-xs font-medium text-slate-600">E-mail *</label>
            <input required type="email" class="mt-1 h-9 w-full rounded-md border border-slate-200 px-2 text-sm" value=${form.email} onInput=${(e) => setForm((f) => ({ ...f, email: e.target.value }))} /></div>
          <div><label class="text-xs font-medium text-slate-600">Telefone</label>
            <input class="mt-1 h-9 w-full rounded-md border border-slate-200 px-2 text-sm" value=${form.phone} onInput=${(e) => setForm((f) => ({ ...f, phone: e.target.value }))} /></div>
          <div>
            <label class="text-xs font-medium text-slate-600">Segmento *</label>
            <select class="mt-1 h-9 w-full rounded-md border border-slate-200 px-2 text-sm" value=${form.segment} onChange=${(e) => setForm((f) => ({ ...f, segment: e.target.value }))}>
              ${SEGMENT_OPTIONS.map((o) => html`<option value=${o}>${o}</option>`)}
            </select>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div><label class="text-xs font-medium text-slate-600">UF</label>
              <select class="mt-1 h-9 w-full rounded-md border border-slate-200 px-1 text-sm" value=${form.uf} onChange=${(e) => setForm((f) => ({ ...f, uf: e.target.value }))}>
                ${UFS.map((u) => html`<option value=${u}>${u}</option>`)}
              </select></div>
            <div><label class="text-xs font-medium text-slate-600">Cidade</label>
              <input class="mt-1 h-9 w-full rounded-md border border-slate-200 px-2 text-sm" value=${form.city} onInput=${(e) => setForm((f) => ({ ...f, city: e.target.value }))} /></div>
          </div>
          <div><label class="text-xs font-medium text-slate-600">Endereço completo</label>
            <textarea class="mt-1 w-full rounded-md border border-slate-200 px-2 py-1 text-sm" rows="2" value=${form.addressFull} onInput=${(e) => setForm((f) => ({ ...f, addressFull: e.target.value }))} /></div>
          <div>
            <label class="text-xs font-medium text-slate-600">Tipo</label>
            <select class="mt-1 h-9 w-full rounded-md border border-slate-200 px-2 text-sm" value=${form.kind} onChange=${(e) => setForm((f) => ({ ...f, kind: e.target.value }))}>
              <option value="standalone">Avulsa</option>
              <option value="network">Parte de uma rede</option>
            </select>
          </div>
          ${form.kind === 'network' &&
          html`<div>
            <label class="text-xs font-medium text-slate-600">Rede</label>
            <select class="mt-1 h-9 w-full rounded-md border border-slate-200 px-2 text-sm" value=${form.networkId} onChange=${(e) => setForm((f) => ({ ...f, networkId: e.target.value }))}>
              <option value="">Selecione…</option>
              ${networks.map((n) => html`<option value=${n.id}>${n.name}</option>`)}
            </select>
          </div>`}
          <div>
            <label class="text-xs font-medium text-slate-600">Status</label>
            <select class="mt-1 h-9 w-full rounded-md border border-slate-200 px-2 text-sm" value=${form.status} onChange=${(e) => setForm((f) => ({ ...f, status: e.target.value }))}>
              <option value="active">Ativo</option>
              <option value="inactive">Inativo</option>
            </select>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <${Btn} type="button" variant="outline" onClick=${() => setDialogStore(false)}>Cancelar</${Btn}>
            <${Btn} type="submit">Salvar</${Btn}>
          </div>
        </form>
      </${Card}>
    </div>`}

    ${dialogNet &&
    html`<div class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" onClick=${() => setDialogNet(false)} />
      <${Card} className="relative z-10 w-full max-w-md p-6">
        <h3 class="mb-3 text-lg font-semibold">Nova rede</h3>
        <form onSubmit=${submitNewNetwork}>
          <label class="text-xs font-medium text-slate-600">Nome da rede</label>
          <input
            class="mt-1 h-9 w-full rounded-md border border-slate-200 px-2 text-sm"
            value=${newNetName}
            onInput=${(e) => setNewNetName(e.target.value)}
            placeholder="Ex.: Grupo Exemplo"
          />
          <div class="mt-4 flex justify-end gap-2">
            <${Btn} type="button" variant="outline" onClick=${() => setDialogNet(false)}>Cancelar</${Btn}>
            <${Btn} type="submit">Criar</${Btn}>
          </div>
        </form>
      </${Card}>
    </div>`}
  </div>`;
}

