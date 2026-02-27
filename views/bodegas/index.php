<?php

/**
 * views/bodegas/index.php
 * 
 * Vista principal del módulo Mantenedor de Bodegas.
 * Muestra el listado de bodegas con filtro de estado,
 * y contiene los modales de creación y edición embebidos.
 * 
 * Variables disponibles (provistas por el controlador):
 *   $bodegas      array   Listado de bodegas
 *   $encargados   array   Lista de encargados para el select
 *   $filtroEstado string  Estado del filtro activo
 *   $error        string  (opcional) Mensaje de error de conexión
 */

// Contar estadísticas para la barra resumen
$totalBodegas   = count($bodegas);
$totalActivadas = count(array_filter($bodegas, function ($b) {
  return $b['estado'] === true || $b['estado'] === 't';
}));
$totalDesact    = $totalBodegas - $totalActivadas;
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mantenedor de Bodegas — Sistemas Expertos</title>

  <!-- Google Fonts: IBM Plex Sans + IBM Plex Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="public/css/app.css">
</head>

<body>

  <!-- ==================== HEADER ==================== -->
  <header class="app-header">
    <span class="app-header__title">Mantenedor de Bodegas - Sistemas Expertos</span>
    <span class="app-header__subtitle">(Prueba técnica)</span>
  </header>

  <!-- ==================== CONTENIDO PRINCIPAL ==================== -->
  <main class="container">

    <?php if (!empty($error)): ?>
      <div style="background:#fde8e8;border:1px solid #f5c6c6;color:#c0392b;padding:.9rem 1.1rem;border-radius:6px;margin-bottom:1rem;">
        ⚠️ <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- Barra de estadísticas rápidas -->
    <div class="stats-bar">
      <div class="stat-chip">Total: <strong id="stat-total"><?= $totalBodegas ?></strong></div>
      <div class="stat-chip" style="color:var(--success);">Activadas: <strong id="stat-activas"><?= $totalActivadas ?></strong></div>
      <div class="stat-chip" style="color:var(--danger);">Desactivadas: <strong id="stat-desactivas"><?= $totalDesact ?></strong></div>
    </div>

    <!-- Toolbar: título + filtros + botón nueva bodega -->
    <div class="toolbar">
      <div class="toolbar__left">
        <span class="toolbar__title">Bodegas</span>

        <!-- Filtro por estado — manejo DOM con JavaScript -->
        <div class="filter-group" role="group" aria-label="Filtrar por estado">
          <button class="filter-group__btn <?= $filtroEstado === 'ambas'     ? 'active' : '' ?>"
            data-estado="ambas">Todas</button>
          <button class="filter-group__btn <?= $filtroEstado === 'activa'    ? 'active' : '' ?>"
            data-estado="activa">Activadas</button>
          <button class="filter-group__btn <?= $filtroEstado === 'desactiva' ? 'active' : '' ?>"
            data-estado="desactiva">Desactivadas</button>
        </div>
      </div>

      <button class="btn btn-primary" id="btn-nueva-bodega">
        + Nueva Bodega
      </button>
    </div>

    <!-- Tabla de bodegas -->
    <div class="card">
      <div class="table-wrapper">
        <table>
          <colgroup>
            <col class="col-codigo">
            <col class="col-nombre">
            <col class="col-direccion">
            <col class="col-dotacion">
            <col class="col-encargados">
            <col class="col-creacion">
            <col class="col-estado">
            <col class="col-acciones">
          </colgroup>
          <thead>
            <tr>
              <th>Código</th>
              <th>Nombre</th>
              <th>Dirección</th>
              <th>Dotación</th>
              <th>Encargado(s)</th>
              <th>Creación</th>
              <th>Estado</th>
              <th style="text-align:center;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($bodegas)): ?>
              <tr>
                <td colspan="8">
                  <div class="empty-state">
                    <span class="empty-state__icon">🏬</span>
                    <strong>No hay bodegas registradas</strong>
                    <p>Haga clic en "+ Nueva Bodega" para comenzar.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($bodegas as $b): ?>
                <?php
                // PostgreSQL retorna boolean como 't'/'f' o true/false según el driver
                $activa = ($b['estado'] === true || $b['estado'] === 't' || $b['estado'] === '1');
                ?>
                <tr data-id="<?= $b['id'] ?>">
                  <td class="td-code"><?= htmlspecialchars($b['codigo']) ?></td>
                  <td><?= htmlspecialchars($b['nombre']) ?></td>
                  <td><?= htmlspecialchars($b['direccion']) ?></td>
                  <td><?= htmlspecialchars($b['dotacion']) ?></td>
                  <td>
                    <?php if ($b['encargados_nombre']): ?>
                      <?php
                      // Mostrar encargados separados con salto de línea si hay múltiples
                      $enc = explode(' / ', $b['encargados_nombre']);
                      foreach ($enc as $idx => $nombre): ?>
                        <?= htmlspecialchars(trim($nombre)) ?>
                        <?php if ($idx < count($enc) - 1): ?><br><?php endif; ?>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <span style="color:var(--text-muted);">Sin asignar</span>
                    <?php endif; ?>
                  </td>
                  <td class="td-date">
                    <?= date('d/m/Y', strtotime($b['created_at'])) ?>
                    <br>
                    <small><?= date('H:i:s', strtotime($b['created_at'])) ?></small>
                  </td>
                  <td>
                    <?php if ($activa): ?>
                      <span class="badge badge-active">Activada</span>
                    <?php else: ?>
                      <span class="badge badge-inactive">Desactivada</span>
                    <?php endif; ?>
                  </td>
                  <td class="td-actions">
                    <!-- Botón Editar -->
                    <button class="btn-icon btn-icon--edit btn-editar"
                      data-id="<?= $b['id'] ?>"
                      title="Editar bodega">
                      ✏️
                    </button>
                    <!-- Botón Eliminar -->
                    <button class="btn-icon btn-icon--delete btn-eliminar"
                      data-id="<?= $b['id'] ?>"
                      data-nombre="<?= htmlspecialchars($b['nombre'], ENT_QUOTES) ?>"
                      title="Eliminar bodega">
                      🗑️
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>

  <!-- ==================== MODAL CREAR BODEGA ==================== -->
  <div class="modal-backdrop" id="modal-crear" role="dialog" aria-modal="true" aria-labelledby="modal-crear-title">
    <div class="modal">
      <div class="modal__header">
        <h2 id="modal-crear-title">Ingresar Bodega</h2>
        <button class="modal__close" aria-label="Cerrar">✕</button>
      </div>

      <form id="form-crear" novalidate>
        <div class="modal__body">
          <div class="form-grid">

            <!-- Código -->
            <div class="form-group" data-field="codigo">
              <label for="crear-codigo">Código <span class="req">*</span></label>
              <input type="text"
                id="crear-codigo"
                name="codigo"
                class="form-control"
                maxlength="5"
                placeholder="Ej: BOD01"
                autocomplete="off">
              <span class="form-hint">Alfanumérico, máximo 5 caracteres.</span>
              <span class="form-error"></span>
            </div>

            <!-- Dotación -->
            <div class="form-group" data-field="dotacion">
              <label for="crear-dotacion">Dotación <span class="req">*</span></label>
              <input type="number"
                id="crear-dotacion"
                name="dotacion"
                class="form-control"
                min="0"
                placeholder="0"
                max="9999">
              <span class="form-error"></span>
            </div>

            <!-- Nombre -->
            <div class="form-group full" data-field="nombre">
              <label for="crear-nombre">Nombre <span class="req">*</span></label>
              <input type="text"
                id="crear-nombre"
                name="nombre"
                class="form-control"
                maxlength="100"
                placeholder="Nombre de la bodega">
              <span class="form-error"></span>
            </div>

            <!-- Dirección -->
            <div class="form-group full" data-field="direccion">
              <label for="crear-direccion">Dirección <span class="req">*</span></label>
              <input type="text"
                id="crear-direccion"
                name="direccion"
                class="form-control"
                placeholder="Av. Ejemplo 1234, Ciudad">
              <span class="form-error"></span>
            </div>

            <!-- Encargados -->
            <div class="form-group full" data-field="encargados">
              <label for="crear-encargados">Encargado(s) <span class="req">*</span></label>
              <select id="crear-encargados"
                name="encargados[]"
                class="form-control"
                multiple
                size="5">
                <?php foreach ($encargados as $enc): ?>
                  <option value="<?= $enc['id'] ?>">
                    <?= htmlspecialchars($enc['apellido_pat'] . ' ' . ($enc['apellido_mat'] ?? '') . ', ' . $enc['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <span class="form-hint">Mantenga Ctrl (o Cmd en Mac) para seleccionar múltiples encargados.</span>
              <span class="form-error"></span>
            </div>

            <!-- Estado -->
            <div class="form-group full" data-field="estado">
              <label>Estado</label>
              <div class="toggle-wrapper">
                <label class="toggle">
                  <input type="checkbox" name="estado" value="1" checked>
                  <span class="toggle__slider"></span>
                </label>
                <span class="toggle-label"></span>
              </div>
            </div>

          </div>
        </div>

        <div class="modal__footer">
          <button type="button" class="btn btn-ghost" data-dismiss>Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ==================== MODAL EDITAR BODEGA ==================== -->
  <div class="modal-backdrop" id="modal-editar" role="dialog" aria-modal="true" aria-labelledby="modal-editar-title">
    <div class="modal">
      <div class="modal__header">
        <h2 id="modal-editar-title">
          Editar Bodega — <code id="edit-codigo-display"></code>
        </h2>
        <button class="modal__close" aria-label="Cerrar">✕</button>
      </div>

      <form id="form-editar" novalidate>
        <!-- Campo oculto con el ID de la bodega a editar -->
        <input type="hidden" name="id">

        <div class="modal__body">
          <div class="form-grid">

            <!-- Dotación -->
            <div class="form-group" data-field="dotacion">
              <label for="editar-dotacion">Dotación <span class="req">*</span></label>
              <input type="number"
                id="editar-dotacion"
                name="dotacion"
                class="form-control"
                min="0">
              <span class="form-error"></span>
            </div>

            <!-- Estado -->
            <div class="form-group" data-field="estado" style="justify-content:flex-end;">
              <label>Estado</label>
              <div class="toggle-wrapper">
                <label class="toggle">
                  <input type="checkbox" name="estado" value="1">
                  <span class="toggle__slider"></span>
                </label>
                <span class="toggle-label"></span>
              </div>
            </div>

            <!-- Nombre -->
            <div class="form-group full" data-field="nombre">
              <label for="editar-nombre">Nombre <span class="req">*</span></label>
              <input type="text"
                id="editar-nombre"
                name="nombre"
                class="form-control"
                maxlength="100">
              <span class="form-error"></span>
            </div>

            <!-- Dirección -->
            <div class="form-group full" data-field="direccion">
              <label for="editar-direccion">Dirección <span class="req">*</span></label>
              <input type="text"
                id="editar-direccion"
                name="direccion"
                class="form-control">
              <span class="form-error"></span>
            </div>

            <!-- Encargados -->
            <div class="form-group full" data-field="encargados">
              <label for="editar-encargados">Encargado(s) <span class="req">*</span></label>
              <select id="editar-encargados"
                name="encargados[]"
                class="form-control"
                multiple
                size="5">
                <?php foreach ($encargados as $enc): ?>
                  <option value="<?= $enc['id'] ?>">
                    <?= htmlspecialchars($enc['apellido_pat'] . ' ' . ($enc['apellido_mat'] ?? '') . ', ' . $enc['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <span class="form-hint">Mantenga Ctrl (o Cmd en Mac) para seleccionar múltiples encargados.</span>
              <span class="form-error"></span>
            </div>

          </div>
        </div>

        <div class="modal__footer">
          <button type="button" class="btn btn-ghost" data-dismiss>Cancelar</button>
          <button type="submit" class="btn btn-primary">Actualizar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ==================== DIÁLOGO CONFIRMACIÓN ELIMINAR ==================== -->
  <div class="confirm-dialog" id="confirm-dialog" role="alertdialog" aria-modal="true">
    <div class="confirm-dialog__box">
      <span class="confirm-dialog__icon"></span>
      <div class="confirm-dialog__title">Eliminar Bodega</div>
      <div class="confirm-dialog__msg">
        Esta acción no se puede deshacer.
      </div>
      <div class="confirm-dialog__actions">
        <button class="btn btn-ghost" id="confirm-cancelar">Cancelar</button>
        <button class="btn btn-danger" id="confirm-eliminar">Sí, eliminar</button>
      </div>
    </div>
  </div>

  <!-- ==================== TOAST NOTIFICATIONS ==================== -->
  <div class="toast-container" id="toast-container" aria-live="polite"></div>

  <script src="public/js/app.js"></script>
</body>

</html>