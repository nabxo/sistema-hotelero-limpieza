    let asignaciones = JSON.parse(localStorage.getItem("asignaciones")) || [];
    let empleados = [];
    let indiceEditando = -1;

    function guardarAsignaciones() {
        localStorage.setItem("asignaciones", JSON.stringify(asignaciones));
    }

    function obtenerHabitaciones() {
        return JSON.parse(localStorage.getItem("habitaciones")) || [];
    }

    function guardarHabitaciones(habitaciones) {
        localStorage.setItem("habitaciones", JSON.stringify(habitaciones));
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

    async function cargarDatosServidor() {
        const respuesta = await fetch("php/obtener_datos.php", { cache: "no-store" });
        const datos = await respuesta.json();

        if (!respuesta.ok || datos.ok === false) {
            throw new Error(datos.mensaje || "No se pudieron cargar los datos.");
        }

        return datos;
    }

    async function sincronizarConServidorSiHaceFalta() {
        const datosServidor = await cargarDatosServidor();
        const servidorVacio = datosServidor.habitaciones.length === 0 &&
                             datosServidor.inventario.length === 0 &&
                             datosServidor.asignaciones.length === 0 &&
                             datosServidor.registros.length === 0;

        if (servidorVacio) {
            await enviarJSON("php/sincronizar_datos.php", {
                habitaciones: obtenerHabitaciones(),
                inventario: JSON.parse(localStorage.getItem("inventario")) || [],
                asignaciones: asignaciones,
                registros: JSON.parse(localStorage.getItem("registros")) || []
            });

            return await cargarDatosServidor();
        }

        return datosServidor;
    }

    async function inicializarDesdeServidor() {
        try {
            const datos = await sincronizarConServidorSiHaceFalta();

            if (datos.habitaciones.length) {
                guardarHabitaciones(datos.habitaciones);
            }

            asignaciones = (datos.asignaciones || []).map(normalizarAsignacion);
            guardarAsignaciones();
            await cargarEmpleados();
            cargarHabitacionesSucias();
            cargarEmpleadosActivos();
        } catch (error) {
            console.error(error);
        }
    }

    async function cargarEmpleados() {
        const respuesta = await fetch("php/obtener_empleados.php", { cache: "no-store" });
        const datos = await respuesta.json();
        if (!respuesta.ok || datos.ok === false) {
            throw new Error(datos.mensaje || "No se pudieron cargar los empleados.");
        }
        empleados = datos.empleados || [];
    }

    function cargarHabitacionesSucias() {
        const select = document.getElementById("habitacion");
        const valorActual = select.value;
        const habitacionesSucias = obtenerHabitaciones().filter(h => h.estado === "Sucia");

        select.innerHTML = `<option value="">Seleccione una habitación sucia</option>`;

        habitacionesSucias.forEach(habitacion => {
            const option = document.createElement("option");
            option.value = habitacion.numero;
            option.textContent = `${habitacion.numero} - ${habitacion.tipo} - Piso ${habitacion.piso}`;
            select.appendChild(option);
        });

        if (valorActual && !habitacionesSucias.some(h => h.numero === valorActual)) {
            const option = document.createElement("option");
            option.value = valorActual;
            option.textContent = `${valorActual} - asignación en edición`;
            select.appendChild(option);
        }

        select.value = valorActual;
    }

    function cargarEmpleadosActivos() {
        const select = document.getElementById("empleado");
        const valorActual = select.value;
        const empleadosActivos = empleados.filter(empleado => empleado.estadoLaboral === "Activo");

        select.innerHTML = `<option value="">Seleccione un auxiliar de limpieza</option>`;

        empleadosActivos.forEach(empleado => {
            const option = document.createElement("option");
            option.value = empleado.id;
            option.textContent = `${empleado.codigo} - ${empleado.nombreCompleto}`;
            option.dataset.nombre = empleado.nombreCompleto;
            select.appendChild(option);
        });

        select.value = valorActual;
    }

    function obtenerEmpleadoSeleccionado() {
        const empleadoId = parseInt(document.getElementById("empleado").value, 10);
        return empleados.find(empleado => empleado.id === empleadoId) || null;
    }

    function generarIdUnico() {
        return "ASIG-" + Date.now() + "-" + Math.floor(Math.random() * 10000);
    }

    function normalizarAsignacionesViejas() {
        let huboCambios = false;

        asignaciones = asignaciones.map(asig => {
            if (!asig.id) {
                huboCambios = true;
                return {
                    ...asig,
                    id: generarIdUnico()
                };
            }
            return asig;
        });

        if (huboCambios) {
            guardarAsignaciones();
        }
    }

    function obtenerClaseBadgeEstado(estado) {
        if (estado === "Limpia") return "badge badge-limpia";
        if (estado === "Sucia") return "badge badge-sucia";
        if (estado === "Mantenimiento") return "badge badge-mantenimiento";
        return "badge";
    }

    function mostrarMensaje(texto, tipo) {
        const mensaje = document.getElementById("mensaje");
        mensaje.textContent = texto;
        mensaje.className = "mensaje " + tipo;
        mensaje.style.display = "block";

        setTimeout(() => {
            mensaje.style.display = "none";
        }, 2500);
    }

    function formatearFecha(fechaISO) {
        if (!fechaISO) return "";

        const partes = fechaISO.split("-");
        if (partes.length !== 3) return fechaISO;

        const anio = partes[0];
        const mesIndex = parseInt(partes[1], 10) - 1;
        const dia = parseInt(partes[2], 10);

        const meses = [
            "enero", "febrero", "marzo", "abril", "mayo", "junio",
            "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"
        ];

        return `${dia} de ${meses[mesIndex]} ${anio}`;
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
        const anio = ahora.getFullYear();
        const mes = String(ahora.getMonth() + 1).padStart(2, "0");
        const dia = String(ahora.getDate()).padStart(2, "0");
        return `${anio}-${mes}-${dia}`;
    }

    function obtenerHoraActual24() {
        const ahora = new Date();
        const horas = String(ahora.getHours()).padStart(2, "0");
        const minutos = String(ahora.getMinutes()).padStart(2, "0");
        return `${horas}:${minutos}`;
    }

    function autocompletarFechaYHoraActual() {
        const habitacion = document.getElementById("habitacion").value;
        const fechaInput = document.getElementById("fecha");
        const horaInput = document.getElementById("hora");

        if (habitacion !== "") {
            fechaInput.value = obtenerFechaActualISO();
            horaInput.value = obtenerHoraActual24();
        } else {
            fechaInput.value = "";
            horaInput.value = "";
        }
    }

    function actualizarHabitacionASucia(numeroHabitacion) {
        const habitaciones = obtenerHabitaciones();
        if (!habitaciones.length) return;

        const indice = habitaciones.findIndex(h => h.numero === numeroHabitacion);

        if (indice !== -1 && habitaciones[indice].estado !== "Mantenimiento") {
            habitaciones[indice].estado = "Sucia";
            guardarHabitaciones(habitaciones);
        }
    }

    async function eliminarAsignacion(indice) {
        const confirmar = confirm("¿Seguro que deseas eliminar esta asignación?");
        if (!confirmar) return;

        const asignacionEliminada = asignaciones[indice];
        asignaciones.splice(indice, 1);
        guardarAsignaciones();

        if (asignacionEliminada && asignacionEliminada.id) {
            try {
                await enviarJSON("php/eliminar_asignacion.php", {
                    id: asignacionEliminada.id
                });
            } catch (error) {
                console.error(error);
                mostrarMensaje("Se eliminó localmente, pero falló la base de datos", "error");
            }
        }

        aplicarFiltrosYOrden();
        mostrarMensaje("Asignación eliminada correctamente", "exito");
    }

    function editarAsignacion(indice) {
        const asignacion = asignaciones[indice];

        const habitacionSelect = document.getElementById("habitacion");
        if (![...habitacionSelect.options].some(option => option.value === asignacion.habitacion)) {
            const option = document.createElement("option");
            option.value = asignacion.habitacion;
            option.textContent = `${asignacion.habitacion} - asignación en edición`;
            habitacionSelect.appendChild(option);
        }

        document.getElementById("habitacion").value = asignacion.habitacion;
        const empleadoEncontrado = empleados.find(empleado => empleado.id === asignacion.empleadoId || empleado.nombreCompleto === asignacion.empleado);
        document.getElementById("empleado").value = empleadoEncontrado ? empleadoEncontrado.id : "";
        document.getElementById("fecha").value = asignacion.fechaISO || "";
        document.getElementById("hora").value = asignacion.hora24 || "";

        indiceEditando = indice;
        document.getElementById("btnGuardar").textContent = "Guardar Cambios";
        mostrarMensaje("Editando asignación seleccionada", "exito");
    }

    function limpiarFormulario() {
        document.getElementById("formAsignacion").reset();
        indiceEditando = -1;
        document.getElementById("btnGuardar").textContent = "Confirmar Asignación";
        cargarHabitacionesSucias();
        cargarEmpleadosActivos();
        mostrarMensaje("Formulario limpiado", "exito");
    }

    function validarNombre(nombre) {
        const soloNumeros = /^[0-9]+$/;
        return nombre.trim() !== "" && !soloNumeros.test(nombre.trim());
    }

    function existeAsignacionDuplicada(habitacion, fechaISO, hora24, indiceActual = -1) {
        return asignaciones.some((a, index) => {
            const fechaGuardada = a.fechaISO || "";
            const horaGuardada = a.hora24 || "";

            return a.habitacion === habitacion &&
                   fechaGuardada === fechaISO &&
                   horaGuardada === hora24 &&
                   index !== indiceActual;
        });
    }

    document.getElementById("formAsignacion").addEventListener("submit", async function(e) {
        e.preventDefault();

        const habitacion = document.getElementById("habitacion").value;
        const empleadoSeleccionado = obtenerEmpleadoSeleccionado();
        const empleadoId = empleadoSeleccionado ? empleadoSeleccionado.id : 0;
        const empleado = empleadoSeleccionado ? empleadoSeleccionado.nombreCompleto : "";
        const fechaISO = document.getElementById("fecha").value;
        const hora24 = document.getElementById("hora").value;

        if (habitacion === "" || empleadoId <= 0 || empleado === "" || fechaISO === "" || hora24 === "") {
            mostrarMensaje("Completa todos los campos", "error");
            return;
        }

        if (!validarNombre(empleado)) {
            mostrarMensaje("El nombre del empleado no puede estar vacío ni ser solo números", "error");
            return;
        }

        if (existeAsignacionDuplicada(habitacion, fechaISO, hora24, indiceEditando)) {
            mostrarMensaje("Ya existe una asignación para esa habitación en esa fecha y hora", "error");
            return;
        }

        const datosAsignacion = {
            id: indiceEditando === -1 ? generarIdUnico() : asignaciones[indiceEditando].id,
            habitacion: habitacion,
            empleadoId: empleadoId,
            empleado: empleado,
            fechaISO: fechaISO,
            fecha: formatearFecha(fechaISO),
            hora24: hora24,
            hora: formatearHora12(hora24),
            estado: "Sucia"
        };

        try {
            await enviarJSON("php/guardar_asignacion.php", datosAsignacion);
        } catch (error) {
            console.error(error);
            mostrarMensaje("No se pudo guardar la asignación en MySQL. " + error.message, "error");
            return;
        }

        if (indiceEditando === -1) {
            asignaciones.push(datosAsignacion);
            mostrarMensaje("Asignación guardada correctamente", "exito");
        } else {
            asignaciones[indiceEditando] = datosAsignacion;
            indiceEditando = -1;
            document.getElementById("btnGuardar").textContent = "Confirmar Asignación";
            mostrarMensaje("Asignación actualizada correctamente", "exito");
        }

        guardarAsignaciones();

        actualizarHabitacionASucia(habitacion);
        cargarHabitacionesSucias();
        cargarEmpleadosActivos();
        aplicarFiltrosYOrden();
        document.getElementById("formAsignacion").reset();
    });

    document.getElementById("buscador")?.addEventListener("input", aplicarFiltrosYOrden);
    document.getElementById("ordenarPor")?.addEventListener("change", aplicarFiltrosYOrden);
    document.getElementById("habitacion").addEventListener("change", autocompletarFechaYHoraActual);

    function aplicarFiltrosYOrden() {
        const buscador = document.getElementById("buscador");
        const ordenarPor = document.getElementById("ordenarPor");
        const texto = buscador ? buscador.value.toLowerCase() : "";
        const orden = ordenarPor ? ordenarPor.value : "ninguno";

        const listaFiltrada = asignaciones.filter(a =>
            a.habitacion.toLowerCase().includes(texto) ||
            a.empleado.toLowerCase().includes(texto) ||
            (a.fecha && a.fecha.toLowerCase().includes(texto)) ||
            (a.fechaISO && a.fechaISO.toLowerCase().includes(texto)) ||
            (a.id && a.id.toLowerCase().includes(texto))
        );

        if (orden === "habitacion") {
            listaFiltrada.sort((a, b) => a.habitacion.localeCompare(b.habitacion));
        } else if (orden === "fecha") {
            listaFiltrada.sort((a, b) => {
                const valorA = (a.fechaISO || "") + " " + (a.hora24 || "");
                const valorB = (b.fechaISO || "") + " " + (b.hora24 || "");
                return valorA.localeCompare(valorB);
            });
        }

        const listaConIndices = listaFiltrada.map(item => {
            return {
                ...item,
                indiceOriginal: asignaciones.findIndex(a => a.id === item.id)
            };
        });

        const tbody = document.querySelector("#tablaAsignaciones tbody");
        tbody.innerHTML = "";

        listaConIndices.forEach(function(asignacion) {
            const fila = document.createElement("tr");

            fila.innerHTML = `
                <td><strong>${asignacion.habitacion}</strong></td>
                <td>${asignacion.empleado}</td>
                <td>${asignacion.fecha}</td>
                <td>${asignacion.hora}</td>
                <td><span class="${obtenerClaseBadgeEstado(asignacion.estado)}">${asignacion.estado}</span></td>
                <td>
                    <button class="acciones-btn btn-editar" onclick="editarAsignacion(${asignacion.indiceOriginal})">Editar</button>
                    <button class="acciones-btn btn-eliminar" onclick="eliminarAsignacion(${asignacion.indiceOriginal})">Eliminar</button>
                </td>
            `;

            tbody.appendChild(fila);
        });
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

    window.addEventListener("load", async function() {
        normalizarAsignacionesViejas();
        await inicializarDesdeServidor();
        aplicarFiltrosYOrden();
        configurarLayoutBase();
    });

