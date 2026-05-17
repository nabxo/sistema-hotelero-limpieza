const habitacionesIniciales = [
  { numero:"101", tipo:"Individual", piso:"1", estado:"Limpia", obs:"" },
  { numero:"102", tipo:"Individual", piso:"1", estado:"Sucia", obs:"Necesita limpieza profunda" },
  { numero:"103", tipo:"Doble", piso:"1", estado:"Limpia", obs:"Cambio de sabanas pendiente" },
  { numero:"104", tipo:"Doble", piso:"1", estado:"Mantenimiento", obs:"Aire acondicionado dañado" },
  { numero:"105", tipo:"Suite Junior", piso:"1", estado:"Limpia", obs:"" },
  { numero:"201", tipo:"Individual", piso:"2", estado:"Sucia", obs:"" },
  { numero:"202", tipo:"Doble", piso:"2", estado:"Limpia", obs:"" },
  { numero:"203", tipo:"Doble Superior", piso:"2", estado:"Limpia", obs:"Cambio de sabanas pendiente" },
  { numero:"204", tipo:"Normal", piso:"2", estado:"Sucia", obs:"" },
  { numero:"205", tipo:"Normal", piso:"2", estado:"Mantenimiento", obs:"" },
  { numero:"301", tipo:"Familiar", piso:"3", estado:"Sucia", obs:"" },
  { numero:"302", tipo:"Familiar", piso:"3", estado:"Limpia", obs:"" },
  { numero:"303", tipo:"Familiar", piso:"3", estado:"Sucia", obs:"" },
  { numero:"304", tipo:"Familiar", piso:"4", estado:"Limpia", obs:"" },
  { numero:"305", tipo:"Familiar", piso:"3", estado:"Mantenimiento", obs:"" },
  { numero:"401", tipo:"Lujosa", piso:"4", estado:"Limpia", obs:"" },
  { numero:"402", tipo:"Lujosa", piso:"5", estado:"Sucia", obs:"" },
  { numero:"403", tipo:"Lujosa", piso:"6", estado:"Limpia", obs:"" },
  { numero:"404", tipo:"Lujosa", piso:"6", estado:"Sucia", obs:"" },
  { numero:"405", tipo:"Lujosa", piso:"6", estado:"Mantenimiento", obs:"" }
];

const inventarioInicial = [
  { producto:"Jabon", cantidad:50 },
  { producto:"Toallas", cantidad:30 },
  { producto:"Detergente", cantidad:20 },
  { producto:"Guantes", cantidad:100 },
  { producto:"Desinfectante", cantidad:15 }
];

function clonarHabitaciones(habitaciones) {
  return habitaciones.map(habitacion => ({ ...habitacion }));
}

function clonarInventario(inventario) {
  return inventario.map(item => ({ ...item }));
}

function obtenerHabitaciones() {
  return JSON.parse(localStorage.getItem("habitaciones")) || habitacionesIniciales;
}

function guardarHabitaciones(habitaciones) {
  localStorage.setItem("habitaciones", JSON.stringify(habitaciones));
}

function obtenerInventario() {
  return JSON.parse(localStorage.getItem("inventario")) || inventarioInicial;
}

function guardarInventario(inventario) {
  localStorage.setItem("inventario", JSON.stringify(inventario));
}

function obtenerAsignaciones() {
  return JSON.parse(localStorage.getItem("asignaciones")) || [];
}

function guardarAsignaciones(asignaciones) {
  localStorage.setItem("asignaciones", JSON.stringify(asignaciones));
}

function obtenerRegistros() {
  return JSON.parse(localStorage.getItem("registros")) || [];
}

function guardarRegistros(registros) {
  localStorage.setItem("registros", JSON.stringify(registros));
}

async function enviarJSON(url, datos) {
  const respuesta = await fetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(datos)
  });

  const texto = await respuesta.text();
  let json = {};

  try {
    json = texto ? JSON.parse(texto) : {};
  } catch (error) {
    throw new Error("La respuesta del servidor no es valida.");
  }

  if (!respuesta.ok || json.ok === false) {
    const detalle = json.error ? " Detalle: " + json.error : "";
    throw new Error((json.mensaje || "No se pudo completar la operacion.") + detalle);
  }

  return json;
}

function normalizarAsignacion(asig) {
  return {
    id: asig.id,
    habitacion: asig.habitacion,
    empleadoId: asig.empleadoId || null,
    empleado: asig.empleado,
    fechaISO: asig.fechaISO,
    fecha: asig.fecha || formatearFecha(asig.fechaISO),
    hora24: asig.hora24,
    hora: asig.hora || formatearHora12(asig.hora24),
    estado: asig.estado || "Sucia"
  };
}

function normalizarRegistro(registro) {
  return {
    idRegistro: registro.idRegistro || null,
    asignacionId: registro.asignacionId,
    habitacion: registro.habitacion,
    empleadoId: registro.empleadoId || null,
    empleado: registro.empleado,
    fecha: registro.fecha,
    fechaMostrada: registro.fechaMostrada || formatearFecha(registro.fecha),
    hora: registro.hora,
    horaMostrada: registro.horaMostrada || formatearHora12(registro.hora),
    estado: registro.estado,
    observaciones: registro.observaciones || ""
  };
}

async function cargarDatosDesdeServidor() {
  const respuesta = await fetch("php/obtener_datos.php", { cache: "no-store" });
  const datos = await respuesta.json();

  if (!respuesta.ok || datos.ok === false) {
    throw new Error(datos.mensaje || "No se pudieron cargar los datos.");
  }

  return datos;
}

async function sincronizarConServidorSiHaceFalta() {
  const datosServidor = await cargarDatosDesdeServidor();
  const servidorVacio = datosServidor.habitaciones.length === 0 &&
                        datosServidor.inventario.length === 0 &&
                        datosServidor.asignaciones.length === 0 &&
                        datosServidor.registros.length === 0;

  if (servidorVacio) {
    await enviarJSON("php/sincronizar_datos.php", {
      habitaciones: obtenerHabitaciones(),
      inventario: obtenerInventario(),
      asignaciones: obtenerAsignaciones(),
      registros: obtenerRegistros()
    });
    return await cargarDatosDesdeServidor();
  }

  return datosServidor;
}

async function inicializarDesdeServidor() {
  try {
    const datos = await sincronizarConServidorSiHaceFalta();
    if (datos.habitaciones.length) guardarHabitaciones(datos.habitaciones);
    if (datos.inventario.length) guardarInventario(datos.inventario);
    guardarAsignaciones((datos.asignaciones || []).map(normalizarAsignacion));
    guardarRegistros((datos.registros || []).map(normalizarRegistro));
  } catch (error) {
    console.error(error);
  }
}

function inicializarDatos() {
  if (!localStorage.getItem("habitaciones")) guardarHabitaciones(habitacionesIniciales);
  if (!localStorage.getItem("inventario")) guardarInventario(inventarioInicial);
  if (!localStorage.getItem("registros")) guardarRegistros([]);
}

function formatearFecha(fechaISO) {
  if (!fechaISO) return "";
  const partes = fechaISO.split("-");
  if (partes.length !== 3) return fechaISO;
  const anio = parseInt(partes[0], 10);
  const mes = parseInt(partes[1], 10) - 1;
  const dia = parseInt(partes[2], 10);
  const meses = ["enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre"];
  return `${dia} de ${meses[mes]} de ${anio}`;
}

function formatearHora12(hora24) {
  if (!hora24) return "";
  const partes = hora24.split(":");
  let horas = parseInt(partes[0], 10);
  const minutos = partes[1];
  const periodo = horas >= 12 ? "PM" : "AM";
  horas = horas % 12;
  if (horas === 0) horas = 12;
  return `${horas}:${minutos} ${periodo}`;
}

function obtenerFechaActualISO() {
  const ahora = new Date();
  return `${ahora.getFullYear()}-${String(ahora.getMonth() + 1).padStart(2, "0")}-${String(ahora.getDate()).padStart(2, "0")}`;
}

function obtenerHoraActual24() {
  const ahora = new Date();
  return `${String(ahora.getHours()).padStart(2, "0")}:${String(ahora.getMinutes()).padStart(2, "0")}`;
}

function obtenerClaseFilaEstado(estado) {
  if (estado === "Limpia") return "fila-limpia";
  if (estado === "Sucia") return "fila-sucia";
  if (estado === "Mantenimiento") return "fila-mantenimiento";
  return "";
}

function obtenerClaseBadge(estado) {
  if (estado === "Limpia") return "badge badge-success";
  if (estado === "Sucia") return "badge badge-warning";
  if (estado === "Mantenimiento") return "badge badge-danger";
  return "badge";
}

function obtenerClaseTimeline(estado) {
  if (estado === "Limpia") return "limp-tl-dot limp-tl-dot-limpia";
  if (estado === "Sucia") return "limp-tl-dot limp-tl-dot-sucia";
  if (estado === "Mantenimiento") return "limp-tl-dot limp-tl-dot-mantenimiento";
  return "limp-tl-dot";
}

function obtenerEtiquetaCumplimiento(registro) {
  if (registro.estado === "Limpia") {
    return {
      texto: "Cumplió",
      clase: "history-badge history-badge-ok"
    };
  }

  return {
    texto: "Pendiente o con incidencia",
    clase: "history-badge history-badge-pending"
  };
}

function obtenerRegistrosFiltrados() {
  const registros = obtenerRegistros();
  const desde = document.getElementById("historialDesde")?.value || "";
  const hasta = document.getElementById("historialHasta")?.value || "";

  return registros
    .map((registro, index) => ({ ...registro, indiceOriginal: index }))
    .filter(registro => {
      if (desde && registro.fecha < desde) return false;
      if (hasta && registro.fecha > hasta) return false;
      return true;
    });
}

let logoHistorialDataUrl = null;
let plantillaHistorialCache = null;

async function obtenerLogoHistorialDataUrl() {
  if (logoHistorialDataUrl) return logoHistorialDataUrl;

  try {
    const respuesta = await fetch("assets/img/logo-hotel.png", { cache: "force-cache" });
    if (!respuesta.ok) {
      throw new Error("No se pudo cargar el logo del hotel.");
    }

    const blob = await respuesta.blob();
    logoHistorialDataUrl = await new Promise((resolve, reject) => {
      const lector = new FileReader();
      lector.onloadend = () => resolve(lector.result);
      lector.onerror = () => reject(new Error("No se pudo convertir el logo para el reporte."));
      lector.readAsDataURL(blob);
    });

    return logoHistorialDataUrl;
  } catch (error) {
    console.error(error);
    return "";
  }
}

async function obtenerPlantillaHistorial() {
  if (plantillaHistorialCache) return plantillaHistorialCache;

  const respuesta = await fetch("plantillas/plantilla_historial_limpieza.html", { cache: "no-store" });
  if (!respuesta.ok) {
    throw new Error("No se pudo cargar la plantilla del historial.");
  }

  plantillaHistorialCache = await respuesta.text();
  return plantillaHistorialCache;
}

function escaparHTML(valor) {
  return String(valor ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function renderKpis() {
  const habitaciones = obtenerHabitaciones();
  document.getElementById("kpi-limpias").textContent = habitaciones.filter(h => h.estado === "Limpia").length;
  document.getElementById("kpi-sucias").textContent = habitaciones.filter(h => h.estado === "Sucia").length;
  document.getElementById("kpi-mantenimiento").textContent = habitaciones.filter(h => h.estado === "Mantenimiento").length;
  document.getElementById("kpi-total").textContent = habitaciones.length;
}

function renderHabitaciones() {
  const habitaciones = obtenerHabitaciones();

  const tbody = document.querySelector("#tablaHabitaciones tbody");
  tbody.innerHTML = "";

  habitaciones.forEach(hab => {
    const fila = document.createElement("tr");
    fila.className = obtenerClaseFilaEstado(hab.estado);

    fila.innerHTML = `
      <td><strong>${hab.numero}</strong></td>
      <td>${hab.tipo}</td>
      <td>${hab.piso}</td>
      <td><span class="${obtenerClaseBadge(hab.estado)}">${hab.estado}</span></td>
      <td>${hab.obs || "-"}</td>
      <td><button class="btn btn-secundario btn-sm" onclick="abrirModalEstado('${hab.numero}')">Cambiar</button></td>
    `;

    tbody.appendChild(fila);
  });

  renderKpis();
}

async function actualizarEstados() {
  const habitaciones = obtenerHabitaciones();
  try {
    await enviarJSON("php/guardar_habitaciones.php", { habitaciones });
    renderHabitaciones();
    alert("Estados actualizados correctamente.");
  } catch (error) {
    console.error(error);
    alert("No se pudieron guardar los estados en MySQL. " + error.message);
  }
}

function abrirModalEstado(numeroHabitacion) {
  const habitaciones = obtenerHabitaciones();
  const indice = habitaciones.findIndex(h => h.numero === numeroHabitacion);
  if (indice === -1) return;

  const habitacion = habitaciones[indice];
  document.getElementById("modalHabitacionIndice").value = String(indice);
  document.getElementById("modalHabitacionNumero").value = habitacion.numero;
  document.getElementById("modalHabitacionEstado").value = habitacion.estado;
  document.getElementById("modalHabitacionObs").value = habitacion.obs || "";
  document.getElementById("modalCambioEstado").style.display = "flex";
}

function cerrarModalEstado() {
  document.getElementById("modalCambioEstado").style.display = "none";
}

function guardarCambioEstado() {
  const indice = parseInt(document.getElementById("modalHabitacionIndice").value, 10);
  const nuevoEstado = document.getElementById("modalHabitacionEstado").value;
  const observaciones = document.getElementById("modalHabitacionObs").value.trim();
  const habitaciones = obtenerHabitaciones();

  if (Number.isNaN(indice) || !habitaciones[indice]) return;

  habitaciones[indice].estado = nuevoEstado;
  habitaciones[indice].obs = observaciones;
  guardarHabitaciones(habitaciones);
  cerrarModalEstado();
  renderHabitaciones();
}

function renderInventario() {
  const inventario = obtenerInventario();
  const tbody = document.querySelector("#tablaInventario tbody");
  tbody.innerHTML = "";

  let stockBajo = 0;
  let stockOk = 0;

  inventario.forEach((item, index) => {
    let claseFila = "";
    let etiqueta = "Cantidad suficiente";
    let claseBadge = "badge badge-success";
    const cantidad = parseInt(item.cantidad, 10) || 0;

    if (cantidad <= 5) {
      claseFila = "bajo-stock";
      etiqueta = "Poca cantidad";
      claseBadge = "badge badge-danger";
      stockBajo++;
    } else if (cantidad <= 15) {
      claseFila = "medio-stock";
      etiqueta = "Revisar pronto";
      claseBadge = "badge badge-warning";
    } else {
      claseFila = "alto-stock";
      stockOk++;
    }

    const fila = document.createElement("tr");
    fila.className = claseFila;
    fila.innerHTML = `
      <td><strong>${item.producto}</strong></td>
      <td><span class="inventario-cantidad">${cantidad}</span></td>
      <td><span class="${claseBadge}">${etiqueta}</span></td>
      <td><input class="inventario-input" type="number" min="0" value="${cantidad}" data-index="${index}" aria-label="Nueva cantidad de ${item.producto}"></td>
    `;
    tbody.appendChild(fila);
  });

  document.getElementById("inv-total-productos").textContent = inventario.length;
  document.getElementById("inv-stock-bajo").textContent = stockBajo;
  document.getElementById("inv-stock-ok").textContent = stockOk;
}

function abrirInventario() {
  renderInventario();
  document.getElementById("modalInventario").style.display = "flex";
}

function cerrarInventario() {
  document.getElementById("modalInventario").style.display = "none";
  history.replaceState(null, "", "index.html");
}

function abrirHistorial() {
  cargarHistorial();
  document.getElementById("modalHistorial").style.display = "flex";
}

function cerrarHistorial() {
  document.getElementById("modalHistorial").style.display = "none";
  history.replaceState(null, "", "index.html");
}

function abrirModalFormatoHistorial() {
  document.getElementById("modalFormatoHistorial").style.display = "flex";
}

function cerrarModalFormatoHistorial() {
  document.getElementById("modalFormatoHistorial").style.display = "none";
}

async function actualizarInventario() {
  const inventarioAnterior = clonarInventario(obtenerInventario());
  const inventario = clonarInventario(inventarioAnterior);
  const inputs = document.querySelectorAll("#tablaInventario tbody input");
  inputs.forEach(input => {
    const index = parseInt(input.dataset.index, 10);
    inventario[index].cantidad = parseInt(input.value, 10) || 0;
  });
  guardarInventario(inventario);
  try {
    await enviarJSON("php/guardar_inventario.php", { inventario });
    renderInventario();
    alert("Inventario actualizado correctamente.");
  } catch (error) {
    console.error(error);
    guardarInventario(inventarioAnterior);
    renderInventario();
    alert("No se pudo guardar el inventario en MySQL. " + error.message);
  }
}

document.getElementById("modalInventario").addEventListener("click", function(event) {
  if (event.target === this) {
    cerrarInventario();
  }
});

document.getElementById("modalHistorial")?.addEventListener("click", function(event) {
  if (event.target === this) {
    cerrarHistorial();
  }
});

document.getElementById("modalFormatoHistorial")?.addEventListener("click", function(event) {
  if (event.target === this) {
    cerrarModalFormatoHistorial();
  }
});

function cargarAsignacionesEnRegistro() {
  const selectAsignacion = document.getElementById("regAsignacion");
  const asignaciones = obtenerAsignaciones();
  selectAsignacion.innerHTML = `<option value="">Seleccione una asignación</option>`;
  asignaciones.forEach(asig => {
    const option = document.createElement("option");
    option.value = asig.id;
    option.textContent = `${asig.habitacion} - ${asig.empleado} - ${asig.fecha} - ${asig.hora}`;
    selectAsignacion.appendChild(option);
  });
}

function autocompletarDatosAsignacion() {
  const asignacionId = document.getElementById("regAsignacion").value;
  const asignacion = obtenerAsignaciones().find(asig => asig.id === asignacionId);
  if (asignacion) {
    document.getElementById("regHabitacion").value = asignacion.habitacion;
    document.getElementById("regEmpleado").value = asignacion.empleado;
    document.getElementById("regEmpleado").dataset.empleadoId = asignacion.empleadoId || "";
    document.getElementById("regFecha").value = obtenerFechaActualISO();
    document.getElementById("regHora").value = obtenerHoraActual24();
  } else {
    document.getElementById("regHabitacion").value = "";
    document.getElementById("regEmpleado").value = "";
    document.getElementById("regEmpleado").dataset.empleadoId = "";
    document.getElementById("regFecha").value = "";
    document.getElementById("regHora").value = "";
  }
}

function cargarHistorial() {
  const registros = obtenerRegistrosFiltrados();
  const timeline = document.getElementById("timelineHistorial");

  if (!timeline) return;

  const total = registros.length;
  const cumplidos = registros.filter(reg => reg.estado === "Limpia").length;
  const pendientes = total - cumplidos;
  const asignaciones = obtenerAsignaciones();

  const totalEl = document.getElementById("historialTotal");
  const cumplidosEl = document.getElementById("historialCumplidos");
  const pendientesEl = document.getElementById("historialPendientes");

  if (totalEl) totalEl.textContent = String(total);
  if (cumplidosEl) cumplidosEl.textContent = String(cumplidos);
  if (pendientesEl) pendientesEl.textContent = String(pendientes);

  if (!registros.length) {
    timeline.innerHTML = `
      <div class="limp-empty">
        <div class="limp-empty-icon">&#128203;</div>
        Sin historial para mostrar.
      </div>
    `;
    return;
  }

  timeline.innerHTML = registros.map((reg) => {
    const asignacion = asignaciones.find(asig => asig.id === reg.asignacionId);
    const cumplimiento = obtenerEtiquetaCumplimiento(reg);
    const fechaAsignacion = asignacion?.fecha || asignacion?.fechaISO || "-";
    const horaAsignacion = asignacion?.hora || asignacion?.hora24 || "-";

    return `
      <div class="limp-tl-item">
        <div class="${obtenerClaseTimeline(reg.estado)}"></div>
        <div class="limp-tl-content">
          <div class="limp-tl-head">
            <div>
              <div class="limp-tl-title">Habitaci&oacute;n ${reg.habitacion} &middot; ${reg.empleado}</div>
              <div class="limp-tl-sub">
                <span class="${obtenerClaseBadge(reg.estado)}">${reg.estado}</span>
                &middot; Asignaci&oacute;n: ${fechaAsignacion} ${horaAsignacion}
                ${reg.observaciones ? " &middot; " + reg.observaciones : ""}
              </div>
              <span class="${cumplimiento.clase}">${cumplimiento.texto}</span>
            </div>
            <div class="limp-tl-time">${reg.fechaMostrada || reg.fecha} &middot; ${reg.horaMostrada || reg.hora}</div>
          </div>
          <div class="limp-tl-actions">
            <button class="btn btn-peligro btn-sm" onclick="borrarRegistro(${reg.indiceOriginal})">Eliminar</button>
          </div>
        </div>
      </div>
    `;
  }).join("");
}

function aplicarFiltroHistorial() {
  const desde = document.getElementById("historialDesde")?.value || "";
  const hasta = document.getElementById("historialHasta")?.value || "";

  if (desde && hasta && desde > hasta) {
    alert("La fecha inicial no puede ser mayor que la fecha final.");
    return;
  }

  cargarHistorial();
}

async function construirDocumentoHistorial() {
  const desde = document.getElementById("historialDesde")?.value || "";
  const hasta = document.getElementById("historialHasta")?.value || "";
  const registros = obtenerRegistrosFiltrados();
  const asignaciones = obtenerAsignaciones();
  const logoUrl = await obtenerLogoHistorialDataUrl();

  if (desde && hasta && desde > hasta) {
    alert("La fecha inicial no puede ser mayor que la fecha final.");
    return null;
  }

  if (!registros.length) {
    alert("No hay registros dentro del rango seleccionado.");
    return null;
  }

  const rango = `${desde ? formatearFecha(desde) : "Inicio"} - ${hasta ? formatearFecha(hasta) : "Hoy"}`;
  const filas = registros.map(reg => {
    const asignacion = asignaciones.find(asig => asig.id === reg.asignacionId);
    const cumplimiento = reg.estado === "Limpia" ? "Sí" : "No";
    return `
      <tr>
        <td>${escaparHTML(reg.habitacion)}</td>
        <td>${escaparHTML(reg.empleado)}</td>
        <td>${escaparHTML(asignacion?.fecha || asignacion?.fechaISO || "-")}</td>
        <td>${escaparHTML(asignacion?.hora || asignacion?.hora24 || "-")}</td>
        <td>${escaparHTML(reg.fechaMostrada || reg.fecha)}</td>
        <td>${escaparHTML(reg.horaMostrada || reg.hora)}</td>
        <td>${escaparHTML(reg.estado)}</td>
        <td>${escaparHTML(cumplimiento)}</td>
        <td>${escaparHTML(reg.observaciones || "-")}</td>
      </tr>
    `;
  }).join("");

  const plantilla = await obtenerPlantillaHistorial();
  const logoHTML = logoUrl
    ? `<img class="header-logo" src="${logoUrl}" alt="Logo Hotel UASD" width="70" height="70">`
    : "";

  return plantilla
    .replace("{{LOGO}}", logoHTML)
    .replace("{{RANGO}}", escaparHTML(rango))
    .replace("{{TOTAL_REGISTROS}}", escaparHTML(registros.length))
    .replace("{{FILAS_TABLA}}", filas);
}

async function imprimirHistorial() {
  const documento = await construirDocumentoHistorial();
  if (!documento) return;

  const ventana = window.open("", "_blank", "width=1080,height=760");
  if (!ventana) {
    alert("No se pudo abrir la ventana de impresión.");
    return;
  }

  ventana.document.write(documento);
  ventana.document.close();
  ventana.focus();
  ventana.print();
}

async function descargarHistorialWord() {
  const documento = await construirDocumentoHistorial();
  if (!documento) return;

  const blob = new Blob([documento], { type: "application/msword" });
  const enlace = document.createElement("a");
  const url = URL.createObjectURL(blob);

  enlace.href = url;
  enlace.download = "historial_limpieza.doc";
  document.body.appendChild(enlace);
  enlace.click();
  enlace.remove();
  URL.revokeObjectURL(url);
}

function construirHistorialJSON() {
  const desde = document.getElementById("historialDesde")?.value || "";
  const hasta = document.getElementById("historialHasta")?.value || "";
  const registros = obtenerRegistrosFiltrados();
  const asignaciones = obtenerAsignaciones();

  if (desde && hasta && desde > hasta) {
    alert("La fecha inicial no puede ser mayor que la fecha final.");
    return null;
  }

  if (!registros.length) {
    alert("No hay registros dentro del rango seleccionado.");
    return null;
  }

  return {
    hotel: "Hotel UASD",
    reporte: "Historial General de Limpieza",
    rango: {
      desde: desde || null,
      hasta: hasta || null,
      mostrado: `${desde ? formatearFecha(desde) : "Inicio"} - ${hasta ? formatearFecha(hasta) : "Hoy"}`
    },
    totalRegistros: registros.length,
    historial: registros.map(reg => {
      const asignacion = asignaciones.find(asig => asig.id === reg.asignacionId);
      return {
        habitacion: reg.habitacion,
        empleado: reg.empleado,
        fechaAsignacion: asignacion?.fecha || asignacion?.fechaISO || "-",
        horaAsignacion: asignacion?.hora || asignacion?.hora24 || "-",
        fechaRegistro: reg.fechaMostrada || reg.fecha,
        horaRegistro: reg.horaMostrada || reg.hora,
        estadoFinal: reg.estado,
        cumplio: reg.estado === "Limpia",
        observaciones: reg.observaciones || ""
      };
    })
  };
}

function descargarHistorialJSON() {
  const datos = construirHistorialJSON();
  if (!datos) return;

  const blob = new Blob([JSON.stringify(datos, null, 2)], { type: "application/json" });
  const enlace = document.createElement("a");
  const url = URL.createObjectURL(blob);

  enlace.href = url;
  enlace.download = "historial_limpieza.json";
  document.body.appendChild(enlace);
  enlace.click();
  enlace.remove();
  URL.revokeObjectURL(url);
}

async function exportarHistorial(formato) {
  cerrarModalFormatoHistorial();

  if (formato === "word") {
    await descargarHistorialWord();
    return;
  }

  if (formato === "pdf") {
    await imprimirHistorial();
    return;
  }

  if (formato === "json") {
    descargarHistorialJSON();
  }
}

async function borrarRegistro(index) {
  const registros = obtenerRegistros();
  const registroEliminado = registros[index];
  registros.splice(index, 1);
  guardarRegistros(registros);
  if (registroEliminado && registroEliminado.idRegistro) {
    try {
      await enviarJSON("php/eliminar_registro.php", { idRegistro: registroEliminado.idRegistro });
    } catch (error) {
      console.error(error);
    }
  }
  cargarHistorial();
}

document.addEventListener("DOMContentLoaded", function() {
  const formRegistro = document.getElementById("formRegistro");

  formRegistro.addEventListener("submit", async function(e) {
    e.preventDefault();
    const asignacionId = document.getElementById("regAsignacion").value;
    const habitacion = document.getElementById("regHabitacion").value.trim();
    const empleadoId = parseInt(document.getElementById("regEmpleado").dataset.empleadoId || "0", 10);
    const empleado = document.getElementById("regEmpleado").value.trim();
    const fechaISO = document.getElementById("regFecha").value;
    const hora24 = document.getElementById("regHora").value;
    const estado = document.getElementById("regEstado").value;
    const observaciones = document.getElementById("regObservaciones").value.trim();
    const hoy = new Date().toISOString().split("T")[0];

    if (asignacionId === "" || habitacion === "" || empleado === "" || fechaISO === "" || hora24 === "") {
      alert("Completa todos los campos obligatorios.");
      return;
    }
    if (fechaISO > hoy) {
      alert("La fecha no puede ser futura.");
      return;
    }

    const nuevoRegistro = {
      asignacionId,
      habitacion,
      empleadoId,
      empleado,
      fecha: fechaISO,
      fechaMostrada: formatearFecha(fechaISO),
      hora: hora24,
      horaMostrada: formatearHora12(hora24),
      estado,
      observaciones
    };

    const registros = obtenerRegistros();

    try {
      const respuesta = await enviarJSON("php/guardar_registro.php", nuevoRegistro);
      nuevoRegistro.idRegistro = respuesta.idRegistro || null;
    } catch (error) {
      console.error(error);
      alert("No se pudo guardar el registro en MySQL. " + error.message);
      return;
    }

    registros.unshift(nuevoRegistro);
    guardarRegistros(registros);
    formRegistro.reset();
    document.getElementById("regHabitacion").value = "";
    document.getElementById("regEmpleado").value = "";
    document.getElementById("regEmpleado").dataset.empleadoId = "";
    cargarHistorial();
    renderHabitaciones();
  });

  document.getElementById("historialDesde")?.addEventListener("change", aplicarFiltroHistorial);
  document.getElementById("historialHasta")?.addEventListener("change", aplicarFiltroHistorial);

  configurarLayoutBase();
});

function mostrar(seccion) {
  const tabs = document.querySelectorAll(".sub-tab-content");
  tabs.forEach(tab => tab.style.display = "none");
  document.getElementById(seccion).style.display = "block";

  document.querySelectorAll(".sub-nav-btn").forEach(btn => btn.classList.remove("activo"));
  const botonActivo = document.querySelector(`[data-seccion="${seccion}"]`);
  if (botonActivo) {
    botonActivo.classList.add("activo");
  }

  if (seccion === "habitaciones") {
    renderHabitaciones();
    history.replaceState(null, "", "index.html");
  } else if (seccion === "registro") {
    cargarAsignacionesEnRegistro();
    history.replaceState(null, "", "index.html#registro");
  }
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
    toggle.addEventListener("click", function() {
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
    horaActual.textContent = ahora.toLocaleTimeString("es-DO", {
      hour: "numeric",
      minute: "2-digit"
    });
  }

  actualizarHora();
  setInterval(actualizarHora, 1000);
}

window.onload = async function() {
  inicializarDatos();
  await inicializarDesdeServidor();
  renderHabitaciones();
  renderInventario();
  cargarAsignacionesEnRegistro();
  cargarHistorial();
  if (window.location.hash === "#registro") {
    mostrar("registro");
  } else if (window.location.hash === "#inventario") {
    mostrar("habitaciones");
    abrirInventario();
  } else {
    mostrar("habitaciones");
  }
};

