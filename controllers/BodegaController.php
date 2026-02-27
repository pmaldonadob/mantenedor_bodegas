<?php
require_once __DIR__ . '/../models/BodegaModel.php';

class BodegaController
{
    private $model;

    public function __construct()
    {
        $this->model = new BodegaModel();
    }

    // =========================================================
    // LISTADO (GET principal)
    // =========================================================

    public function index(): void
    {
        $filtroEstado = $_GET['estado'] ?? 'ambas';

        // Sanitizar el valor del filtro para evitar valores inesperados
        if (!in_array($filtroEstado, ['activa', 'desactiva', 'ambas'], true)) {
            $filtroEstado = 'ambas';
        }

        try {
            $bodegas    = $this->model->listar($filtroEstado);
            $encargados = $this->model->listarEncargados();
        } catch (Exception $e) {
            $error = 'Error al obtener bodegas: ' . $e->getMessage();
            $bodegas    = [];
            $encargados = [];
        }

        require __DIR__ . '/../views/bodegas/index.php';
    }

    // =========================================================
    // CREAR (POST)
    // =========================================================

    public function crear(): void
    {
        header('Content-Type: application/json');

        $errores = $this->validarDatos($_POST);

        if (!empty($errores)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'errores' => $errores]);
            return;
        }

        // Verificar unicidad del código
        if ($this->model->codigoExiste($_POST['codigo'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'errores' => ['codigo' => 'El código ya está en uso.']]);
            return;
        }

        // Los encargados son obligatorios, debe asignarse al menos uno
        $encargadosIds = $this->obtenerEncargadosPost();
        if (empty($encargadosIds)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'errores' => ['encargados' => 'Debe seleccionar al menos un encargado.']]);
            return;
        }

        try {
            $id = $this->model->crear($_POST, $encargadosIds);
            echo json_encode(['success' => true, 'message' => 'Bodega creada exitosamente.', 'id' => $id]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'errores' => ['general' => 'Error al crear la bodega: ' . $e->getMessage()]]);
        }
    }

    // =========================================================
    // OBTENER BODEGA PARA EDITAR (GET AJAX)
    // =========================================================

    public function obtener(): void
    {
        header('Content-Type: application/json');

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            return;
        }

        try {
            $bodega = $this->model->obtenerPorId($id);

            if (!$bodega) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Bodega no encontrada.']);
                return;
            }

            echo json_encode(['success' => true, 'bodega' => $bodega]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // =========================================================
    // EDITAR (POST)
    // =========================================================

    public function editar(): void
    {
        header('Content-Type: application/json');

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            return;
        }

        $errores = $this->validarDatos($_POST, true);

        if (!empty($errores)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'errores' => $errores]);
            return;
        }

        $encargadosIds = $this->obtenerEncargadosPost();
        if (empty($encargadosIds)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'errores' => ['encargados' => 'Debe seleccionar al menos un encargado.']]);
            return;
        }

        try {
            $this->model->editar($id, $_POST, $encargadosIds);
            echo json_encode(['success' => true, 'message' => 'Bodega actualizada exitosamente.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'errores' => ['general' => 'Error al actualizar: ' . $e->getMessage()]]);
        }
    }

    // =========================================================
    // ELIMINAR (POST)
    // =========================================================

    public function eliminar(): void
    {
        header('Content-Type: application/json');

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            return;
        }

        try {
            $this->model->eliminar($id);
            echo json_encode(['success' => true, 'message' => 'Bodega eliminada correctamente.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()]);
        }
    }

    // =========================================================
    // VALIDACIONES PRIVADAS
    // =========================================================

    private function validarDatos(array $datos, bool $excluirCodigo = false): array
    {
        $errores = [];

        if (!$excluirCodigo) {
            $codigo = trim($datos['codigo'] ?? '');
            if ($codigo === '') {
                $errores['codigo'] = 'El código es obligatorio.';
            } elseif (!preg_match('/^[A-Za-z0-9]{1,5}$/', $codigo)) {
                $errores['codigo'] = 'El código debe ser alfanumérico y tener máximo 5 caracteres.';
            }
        }

        $nombre = trim($datos['nombre'] ?? '');
        if ($nombre === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) > 100) {
            $errores['nombre'] = 'El nombre no puede superar los 100 caracteres.';
        } elseif (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9\s\-\.]+$/u', $nombre)) {
            $errores['nombre'] = 'El nombre solo puede contener letras, números, espacios, guiones y puntos.';
        }

        $direccion = trim($datos['direccion'] ?? '');
        if ($direccion === '') {
            $errores['direccion'] = 'La dirección es obligatoria.';
        }

        $dotacion = $datos['dotacion'] ?? '';
        if ($dotacion === '' || !ctype_digit((string) $dotacion)) {
            $errores['dotacion'] = 'La dotación debe ser un número entero positivo.';
        } elseif ((int) $dotacion <= 0) {
            $errores['dotacion'] = 'La dotación debe ser mayor a cero.';
        } elseif ((int) $dotacion > 9999) {
            $errores['dotacion'] = 'La dotación no puede superar 9.999 personas.';
        }

        return $errores;
    }

    private function obtenerEncargadosPost(): array
    {
        $raw = $_POST['encargados'] ?? [];

        if (!is_array($raw)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $raw), function ($v) {
            return $v > 0;
        }));
    }
}
