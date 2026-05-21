# Hub de Documentación: CI4 Admin Starter

Bienvenido a la documentación oficial de **CI4 Admin Starter** — una plantilla de panel administrativo lista para producción para CodeIgniter 4.

🌐 **Idiomas:** [English](../INDEX.md) | [Español](./INDEX.md)

> **Nota de sincronización.** La versión en inglés (`docs/`) es la fuente autoritativa.
> La traducción en español se actualiza periódicamente, no en tiempo real. Si esta
> versión contradice a la inglesa, asume que la inglesa es la correcta y reporta la
> discrepancia.

## 🚀 Comenzar

**¿Nuevo en el proyecto?** Comienza aquí:

### 📖 [Guía Inicio Rápido](./QUICK-START.md)
Instrucciones paso a paso para usuarios primerizos. Cubre instalación, configuración y verificación. **Guía de configuración de 5 minutos.**

### ❓ [Preguntas Frecuentes (FAQ)](./FAQ.md)
Preguntas comunes sobre arquitectura, desarrollo, pruebas, despliegue e integración de API.

### 🆘 [Guía de Resolución de Problemas](./TROUBLESHOOTING.md)
Soluciones para problemas comunes: problemas de configuración, errores del servidor, problemas de estilo, problemas de autenticación y más.

---

## 📚 Documentación Core

### 🏗️ [Arquitectura y Conceptos Clave](./ARCHITECTURE.md)
Inmersión profunda en el diseño del sistema:
- Arquitectura renderizada en servidor vs SPA
- ApiClient: Cliente HTTP con actualización automática de tokens
- Almacenamiento JWT basado en sesión
- Patrones de seguridad y mejores prácticas
- Flujo de datos desde envío de formulario hasta respuesta de la API

### 🔌 [Servicios y Capa de Validación](./SERVICES.md)
Aprende cómo comunicarte con la API backend:
- Patrón de servicio y BaseApiService
- Validación FormRequest (reglas, payload, manejo de errores)
- Ayudantes de manejo de errores (safeApiCall, failApi, extractData)
- Normalización de respuestas

### 🎨 [Guía Frontend y UI](./FRONTEND.md)
Construye interfaces usando nuestro sistema de diseño:
- Clases de utilidad Tailwind CSS
- Alpine.js para interactividad del lado del cliente
- Integración de iconos Lucide
- Componentes y patrones reutilizables
- Gestión de tablas y paginación
- Diálogos modales y confirmaciones

### 🧪 [Pruebas y Aseguramiento de Calidad](./TESTING.md)
Escribe código confiable con pruebas integrales:
- Estrategia de pruebas unitarias
- Pruebas de características con ApiClient simulado
- Patrones de mocking
- Informes de cobertura
- Herramientas de calidad de código (PHPStan, PHP-CS-Fixer)

### 🚀 [Despliegue y Producción](./DEPLOYMENT.md)
Todo lo necesario para despliegue en producción:
- Configuración de entorno
- Configuración de servidor (Nginx/Apache)
- Encabezados de seguridad y HTTPS
- Optimización del rendimiento
- Configuración de base de datos y sesión
- Checklist de producción

---

## 🛠️ Guías How-To

### 📖 [Guía How-To](./HOW-TO.md)
Instrucciones paso a paso para tareas comunes:
- Agregar un nuevo módulo/característica
- Crear endpoints de API
- Administrar navegación de barra lateral
- Personalizar marca y colores
- Agregar internacionalización (i18n)
- Y más...

---

## 🔗 Documentación de Referencia

| Guía | Propósito |
|------|-----------|
| **[Librería de Componentes](./COMPONENTS.md)** | Catálogo de componentes UI con ejemplos |
| **[Compatibilidad API](./API-COMPATIBILITY.md)** | Contrato de integración backend/frontend |
| **[Capa de Validación](./VALIDATION-LAYER.md)** | Patrones detallados de FormRequest |
| **[Flujos Críticos](./CRITICAL-FLOWS.md)** | Flujos de carga de archivos y refresco JWT |
| **[Configuración Google OAuth](./GOOGLE-LOGIN-SETUP.md)** | Configuración de inicio de sesión con Google |

---

## 📊 Estructura de Documentación

```
docs/
├── INDEX.md                    ← Estás aquí
├── QUICK-START.md             ← Comienza aquí primero
├── FAQ.md                      ← Preguntas comunes
├── TROUBLESHOOTING.md          ← Resolución de problemas
│
├── ARCHITECTURE.md            ← Diseño del sistema
├── SERVICES.md                ← Comunicación API
├── FRONTEND.md                ← Patrones UI/UX
├── TESTING.md                 ← Estrategias de prueba
├── DEPLOYMENT.md              ← Checklist de producción
│
├── HOW-TO.md                  ← Guías de desarrollo de características
├── COMPONENTS.md              ← Catálogo de componentes UI
├── VALIDATION-LAYER.md        ← Detalles de FormRequest
├── API-COMPATIBILITY.md       ← Contrato API
├── CRITICAL-FLOWS.md          ← Flujos críticos
└── GOOGLE-LOGIN-SETUP.md      ← Configuración OAuth
```

---

## 🎯 Por Rol

### 👨‍💻 **Desarrolladores**
1. Comienza con [Inicio Rápido](./QUICK-START.md) para configurar
2. Lee [Arquitectura](./ARCHITECTURE.md) para entender el sistema
3. Sigue [Servicios y Validación](./SERVICES.md) al agregar características
4. Consulta [Guía Frontend](./FRONTEND.md) para implementación UI
5. Usa [Guía How-To](./HOW-TO.md) para instrucciones paso a paso

### 🧪 **QA & Pruebas**
1. Revisa [Guía de Pruebas](./TESTING.md) para patrones de prueba
2. Verifica [Resolución de Problemas](./TROUBLESHOOTING.md) para problemas comunes
3. Entiende [Arquitectura](./ARCHITECTURE.md) para diseño del sistema
4. Usa [Despliegue](./DEPLOYMENT.md) para configuración de entorno

### 🚀 **DevOps & Despliegue**
1. Lee [Guía de Despliegue](./DEPLOYMENT.md) primero
2. Verifica [Arquitectura](./ARCHITECTURE.md) para diseño del sistema
3. Consulta [Inicio Rápido](./QUICK-START.md) para configuración
4. Usa [FAQ](./FAQ.md) para preguntas sobre despliegue

### 📚 **Gerentes de Proyecto**
1. Revisa [Arquitectura](./ARCHITECTURE.md) para descripción general del sistema
2. Verifica [Guía How-To](./HOW-TO.md) para proceso de desarrollo de características
3. Usa [FAQ](./FAQ.md) para preguntas comunes
4. Ver [Despliegue](./DEPLOYMENT.md) para checklist de lanzamiento

---

## 🔍 Busca Información por Tema

| Tema | Documento |
|------|-----------|
| **Configuración e Instalación** | [Inicio Rápido](./QUICK-START.md) |
| **Comunicación API** | [Servicios y Validación](./SERVICES.md) |
| **Construir Formularios** | [Capa de Validación](./VALIDATION-LAYER.md) |
| **Construir UI** | [Guía Frontend](./FRONTEND.md) |
| **Agregar Características** | [Guía How-To](./HOW-TO.md) |
| **Escribir Pruebas** | [Guía de Pruebas](./TESTING.md) |
| **Desplegar** | [Guía de Despliegue](./DEPLOYMENT.md) |
| **Autenticación** | [Arquitectura](./ARCHITECTURE.md) → Patrones de Seguridad |
| **Descargas de Archivos** | [Flujos Críticos](./CRITICAL-FLOWS.md) |
| **Refresco JWT** | [Flujos Críticos](./CRITICAL-FLOWS.md) |
| **Google OAuth** | [Configuración Google OAuth](./GOOGLE-LOGIN-SETUP.md) |
| **Manejo de Errores** | [Servicios y Validación](./SERVICES.md) → Manejo de Errores |
| **i18n/Localización** | [Guía How-To](./HOW-TO.md) → Localización |
| **Resolución de Problemas** | [Guía de Resolución de Problemas](./TROUBLESHOOTING.md) |
| **FAQ** | [FAQ](./FAQ.md) |

---

## 💡 Navegación Rápida

**Documentos Más Vistos:**
1. [Inicio Rápido](./QUICK-START.md) — Configurarse
2. [Arquitectura](./ARCHITECTURE.md) — Entender el sistema
3. [Servicios y Validación](./SERVICES.md) — Construir características
4. [Guía Frontend](./FRONTEND.md) — Crear interfaces
5. [Guía How-To](./HOW-TO.md) — Recetas paso a paso
6. [Resolución de Problemas](./TROUBLESHOOTING.md) — Resolver problemas
7. [Despliegue](./DEPLOYMENT.md) — Ir a producción

**Otros Recursos:**
- **[Repositorio de Código](https://github.com/dcardenasl/ci4-admin-starter)** — Código fuente e issues
- **[API Backend](https://github.com/dcardenasl/ci4-api-starter)** — Plantilla backend complementaria
- **[Documentación de CodeIgniter 4](https://codeigniter.com/user_guide/)** — Referencia del framework
- **[Documentación de Tailwind CSS](https://tailwindcss.com/docs)** — Framework CSS
- **[Documentación de Alpine.js](https://alpinejs.dev/)** — Librería JavaScript

---

## 📝 Versiones de Documentación

Toda la documentación está actualizada con la última versión estable.

- **Última Actualización:** 2026-04-15
- **Versión:** Alineada con rama main
- **Estado:** ✅ Listo para Producción

---

## 🤝 Contribuyendo

¿Tienes sugerencias o encontraste errores en la documentación?

1. Abre un issue en [GitHub](https://github.com/dcardenasl/ci4-admin-starter/issues)
2. Envía un pull request con mejoras
3. Ver [CONTRIBUTING.md](../CONTRIBUTING.md) para directrices

---

**¿Listo para comenzar?** → [Guía Inicio Rápido](./QUICK-START.md)
