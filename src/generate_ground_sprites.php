<?php

require_once 'library.php';

if ($argc < 3) {
    echo("Usage: php generate_ground_sprites.php [inputFile] [outputFile]\n");
    exit(1);
}

$inputFilename = $argv[1];
$outputFilename = $argv[2];

if (!file_exists($inputFilename)) {
    echo("Input filename does not exist\n");
    exit(1);
}

$typeDefinitions = [
    [
        'label' => 'billboard_sold',
        'left' => 7,
        'top' => 8,
        'width' => 147,
        'height' => 103,
    ],
    [
        'label' => 'billboard_victory',
        'left' => 160,
        'top' => 9,
        'width' => 137,
        'height' => 102,
    ],
    [
        'label' => 'billboard_westwood',
        'left' => 8,
        'top' => 241,
        'width' => 118,
        'height' => 131,
    ],
    [
        'label' => 'billboard_lobster',
        'left' => 9,
        'top' => 381,
        'width' => 150,
        'height' => 83,
    ],
    [
        'label' => 'billboard_bubweiser',
        'left' => 292,
        'top' => 136,
        'width' => 94,
        'height' => 157,
    ],
    [
        'label' => 'billboard_wlak',
        'left' => 286,
        'top' => 304,
        'width' => 168,
        'height' => 84,
    ],
    [
        'label' => 'rock_pillar',
        'left' => 145,
        'top' => 124,
        'width' => 127,
        'height' => 172,
    ],
    [
        'label' => 'tree_rustic',
        'left' => 305,
        'top' => 10,
        'width' => 155,
        'height' => 112,
    ],
    [
        'label' => 'tree_pineapple',
        'left' => 6,
        'top' => 119,
        'width' => 124,
        'height' => 116,
    ],
    [
        'label' => 'tree_palm',
        'left' => 166,
        'top' => 309,
        'width' => 89,
        'height' => 159,
    ],
];

$scaleFactors = [];
$currentScaleFactor = 31;
for ($index = 0; $index < 8; $index++) {
    $scaleFactors[] = $currentScaleFactor;
    $currentScaleFactor += 32;
}

$indexedBitmap = IndexedBitmap::loadGif($inputFilename);

$types = [];
foreach ($typeDefinitions as $typeDefinition) {
    echo("generating data for sprite '".$typeDefinition['label']."'...\n");

    $typeIndexedBitmap = $indexedBitmap->extractRegionToIndexedBitmap(
        $typeDefinition['left'],
        $typeDefinition['top'],
        $typeDefinition['width'],
        $typeDefinition['height'],
        intval($typeDefinition['width'] / 2),
        $typeDefinition['height'] - 1
    );

    $sprites = [];
    foreach ($scaleFactors as $scaleFactor) {
        $scaledWidth = intval($typeDefinition['width'] / 255 * $scaleFactor);
        $scaledHeight = intval($typeDefinition['height'] / 255 * $scaleFactor);

        $scaledIndexedBitmap = $typeIndexedBitmap->getScaledCopy($scaledWidth, $scaledHeight)
            ->getCopyRoundedTo16PixelDivisibleWidth();

        $maskedSprite = SpriteConvertor::createMaskedSprite($scaledIndexedBitmap);
        $planarData = $maskedSprite->exportToPlanarData();

        $sprite = [
            'scale_factor' => $scaleFactor,
            'origin_x' => $scaledIndexedBitmap->getOriginX(),
            'origin_y' => $scaledIndexedBitmap->getOriginY(),
            'source_data_width' => $maskedSprite->getWidth(),
            'source_data_height' => $maskedSprite->getHeight(),
            'words' => $planarData->getWords(),
        ];

        $sprites[] = $sprite;
    }

    $type = [
        'label' => $typeDefinition['label'],
        'number_of_sizes' => count($scaleFactors),
        'sprites' => $sprites,
    ];

    $types[] = $type;
}

ob_start();
require('ground_sprites_template.php');
$output = ob_get_clean();

$result = file_put_contents($outputFilename, $output);
if ($result === false) {
    echo("Unable to write ground sprites data");
    exit(1);
}

