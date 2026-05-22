# Quickstart: Sistema de Trazabilidad de Carga

**Date**: 2026-03-21
**Stack**: Laravel 11 + PHP 8.2 + MySQL 8.0 + Bootstrap 5.3

---

## Prerequisites

- PHP 8.2+ con extensiones: `mbstring`, `xml`, `curl`, `mysql`, `gd`, `zip`
- Composer 2.x
- MySQL 8.0+
- Node.js 18+ y npm (para assets)
- Git

## Setup

### 1. Crear proyecto Laravel

```bash
composer create-project laravel/laravel cargo_express
cd cargo_express
```

### 2. Configurar .env

```env
APP_NAME="Cargo Express"
APP_URL=http://localhost:8000
APP_TIMEZONE=America/Bogota
APP_LOCALE=es

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cargo_express
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

BROADCAST_CONNECTION=reverb

# Email (SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.ejemplo.com
MAIL_PORT=587
MAIL_USERNAME=notificaciones@cargoexpress.com
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=notificaciones@cargoexpress.com
MAIL_FROM_NAME="Cargo Express"
```

### 3. Crear base de datos

```sql
CREATE DATABASE cargo_express CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Instalar dependencias

```bash
# Auth scaffolding con Blade + Bootstrap
composer require laravel/breeze --dev
php artisan breeze:install blade

# RBAC
composer require spatie/laravel-permission

# PDF
composer require barryvdh/laravel-dompdf

# Excel
composer require maatwebsite/excel

# QR/Barcode para stickers
composer require picqer/php-barcode-generator
composer require simplesoftwareio/simple-qrcode

# Image processing
composer require intervention/image

# Broadcasting (WebSockets)
php artisan install:broadcasting
```

### 5. Publicar configuraciones

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

### 6. Instalar assets (Bootstrap 5)

```bash
npm install
npm install bootstrap @popperjs/core bootstrap-icons
npm run build
```

### 7. Configurar Storage

```bash
php artisan storage:link
```

### 8. Migraciones y seeders

```bash
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=UbicacionPatioSeeder
php artisan db:seed --class=AdminUserSeeder
```

### 9. Cola de trabajos

```bash
# Crear tabla de jobs
php artisan queue:table
php artisan migrate

# Ejecutar worker (desarrollo)
php artisan queue:work --queue=notifications,reports,default
```

### 10. WebSocket server (desarrollo)

```bash
php artisan reverb:start
```

### 11. Servidor de desarrollo

```bash
php artisan serve
```

Acceder a `http://localhost:8000`

---

## Estructura de directorios clave

```
app/
├── Enums/           → Estados, tipos (ContenedorEstado, NovedadTipo, etc.)
├── Http/Controllers/ → Un controller por módulo
├── Http/Requests/   → Form Requests para validación
├── Models/          → Eloquent models (14 entidades)
├── Services/        → Lógica de negocio (1 service por módulo)
├── Notifications/   → WhatsApp + email notifications
├── Jobs/            → Async: notificaciones, reportes
├── Events/          → Broadcasting: InventoryUpdated, etc.
├── Exports/         → Maatwebsite Excel exports
└── Policies/        → Authorization policies (scope por cliente)

resources/views/
├── layouts/app.blade.php  → Layout Bootstrap 5
├── solicitudes/           → Vistas módulo 1
├── gate-in/              → Vistas módulo 2
├── vaciado/              → Vistas módulo 3
├── almacenamiento/       → Vistas módulo 4
├── gate-out/             → Vistas módulo 5
├── entregas/             → Vistas módulo 6
├── trazabilidad/         → Vistas módulo 7
├── reportes/             → Vistas módulo 7
├── pdf/                  → Templates PDF (tirillas, tarjas, stickers)
└── components/           → Componentes Blade reutilizables
```

---

## Comandos útiles

```bash
# Crear model con migration, factory, seeder, controller, requests
php artisan make:model Contenedor -mfsc --requests

# Ejecutar tests
php artisan test
php artisan test --filter=GateInTest

# Limpiar cache
php artisan config:clear && php artisan cache:clear

# Ver rutas registradas
php artisan route:list
```

---

## Verificación del setup

1. Acceder a `http://localhost:8000` → debe mostrar login
2. Login con admin seeded → debe mostrar dashboard
3. Crear una solicitud de prueba → debe aparecer en la lista
4. Verificar que las colas procesan: `php artisan queue:work --once`