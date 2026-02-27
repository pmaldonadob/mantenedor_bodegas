"use strict";

// =====================================================================
// UTILIDADES
// =====================================================================

/**
 * Muestra una notificación toast en la esquina superior derecha.
 * @param {string} mensaje
 * @param {'success'|'error'} tipo
 * @param {number} duracion  milisegundos
 */
function mostrarToast(mensaje, tipo = "success", duracion = 3500) {
	const container = document.getElementById("toast-container");
	const icon = tipo === "success" ? "✓" : "✕";

	const toast = document.createElement("div");
	toast.className = `toast toast--${tipo}`;
	toast.innerHTML = `<span>${icon}</span> <span>${mensaje}</span>`;

	container.appendChild(toast);

	setTimeout(() => {
		toast.style.opacity = "0";
		toast.style.transform = "translateX(20px)";
		toast.style.transition = "opacity .3s, transform .3s";
		setTimeout(() => toast.remove(), 320);
	}, duracion);
}

/**
 * Muestra un error de campo en el formulario.
 * @param {HTMLElement} form
 * @param {string} campo    Nombre del campo
 * @param {string} mensaje
 */
function mostrarErrorCampo(form, campo, mensaje) {
	const group = form.querySelector(`[data-field="${campo}"]`);
	if (!group) return;
	group.classList.add("has-error");
	const errEl = group.querySelector(".form-error");
	if (errEl) errEl.textContent = mensaje;
	const input = group.querySelector(".form-control");
	if (input) input.classList.add("is-invalid");
}

/**
 * Limpia todos los errores de un formulario.
 * @param {HTMLElement} form
 */
function limpiarErrores(form) {
	form.querySelectorAll(".form-group").forEach(g => {
		g.classList.remove("has-error");
		const errEl = g.querySelector(".form-error");
		if (errEl) errEl.textContent = "";
		const ctrl = g.querySelector(".form-control");
		if (ctrl) ctrl.classList.remove("is-invalid");
	});
}

/**
 * Valida el formulario del lado cliente antes de enviar.
 * Retorna true si es válido, false si hay errores.
 * @param {HTMLElement} form
 * @param {boolean} esCrea  Si true, valida también el campo 'codigo'
 * @returns {boolean}
 */
function validarFormularioCliente(form, esCrea = true) {
	limpiarErrores(form);
	let valido = true;

	const getValue = name => {
		const el = form.querySelector(`[name="${name}"]`);
		return el ? el.value.trim() : "";
	};

	// Código (solo en creación)
	if (esCrea) {
		const codigo = getValue("codigo");
		if (!codigo) {
			mostrarErrorCampo(form, "codigo", "El código es obligatorio.");
			valido = false;
		} else if (!/^[A-Za-z0-9]{1,5}$/.test(codigo)) {
			mostrarErrorCampo(form, "codigo", "Máximo 5 caracteres alfanuméricos.");
			valido = false;
		}
	}

	// Nombre
	const nombre = getValue("nombre");
	if (!nombre) {
		mostrarErrorCampo(form, "nombre", "El nombre es obligatorio.");
		valido = false;
	} else if (nombre.length > 100) {
		mostrarErrorCampo(form, "nombre", "Máximo 100 caracteres.");
		valido = false;
	}

	// Dirección
	if (!getValue("direccion")) {
		mostrarErrorCampo(form, "direccion", "La dirección es obligatoria.");
		valido = false;
	}

	// Dotación
	const dotacion = getValue("dotacion");
	if (dotacion === "" || isNaN(dotacion) || !Number.isInteger(Number(dotacion)) || Number(dotacion) < 0) {
		mostrarErrorCampo(form, "dotacion", "Ingrese un número entero positivo.");
		valido = false;
	} else if (Number(dotacion) > 9999) {
		mostrarErrorCampo(form, "dotacion", "La dotación no puede superar 9.999 personas.");
		valido = false;
	}

	// Encargados (al menos uno seleccionado)
	const encargadosSelect = form.querySelector('[name="encargados[]"]');
	const seleccionados = encargadosSelect ? Array.from(encargadosSelect.selectedOptions) : [];

	if (seleccionados.length === 0) {
		mostrarErrorCampo(form, "encargados", "Seleccione al menos un encargado.");
		valido = false;
	}

	return valido;
}

/**
 * Envía un formulario por AJAX (fetch) y retorna la respuesta JSON.
 * @param {string}      url
 * @param {FormData}    formData
 * @returns {Promise<Object>}
 */
async function enviarFormulario(url, formData) {
	const resp = await fetch(url, {
		method: "POST",
		body: formData,
	});
	return resp.json();
}

// =====================================================================
// FILTRO DE ESTADO
// =====================================================================

/**
 * Inicializa los botones de filtro de estado.
 */
function initFiltros() {
	const btns = document.querySelectorAll(".filter-group__btn");

	btns.forEach(btn => {
		btn.addEventListener("click", () => {
			const estado = btn.dataset.estado;
			// Construir URL con el filtro seleccionado
			const url = new URL(window.location.href);
			url.searchParams.set("estado", estado);
			window.location.href = url.toString();
		});
	});
}

// =====================================================================
// MODAL CREAR BODEGA
// =====================================================================

function initModalCrear() {
	const backdrop = document.getElementById("modal-crear");
	const form = document.getElementById("form-crear");
	const btnAbrir = document.getElementById("btn-nueva-bodega");
	const btnCerrar = backdrop.querySelector(".modal__close");
	const btnCancelar = backdrop.querySelector("[data-dismiss]");

	const abrir = () => backdrop.classList.add("open");
	const cerrar = () => {
		backdrop.classList.remove("open");
		form.reset();
		limpiarErrores(form);
	};

	btnAbrir.addEventListener("click", abrir);
	btnCerrar.addEventListener("click", cerrar);
	btnCancelar.addEventListener("click", cerrar);

	form.addEventListener("submit", async e => {
		e.preventDefault();

		if (!validarFormularioCliente(form, true)) return;

		const submitBtn = form.querySelector('[type="submit"]');
		submitBtn.disabled = true;
		submitBtn.textContent = "Guardando…";

		try {
			const resp = await enviarFormulario("?action=crear", new FormData(form));

			if (resp.success) {
				mostrarToast(resp.message, "success");
				cerrar();
				setTimeout(() => window.location.reload(), 500);
			} else {
				if (resp.errores) {
					Object.entries(resp.errores).forEach(([campo, msg]) => {
						mostrarErrorCampo(form, campo, msg);
					});
				}
			}
		} catch (err) {
			mostrarToast("Error de conexión con el servidor.", "error");
		} finally {
			submitBtn.disabled = false;
			submitBtn.textContent = "Guardar";
		}
	});
}

// =====================================================================
// MODAL EDITAR BODEGA
// =====================================================================

function initModalEditar() {
	const backdrop = document.getElementById("modal-editar");
	const form = document.getElementById("form-editar");
	const btnCerrar = backdrop.querySelector(".modal__close");
	const btnCancelar = backdrop.querySelector("[data-dismiss]");

	const cerrar = () => {
		backdrop.classList.remove("open");
		form.reset();
		limpiarErrores(form);
	};

	btnCerrar.addEventListener("click", cerrar);
	btnCancelar.addEventListener("click", cerrar);

	document.querySelectorAll(".btn-editar").forEach(btn => {
		btn.addEventListener("click", async () => {
			const id = btn.dataset.id;

			backdrop.classList.add("open");

			try {
				const resp = await fetch(`?action=obtener&id=${id}`);
				const data = await resp.json();

				if (!data.success) {
					mostrarToast(data.message || "Error al cargar la bodega.", "error");
					cerrar();
					return;
				}

				const b = data.bodega;

				form.querySelector('[name="id"]').value = b.id;
				form.querySelector('[name="nombre"]').value = b.nombre;
				form.querySelector('[name="direccion"]').value = b.direccion;
				form.querySelector('[name="dotacion"]').value = b.dotacion;

				const select = form.querySelector('[name="encargados[]"]');
				Array.from(select.options).forEach(opt => {
					opt.selected = b.encargados_ids.includes(parseInt(opt.value));
				});

				const toggleEstado = form.querySelector('[name="estado"]');
				toggleEstado.checked = b.estado === true || b.estado === "t" || b.estado === "1";
				toggleEstado.dispatchEvent(new Event("change"));

				const codigoBadge = backdrop.querySelector("#edit-codigo-display");
				if (codigoBadge) codigoBadge.textContent = b.codigo;

				limpiarErrores(form);
			} catch (err) {
				mostrarToast("Error al obtener datos de la bodega.", "error");
				cerrar();
			}
		});
	});

	form.addEventListener("submit", async e => {
		e.preventDefault();

		if (!validarFormularioCliente(form, false)) return;

		const submitBtn = form.querySelector('[type="submit"]');
		submitBtn.disabled = true;
		submitBtn.textContent = "Actualizando…";

		try {
			const resp = await enviarFormulario("?action=editar", new FormData(form));

			if (resp.success) {
				mostrarToast(resp.message, "success");
				cerrar();
				setTimeout(() => window.location.reload(), 500);
			} else {
				if (resp.errores) {
					Object.entries(resp.errores).forEach(([campo, msg]) => {
						mostrarErrorCampo(form, campo, msg);
					});
				}
			}
		} catch (err) {
			mostrarToast("Error de conexión con el servidor.", "error");
		} finally {
			submitBtn.disabled = false;
			submitBtn.textContent = "Actualizar";
		}
	});
}

// =====================================================================
// CONFIRMACIÓN Y ELIMINACIÓN
// =====================================================================

function initEliminar() {
	const dialog = document.getElementById("confirm-dialog");
	const btnConfirmar = document.getElementById("confirm-eliminar");
	const btnCancelar = document.getElementById("confirm-cancelar");
	let bodegaIdActual = null;

	document.querySelectorAll(".btn-eliminar").forEach(btn => {
		btn.addEventListener("click", () => {
			bodegaIdActual = btn.dataset.id;
			const nombre = btn.dataset.nombre;

			const msgEl = dialog.querySelector(".confirm-dialog__msg");
			msgEl.innerHTML = `Se eliminará permanentemente la bodega <strong>"${nombre}"</strong>.
                         <br><br>Esta acción <strong>no se puede deshacer</strong>.`;

			dialog.classList.add("open");
		});
	});

	btnCancelar.addEventListener("click", () => {
		dialog.classList.remove("open");
		bodegaIdActual = null;
	});

	btnConfirmar.addEventListener("click", async () => {
		if (!bodegaIdActual) return;

		btnConfirmar.disabled = true;
		btnConfirmar.textContent = "Eliminando…";

		try {
			const formData = new FormData();
			formData.append("id", bodegaIdActual);

			const resp = await enviarFormulario("?action=eliminar", formData);

			if (resp.success) {
				dialog.classList.remove("open");
				mostrarToast(resp.message, "success");
				const fila = document.querySelector(`tr[data-id="${bodegaIdActual}"]`);
				if (fila) {
					fila.style.transition = "opacity .3s";
					fila.style.opacity = "0";
					setTimeout(() => {
						fila.remove();
						recalcularTotales();
						const tbody = document.querySelector("tbody");
						if (tbody && !tbody.querySelector("tr[data-id]")) {
							window.location.reload();
						}
					}, 320);
				}
			} else {
				mostrarToast(resp.message || "Error al eliminar.", "error");
				dialog.classList.remove("open");
			}
		} catch (err) {
			mostrarToast("Error de conexión con el servidor.", "error");
			dialog.classList.remove("open");
		} finally {
			btnConfirmar.disabled = false;
			btnConfirmar.textContent = "Sí, eliminar";
			bodegaIdActual = null;
		}
	});
}

// =====================================================================
// HELPERS DE FORMULARIO
// =====================================================================

/**
 * Pone en mayúsculas el campo código mientras se escribe.
 */
function initCodigoUppercase() {
	document.querySelectorAll('[name="codigo"]').forEach(input => {
		input.addEventListener("input", () => {
			const pos = input.selectionStart;
			input.value = input.value.toUpperCase();
			input.setSelectionRange(pos, pos);
		});
	});
}

/**
 * Muestra ayuda dinámica del estado del toggle.
 */
function initToggleLabels() {
	document.querySelectorAll('.toggle input[name="estado"]').forEach(toggle => {
		const label = toggle.closest(".toggle-wrapper").querySelector(".toggle-label");
		const update = () => {
			label.textContent = toggle.checked ? "Activada" : "Desactivada";
			label.style.color = toggle.checked ? "var(--success)" : "var(--danger)";
		};
		toggle.addEventListener("change", update);
		update(); // Estado inicial
	});
}

// =====================================================================
// TOTALES
// =====================================================================

/**
 * Recalcula y actualiza los totales de estadísticas
 */
function recalcularTotales() {
	const filas = document.querySelectorAll("tbody tr[data-id]");
	const total = filas.length;
	let activas = 0;

	filas.forEach(function (fila) {
		if (fila.querySelector(".badge-active")) activas++;
	});

	document.getElementById("stat-total").textContent = total;
	document.getElementById("stat-activas").textContent = activas;
	document.getElementById("stat-desactivas").textContent = total - activas;
}
document.addEventListener("DOMContentLoaded", () => {
	initFiltros();
	initModalCrear();
	initModalEditar();
	initEliminar();
	initCodigoUppercase();
	initToggleLabels();
});
