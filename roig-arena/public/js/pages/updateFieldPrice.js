// Formatea un valor numérico como precio visible en formato español.
function formatPrice(value) {
    const numericValue = Number.parseFloat(value);

    if (Number.isNaN(numericValue)) {
        return value;
    }

    return new Intl.NumberFormat('es-ES', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(numericValue) + '€';
}

// Activa todos los editores inline de precio que existan en la página.
function initPriceInlineEditors() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    if (!csrfToken) return;

    // Cada formulario representa un precio editable dentro de la interfaz.
    document.querySelectorAll('[data-sector-price-editor]').forEach((form) => {
        if (form.dataset.priceEditorInitialized === 'true') return;

        // Buscamos el contenedor visual cercano para alternar el texto y el formulario.
        const container = form.closest('td, .pricing-table-actions, .event-price-editor') || form.parentElement;
        const toggleButton = container?.querySelector('[data-sector_price-toggle], [data-sector-price-toggle]');
        const input = form.querySelector('[data-sector-price-input]');
        const displaySelector = form.dataset.sectorPriceDisplay;
        const display = displaySelector ? document.querySelector(displaySelector) : container?.querySelector('[data-sector-price-display]');

        if (!toggleButton || !input || !display || !form.action) return;

        // Muestra el editor y prepara el input para escribir.
        const openEditor = () => {
            form.hidden = false;
            toggleButton.hidden = true;
            input.focus();
            input.select();
        };

        // Devuelve la vista a su estado normal.
        const closeEditor = () => {
            form.hidden = true;
            toggleButton.hidden = false;
        };

        // El botón abre el modo edición.
        toggleButton.addEventListener('click', openEditor);

        // Escape revierte el valor actual y cierra el editor.
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                input.value = input.defaultValue;
                closeEditor();
            }
        });

        // Guardamos el nuevo precio sin recargar la página.
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const newValue = input.value.trim();
            if (!newValue) {
                input.setCustomValidity('El precio no puede estar vacío.');
                input.reportValidity();
                return;
            }

            try {
                // El backend espera el campo precio y la acción PATCH simulada.
                const formData = new FormData(form);
                formData.set('precio', newValue);
                formData.set('_method', 'PATCH');

                const response = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'include',
                    mode: 'cors',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const message = payload?.message || payload?.error || 'No se pudo actualizar el precio.';
                    input.setCustomValidity(message);
                    input.reportValidity();
                    input.focus();
                    return;
                }

                // Si todo va bien, actualizamos el texto visible con el valor confirmado.
                const updatedPrice = payload?.data?.precio ?? newValue;
                display.textContent = formatPrice(updatedPrice);
                input.defaultValue = Number.parseFloat(updatedPrice).toFixed(2);
                input.value = input.defaultValue;
                closeEditor();
            } catch (error) {
                // Cualquier fallo de red se muestra directamente en el campo.
                input.setCustomValidity('Error de red al actualizar el precio.');
                input.reportValidity();
            }
        });

        // Marcamos este formulario para no inicializarlo dos veces.
        form.dataset.priceEditorInitialized = 'true';
    });
}

// Inicialización automática cuando el DOM ya está listo.
document.addEventListener('DOMContentLoaded', initPriceInlineEditors);
// Dejamos la función disponible por si otra vista necesita invocarla manualmente.
window.initPriceInlineEditors = initPriceInlineEditors;
