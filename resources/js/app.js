import './bootstrap';

// Ajout rapide au panier depuis les vignettes produit (produits sans option).
window.nozaQuickAdd = async (productId, el) => {
    el?.setAttribute('aria-busy', 'true');
    try {
        const res = await window.axios.post('/panier/ajouter', { product_id: productId, qty: 1 });
        window.dispatchEvent(new CustomEvent('cart:updated', { detail: res.data }));
        window.dispatchEvent(new CustomEvent('toast', { detail: { message: res.data.message, product: res.data.product } }));
    } catch (e) {
        const msg = e.response?.data?.message ?? 'Impossible d\'ajouter ce produit.';
        window.dispatchEvent(new CustomEvent('toast', { detail: { message: msg, error: true } }));
    } finally {
        el?.removeAttribute('aria-busy');
    }
};

// Compte à rebours des ventes flash : <span data-countdown="ISO"></span>
const tick = () => {
    document.querySelectorAll('[data-countdown]').forEach((n) => {
        const ms = new Date(n.dataset.countdown) - Date.now();
        if (ms <= 0) { n.textContent = 'Terminée'; return; }
        const d = Math.floor(ms / 864e5), h = Math.floor(ms / 36e5) % 24, m = Math.floor(ms / 6e4) % 60, s = Math.floor(ms / 1e3) % 60;
        const p = (x) => String(x).padStart(2, '0');
        n.textContent = (d ? d + ' j ' : '') + `${p(h)}:${p(m)}:${p(s)}`;
    });
};
setInterval(tick, 1000);
document.addEventListener('DOMContentLoaded', tick);
document.addEventListener('livewire:navigated', tick);
