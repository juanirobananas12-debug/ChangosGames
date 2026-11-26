-- ChangosGames - esquema inicial de MySQL / MariaDB
-- Derivado de: SRS, diagrama de casos de uso, diagrama de clases,
-- diagramas de actividades y diagrama de estados (C:\changosgames).
-- Charset: utf8mb4 | Motor: InnoDB

CREATE DATABASE IF NOT EXISTS changosgames
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE changosgames;

-- Roles: cliente, empleado, gerente (encargado) y administrador.
CREATE TABLE IF NOT EXISTS roles (
  id_rol TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(30) NOT NULL,
  PRIMARY KEY (id_rol),
  UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuarios: une los conceptos Administrador, Empleado y Cliente del diagrama de clases.
-- id_usuario equivale a id_admin / id_empleado / id_cliente según el rol.
CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_rol TINYINT UNSIGNED NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(150) NOT NULL,
  usuario VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uq_usuarios_correo (correo),
  UNIQUE KEY uq_usuarios_usuario (usuario),
  KEY idx_usuarios_rol (id_rol),
  CONSTRAINT fk_usuarios_roles FOREIGN KEY (id_rol) REFERENCES roles (id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Productos: No_Producto, lote, categoria, clasificacion del diagrama de clases.
-- estado corresponde a la enumeración <<enumeration>> estados: pristino / dañado.
CREATE TABLE IF NOT EXISTS productos (
  id_producto INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(120) NOT NULL,
  catalogo VARCHAR(80) NOT NULL,
  categoria VARCHAR(80) NOT NULL,
  clasificacion VARCHAR(80) NOT NULL,
  lote VARCHAR(50) NOT NULL,
  precio DECIMAL(10,2) NOT NULL,
  estado ENUM('pristino','dañado') NOT NULL DEFAULT 'pristino',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_producto),
  KEY idx_productos_nombre (nombre),
  KEY idx_productos_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventario: cantidad_de_productos. Relación 1 Inventario -> 1..* Productos.
CREATE TABLE IF NOT EXISTS inventario (
  id_inventario INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_producto INT UNSIGNED NOT NULL,
  cantidad_de_productos INT UNSIGNED NOT NULL DEFAULT 0,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_inventario),
  UNIQUE KEY uq_inventario_producto (id_producto),
  CONSTRAINT fk_inventario_productos FOREIGN KEY (id_producto) REFERENCES productos (id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reservas: estados del diagrama de estados (abierta -> reservada -> pagada -> registrada; cancelada).
-- Se añaden 'entregada' y 'vencida' para cubrir el diagrama de actividades (7 días y entrega).
CREATE TABLE IF NOT EXISTS reservas (
  id_reserva INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_cliente INT UNSIGNED NOT NULL,
  id_producto INT UNSIGNED NOT NULL,
  cantidad INT UNSIGNED NOT NULL,
  metodo_entrega ENUM('sucursal','domicilio') NOT NULL DEFAULT 'sucursal',
  estado ENUM('abierta','reservada','pagada','entregada','cancelada','vencida') NOT NULL DEFAULT 'abierta',
  vence_en DATETIME NOT NULL,
  creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_reserva),
  KEY idx_reservas_estado (estado),
  KEY idx_reservas_cliente (id_cliente),
  KEY idx_reservas_vence (vence_en),
  CONSTRAINT fk_reservas_cliente FOREIGN KEY (id_cliente) REFERENCES usuarios (id_usuario),
  CONSTRAINT fk_reservas_producto FOREIGN KEY (id_producto) REFERENCES productos (id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ventas: No_venta, fecha, total_venta, metodo_de_pago del diagrama de clases.
CREATE TABLE IF NOT EXISTS ventas (
  id_venta INT UNSIGNED NOT NULL AUTO_INCREMENT,
  numero_venta VARCHAR(20) NOT NULL,
  id_reserva INT UNSIGNED NULL,
  id_cliente INT UNSIGNED NOT NULL,
  id_empleado INT UNSIGNED NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total_venta DECIMAL(10,2) NOT NULL,
  metodo_de_pago ENUM('efectivo','tarjeta') NOT NULL,
  estado ENUM('pagada','registrada','cancelada') NOT NULL DEFAULT 'pagada',
  PRIMARY KEY (id_venta),
  UNIQUE KEY uq_ventas_numero (numero_venta),
  KEY idx_ventas_estado (estado),
  KEY idx_ventas_cliente (id_cliente),
  CONSTRAINT fk_ventas_reserva FOREIGN KEY (id_reserva) REFERENCES reservas (id_reserva),
  CONSTRAINT fk_ventas_cliente FOREIGN KEY (id_cliente) REFERENCES usuarios (id_usuario),
  CONSTRAINT fk_ventas_empleado FOREIGN KEY (id_empleado) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Detalle de cada venta (permite devolver el stock al cancelar).
CREATE TABLE IF NOT EXISTS venta_detalles (
  id_detalle INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_venta INT UNSIGNED NOT NULL,
  id_producto INT UNSIGNED NOT NULL,
  cantidad INT UNSIGNED NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id_detalle),
  KEY idx_detalles_venta (id_venta),
  CONSTRAINT fk_detalles_venta FOREIGN KEY (id_venta) REFERENCES ventas (id_venta),
  CONSTRAINT fk_detalles_producto FOREIGN KEY (id_producto) REFERENCES productos (id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Movimientos de caja / ingresos: los atributos de la clase Registros del diagrama.
CREATE TABLE IF NOT EXISTS movimientos_caja (
  id_movimiento INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo ENUM('ingreso','egreso') NOT NULL,
  concepto VARCHAR(150) NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  metodo_pago ENUM('efectivo','tarjeta') NULL DEFAULT NULL,
  id_venta INT UNSIGNED NULL DEFAULT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_movimiento),
  KEY idx_caja_tipo (tipo),
  KEY idx_caja_fecha (creado_en),
  CONSTRAINT fk_caja_venta FOREIGN KEY (id_venta) REFERENCES ventas (id_venta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro de auditoría: venta, movimientos de caja, ingresos, reservas, cuentas, entregas.
CREATE TABLE IF NOT EXISTS registros (
  id_registro INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo ENUM('venta','cancelacion','reserva','entrega','ingreso_producto','cuenta') NOT NULL,
  referencia_id INT UNSIGNED NULL DEFAULT NULL,
  descripcion VARCHAR(255) NOT NULL,
  id_usuario INT UNSIGNED NULL DEFAULT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_registro),
  KEY idx_registros_tipo (tipo),
  KEY idx_registros_fecha (creado_en),
  CONSTRAINT fk_registros_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
