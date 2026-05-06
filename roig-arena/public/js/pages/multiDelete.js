// Habilita o deshabilita los controles de acciones (editar/borrar) de una fila concreta.
// - checkbox: checkbox de la fila actual.
// - isEnabled: true para habilitar acciones, false para deshabilitarlas.

function setRowActionsState(checkbox, isEnabled) {
	// Localiza la fila completa donde vive el checkbox.
	const row = checkbox.closest('tr');
	if (!row) return;

	// Busca los elementos de acción individuales de esa fila.
	const editButton = row.querySelector('[data-row-edit-button]');
	const deleteForm = row.querySelector('[data-row-delete-form]');
	const deleteButton = row.querySelector('[data-row-delete-button]');

	// Alterna estado habilitado del botón de editar.
	if (editButton) {
		editButton.disabled = !isEnabled;
		editButton.setAttribute('aria-disabled', String(!isEnabled));
	}

	// Alterna estado habilitado del botón de borrado.
	// No se oculta nada, solo se desactiva la interacción.
	if (deleteButton) {
		deleteButton.disabled = !isEnabled;
		deleteButton.setAttribute('aria-disabled', String(!isEnabled));
	}

	// Marca visual opcional en la fila para poder estilizar en CSS si quieres.
	if (deleteForm) {
		deleteForm.classList.toggle('is-disabled', !isEnabled);
	}
	row.classList.toggle('row-actions-disabled', !isEnabled);
}

// Sincroniza la zona de acciones globales (cabecera "Acciones").
// Solo se muestra cuando TODOS los checkboxes están marcados.
function syncBulkActions(checkboxes) {
	const bulkActionsControls = document.getElementById('bulk-actions-controls');
	if (!bulkActionsControls || checkboxes.length === 0) return;

	// Comprueba selección total de filas.
	const allChecked = checkboxes.some((checkbox) => checkbox.checked);
	// Muestra acciones masivas si la selección es completa.
	bulkActionsControls.style.display = allChecked ? 'inline-flex' : 'none';
}

// Inicializa toda la lógica de checkboxes y sincronización de UI.
function initMultiDeleteUI() {
	// Obtiene todas las checkboxes de sectores de la tabla.
	const checkboxes = Array.from(document.querySelectorAll('[data-sector-price-checkbox]'));
	if (checkboxes.length === 0) return;

	// Estado visual por fila: marcada => oculta acciones; no marcada => muestra acciones.
	const applyState = (checkbox) => {
		setRowActionsState(checkbox, !checkbox.checked);
	};

	// 1) Aplica estado inicial al cargar.
	// 2) Escucha cambios para actualizar fila + acciones globales.
	checkboxes.forEach((checkbox) => {
		applyState(checkbox);

		checkbox.addEventListener('change', () => {
			// Al cambiar una checkbox, se recalcula su fila y el estado global.
			applyState(checkbox);
			syncBulkActions(checkboxes);
		});
	});

	// Sincroniza estado global al inicio (por si la vista carga con checks marcados).
	syncBulkActions(checkboxes);
}

// Espera a que el DOM esté listo antes de buscar elementos y enlazar eventos.
document.addEventListener('DOMContentLoaded', initMultiDeleteUI);
