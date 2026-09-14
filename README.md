# 🎓 Academicus - Academia de Prisiones

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=flat-square&logo=tailwind-css)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-Green?style=flat-square&logo=alpinejs)](https://alpinejs.dev)

**Academicus** es una plataforma de aprendizaje avanzada diseñada específicamente para la preparación de oposiciones de la Academia de Prisiones. El sistema combina la gestión académica tradicional con herramientas de psicología del aprendizaje, gamificación y automatización administrativa.

## 🚀 Características Principales

### 📚 Gestión Académica Inteligente
- **Temario con Drip Content:** Liberación progresiva y automática de bloques y temas basada en la fecha de suscripción del alumno.
- **Motor de Tests Multimodal:** Soporte para preguntas de opción múltiple, completar huecos, casos prácticos ("supuestos") y preguntas abiertas.
- **Test "A la Carta":** Generador personalizado de exámenes basado en bloques, temas y nivel de dificultad.
- **VideoClases Seguras:** Streaming de contenido protegido con validación de suscripción en tiempo real.

### ⏱️ Herramientas de Estudio Estratégico
- **Ciclo de Estudio Dirigido:** Proceso estructurado en cuatro fases: *Lectura $\rightarrow$ Subrayado $\rightarrow$ Repaso $\rightarrow$ Repaso Rápido*.
- **Temporizador Pomodoro Integrado:** Gestión de tiempo focalizado (25/5) con registro automático de horas reales por tema.
- **Cronograma Personalizado:** Planificación visual y flexible de los objetivos de estudio.

### 🎮 Gamificación y Motivación
- **Sistema de Rangos:** Progresión basada en puntos de experiencia (XP) desde **Cobre** hasta **Diamante**.
- **Avatares Dinámicos:** Sistema de equipamiento y uniformes desbloqueables según el rango alcanzado.
- **Batallas entre Opositores:** Desafíos directos entre alumnos para fomentar la competitividad saludable.

### 🛠️ Administración y Operaciones
- **Sincronización Financiera:** Integración con **Stripe** para pagos y **Holded** para la gestión automatizada de facturas.
- **Tutorías y Soporte:** Sistema de Q&A vinculado a temas específicos e integración con Zoom para clases en vivo.
- **Reporting Avanzado:** Generación de informes de progreso detallados en PDF.

## 🛠️ Stack Tecnológico

| Capa | Tecnología |
| :--- | :--- |
| **Backend** | Laravel 11 / PHP 8.3 |
| **Frontend** | Blade / Tailwind CSS / Alpine.js |
| **Base de Datos** | MySQL |
| **Pagos** | Stripe API |
| **Facturación** | Holded API |
| **Infraestructura** | Ubuntu / Plesk / Vite |

## 📦 Instalación y Configuración

### Requisitos Previos
- PHP $\ge$ 8.3
- Composer
- Node.js & NPM
- MySQL

### Pasos de Instalación
1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/selvaggiesteban/academicus.git
   cd academicus
   ```

2. **Instalar dependencias**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Configurar entorno**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Migraciones y Seeders**
   ```bash
   php artisan migrate --seed
   ```

5. **Lanzar servidor**
   ```bash
   php artisan serve
   ```

## 📂 Estructura del Proyecto (Simplificada)
- `app/Actions`: Lógica de negocio encapsulada (ej. `AcceptBattleAction`).
- `app/Http/Controllers`: Controladores de rutas y flujo.
- `app/Models`: Definición de datos y relaciones (Course, Block, Topic, User).
- `app/Services`: Lógica compleja y servicios externos (Dashboard, Metrics).
- `resources/views`: Plantillas Blade organizadas por módulos.
- `routes/`: Definición de rutas web y API.

## 🛡️ Estándares de Desarrollo
Este proyecto sigue una metodología de **Spec-Driven Development (SDD)** y **Security-First**, asegurando que cada funcionalidad sea especificada, validada y auditada antes de su despliegue en producción.

---
© 2026 - Academia de Prisiones. Todos los derechos reservados.
