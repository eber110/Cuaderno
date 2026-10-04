<?php
  
namespace App\Controllers;

use Base\Control\Control;

class TestControllers extends Control {

  /**
   * Vista de demostración de los gráficos SVG minimalistas y laboratorio de animaciones.
   * 
   * @return string|void
   */
  public function test2() {
    $demoData = [
      "arcMeter" => [
        "title"       => "ARC METER",
        "tag"         => "Velocímetro",
        "value"       => 84,
        "suffix"      => "%",
        "label"       => "Sistema",
        "status"      => "Carga Óptima",
        "category"    => "Arco Semicircular 180°",
        "metaKey"     => "Nodos activos",
        "metaVal"     => "14 / 16",
        "footerTitle" => "Mono Arc Meter",
        "footerDesc"  => "Arco semicircular con extremo redondeado"
      ],
      "stackedTones" => [
        "title"       => "STACKED TONES",
        "tag"         => "Barras Apiladas",
        "value"       => 735,
        "suffix"      => "",
        "label"       => "Total acumulado",
        "category"    => "3 Capas Monocromáticas",
        "metaKey"     => "Pico semanal",
        "metaVal"     => "Miércoles (+38%)",
        "footerTitle" => "Mono Stacked Tones",
        "footerDesc"  => "Cápsulas multicapa con escala dinámica",
        "quarters"    => [
          ["label" => "Lun", "total" => 95,  "white" => 45, "mid" => 30, "dark" => 20],
          ["label" => "Mar", "total" => 120, "white" => 50, "mid" => 40, "dark" => 30],
          ["label" => "Mié", "total" => 165, "white" => 75, "mid" => 55, "dark" => 35],
          ["label" => "Jue", "total" => 130, "white" => 55, "mid" => 45, "dark" => 30],
          ["label" => "Vie", "total" => 145, "white" => 60, "mid" => 50, "dark" => 35],
          ["label" => "Sáb", "total" => 80,  "white" => 35, "mid" => 25, "dark" => 20],
        ]
      ],
      "treemap" => [
        "title"       => "TILE TREEMAP",
        "tag"         => "Mosaico",
        "value"       => 100,
        "suffix"      => "%",
        "label"       => "Distribución de recursos",
        "category"    => "Mosaicos con Esquinas Redondeadas",
        "metaKey"     => "Mayor consumo",
        "metaVal"     => "Almacenamiento (35%)",
        "footerTitle" => "Mono Tile Treemap",
        "footerDesc"  => "Partición dinámica de bloques",
        "tiles"       => [
          ["label" => "Almacenamiento", "pct" => "35%", "tag" => "350 GB NVMe", "name" => "Almacenamiento", "info" => "350 GB NVMe"],
          ["label" => "Cómputo",        "pct" => "25%", "tag" => "32 vCPU Activas", "name" => "Cómputo", "info" => "32 vCPU Activas"],
          ["label" => "Red",            "pct" => "18%", "tag" => "10 Gbps Fibra", "name" => "Red", "info" => "10 Gbps Fibra"],
          ["label" => "Memoria RAM",    "pct" => "12%", "tag" => "64 GB DDR5", "name" => "Memoria RAM", "info" => "64 GB DDR5"],
          ["label" => "Caché Redis",    "pct" => "10%", "tag" => "En Memoria", "name" => "Caché Redis", "info" => "En Memoria"],
        ]
      ],
      "hybridSpline" => [
        "title"       => "HYBRID SPLINE",
        "tag"         => "Curva Bézier",
        "value"       => "95.0k",
        "suffix"      => "",
        "label"       => "Pico de volumen",
        "category"    => "Spline Bézier Continuo",
        "metaKey"     => "Eficiencia",
        "metaVal"     => "96.4%",
        "footerTitle" => "Mono Hybrid Spline + Bar",
        "footerDesc"  => "Curva spline continua sobre cápsulas",
        "values"      => [35, 62, 48, 88, 70, 95, 78],
        "labels"      => ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul"],
        "points"      => [
          ["label" => "Ene", "val" => 35],
          ["label" => "Feb", "val" => 62],
          ["label" => "Mar", "val" => 48],
          ["label" => "Abr", "val" => 88],
          ["label" => "May", "val" => 70],
          ["label" => "Jun", "val" => 95],
          ["label" => "Jul", "val" => 78]
        ]
      ],
      "pillPillars" => [
        "title"       => "ROUNDED PILL",
        "tag"         => "Pilares",
        "value"       => "84.8",
        "suffix"      => "row",
        "label"       => "Puntuación de actividad",
        "category"    => "Columnas Emparejadas en Cápsula",
        "metaKey"     => "Serie líder",
        "metaVal"     => "Serie Alfa",
        "footerTitle" => "Mono Rounded Pill Pillars",
        "footerDesc"  => "Pilares dobles con distribución automática",
        "pairs"       => [
          ["label" => "Sem 1", "v1" => 75, "v2" => 42],
          ["label" => "Sem 2", "v1" => 90, "v2" => 65],
          ["label" => "Sem 3", "v1" => 60, "v2" => 38],
          ["label" => "Sem 4", "v1" => 85, "v2" => 55],
          ["label" => "Sem 5", "v1" => 98, "v2" => 72],
        ],
        "groups"      => [
          ["label" => "Sem 1", "p" => 75, "s" => 42],
          ["label" => "Sem 2", "p" => 90, "s" => 65],
          ["label" => "Sem 3", "p" => 60, "s" => 38],
          ["label" => "Sem 4", "p" => 85, "s" => 55],
          ["label" => "Sem 5", "p" => 98, "s" => 72],
        ]
      ],
      "simpleBars" => [
        "title"       => "SIMPLE BARS",
        "tag"         => "Barras Verticales",
        "value"       => "21:00",
        "label"       => "Hora pico del día",
        "category"    => "Fechas en X • Horarios en Y",
        "metaKey"     => "Pico máximo",
        "metaVal"     => "Viernes 21:00",
        "yLabels"     => ["24:00", "18:00", "12:00", "06:00", "00:00"],
        "bars"        => [
          ["label" => "Lun", "value" => 8.5,  "display" => "08:30"],
          ["label" => "Mar", "value" => 14.0, "display" => "14:00"],
          ["label" => "Mié", "value" => 19.5, "display" => "19:30"],
          ["label" => "Jue", "value" => 11.0, "display" => "11:00"],
          ["label" => "Vie", "value" => 21.0, "display" => "21:00"],
          ["label" => "Sáb", "value" => 16.5, "display" => "16:30"],
          ["label" => "Dom", "value" => 13.0, "display" => "13:00"],
        ]
      ],
      "horizontalBars" => [
        "title"       => "HORIZONTAL BARS",
        "tag"         => "Barras Horizontales",
        "value"       => "5.0h",
        "label"       => "Tiempo de uso",
        "category"    => "Horarios en Y • Horas en X",
        "metaKey"     => "Mayor duración",
        "metaVal"     => "16:00 - 18:00",
        "xLabels"     => ["0h", "1.5h", "3h", "4.5h", "6h"],
        "maxVal"      => 6.0,
        "bars"        => [
          ["label" => "08:00", "value" => 2.5, "display" => "2.5 hrs"],
          ["label" => "10:00", "value" => 4.2, "display" => "4.2 hrs"],
          ["label" => "12:00", "value" => 1.8, "display" => "1.8 hrs"],
          ["label" => "14:00", "value" => 3.6, "display" => "3.6 hrs"],
          ["label" => "16:00", "value" => 5.0, "display" => "5.0 hrs"],
        ]
      ],
      "bulletTarget" => [
        "title"       => "BULLET TARGET",
        "tag"         => "Benchmark",
        "value"       => "3 Targets",
        "suffix"      => " evaluated",
        "label"       => "Evaluación de métricas",
        "category"    => "Rounded Bullet Bars",
        "metaKey"     => "Marcador",
        "metaVal"     => "Benchmark Marker",
        "targets"     => [
          ["label" => "Throughput", "value" => 82, "target" => 75, "suffix" => "%"],
          ["label" => "Latency",    "value" => 65, "target" => 80, "suffix" => "%"],
          ["label" => "Uptime",     "value" => 95, "target" => 90, "suffix" => "%"],
        ]
      ],
      "roundedDonut" => [
        "title"       => "MONO ROUNDED DONUT",
        "tag"         => "Soft Arc Caps",
        "value"       => "100%",
        "suffix"      => " allocation",
        "label"       => "Distribución modular",
        "category"    => "Rounded Arc Caps • OKLCH",
        "metaKey"     => "Segmentos",
        "metaVal"     => "4 Capas Monocromáticas",
        "centerValue" => "100%",
        "centerLabel" => "Mono Arc",
        "segments"    => [
          ["label" => "Core Engine", "value" => 45, "suffix" => "%"],
          ["label" => "UI Layer",    "value" => 28, "suffix" => "%"],
          ["label" => "Assets",      "value" => 17, "suffix" => "%"],
          ["label" => "Other",       "value" => 10, "suffix" => "%"],
        ]
      ]
    ];

    return $this->view("Test.test2", ["demo" => $demoData]);
  }

}