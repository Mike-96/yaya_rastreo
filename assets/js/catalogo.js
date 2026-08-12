/**
 * Lógica del catálogo público (catalogo.php).
 * Depende de jQuery 4 y Bootstrap 5 (bundle) cargados localmente.
 */

(function () {
    'use strict';

    const IMAGEN_PLACEHOLDER =
        'data:image/svg+xml;utf8,' +
        encodeURIComponent(
            '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120">' +
            '<rect width="120" height="120" fill="#f1f3f5"/>' +
            '<rect x="30" y="30" width="60" height="60" rx="4" fill="none" stroke="#c3cad3" stroke-width="4"/>' +
            '<circle cx="48" cy="48" r="6" fill="#c3cad3"/>' +
            '<path d="M30 78 L54 58 L70 72 L90 52 L90 84 L30 84 Z" fill="#c3cad3"/>' +
            '</svg>'
        );

    const estado = {
        buscar: '',
        marcas: [],
        categorias: [],
        departamentos: [],
        precioMin: 0,
        precioMax: 0,
        precioMinSel: 0,
        precioMaxSel: 0,
        orden: 'relevancia',
        pagina: 1,
        cargando: false,
        modalProductoActual: null
    };

    function escapeHtml(texto) {
        return String(texto ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatoCordoba(valor) {
        const numero = parseFloat(valor) || 0;
        return 'C$ ' + numero.toLocaleString('es-NI', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /**
     * Texto corto con código y/o número de parte del producto, ej:
     * "Cód. ABC123 · No. Parte: 8891-20". Devuelve '' si no tiene ninguno.
     */
    function referenciaProducto(p) {
        const partes = [];
        if (p.codigo) partes.push('Cód. ' + p.codigo);
        if (p.num_parte) partes.push('No. Parte: ' + p.num_parte);
        return partes.join(' · ');
    }

    // =====================================================================
    //  CARRITO (persistido en localStorage)
    // =====================================================================

    function obtenerCarrito() {
        try {
            const datos = JSON.parse(localStorage.getItem('catalogoCarrito') || '[]');
            return Array.isArray(datos) ? datos : [];
        } catch (e) {
            return [];
        }
    }

    function guardarCarrito(carrito) {
        localStorage.setItem('catalogoCarrito', JSON.stringify(carrito));
        renderCarrito();
    }

    function agregarAlCarrito(producto) {
        const carrito = obtenerCarrito();
        const existente = carrito.find(function (p) { return p.id === producto.id; });

        if (existente) {
            existente.cantidad += 1;
            // Refresca datos del producto por si el ítem se agregó antes de
            // tener codigo/num_parte guardados (carritos viejos en localStorage).
            existente.nombre = producto.nombre;
            existente.codigo = producto.codigo || '';
            existente.num_parte = producto.num_parte || '';
            existente.precio = parseFloat(producto.precio_venta_cordoba) || 0;
            existente.imagen = producto.imagen_principal || '';
        } else {
            carrito.push({
                id: producto.id,
                nombre: producto.nombre,
                codigo: producto.codigo || '',
                num_parte: producto.num_parte || '',
                precio: parseFloat(producto.precio_venta_cordoba) || 0,
                imagen: producto.imagen_principal || '',
                cantidad: 1
            });
        }

        guardarCarrito(carrito);
        mostrarToastAgregado(producto.nombre);
    }

    function mostrarToastAgregado(nombreProducto) {
        const toastEl = document.getElementById('toastAgregado');
        if (!toastEl) return;

        let texto = nombreProducto || 'Producto';
        if (texto.length > 40) {
            texto = texto.substring(0, 40) + '…';
        }
        $('#toastAgregadoTexto').text(texto + ' agregado al carrito');

        bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 2000 }).show();
    }

    function quitarDelCarrito(id) {
        const carrito = obtenerCarrito().filter(function (p) { return p.id !== id; });
        guardarCarrito(carrito);
    }

    function cambiarCantidad(id, delta) {
        const carrito = obtenerCarrito();
        const item = carrito.find(function (p) { return p.id === id; });
        if (!item) return;

        item.cantidad += delta;
        if (item.cantidad <= 0) {
            guardarCarrito(carrito.filter(function (p) { return p.id !== id; }));
        } else {
            guardarCarrito(carrito);
        }
    }

    function renderCarrito() {
        const carrito = obtenerCarrito();
        const $lista = $('#listaCarrito');
        const totalUnidades = carrito.reduce(function (s, p) { return s + p.cantidad; }, 0);
        const totalMonto = carrito.reduce(function (s, p) { return s + (p.cantidad * p.precio); }, 0);

        $('#badgeCarrito').text(totalUnidades);
        $('#totalCarrito').text(formatoCordoba(totalMonto));

        if (carrito.length === 0) {
            $lista.empty();
            $('#carritoVacio').show();
            return;
        }

        $('#carritoVacio').hide();

        $lista.html(carrito.map(function (p) {
            const img = p.imagen ? escapeHtml(p.imagen) : IMAGEN_PLACEHOLDER;
            const referencia = referenciaProducto(p);
            return (
                '<div class="item-carrito" data-id="' + p.id + '">' +
                '<img src="' + img + '" loading="lazy" decoding="async" ' +
                'onerror="this.src=\'' + IMAGEN_PLACEHOLDER + '\'">' +
                '<div class="flex-grow-1">' +
                '<div class="fw-semibold small">' + escapeHtml(p.nombre) + '</div>' +
                (referencia ? '<div class="text-muted" style="font-size:0.72rem;">' + escapeHtml(referencia) + '</div>' : '') +
                '<div class="text-muted small">' + formatoCordoba(p.precio) + ' c/u</div>' +
                '<div class="cantidad-control mt-1">' +
                '<button type="button" class="btn-restar" data-id="' + p.id + '">−</button>' +
                '<span>' + p.cantidad + '</span>' +
                '<button type="button" class="btn-sumar" data-id="' + p.id + '">+</button>' +
                '<button type="button" class="btn-quitar" data-id="' + p.id + '" title="Quitar"><i class="bi bi-x-lg"></i></button>' +
                '</div></div></div>'
            );
        }).join(''));
    }

    function obtenerNombreCliente() {
        return localStorage.getItem('catalogoNombreCliente') || '';
    }

    function guardarNombreCliente(nombre) {
        localStorage.setItem('catalogoNombreCliente', nombre);
    }

    function mostrarConfirmacionPedido() {
        const carrito = obtenerCarrito();
        if (carrito.length === 0) return;

        const nombre = $('#inputNombreCliente').val().trim();
        guardarNombreCliente(nombre);

        const $resumenNombre = $('#resumenConfirmarNombre');
        if (nombre !== '') {
            $resumenNombre.html('<i class="bi bi-person"></i> ' + escapeHtml(nombre)).show();
        } else {
            $resumenNombre.hide();
        }

        $('#resumenConfirmarPedido').html(carrito.map(function (p) {
            const referencia = referenciaProducto(p);
            return '<li class="mb-1">' + p.cantidad + '× ' + escapeHtml(p.nombre) +
                (referencia ? ' <span class="text-muted">(' + escapeHtml(referencia) + ')</span>' : '') +
                ' — ' + formatoCordoba(p.precio * p.cantidad) + '</li>';
        }).join(''));

        const total = carrito.reduce(function (s, p) { return s + (p.cantidad * p.precio); }, 0);
        $('#totalConfirmarPedido').text(formatoCordoba(total));

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmarPedido')).show();
    }

    function enviarPedidoWhatsapp() {
        const carrito = obtenerCarrito();
        if (carrito.length === 0) return;

        const nombre = obtenerNombreCliente();

        let mensaje = '*Pedido desde el catálogo web*\n\n';
        if (nombre !== '') {
            mensaje += '*Cliente:* ' + nombre + '\n\n';
        }
        carrito.forEach(function (p) {
            const referencia = referenciaProducto(p);
            mensaje += '• ' + p.nombre + (referencia ? ' (' + referencia + ')' : '') +
                ' x' + p.cantidad + ' — ' + formatoCordoba(p.precio * p.cantidad) + '\n\n';
        });
        const total = carrito.reduce(function (s, p) { return s + (p.cantidad * p.precio); }, 0);
        mensaje += '*Total: ' + formatoCordoba(total) + '*';

        const url = 'https://wa.me/' + WHATSAPP_NUMERO + '?text=' + encodeURIComponent(mensaje);
        window.open(url, '_blank', 'noopener,noreferrer');

        // El envío por WhatsApp equivale al "checkout" de este carrito: de
        // acá en adelante la negociación sigue en el chat con la tienda.
        // El nombre del cliente NO se borra (queda para la próxima visita).
        guardarCarrito([]);

        const modalEl = document.getElementById('modalConfirmarPedido');
        const instanciaModal = bootstrap.Modal.getInstance(modalEl);
        if (instanciaModal) instanciaModal.hide();

        const offcanvasEl = document.getElementById('offcanvasCarrito');
        const instanciaOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
        if (instanciaOffcanvas) instanciaOffcanvas.hide();
    }

    // =====================================================================
    //  FILTROS (marca / categoría / departamento / precio)
    // =====================================================================

    function cargarMeta() {
        $.ajax({
            url: 'catalogo_data.php',
            method: 'GET',
            data: { accion: 'meta' },
            dataType: 'json',
            success: function (data) {
                if (!data || !data.success) return;

                renderListaFiltro('#listaMarcas', data.marcas, 'marca');
                renderListaFiltro('#listaCategorias', data.categorias, 'categoria');
                renderListaFiltro('#listaDepartamentos', data.departamentos, 'departamento');

                estado.precioMin = Math.floor(data.precio_min || 0);
                estado.precioMax = Math.ceil(data.precio_max || 0);
                estado.precioMinSel = estado.precioMin;
                estado.precioMaxSel = estado.precioMax;

                inicializarSliderPrecio();
                cargarProductos(1);
            },
            error: function () {
                $('#textoResultados').text('No se pudo cargar el catálogo.');
            }
        });
    }

    function renderListaFiltro(selector, valores, grupo) {
        const $contenedor = $(selector);
        if (!Array.isArray(valores) || valores.length === 0) {
            $contenedor.html('<div class="text-muted small">Sin opciones</div>');
            return;
        }

        $contenedor.html(valores.map(function (valor, i) {
            const id = grupo + 'Chk' + i;
            return (
                '<div class="form-check">' +
                '<input class="form-check-input filtro-check" type="checkbox" value="' + escapeHtml(valor) + '" ' +
                'data-grupo="' + grupo + '" id="' + id + '">' +
                '<label class="form-check-label" for="' + id + '">' + escapeHtml(valor) + '</label>' +
                '</div>'
            );
        }).join(''));
    }

    function inicializarSliderPrecio() {
        const $min = $('#rangoMin');
        const $max = $('#rangoMax');

        $min.attr({ min: estado.precioMin, max: estado.precioMax }).val(estado.precioMin);
        $max.attr({ min: estado.precioMin, max: estado.precioMax }).val(estado.precioMax);

        actualizarVisualSlider();
    }

    function actualizarVisualSlider() {
        const min = parseFloat($('#rangoMin').val());
        const max = parseFloat($('#rangoMax').val());
        const rango = (estado.precioMax - estado.precioMin) || 1;

        const pctMin = ((min - estado.precioMin) / rango) * 100;
        const pctMax = ((max - estado.precioMin) / rango) * 100;

        $('#rangoRelleno').css({ left: pctMin + '%', width: (pctMax - pctMin) + '%' });
        $('#valorRangoMin').text(formatoCordoba(min));
        $('#valorRangoMax').text(formatoCordoba(max));

        estado.precioMinSel = min;
        estado.precioMaxSel = max;
    }

    function obtenerSeleccionados(grupo) {
        return $('.filtro-check[data-grupo="' + grupo + '"]:checked')
            .map(function () { return $(this).val(); })
            .get();
    }

    // =====================================================================
    //  LISTADO DE PRODUCTOS
    // =====================================================================

    function cargarProductos(pagina) {
        if (estado.cargando) return;
        estado.cargando = true;
        estado.pagina = pagina || 1;

        const params = {
            accion: 'listar',
            buscar: estado.buscar,
            orden: estado.orden,
            pagina: estado.pagina,
            por_pagina: 24,
            precio_min: estado.precioMinSel,
            precio_max: estado.precioMaxSel
        };

        const marcas = obtenerSeleccionados('marca');
        const categorias = obtenerSeleccionados('categoria');
        const departamentos = obtenerSeleccionados('departamento');

        $('#textoResultados').text('Cargando productos...');

        $.ajax({
            url: 'catalogo_data.php',
            method: 'GET',
            data: $.param(params) +
                marcas.map(function (m) { return '&marca[]=' + encodeURIComponent(m); }).join('') +
                categorias.map(function (c) { return '&categoria[]=' + encodeURIComponent(c); }).join('') +
                departamentos.map(function (d) { return '&departamento[]=' + encodeURIComponent(d); }).join(''),
            dataType: 'json',
            success: function (data) {
                estado.cargando = false;
                if (!data || !data.success) {
                    $('#textoResultados').text('Ocurrió un error al cargar los productos.');
                    return;
                }
                renderGrid(data.productos || []);
                renderPaginacion(data.pagina, data.total_paginas);
                $('#textoResultados').text('Catálogo de Productos: ' + data.total + ' producto(s) encontrado(s)');
            },
            error: function () {
                estado.cargando = false;
                $('#textoResultados').text('Error de conexión con el catálogo.');
            }
        });
    }

    function renderGrid(productos) {
        const $grid = $('#gridProductos');

        if (!productos.length) {
            $grid.empty();
            $('#sinResultados').show();
            return;
        }

        $('#sinResultados').hide();

        $grid.html(productos.map(function (p) {
            const img = p.imagen_principal ? escapeHtml(p.imagen_principal) : IMAGEN_PLACEHOLDER;
            const productoJson = JSON.stringify(p).replace(/'/g, '&#39;');
            return (
                '<div class="col">' +
                '<div class="tarjeta-producto" data-producto=\'' + productoJson + '\'>' +
                '<div class="tarjeta-imagen abrir-detalle">' +
                '<img src="' + img + '" loading="lazy" decoding="async" ' +
                'onerror="this.src=\'' + IMAGEN_PLACEHOLDER + '\'">' +
                '</div>' +
                '<div class="tarjeta-disponible-fila">' +
                '<div class="badge-disponible"><i class="bi bi-check-circle-fill"></i> Disponible</div>' +
                '</div>' +
                '<div class="tarjeta-cuerpo">' +
                '<div class="tarjeta-marca">' + escapeHtml(p.marca || '') + '</div>' +
                '<div class="tarjeta-nombre abrir-detalle">' + escapeHtml(p.nombre) + '</div>' +
                (p.num_parte ? '<div class="tarjeta-num-parte">No. Parte: ' + escapeHtml(p.num_parte) + '</div>' : '') +
                (p.unidad ? '<div class="tarjeta-unidad">Unidad: ' + escapeHtml(p.unidad) + '</div>' : '') +
                '<div class="tarjeta-precio">' + formatoCordoba(p.precio_venta_cordoba) + '</div>' +
                '<div class="tarjeta-acciones">' +
                '<button type="button" class="btn-ver-detalle abrir-detalle"><i class="bi bi-eye"></i> Ver</button>' +
                '<button type="button" class="btn-agregar-carrito btn-agregar-directo"><i class="bi bi-cart-plus"></i> Agregar</button>' +
                '</div>' +
                '</div></div></div>'
            );
        }).join(''));
    }

    function renderPaginacion(paginaActual, totalPaginas) {
        const $pag = $('#paginacion');
        $pag.empty();

        if (totalPaginas <= 1) return;

        const crearItem = function (etiqueta, pagina, disabled, activo) {
            return (
                '<li class="page-item' + (disabled ? ' disabled' : '') + (activo ? ' active' : '') + '">' +
                '<a class="page-link" href="#" data-pagina="' + pagina + '">' + etiqueta + '</a></li>'
            );
        };

        let html = crearItem('«', paginaActual - 1, paginaActual <= 1, false);

        const desde = Math.max(1, paginaActual - 2);
        const hasta = Math.min(totalPaginas, paginaActual + 2);

        for (let i = desde; i <= hasta; i++) {
            html += crearItem(i, i, false, i === paginaActual);
        }

        html += crearItem('»', paginaActual + 1, paginaActual >= totalPaginas, false);

        $pag.html(html);
    }

    // =====================================================================
    //  MODAL DE DETALLE / GALERÍA
    // =====================================================================

    function abrirModalProducto(id, datosProducto) {
        estado.modalProductoActual = datosProducto;

        $('#modalProductoMarca').text(datosProducto.marca || '');
        $('#modalProductoNombre').text(datosProducto.nombre || '');
        $('#modalProductoPrecio').text(formatoCordoba(datosProducto.precio_venta_cordoba));

        const descripcion = (datosProducto.comentarios || '').trim();
        if (descripcion !== '') {
            $('#modalProductoDescripcion').text(descripcion);
            $('#modalProductoDescripcionWrap').show();
        } else {
            $('#modalProductoDescripcionWrap').hide();
        }

        if (datosProducto.num_parte) {
            $('#modalProductoNumParte').text('No. Parte: ' + datosProducto.num_parte);
            $('#modalProductoNumParteWrap').show();
        } else {
            $('#modalProductoNumParteWrap').hide();
        }

        if (datosProducto.unidad) {
            $('#modalProductoUnidad').text(datosProducto.unidad);
            $('#modalProductoUnidadWrap').show();
        } else {
            $('#modalProductoUnidadWrap').hide();
        }

        const imagenPrincipal = datosProducto.imagen_principal || IMAGEN_PLACEHOLDER;
        renderMiniaturasProducto([imagenPrincipal]);

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalProducto'));
        modal.show();

        $.ajax({
            url: 'catalogo_data.php',
            method: 'GET',
            data: { accion: 'imagenes', id: id },
            dataType: 'json',
            success: function (data) {
                if (!data || !data.success || !Array.isArray(data.imagenes) || data.imagenes.length === 0) {
                    return;
                }
                const todas = [imagenPrincipal].concat(
                    data.imagenes.filter(function (url) { return url !== imagenPrincipal; })
                );
                renderMiniaturasProducto(todas);
            }
        });
    }

    function renderMiniaturasProducto(imagenes) {
        $('#modalImagenPrincipal')
            .attr('src', imagenes[0] || IMAGEN_PLACEHOLDER)
            .off('error')
            .on('error', function () { this.src = IMAGEN_PLACEHOLDER; });

        const $miniaturas = $('#modalMiniaturas');

        if (imagenes.length <= 1) {
            $miniaturas.empty();
            return;
        }

        $miniaturas.html(imagenes.map(function (url, i) {
            return '<img src="' + escapeHtml(url) + '" class="detalle-miniatura' + (i === 0 ? ' activa' : '') +
                '" data-src="' + escapeHtml(url) + '" loading="lazy" ' +
                'onerror="this.src=\'' + IMAGEN_PLACEHOLDER + '\'">';
        }).join(''));
    }

    function cerrarOffcanvasFiltrosSiAbierto() {
        const el = document.getElementById('offcanvasFiltros');
        if (!el) return;
        const instancia = bootstrap.Offcanvas.getInstance(el);
        if (instancia) {
            instancia.hide();
        }
    }

    function aplicarFiltros() {
        estado.buscar = $('#inputBuscar').val().trim();
        cargarProductos(1);
        cerrarOffcanvasFiltrosSiAbierto();
    }

    // =====================================================================
    //  COMPARTIR: composición del QR en canvas (logo + nombre + leyenda)
    // =====================================================================

    // Guarda el <canvas> generado más recientemente para poder descargarlo.
    let canvasQrCompartir = null;

    function envolverTexto(ctx, texto, anchoMaximo) {
        const palabras = texto.split(' ');
        const lineas = [];
        let lineaActual = '';
        palabras.forEach((palabra) => {
            const prueba = lineaActual ? lineaActual + ' ' + palabra : palabra;
            if (ctx.measureText(prueba).width > anchoMaximo && lineaActual) {
                lineas.push(lineaActual);
                lineaActual = palabra;
            } else {
                lineaActual = prueba;
            }
        });
        if (lineaActual) lineas.push(lineaActual);
        return lineas;
    }

    function generarCanvasQr(enlace, logoImg) {
        const qr = qrcode(0, 'M');
        qr.addData(enlace);
        qr.make();
        const moduleCount = qr.getModuleCount();

        const anchoCanvas = 300;
        const margenLateral = 30;
        const zonaQuietaCeldas = 2;
        const cellSize = Math.floor((anchoCanvas - margenLateral * 2) / (moduleCount + zonaQuietaCeldas * 2));
        const qrSize = cellSize * (moduleCount + zonaQuietaCeldas * 2);
        const anchoUtil = anchoCanvas - margenLateral * 2;

        const nombreEmpresa = (typeof EMPRESA_NOMBRE !== 'undefined') ? EMPRESA_NOMBRE.trim() : '';
        const leyenda = nombreEmpresa
            ? 'Escaneá el código para ver el catálogo de productos de ' + nombreEmpresa
            : 'Escaneá el código para ver nuestro catálogo de productos';

        // Canvas temporal solo para medir el texto de la leyenda antes de fijar el tamaño final.
        const medidor = document.createElement('canvas').getContext('2d');
        medidor.font = '13px Arial, sans-serif';
        const lineasLeyenda = envolverTexto(medidor, leyenda, anchoUtil);

        let logoAncho = 0;
        let logoAlto = 0;
        if (logoImg) {
            const logoAltoMax = 60;
            const ratio = logoImg.naturalWidth / logoImg.naturalHeight;
            logoAlto = logoAltoMax;
            logoAncho = logoAltoMax * ratio;
            if (logoAncho > anchoUtil) {
                logoAncho = anchoUtil;
                logoAlto = logoAncho / ratio;
            }
        }

        // Bloques a dibujar, de arriba a abajo, cada uno con su alto en px.
        const bloques = [{ tipo: 'espacio', alto: 22 }];
        if (logoImg) {
            bloques.push({ tipo: 'logo', alto: logoAlto, ancho: logoAncho });
            bloques.push({ tipo: 'espacio', alto: 10 });
        }
        if (nombreEmpresa) {
            bloques.push({ tipo: 'nombre', alto: 22, texto: nombreEmpresa });
            bloques.push({ tipo: 'espacio', alto: 14 });
        }
        bloques.push({ tipo: 'qr', alto: qrSize });
        bloques.push({ tipo: 'espacio', alto: 16 });
        lineasLeyenda.forEach((linea) => {
            bloques.push({ tipo: 'linea', alto: 18, texto: linea });
        });
        bloques.push({ tipo: 'espacio', alto: 22 });

        const altoCanvas = bloques.reduce((suma, b) => suma + b.alto, 0);

        const canvas = document.createElement('canvas');
        canvas.width = anchoCanvas;
        canvas.height = altoCanvas;
        const ctx = canvas.getContext('2d');

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, anchoCanvas, altoCanvas);
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        let y = 0;
        bloques.forEach((bloque) => {
            const centroY = y + bloque.alto / 2;
            if (bloque.tipo === 'logo') {
                ctx.drawImage(logoImg, (anchoCanvas - bloque.ancho) / 2, y, bloque.ancho, bloque.alto);
            } else if (bloque.tipo === 'nombre') {
                ctx.fillStyle = '#d35400';
                ctx.font = '700 16px Arial, sans-serif';
                ctx.fillText(bloque.texto, anchoCanvas / 2, centroY);
            } else if (bloque.tipo === 'qr') {
                const offsetX = (anchoCanvas - qrSize) / 2 + zonaQuietaCeldas * cellSize;
                ctx.fillStyle = '#1a1a1a';
                for (let fila = 0; fila < moduleCount; fila++) {
                    for (let col = 0; col < moduleCount; col++) {
                        if (qr.isDark(fila, col)) {
                            ctx.fillRect(offsetX + col * cellSize, y + zonaQuietaCeldas * cellSize + fila * cellSize, cellSize, cellSize);
                        }
                    }
                }
            } else if (bloque.tipo === 'linea') {
                ctx.fillStyle = '#5a6473';
                ctx.font = '13px Arial, sans-serif';
                ctx.fillText(bloque.texto, anchoCanvas / 2, centroY);
            }
            y += bloque.alto;
        });

        return canvas;
    }

    function renderizarModalCompartir(enlace) {
        const contenedorQr = document.getElementById('compartirQr');
        contenedorQr.innerHTML = '<div class="text-muted small py-4">Generando QR...</div>';

        const logoImg = new Image();
        logoImg.onload = function () {
            const canvas = generarCanvasQr(enlace, logoImg);
            canvasQrCompartir = canvas;
            contenedorQr.innerHTML = '';
            contenedorQr.appendChild(canvas);
        };
        logoImg.onerror = function () {
            const canvas = generarCanvasQr(enlace, null);
            canvasQrCompartir = canvas;
            contenedorQr.innerHTML = '';
            contenedorQr.appendChild(canvas);
        };
        logoImg.src = 'assets/img/logo.png';
    }

    // =====================================================================
    //  EVENTOS
    // =====================================================================

    $(document).ready(function () {
        renderCarrito();
        cargarMeta();
        $('#inputNombreCliente').val(obtenerNombreCliente());

        // La búsqueda, marca/categoría/departamento y el rango de precio ya
        // NO disparan la consulta solos (era muy pesado para la db con cada
        // clic). Solo se aplican cuando se presiona "Aplicar filtros" (o
        // Enter en el buscador). El slider sí actualiza su parte visual en
        // vivo, pero sin consultar todavía.

        // Botón de la lupa y Enter en el buscador == "Aplicar filtros"
        $('#btnBuscar').on('click', aplicarFiltros);
        $('#inputBuscar').on('keypress', function (e) {
            if (e.which === 13) {
                aplicarFiltros();
            }
        });

        // Mostrar/ocultar la "x" de limpiar búsqueda según haya texto o no
        $('#inputBuscar').on('input', function () {
            $('#btnLimpiarBuscar').toggle($(this).val().length > 0);
        });

        // Limpiar solo la búsqueda (no toca el resto de filtros) y re-aplicar
        $('#btnLimpiarBuscar').on('click', function () {
            $('#inputBuscar').val('');
            $(this).hide();
            aplicarFiltros();
        });

        // Slider de precio: solo visual mientras se arrastra
        $('#rangoMin, #rangoMax').on('input', function () {
            const min = parseFloat($('#rangoMin').val());
            const max = parseFloat($('#rangoMax').val());

            if (min > max) {
                if (this.id === 'rangoMin') {
                    $('#rangoMin').val(max);
                } else {
                    $('#rangoMax').val(min);
                }
            }
            actualizarVisualSlider();
        });

        // Orden: este sí se aplica al momento, es una sola acción puntual
        $('#selectOrden').on('change', function () {
            estado.orden = $(this).val();
            cargarProductos(1);
        });

        // Aplicar filtros (marca, categoría, departamento, precio, buscar)
        $('#btnAplicarFiltros').on('click', aplicarFiltros);

        // Limpiar filtros
        $('#btnLimpiarFiltros').on('click', function () {
            $('.filtro-check').prop('checked', false);
            $('#inputBuscar').val('');
            $('#btnLimpiarBuscar').hide();
            estado.buscar = '';
            $('#rangoMin').val(estado.precioMin);
            $('#rangoMax').val(estado.precioMax);
            actualizarVisualSlider();
            estado.orden = 'relevancia';
            $('#selectOrden').val('relevancia');
            cargarProductos(1);
            cerrarOffcanvasFiltrosSiAbierto();
        });

        // Paginación
        $(document).on('click', '#paginacion a.page-link', function (e) {
            e.preventDefault();
            const pagina = parseInt($(this).data('pagina'), 10);
            if (!pagina || pagina < 1) return;
            cargarProductos(pagina);
            $('html, body').animate({ scrollTop: 0 }, 200);
        });

        // Abrir modal de detalle
        $(document).on('click', '.abrir-detalle', function () {
            const producto = $(this).closest('.tarjeta-producto').data('producto');
            if (!producto) return;
            abrirModalProducto(producto.id, producto);
        });

        // Cambiar imagen principal del modal al hacer clic en una miniatura
        $(document).on('click', '.detalle-miniatura', function () {
            $('.detalle-miniatura').removeClass('activa');
            $(this).addClass('activa');
            $('#modalImagenPrincipal').attr('src', $(this).data('src'));
        });

        // Agregar al carrito desde la tarjeta
        $(document).on('click', '.btn-agregar-directo', function () {
            const producto = $(this).closest('.tarjeta-producto').data('producto');
            if (!producto) return;
            agregarAlCarrito(producto);
        });

        // Agregar al carrito desde el modal
        $('#btnAgregarDesdeModal').on('click', function () {
            if (!estado.modalProductoActual) return;
            agregarAlCarrito(estado.modalProductoActual);
        });

        // Carrito: sumar / restar / quitar (delegado)
        $(document).on('click', '.btn-sumar', function () {
            cambiarCantidad(parseInt($(this).data('id'), 10), 1);
        });
        $(document).on('click', '.btn-restar', function () {
            cambiarCantidad(parseInt($(this).data('id'), 10), -1);
        });
        $(document).on('click', '.btn-quitar', function () {
            quitarDelCarrito(parseInt($(this).data('id'), 10));
        });

        // Vaciar carrito completo
        $('#btnVaciarCarrito').on('click', function () {
            if (obtenerCarrito().length === 0) return;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmarVaciar')).show();
        });

        $('#btnConfirmarVaciar').on('click', function () {
            guardarCarrito([]);
            guardarNombreCliente('');
            $('#inputNombreCliente').val('');
            const instancia = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarVaciar'));
            if (instancia) instancia.hide();
        });

        // Enviar pedido
        $('#btnEnviarPedido').on('click', mostrarConfirmacionPedido);
        $('#btnConfirmarEnviarPedido').on('click', enviarPedidoWhatsapp);

        // Mover el panel de filtros al offcanvas en móvil, y devolverlo al cerrarlo
        $('#offcanvasFiltros').on('show.bs.offcanvas', function () {
            $('#offcanvasFiltrosBody').append($('.col-filtros').children());
        });
        $('#offcanvasFiltros').on('hidden.bs.offcanvas', function () {
            $('.col-filtros').append($('#offcanvasFiltrosBody').children());
        });

        // Compartir catálogo: QR (con logo, nombre y leyenda) + enlace
        $('#modalCompartir').on('show.bs.modal', function () {
            const enlace = (typeof CATALOGO_URL !== 'undefined' && CATALOGO_URL)
                ? CATALOGO_URL
                : window.location.origin + window.location.pathname;
            $('#inputEnlaceCompartir').val(enlace);
            $('#textoCopiado').hide();

            if (typeof qrcode === 'function') {
                renderizarModalCompartir(enlace);
            }
        });

        $('#btnDescargarQr').on('click', function () {
            if (!canvasQrCompartir) return;
            const nombreArchivo = 'qr-catalogo' +
                ((typeof EMPRESA_NOMBRE !== 'undefined' && EMPRESA_NOMBRE)
                    ? '-' + EMPRESA_NOMBRE.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')
                    : '') + '.png';
            const enlaceDescarga = document.createElement('a');
            enlaceDescarga.href = canvasQrCompartir.toDataURL('image/png');
            enlaceDescarga.download = nombreArchivo;
            document.body.appendChild(enlaceDescarga);
            enlaceDescarga.click();
            document.body.removeChild(enlaceDescarga);
        });

        $('#btnCopiarEnlace').on('click', function () {
            const enlace = $('#inputEnlaceCompartir').val();
            const mostrarCopiado = () => {
                $('#textoCopiado').stop(true).show();
                setTimeout(() => $('#textoCopiado').fadeOut(), 2000);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(enlace).then(mostrarCopiado);
            } else {
                const campo = document.getElementById('inputEnlaceCompartir');
                campo.select();
                campo.setSelectionRange(0, 99999);
                document.execCommand('copy');
                mostrarCopiado();
            }
        });
    });

})();
