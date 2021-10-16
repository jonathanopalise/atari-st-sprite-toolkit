<?php

class Point2d
{
    private $x;
    private $y;

    public function __construct()
    {
    }
}

function getPointOnCubicBezier($t, $x1, $y1, $x2, $y2, $x3, $y3, $x4, $y4)
{
    $t2 = $t * $t
    $t3 = $t * $t * $t
    $x = $t3*$x4 + (3*$t2 - 3*$t3)*$x3 + (3*$t3 - 6*$t2 + 3*$t)*$x2 + (3*$t2 - $t3 - 3*$t + 1)*$x1
    $y = $t3*$y4 + (3*$t2 - 3*$t3)*$y3 + (3*$t3 - 6*$t2 + 3*$t)*$y2 + (3*$t2 - $t3 - 3*$t + 1)*$y1

    return [
    ];
}
