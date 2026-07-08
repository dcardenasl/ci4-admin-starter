# Guía de Inicio Rápido

Ponte en funcionamiento con **CI4 Admin Starter** en 5 minutos.

## Requisitos Previos

Antes de comenzar, asegúrate de tener:

- **PHP 8.1+** instalado y accesible via comando `php`
- **Composer 2.x** instalado
- **Node.js 16+** y npm instalados
- **Git** (para clonar el repositorio)
- Una instancia ejecutándose de **[ci4-api-starter](https://github.com/dcardenasl/ci4-api-starter)** en `http://localhost:8180` (o actualizar la URL en `.env`)

Verifica tus versiones:
```bash
php --version
composer --version
node --version
npm --version
```

## Paso 1: Clonar el Repositorio

```bash
git clone https://github.com/dcardenasl/ci4-admin-starter.git
cd ci4-admin-starter
```

## Paso 2: Ejecutar el Script de Instalación (Recomendado)

```bash
bash install.sh
```

El script hará:
1. Crear `.env` desde la plantilla
2. Pedirte configuración (URL de API, entorno, etc)
3. Opcionalmente instalar dependencias de Composer
4. Opcionalmente instalar dependencias de npm
5. Ejecutar seeds/migraciones de base de datos (si aplica)

**Responde los prompts de la siguiente manera:**

| Prompt | Ejemplo |
|--------|---------|
| Environment (dev/prod) | `development` |
| Application Base URL | `http://localhost:8182/` |
| Backend API Base URL | `http://localhost:8180/` |
| API Prefix | `/api/v1` |
| ¿Instalar dependencias de Composer? | `y` |
| ¿Instalar dependencias de npm? | `y` |

## Paso 3: Instalación Manual (Alternativa al Script)

Si prefieres configuración manual:

```bash
# Instalar dependencias PHP
composer install

# Instalar dependencias npm
npm install

# Copiar plantilla de entorno
cp env .env
```

Luego edita `.env`:

```dotenv
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8182/'
apiClient.baseUrl = 'http://localhost:8180'
apiClient.apiPrefix = '/api/v1'
```

## Paso 4: Iniciar Servidores de Desarrollo

Abre **dos ventanas de terminal** en tu directorio de proyecto.

**Terminal 1 — Servidor de Desarrollo PHP:**
```bash
php spark serve --port 8182
```

Deberías ver:
```
CodeIgniter v4.x.x Command Line Tool - Server Edition
...
Server running on http://localhost:8182
```

**Terminal 2 — Observador de CSS de Tailwind:**
```bash
npm run dev:css
```

Deberías ver:
```
> npm run dev:css
Rebuilding CSS...
```

Ambos deben estar ejecutándose durante el desarrollo.

## Paso 5: Abrir en Navegador

Navega a: **`http://localhost:8182`**

Deberías ver la página de inicio de sesión. Si la API del backend está ejecutándose en `http://localhost:8180`, puedes:

1. **Crear una nueva cuenta** (si el registro está habilitado)
2. **Iniciar sesión** con credenciales de prueba del backend

### Verificar la Configuración

- [ ] La página se carga sin errores
- [ ] Los estilos se aplican correctamente (colores, layout)
- [ ] Puedes navegar a la página de inicio de sesión/registro
- [ ] La API del backend es accesible (verifica la consola del navegador para errores)

## Checklist de Configuración

Antes de usar la aplicación, verifica:

```
✅ .env existe y está configurado correctamente
✅ apiClient.baseUrl apunta a tu API del backend
✅ Ambos servidores de desarrollo PHP y npm están ejecutándose
✅ Sin errores en la consola del navegador (F12)
✅ La API del backend está ejecutándose y es accesible
✅ Las migraciones de base de datos en el backend están completas (si usas seeding de API)
```

## Problemas Comunes de Configuración

### Error "Connection refused"

**Problema:** `Connection to http://localhost:8180 refused`

**Solución:**
1. Asegúrate de que la API del backend está ejecutándose: `php spark serve --port 8180` en el proyecto del backend
2. Verifica que `apiClient.baseUrl` en `.env` coincide con el puerto del backend
3. Reinicia el servidor de desarrollo PHP

### Estilos No Aplicados (La Página Se Ve Rota)

**Problema:** Sin colores, layout roto

**Solución:**
1. Detén el servidor npm dev (Ctrl+C)
2. Ejecuta `npm run build:css` para reconstruir CSS
3. Reinicia con `npm run dev:css`
4. Recarga el navegador (Cmd+Shift+R o Ctrl+Shift+R)

### Puerto Ya en Uso

**Problema:** `Address already in use` al iniciar `php spark serve`

**Solución:**
```bash
# Usa un puerto diferente
php spark serve --port 8083

# O mata el proceso que usa el puerto 8182
lsof -i :8182  # Encuentra el ID del proceso
kill -9 <PID>  # Mátalo
```

### Errores 401 Unauthorized

**Problema:** Todas las solicitudes de API devuelven 401

**Solución:**
1. Verifica que la API del backend está ejecutándose y saludable
2. Si usas `apiClient.appKey`, verifica que sea correcta (clave incorrecta causa que todas las solicitudes devuelvan 401)
3. Intenta eliminar `apiClient.appKey` temporalmente para aislar el problema
4. Verifica los logs del backend para errores de validación

## Ejecutar Pruebas

```bash
# Ejecutar todas las pruebas
composer test

# Ejecutar solo pruebas unitarias
composer test:unit

# Ejecutar solo pruebas de características
composer test:feature

# Ejecutar con informe de cobertura
composer test:coverage
```

Todas las pruebas deben pasar antes de proceder al desarrollo.

## Próximos Pasos

1. **Lee el [Hub de Documentación](./INDEX.md)** para guías detalladas
2. **Explora la [Arquitectura](./ARCHITECTURE.md)** para entender el flujo de solicitudes
3. **Verifica la [Guía Frontend](./FRONTEND.md)** para patrones de componentes UI
4. **Revisa [Servicios y Validación](./SERVICES.md)** para aprender cómo agregar nuevas características
5. **Ver [Guías How-To](./HOW-TO.md)** para desarrollo de características paso a paso

## Comandos de Desarrollo

```bash
# Pruebas
composer test              # Ejecutar todas las pruebas
composer test:unit         # Solo pruebas unitarias
composer test:feature      # Solo pruebas de características
composer test:coverage     # Con informe de cobertura

# Calidad de Código
composer analyse           # Análisis estático PHPStan
composer format            # Corregir estilo de código automáticamente
composer format:check      # Verificar estilo (simulación)
composer quality          # Verificación de calidad completa

# JavaScript
npm run lint:js           # Linter para archivos JavaScript
npm run lint:all          # Linter para todo JS
npm run dev:css           # Observar CSS de Tailwind
npm run build:css         # Construcción CSS de producción
```

## Obtener Ayuda

- **¿Problemas con la configuración?** Verifica [RESOLUCIÓN DE PROBLEMAS](./TROUBLESHOOTING.md)
- **¿Preguntas generales?** Ver [PREGUNTAS FRECUENTES](./FAQ.md)
- **¿Necesitas descripción general de arquitectura?** Lee [ARQUITECTURA](./ARCHITECTURE.md)
- **¿Quieres agregar una característica?** Sigue [GUÍA HOW-TO](./HOW-TO.md)

## Referencia de Variables de Entorno

| Variable | Propósito | Requerida | Por Defecto |
|----------|-----------|-----------|-------------|
| `CI_ENVIRONMENT` | Entorno de la app | Sí | `development` |
| `app.baseURL` | URL pública del frontend | Sí | `http://localhost:8182/` |
| `apiClient.baseUrl` | URL de la API del backend | Sí | `http://localhost:8180` |
| `apiClient.apiPrefix` | Prefijo de API | No | `/api/v1` |
| `GOOGLE_CLIENT_ID` | ID de cliente de Google OAuth | No | — |
| `FILE_MAX_SIZE` | Tamaño máximo de carga (bytes) | No | `10485760` (10 MB) |
| `apiClient.appKey` | Clave de app de API para límite de velocidad | No | — |

Ver `.env.example` o la guía [DESPLIEGUE](./DEPLOYMENT.md) para referencia completa.

---

**✅ ¿Listo para desarrollar?** ¡Estás listo! Comienza a construir características y consulta el hub de documentación según sea necesario.

**¿Preguntas?** Verifica [PREGUNTAS FRECUENTES](./FAQ.md) o [RESOLUCIÓN DE PROBLEMAS](./TROUBLESHOOTING.md).
