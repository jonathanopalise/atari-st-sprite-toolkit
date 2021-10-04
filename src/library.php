<?php

// TODO: strict types?

class IndexedBitmapLine {
    private $pixels = [];

    public function addPixel(int $index)
    {
        if ($index > 15) {
            throw new RuntimeException('Index higher than 15 not allowed');
        }

        $this->pixels[] = $index;
    }

    public function getPixel(int $xpos)
    {
        if (!isset($this->pixels[$xpos])) {
            throw new RuntimeException('Unable to fetch pixel at position ' . $xpos);
        }

        return $this->pixels[$xpos];
    }

    public function getWidth()
    {
        return count($this->pixels);
    }

    public function toBitplaneWordSequence($bitplaneIndex)
    {
        $pixelCount = count($this->pixels);
        if ($pixelCount & 15 != 0) {
            throw new RuntimeException('Can only extract bitplanes where width divisible by 16');
        }

        if ($bitplaneIndex < 0 || $bitplaneIndex > 3) {
            throw new RuntimeException('Bitplane index must be between 0 and 3 inclusive');
        }

        $andValue = 1 << $bitplaneIndex;
        $bits = [];

        $sixteenPixelArrays = array_chunk($this->pixels, 16);
        foreach ($sixteenPixelArrays as $sixteenPixelArray) {
            foreach ($sixteenPixelArray as $pixel) {
                $bits[] = ($pixel & $andValue) ? 1 : 0;
            }
        }

        $bitplane = new Bitplane($bits);

        return $bitplane->toWordSequence();
    }

    public function toMaskWordSequence()
    {
        $pixelCount = count($this->pixels);
        if ($pixelCount & 15 != 0) {
            throw new RuntimeException('Can only extract mask where width divisible by 16');
        }

        $bits = [];

        $sixteenPixelArrays = array_chunk($this->pixels, 16);
        foreach ($sixteenPixelArrays as $sixteenPixelArray) {
            foreach ($sixteenPixelArray as $pixel) {
                $bits[] = ($pixel > 0) ? 1 : 0;
            }
        }

        $bitplane = new Bitplane($bits);

        return $bitplane->toWordSequence();
    }
}

class IndexedBitmap {
    private $lines;

    private function __construct(array $lines)
    {
        $this->lines = $lines;
    }

    public static function load($filename, $width, $height)
    {
        $contents = file_get_contents($filename);
        if ($contents === false) {
            throw new RuntimeException('failed to open file ' . $filename);
        }

        $expectedLength = $width * $height * 2;
        if (strlen($contents) != $expectedLength) {
            throw new RuntimeException('raw image data is not expected length of ' . $expectedLength);
        }

        $lines = [];
        $offset = 0;
        for ($y = 0; $y < $height; $y++) {
            $line = new IndexedBitmapLine();
            for ($x = 0; $x < $width; $x++) {
                $pixelIndex = ord($contents[$offset]);
                $line->addPixel($pixelIndex);
                $offset += 2;
            }
            $lines[] = $line;
        }

        return new static($lines);
    }

    public function extractRegionToIndexedBitmap($left, $top, $width, $height)
    {
        $sourceWidth = $this->getWidth();
        $sourceHeight = $this->getHeight();

        $maxX = $sourceWidth - 1;
        $maxY = $sourceHeight - 1;

        $x1 = $left;
        $y1 = $top;
        $x2 = ($left + $width) - 1;
        $y2 = ($top + $height) - 1;

        if ($width < 1) {
            throw new RuntimeException('Width must be 1 or greater');
        }

        if ($height < 1) {
            throw new RuntimeException('Height must be 1 or greater');
        }

        if ($left < 0) {
            throw new RuntimeException('Left must be greater than 0');
        }

        if ($top < 0) {
            throw new RuntimeException('Top must be greater than 0');
        }

        if ($x2 > $maxX) {
            throw new RuntimeException('Specified extract is beyond right hand side of image');
        }

        if ($y2 > $maxY) {
            var_dump($y2);
            var_dump($maxY);
            throw new RuntimeException('Specified region is beyond bottom of image');
        }

        $lines = [];
        $offset = 0;
        for ($y = $y1; $y <= $y2; $y++) {
            $line = new IndexedBitmapLine();
            for ($x = $x1; $x <= $x2; $x++) {
                $line->addPixel($this->lines[$y]->getPixel($x));
            }
            $lines[] = $line;
        }

        return new static($lines);
    }

    public function getCopyRoundedTo16PixelDivisibleWidth()
    {
        $firstLineWidth = $this->lines[0]->getWidth();

        if (($firstLineWidth & 15) == 0) {
            return new static($this->lines);
        }

        $expectedLineLength = ($firstLineWidth + 16) & 240;

        $lines = [];
        foreach ($this->lines as $line) {
            $lineClone = clone $line;
            while ($lineClone->getWidth() < $expectedLineLength) {
                $lineClone->addPixel(0);
            }
            $lines[] = $lineClone;
        }

        return new static($lines);
    }

    public function matchesDimensionsOf(IndexedBitmap $indexedBitmap)
    {
        return $this->getWidth() == $indexedBitmap->getWidth() &&
            $this->getHeight() == $indexedBitmap->getHeight();
    }

    public function extractBitplanesToSprite(Sprite $sprite)
    {
        $spriteLineIndex = 0;

        foreach ($this->lines as $line) {
            for ($bitplane = 0; $bitplane <= 3; $bitplane++) {
                $sprite->setBitplaneWordSequenceAtLine(
                    $spriteLineIndex,
                    $bitplane,
                    $line->toBitplaneWordSequence($bitplane)
                );
            }
            $spriteLineIndex++;
        }
    }

    public function extractMaskToSprite(Sprite $sprite)
    {
        $spriteLineIndex = 0;

        foreach ($this->lines as $line) {
            $sprite->setMaskWordSequenceAtLine(
                $spriteLineIndex,
                $line->toMaskWordSequence()
            );
            $spriteLineIndex++;
        }
    }

    public function getWidth()
    {
        return $this->lines[0]->getWidth();
    }

    public function getHeight()
    {
        return count($this->lines);
    }
}

class Bitplane
{
    private $bits;

    public function __construct(array $bits)
    {
        if ((count($bits) & 15) != 0) {
            throw new RuntimeException('Bit count must be divisible by 16');
        }

        foreach ($bits as $bit) {
            if (!is_integer($bit)) {
                throw new RuntimeException('Non-integer value found in bit array: '.var_export($bit,1));
            }

            if ($bit != 0 && $bit != 1) {
                throw new RuntimeException('Bitplanes must only contain 0 or 1');
            }
        }

        $this->bits = $bits;
    }

    public function getShiftedCopy($bitsToShift)
    {
        if ($bitsToShift == 0) {
            return clone $this;
        } elseif ($bitsToShift > 15) {
            throw new RuntimeException('Invalid shift value');
        }

        $shiftedBits = array_merge(
            array_fill(0, $bitsToShift, 0),
            $bits,
            array_fill(0, 16 - $bitsToShift, 0)
        );

        return new static($shiftedBits);
    }

    public function toWordSequence()
    {
        $words = [];
        $bitsChunks = array_chunk($this->bits, 16);
        foreach ($bitsChunks as $bitsChunk) {
            $word = 0;
            for ($index = 0; $index < 16; $index++) {
                $bit = $bitsChunk[$index];
                $word |= $bit;
                if ($index < 15) {
                    $word <<= 1;
                }
            }
            $words[] = $word;
        }

        return new WordSequence($words);
    }
}

class WordSequence
{
    private $words;

    public function __construct(array $words)
    {
        foreach ($words as $word) {
            if (!is_integer($word)) {
                throw new RuntimeException('Non-integer value found in word array: '.var_export($word,1));
            }

            if ($word < 0 || $word > 65535) {
                throw new RuntimeException('Word sequences must only contain unsigned 16-bit values');
            }

            $this->words = $words;
        }
    }

    public function getShiftedCopy($bitsToShift)
    {
        $bits = [];
        foreach ($this->words as $word) {
            for ($index = 0; $index < 16; $index++) {
                $bits[] = $word & 1;
                $word >>= 1;
            }
        }

        $bitplane = new Bitplane($bits);
        $shiftedBitplane = $bitplane->getShiftedCopy($bitsToShift);
        return $shiftedBitplane->toWordSequence();
    }

    public function getSpriteWidth()
    {
        return count($this->words) * 16;
    }
}

class SpriteLine {
    const HIGHEST_BITPLANE = 3;

    private $sprite;
    private $bitplaneWordSequences;
    private $maskWordSequence;

    public function __construct(Sprite $sprite)
    {
        $this->sprite = $sprite;
        $this->bitplaneWordSequences = [];
        $this->maskWordSequence = null;
    }

    public function setBitplaneWordSequence(int $bitplaneIndex, WordSequence $wordSequence)
    {
        if ($wordSequence->getSpriteWidth() != $this->sprite->getWidth()) {
            throw new RuntimeException('Word sequence sprite width must match owning sprite width');
        }

        if ($bitplaneIndex < 0 || $bitplaneIndex > self::HIGHEST_BITPLANE) {
            throw new RuntimeException('Bitplane index must be from 0 to 3 inclusive');
        }

        $this->bitplaneWordSequences[$bitplaneIndex] = $wordSequence;
    }

    public function setMaskWordSequence(WordSequence $wordSequence)
    {
        if (!$this->sprite->hasMask()) {
            throw new RuntimeException('Mask specified, but owning sprite specifies no mask');
        }

        if ($wordSequence->getSpriteWidth() != $this->sprite->getWidth()) {
            throw new RuntimeException('Word sequence sprite width must match owning sprite width');
        }

        $this->maskWordSequence = $wordSequence;
    }

    public function isComplete()
    {
        $requiresMask = $this->sprite->hasMask();

        for ($index = 0; $index <= self::HIGHEST_BITPLANE; $index++) {
            if (!isset($this->bitplaneWordSequences[$index])) {
                return false;
            }
        }

        if ($this->sprite->hasMask() && !isset($this->maskWordSequence)) {
            return false;
        }

        return true;
    }

    public function getShiftedCopy(Sprite $sprite, int $bitsToShift)
    {
        for ($index = 0; $index <= self::HIGHEST_BITPLANE; $index++) {
            if (!issset($this->bitplaneWordSequences[$index])) {
                throw new RuntimeException('No WordSequence has been provided for bitplane ' . $index);
            }
        }

        $shiftedSpriteLine = new static($sprite);

        for ($index = 0; $index <= self::HIGHEST_BITPLANE; $index++) {
            $shiftedSpriteLine->setBitplaneWordSequence(
                $index,
                $this->bitplaneWordSequences[$index]->getShiftedCopy($bitsToShift)
            );
        }

        if (isset($this->maskWordSequence)) {
            $shiftedSpriteLine->setMaskWordSequence(
                $this->maskWordSequence->getShiftedCopy($bitsToShift)
            );
        }

        return $shiftedSpriteLine;
    }

    public function belongsTo(Sprite $sprite)
    {
        return $sprite === $this->sprite;
    }
}

class Sprite {
    private $spriteLines = [];
    private $masked;
    private $width;

    public function __construct(int $width, int $height, bool $masked)
    {
        if ($width < 1) {
            throw new RuntimeException('Sprite width must be greater than 0');
        } elseif (($width & 15) != 0) {
            throw new RuntimeException('Sprite width must be divisible by 16');
        } elseif ($height < 1) {
            throw new RuntimeException('Sprite height must be greater than 0');
        }

        for ($index = 0; $index < $height; $index++) {
            $this->spriteLines[] = new SpriteLine($this);
        }

        $this->width = $width;
        $this->masked = $masked;
    }

    private function getSpriteLine(int $ypos)
    {
        if (!isset($this->spriteLines[$ypos])) {
            throw new RuntimeException('Requested SpriteLine ' . $ypos . ' does not exist');
        }

        return $this->spriteLines[$ypos];
    }
    
    public function setBitplaneWordSequenceAtLine(int $lineIndex, int $bitplaneIndex, WordSequence $wordSequence)
    {
        $this->getSpriteLine($lineIndex)->setBitplaneWordSequence($bitplaneIndex, $wordSequence);
    }

    public function setMaskWordSequenceAtLine(int $lineIndex, WordSequence $wordSequence)
    {
        $this->getSpriteLine($lineIndex)->setMaskWordSequence($wordSequence);
    }

    public function hasMask()
    {
        return $this->masked;
    }

    public function isComplete()
    {
        foreach ($this->spriteLines as $spriteLine) {
            if (!$spriteLine->isComplete()) {
                return false;
            }
        }

        return true;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function exportAsBinaryPlanar($shiftCount = 0)
    {
        if (!$this->isComplete()) {
            throw new RuntimeException('Cannot export incomplete sprite');
        }
        // we want to export like this:
        // one word of mask (if applicable)
        // four words of bitplane
    }

    public function exportAsCompiledSpriteAssembly($shiftCount = 0)
    {
        if (!$this->isComplete()) {
            throw new RuntimeException('Cannot export incomplete sprite');
        }
        // we want to iterate through the mask a word at a time
        // we want to iterate through the graphics a longword at a time
    }
}


class SpriteBuilder
{
    public static function createUnmaskedSprite(IndexedBitmap $bitmapData)
    {
        $sprite = new Sprite($bitmapData->getWidth(), $bitmapData->getHeight(), false);
        $bitmapData->extractBitplanesToSprite($sprite);
    }

    public static function createMaskedSprite(IndexedBitmap $bitmapData, IndexedBitmap $maskData)
    {
        if (!$bitmapData->matchesDimensionsOf($maskData)) {
            throw new RuntimeException('Bitmap data does not match dimensions of mask data');
        }

        $sprite = new Sprite($bitmapData->getWidth(), $bitmapData->getHeight(), true);
        $bitmapData->extractBitplanesToSprite($sprite);
        $maskData->extractMaskToSprite($sprite);

        return $sprite;
    }
}

$indexedBitmap = IndexedBitmap::load('megaman.data', 320, 318);
$bossBitmapData = $indexedBitmap->extractRegionToIndexedBitmap(0, 220, 109, 98)
    ->getCopyRoundedTo16PixelDivisibleWidth();
$bossMaskData = $indexedBitmap->extractRegionToIndexedBitmap(109, 220, 109, 98)
    ->getCopyRoundedTo16PixelDivisibleWidth();

$maskedSprite = SpriteBuilder::createMaskedSprite($bossBitmapData, $bossMaskData);

var_dump($maskedSprite);
