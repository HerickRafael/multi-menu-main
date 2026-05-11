import { createElement as h } from 'react';
import htm from 'htm';
import { cn } from '@/components/ui/utils';

const html = htm.bind(h);

export function Card({ className = '', children }) {
  return html`<div class=${cn('rounded-xl border border-slate-200 bg-white shadow-sm', className)}>${children}</div>`;
}
