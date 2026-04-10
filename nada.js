async confirmPayment() {
    const payBtn = document.getElementById('payBtn');
    payBtn.disabled = true;
    payBtn.textContent = 'Procesando...';

    const token = localStorage.getItem('sanctum_token');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const headers = {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Authorization': token ? `Bearer ${token}` : ''
    };

    try {
        const res = await fetch('/api/compras/confirmar', {
            method: 'POST', headers, credentials: 'include',
            body: JSON.stringify({ metodo_pago: 'tarjeta' })
        });
        const data = await res.json().catch(() => ({}));

        if (!res.ok || !data.success) {
            throw new Error(data.message || `HTTP ${res.status}`);
        }

        clearInterval(this.paymentTimerInterval);
        localStorage.removeItem('seatmap_cart');
        this.selectedSeats.clear();
        this.reservasActivas = [];
        this.updateSeatVisuals();
        this.updateCart();
        this.closePaymentModal();

        alert(`¡Compra confirmada! Total: ${Number(data.total || 0).toFixed(2)}€`);
        window.location.href = '/eventos';

    } catch (error) {
        console.error('Error al confirmar pago:', error);
        alert('Error al procesar el pago: ' + error.message);
        payBtn.disabled = false;
        payBtn.textContent = 'Pagar ahora';
    }
}