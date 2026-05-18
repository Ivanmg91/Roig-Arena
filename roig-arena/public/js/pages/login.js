document.addEventListener('DOMContentLoaded', () => {
	// Solo inicializamos la lógica cuando la página ya está lista y existe el formulario.
	const form = document.getElementById('loginForm');

	if (!form) {
		return;
	}

	// Elementos de la interfaz que se actualizan durante el proceso de login.
	const submitButton = document.getElementById('loginSubmit');
	const alertBox = document.getElementById('loginAlert');
	// Mapa para pintar errores de validación justo debajo de cada campo.
	const fieldErrors = new Map(
		Array.from(form.querySelectorAll('[data-error-for]')).map(element => [element.dataset.errorFor, element])
	);

	// Limpia cualquier mensaje previo antes de un nuevo intento.
	const clearMessages = () => {
		alertBox.hidden = true;
		alertBox.textContent = '';

		fieldErrors.forEach(element => {
			element.textContent = '';
		});
	};

	// Bloquea o desbloquea el botón para evitar envíos duplicados.
	const setBusy = isBusy => {
		submitButton.disabled = isBusy;
		submitButton.textContent = isBusy ? 'Entrando...' : 'Entrar';
	};

	// Calcula a qué URL debe volver el usuario después de autenticarse.
	const getRedirectTo = () => {
		const fallback = '/';
		const redirectTo = form.dataset.redirectTo || fallback;

		try {
			const url = new URL(redirectTo, window.location.origin);
			return `${url.pathname}${url.search}${url.hash}` || fallback;
		} catch {
			return fallback;
		}
	};

	// Coloca el primer error recibido por cada campo visible.
	const showFieldErrors = errors => {
		Object.entries(errors).forEach(([fieldName, messages]) => {
			const target = fieldErrors.get(fieldName);
			if (target) {
				target.textContent = Array.isArray(messages) ? messages[0] : String(messages);
			}
		});
	};

	form.addEventListener('submit', async event => {
		// Evitamos el envío tradicional para gestionar todo con fetch.
		event.preventDefault();
		clearMessages();
		setBusy(true);

		// Recogemos solo los datos que necesita el backend para autenticar.
		const formData = new FormData(form);
		const payload = {
			email: String(formData.get('email') || '').trim(),
			password: String(formData.get('password') || '')
		};

		try {
			const response = await fetch(form.action, {
				method: 'POST',
				headers: {
					// Laravel espera JSON y el token CSRF para aceptar la petición.
					'Accept': 'application/json',
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
				},
				credentials: 'same-origin',
				body: JSON.stringify(payload)
			});

			const data = await response.json().catch(() => ({}));

			if (!response.ok) {
				// Si la validación falla, pintamos errores por campo; si no, mostramos un aviso general.
				if (response.status === 422 && data.errors) {
					showFieldErrors(data.errors);
				} else {
					alertBox.hidden = false;
					alertBox.textContent = data.message || 'No se pudo iniciar sesión.';
				}

				setBusy(false);
				return;
			}

			// Guardamos la sesión devuelta por Sanctum si el backend la proporciona.
			if (data.token) {
				localStorage.setItem('sanctum_token', data.token);
			} else {
				localStorage.removeItem('sanctum_token');
			}

			if (data.user) {
				localStorage.setItem('sanctum_user', JSON.stringify(data.user));
			} else {
				localStorage.removeItem('sanctum_user');
			}

			// Una vez autenticado, redirigimos a la ruta indicada por el formulario.
			window.location.href = getRedirectTo();
		} catch (error) {
			// Fallo de red o de conexión con el servidor.
			alertBox.hidden = false;
			alertBox.textContent = 'Error de red al iniciar sesión.';
			console.error('Error iniciando sesión:', error);
			setBusy(false);
		}
	});
});
