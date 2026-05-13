<?php
/**
 * ReporteController
 * Centraliza la generación de reportes de ventas,
 * pedidos, reservas y productos más vendidos.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reporte.php';

class ReporteController
{
    private Reporte $reporteModel;

    public function __construct()
    {
        $this->reporteModel = new Reporte((new Database())->conectar());
    }

    /* =========================
       CALCULAR RANGO DE FECHAS
       Según el periodo seleccionado
    ========================= */
    public function calcularRango(string $periodo, string $desde = '', string $hasta = ''): array
    {
        $hoy = date('Y-m-d');

        switch ($periodo) {
            case 'semana':
                return [
                    'desde'  => date('Y-m-d', strtotime('monday this week')),
                    'hasta'  => date('Y-m-d', strtotime('sunday this week')),
                    'label'  => 'Esta semana',
                ];
            case 'mes':
                return [
                    'desde'  => date('Y-m-01'),
                    'hasta'  => date('Y-m-t'),
                    'label'  => 'Este mes (' . date('F Y') . ')',
                ];
            case 'personalizado':
                return [
                    'desde'  => $desde ?: $hoy,
                    'hasta'  => $hasta ?: $hoy,
                    'label'  => 'Del ' . date('d/m/Y', strtotime($desde ?: $hoy))
                                . ' al ' . date('d/m/Y', strtotime($hasta ?: $hoy)),
                ];
            default: // hoy
                return [
                    'desde'  => $hoy,
                    'hasta'  => $hoy,
                    'label'  => 'Hoy (' . date('d/m/Y') . ')',
                ];
        }
    }

    /* =========================
       RESUMEN (KPIs)
    ========================= */
    public function resumen(string $desde, string $hasta): array
    {
        return $this->reporteModel->resumen($desde, $hasta);
    }

    /* =========================
       VENTAS / FACTURAS
    ========================= */
    public function ventas(string $desde, string $hasta): array
    {
        return $this->reporteModel->ventasPorRango($desde, $hasta);
    }

    /* =========================
       PEDIDOS
    ========================= */
    public function pedidos(string $desde, string $hasta): array
    {
        return $this->reporteModel->pedidosPorRango($desde, $hasta);
    }

    /* =========================
       RESERVAS
    ========================= */
    public function reservas(string $desde, string $hasta): array
    {
        return $this->reporteModel->reservasPorRango($desde, $hasta);
    }

    /* =========================
       PRODUCTOS MÁS VENDIDOS
    ========================= */
    public function topProductos(string $desde, string $hasta, int $limite = 5): array
    {
        return $this->reporteModel->productosMasVendidos($desde, $hasta, $limite);
    }

    /* =========================
       VENTAS POR DÍA (gráfica)
    ========================= */
    public function ventasPorDia(string $desde, string $hasta): array
    {
        return $this->reporteModel->ventasPorDia($desde, $hasta);
    }

    /* =========================
       REPORTE COMPLETO
       Retorna todos los datos en un solo array
    ========================= */
    public function reporteCompleto(string $periodo, string $desde = '', string $hasta = ''): array
    {
        $rango = $this->calcularRango($periodo, $desde, $hasta);
        $d     = $rango['desde'];
        $h     = $rango['hasta'];

        return [
            'rango'      => $rango,
            'resumen'    => $this->resumen($d, $h),
            'ventas'     => $this->ventas($d, $h),
            'pedidos'    => $this->pedidos($d, $h),
            'reservas'   => $this->reservas($d, $h),
            'topProd'    => $this->topProductos($d, $h),
            'ventasDia'  => $this->ventasPorDia($d, $h),
        ];
    }
}
