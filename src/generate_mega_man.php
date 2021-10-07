<?php

require 'library.php';

$indexedBitmap = IndexedBitmap::load('megaman.data', 320, 318);
$bossBitmapData = $indexedBitmap->extractRegionToIndexedBitmap(0, 220, 109, 98)
    ->getCopyRoundedTo16PixelDivisibleWidth();
$bossMaskData = $indexedBitmap->extractRegionToIndexedBitmap(109, 220, 109, 98)
    ->getCopyRoundedTo16PixelDivisibleWidth();

$maskedSprite = SpriteConvertor::createMaskedSprite($bossBitmapData, $bossMaskData);

echo("Generate 'mega man' sprite:\n");
echo("- width: ".$maskedSprite->getWidth()."\n");
echo("- height: ".$maskedSprite->getHeight()."\n");

$planarData = $maskedSprite->exportToPlanarData();
$asmCode = $planarData->exportToAsm('_mega_man');

file_put_contents('src/mega_man.s', $asmCode);


