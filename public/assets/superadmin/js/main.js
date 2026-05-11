import { createElement as h } from 'react';
import { createRoot } from 'react-dom/client';
import { App } from './App.js';

const el = document.getElementById('superadmin-root');

if (el) {
  createRoot(el).render(h(App));
}
