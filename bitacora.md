# Bitácora de ChangosGames

Registro cronológico de cambios, decisiones y despliegues del proyecto.
**Toda persona o asistente debe agregar una entrada aquí al terminar una tarea**, en el mismo commit donde hizo el cambio.

---

## Convención de entradas

- Orden: **la más reciente arriba**.
- Formato del título: `YYYY-MM-DD — [tipo] título corto`.
- Tipos válidos: `docs`, `feat`, `fix`, `style`, `refactor`, `chore`, `deploy`, `decision`.
- Campos obligatorios: **Qué**, **Por qué**, **Archivos**, **Estado**, **Siguiente paso**.
- Usar español y fechas en formato `AAAA-MM-DD`.

---

## Resumen

| Fecha | Tipo | Entrada | Estado |
| --- | --- | --- | --- |
| 2026-11-26 | fix | Corrección de filtros en la API y consulta de ventas | ✅ |
| 2026-11-26 | chore | Validación de sintaxis PHP (`php -l`) | ✅ |
| 2026-11-26 | feat | Implementación de la aplicación web completa | ✅ |
| 2026-11-26 | decision | Mapeo de diagramas a base de datos y reglas de negocio | ✅ |
| 2026-11-26 | chore | Extracción de requisitos de los 7 PDF | ✅ |
| 2026-11-26 | docs | Creación de reglas del proyecto y bitácora inicial | ✅ |

---

## 2026-11-26 — [fix] Corrección de filtros en la API y consulta de ventas

- **Qué:** se corrigieron dos problemas detectados en la revisión final:
  1. `public/js/api.js` concatenaba los filtros dentro del parámetro `accion` (`listar&q=...`), por lo que PHP recibía un solo valor codificado y la búsqueda/filtro no funcionaba. Ahora `peticion()` acepta `parametros` y los envía como parámetros GET reales (`q`, `estado`, `tipo`).
  2. `src/models/Venta.php::listar()` usaba `GROUP BY` con columnas de tablas unidas, lo que puede fallar con `ONLY_FULL_GROUP_BY` en MySQL 8. Se sustituyó por una subconsulta `GROUP_CONCAT` correlacionada.
  Además se creó `docs/README.md` para documentar los 7 PDF originales y se ajustó `README.md`.
- **Por qué:** asegurar que los filtros del catálogo, reservas y caja funcionen y que la consulta de ventas sea compatible con la configuración estricta de MySQL.
- **Archivos:** `public/js/api.js`, `src/models/Venta.php`, `docs/README.md`, `README.md`, `bitacora.md`.
- **Estado:** ✅ completado (cambios escritos; la sintaxis PHP de `Venta.php` no se volvió a validar con `php -l` porque no hay nueva autorización para ejecutar comandos).
- **Siguiente paso:** al autorizar, repetir `php -l` y probar los filtros en XAMPP.

---

## 2026-11-26 — [chore] Validación de sintaxis PHP (`php -l`)

- **Qué:** se ejecutó `php -l` con `C:\xampp\php\php.exe` sobre los 25 archivos `.php` de `C:\changosgames` (api, src y database), previa autorización del usuario. Resultado: **25/25 sin errores de sintaxis**.
- **Por qué:** verificar el código escrito antes de cualquier prueba en servidor o subida a GitHub.
- **Archivos:** solo lectura; sin modificaciones.
- **Estado:** ✅ completado.
- **Siguiente paso:** (con autorización) importar `schema.sql` / `seeds.sql` y ejecutar `php database/seed.php` en XAMPP; validar también el JS con Node si se desea.

---

## 2026-11-26 — [feat] Implementación de la aplicación web completa

- **Qué:** se desarrolló ChangosGames respetando el stack de `claude.md` (HTML5, CSS3, JavaScript vainilla, PHP nativo, MySQL):
  - **Base de datos:** `database/schema.sql` (11 tablas: roles, usuarios, productos, inventario, reservas, ventas, venta_detalles, movimientos_caja, registros), `database/seeds.sql` y `database/seed.php` (usuarios demo con `password_hash()`).
  - **Backend:** `src/config/`, `src/helpers/` (Response, Auth, Validation, Csrf, bootstrap), `src/models/` (Usuario, Producto, Reserva, Venta, Caja), `src/controllers/` (Auth, Cuenta, Producto, Reserva, Venta, Caja) y 6 endpoints en `api/`.
  - **Frontend:** `public/index.html`, 4 hojas CSS y 3 módulos JS (`api.js`, `ui.js`, `main.js`) con catálogo/búsqueda, reservas, ventas, caja/reportes, cuentas y acceso.
  - **Repositorio:** `README.md`, `.gitignore` y `.env.example`.
  - Cobertura: RF-01 a RF-12, casos de uso de los tres actores, altas de productos/cuentas, flujo de entrega, cancelación y vencimiento a 7 días, y los estados `abierta → reservada → pagada → registrada / cancelada`.
- **Por qué:** construir la aplicación completa a partir de los 7 diagramas de `C:\changosgames` sin frameworks ni dependencias, dejándola lista para GitHub.
- **Archivos:** `database/*`, `src/**`, `api/*`, `public/**`, `README.md`, `.gitignore`, `.env.example`, `claude.md`, `bitacora.md`.
- **Estado:** ✅ completado (sintaxis PHP validada; sin instalar componentes ni modificar la base de datos).
- **Siguiente paso:** con autorización, importar el esquema en MySQL y probar el flujo en XAMPP; después inicializar Git y subir a GitHub.

---

## 2026-11-26 — [decision] Mapeo de diagramas a base de datos y reglas de negocio

- **Qué:** decisiones tomadas donde los diagramas y el SRS dejaban puntos abiertos (SP-01 a SP-7):
  1. `Administrador`, `Empleado` y `Cliente` se unifican en `usuarios` + `roles` (`id_usuario` = `id_admin` / `id_empleado` / `id_cliente`).
  2. La clase `Registros` se implementa como tabla de auditoría `registros` junto con `movimientos_caja`.
  3. La enumeración `estados` (pristino / dañado) queda en `productos.estado`.
  4. Se añaden los estados `entregada` y `vencida` para cubrir la entrega y el vencimiento de 7 días del diagrama de actividades.
  5. Métodos de pago: solo efectivo y tarjeta. Notificaciones por correo y al repartidor simuladas en `registros` (sin servicios externos).
  6. El inventario se descuenta al **reservar** (no al vender); cancelar reserva o venta lo devuelve y asienta un egreso en caja.
  7. Permisos: cliente (catálogo/reservas), empleado (productos/ventas/entregas), encargado (caja/registros/reportes), administrador (cuentas/roles).
  8. Se añade protección CSRF nativa y `password_hash()` aunque el SRS marca "seguridad" como no incluido, para cumplir `claude.md`.
- **Por qué:** los diagramas no especifican estos detalles y el SRS los deja como decisiones pendientes; era necesario fijarlos para poder implementar.
- **Archivos:** `database/schema.sql`, `src/models/*.php`, `src/controllers/*.php`, `README.md`.
- **Estado:** ✅ completado, pendiente de confirmación con el encargado.
- **Siguiente paso:** revisar estas decisiones con el usuario antes de desplegar en producción.

---

## 2026-11-26 — [chore] Extracción de requisitos de los 7 PDF

- **Qué:** se extrajo el texto de los 7 PDF de `C:\changosgames` (SRS, casos de uso, clases, 3 actividades y estados) usando `pdftotext.exe` incluido en Git for Windows. El modelo no podía leer PDF directamente; se pidió autorización antes de ejecutar cualquier comando y **no se instaló ningún componente**.
- **Por qué:** obtener los requisitos exactos antes de escribir código.
- **Archivos:** solo lectura; sin modificaciones en los PDF.
- **Estado:** ✅ completado.
- **Siguiente paso:** conservar los PDF originales y, si se quiere versionar la documentación, mover copias a `docs/`.

---

## 2026-11-26 — [docs] Creación de reglas del proyecto y bitácora inicial

- **Qué:** se crearon `claude.md` y `bitacora.md` en `C:\changosgames`. Se definieron el stack permitido (HTML5, CSS3, JavaScript vainilla, PHP nativo y MySQL), la estructura de carpetas, las convenciones de código, el flujo Git/GitHub y el procedimiento de despliegue.
- **Por qué:** unificar criterios antes de escribir código y dejar constancia de cada avance del proyecto.
- **Archivos:** `claude.md`, `bitacora.md`.
- **Estado:** ✅ completado.
- **Siguiente paso:** crear la estructura de carpetas junto con `.gitignore`, `README.md` y `.env.example`; luego inicializar Git y conectar el remoto de GitHub.

---

## Plantilla para nuevas entradas (copiar y pegar)

```markdown
## AAAA-MM-DD — [tipo] título corto

- **Qué:**
- **Por qué:**
- **Archivos:**
- **Estado:** ⏳ / ✅ / ❌
- **Siguiente paso:**
```

---

## Registro de despliegues

| Fecha | Versión / commit | Entorno | Resultado | Registrado por |
| --- | --- | --- | --- | --- |
| — | — | — | — | — |

> Completar esta tabla cada vez que se despliegue a GitHub o a un servidor.
