document.addEventListener('DOMContentLoaded', () => {
	// Esta pantalla controla el alta de usuarios y envía el formulario por fetch.
	const form = document.getElementById('registerForm');

	if (!form) {
		return;
	}

	// Elementos de estado: botón, aviso general y errores por campo.
	const submitButton = document.getElementById('registerSubmit');
	const alertBox = document.getElementById('registerAlert');
	const fieldErrors = new Map(
		Array.from(form.querySelectorAll('[data-error-for]')).map(element => [element.dataset.errorFor, element])
	);

	// Limpia mensajes antes de reintentar el registro.
	const clearMessages = () => {
		alertBox.hidden = true;
		alertBox.textContent = '';

		fieldErrors.forEach(element => {
			element.textContent = '';
		});
	};

	// Evita que el usuario dispare varios registros seguidos.
	const setBusy = isBusy => {
		submitButton.disabled = isBusy;
		submitButton.textContent = isBusy ? 'Registrando...' : 'Registrarse';
	};

	// Calcula la ruta de destino tras completar el alta.
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

	// Muestra bajo cada campo el primer error devuelto por el servidor.
	const showFieldErrors = errors => {
		Object.entries(errors).forEach(([fieldName, messages]) => {
			const target = fieldErrors.get(fieldName);
			if (target) {
				target.textContent = Array.isArray(messages) ? messages[0] : String(messages);
			}
		});
	};

	// Interceptamos el envío para gestionarlo con JSON y fetch.
	form.addEventListener('submit', async event => {
		event.preventDefault();
		clearMessages();
		setBusy(true);

		// Reunimos solo los campos que necesita el backend para crear el usuario.
		const formData = new FormData(form);
		const payload = {
            nombre: String(formData.get('nombre') || '').trim(),
            apellido: String(formData.get('apellido') || '').trim(),
			email: String(formData.get('email') || '').trim(),
			password: String(formData.get('password') || ''),
            password_confirmation: String(formData.get('password_confirmation') || '')
		};

		try {
			// Enviamos la petición con la cabecera CSRF que espera Laravel.
			const response = await fetch(form.action, {
				method: 'POST',
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
				},
				credentials: 'same-origin',
				body: JSON.stringify(payload)
			});

			const data = await response.json().catch(() => ({}));

			if (!response.ok) {
				// Si falla la validación, mostramos errores por campo; si no, un mensaje general.
				if (response.status === 422 && data.errors) {
					showFieldErrors(data.errors);
				} else {
					alertBox.hidden = false;
					alertBox.textContent = data.message || 'No se pudo registrar.';
				}

				setBusy(false);
				return;
			}

			// Guardamos el token devuelto por el backend para futuras peticiones autenticadas.
			localStorage.setItem('sanctum_token', data.token);

			if (data.user) {
				localStorage.setItem('sanctum_user', JSON.stringify(data.user));
			}

			window.location.href = getRedirectTo();
		} catch (error) {
			// Error de red o caída temporal del servidor.
			alertBox.hidden = false;
			alertBox.textContent = 'Error de red al iniciar sesión.';
			console.error('Error iniciando sesión:', error);
			setBusy(false);
		}
	});
});
