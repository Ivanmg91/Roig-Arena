// Componente reutilizable para editar un campo directamente en pantalla sin recargar la página.
function initInlineEditor(options) {
    const {
        containerSelector,
        displaySelector,
        toggleSelector,
        formSelector,
        inputSelector,
        updateUrl: updateUrlOption,
        fieldName = 'value' // Nombre del campo en el form (por defecto 'value')
    } = options;

    // Buscamos el bloque que contiene el editor y salimos si no existe en esta vista.
    const container = document.querySelector(containerSelector);
    if (!container) return;

    // La URL puede venir en opciones, en un data-attribute o en el action del formulario.
    const updateUrl = updateUrlOption || container.dataset.updateUrl || container.querySelector(formSelector)?.getAttribute('action');
    const toggleButton = container.querySelector(toggleSelector);
    const titleDisplay = container.querySelector(displaySelector);
    const form = container.querySelector(formSelector);
    const input = container.querySelector(inputSelector);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // Si falta una pieza básica, no inicializamos nada para evitar errores en la interfaz.
    if (!toggleButton || !titleDisplay || !form || !input || !updateUrl || !csrfToken) return;

    // Algunos campos, como la fecha, necesitan una normalización especial antes de enviarse.
    const isDateField = fieldName === 'fecha';

    // Convierte valores de fecha a un formato consistente para editar y guardar.
    const normalizeDateValue = (value) => {
        if (!value) return '';
        if (/^\d{4}-\d{2}-\d{2}/.test(value)) {
            return value.slice(0, 10);
        }
        const match = value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
        if (match) {
            return `${match[3]}-${match[2]}-${match[1]}`;
        }
        return value;
    };

    // Convierte la fecha al formato legible que se muestra en pantalla.
    const formatDateForDisplay = (value) => {
        const normalizedValue = normalizeDateValue(value);
        const match = normalizedValue.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match) return value;
        return `${match[3]}/${match[2]}/${match[1]}`;
    };

    // Recupera el valor actual visible para usarlo al abrir o cancelar la edición.
    const getEditableValue = () => {
        const displayValue = titleDisplay.textContent.trim();
        if (!isDateField) {
            return displayValue;
        }

        const normalizedValue = normalizeDateValue(displayValue);
        return /^\d{4}-\d{2}-\d{2}$/.test(normalizedValue) ? normalizedValue : '';
    };

    const openEditor = () => {
        titleDisplay.hidden = true;
        // Muestra el formulario y oculta el texto estático para permitir la edición.
        toggleButton.hidden = true;
        form.hidden = false;
        input.hidden = false;
        if (isDateField) {
            input.value = input.value || getEditableValue();
        } else {
            input.value = input.value || getEditableValue();
        }
        input.focus();
        input.select();
    };

    // Restaura la vista normal cuando se cancela o termina la edición.
    const closeEditor = () => {
        form.hidden = true;
        input.hidden = true;
        toggleButton.hidden = false;
        titleDisplay.hidden = false;
        input.setCustomValidity('');
    };

    // Activamos el editor cuando el usuario pulsa el botón de editar.
    toggleButton.addEventListener('click', openEditor);

    // Limpiamos el error visual mientras el usuario corrige el valor.
    input.addEventListener('input', () => {
        input.setCustomValidity('');
    });

    // Escape cancela la edición y Enter intenta guardar el cambio.
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            input.value = getEditableValue();
            closeEditor();
        }
        if (event.key === 'Enter') {
            event.preventDefault();
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.click();
                return;
            }

            form.submit();
        }
    });

    // Guardamos el nuevo valor mediante fetch para actualizar solo ese campo.
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const newValue = input.value.trim();
        const normalizedValue = isDateField ? normalizeDateValue(newValue) : newValue;

        if (!normalizedValue) {
            input.setCustomValidity('El valor no puede estar vacío.');
            input.reportValidity();
            return;
        }

        try {
            const formData = new FormData(form);
            // Nos aseguramos de enviar el nombre real del campo que espera el backend.
            formData.set(fieldName, normalizedValue);
            formData.set('_method', 'PATCH');

            // Laravel procesa la actualización como una petición PATCH simulada.
            const response = await fetch(updateUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                // Si falla, mostramos el mensaje devuelto por el servidor en el propio campo.
                const message = payload?.message || payload?.error || 'No se pudo actualizar.';
                input.setCustomValidity(message);
                input.reportValidity();
                input.focus();
                return;
            }

            // Si todo va bien, actualizamos el texto visible con la respuesta del backend.
            const updatedValue = payload?.data?.[fieldName] ?? normalizedValue;
            const displayValue = isDateField ? formatDateForDisplay(updatedValue) : updatedValue;
            titleDisplay.textContent = displayValue;
            input.value = normalizedValue;
            closeEditor();
        } catch (error) {
            // Error de red o fallo inesperado al enviar la petición.
            input.setCustomValidity('Error de red al actualizar.');
            input.reportValidity();
        }
    });
}
// Inicializamos varias instancias del editor, una por cada campo editable de la página.
document.addEventListener('DOMContentLoaded', () => {
    // Título del evento.
    initInlineEditor({
        containerSelector: '[data-event-title-editor]',
        displaySelector: '[data-event-title-display]',
        toggleSelector: '[data-event-title-toggle]',
        formSelector: '[data-event-title-form]',
        inputSelector: '[data-event-title-input]',
        fieldName: 'nombre'
    });
    // Descripción corta.
    initInlineEditor({
        containerSelector: '[data-description-editor]',
        displaySelector: '[data-description-display]',
        toggleSelector: '[data-description-toggle]',
        formSelector: '[data-description-form]',
        inputSelector: '[data-description-input]',
        fieldName: 'descripcion_corta'
    });
    // Descripción larga.
    initInlineEditor({
        containerSelector: '[data-description_long-editor]',
        displaySelector: '[data-description_long-display]',
        toggleSelector: '[data-description_long-toggle]',
        formSelector: '[data-description_long-form]',
        inputSelector: '[data-description_long-input]',
        fieldName: 'descripcion_larga'
    });
    // Fecha.
    initInlineEditor({
        containerSelector: '[data-date-editor]',
        displaySelector: '[data-date-display]',
        toggleSelector: '[data-date-toggle]',
        formSelector: '[data-date-form]',
        inputSelector: '[data-date-input]',
        fieldName: 'fecha'
    });
    // Hora.
    initInlineEditor({
        containerSelector: '[data-hour-editor]',
        displaySelector: '[data-hour-display]',
        toggleSelector: '[data-hour-toggle]',
        formSelector: '[data-hour-form]',
        inputSelector: '[data-hour-input]',
        fieldName: 'hora'
    });
    // Aquí se pueden añadir más campos reutilizando la misma función.
    // initInlineEditor({ ... });
});
