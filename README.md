# ChangosGames

Aplicación web de gestión para una tienda de videojuegos: catálogo, reservas, ventas, inventario, movimientos de caja, cuentas y reportes.

## Stack

- **Frontend:** HTML5 semántico, CSS3 puro y JavaScript vainilla (módulos ES + `fetch` nativo). Sin frameworks ni dependencias.
- **Backend:** PHP 8.1+ nativo, sesiones y PDO con prepared statements. Sin frameworks ni ORMs.
- **Base de datos:** MySQL / MariaDB (`utf8mb4`, InnoDB).
- **Control de versiones:** Git + GitHub.

## Requisitos

- PHP 8.1 o superior (probado con XAMPP).
- MySQL / MariaDB.
- Navegador moderno con soporte para módulos ES.

## Instalación local (XAMPP)

1. Copia esta carpeta a `C:\xampp\htdocs\changosgames` (o configura un *Alias* apuntando a `C:\changosgames`).
2. Copia `.env.example` como `.env` y ajusta `DB_USER` / `DB_PASS` si hace falta.
3. Crea la base de datos y las tablas:
   - Importa `database/schema.sql` desde phpMyAdmin, o
   - `mysql -u root -p < database/schema.sql`
4. Importa los datos de prueba: `database/seeds.sql`.
5. Crea los usuarios demo con contraseña cifrada:
   ```powershell
   php database/seed.php
   ```
   Contraseña de prueba para todos: `Changos123!`
6. Abre `http://localhost/changosgames/public/index.html`.

> La URL de la API se calcula desde `public/js/api.js` como `../../api/`, así que `public/` y `api/` deben ser hermanos bajo la misma raíz web.

## Cuentas de demostración

| Usuario | Rol | Permisos |
| --- | --- | --- |
| `cliente` | Cliente | Consultar productos, reservar, cancelar sus reservas, ver sus ventas. |
| `empleado` | Empleado | Ingresar productos, vender, cancelar ventas, confirmar entregas. |
| `gerente` | Encargado | Lo anterior + caja, registros y reportes. |
| `administrador` | Administrador | Todo lo anterior + crear cuentas y asignar roles. |

Contraseña: `Changos123!` (cámbiala antes de usar la aplicación fuera de local).

## Estructura

```text
public/     HTML, CSS y JavaScript del cliente (única parte pensada para el navegador)
api/        Endpoints PHP que devuelven JSON
src/        Configuración, helpers, modelos y controladores PHP
database/   schema.sql, seeds.sql y seed.php
docs/       Notas sobre los SRS y diagramas (los PDF originales siguen en la raíz)
```

## Flujo implementado

1. El cliente consulta el catálogo y reserva un producto (entrega en sucursal o a domicilio). La reserva descuenta stock y vence a los 7 días.
2. El sistema notifica al cliente (simulado en `registros`) y al repartidor si es a domicilio.
3. El empleado elige método de pago (efectivo o tarjeta) y convierte la reserva en venta: pasa a `pagada` y luego a `registrada`, se asienta el ingreso en caja y se crea el registro de auditoría.
4. El encargado confirma la entrega a domicilio, consulta caja, registros y genera reportes.
5. Una venta o reserva cancelada devuelve el producto al inventario y asienta un egreso en caja.

## Decisiones derivadas de los diagramas

- Los roles `Administrador`, `Empleado` y `Cliente` del diagrama de clases se unifican en una tabla `usuarios` con `roles`; el `id_usuario` equivale a `id_admin` / `id_empleado` / `id_cliente`.
- La clase `Registros` se implementa como tabla de auditoría `registros` más la tabla `movimientos_caja` (venta, movimientos de caja, ingresos).
- La enumeración `estados` (dañado / pristino) se aplica a `productos.estado`.
- A los estados del diagrama de estados se añaden `entregada` y `vencida` para cubrir la entrega y el vencimiento a 7 días del diagrama de actividades.
- Los métodos de pago se limitan a efectivo y tarjeta; las notificaciones por correo y al repartidor se simulan en la tabla `registros` (sin servicios externos).
- El inventario se descuenta al reservar (no al vender) para evitar ventas duplicadas; la cancelación lo restaura.

Las decisiones y cambios se documentan en `bitacora.md`.

## Publicar en GitHub

```powershell
git status
git add .
git commit -m "feat: descripción del cambio"
git push origin main
```

Nunca subir `.env`, contraseñas ni archivos temporales. Revisar `claude.md` antes de cada cambio.
