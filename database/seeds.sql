-- Datos de prueba (sin contraseñas reales).
-- Los usuarios se crean con database/seed.php para usar password_hash().

USE changosgames;

INSERT INTO roles (id_rol, nombre) VALUES
  (1, 'cliente'),
  (2, 'empleado'),
  (3, 'gerente'),
  (4, 'administrador')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO productos (id_producto, nombre, catalogo, categoria, clasificacion, lote, precio, estado) VALUES
  (1, 'Changos Kart Deluxe', 'CG-001', 'Carreras', 'Todos', 'L-2401', 899.00, 'pristino'),
  (2, 'Super Changos Bros', 'CG-002', 'Plataformas', 'Todos', 'L-2402', 649.00, 'pristino'),
  (3, 'Changos Fútbol 2026', 'CG-003', 'Deportes', 'Todos', 'L-2403', 1199.00, 'pristino'),
  (4, 'Misterio de la Changa', 'CG-004', 'Aventura', 'Mayores de 12', 'L-2404', 799.00, 'pristino'),
  (5, 'Changos Space Invaders', 'CG-005', 'Arcade', 'Todos', 'L-2405', 449.00, 'dañado'),
  (6, 'Leyenda del Bosque', 'CG-006', 'Rol', 'Mayores de 12', 'L-2406', 999.00, 'pristino')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO inventario (id_producto, cantidad_de_productos) VALUES
  (1, 12), (2, 8), (3, 5), (4, 3), (5, 2), (6, 7)
ON DUPLICATE KEY UPDATE cantidad_de_productos = VALUES(cantidad_de_productos);
