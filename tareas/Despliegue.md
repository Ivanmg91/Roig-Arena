# Internship web applications deployment report

## Empresa / Business

**Empresa:** Roig Arena  
**Proyecto:** API web para gestión de eventos, reservas y compra de entradas (Laravel 12 + Docker/Sail)

---

## Informe en español

### 1) Contexto de despliegue

Durante las prácticas, participé en tareas de despliegue y preparación de entornos para una aplicación web del proyecto **Roig Arena**. La aplicación está desarrollada con **Laravel 12**, usa **MySQL 8** y se ejecuta de forma contenerizada con **Docker** mediante **Laravel Sail**.

Mi trabajo estuvo orientado a que el entorno quedase siempre operativo, reproducible y rápido de levantar para desarrollo, pruebas funcionales y validación de cambios.

### 2) Herramientas utilizadas

- **Docker + Docker Compose**: ejecución aislada y consistente de servicios.
- **Laravel Sail**: gestión del entorno Laravel dentro de contenedores.
- **Bash scripts** (`setup-arena.sh`, `arena.sh`, variantes cloud): automatización de setup, arranque, limpieza, migraciones, seeders y tests.
- **Composer**: gestión de dependencias PHP y comandos de test.
- **NPM/Vite**: compilación de assets frontend cuando era necesario.
- **Artisan (Laravel CLI)**: migraciones, seeders, generación de claves y mantenimiento técnico.
- **Git/GitHub**: control de versiones y coordinación de cambios.

### 3) Procedimiento habitual de despliegue/preparación

1. **Setup inicial del entorno**  
   - Verificación de Docker y requisitos.
   - Creación de `.env` a partir de plantilla.
   - Ajuste de variables de base de datos para Sail.
   - Instalación de dependencias con Composer/NPM.

2. **Levantado de servicios**
   - Arranque de contenedores en segundo plano.
   - Comprobación de estado de base de datos.
   - Generación de `APP_KEY` si faltaba.

3. **Inicialización de datos**
   - Ejecución de migraciones y seeders.
   - En entornos de reinicio completo: `migrate:fresh --seed`.

4. **Verificación posterior al despliegue**
   - Ejecución de tests automáticos.
   - Comprobación de rutas clave de la API.
   - Revisión de logs para detectar errores de configuración.

5. **Mantenimiento y recuperación**
   - Reinicio limpio de contenedores y volúmenes en caso de inconsistencias.
   - Ajustes de configuración según contexto local o cloud.

### 4) Frecuencia de las tareas

- **Diaria**: arranque/parada de entorno, revisión de estado y logs básicos.
- **Varias veces por semana**: reinicio completo con migraciones y seeders para validar escenarios limpios.
- **En cada cambio relevante**: pruebas de funcionamiento tras modificar lógica crítica (reservas, compras, autenticación).
- **Puntualmente**: ajuste de scripts y parámetros de despliegue para mejorar fiabilidad.

### 5) Buenas prácticas aplicadas

- Automatización de pasos repetitivos con scripts.
- Uso de entornos aislados para evitar “funciona en mi máquina”.
- Comprobación post-despliegue con tests y validaciones funcionales.
- Cuidado de seguridad básica en evidencias: ocultación de secretos, contraseñas y tokens.
- Documentación clara para que cualquier miembro del equipo pueda reproducir el proceso.

### 6) Evaluación personal

Esta parte de las prácticas me ayudó a entender que desplegar no es solo “subir código”, sino garantizar estabilidad, repetibilidad y capacidad de recuperación. Aprendí a detectar rápidamente fallos comunes (variables mal configuradas, dependencias faltantes, servicios caídos) y a resolverlos de forma sistemática con procedimientos definidos.

También mejoré en autonomía técnica, porque pasé de ejecutar comandos sueltos a trabajar con flujos completos de despliegue, verificación y mantenimiento. Como mejora futura, me gustaría profundizar más en observabilidad avanzada (métricas y alertado) y en pipelines CI/CD más automatizados.

### 7) Evidencias (capturas)

> **Nota:** Las capturas deben incluirse con el conocimiento y consentimiento del tutor/a de empresa, ocultando siempre datos sensibles.

Capturas recomendadas para adjuntar:

1. Terminal ejecutando `setup-arena.sh` correctamente.
2. Arranque con `arena.sh` mostrando servicios activos.
3. Ejecución de migraciones/seeders sin errores.
4. Resultado de tests tras despliegue.
5. Logs o verificación de endpoint funcionando (sin exponer tokens ni credenciales).

---

## English report

### 1) Deployment context

During my internship, I worked on deployment and environment-preparation tasks for the **Roig Arena** web application project. The application is built with **Laravel 12**, uses **MySQL 8**, and runs in containers using **Docker** through **Laravel Sail**.

My main objective was to keep environments stable, reproducible, and fast to bootstrap for development, functional testing, and change validation.

### 2) Tools used

- **Docker + Docker Compose** for isolated and consistent service execution.
- **Laravel Sail** for Laravel container orchestration.
- **Bash scripts** (`setup-arena.sh`, `arena.sh`, cloud variants) for setup/start/reset/migrations/seeding/testing automation.
- **Composer** for PHP dependency and test command management.
- **NPM/Vite** for frontend asset compilation when required.
- **Artisan (Laravel CLI)** for migrations, seeding, key generation, and technical maintenance tasks.
- **Git/GitHub** for version control and team collaboration.

### 3) Standard deployment/environment procedure

1. **Initial environment setup**
   - Validate Docker and required tools.
   - Create `.env` from template.
   - Configure DB variables for Sail.
   - Install Composer/NPM dependencies.

2. **Service startup**
   - Start containers in detached mode.
   - Check database readiness.
   - Generate `APP_KEY` when missing.

3. **Data initialization**
   - Run migrations and seeders.
   - Use `migrate:fresh --seed` for full reset scenarios.

4. **Post-deployment verification**
   - Execute automated tests.
   - Validate key API routes.
   - Review logs for configuration/runtime errors.

5. **Maintenance and recovery**
   - Clean restart of containers/volumes when inconsistencies appear.
   - Environment-specific config adjustments (local/cloud).

### 4) Task frequency

- **Daily**: environment start/stop operations and basic health/log checks.
- **Several times per week**: full reset workflows with migrations and seeding.
- **For every relevant change**: post-deployment validation, especially on critical modules (reservations, purchases, authentication).
- **Occasionally**: script and deployment parameter improvements for reliability.

### 5) Applied best practices

- Script-based automation of repetitive steps.
- Isolated environments to avoid machine-specific behavior.
- Post-deployment validation through tests and functional checks.
- Security awareness in shared evidence (masking secrets, credentials, and tokens).
- Clear documentation to ensure process reproducibility.

### 6) Personal evaluation

This internship work helped me understand that deployment is not only about releasing code; it is about ensuring reliability, repeatability, and recovery capacity. I improved my ability to identify and fix common deployment failures (misconfigured variables, missing dependencies, unavailable services) using systematic procedures.

I also improved my technical autonomy by moving from isolated commands to full deployment-validation-maintenance workflows. As a next step, I would like to deepen my skills in observability (metrics and alerting) and more advanced CI/CD automation.

### 7) Evidence (screenshots)

> **Note:** Screenshots should be included only with business instructor awareness and approval, always masking sensitive data.

Suggested evidence to attach:

1. Terminal showing successful `setup-arena.sh` execution.
2. `arena.sh` startup output with running services.
3. Successful migration/seeding execution.
4. Test results after deployment.
5. Endpoint or log verification without exposing tokens/credentials.