    /**
     * Script: Popup para añadir artistas a eventos
     *
     * Funcionalidad:
     * - Abre un modal con lista de artistas disponibles
     * - Permite buscar artistas por nombre
     * - Añade artistas al evento sin recargar la página
     * - Actualiza la lista en tiempo real
     */

document.addEventListener('DOMContentLoaded', function () {
    // ─────────────────────────────────────────────────────────────
    // 1. SELECCIONAR ELEMENTOS DEL DOM
    // ─────────────────────────────────────────────────────────────

    // Botón "Añadir" que abre el modal
        const addBtn = document.querySelector('[data-add-artista-button]');
        if (!addBtn) return; // Salir si el botón no existe

    // Componentes del modal
    const modal = document.querySelector('#artista-modal');
    const backdrop = modal && modal.querySelector('[data-modal-backdrop]'); // Fondo oscuro
    const closeButtons = modal && modal.querySelectorAll('[data-modal-close]'); // Botones de cerrar
    const listEl = modal && modal.querySelector('#artista-list'); // Contenedor de artistas
    const searchInput = modal && modal.querySelector('#artista-search'); // Input de búsqueda

    // Datos del modal (extraídos de atributos HTML)
    const attachUrl = modal.getAttribute('data-attach-url'); // URL para POST de añadir artista
    const existing = JSON.parse(modal.getAttribute('data-existing-artistas') || '[]'); // IDs de artistas ya asociados


    // ─────────────────────────────────────────────────────────────
    // 2. ABRIR MODAL
    // ─────────────────────────────────────────────────────────────

    /**
     * Abre el modal y carga la lista de artistas
     * - Hace visible el modal (hidden = false)
     * - Enfoca automáticamente el input de búsqueda
     * - Carga los artistas disponibles desde la API
     */
    function openModal() {
        modal.hidden = false;
        searchInput.focus();
        fetchArtistas();
    }

    // ─────────────────────────────────────────────────────────────
    // 3. CERRAR MODAL
    // ─────────────────────────────────────────────────────────────

    /**
     * Cierra el modal y limpia su contenido
     * - Oculta el modal (hidden = true)
     * - Limpia la lista de artistas
     * - Borra el texto de búsqueda
     */
    function closeModal() {
        modal.hidden = true;
        listEl.innerHTML = '';
        searchInput.value = '';
    }


    // ─────────────────────────────────────────────────────────────
    // 4. CARGAR ARTISTAS DESDE API
    // ─────────────────────────────────────────────────────────────

    /**
     * Obtiene la lista de artistas desde la API
     * @param {string} q - Término de búsqueda (opcional)
     *
     * Lógica:
     * - Si hay búsqueda (q): usa /api/artistas/buscar?q=...
     * - Si no hay búsqueda: usa /api/artistas (todos)
     * - Muestra "Cargando..." mientras espera la respuesta
     * - Si hay error o sin resultados: muestra mensaje
     * - Si hay resultados: llama a renderList() para mostrarlos
     */
    function fetchArtistas(q = '') {
        // Mostrar estado de carga
        listEl.innerHTML = '<p class="muted">Cargando artistas...</p>';

        // Construir URL según si hay búsqueda
        const url = q
        ? '/api/artistas/buscar?q=' + encodeURIComponent(q)
        : '/api/artistas';

        // Hacer request a la API
        fetch(url)
        .then(r => r.json())
        .then(data => {
            const artistas = Array.isArray(data.data) ? data.data : [];
            if (artistas.length === 0) {
            listEl.innerHTML = '<p class="muted">No hay artistas disponibles.</p>';
            } else {
            renderList(artistas);
            }
        })
        .catch(err => {
            listEl.innerHTML = '<p class="muted">Error cargando artistas.</p>';
            console.error(err);
        });
    }


    // ─────────────────────────────────────────────────────────────
    // 5. RENDERIZAR LISTA DE ARTISTAS
    // ─────────────────────────────────────────────────────────────

    /**
     * Crea el HTML de cada artista y lo añade al modal
     * @param {array} artistas - Array de datos de artistas
     *
     * Para cada artista:
     * - Extrae id, nombre, descripción e imagen
     * - Filtra los que ya están asociados (están en array 'existing')
     * - Crea una tarjeta HTML con foto, nombre, descripción y botón "Añadir"
     * - Añade listener al botón para asociar el artista
     */
    function renderList(artistas) {
        listEl.innerHTML = '';

        artistas.forEach(item => {
        // Extraer datos del artista (puede venir envuelto en propiedad 'data')
        const artista = item.data ? item.data : item;
        const id = artista.id;

        // Filtrar artistas ya asociados al evento (no mostrarlos)
        if (existing.includes(id)) return;

        // Crear elemento DOM para la tarjeta del artista
        const card = document.createElement('div');
        card.className = 'artist-row';
        card.innerHTML = `
            <div class="artist-info">
            ${artista.imagen_url ? `<img src="${artista.imagen_url}" alt="${escapeHtml(artista.nombre)}" class="artist-thumb">` : ''}
            <div>
                <div class="artist-name">${escapeHtml(artista.nombre)}</div>
                ${artista.descripcion ? `<div class="artist-desc muted">${escapeHtml(artista.descripcion)}</div>` : ''}
            </div>
            </div>
            <button class="btn btn-sm btn-primary add-artista-btn" data-artista-id="${id}">Añadir</button>
        `;

        // Añadir la tarjeta a la lista
        listEl.appendChild(card);
        });

        // Añadir listener a TODOS los botones "Añadir" recién creados
        listEl.querySelectorAll('.add-artista-btn').forEach(btn => {
        btn.addEventListener('click', onAddArtista);
        });
    }


    // ─────────────────────────────────────────────────────────────
    // 6. AÑADIR ARTISTA AL EVENTO (VÍA API)
    // ─────────────────────────────────────────────────────────────

    /**
     * Maneja el clic en un botón "Añadir" de artista
     * @param {event} e - Evento del clic
     *
     * Proceso:
     * 1. Obtiene el ID del artista del atributo data-artista-id
     * 2. Deshabilita el botón y muestra "Añadiendo..."
     * 3. Envía POST a /admin/eventos/{id}/artistas con {artista_id}
     * 4. Si éxito:
     *    - Añade el ID a array 'existing' (para no volver a mostrar)
     *    - Anima la desaparición de la fila en el modal
     *    - Añade el artista a la lista en la página principal
     * 5. Si error: restaura el botón y muestra alerta
     */
    function onAddArtista(e) {
        e.preventDefault();
        const id = e.currentTarget.getAttribute('data-artista-id');
        if (!id) return;

        // Desactivar botón durante la petición
        const btn = e.currentTarget;
        btn.disabled = true;
        btn.textContent = 'Añadiendo...';

        // Enviar petición POST al servidor
        fetch(attachUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ artista_id: id })
        })
        .then(res => res.json().then(body => ({ status: res.status, body })))
        .then(({ status, body }) => {
            // Si la respuesta fue exitosa (200-299)
            if (status >= 200 && status < 300) {
            // Marcar este artista como ya asociado
            existing.push(parseInt(id, 10));

            // Animar la desaparición de la fila en el modal
            const row = btn.closest('.artist-row');
            row.style.animation = 'slideOut 0.3s ease-out forwards';

            // Esperar a que termine la animación y remover el elemento
            setTimeout(() => row.remove(), 300);

            // Actualizar la lista de artistas en la página principal
            appendArtistCardToPage(body.artista_id || id, btn.closest('.artist-row'));
            } else {
            // Si hubo error, mostrar mensaje y restaurar botón
            alert(body.message || body.error || 'Error al añadir artista');
            btn.disabled = false;
            btn.textContent = 'Añadir';
            }
        })
        .catch(err => {
            // Si hay error de red, mostrar alerta y restaurar botón
            console.error(err);
            alert('Error de red al añadir artista');
            btn.disabled = false;
            btn.textContent = 'Añadir';
        });
    }


    // ─────────────────────────────────────────────────────────────
    // 7. AÑADIR ARTISTA A LA PÁGINA (SIN RECARGAR)
    // ─────────────────────────────────────────────────────────────

    /**
     * Crea una tarjeta de artista en la sección "Artistas" de la página
     * @param {number} artistaId - ID del artista
     * @param {element} row - Fila del artista en el modal (para obtener datos)
     *
     * Acciones:
     * - Busca la sección "Artistas" en la página
     * - Extrae datos del artista (nombre, foto, descripción)
     * - Crea una tarjeta con el mismo estilo que las existentes
     * - Incluye un botón para quitar el artista
     * - Remueve mensaje "No hay artistas" si existe
     */
    function appendArtistCardToPage(artistaId, row) {
        // Encontrar la sección de artistas en la página (2ª sección)
        const artistasSection = document.querySelector('.event-info-section:nth-of-type(2)');
        if (!artistasSection) return;

        // Remover mensaje "No hay artistas asignados" si existe
        const emptyMsg = artistasSection.querySelector('.muted');
        if (emptyMsg) emptyMsg.remove();

        // Extraer datos del artista desde la fila del modal
        const nameEl = row.querySelector('.artist-name');
        const imgEl = row.querySelector('img');
        const descEl = row.querySelector('.artist-desc');

        // Crear tarjeta del artista manteniendo el mismo estilo
        const container = document.createElement('div');
        container.className = 'artist-card';
        container.innerHTML = `
        <div class="artist-card-header">
            ${imgEl ? `<img src="${imgEl.src}" alt="${escapeHtml(nameEl.textContent)}" class="artist-image">` : ''}
            <div>
            <p class="artist-name">${escapeHtml(nameEl.textContent)}</p>
            ${descEl ? `<p class="artist-description">${escapeHtml(descEl.textContent)}</p>` : ''}
            </div>
        </div>
        <form action="{{ route('admin.eventos.artistas.destroy', ['eventoId' => $evento->id, 'artistaId' => 'REPLACE_ID'], false) }}" method="POST" style="display:inline;">
            @csrf
            @method('DELETE')
            <button type="submit" class="event-card-trash" aria-label="Quitar artista del evento" onclick="return confirm('¿Estás seguro?')">🗑️</button>
        </form>
        `;

        // Reemplazar el ID placeholder en la URL para quitar artista
        const form = container.querySelector('form');
        if (form) {
        form.action = form.action.replace('REPLACE_ID', artistaId);
        }

        // Añadir la tarjeta al final de la sección
        artistasSection.appendChild(container);
    }


    // ─────────────────────────────────────────────────────────────
    // 8. FUNCIÓN AUXILIAR: ESCAPAR HTML
    // ─────────────────────────────────────────────────────────────

    /**
     * Escapa caracteres especiales HTML para evitar inyecciones XSS
     * @param {string} text - Texto a escapar
     * @returns {string} Texto con caracteres escapados
     *
     * Reemplaza:
     * & → &amp;
     * < → &lt;
     * > → &gt;
     * " → &quot;
     * ' → &#39;
     */
    function escapeHtml(text) {
        if (!text) return '';
        return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    // ─────────────────────────────────────────────────────────────
    // 9. LISTENERS Y EVENTOS
    // ─────────────────────────────────────────────────────────────

    // Abrir modal al hacer clic en botón "Añadir"
    addBtn.addEventListener('click', openModal);

    // Cerrar modal al hacer clic en fondo oscuro
    backdrop?.addEventListener('click', closeModal);

    // Cerrar modal al hacer clic en botones "X" (cerrar)
    closeButtons?.forEach(b => b.addEventListener('click', closeModal));

    // Búsqueda en tiempo real (con debounce de 300ms)
    if (searchInput) {
        let timeout;
        searchInput.addEventListener('input', function () {
        clearTimeout(timeout); // Cancelar búsqueda anterior
        timeout = setTimeout(() => {
            // Esperar 300ms después de que el usuario deje de escribir
            fetchArtistas(searchInput.value.trim());
        }, 300);
        });
    }

    // Cerrar modal presionando tecla ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) {
        closeModal();
        }
    });
});

// ─────────────────────────────────────────────────────────────
// 10. ESTILOS DE ANIMACIÓN
// ─────────────────────────────────────────────────────────────

/**
 * Inyecta animación CSS para que las filas desaparezcan suavemente
 * cuando se añade un artista al evento
 */
const style = document.createElement('style');
style.textContent = `
@keyframes slideOut {
    to {
    opacity: 0;
    transform: slideOutLeft 0.3s ease-out;
    }
}
`;
document.head.appendChild(style);
