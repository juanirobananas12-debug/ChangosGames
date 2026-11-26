/** Cliente HTTP nativo (fetch) para los endpoints PHP de /api. */
const RAIZ = new URL('../../api/', import.meta.url).href;
let csrf = '';

export async function peticion(archivo, accion, { metodo = 'GET', datos, parametros = {} } = {}) {
  const url = new URL(archivo, RAIZ);
  url.searchParams.set('accion', accion);
  for (const [clave, valor] of Object.entries(parametros)) {
    if (valor !== '' && valor !== null && valor !== undefined) url.searchParams.set(clave, valor);
  }

  const opciones = {
    method: metodo,
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  };

  if (datos !== undefined) {
    opciones.headers['Content-Type'] = 'application/json';
    opciones.body = JSON.stringify(datos);
  }
  if (metodo !== 'GET' && csrf) {
    opciones.headers['X-CSRF-Token'] = csrf;
  }

  const respuesta = await fetch(url, opciones);
  let cuerpo;
  try {
    cuerpo = await respuesta.json();
  } catch {
    throw new Error('El servidor no devolvió una respuesta JSON válida.');
  }

  if (cuerpo.csrf) csrf = cuerpo.csrf;
  if (!respuesta.ok) throw new Error(cuerpo.error || 'No se pudo completar la solicitud.');
  return cuerpo;
}

export const api = {
  yo: () => peticion('auth.php', 'yo'),
  login: (usuario, contraseña) => peticion('auth.php', 'login', { metodo: 'POST', datos: { usuario, contraseña } }),
  logout: async () => {
    const respuesta = await peticion('auth.php', 'logout', { metodo: 'POST', datos: {} });
    await peticion('auth.php', 'yo'); // renueva el token CSRF de la nueva sesión
    return respuesta;
  },

  registrarCliente: (datos) => peticion('cuentas.php', 'registrar-cliente', { metodo: 'POST', datos }),
  listarCuentas: () => peticion('cuentas.php', 'listar'),
  crearEmpleado: (datos) => peticion('cuentas.php', 'crear-empleado', { metodo: 'POST', datos }),
  cambiarRol: (datos) => peticion('cuentas.php', 'cambiar-rol', { metodo: 'POST', datos }),

  listarProductos: (q = '') => peticion('productos.php', 'listar', { parametros: { q } }),
  ingresarProducto: (datos) => peticion('productos.php', 'ingresar', { metodo: 'POST', datos }),

  crearReserva: (datos) => peticion('reservas.php', 'crear', { metodo: 'POST', datos }),
  misReservas: () => peticion('reservas.php', 'mias'),
  listarReservas: (estado = '') => peticion('reservas.php', 'listar', { parametros: { estado } }),
  cancelarReserva: (datos) => peticion('reservas.php', 'cancelar', { metodo: 'POST', datos }),
  confirmarEntrega: (datos) => peticion('reservas.php', 'confirmar-entrega', { metodo: 'POST', datos }),
  expirarReservas: () => peticion('reservas.php', 'expirar', { metodo: 'POST', datos: {} }),

  listarVentas: () => peticion('ventas.php', 'listar'),
  crearVenta: (datos) => peticion('ventas.php', 'crear', { metodo: 'POST', datos }),
  cancelarVenta: (datos) => peticion('ventas.php', 'cancelar', { metodo: 'POST', datos }),

  movimientos: (tipo = '') => peticion('caja.php', 'movimientos', { parametros: { tipo } }),
  registros: () => peticion('caja.php', 'registros'),
  reporte: () => peticion('caja.php', 'reporte'),
};
