/** Aplicación ChangosGames: catálogo, reservas, ventas, caja y cuentas. */
import { api } from './api.js';
import {
  el, campo, boton, insignia, tabla, cabecera, tarjeta, medidor,
  vacio, moneda, fecha, aviso, confirmar,
} from './ui.js';

const estado = { usuario: null, productos: [], busqueda: '', seccion: 'catalogo' };
const $ = (id) => document.getElementById(id);

const ROLES = {
  cliente: 'Cliente',
  empleado: 'Empleado',
  gerente: 'Encargado',
  administrador: 'Administrador',
};

const es = (...roles) => roles.includes(estado.usuario?.rol);
const esEmpleado = () => es('empleado', 'gerente', 'administrador');
const esEncargado = () => es('gerente', 'administrador');
const esAdmin = () => es('administrador');

function mostrar(nombre) {
  estado.seccion = nombre;
  document.querySelectorAll('.seccion').forEach((seccion) => { seccion.hidden = true; });
  $(`seccion-${nombre}`).hidden = false;
  document.querySelectorAll('#nav-principal button').forEach((b) => {
    b.classList.toggle('boton--activo', b.dataset.seccion === nombre);
  });

  if (nombre === 'catalogo') cargarCatalogo();
  if (nombre === 'reservas') cargarReservas($('filtro-reservas')?.value || '');
  if (nombre === 'ventas') cargarVentas();
  if (nombre === 'caja') cargarCaja();
  if (nombre === 'cuentas') cargarCuentas();
}

function construirNav() {
  const nav = $('nav-principal');
  nav.replaceChildren();
  const enlaces = [
    ['catalogo', 'Catálogo', true],
    ['reservas', estado.usuario?.rol === 'cliente' ? 'Mis reservas' : 'Reservas', Boolean(estado.usuario)],
    ['ventas', 'Ventas', Boolean(estado.usuario)],
    ['caja', 'Caja y reportes', esEncargado()],
    ['cuentas', 'Cuentas', esAdmin()],
    ['acceso', 'Acceder', !estado.usuario],
  ];
  for (const [id, etiqueta, visible] of enlaces) {
    if (!visible) continue;
    nav.append(boton(etiqueta, () => mostrar(id), { variante: 'enlace' }));
    nav.lastElementChild.dataset.seccion = id;
  }
}

function construirSesion() {
  const contenedor = $('sesion');
  contenedor.replaceChildren();
  if (!estado.usuario) {
    contenedor.append(el('span', { texto: 'Invitado' }));
    return;
  }
  contenedor.append(
    el('span', { texto: `${estado.usuario.nombre} · ${ROLES[estado.usuario.rol] || estado.usuario.rol}` }),
    boton('Salir', async () => {
      try {
        await api.logout();
        estado.usuario = null;
        refrescar();
      } catch (error) {
        aviso(error.message, true);
      }
    }, { variante: 'secundario' }),
  );
}

function refrescar() {
  construirNav();
  construirSesion();
  mostrar('catalogo');
}

/* ---------------------------------- Catálogo --------------------------------- */

async function cargarCatalogo() {
  try {
    const respuesta = await api.listarProductos(estado.busqueda);
    estado.productos = respuesta.productos;
  } catch (error) {
    aviso(error.message, true);
  }
  pintarCatalogo();
}

function pintarCatalogo() {
  const seccion = $('seccion-catalogo');
  seccion.replaceChildren();

  const buscador = el('form', { clase: 'fila', onsubmit: (evento) => {
    evento.preventDefault();
    estado.busqueda = buscador.querySelector('input').value.trim();
    cargarCatalogo();
  } });
  buscador.append(
    el('input', { type: 'search', placeholder: 'Buscar por nombre, categoría o catálogo', value: estado.busqueda, 'aria-label': 'Buscar productos' }),
    boton('Buscar', null, { tipo: 'submit' }),
  );

  seccion.append(cabecera(
    'Catálogo de videojuegos',
    'Consulta precios y disponibilidad. Los clientes pueden reservar productos por 7 días.',
    [buscador],
  ));

  const panel = el('div', { id: 'panel-reserva', clase: 'tarjeta' }, el('p', {
    clase: 'tarjeta__texto',
    texto: estado.usuario?.rol === 'cliente'
      ? 'Selecciona un producto para reservarlo.'
      : 'Inicia sesión como cliente para poder reservar productos.',
  }));
  seccion.append(panel);

  if (esEmpleado()) {
    seccion.append(el('h3', { texto: 'Ingresar producto al inventario', style: 'margin:24px 0 12px' }));
    seccion.append(formularioIngresoProducto());
  }

  seccion.append(el('h3', { texto: `Productos (${estado.productos.length})`, style: 'margin:24px 0 12px' }));

  if (estado.productos.length === 0) {
    seccion.append(vacio('No hay productos que coincidan con la búsqueda.'));
    return;
  }

  const rejilla = el('div', { clase: 'rejilla' });
  for (const producto of estado.productos) {
    const sinStock = Number(producto.cantidad_de_productos) === 0;
    const pie = [
      el('span', { clase: 'tarjeta__precio', texto: moneda(producto.precio) }),
      estado.usuario?.rol === 'cliente'
        ? boton('Reservar', () => abrirReserva(producto), { desactivado: sinStock })
        : insignia(sinStock ? 'Agotado' : `${producto.cantidad_de_productos} disponibles`, sinStock ? 'peligro' : 'ok'),
    ];
    rejilla.append(tarjeta(
      producto.nombre,
      `${producto.categoria} · ${producto.clasificacion} · Catálogo ${producto.catalogo} · Lote ${producto.lote}`,
      pie,
    ));
  }
  seccion.append(rejilla);
}

function formularioIngresoProducto() {
  const form = el('form', { clase: 'formulario', onsubmit: async (evento) => {
    evento.preventDefault();
    const datos = new FormData(form);
    try {
      await api.ingresarProducto({
        nombre: datos.get('nombre'),
        catalogo: datos.get('catalogo'),
        categoria: datos.get('categoria'),
        clasificacion: datos.get('clasificacion'),
        lote: datos.get('lote'),
        precio: Number(datos.get('precio')),
        cantidad: Number(datos.get('cantidad')),
        estado: datos.get('estado'),
      });
      aviso('Producto ingresado al inventario.');
      form.reset();
      cargarCatalogo();
    } catch (error) {
      aviso(error.message, true);
    }
  } });

  form.append(
    campo('Nombre', 'nombre'),
    campo('Catálogo', 'catalogo'),
    campo('Categoría', 'categoria'),
    campo('Clasificación', 'clasificacion'),
    campo('Lote', 'lote'),
    campo('Precio', 'precio', { tipo: 'number', paso: '0.01' }),
    campo('Cantidad', 'cantidad', { tipo: 'number' }),
    campo('Estado', 'estado', { opciones: [['pristino', 'Pristino'], ['dañado', 'Dañado']] }),
    boton('Ingresar', null, { tipo: 'submit' }),
  );
  return form;
}

function abrirReserva(producto) {
  const panel = $('panel-reserva');
  const form = el('form', { clase: 'formulario', onsubmit: async (evento) => {
    evento.preventDefault();
    const datos = new FormData(form);
    try {
      await api.crearReserva({
        id_producto: producto.id_producto,
        cantidad: Number(datos.get('cantidad')),
        metodo_entrega: datos.get('metodo_entrega'),
      });
      aviso('Producto reservado correctamente. Vence en 7 días.');
      cargarCatalogo();
    } catch (error) {
      aviso(error.message, true);
    }
  } });

  form.append(
    campo('Cantidad', 'cantidad', { tipo: 'number', valor: '1' }),
    campo('Método de entrega', 'metodo_entrega', {
      opciones: [['sucursal', 'Recoger en sucursal'], ['domicilio', 'Entrega a domicilio']],
    }),
    boton('Confirmar reserva', null, { tipo: 'submit' }),
  );

  panel.replaceChildren(
    el('h3', { clase: 'tarjeta__titulo', texto: `Reservar ${producto.nombre}` }),
    el('p', { clase: 'tarjeta__texto', texto: `${moneda(producto.precio)} · ${producto.cantidad_de_productos} disponibles` }),
    form,
  );
  panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

/* ---------------------------------- Reservas --------------------------------- */

async function cargarReservas(filtro = '') {
  try {
    const respuesta = estado.usuario?.rol === 'cliente'
      ? await api.misReservas()
      : await api.listarReservas(filtro);
    pintarReservas(respuesta.reservas, filtro);
  } catch (error) {
    aviso(error.message, true);
  }
}

function pintarReservas(reservas, filtro) {
  const seccion = $('seccion-reservas');
  seccion.replaceChildren();

  const acciones = [];
  if (esEmpleado()) {
    const filtroSelect = el('select', {
      id: 'filtro-reservas',
      'aria-label': 'Filtrar reservas',
      onchange: (evento) => cargarReservas(evento.target.value),
    }, [
      ['', 'Todas'], ['reservada', 'Reservadas'], ['pagada', 'Pagadas'],
      ['entregada', 'Entregadas'], ['cancelada', 'Canceladas'], ['vencida', 'Vencidas'],
    ].map(([valor, texto]) => el('option', { value: valor, texto })));
    filtroSelect.value = filtro;
    acciones.push(filtroSelect);
    acciones.push(boton('Expirar vencidas', async () => {
      try {
        const r = await api.expirarReservas();
        aviso(r.mensaje);
        cargarReservas(filtro);
      } catch (error) {
        aviso(error.message, true);
      }
    }, { variante: 'secundario' }));
  }

  seccion.append(cabecera(
    esEmpleado() ? 'Reservas pendientes' : 'Mis reservas',
    'Una reserva mantiene el producto apartado durante 7 días y puede cancelarse devolviendo el stock.',
    acciones,
  ));
  seccion.append(el('div', { id: 'panel-venta', clase: 'tarjeta', hidden: true }));

  if (reservas.length === 0) {
    seccion.append(vacio('No hay reservas para mostrar.'));
    return;
  }

  const columnas = ['Reserva', 'Cliente', 'Producto', 'Cantidad', 'Entrega', 'Vence', 'Estado', 'Acciones'];
  const filas = reservas.map((reserva) => {
    const accionesFila = el('div', { clase: 'fila' });

    if (reserva.estado === 'reservada') {
      if (esEmpleado()) {
        accionesFila.append(boton('Vender', () => abrirVenta(reserva), { variante: 'secundario' }));
      }
      accionesFila.append(boton('Cancelar', async () => {
        if (!confirmar(`¿Cancelar la reserva #${reserva.id_reserva}?`)) return;
        try {
          await api.cancelarReserva({ id_reserva: reserva.id_reserva });
          aviso('Reserva cancelada. El stock fue restaurado.');
          cargarReservas(filtro);
        } catch (error) {
          aviso(error.message, true);
        }
      }, { variante: 'peligro' }));
    }

    if (reserva.estado === 'pagada' && reserva.metodo_entrega === 'domicilio' && esEmpleado()) {
      accionesFila.append(boton('Confirmar entrega', async () => {
        try {
          await api.confirmarEntrega({ id_reserva: reserva.id_reserva });
          aviso('Entrega confirmada y cliente notificado.');
          cargarReservas(filtro);
        } catch (error) {
          aviso(error.message, true);
        }
      }, { variante: 'secundario' }));
    }

    if (accionesFila.childElementCount === 0) {
      accionesFila.append(el('span', { clase: 'tarjeta__texto', texto: '—' }));
    }

    const tono = { reservada: 'aviso', pagada: 'ok', entregada: 'ok', cancelada: 'peligro', vencida: 'peligro' }[reserva.estado] || '';
    return [
      `#${reserva.id_reserva}`,
      reserva.cliente,
      reserva.producto,
      reserva.cantidad,
      reserva.metodo_entrega === 'domicilio' ? 'Domicilio' : 'Sucursal',
      fecha(reserva.vence_en),
      insignia(reserva.estado, tono),
      accionesFila,
    ];
  });

  seccion.append(el('div', { clase: 'contenedor-tabla' }, tabla(columnas, filas)));
}

function abrirVenta(reserva) {
  const panel = $('panel-venta');
  const form = el('form', { clase: 'formulario', onsubmit: async (evento) => {
    evento.preventDefault();
    const datos = new FormData(form);
    try {
      await api.crearVenta({
        id_reserva: reserva.id_reserva,
        metodo_pago: datos.get('metodo_pago'),
      });
      aviso('Venta realizada, registrada en caja y con inventario actualizado.');
      panel.hidden = true;
      cargarReservas($('filtro-reservas')?.value || '');
    } catch (error) {
      aviso(error.message, true);
    }
  } });

  form.append(
    el('p', { clase: 'tarjeta__texto', texto: `${reserva.cliente} · ${reserva.producto} x${reserva.cantidad} · ${moneda(reserva.precio * reserva.cantidad)}` }),
    campo('Método de pago', 'metodo_pago', { opciones: [['efectivo', 'Efectivo'], ['tarjeta', 'Tarjeta']] }),
    boton('Realizar venta', null, { tipo: 'submit' }),
    boton('Cerrar', () => { panel.hidden = true; }, { variante: 'secundario' }),
  );

  panel.replaceChildren(
    el('h3', { clase: 'tarjeta__titulo', texto: `Realizar venta de la reserva #${reserva.id_reserva}` }),
    form,
  );
  panel.hidden = false;
  panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

/* ----------------------------------- Ventas ---------------------------------- */

async function cargarVentas() {
  try {
    const respuesta = await api.listarVentas();
    pintarVentas(respuesta.ventas);
  } catch (error) {
    aviso(error.message, true);
  }
}

function pintarVentas(ventas) {
  const seccion = $('seccion-ventas');
  seccion.replaceChildren(cabecera(
    'Ventas registradas',
    'Ventas con pago en efectivo o tarjeta; el inventario se actualiza automáticamente.',
  ));

  if (ventas.length === 0) {
    seccion.append(vacio('Aún no hay ventas registradas.'));
    return;
  }

  const filas = ventas.map((venta) => {
    const acciones = el('div', { clase: 'fila' });
    if (esEmpleado() && venta.estado !== 'cancelada') {
      acciones.append(boton('Cancelar venta', async () => {
        if (!confirmar(`¿Cancelar la venta ${venta.numero_venta}? Se devolverá el stock.`)) return;
        try {
          await api.cancelarVenta({ id_venta: venta.id_venta, motivo: 'Cancelación desde el sistema' });
          aviso('Venta cancelada. Stock y caja actualizados.');
          cargarVentas();
        } catch (error) {
          aviso(error.message, true);
        }
      }, { variante: 'peligro' }));
    } else {
      acciones.append(el('span', { clase: 'tarjeta__texto', texto: '—' }));
    }

    const tono = { registrada: 'ok', pagada: 'aviso', cancelada: 'peligro' }[venta.estado] || '';
    return [
      venta.numero_venta,
      fecha(venta.fecha),
      venta.cliente,
      moneda(venta.total_venta),
      venta.metodo_de_pago,
      insignia(venta.estado, tono),
      acciones,
    ];
  });

  seccion.append(el('div', { clase: 'contenedor-tabla' }, tabla(
    ['Venta', 'Fecha', 'Cliente', 'Total', 'Pago', 'Estado', 'Acciones'], filas,
  )));
}

/* ------------------------------- Caja y reportes ------------------------------ */

async function cargarCaja() {
  try {
    const [movimientos, registros, reporte] = await Promise.all([
      api.movimientos(), api.registros(), api.reporte(),
    ]);
    pintarCaja(movimientos, registros, reporte);
  } catch (error) {
    aviso(error.message, true);
  }
}

function pintarCaja(movimientos, registros, reporte) {
  const seccion = $('seccion-caja');
  seccion.replaceChildren(cabecera(
    'Caja, ingresos y reportes',
    'Solo el encargado y el administrador pueden consultar los movimientos y los registros.',
  ));

  const resumen = movimientos.resumen;
  seccion.append(el('div', { clase: 'columnas' }, [
    medidor('Ingresos', moneda(resumen.ingresos)),
    medidor('Egresos', moneda(resumen.egresos)),
    medidor('Saldo', moneda(resumen.saldo)),
  ]));

  seccion.append(el('h3', { texto: 'Reporte generado', style: 'margin:24px 0 12px' }));
  seccion.append(el('div', { clase: 'columnas' }, [
    el('div', { clase: 'contenedor-tabla' }, tabla(
      ['Método de pago', 'Ventas', 'Total'],
      reporte.reporte.por_metodo.map((fila) => [fila.metodo_de_pago, fila.ventas, moneda(fila.total)]),
    )),
    el('div', { clase: 'contenedor-tabla' }, tabla(
      ['Producto', 'Unidades', 'Total'],
      reporte.reporte.productos.map((fila) => [fila.nombre, fila.unidades, moneda(fila.total)]),
    )),
  ]));

  seccion.append(el('h3', { texto: 'Movimientos de caja', style: 'margin:24px 0 12px' }));
  seccion.append(el('div', { clase: 'contenedor-tabla' }, tabla(
    ['Fecha', 'Tipo', 'Concepto', 'Monto', 'Método', 'Venta'],
    movimientos.movimientos.map((m) => [
      fecha(m.creado_en),
      insignia(m.tipo, m.tipo === 'ingreso' ? 'ok' : 'peligro'),
      m.concepto,
      moneda(m.monto),
      m.metodo_pago || '—',
      m.numero_venta || '—',
    ]),
  )));

  seccion.append(el('h3', { texto: 'Registros del sistema', style: 'margin:24px 0 12px' }));
  seccion.append(el('div', { clase: 'contenedor-tabla' }, tabla(
    ['Fecha', 'Tipo', 'Descripción', 'Usuario'],
    registros.registros.map((r) => [fecha(r.creado_en), r.tipo, r.descripcion, r.usuario || 'Sistema']),
  )));
}

/* ---------------------------------- Cuentas ---------------------------------- */

async function cargarCuentas() {
  try {
    const respuesta = await api.listarCuentas();
    pintarCuentas(respuesta.cuentas, respuesta.roles);
  } catch (error) {
    aviso(error.message, true);
  }
}

function pintarCuentas(cuentas, roles) {
  const seccion = $('seccion-cuentas');
  seccion.replaceChildren(cabecera(
    'Cuentas y permisos',
    'El administrador crea cuentas de empleados/encargados y asigna sus permisos.',
  ));

  if (esAdmin()) {
    const form = el('form', { clase: 'formulario', onsubmit: async (evento) => {
      evento.preventDefault();
      const datos = new FormData(form);
      try {
        await api.crearEmpleado({
          nombre: datos.get('nombre'),
          correo: datos.get('correo'),
          usuario: datos.get('usuario'),
          contraseña: datos.get('contraseña'),
          rol: datos.get('rol'),
        });
        aviso('Cuenta creada correctamente.');
        form.reset();
        cargarCuentas();
      } catch (error) {
        aviso(error.message, true);
      }
    } });

    form.append(
      campo('Nombre', 'nombre'),
      campo('Correo', 'correo', { tipo: 'email' }),
      campo('Usuario', 'usuario'),
      campo('Contraseña', 'contraseña', { tipo: 'password' }),
      campo('Rol', 'rol', { opciones: [['empleado', 'Empleado'], ['gerente', 'Encargado'], ['administrador', 'Administrador']] }),
      boton('Crear cuenta', null, { tipo: 'submit' }),
    );
    seccion.append(el('div', { clase: 'tarjeta' }, [
      el('h3', { clase: 'tarjeta__titulo', texto: 'Nueva cuenta' }), form,
    ]));
  }

  const filas = cuentas.map((cuenta) => {
    const opcionesRol = roles.map((rol) => [rol.nombre, ROLES[rol.nombre] || rol.nombre]);
    const selector = el('select', { 'aria-label': `Rol de ${cuenta.usuario}` },
      opcionesRol.map(([valor, texto]) => el('option', { value: valor, texto })));
    selector.value = cuenta.rol;

    const acciones = el('div', { clase: 'fila' });
    if (esAdmin()) {
      acciones.append(selector, boton('Guardar rol', async () => {
        try {
          await api.cambiarRol({ id_usuario: cuenta.id_usuario, rol: selector.value });
          aviso('Permisos actualizados.');
          cargarCuentas();
        } catch (error) {
          aviso(error.message, true);
        }
      }, { variante: 'secundario' }));
    } else {
      acciones.append(insignia(ROLES[cuenta.rol] || cuenta.rol));
    }

    return [
      cuenta.id_usuario,
      cuenta.nombre,
      cuenta.usuario,
      cuenta.correo,
      insignia(cuenta.activo ? 'Activa' : 'Inactiva', cuenta.activo ? 'ok' : 'peligro'),
      acciones,
    ];
  });

  seccion.append(el('h3', { texto: `Usuarios (${cuentas.length})`, style: 'margin:24px 0 12px' }));
  seccion.append(el('div', { clase: 'contenedor-tabla' }, tabla(
    ['ID', 'Nombre', 'Usuario', 'Correo', 'Estado', 'Permisos'], filas,
  )));
}

/* ----------------------------------- Acceso ---------------------------------- */

function pintarAcceso() {
  const seccion = $('seccion-acceso');
  const login = el('form', { clase: 'formulario formulario--vertical', onsubmit: async (evento) => {
    evento.preventDefault();
    const datos = new FormData(login);
    try {
      const respuesta = await api.login(datos.get('usuario'), datos.get('contraseña'));
      estado.usuario = respuesta.usuario;
      aviso(`Bienvenido, ${estado.usuario.nombre}.`);
      refrescar();
    } catch (error) {
      aviso(error.message, true);
    }
  } });
  login.append(
    campo('Usuario', 'usuario'),
    campo('Contraseña', 'contraseña', { tipo: 'password' }),
    boton('Iniciar sesión', null, { tipo: 'submit' }),
  );

  const registro = el('form', { clase: 'formulario formulario--vertical', onsubmit: async (evento) => {
    evento.preventDefault();
    const datos = new FormData(registro);
    try {
      await api.registrarCliente({
        nombre: datos.get('nombre'),
        correo: datos.get('correo'),
        usuario: datos.get('usuario'),
        contraseña: datos.get('contraseña'),
      });
      aviso('Cuenta creada. Ahora puedes iniciar sesión.');
      registro.reset();
    } catch (error) {
      aviso(error.message, true);
    }
  } });
  registro.append(
    campo('Nombre', 'nombre'),
    campo('Correo', 'correo', { tipo: 'email' }),
    campo('Usuario', 'usuario'),
    campo('Contraseña', 'contraseña', { tipo: 'password' }),
    boton('Crear cuenta de cliente', null, { tipo: 'submit' }),
  );

  seccion.replaceChildren(
    cabecera('Acceso al sistema', 'Inicia sesión para reservar, vender o consultar reportes según tu rol.'),
    el('div', { clase: 'columnas' }, [
      el('div', { clase: 'tarjeta' }, [el('h3', { clase: 'tarjeta__titulo', texto: 'Iniciar sesión' }), login]),
      el('div', { clase: 'tarjeta' }, [el('h3', { clase: 'tarjeta__titulo', texto: 'Crear cuenta de cliente' }), registro]),
    ]),
  );
}

/* ----------------------------------- Arranque --------------------------------- */

async function iniciar() {
  try {
    const respuesta = await api.yo();
    estado.usuario = respuesta.usuario;
  } catch (error) {
    aviso(error.message, true);
  }
  pintarAcceso();
  refrescar();
}

iniciar();
