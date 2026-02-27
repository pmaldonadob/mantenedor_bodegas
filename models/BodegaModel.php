<?php
require_once __DIR__ . '/../config/database.php';

class BodegaModel
{
    private $db; // PDO

    public function __construct()
    {
        $this->db = getDB();
    }

    // =========================================================
    // LISTADO
    // =========================================================

    /**
     * Obtiene todas las bodegas con sus encargados concatenados.
     * Se usa STRING_AGG para mostrar múltiples encargados en una sola fila.
     *
     * @param string $filtroEstado 'activa' | 'desactiva' | 'ambas'
     * @return array
     */
    public function listar(string $filtroEstado = 'ambas'): array
    {
        $whereClause = '';
        $params      = [];

        if ($filtroEstado === 'activa') {
            $whereClause   = 'WHERE b.estado = TRUE';
        } elseif ($filtroEstado === 'desactiva') {
            $whereClause   = 'WHERE b.estado = FALSE';
        }

        $sql = "
            SELECT
                b.id,
                b.codigo,
                b.nombre,
                b.direccion,
                b.dotacion,
                b.estado,
                b.created_at,
                -- Concatena nombre completo + RUT formateado de todos los encargados
                STRING_AGG(
                    e.nombre || ' ' || e.apellido_pat || COALESCE(' ' || e.apellido_mat, ''),
                    ' / '
                    ORDER BY e.apellido_pat
                ) AS encargados_nombre
            FROM bodegas b
            LEFT JOIN bodega_encargado be ON be.bodega_id = b.id
            LEFT JOIN encargados e        ON e.id = be.encargado_id
            {$whereClause}
            GROUP BY b.id, b.codigo, b.nombre, b.direccion, b.dotacion, b.estado, b.created_at
            ORDER BY b.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    // =========================================================
    // OBTENER UNA BODEGA
    // =========================================================

    /**
     * Obtiene una bodega por su ID, incluyendo los IDs de encargados asignados.
     *
     * @param int $id
     * @return array|false
     */
    public function obtenerPorId(int $id)
    {
        $stmt = $this->db->prepare('
            SELECT id, codigo, nombre, direccion, dotacion, estado, created_at
            FROM bodegas
            WHERE id = :id
        ');
        $stmt->execute([':id' => $id]);
        $bodega = $stmt->fetch();

        if (!$bodega) {
            return false;
        }

        // IDs de encargados asignados a esta bodega
        $stmt2 = $this->db->prepare('
            SELECT encargado_id
            FROM bodega_encargado
            WHERE bodega_id = :bodega_id
        ');
        $stmt2->execute([':bodega_id' => $id]);
        $bodega['encargados_ids'] = $stmt2->fetchAll(PDO::FETCH_COLUMN);

        return $bodega;
    }

    // =========================================================
    // CREAR
    // =========================================================

    /**
     * Crea una nueva bodega y asigna sus encargados
     *
     * @param array $datos  Datos del formulario ya validados
     * @param array $encargadosIds  IDs de encargados a asignar
     * @return int  ID de la bodega creada
     * @throws Exception
     */
    public function crear(array $datos, array $encargadosIds): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare('
                INSERT INTO bodegas (codigo, nombre, direccion, dotacion, estado, created_at)
                VALUES (:codigo, :nombre, :direccion, :dotacion, :estado, NOW())
                RETURNING id
            ');
            $stmt->execute([
                ':codigo'    => strtoupper(trim($datos['codigo'])),
                ':nombre'    => trim($datos['nombre']),
                ':direccion' => trim($datos['direccion']),
                ':dotacion'  => (int) $datos['dotacion'],
                ':estado'    => isset($datos['estado']) ? 'true' : 'false',
            ]);

            $bodegaId = (int) $stmt->fetchColumn();

            $this->asignarEncargados($bodegaId, $encargadosIds);

            $this->db->commit();
            return $bodegaId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // =========================================================
    // EDITAR
    // =========================================================

    /**
     * Actualiza los datos de una bodega y reemplaza sus encargados
     *
     * @param int   $id
     * @param array $datos
     * @param array $encargadosIds
     * @throws Exception
     */
    public function editar(int $id, array $datos, array $encargadosIds): void
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare('
                UPDATE bodegas
                SET nombre    = :nombre,
                    direccion = :direccion,
                    dotacion  = :dotacion,
                    estado    = :estado
                WHERE id = :id
            ');
            $stmt->execute([
                ':nombre'    => trim($datos['nombre']),
                ':direccion' => trim($datos['direccion']),
                ':dotacion'  => (int) $datos['dotacion'],
                ':estado'    => isset($datos['estado']) ? 'true' : 'false',
                ':id'        => $id,
            ]);

            $this->db->prepare('DELETE FROM bodega_encargado WHERE bodega_id = :id')
                ->execute([':id' => $id]);

            $this->asignarEncargados($id, $encargadosIds);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // =========================================================
    // ELIMINAR
    // =========================================================

    /**
     * Elimina una bodega
     *
     * @param int $id
     * @throws Exception
     */
    public function eliminar(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM bodegas WHERE id = :id');
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Bodega no encontrada.');
        }
    }

    // =========================================================
    // ENCARGADOS (auxiliar)
    // =========================================================

    /**
     * Retorna todos los encargados disponibles en el sistema.
     *
     * @return array
     */
    public function listarEncargados(): array
    {
        $stmt = $this->db->query('
            SELECT
                id,
                rut_numero,
                rut_dv,
                -- RUT formateado para mostrarlo en la vista (ej: 12345678-9)
                rut_numero::TEXT || \'-\' || rut_dv AS rut_formato,
                nombre,
                apellido_pat,
                apellido_mat
            FROM encargados
            ORDER BY apellido_pat, apellido_mat, nombre
        ');
        return $stmt->fetchAll();
    }

    /**
     * Verifica si un código de bodega ya existe en la BD.
     *
     * @param string $codigo
     * @param int    $excluirId  ID a ignorar (útil al editar)
     * @return bool
     */
    public function codigoExiste(string $codigo, int $excluirId = 0): bool
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) FROM bodegas
            WHERE codigo = :codigo AND id != :id
        ');
        $stmt->execute([
            ':codigo' => strtoupper(trim($codigo)),
            ':id'     => $excluirId,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // =========================================================
    // PRIVADOS
    // =========================================================

    /**
     * Inserta las relaciones bodega-encargado para un conjunto de IDs.
     *
     * @param int   $bodegaId
     * @param array $encargadosIds
     */
    private function asignarEncargados(int $bodegaId, array $encargadosIds): void
    {
        if (empty($encargadosIds)) {
            return;
        }

        $stmt = $this->db->prepare('
            INSERT INTO bodega_encargado (bodega_id, encargado_id)
            VALUES (:bodega_id, :encargado_id)
        ');

        foreach ($encargadosIds as $encargadoId) {
            $stmt->execute([
                ':bodega_id'    => $bodegaId,
                ':encargado_id' => (int) $encargadoId,
            ]);
        }
    }
}
