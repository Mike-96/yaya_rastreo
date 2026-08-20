<?php

/**
 * Endpoint JSON para el catálogo público (catalogo.php).
 *
 * Schema real:
 *   productos_catalogo: id_producto (PK), codigo, nombre, num_parte, marca,
 *     categoria, departamento, unidad, precio_venta_usd, precio_venta_cordoba,
 *     stock, comentarios, imagen_url, fecha_registro, fecha_sync
 *     (no tiene columna "activo": el modelo de sincronización ya sólo
 *     replica productos activos, así que aquí no hace falta filtrarlo).
 *
 *   productos_catalogo_imagenes: id (PK), producto_id, url_imagen, orden
 *
 * Las consultas alían id_producto -> id y las columnas de imagen -> el
 * mismo nombre que ya consume catalogo.js, para no tener que tocar el
 * front por este cambio de schema.
 *
 * Acciones (parámetro "accion" por GET):
 *   - meta:     marcas, categorías, departamentos y rango de precio
 *   - listar:   productos filtrados + paginados
 *   - imagenes: galería de imágenes de un producto
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion_catalogo.php';

$conexionCatalogo = new ConexionCatalogo();
$pdo = $conexionCatalogo->conectar();

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo conectar a la base de datos del catálogo.']);
    exit;
}

$accion = $_GET['accion'] ?? 'listar';

/**
 * Arma la cláusula WHERE + parámetros según los filtros recibidos.
 * Se comparte entre el conteo total y el listado paginado.
 */
function construirFiltros()
{
    $condiciones = ['stock <> 0', 'precio_venta_cordoba <> 0'];
    $parametros  = [];

    $buscar = trim($_GET['buscar'] ?? '');
    if ($buscar !== '') {
        // Ojo: con PDO::ATTR_EMULATE_PREPARES en false, MySQL no permite
        // reusar el mismo placeholder con nombre varias veces en la misma
        // consulta, así que va uno distinto por columna (mismo valor).
        $condiciones[] = '(nombre LIKE :buscar1 OR num_parte LIKE :buscar2 OR codigo LIKE :buscar3 OR comentarios LIKE :buscar4)';
        $parametros[':buscar1'] = '%' . $buscar . '%';
        $parametros[':buscar2'] = '%' . $buscar . '%';
        $parametros[':buscar3'] = '%' . $buscar . '%';
        $parametros[':buscar4'] = '%' . $buscar . '%';
    }

    $marcas = $_GET['marca'] ?? [];
    if (is_array($marcas) && count($marcas) > 0) {
        $marcadores = [];
        foreach ($marcas as $i => $marca) {
            $clave = ':marca' . $i;
            $marcadores[] = $clave;
            $parametros[$clave] = $marca;
        }
        $condiciones[] = 'marca IN (' . implode(', ', $marcadores) . ')';
    }

    $categorias = $_GET['categoria'] ?? [];
    if (is_array($categorias) && count($categorias) > 0) {
        $marcadores = [];
        foreach ($categorias as $i => $categoria) {
            $clave = ':categoria' . $i;
            $marcadores[] = $clave;
            $parametros[$clave] = $categoria;
        }
        $condiciones[] = 'categoria IN (' . implode(', ', $marcadores) . ')';
    }

    $departamentos = $_GET['departamento'] ?? [];
    if (is_array($departamentos) && count($departamentos) > 0) {
        $marcadores = [];
        foreach ($departamentos as $i => $departamento) {
            $clave = ':departamento' . $i;
            $marcadores[] = $clave;
            $parametros[$clave] = $departamento;
        }
        $condiciones[] = 'departamento IN (' . implode(', ', $marcadores) . ')';
    }

    if (isset($_GET['precio_min']) && $_GET['precio_min'] !== '') {
        $condiciones[] = 'precio_venta_cordoba >= :precio_min';
        $parametros[':precio_min'] = floatval($_GET['precio_min']);
    }

    if (isset($_GET['precio_max']) && $_GET['precio_max'] !== '') {
        $condiciones[] = 'precio_venta_cordoba <= :precio_max';
        $parametros[':precio_max'] = floatval($_GET['precio_max']);
    }

    if (($_GET['orden'] ?? '') === 'ofertas') {
        $condiciones[] = "precio_oferta IS NOT NULL AND vencimiento_oferta >= CURDATE()";
    }

    return [
        'where'      => implode(' AND ', $condiciones),
        'parametros' => $parametros,
    ];
}

/**
 * Agrega ?v=<timestamp de fecha_sync> a una URL de imagen, para que el CDN
 * (Cloudflare) sirva una versión "nueva" en cuanto se vuelva a sincronizar
 * el catálogo, en vez de quedarse con la imagen vieja cacheada.
 */
function versionarImagen($url, $fechaSync)
{
    if (empty($url)) {
        return $url;
    }

    $version = $fechaSync ? strtotime($fechaSync) : time();
    $separador = (strpos($url, '?') === false) ? '?' : '&';

    return $url . $separador . 'v=' . $version;
}

try {
    switch ($accion) {

        case 'meta':
            $marcasStmt = $pdo->query(
                "SELECT DISTINCT marca FROM productos_catalogo
                 WHERE marca IS NOT NULL AND marca <> '' AND stock <> 0 AND precio_venta_cordoba <> 0
                 ORDER BY marca ASC"
            );
            $marcas = $marcasStmt->fetchAll(PDO::FETCH_COLUMN);

            $categoriasStmt = $pdo->query(
                "SELECT DISTINCT categoria FROM productos_catalogo
                 WHERE categoria IS NOT NULL AND categoria <> '' AND stock <> 0 AND precio_venta_cordoba <> 0
                 ORDER BY categoria ASC"
            );
            $categorias = $categoriasStmt->fetchAll(PDO::FETCH_COLUMN);

            $departamentosStmt = $pdo->query(
                "SELECT DISTINCT departamento FROM productos_catalogo
                 WHERE departamento IS NOT NULL AND departamento <> '' AND stock <> 0 AND precio_venta_cordoba <> 0
                 ORDER BY departamento ASC"
            );
            $departamentos = $departamentosStmt->fetchAll(PDO::FETCH_COLUMN);

            $rangoStmt = $pdo->query(
                "SELECT MIN(precio_venta_cordoba) AS precio_min, MAX(precio_venta_cordoba) AS precio_max
                 FROM productos_catalogo
                 WHERE stock <> 0 AND precio_venta_cordoba <> 0"
            );
            $rango = $rangoStmt->fetch();

            echo json_encode([
                'success'       => true,
                'marcas'        => $marcas,
                'categorias'    => $categorias,
                'departamentos' => $departamentos,
                'precio_min'    => floatval($rango['precio_min'] ?? 0),
                'precio_max'    => floatval($rango['precio_max'] ?? 0),
            ]);
            break;

        case 'listar':
            $filtros = construirFiltros();

            $pagina     = max(1, intval($_GET['pagina'] ?? 1));
            $porPagina  = max(1, min(60, intval($_GET['por_pagina'] ?? 24)));
            $offset     = ($pagina - 1) * $porPagina;

            // La marca va primero en el ORDER BY para agrupar productos de
            // una misma marca (en cualquier filtro/búsqueda activa), excepto
            // en "más reciente": ese modo respeta siempre fecha_sync DESC
            // primero, tal cual estaba antes.
            $orden = $_GET['orden'] ?? 'relevancia';
            switch ($orden) {
                case 'precio_asc':
                    $ordenSql = 'marca ASC, precio_venta_cordoba ASC';
                    break;
                case 'precio_desc':
                    $ordenSql = 'marca ASC, precio_venta_cordoba DESC';
                    break;
                case 'nombre':
                    $ordenSql = 'marca ASC, nombre ASC';
                    break;
                case 'ofertas':
                    $ordenSql = 'marca ASC, nombre ASC';
                    break;
                default:
                    $ordenSql = 'fecha_sync DESC, nombre ASC';
            }

            // Total de resultados (para la paginación)
            $sqlTotal = "SELECT COUNT(*) FROM productos_catalogo WHERE {$filtros['where']}";
            $stmtTotal = $pdo->prepare($sqlTotal);
            $stmtTotal->execute($filtros['parametros']);
            $total = intval($stmtTotal->fetchColumn());

            // Listado paginado
            // (alías id_producto -> id, imagen_url -> imagen_principal, para no tocar el front)
            $sql = "SELECT id_producto AS id, codigo, nombre, num_parte, marca, categoria, departamento,
                           unidad, precio_venta_cordoba, precio_oferta, vencimiento_oferta, stock, comentarios,
                           imagen_url AS imagen_principal, fecha_sync
                    FROM productos_catalogo
                    WHERE {$filtros['where']}
                    ORDER BY {$ordenSql}
                    LIMIT :limite OFFSET :offset";

            $stmt = $pdo->prepare($sql);
            foreach ($filtros['parametros'] as $clave => $valor) {
                $stmt->bindValue($clave, $valor);
            }
            $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $productos = $stmt->fetchAll();

            $hoy = date('Y-m-d');

            foreach ($productos as &$producto) {
                $producto['imagen_principal'] = versionarImagen($producto['imagen_principal'], $producto['fecha_sync']);
                unset($producto['fecha_sync']);

                // El precio de oferta (más alto, tachado) solo se muestra
                // mientras la fecha de vencimiento sea hoy o futura.
                $ofertaVigente = $producto['vencimiento_oferta'] !== null
                    && $producto['vencimiento_oferta'] >= $hoy;

                if (!$ofertaVigente) {
                    $producto['precio_oferta'] = null;
                }
                unset($producto['vencimiento_oferta']);
            }
            unset($producto);

            echo json_encode([
                'success'       => true,
                'productos'     => $productos,
                'total'         => $total,
                'pagina'        => $pagina,
                'total_paginas' => (int) ceil($total / $porPagina),
            ]);
            break;

        case 'imagenes':
            $productoId = intval($_GET['id'] ?? 0);

            if ($productoId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID de producto inválido.']);
                break;
            }

            $stmt = $pdo->prepare(
                "SELECT pci.url_imagen, pc.fecha_sync
                 FROM productos_catalogo_imagenes pci
                 JOIN productos_catalogo pc ON pc.id_producto = pci.producto_id
                 WHERE pci.producto_id = :id
                 ORDER BY pci.orden ASC"
            );
            $stmt->execute([':id' => $productoId]);
            $filas = $stmt->fetchAll();

            $imagenes = array_map(function ($fila) {
                return versionarImagen($fila['url_imagen'], $fila['fecha_sync']);
            }, $filas);

            echo json_encode(['success' => true, 'imagenes' => $imagenes]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
    }
} catch (PDOException $e) {
    error_log('Error en catalogo_data.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al consultar el catálogo.']);
}
