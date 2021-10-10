<?php

$image = imagecreatefromgif('pdrift.gif');
$colour = imagecolorat($image, 50, 50);

var_dump($colour);
var_dump(imagecolortransparent($image));
