<?php
require_once __DIR__ . '/config.php';

/**
 * Le agrega ?v=<fecha de modificación del archivo> a un asset local (css/js).
 * Así, cada vez que se edita style.css o catalogo.js, la URL cambia y el
 * navegador del cliente descarga la versión nueva automáticamente, sin
 * necesidad de pedirle a nadie que borre la caché.
 */
function assetVersionado($rutaRelativa)
{
    $rutaAbsoluta = __DIR__ . '/' . $rutaRelativa;
    $version = file_exists($rutaAbsoluta) ? filemtime($rutaAbsoluta) : time();
    return $rutaRelativa . '?v=' . $version;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cpsystems Catálogo</title>

    <!-- Nombre corto al agregar la web como app al celular (icono + pantalla de inicio) -->
    <link rel="manifest" href="<?php echo assetVersionado('manifest.json'); ?>">
    <meta name="application-name" content="Cpsystems Catálogo">
    <meta name="apple-mobile-web-app-title" content="Cpsystems Catálogo">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#d35400">

    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo assetVersionado('assets/img/icons/icon-192.png'); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo assetVersionado('assets/img/icons/icon-512.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo assetVersionado('assets/img/icons/apple-touch-icon.png'); ?>">

    <link rel="stylesheet" href="plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="plugins/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo assetVersionado('assets/css/style.css'); ?>">
</head>

<body>

    <div class="catalogo-header">
        <div class="marca">
            <img src="assets/img/logo.png" alt="Logo" class="logo-empresa">
            <span><?php echo htmlspecialchars(EMPRESA_NOMBRE, ENT_QUOTES) ?: 'Catálogo de Productos'; ?></span>
        </div>
        <div class="d-flex align-items-center gap-2 catalogo-header-acciones">
            <button type="button" class="btn btn-sm btn-light btn-header-accion" id="btnCompartirCatalogo" data-bs-toggle="modal"
                data-bs-target="#modalCompartir">
                <i class="bi bi-share"></i> Compartir
            </button>
            <button type="button" class="btn btn-sm btn-light btn-header-accion offcanvas-filtros-toggle" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasFiltros">
                <i class="bi bi-funnel"></i> Búsqueda
            </button>
        </div>
    </div>

    <div class="catalogo-wrap">
        <div class="row g-3">

            <!-- ============ SIDEBAR DE FILTROS (desktop) ============ -->
            <div class="col-lg-2 col-filtros">
                <div class="panel">
                    <h6>Buscar</h6>
                    <div class="buscador-box mb-1">
                        <input type="text" id="inputBuscar" placeholder="Nombre o N° de parte...">
                        <button type="button" id="btnLimpiarBuscar" class="btn-limpiar-buscar" title="Limpiar búsqueda"
                            style="display:none;">
                            <i class="bi bi-x-lg"></i>
                        </button>
                        <button type="button" id="btnBuscar"><i class="bi bi-search"></i></button>
                    </div>
                </div>

                <div class="panel mt-3">
                    <div class="filtro-bloque">
                        <h6>Marca</h6>
                        <div class="filtro-lista" id="listaMarcas"></div>
                    </div>

                    <div class="filtro-bloque">
                        <h6>Categoría</h6>
                        <div class="filtro-lista" id="listaCategorias"></div>
                    </div>

                    <div class="filtro-bloque">
                        <h6>Departamento</h6>
                        <div class="filtro-lista" id="listaDepartamentos"></div>
                    </div>

                    <!-- Filtro de precio: oculto por ahora (no se necesita todavía),
                         pero se deja el markup/JS intacto. Para reactivarlo, quitar
                         el style="display:none;" de abajo. -->
                    <div class="filtro-bloque" style="display:none;">
                        <h6>Precio (C$)</h6>
                        <div class="rango-precio-track">
                            <div class="rango-precio-base"></div>
                            <div class="rango-precio-relleno" id="rangoRelleno"></div>
                            <input type="range" id="rangoMin" class="rango-slider">
                            <input type="range" id="rangoMax" class="rango-slider">
                        </div>
                        <div class="rango-precio-valores">
                            <span id="valorRangoMin">C$ 0</span>
                            <span id="valorRangoMax">C$ 0</span>
                        </div>
                    </div>

                    <button type="button" id="btnAplicarFiltros" class="btn btn-aplicar-filtros mb-2">
                        Aplicar filtros
                    </button>
                    <button type="button" id="btnLimpiarFiltros" class="btn btn-outline-secondary btn-sm">
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- ============ CONTENIDO PRINCIPAL ============ -->
            <div class="col-lg-10">
                <div class="toolbar-resultados">
                    <div id="textoResultados" class="text-muted small">Cargando productos...</div>
                    <select id="selectOrden">
                        <option value="relevancia">Más recientes</option>
                        <option value="nombre">Nombre A-Z</option>
                        <option value="precio_asc">Precio: menor a mayor</option>
                        <option value="precio_desc">Precio: mayor a menor</option>
                    </select>
                </div>

                <div id="gridProductos" class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3"></div>

                <div id="sinResultados" class="sin-resultados" style="display:none;">
                    <i class="bi bi-search" style="font-size:2.5rem;"></i>
                    <p class="mb-0">No hay productos que coincidan con los filtros seleccionados.</p>
                </div>

                <nav class="mt-4">
                    <ul id="paginacion" class="pagination justify-content-center"></ul>
                </nav>
            </div>

        </div>
    </div>

    <!-- ============ FOOTER: datos de la empresa ============ -->
    <footer class="catalogo-footer">
        <div class="catalogo-footer-inner">
            <img src="assets/img/logo.png" alt="Logo" class="logo-footer">
            <div class="footer-info">
                <?php if (EMPRESA_NOMBRE !== ''): ?>
                    <div class="footer-nombre"><?php echo htmlspecialchars(EMPRESA_NOMBRE, ENT_QUOTES); ?></div>
                <?php endif; ?>
                <?php if (EMPRESA_DIRECCION !== ''): ?>
                    <div class="footer-dato"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars(EMPRESA_DIRECCION, ENT_QUOTES); ?></div>
                <?php endif; ?>
                <?php if (EMPRESA_TELEFONO !== ''): ?>
                    <div class="footer-dato"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars(EMPRESA_TELEFONO, ENT_QUOTES); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="footer-aviso-privacidad">
            <i class="bi bi-shield-lock"></i>
            Tu nombre y tu carrito se guardan solo en este dispositivo. Los pedidos se envían directo por WhatsApp y no se almacenan en este sitio.
        </div>
    </footer>

    <!-- ============ OFFCANVAS DE FILTROS (mobile) ============ -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasFiltros">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Búsqueda</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body" id="offcanvasFiltrosBody">
            <!-- Se clona el contenido del panel de filtros por JS -->
        </div>
    </div>

    <!-- ============ MODAL GALERÍA / DETALLE ============ -->
    <div class="modal fade" id="modalProducto" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-seam"></i> Detalle de producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-5">
                            <div class="detalle-galeria d-flex gap-2">
                                <div id="modalMiniaturas" class="d-flex flex-column gap-2"></div>
                                <div class="detalle-imagen-principal flex-grow-1">
                                    <img id="modalImagenPrincipal" src="" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="detalle-marca" id="modalProductoMarca"></div>
                            <h4 id="modalProductoNombre" class="mb-1"></h4>
                            <div id="modalProductoNumParteWrap" class="mb-3" style="display:none;">
                                <span class="tarjeta-num-parte" id="modalProductoNumParte"></span>
                            </div>
                            <div id="modalProductoUnidadWrap" class="mb-2" style="display:none;">
                                <span class="text-muted small">Presentación: <strong id="modalProductoUnidad"></strong></span>
                            </div>
                            <div id="modalProductoPrecioOfertaWrap" class="detalle-precio-oferta-fila" style="display:none;">
                                <span class="detalle-precio-oferta" id="modalProductoPrecioOferta"></span>
                                <span class="badge-oferta"><i class="bi bi-tag-fill"></i> Oferta</span>
                            </div>
                            <div class="detalle-precio-banner" id="modalProductoPrecio">C$ 0.00</div>
                            <div id="modalProductoDescripcionWrap" class="mt-3" style="display:none;">
                                <h6 class="text-uppercase small fw-bold text-muted">Descripción</h6>
                                <p id="modalProductoDescripcion" class="small detalle-descripcion"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-agregar-carrito" style="width:auto; padding:10px 24px;"
                        id="btnAgregarDesdeModal">
                        <i class="bi bi-cart-plus"></i> Agregar al carrito
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ BOTÓN + OFFCANVAS DEL CARRITO ============ -->
    <button type="button" class="btn-carrito-flotante" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCarrito">
        <i class="bi bi-cart3"></i> <span class="badge rounded-pill" id="badgeCarrito">0</span>
    </button>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCarrito">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Tu pedido</h5>
            <div class="d-flex align-items-center gap-2 ms-auto">
                <button type="button" id="btnVaciarCarrito" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash3"></i> Vaciar
                </button>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            <div id="listaCarrito" class="flex-grow-1"></div>
            <div id="carritoVacio" class="text-muted text-center py-4">
                Todavía no has agregado productos.
            </div>
            <div class="border-top pt-3 mt-2">
                <div class="mb-3">
                    <label for="inputNombreCliente" class="form-label small fw-semibold mb-1">
                        <i class="bi bi-person"></i> Tu nombre (opcional)
                    </label>
                    <input type="text" id="inputNombreCliente" class="form-control form-control-sm"
                        placeholder="Ej: Juan Pérez">
                </div>
                <div class="d-flex justify-content-between fw-bold mb-3">
                    <span>Total</span>
                    <span id="totalCarrito">C$ 0.00</span>
                </div>
                <button type="button" id="btnEnviarPedido" class="btn text-white">
                    <i class="bi bi-whatsapp"></i> Enviar pedido por WhatsApp
                </button>
            </div>
        </div>
    </div>

    <!-- ============ MODAL DE CONFIRMACIÓN PARA VACIAR CARRITO ============ -->
    <div class="modal fade" id="modalConfirmarVaciar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-trash3 text-danger"></i> Vaciar carrito</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Se eliminarán todos los productos del carrito. ¿Continuar?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnConfirmarVaciar" class="btn btn-danger">
                        <i class="bi bi-trash3"></i> Sí, vaciar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ MODAL DE CONFIRMACIÓN DE PEDIDO ============ -->
    <div class="modal fade" id="modalConfirmarPedido" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-whatsapp text-success"></i> Confirmar cotización</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Se va a enviar esta cotización por WhatsApp:</p>
                    <div id="resumenConfirmarNombre" class="small text-muted mb-2" style="display:none;"></div>
                    <ul id="resumenConfirmarPedido" class="list-unstyled small mb-2"></ul>
                    <div class="d-flex justify-content-between fw-bold border-top pt-2">
                        <span>Total</span>
                        <span id="totalConfirmarPedido">C$ 0.00</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnConfirmarEnviarPedido" class="btn text-white" style="background:#25D366;">
                        <i class="bi bi-whatsapp"></i> Confirmar y enviar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ MODAL COMPARTIR (QR + enlace) ============ -->
    <div class="modal fade" id="modalCompartir" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-share"></i> Compartir catálogo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="compartirQr" class="compartir-qr-wrap mb-2"></div>
                    <button type="button" id="btnDescargarQr" class="btn btn-outline-secondary btn-sm mb-3">
                        <i class="bi bi-download"></i> Descargar QR
                    </button>
                    <div class="input-group">
                        <input type="text" id="inputEnlaceCompartir" class="form-control form-control-sm" readonly>
                        <button type="button" id="btnCopiarEnlace" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    <div id="textoCopiado" class="small text-success mt-2" style="display:none;">
                        <i class="bi bi-check-circle-fill"></i> Enlace copiado
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ TOAST: producto agregado ============ -->
    <div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1080;">
        <div id="toastAgregado" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive"
            aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle-fill"></i> <span id="toastAgregadoTexto">Agregado al carrito</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script src="plugins/jquery/jquery-4.0.0.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="plugins/qrcode/qrcode.min.js"></script>
    <script>
        const WHATSAPP_NUMERO = "<?php echo htmlspecialchars(WHATSAPP_NUMERO, ENT_QUOTES); ?>";
        const CATALOGO_URL = "<?php echo htmlspecialchars(CATALOGO_URL, ENT_QUOTES); ?>";
        const EMPRESA_NOMBRE = "<?php echo htmlspecialchars(EMPRESA_NOMBRE, ENT_QUOTES); ?>";
    </script>
    <script src="<?php echo assetVersionado('assets/js/catalogo.js'); ?>"></script>
</body>

</html>
