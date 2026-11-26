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
| 2026-11-26 | deploy | Publicación en GitHub (rama `aplicacion-web`) | ✅ |
| 2026-11-26 | chore | Pruebas funcionales en navegador | ✅ |
| 2026-11-26 | chore | Puesta en marcha y pruebas de API en XAMPP | ✅ |
| 2026-11-26 | fix | Correcciones detectadas durante las pruebas en vivo | ✅ |
| 2026-11-26 | fix | Corrección de filtros en la API y consulta de ventas | ✅ |
| 2026-11-26 | chore | Validación de sintaxis PHP (`php -l`) | ✅ |
| 2026-11-26 | feat | Implementación de la aplicación web completa | ✅ |
| 2026-11-26 | decision | Mapeo de diagramas a base de datos y reglas de negocio | ✅ |
| 2026-11-26 | chore | Extracción de requisitos de los 7 PDF | ✅ |
| 2026-11-26 | docs | Creación de reglas del proyecto y bitácora inicial | ✅ |

---

## 2026-11-26 — [deploy] Publicación en GitHub (rama `aplicacion-web`)

- **Qué:** se inicializó Git en `C:\changosgames` (`git init -b main`), se configuró `origin` apuntando a `https://github.com/juanirobananas12-debug/ChangosGames.git` y se creó el commit `f406f64` con 48 archivos y 3261 inserciones. El historial se subió a una rama **nueva** `aplicacion-web` para no sobrescribir la rama `main` existente del repositorio (que contiene la documentación de las semanas anteriores).
- **Por qué:** guardar y desplegar el proyecto en GitHub sin destruir el trabajo previo.
- **Archivos:** todo el repositorio; `.env` excluido por `.gitignore`.
- **Estado:** ✅ completado. PR sugerido: `https://github.com/juanirobananas12-debug/ChangosGames/pull/new/aplicacion-web`.
- **Siguiente paso:** revisar el PR y fusionarlo en `main` cuando se confirme que es deseable reemplazar/mezclar el historial anterior.

---

## 2026-11-26 — [chore] Pruebas funcionales en navegador

- **Qué:** se abrió `http://127.0.0.1:8000/public/index.html` en el navegador integrado. Sin errores en consola. Verificado:
  - Catálogo con 7 productos y búsqueda (filtrar por "Kart" devuelve 1 producto).
  - Login de cliente, menú con `Mis reservas`/`Ventas` y sin opciones de encargado.
  - Reserva desde la UI (selector de entrega con etiquetas correctas) y cancelación con devolución de stock.
  - Login de empleado: menú `Catálogo/Reservas/Ventas` y formulario de ingreso de productos.
  - Login de administrador: menú completo; vistas de Caja (ingresos $1,898 / egresos $100 / saldo $1,798), reportes, registros y cuentas renderizadas.
- **Por qué:** comprobar el frontend y los permisos por rol en un navegador real.
- **Archivos:** solo lectura; sin modificaciones.
- **Estado:** ✅ completado.
- **Siguiente paso:** revisar visualmente el diseño y ajustar estilos si se requiere.

---

## 2026-11-26 — [chore] Puesta en marcha y pruebas de API en XAMPP

- **Qué:** se importaron `schema.sql` y `seeds.sql` en MariaDB 10.4.32 y se ejecutó `php database/seed.php` (4 usuarios). Se inició el servidor integrado de PHP en `127.0.0.1:8000` y se probaron por HTTP: login de los 4 roles, búsqueda de productos, alta de producto, reserva, venta con tarjeta, confirmación de entrega, venta con efectivo y su cancelación (stock restaurado a 4), caja, reportes, registros, creación de cuenta y cambio de rol. Permisos verificados: un empleado recibe HTTP 403 al consultar caja; una petición POST sin token CSRF recibe HTTP 403. Después se reinició la BD a estado limpio (4 usuarios, 6 productos, 0 ventas/reservas).
- **Por qué:** validar el backend completo antes de publicarlo.
- **Archivos:** base de datos `changosgames` (sin cambios en el código).
- **Estado:** ✅ completado.
- **Siguiente paso:** usar las credenciales demo del README para pruebas manuales.

---

## 2026-11-26 — [fix] Correcciones detectadas durante las pruebas en vivo

- **Qué:** se corrigieron 4 defectos que solo aparecieron al ejecutar la aplicación:
  1. `Producto::listar()` y `Producto::ajustarStock()` repetían marcadores named (`:q`, `:delta`) con `ATTR_EMULATE_PREPARES=false`; PDO lanzaba `HY093`. Se usaron marcadores distintos (`:q1/:q2/:q3`, `:delta1/:delta2`).
  2. `ui.js::campo()` creaba las opciones de `<select>` con el atributo `text` en vez de `texto`, por lo que no se veían las etiquetas. Corregido.
  3. Los `<select>` generados empezaban sin selección válida; ahora toman la primera opción por defecto.
  4. Tras `Salir`, el token CSRF en memoria quedaba obsoleto y el siguiente login devolvía 403. `api.logout()` ahora vuelve a pedir `auth.php?accion=yo` para renovarlo.
  5. La cuenta demo se creaba como `admin` pero el README indicaba `administrador`; se unificó el nombre en `seed.php` y en la BD.
- **Por qué:** los errores no se detectaron con `php -l` ni con la revisión estática; aparecieron al probar búsqueda, reservas y cambio de usuario.
- **Archivos:** `src/models/Producto.php`, `public/js/ui.js`, `public/js/api.js`, `database/seed.php`.
- **Estado:** ✅ completado y verificado de nuevo en navegador y por HTTP.
- **Siguiente paso:** ninguno pendiente para estas correcciones.

---

## 2026-11-26 — [fix] Corrección de filtros en la API y consulta de ventas

- **Qué:** se corrigieron dos problemas detectados en la revisión final:
  1. `public/js/api.js` concatenaba los filtros dentro del parámetro `accion` (`listar&q=...`), por lo que PHP recibía un solo valor codificado y la búsqueda/filtro no funcionaba. Ahora `peticion()` acepta `parametros` y los envía como parámetros GET reales (`q`, `estado`, `tipo`).
  2. `src/models/Venta.php::listar()` usaba `GROUP BY` con columnas de tablas unidas, lo que puede fallar con `ONLY_FULL_GROUP_BY` en MySQL 8. Se sustituyó por una subconsulta `GROUP_CONCAT` correlacionada.
  Además se creó `docs/README.md` para documentar los 7 PDF originales y se ajustó `README.md`.
- **Por qué:** asegurar que los filtros del catálogo, reservas y caja funcionen y que la consulta de ventas sea compatible con la configuración estricta de MySQL.
- **Archivos:** `public/js/api.js`, `src/models/Venta.php`, `docs/README.md`, `README.md`, `bitacora.md`.
- **Estado:** ✅ completado y validado: `php -l` pasó nuevamente y los filtros funcionaron en XAMPP y en el navegador.
- **Siguiente paso:** ninguno pendiente para esta corrección.

---

## 2026-11-26 — [chore] Validación de sintaxis PHP (`php -l`)

- **Qué:** se ejecutó `php -l` con `C:\xampp\php\php.exe` sobre los 25 archivos `.php` de `C:\changosgames` (api, src y database), previa autorización del usuario. Resultado: **25/25 sin errores de sintaxis**.
- **Por qué:** verificar el código escrito antes de cualquier prueba en servidor o subida a GitHub.
- **Archivos:** solo lectura; sin modificaciones.
- **Estado:** ✅ completado.
- **Siguiente paso:** completado: la base de datos se importó y el JS se validó ejecutándolo en el navegador (no había Node instalado).

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
- **Estado:** ✅ completado (sintaxis PHP validada, base de datos importada, API y navegador probados y código publicado en GitHub).
- **Siguiente paso:** ninguno pendiente; revisar las decisiones pendientes del SRS con el encargado.

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
