<?php

if ($argc < 3) {
    echo("usage: generate_palette [inputFile] [outputFile]");
    exit(1);
}

function generateSteNibble($value)
{
    $amigaNibble = ($value >> 4);
    return (($amigaNibble >> 1) | (($amigaNibble & 1) << 3));
}

$rgbPalette = file_get_contents($argv[1]);
if (strlen($rgbPalette) != 48) {
    echo("palette file needs to be exactly 48 bytes");
    exit(1);
}

$stePalette = [];
$offset = 0;
for ($index = 0; $index < 16; $index++) {
    $red = ord($rgbPalette[$offset]);
    $green = ord($rgbPalette[$offset+1]);
    $blue = ord($rgbPalette[$offset+2]);

    $steRed = generateSteNibble($red);
    $steGreen = generateSteNibble($green);
    $steBlue = generateSteNibble($blue);

    $stePalette[] = ($steRed << 8) | ($steGreen << 4) | ($steBlue);

    $offset += 3;
}

$identifier = '_palette';

$lines = [
    '    public ' . $identifier,
    '',
    $identifier . ':'
];

foreach ($stePalette as $entry) {
    $lines[] = '    dc.w $' . dechex($entry);
}

$contents = implode("\n", $lines);

$result = file_put_contents($argv[2], $contents);
if ($result === false) {
    echo("unable to write palette file");
    exit(1);
}

