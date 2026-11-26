# CLAUDE.md — ChangosGames

Instrucciones permanentes del proyecto **ChangosGames**.
Aplica a cualquier persona o asistente (Claude, OpenCode, etc.) que trabaje dentro de esta carpeta.
**Leer este archivo antes de escribir una sola línea de código.**

---

## 1. Stack permitido (obligatorio)

| Capa | Tecnología | Reglas |
| --- | --- | --- |
| Estructura | **HTML5 semántico** | Sin frameworks ni motores de plantillas de terceros |
| Estilos | **CSS3 puro** | Sin Bootstrap, Tailwind, Sass ni librerías de UI |
| Cliente | **JavaScript vainilla (ES6+)** | Sin React, Vue, Angular, jQuery, npm, bundlers ni CDN de frameworks |
| Backend | **PHP nativo (8.1+)** | Sin Laravel, Symfony, CodeIgniter ni ORMs |
| Datos | **MySQL / MariaDB** | Acceso con PDO y *prepared statements* |
| Versiones | **Git + GitHub** | Commits frecuentes, `main` es la rama de producción |

### Prohibido

- Instalar dependencias de cualquier tipo (`package.json`, `composer.json`, `node_modules`, `vendor/`).
- Usar `fetch` de librerías externas: se usa el `fetch` nativo del navegador.
- Meter lógica de negocio en el HTML o en los archivos `.js` del cliente.
- Subir credenciales, `.env`, claves API, dump de producción o archivos temporales.
- Salirse de la carpeta `C:\changosgames` sin permiso explícito.

Si surge la necesidad de una librería o tecnología nueva: **no agregarla**. Documentar la propuesta en `bitacora.md` y esperar aprobación.

---

## 2. Estructura de carpetas acordada

```text
C:\changosgames\
├── claude.md              # Este archivo: reglas del proyecto
├── bitacora.md            # Registro cronológico de cambios y despliegues
├── README.md              # Descripción, requisitos y puesta en marcha
├── .gitignore             # Excluir .env, vendor, node_modules, temporales
├── .env.example           # Plantilla de variables (sin valores reales)
├── public/                # HTML, CSS y JS del cliente (accesible desde el navegador)
│   ├── index.html         # Punto de entrada del sitio
│   ├── css/
│   │   ├── variables.css  # Variables CSS, colores, tipografía, spacing
│   │   ├── reset.css      # Normalización mínima
│   │   ├── layout.css     # Grid / flex / estructura general
│   │   └── components.css # Componentes reutilizables
│   ├── js/
│   │   ├── api.js         # Llamadas fetch a /api/*.php
│   │   ├── ui.js          # Manipulación del DOM
│   │   └── main.js        # Arranque de la aplicación
│   └── assets/            # Imágenes, iconos, fuentes propias
├── src/                   # Backend PHP (fuera de la raíz pública)
│   ├── config/
│   │   └── database.php   # Conexión PDO única, lee variables de entorno
│   ├── controllers/
│   ├── models/
│   └── helpers/           # Validación, sesión, respuestas JSON
├── api/                   # Endpoints PHP que devuelven JSON al frontend
├── database/
│   ├── schema.sql         # CREATE TABLE (versionado)
│   └── seeds.sql          # Datos de prueba, sin contraseñas reales
├── docs/                  # SRS, diagramas UML, decisiones de diseño
└── deploy/                # Notas de configuración y despliegue
```

Reglas de ubicación:

- Un archivo, una responsabilidad. Nada de `index_final_v2_definitivo.php`.
- Los PDF del SRS y los diagramas van en `docs/`, nunca en `public/`.
- Los archivos subidos por usuarios van a una carpeta ignorada por Git; nunca se versionan.
- Nombres de archivos en `kebab-case` para CSS/JS/imagenes; `PascalCase` para clases PHP.

---

## 3. Convenciones de código

### HTML

- `<!DOCTYPE html>`, `lang="es"`, `<meta charset="UTF-8">` y viewport.
- Etiquetas semánticas: `header`, `nav`, `main`, `section`, `article`, `footer`.
- Todo input lleva `<label>` asociado; toda imagen lleva `alt`.
- Sin estilos en línea y sin `<script>` con código dentro del HTML: usar `js/main.js` con `type="module"`.

### CSS

- Mobile-first y diseño responsive con media queries.
- Variables CSS en `variables.css`; no repetir valores mágicos.
- Convención de nombres tipo BEM: `.bloque__elemento--modificador`.
- Evitar `!important` salvo caso justificado y documentado.

### JavaScript

- Módulos ES nativos (`<script type="module" src="js/main.js">`).
- `const` / `let`, nunca `var`. Funciones flecha donde aporte claridad.
- Comunicación con el backend solo mediante `fetch` + `async/await`, con manejo de errores (`try/catch`).
- **Nunca** insertar datos del servidor con `innerHTML`; usar `textContent` o `createElement`.
- Sin `eval`, sin variables globales innecesarias, sin comentarios obvios.

### PHP

- `declare(strict_types=1);` al inicio de cada archivo PHP.
- Conexión PDO **una sola vez** en `src/config/database.php`, con `ERRMODE_EXCEPTION` y `ATTR_EMULATE_PREPARES => false`.
- **Toda** consulta con datos externos usa `prepare()` + `execute()` (prepared statements). Cero concatenación de SQL.
- Contraseñas solo con `password_hash()` / `password_verify()`.
- Validar entradas con `filter_input()` / `filter_var()`; nunca confiar en `$_POST`.
- Respuestas JSON con `header('Content-Type: application/json; charset=utf-8')` y códigos HTTP correctos.
- `display_errors = Off` en producción; logs en `deploy/` o fuera de la raíz pública.
- Sesiones: `session_regenerate_id(true)` al iniciar sesión.

### MySQL

- `utf8mb4` + `InnoDB` en todas las tablas.
- Claves primarias, foráneas e índices donde haga falta.
- `schema.sql` y `seeds.sql` siempre actualizados cuando cambie la base de datos.
- Ningún dump con datos reales o contraseñas dentro de Git.

---

## 4. Flujo de trabajo con Git / GitHub

### Ramas

| Rama | Uso |
| --- | --- |
| `main` | Producción. Solo entra por Pull Request. |
| `dev` | Integración de cambios. |
| `feature/nombre` | Funcionalidades nuevas. |
| `fix/nombre` | Correcciones. |
| `docs/nombre` | Documentación y bitácora. |

### Commits

Formato: `tipo: descripción corta en imperativo`

- `feat:` nueva funcionalidad
- `fix:` corrección de errores
- `style:` CSS / formato sin cambio de lógica
- `refactor:` reestructuración sin cambio de comportamiento
- `docs:` documentación, bitácora, diagramas
- `chore:` configuración, .gitignore, tareas de mantenimiento
- `deploy:` cambios de despliegue

Ejemplo: `docs: agregar reglas del proyecto y bitácora inicial`

### Antes de cada push

```powershell
git status
git pull --rebase origin dev
git add .
git commit -m "tipo: descripción corta"
git push origin nombre-de-la-rama
```

- Nunca hacer `git push --force` sin confirmación.
- Nunca subir `.env`, credenciales ni archivos grandes innecesarios (videos, PDFs temporales).
- Revisar `git status` antes de `git add .` para no incluir basura.
- Todo cambio terminado debe agregar una entrada en `bitacora.md` **en el mismo commit**.

### Despliegue

1. Verificar que `git status` esté limpio y que el remoto esté actualizado.
2. Aplicar `database/schema.sql` (y `seeds.sql` solo si aplica) en el servidor.
3. Configurar las variables de entorno del servidor a partir de `.env.example` (valores reales **fuera** de Git).
4. La raíz web debe contener `public/` y `api/` como hermanos (la API se llama con `../../api/` desde `public/js/api.js`). En producción, denegar el acceso HTTP directo a `src/` y `database/` o sacarlos de la raíz web.
5. Permisos: `assets/` y subidas en `0755` / `0644`, nunca `0777`.
6. Registrar el despliegue en `bitacora.md` con el tipo `deploy`.

---

## 5. Reglas para asistentes de código

1. Leer `claude.md` y `bitacora.md` al iniciar cualquier tarea.
2. Respetar el stack del punto 1 sin excepciones silenciosas.
3. Basarse en los documentos de `docs/`; si falta información, preguntar en lugar de inventar requisitos.
4. No crear estructuras paralelas ni archivos "de prueba" que luego queden en el repo.
5. Mantener comentarios y documentación en español.
6. Al terminar, actualizar `bitacora.md` con qué se hizo, por qué, archivos tocados y siguiente paso.
7. No ejecutar `git push`, borrar archivos ni modificar la base de datos sin confirmación del usuario.

---

## 6. Estado actual

- [x] Definir stack y reglas (`claude.md`).
- [x] Crear bitácora (`bitacora.md`).
- [x] Crear la estructura de carpetas y el `.gitignore`.
- [x] Crear `README.md` y `.env.example`.
- [ ] Inicializar Git y conectar el repositorio remoto de GitHub (requiere autorización del usuario).
- [x] Definir el esquema inicial en `database/schema.sql`.
- [x] Implementar la aplicación web a partir de los diagramas de `C:\changosgames`.

---

_Ultima actualización: 2026-11-26._
