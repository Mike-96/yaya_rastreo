<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Paquetes | YaYa Envios</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <style>
        :root {
            --primary: #0b3c5d;
            --secondary: #27ae60;
            --accent: #3498db;
            --text-dark: #2c3e50;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)),
                url('fondo.jpg') center/cover no-repeat;
            z-index: -1;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            padding: 40px 20px;
            color: white;
            text-align: center;
        }

        .logo img {
            height: 60px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
            border-radius: 5px;
        }

        header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 2.2rem;
            letter-spacing: -1px;
            text-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .main {
            flex: 1;
            display: flex;
            justify-content: center;
            padding: 0 20px 40px;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }

        .top-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }

        .top-buttons button {
            padding: 12px;
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .top-buttons button:hover {
            background: var(--primary);
            color: white;
        }

        .search-box {
            display: flex;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 15px;
            border: 1px solid #e0e0e0;
            gap: 10px;
            margin-bottom: 25px;
        }

        .search-box select,
        .search-box input {
            border: none;
            background: transparent;
            padding: 12px;
            outline: none;
            font-size: 1rem;
        }

        .search-box select {
            border-right: 1px solid #ddd;
            font-weight: 600;
            color: var(--primary);
            cursor: pointer;
            min-width: 150px;
        }

        .search-box input {
            flex: 1;
        }

        .search-box button {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 0 25px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .search-box button:hover {
            background: #1e874b;
            transform: translateY(-1px);
        }

        .search-box button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        #tablaContainer {
            display: none;
            animation: fadeIn 0.5s ease forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dataTables_wrapper {
            margin-top: 20px;
            width: 100% !important;
            overflow-x: hidden !important;
        }

        #tabla {
            width: 100% !important;
            margin: 0 auto;
        }

        table.dataTable thead th {
            background: var(--primary);
            color: white !important;
            border-radius: 0;
            padding: 15px !important;
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        table.dataTable tbody td {
            padding: 12px !important;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        #loader {
            display: none;
            text-align: center;
            margin: 20px 0;
            color: var(--primary);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-pagado {
            background: #d4edda;
            color: #155724;
        }

        .status-bodega {
            background: #fff3cd;
            color: #856404;
        }

        .badge {
            background: var(--primary);
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            display: inline-block;
        }

        .tracking-text {
            color: var(--accent);
            font-weight: bold;
        }

        #mensaje {
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
        }

        table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control:before,
        table.dataTable.dtr-inline.collapsed>tbody>tr>th.dtr-control:before {
            background-color: var(--accent) !important;
        }

        table.dataTable tbody tr.child ul.dtr-details {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        table.dataTable tbody tr.child ul.dtr-details li {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
        }

        table.dataTable tbody tr.child span.dtr-title {
            font-weight: 700;
            color: var(--primary);
        }

        table.dataTable tbody tr.child span.dtr-data {
            color: var(--text-dark);
        }

        @media (max-width: 1024px) {
            .container {
                max-width: 90%;
                padding: 30px;
            }

            header h2 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                padding: 20px;
            }

            header h2 {
                font-size: 1.5rem;
            }

            .container {
                padding: 20px;
                margin-top: 10px;
            }

            .top-buttons {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .search-box {
                flex-direction: column;
                background: transparent;
                border: none;
                padding: 0;
                gap: 10px;
            }

            .search-box select,
            .search-box input,
            .search-box button {
                background: white;
                border-radius: 12px;
                border: 1px solid #ddd;
                width: 100% !important;
                display: block;
                height: 55px;
                margin: 0;
                font-size: 16px;
            }

            .search-box select {
                border-right: 1px solid #ddd;
            }

            .search-box button {
                background: var(--secondary);
                box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
            }

            .dataTables_wrapper {
                font-size: 0.85rem;
            }
        }
    </style>
</head>

<body>

    <div class="bg"></div>

    <header>
        <div class="logo">
            <img src="logo.jpg" alt="Logo"
                onerror="this.src='https://cdn-icons-png.flaticon.com/512/726/726469.png'">
        </div>
        <h2>Consulta t&uacute; Paquete</h2>
    </header>

    <div class="main">
        <div class="container">

            <div class="top-buttons">
                <button type="button"
                    onclick="window.open('https://aereomarexpress.multitrack.trackingpremium.us/tracking/tracking', '_blank', 'noopener,noreferrer')">
                    <i class="fas fa-external-link-alt"></i> Warehouse Doral
                </button>

                <button type="button"
                    onclick="window.open('https://everest.cargotrack.net/m/track.asp', '_blank', 'noopener,noreferrer')">
                    <i class="fas fa-external-link-alt"></i> Warehouse Miami
                </button>
            </div>

            <div class="search-box">
                <select id="tipo">
                    <option value="tracking">📦 Tracking</option>
                    <option value="warehouse">🏠 Warehouse</option>
                </select>

                <input type="text" id="buscar" placeholder="Ej: ABC123456789">

                <button id="btnBuscar" type="button">
                    <i class="fas fa-search"></i> <span class="btn-text">Buscar</span>
                </button>
            </div>

            <div id="loader">
                <i class="fas fa-circle-notch fa-spin fa-2x"></i>
            </div>

            <div id="mensaje"></div>

            <div id="tablaContainer">
                <table id="tabla" class="display responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Nombre</th>
                            <th>Servicio</th>
                            <th>Warehouse</th>
                            <th>Tracking</th>
                            <th>Peso</th>
                            <th>Estatus</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>
    </div>

    <script>
        let buscando = false;
        let tabla = null;

        function escapeHtml(text) {
            return String(text ?? "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function inicializarTabla() {
            tabla = $('#tabla').DataTable({
                paging: false,
                searching: false,
                info: false,
                autoWidth: false,
                responsive: true,
                columnDefs: [{
                        responsivePriority: 1,
                        targets: 0
                    }, // Fecha
                    {
                        responsivePriority: 2,
                        targets: 4
                    }, // Tracking
                    {
                        responsivePriority: 3,
                        targets: 6
                    } // Estatus
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                }
            });
        }

        $(document).ready(function() {
            inicializarTabla();

            $("#btnBuscar").on("click", function() {
                buscar();
            });

            $("#buscar").on("keypress", function(e) {
                if (e.which === 13) buscar();
            });

            $(window).on("resize", function() {
                if (tabla) {
                    tabla.columns.adjust();
                    tabla.responsive.recalc();
                }
            });
        });

        function buscar() {
            if (buscando) return;

            let q = $("#buscar").val().trim();
            let tipo = $("#tipo").val();

            if (!q) {
                $("#mensaje").css("color", "#e74c3c").html("⚠️ Por favor, ingrese un número.");
                return;
            }

            buscando = true;
            $("#mensaje").html("");
            $("#loader").show();
            $("#btnBuscar").prop("disabled", true);
            $("#tablaContainer").hide();

            $.ajax({
                url: "api.php",
                method: "GET",
                data: {
                    q: q,
                    tipo: tipo
                },
                dataType: "json",
                success: function(data) {
                    tabla.clear();

                    if (!Array.isArray(data) || data.length === 0) {
                        $("#mensaje").css("color", "#7f8c8d").html("No se encontraron paquetes.");
                        return;
                    }

                    data.forEach(function(row) {
                        let estatus = String(row.estatus || "").toUpperCase();
                        let esPagado = estatus === "PAGADO";

                        let fecha = escapeHtml(row.fecha_registro || "");
                        let nombre = escapeHtml(row.nombre || "");
                        let servicio = escapeHtml(row.servicio || "");
                        let warehouse = escapeHtml(row.warehouse || "");
                        let tracking = escapeHtml(row.tracking || "");
                        let peso = escapeHtml(row.peso || "0");

                        let textoEstatus = esPagado ? "ENTREGADO" : "EN BODEGA";
                        let claseEstatus = esPagado ? "status-pagado" : "status-bodega";

                        tabla.row.add([
                            `<b>${fecha}</b>`,
                            nombre,
                            `<span class="badge">${servicio}</span>`,
                            warehouse,
                            `<span style="color:var(--accent); font-weight:bold;">${tracking}</span>`,
                            `${peso} lbs`,
                            `<span class="status-badge ${claseEstatus}">${textoEstatus}</span>`
                        ]);
                    });

                    tabla.draw();

                    $("#tablaContainer").fadeIn(200, function() {
                        tabla.columns.adjust();
                        tabla.responsive.recalc();
                    });
                },
                error: function() {
                    $("#mensaje").css("color", "#e74c3c").html("❌ Error de conexión.");
                },
                complete: function() {
                    buscando = false;
                    $("#loader").hide();
                    $("#btnBuscar").prop("disabled", false);
                }
            });
        }
    </script>

</body>

</html>