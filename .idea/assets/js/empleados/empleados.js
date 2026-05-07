let empleados = [];

function alternarCamposSalida() {
  const estado = document.getElementById("estadoLaboral").value;
  const mostrar = estado === "Inactivo";
  const grupoFecha = document.getElementById("grupoFechaSalida");
  const grupoMotivo = document.getElementById("grupoMotivoSalida");
  const inputFecha = document.getElementById("fechaSalida");
  const inputMotivo = document.getElementById("motivoSalida");

  grupoFecha.style.display = mostrar ? "flex" : "none";
  grupoMotivo.style.display = mostrar ? "flex" : "none";
  inputFecha.required = mostrar;
  inputMotivo.required = mostrar;

  if (!mostrar) {
    inputFecha.value = "";
    inputMotivo.value = "";
  }
}

async function cargarEmpleados() {
  try {
    const respuesta = await fetch("php/obtener_empleados.php", { cache: "no-store" });
    const datos = await respuesta.json();
    empleados = datos.empleados || [];
    renderEmpleados();
    actualizarKPIs();
  } catch (error) {
    console.error(error);
    mostrarMensaje("No se pudieron cargar los empleados. Verifica que el servidor local est\u00e9 activo.", "error");
  }
}

async function guardarEmpleado(formData) {
  const respuesta = await fetch("php/guardar_empleado.php", {
    method: "POST",
    body: formData
  });
  const datos = await respuesta.json();
  if (!respuesta.ok || datos.ok === false) {
    throw new Error(datos.mensaje || "No se pudo guardar el empleado");
  }
  return datos;
}

function obtenerClaseEstadoLaboral(estado) {
  if (estado === "Activo") return "badge badge-success";
  if (estado === "Vacaciones") return "badge badge-info";
  return "badge badge-danger";
}

function inicialesEmpleado(empleado) {
  return `${empleado.nombre?.[0] || ""}${empleado.apellido?.[0] || ""}`.toUpperCase() || "EL";
}

function renderFotoEmpleado(empleado) {
  const iniciales = inicialesEmpleado(empleado);
  if (!empleado.foto) {
    return `<div class="empleado-avatar-fallback">${iniciales}</div>`;
  }

  return `<img class="empleado-foto" src="${empleado.foto}" alt="Foto de ${empleado.nombreCompleto}" onerror="this.replaceWith(Object.assign(document.createElement('div'), {className:'empleado-avatar-fallback', textContent:'${iniciales}'}))">`;
}

function renderEmpleados() {
  const tbody = document.getElementById("tablaEmpleados");
  const texto = document.getElementById("buscador").value.trim().toLowerCase();
  const estado = document.getElementById("filtroEstado").value;

  const lista = empleados.filter(empleado => {
    const coincideTexto = empleado.nombreCompleto.toLowerCase().includes(texto);
    const coincideEstado = estado === "" || empleado.estadoLaboral === estado;
    return coincideTexto && coincideEstado;
  });

  if (!lista.length) {
    tbody.innerHTML = `<tr><td colspan="7">No hay empleados para mostrar.</td></tr>`;
    return;
  }

  tbody.innerHTML = lista.map(empleado => {
    const stats = empleado.estadisticas || {};
    return `
      <tr>
        <td>
          <div class="empleado-cell">
            ${renderFotoEmpleado(empleado)}
            <div>
              <strong>${empleado.nombreCompleto}</strong>
              <span>${empleado.codigo}</span>
              <small>${empleado.direccion}</small>
            </div>
          </div>
        </td>
        <td>${empleado.puesto}</td>
        <td>${empleado.telefono}</td>
        <td>${empleado.fechaIngreso}</td>
        <td><span class="${obtenerClaseEstadoLaboral(empleado.estadoLaboral)}">${empleado.estadoLaboral}</span></td>
        <td>
          <strong>${stats.cumplimiento || 0}%</strong>
          <small class="stat-mini">${stats.registradas || 0}/${stats.asignadas || 0} tareas</small>
        </td>
        <td>
          <button class="acciones-btn btn-info-lite" onclick="verEstadisticas(${empleado.id})">Estadísticas</button>
          <button class="acciones-btn btn-editar" onclick="editarEmpleado(${empleado.id})">Editar</button>
        </td>
      </tr>
    `;
  }).join("");
}

function actualizarKPIs() {
  const activos = empleados.filter(e => e.estadoLaboral === "Activo").length;
  const mejor = empleados.reduce((max, empleado) => Math.max(max, empleado.estadisticas?.cumplimiento || 0), 0);

  document.getElementById("kpiTotal").textContent = empleados.length;
  document.getElementById("kpiActivos").textContent = activos;
  document.getElementById("kpiMejor").textContent = `${mejor}%`;
}

function verEstadisticas(id) {
  const empleado = empleados.find(item => item.id === id);
  if (!empleado) return;

  const stats = empleado.estadisticas || {};
  document.getElementById("estadisticasFoto").innerHTML = renderFotoEmpleado(empleado);
  document.getElementById("estadisticasNombre").textContent = empleado.nombreCompleto;
  document.getElementById("estadisticasCodigo").textContent = empleado.codigo;
  document.getElementById("estadisticasPuesto").textContent = empleado.puesto;
  document.getElementById("estadisticasTelefono").textContent = empleado.telefono || "-";
  document.getElementById("estadisticasDireccion").textContent = empleado.direccion || "-";
  document.getElementById("estadisticasIngreso").textContent = empleado.fechaIngreso || "-";
  document.getElementById("estadisticasEstado").textContent = empleado.estadoLaboral || "-";
  document.getElementById("estadisticasSalida").textContent = empleado.fechaSalida || "-";
  document.getElementById("estadisticasMotivo").textContent = empleado.motivoSalida || "-";
  document.getElementById("estadisticasSalidaFila").style.display = empleado.estadoLaboral === "Inactivo" ? "block" : "none";
  document.getElementById("estadisticasMotivoFila").style.display = empleado.estadoLaboral === "Inactivo" ? "block" : "none";
  document.getElementById("estadisticasNotas").textContent = empleado.notasInternas || "Sin notas.";
  document.getElementById("statAsignadas").textContent = stats.asignadas || 0;
  document.getElementById("statRegistradas").textContent = stats.registradas || 0;
  document.getElementById("statCumplimiento").textContent = `${stats.cumplimiento || 0}%`;
  document.getElementById("statLimpias").textContent = stats.limpias || 0;
  document.getElementById("statSucias").textContent = stats.sucias || 0;
  document.getElementById("statMantenimiento").textContent = stats.mantenimiento || 0;
  document.getElementById("modalEstadisticas").style.display = "flex";
}

function cerrarModalEstadisticas() {
  document.getElementById("modalEstadisticas").style.display = "none";
}

function abrirModalEmpleado() {
  limpiarFormulario();
  document.getElementById("modalEmpleadoTitulo").textContent = "Registrar empleado";
  document.getElementById("modalEmpleado").style.display = "flex";
  document.getElementById("nombre").focus();
}

function cerrarModalEmpleado() {
  document.getElementById("modalEmpleado").style.display = "none";
  limpiarFormulario();
}

function editarEmpleado(id) {
  const empleado = empleados.find(item => item.id === id);
  if (!empleado) return;

  document.getElementById("modalEmpleado").style.display = "flex";
  document.getElementById("modalEmpleadoTitulo").textContent = "Editar empleado";
  document.getElementById("empleadoId").value = empleado.id;
  document.getElementById("nombre").value = empleado.nombre;
  document.getElementById("apellido").value = empleado.apellido;
  document.getElementById("telefono").value = empleado.telefono;
  document.getElementById("direccion").value = empleado.direccion;
  document.getElementById("fechaIngreso").value = empleado.fechaIngreso;
  document.getElementById("estadoLaboral").value = empleado.estadoLaboral;
  document.getElementById("fechaSalida").value = empleado.fechaSalida || "";
  document.getElementById("motivoSalida").value = empleado.motivoSalida || "";
  document.getElementById("notasInternas").value = empleado.notasInternas || "";
  document.getElementById("btnGuardar").textContent = "Actualizar Empleado";
  alternarCamposSalida();
  document.getElementById("nombre").focus();
}

function limpiarFormulario() {
  document.getElementById("formEmpleado").reset();
  document.getElementById("empleadoId").value = "";
  document.getElementById("btnGuardar").textContent = "Guardar Empleado";
  document.getElementById("estadoLaboral").value = "Activo";
  alternarCamposSalida();
}

function mostrarMensaje(texto, tipo) {
  const mensaje = document.getElementById("mensaje");
  mensaje.textContent = texto;
  mensaje.className = `mensaje ${tipo}`;
  mensaje.style.display = "block";
  setTimeout(() => {
    mensaje.style.display = "none";
  }, 3500);
}

function configurarLayoutBase() {
  const sidebar = document.getElementById("sidebar");
  const toggle = document.getElementById("toggleSidebar");
  const backdrop = document.getElementById("sidebarBackdrop");
  const horaActual = document.getElementById("horaActual");
  const mobileQuery = window.matchMedia("(max-width: 1024px)");

  function abrirSidebarMobile() {
    sidebar.classList.add("sidebar-mobile-open");
    backdrop?.classList.add("visible");
    document.body.classList.add("sidebar-mobile-open");
    toggle.setAttribute("title", "Cerrar men\u00fa");
  }

  function cerrarSidebarMobile() {
    sidebar.classList.remove("sidebar-mobile-open");
    backdrop?.classList.remove("visible");
    document.body.classList.remove("sidebar-mobile-open");
    toggle.setAttribute("title", "Abrir men\u00fa");
  }

  function aplicarModoActual() {
    if (!sidebar || !toggle) return;

    if (mobileQuery.matches) {
      sidebar.classList.remove("collapsed");
      cerrarSidebarMobile();
    } else {
      sidebar.classList.remove("sidebar-mobile-open");
      backdrop?.classList.remove("visible");
      document.body.classList.remove("sidebar-mobile-open");
      toggle.setAttribute("title", sidebar.classList.contains("collapsed") ? "Expandir men\u00fa" : "Contraer men\u00fa");
    }
  }

  if (toggle && sidebar) {
    toggle.addEventListener("click", () => {
      if (mobileQuery.matches) {
        if (sidebar.classList.contains("sidebar-mobile-open")) {
          cerrarSidebarMobile();
        } else {
          abrirSidebarMobile();
        }
        return;
      }

      sidebar.classList.toggle("collapsed");
      toggle.setAttribute("title", sidebar.classList.contains("collapsed") ? "Expandir men\u00fa" : "Contraer men\u00fa");
    });
  }

  backdrop?.addEventListener("click", cerrarSidebarMobile);
  window.addEventListener("resize", aplicarModoActual);
  aplicarModoActual();

  function actualizarHora() {
    const ahora = new Date();
    horaActual.textContent = ahora.toLocaleTimeString("es-DO", { hour: "2-digit", minute: "2-digit" });
  }

  actualizarHora();
  setInterval(actualizarHora, 60000);
}

document.getElementById("formEmpleado").addEventListener("submit", async function(event) {
  event.preventDefault();
  const formData = new FormData(this);

  try {
    await guardarEmpleado(formData);
    mostrarMensaje("Empleado guardado correctamente", "exito");
    limpiarFormulario();
    await cargarEmpleados();
    cerrarModalEmpleado();
  } catch (error) {
    mostrarMensaje(error.message, "error");
  }
});

document.getElementById("modalEmpleado").addEventListener("click", function(event) {
  if (event.target === this) {
    cerrarModalEmpleado();
  }
});

document.getElementById("modalEstadisticas").addEventListener("click", function(event) {
  if (event.target === this) {
    cerrarModalEstadisticas();
  }
});

document.getElementById("buscador").addEventListener("input", renderEmpleados);
document.getElementById("filtroEstado").addEventListener("change", renderEmpleados);
document.getElementById("estadoLaboral").addEventListener("change", alternarCamposSalida);

window.addEventListener("load", async function() {
  configurarLayoutBase();
  alternarCamposSalida();
  await cargarEmpleados();
});

