<?php
  
namespace App\Controllers;

use Base\Control\Control;

class TestControllers extends Control {

  public function test2() {
    $demoData = [
      "arcMeter" => [
        "title"    => "ARC METER",
        "tag"      => "Speedometer",
        "value"    => 78,
        "suffix"   => "%",
        "label"    => "load index",
        "status"   => "Optimal Load",
        "category" => "Rounded Semi-Circle Arc"
      ],
      "stackedTones" => [
        "title"    => "STACKED TONES",
        "tag"      => "Layers",
        "value"    => 160,
        "suffix"   => "",
        "label"    => "cumulative",
        "category" => "3 Monochrome Layers"
      ],
      "treemap" => [
        "title"    => "TILE TREEMAP",
        "tag"      => "Allocation",
        "value"    => 100,
        "suffix"   => "%",
        "label"    => "partitioned",
        "category" => "Rounded Corner Tiles"
      ]
    ];

    return $this->view("Test.test2", ["demo" => $demoData]);
  }

}