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
        "tag"         => "Speedometer",
        "value"       => 90,
        "suffix"      => "%",
        "label"       => "Optimal Load",
        "status"      => "Optimal Load",
        "metaKey"     => "Active nodes",
        "metaVal"     => "12 / 16",
        "footerTitle" => "Mono Arc Meter",
        "footerDesc"  => "Semi-circular track with linecap"
      ],
      "stackedTones" => [
        "title"       => "STACKED TONES",
        "tag"         => "Bar",
        "value"       => 1248,
        "suffix"      => "",
        "label"       => "Total units",
        "metaKey"     => "Peak period",
        "metaVal"     => "Q3 (+24%)",
        "footerTitle" => "Mono Stacked Tones",
        "footerDesc"  => "Multi-layer rounded capsule bars",
        "quarters"    => [
          ["label" => "Q1", "total" => 80,  "white" => 35, "mid" => 25, "dark" => 20],
          ["label" => "Q2", "total" => 110, "white" => 48, "mid" => 37, "dark" => 25],
          ["label" => "Q3", "total" => 155, "white" => 65, "mid" => 52, "dark" => 38],
          ["label" => "Q4", "total" => 125, "white" => 50, "mid" => 45, "dark" => 30]
        ]
      ],
      "treemap" => [
        "title"       => "TILE TREEMAP",
        "tag"         => "Partition",
        "value"       => 100,
        "suffix"      => "%",
        "label"       => "Allocation",
        "metaKey"     => "Primary share",
        "metaVal"     => "Storage (45%)",
        "footerTitle" => "Mono Tile Treemap",
        "footerDesc"  => "Partition blocks with rounded corners",
        "tiles"       => [
          ["name" => "Storage", "pct" => "45%", "class" => "tile-storage", "info" => "450 GB asignados"],
          ["name" => "Compute", "pct" => "30%", "class" => "tile-compute", "info" => "300 vCPU asignados"],
          ["name" => "Network", "pct" => "15%", "class" => "tile-network", "info" => "150 Gbps balanceados"],
          ["name" => "Cache",   "pct" => "10%", "class" => "tile-cache",   "info" => "100 GB en memoria rápida"]
        ]
      ],
      "hybridSpline" => [
        "title"       => "HYBRID SPLINE",
        "tag"         => "Spline On",
        "value"       => "8.4k",
        "suffix"      => "",
        "label"       => "Peak volume",
        "metaKey"     => "Efficiency",
        "metaVal"     => "94.2%",
        "footerTitle" => "Mono Hybrid Spline + Bar",
        "footerDesc"  => "Overlay spline on capsule bars",
        "points"      => [
          ["label" => "Jan", "val" => 4.2, "height" => 68],
          ["label" => "Feb", "val" => 7.1, "height" => 115],
          ["label" => "Mar", "val" => 5.8, "height" => 94],
          ["label" => "Apr", "val" => 8.4, "height" => 136],
          ["label" => "May", "val" => 6.9, "height" => 110]
        ]
      ],
      "pillPillars" => [
        "title"       => "ROUNDED PILL",
        "tag"         => "Pillars",
        "value"       => "42.8",
        "suffix"      => "",
        "label"       => "Index score",
        "metaKey"     => "Dominant set",
        "metaVal"     => "Alpha series",
        "footerTitle" => "Mono Rounded Pill Pillars",
        "footerDesc"  => "Paired capsule pillars with orientation toggle",
        "groups"      => [
          ["label" => "Grp A", "p" => 85, "s" => 52],
          ["label" => "Grp B", "p" => 62, "s" => 88],
          ["label" => "Grp C", "p" => 94, "s" => 40],
          ["label" => "Grp D", "p" => 75, "s" => 65]
        ]
      ]
    ];

    return $this->view("Test.test2", ["demo" => $demoData]);
  }

}