/** Construcción de DOM con createElement/textContent (nunca innerHTML con datos del servidor). */
let contadorCampos = 0;

export function el(etiqueta, atributos = {}, hijos = []) {
  const nodo = document.createElement(etiqueta);

  for (const [clave, valor] of Object.entries(atributos)) {
    if (valor === null || valor === undefined || valor === false) continue;
    if (clave === 'clase') nodo.className = valor;
    else if (clave === 'texto') nodo.textContent = valor;
    else if (clave.startsWith('on') && typeof valor === 'function') {
      nodo.addEventListener(clave.slice(2).toLowerCase(), valor);
    } else {
      nodo.setAttribute(clave, valor === true ? '' : valor);
    }
  }

  const lista = Array.isArray(hijos) ? hijos : [hijos];
  for (const hijo of lista) {
    if (hijo === null || hijo === undefined) continue;
    nodo.append(hijo);
  }
  return nodo;
}

export function campo(etiqueta, nombre, { tipo = 'text', valor = '', requerido = true, opciones = null, paso = null } = {}) {
  contadorCampos += 1;
  const id = `campo-${contadorCampos}`;
  const atributos = { id, name: nombre, type: tipo, value: valor };
  if (requerido) atributos.required = true;
  if (paso !== null) atributos.step = paso;

  let control;
  if (opciones) {
    control = el('select', atributos, opciones.map(([v, t]) => el('option', { value: v, texto: t })));
    control.value = valor || (opciones[0]?.[0] ?? '');
  } else {
    control = el('input', atributos);
  }
  return el('div', { clase: 'campo' }, [
    el('label', { clase: 'campo__etiqueta', for: id, texto: etiqueta }),
    control,
  ]);
}

export function boton(texto, alClic, { tipo = 'button', variante = '', desactivado = false } = {}) {
  const clases = ['boton'];
  if (variante) clases.push(`boton--${variante}`);
  return el('button', {
    type: tipo,
    clase: clases.join(' '),
    texto,
    onclick: alClic,
    disabled: desactivado,
  });
}

export function insignia(texto, tono = '') {
  return el('span', { clase: `insignia${tono ? ` insignia--${tono}` : ''}`, texto });
}

export function tabla(columnas, filas) {
  const thead = el('thead', {}, el('tr', {}, columnas.map((c) => el('th', { texto: c }))));
  const tbody = el('tbody', {}, filas.map((fila) => el('tr', {}, fila)));
  return el('table', { clase: 'tabla' }, [thead, tbody]);
}

export function cabecera(titulo, descripcion, acciones = []) {
  return el('div', { clase: 'seccion__cabecera' }, [
    el('div', {}, [el('h2', { texto: titulo }), el('p', { texto: descripcion })]),
    el('div', { clase: 'fila' }, acciones),
  ]);
}

export function tarjeta(titulo, descripcion, pie = []) {
  return el('article', { clase: 'tarjeta' }, [
    el('h3', { clase: 'tarjeta__titulo', texto: titulo }),
    el('p', { clase: 'tarjeta__texto', texto: descripcion }),
    pie.length ? el('div', { clase: 'tarjeta__pie' }, pie) : null,
  ]);
}

export function medidor(etiqueta, valor) {
  return el('div', { clase: 'medidor' }, [
    el('div', { clase: 'medidor__etiqueta', texto: etiqueta }),
    el('div', { clase: 'medidor__valor', texto: valor }),
  ]);
}

export function vacio(mensaje) {
  return el('p', { clase: 'vacio', texto: mensaje });
}

export function moneda(valor) {
  return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(valor) || 0);
}

export function fecha(valor) {
  if (!valor) return '—';
  const f = new Date(String(valor).replace(' ', 'T'));
  return Number.isNaN(f.getTime()) ? String(valor) : f.toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' });
}

export function aviso(mensaje, esError = false) {
  const toast = document.getElementById('toast');
  if (!toast) return;
  toast.textContent = mensaje;
  toast.classList.toggle('toast--error', esError);
  toast.hidden = false;
  clearTimeout(aviso._temporizador);
  aviso._temporizador = setTimeout(() => { toast.hidden = true; }, 4200);
}

export function confirmar(mensaje) {
  return window.confirm(mensaje);
}
