-- =============================================================
-- PRUEBA TÉCNICA SISTEMAS EXPERTOS - MANTENEDOR DE BODEGAS
-- Script de creación de tablas y datos de ejemplo
-- Base de datos: PostgreSQL
-- =============================================================

-- Eliminar tablas si existen
DROP TABLE IF EXISTS bodega_encargado CASCADE;
DROP TABLE IF EXISTS bodegas CASCADE;
DROP TABLE IF EXISTS encargados CASCADE;

-- =============================================================
-- TABLA: encargados
-- Almacena los encargados que pueden ser asignados a bodegas.
-- =============================================================
CREATE TABLE encargados (
    id          SERIAL PRIMARY KEY,
    rut_numero  INTEGER      NOT NULL CHECK (rut_numero BETWEEN 1000000 AND 99999999),
    rut_dv      CHAR(1)      NOT NULL CHECK (rut_dv IN ('0','1','2','3','4','5','6','7','8','9','K')),
    nombre      VARCHAR(100) NOT NULL,
    apellido_pat   VARCHAR(100) NOT NULL,
    apellido_mat   VARCHAR(100),
    direccion   TEXT,
    telefono    VARCHAR(20),
    CONSTRAINT uq_encargados_rut UNIQUE (rut_numero, rut_dv)
);

COMMENT ON TABLE  encargados              IS 'Personas que pueden ser encargadas de una o más bodegas';
COMMENT ON COLUMN encargados.rut_numero   IS 'Parte numérica del RUT';
COMMENT ON COLUMN encargados.rut_dv       IS 'Dígito verificador del RUT: 0-9 o K';
COMMENT ON COLUMN encargados.apellido_mat    IS 'Segundo apellido (opcional)';

-- =============================================================
-- TABLA: bodegas
-- Entidad principal del módulo.
-- Estado: TRUE = Activada, FALSE = Desactivada
-- =============================================================
CREATE TABLE bodegas (
    id           SERIAL PRIMARY KEY,
    codigo       VARCHAR(5)   NOT NULL UNIQUE,
    nombre       VARCHAR(100) NOT NULL,
    direccion    TEXT         NOT NULL,
    dotacion     INTEGER      NOT NULL CHECK (dotacion >= 0),
    estado       BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at   TIMESTAMP    NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE  bodegas          IS 'Bodegas de la empresa';
COMMENT ON COLUMN bodegas.codigo   IS 'Código identificador único, alfanumérico, máx 5 caracteres';
COMMENT ON COLUMN bodegas.dotacion IS 'Cantidad de personas que trabajan en la bodega';
COMMENT ON COLUMN bodegas.estado   IS 'TRUE = Activada | FALSE = Desactivada';

-- =============================================================
-- TABLA: bodega_encargado
-- Relación muchos a muchos entre bodegas y encargados.
-- =============================================================
CREATE TABLE bodega_encargado (
    bodega_id    INTEGER NOT NULL REFERENCES bodegas(id)    ON DELETE CASCADE,
    encargado_id INTEGER NOT NULL REFERENCES encargados(id) ON DELETE RESTRICT,
    PRIMARY KEY (bodega_id, encargado_id)
);

COMMENT ON TABLE bodega_encargado IS 'Tabla intermedia relación N:M entre bodegas y encargados';

-- =============================================================
-- ÍNDICES para optimizar consultas frecuentes
-- =============================================================
CREATE INDEX idx_bodegas_estado    ON bodegas(estado);
CREATE INDEX idx_bodegas_codigo    ON bodegas(codigo);
CREATE INDEX idx_be_bodega_id      ON bodega_encargado(bodega_id);
CREATE INDEX idx_be_encargado_id   ON bodega_encargado(encargado_id);

-- =============================================================
-- DATOS DE EJEMPLO: Encargados
-- =============================================================
INSERT INTO encargados (rut_numero, rut_dv, nombre, apellido_pat, apellido_mat, direccion, telefono) VALUES
(12345678, '9', 'Carlos',  'González',  'Muñoz',   'Av. Providencia 1234, Santiago',      '+56912345678'),
(98765432, '1', 'María',   'Rodríguez', 'Pérez',   'Calle Los Olivos 567, Valparaíso',    '+56987654321'),
(11111111, '1', 'Jorge',   'Martínez',  'López',   'Pasaje El Sol 89, Concepción',        '+56911111111'),
(22222222, '2', 'Ana',     'Flores',    'Castro',  'Av. Las Condes 4321, Las Condes',     '+56922222222'),
(33333333, '3', 'Pedro',   'Soto',      'Herrera', 'Calle Maipú 100, Maipú',              '+56933333333');

-- =============================================================
-- DATOS DE EJEMPLO: Bodegas
-- =============================================================
INSERT INTO bodegas (codigo, nombre, direccion, dotacion, estado, created_at) VALUES
('BOD01', 'Bodega Central',      'Av. Américo Vespucio 1000, Pudahuel',    15, TRUE,  NOW() - INTERVAL '30 days'),
('BOD02', 'Bodega Norte',        'Ruta 5 Norte Km 15, Quilicura',          8,  TRUE,  NOW() - INTERVAL '20 days'),
('BOD03', 'Bodega Sur',          'Av. Departamental 3456, San Bernardo',   12, FALSE, NOW() - INTERVAL '15 days'),
('A1B2C', 'Almacén Oriente',     'Camino El Alba 890, La Florida',         6,  TRUE,  NOW() - INTERVAL '10 days'),
('X9Z3K', 'Almacén Poniente',    'Av. Pajaritos 2222, Maipú',              20, TRUE,  NOW() - INTERVAL '5 days');

-- =============================================================
-- DATOS DE EJEMPLO: Asignación encargados a bodegas
-- =============================================================
INSERT INTO bodega_encargado (bodega_id, encargado_id) VALUES
(1, 1),
(1, 2), 
(2, 3),
(3, 2),
(4, 4),
(5, 5),
(5, 1);
