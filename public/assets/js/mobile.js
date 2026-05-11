/**
 * Mobile JS - Admin Mobile Multi Menu
 * 
 * JavaScript isolado e otimizado para mobile.
 * Não depende de nenhum JS desktop.
 * 
 * @author Multi Menu
 * @version 1.0.0
 */

(function() {
    'use strict';

    // ========= MOBILE APP =========
    const MobileApp = {
        config: {
            apiBase: '/api',
            refreshInterval: 30000, // 30 segundos
            toastDuration: 3000,
        },

        // Inicialização
        init() {
            this.initEventListeners();
            this.initPullToRefresh();
            this.initOrderActions();
            this.initProductToggles();
            this.initTabs();
            this.initAutoRefresh();
            this.checkPendingOrders();
            console.log('[Mobile App] Inicializado');
        },

        // Event Listeners globais
        initEventListeners() {
            // Prevenir zoom duplo-toque
            let lastTouchEnd = 0;
            document.addEventListener('touchend', (e) => {
                const now = Date.now();
                if (now - lastTouchEnd <= 300) {
                    e.preventDefault();
                }
                lastTouchEnd = now;
            }, { passive: false });

            // Fechar modais ao clicar fora
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('modal-overlay')) {
                    this.closeModal();
                }
            });

            // Vibração em botões de ação
            document.querySelectorAll('.btn-action').forEach(btn => {
                btn.addEventListener('click', () => {
                    if ('vibrate' in navigator) {
                        navigator.vibrate(10);
                    }
                });
            });
        },

        // Pull to Refresh
        initPullToRefresh() {
            let startY = 0;
            let pulling = false;
            const threshold = 80;
            const main = document.querySelector('.mobile-main');
            if (!main) return;

            const indicator = document.createElement('div');
            indicator.className = 'ptr-indicator';
            indicator.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 4v6h-6M1 20v-6h6"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
            `;
            document.body.appendChild(indicator);

            main.addEventListener('touchstart', (e) => {
                if (window.scrollY === 0) {
                    startY = e.touches[0].pageY;
                    pulling = true;
                }
            }, { passive: true });

            main.addEventListener('touchmove', (e) => {
                if (!pulling) return;
                const y = e.touches[0].pageY;
                const diff = y - startY;
                
                if (diff > 0 && diff < threshold * 2) {
                    indicator.style.transform = `translateX(-50%) translateY(${Math.min(diff / 2, threshold)}px)`;
                    if (diff > threshold) {
                        indicator.classList.add('active');
                    }
                }
            }, { passive: true });

            main.addEventListener('touchend', () => {
                if (pulling && indicator.classList.contains('active')) {
                    location.reload();
                }
                pulling = false;
                indicator.style.transform = '';
                indicator.classList.remove('active');
            });
        },

        // Ações de pedidos
        initOrderActions() {
            document.querySelectorAll('[data-action]').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const action = btn.dataset.action;
                    const orderId = btn.dataset.order;
                    
                    if (!orderId) return;

                    const statusMap = {
                        'confirm': 'confirmed',
                        'cancel': 'cancelled',
                        'preparing': 'preparing',
                        'ready': 'ready',
                        'delivered': 'delivered'
                    };

                    const newStatus = statusMap[action];
                    if (!newStatus) return;

                    // Confirmação para cancelar
                    if (action === 'cancel') {
                        if (!confirm('Tem certeza que deseja recusar este pedido?')) {
                            return;
                        }
                    }

                    await this.updateOrderStatus(orderId, newStatus);
                });
            });
        },

        // Toggle de produtos
        initProductToggles() {
            document.querySelectorAll('.toggle-availability').forEach(toggle => {
                toggle.addEventListener('change', async (e) => {
                    const productId = e.target.dataset.product;
                    const isAvailable = e.target.checked;
                    
                    try {
                        const response = await fetch(`/products/${productId}/toggle`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': window._csrfToken || '',
                            },
                            body: JSON.stringify({ is_available: isAvailable })
                        });

                        if (!response.ok) throw new Error('Falha ao atualizar');

                        this.showToast(
                            isAvailable ? 'Produto ativado' : 'Produto desativado',
                            isAvailable ? 'success' : 'warning'
                        );

                        // Atualiza visual do card
                        const card = e.target.closest('.product-card');
                        if (card) {
                            card.dataset.available = isAvailable ? '1' : '0';
                            const unavailable = card.querySelector('.product-card__unavailable');
                            if (unavailable) {
                                unavailable.style.display = isAvailable ? 'none' : 'flex';
                            }
                        }
                    } catch (error) {
                        console.error('Erro ao atualizar produto:', error);
                        e.target.checked = !isAvailable; // Reverte
                        this.showToast('Erro ao atualizar produto', 'error');
                    }
                });
            });
        },

        // Tabs
        initTabs() {
            document.querySelectorAll('.tabs').forEach(tabsContainer => {
                const tabs = tabsContainer.querySelectorAll('.tab');
                tabs.forEach(tab => {
                    tab.addEventListener('click', () => {
                        tabs.forEach(t => t.classList.remove('active'));
                        tab.classList.add('active');
                        
                        const target = tab.dataset.target;
                        if (target) {
                            document.querySelectorAll('.tab-content').forEach(content => {
                                content.classList.add('hidden');
                            });
                            document.getElementById(target)?.classList.remove('hidden');
                        }
                    });
                });
            });
        },

        // Auto refresh para pedidos
        initAutoRefresh() {
            if (document.querySelector('.orders-list')) {
                setInterval(() => {
                    this.checkPendingOrders();
                }, this.config.refreshInterval);
            }
        },

        // Verifica pedidos pendentes
        async checkPendingOrders() {
            try {
                const response = await fetch('/api/stats');
                if (!response.ok) return;
                
                const data = await response.json();
                const badge = document.getElementById('ordersBadge');
                
                if (badge && data.pending_orders !== undefined) {
                    if (data.pending_orders > 0) {
                        badge.textContent = data.pending_orders;
                        badge.style.display = 'flex';
                        
                        // Vibra se tiver novos pedidos
                        if ('vibrate' in navigator) {
                            navigator.vibrate([200, 100, 200]);
                        }
                    } else {
                        badge.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Erro ao verificar pedidos:', error);
            }
        },

        // Atualiza status do pedido
        async updateOrderStatus(orderId, status) {
            this.showLoading();
            
            try {
                const response = await fetch('/orders/setStatus', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': window._csrfToken || '',
                    },
                    body: `id=${orderId}&status=${status}`
                });

                if (!response.ok) throw new Error('Falha ao atualizar status');

                this.showToast('Pedido atualizado com sucesso!', 'success');
                
                // Atualiza o card do pedido
                const card = document.querySelector(`[data-order-id="${orderId}"]`);
                if (card) {
                    // Simples: recarrega a página para atualizar
                    location.reload();
                }
            } catch (error) {
                console.error('Erro ao atualizar pedido:', error);
                this.showToast('Erro ao atualizar pedido', 'error');
            } finally {
                this.hideLoading();
            }
        },

        toast(message, type = 'info') {
            this.showToast(message, type);
        },

        // Toast notifications
        showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `toast toast--${type}`;
            toast.innerHTML = `
                <span>${message}</span>
            `;
            
            container.appendChild(toast);

            // Remove após duração
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(20px)';
                setTimeout(() => toast.remove(), 300);
            }, this.config.toastDuration);
        },

        // Loading overlay
        showLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.style.display = 'flex';
        },

        hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.style.display = 'none';
        },

        // Modal
        openModal(content) {
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = `<div class="modal-content">${content}</div>`;
            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            const modal = document.querySelector('.modal-overlay');
            if (modal) {
                modal.remove();
                document.body.style.overflow = '';
            }
        },

        // Format helpers
        formatMoney(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(value);
        },

        formatTime(date) {
            return new Intl.DateTimeFormat('pt-BR', {
                hour: '2-digit',
                minute: '2-digit'
            }).format(new Date(date));
        }
    };

    // ========= NOTIFICATIONS =========
    const Notifications = {
        async init() {
            if (!('Notification' in window)) {
                console.log('Notificações não suportadas');
                return;
            }

            if (Notification.permission === 'default') {
                await this.requestPermission();
            }

            if (Notification.permission === 'granted') {
                await this.subscribe();
            }
        },

        async requestPermission() {
            const permission = await Notification.requestPermission();
            console.log('Permissão de notificação:', permission);
            return permission;
        },

        async subscribe() {
            try {
                const registration = await navigator.serviceWorker.ready;
                
                // Busca VAPID key
                const response = await fetch('/push/vapid-key');
                if (!response.ok) return;
                
                const { vapidPublicKey } = await response.json();
                if (!vapidPublicKey) return;

                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.urlBase64ToUint8Array(vapidPublicKey)
                });

                // Envia subscription para o servidor
                await fetch('/push/subscribe', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(subscription)
                });

                console.log('Inscrito em push notifications');
            } catch (error) {
                console.error('Erro ao inscrever para push:', error);
            }
        },

        urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/-/g, '+')
                .replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }
    };

    // ========= INITIALIZE =========
    document.addEventListener('DOMContentLoaded', () => {
        MobileApp.init();
        Notifications.init();
    });

    // Exporta para uso global se necessário
    window.MobileApp = MobileApp;

})();
