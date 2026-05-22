# Cargo Express Constitution

## Core Principles

### I. Código Limpio sobre Código Rápido
La legibilidad siempre tiene prioridad sobre la velocidad de escritura. Prohibido el uso de hacks o workarounds sin documentar el motivo y crear un issue de seguimiento. Los nombres de variables, funciones y clases deben ser autoexplicativos; los comentarios explican el por qué, no el qué. No se aceptan funciones de más de 40 líneas ni archivos de más de 300 líneas sin justificación documentada.

### II. Convención sobre Configuración
Se adoptan las convenciones del framework/lenguaje principal del proyecto sin desviaciones injustificadas. Toda configuración de herramientas (linters, formateadores, tests) vive en archivos dedicados, versionados en el repositorio. La estructura de directorios sigue el patrón acordado y no se modifica sin consenso del equipo.

### III. Responsabilidad Única (SRP)
Cada módulo, clase, servicio o función tiene una sola razón para cambiar. Los controladores solo orquestan; la lógica de negocio vive en servicios; la persistencia en repositorios. Si una función hace más de una cosa, se divide.

### IV. No Duplicidad (DRY)
Toda lógica repetida en dos o más lugares debe extraerse a una función, servicio o utilidad compartida. Las constantes de negocio (estados, tipos, límites) se definen una sola vez en un módulo centralizado. Las validaciones reutilizables viven en utilidades compartidas, nunca duplicadas entre módulos.

### V. Simplicidad (KISS)
Ante dos soluciones que resuelven el mismo problema, se elige la más simple. No se introduce abstracción hasta que el patrón de duplicación es claro (regla de tres). No se diseña para casos hipotéticos futuros; se diseña para el requerimiento actual.

### VI. Código Testeable Siempre
Todo código nuevo debe poder probarse de forma aislada. Las dependencias externas (BD, APIs, servicios) se inyectan, nunca se instancian directamente dentro de la lógica de negocio. Cobertura mínima requerida: 80% en lógica de negocio (servicios), 60% en controladores. Toda funcionalidad crítica (cálculo de tarifas, estados de envío, autenticación) tiene pruebas de integración.

### VII. Escalabilidad como Principio Base
La arquitectura debe soportar crecimiento en volumen de datos y usuarios sin refactorizaciones mayores. Las operaciones costosas (reportes, notificaciones masivas) se procesan de forma asíncrona mediante colas. Las consultas a base de datos deben incluir índices adecuados y paginación; prohibido traer colecciones completas sin límite. El sistema evita estado compartido entre instancias para permitir escalado horizontal.

## Estándares de Código

### Nomenclatura
| Elemento | Convención | Ejemplo |
|---|---|---|
| Variables / funciones | camelCase | `trackShipment`, `deliveryDate` |
| Clases / tipos / interfaces | PascalCase | `ShipmentService`, `DeliveryStatus` |
| Constantes globales | UPPER_SNAKE_CASE | `MAX_WEIGHT_KG`, `DEFAULT_TIMEOUT_MS` |
| Archivos de módulo | kebab-case | `shipment-service.ts`, `auth-middleware.ts` |
| Tablas / colecciones BD | snake_case | `shipment_tracking`, `user_addresses` |

### Estructura de Commits
Se sigue la especificación Conventional Commits: `<tipo>(<alcance>): <descripción corta>`. Tipos válidos: feat, fix, refactor, test, docs, chore, perf.

### Pull Requests
Todo PR debe tener descripción del cambio, tipo de cambio y checklist de pruebas. Un PR no puede modificar más de 400 líneas sin justificación. Se requiere al menos 1 aprobación antes de hacer merge. Prohibido hacer merge a main/master directamente; se usa develop como rama base.

### Manejo de Errores
Los errores de negocio se representan con clases de error tipadas. Toda excepción no manejada debe ser capturada por un middleware global. Los logs de error incluyen: timestamp, correlationId, contexto de la operación y stack trace.

## Estándares de Seguridad
- Autenticación: JWT con expiración máx. 24h + refresh token de rotación.
- Autorización: RBAC verificado en cada endpoint.
- Datos sensibles: nunca en logs, nunca en URLs, cifrados en reposo.
- Secretos: solo mediante variables de entorno o gestor de secretos.
- Validación de entrada: toda entrada del usuario es validada y sanitizada.
- Dependencias: auditadas regularmente; versiones fijadas en lockfile.
- HTTPS obligatorio en todos los ambientes.

## Estructura de Directorios
```
cargo_express/
├── src/
│   ├── modules/          # Módulos de negocio (shipments, users, tracking, billing...)
│   │   └── <module>/
│   │       ├── controllers/
│   │       ├── services/
│   │       ├── repositories/
│   │       ├── dtos/
│   │       └── tests/
│   ├── shared/           # Utilidades, tipos y helpers compartidos
│   ├── config/           # Configuración del sistema
│   └── main.ts
├── tests/
│   ├── unit/
│   └── integration/
├── docs/
└── .env.example
```

## Flujo de Trabajo
```
feature branch → develop → staging → main (producción)
```
| Rama | Propósito |
|---|---|
| `main` | Producción estable — solo via PR desde `staging` |
| `staging` | Pre-producción / QA |
| `develop` | Integración continua |
| `feature/*` | Desarrollo activo |
| `fix/*` | Corrección de bugs |
| `hotfix/*` | Fix urgente en producción |

## Governance
Esta constitución rige sobre todas las decisiones de diseño, arquitectura e implementación del sistema Cargo Express. Toda decisión técnica que afecte la arquitectura debe documentarse en un ADR (Architecture Decision Record) en `docs/adr/`. Cualquier modificación a esta constitución requiere consenso del equipo y se registra en el historial del repositorio. La constitución se revisa al inicio de cada trimestre, cuando se incorpora tecnología nueva al stack, o cuando un principio genera fricción repetida.

**Version**: 1.0 | **Ratified**: 2026-03-21 | **Last Amended**: 2026-03-21