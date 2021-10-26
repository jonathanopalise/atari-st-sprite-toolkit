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
        'masked' => true,
    ],
    [
        'label' => 'billboard_victory',
        'left' => 160,
        'top' => 9,
        'width' => 137,
        'height' => 102,
        'masked' => true,
    ],
    [
        'label' => 'billboard_westwood',
        'left' => 8,
        'top' => 241,
        'width' => 118,
        'height' => 131,
        'masked' => true,
    ],
    [
        'label' => 'billboard_lobster',
        'left' => 9,
        'top' => 381,
        'width' => 150,
        'height' => 83,
        'masked' => true,
    ],
    [
        'label' => 'billboard_bubweiser',
        'left' => 292,
        'top' => 136,
        'width' => 94,
        'height' => 157,
        'masked' => true,
    ],
    [
        'label' => 'billboard_wlak',
        'left' => 286,
        'top' => 304,
        'width' => 168,
        'height' => 84,
        'masked' => true,
    ],
    [
        'label' => 'rock_pillar',
        'left' => 145,
        'top' => 124,
        'width' => 127,
        'height' => 172,
        'masked' => true,
    ],
    [
        'label' => 'tree_rustic',
        'left' => 305,
        'top' => 10,
        'width' => 155,
        'height' => 112,
        'masked' => true,
    ],
    [
        'label' => 'tree_pineapple',
        'left' => 6,
        'top' => 119,
        'width' => 124,
        'height' => 116,
        'masked' => true,
    ],
    [
        'label' => 'tree_palm',
        'left' => 166,
        'top' => 309,
        'width' => 89,
        'height' => 159,
        'masked' => true,
    ],
    [
        'label' => 'log',
        'left' => 265,
        'top' => 451,
        'width' => 241,
        'height' => 30,
        'masked' => false,
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

    if ($typeDefinition['label'] == 'log') {
        $originY = 0;
    } else {
        $originY = $typeDefinition['height'] - 1;
    }

    $typeIndexedBitmap = $indexedBitmap->extractRegionToIndexedBitmap(
        $typeDefinition['left'],
        $typeDefinition['top'],
        $typeDefinition['width'],
        $typeDefinition['height'],
        intval($typeDefinition['width'] / 2),
        $originY
    );

    $sprites = [];
    foreach ($scaleFactors as $scaleFactor) {
        $scaledWidth = intval($typeDefinition['width'] / 255 * $scaleFactor);
        $scaledHeight = intval($typeDefinition['height'] / 255 * $scaleFactor);

        $scaledIndexedBitmap = $typeIndexedBitmap->getScaledCopy($scaledWidth, $scaledHeight)
            ->getCopyRoundedTo16PixelDivisibleWidth();

        $emptyPixelsOnRight = $scaledIndexedBitmap->getWidth() - $scaledWidth;
        //$emptyPixelsOnRight = 0;
        echo("emptyPixelsOnRight = " . $emptyPixelsOnRight . "\n");

        //echo("Rounded width is ". $scaledIndexedBitmap->getWidth(). "\n");

        $maskedSprite = SpriteConvertor::createMaskedSprite($scaledIndexedBitmap);
        $planarData = $maskedSprite->exportToPlanarData();

        $sprite = [
            'scale_factor' => $scaleFactor,
            'origin_x' => $scaledIndexedBitmap->getOriginX(),
            'origin_y' => $scaledIndexedBitmap->getOriginY(),
            'source_data_width' => $maskedSprite->getWidth(),
            'source_data_height' => $maskedSprite->getHeight(),
            'empty_pixels_on_right' => $emptyPixelsOnRight,
            'words' => $planarData->getWords(),
        ];

        $sprites[] = $sprite;
    }

    $type = [
        'label' => $typeDefinition['label'],
        'number_of_sizes' => count($scaleFactors),
        'masked' => $typeDefinition['masked'] ? 1 : 0,
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

