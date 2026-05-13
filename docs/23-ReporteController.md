# 📊 ReporteController

**Archivo:** `controllers/ReporteController.php`  
**Modelo:** `Reporte`  
**Acceso:** Solo Admin

---

## Propósito

Centraliza la generación de reportes del sistema. Calcula rangos de fechas según el periodo seleccionado y expone métodos individuales para cada tipo de dato, además de un método `reporteCompleto()` que retorna todo en una sola llamada.

---

## Constructor

```php
public function __construct()
{
    $this->reporteModel = new Reporte((new Database())->conectar());
}
```

---

## `calcularRango(string $periodo, string $desde, string $hasta): array`

Convierte el periodo seleccionado en un rango de fechas concreto.

| `$periodo` | `desde` | `hasta` | `label` |
|---|---|---|---|
| `'hoy'` | `date('Y-m-d')` | `date('Y-m-d')` | `'Hoy (dd/mm/YYYY)'` |
| `'semana'` | Lunes de esta semana | Domingo de esta semana | `'Esta semana (dd/mm - dd/mm/YYYY)'` |
| `'mes'` | Primer día del mes | Último día del mes | `'Este mes (Month YYYY)'` |
| `'personalizado'` | `$desde` (o hoy si vacío) | `$hasta` (o hoy si vacío) | `'Del dd/mm/YYYY al dd/mm/YYYY'` |

```php
// Ejemplo para 'semana':
'desde' => date('Y-m-d', strtotime('monday this week')),
'hasta' => date('Y-m-d', strtotime('sunday this week')),

// Ejemplo para 'mes':
'desde' => date('Y-m-01'),
'hasta' => date('Y-m-t'),  // 't' = último día del mes actual
```

---

## Métodos de consulta individual

### `resumen(string $desde, string $hasta): array`

Retorna los 5 KPIs del periodo:

```php
[
    'total_ventas'    => FLOAT,  // SUM(total_factura) de facturas
    'num_pedidos'     => INT,    // COUNT(*) de pedidos
    'num_reservas'    => INT,    // COUNT(*) de reservas
    'num_facturas'    => INT,    // COUNT(*) de facturas
    'ticket_promedio' => FLOAT,  // total_ventas / num_facturas
]
```

### `ventas(string $desde, string $hasta): array`

Facturas del periodo con:
- `id_factura`, `fecha`, `metodo_pago`, `total_factura`
- `cliente` (nombre del usuario)
- `id_pedido`

### `pedidos(string $desde, string $hasta): array`

Pedidos del periodo con:
- `id_pedido`, `fecha_pedido`, `estado`, `tipo`
- `cliente` (nombre del usuario)
- `total` (calculado con `SUM(dp.subtotal)`)

### `reservas(string $desde, string $hasta): array`

Reservas del periodo con:
- `id_reserva`, `fecha_reserva`, `hora_reserva`, `numero_personas`
- `numero_mesa`, `nombre_estado`
- `cliente` (nombre del usuario)

### `topProductos(string $desde, string $hasta, int $limite): array`

Top N productos más vendidos:
```php
[
    ['nombre' => STRING, 'total_unidades' => INT, 'total_ingresos' => FLOAT],
    ...
]
// Ordenado por total_unidades DESC
```

### `ventasPorDia(string $desde, string $hasta): array`

Ventas agrupadas por día para la gráfica de barras:
```php
[
    ['fecha' => 'YYYY-MM-DD', 'num_facturas' => INT, 'total' => FLOAT],
    ...
]
// Ordenado por fecha ASC
```

---

## `reporteCompleto(string $periodo, string $desde, string $hasta): array`

Método conveniente que ejecuta todas las consultas en una sola llamada.

```php
public function reporteCompleto(string $periodo, ...): array
{
    $rango = $this->calcularRango($periodo, $desde, $hasta);
    $d = $rango['desde'];
    $h = $rango['hasta'];

    return [
        'rango'     => $rango,      // ['desde', 'hasta', 'label']
        'resumen'   => $this->resumen($d, $h),
        'ventas'    => $this->ventas($d, $h),
        'pedidos'   => $this->pedidos($d, $h),
        'reservas'  => $this->reservas($d, $h),
        'topProd'   => $this->topProductos($d, $h),
        'ventasDia' => $this->ventasPorDia($d, $h),
    ];
}
```

---

## Cómo se usa en `admin_reportes.php`

Actualmente la vista usa el modelo directamente. Con el controlador el código quedaría:

```php
require_once __DIR__ . '/../../controllers/ReporteController.php';

$ctrl    = new ReporteController();
$periodo = $_GET['periodo'] ?? 'hoy';
$desde   = $_GET['desde']   ?? '';
$hasta   = $_GET['hasta']   ?? '';

$reporte = $ctrl->reporteCompleto($periodo, $desde, $hasta);

// Acceso a los datos:
$labelPeriodo = $reporte['rango']['label'];
$resumen      = $reporte['resumen'];
$ventas       = $reporte['ventas'];
$pedidos      = $reporte['pedidos'];
$reservas     = $reporte['reservas'];
$topProd      = $reporte['topProd'];
$ventasDia    = $reporte['ventasDia'];
```

---

## Datos embebidos en el PDF (jsPDF)

Los datos del controlador se pasan como literales JavaScript en la vista:

```php
// En admin_reportes.php:
const ventasRows = <?= json_encode(array_map(fn($v) => [
    $v['id_factura'],
    date('d/m/Y', strtotime($v['fecha'])),
    $v['cliente'] ?? '—',
    '#' . $v['id_pedido'],
    $v['metodo_pago'] ?? '—',
    '$' . number_format((float)$v['total_factura'], 2),
], $ventas)) ?>;
```

Esto embebe los datos PHP directamente en el JS para que `jsPDF` los use al generar el PDF sin necesidad de una llamada AJAX adicional.
