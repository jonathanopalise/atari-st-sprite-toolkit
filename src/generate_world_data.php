<?php

define('ENTITY_TYPE_LOG', 0);
define('ENTITY_TYPE_SCENERY', 1);

class Point
{
    private $x;
    private $y;

    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function getX()
    {
        return $this->x;
    }

    public function getY()
    {
        return $this->y;
    }

    public function equals(Point $point2d)
    {
        return $this->x == $point2d->getX() &&
            $this->y == $point2d->getY();
    }

    public function interpolateWith(Point $point2d, $divisor, $multiplier)
    {
        $x1 = $this->x;
        $y1 = $this->y;

        $x2 = $point2d->getX();
        $y2 = $point2d->getY();

        $x = $x1 + (($x2 - $x1) * $multiplier / $divisor);
        $y = $y1 + (($y2 - $y1) * $multiplier / $divisor);

        return new Point($x, $y);
    }

    public function getDistanceTo(Point $point2d)
    {
        $x1 = $this->x;
        $y1 = $this->y;

        $x2 = $point2d->getX();
        $y2 = $point2d->getY();

        $xDiff = $x2 - $x1;
        $yDiff = $y2 - $y1;

        return sqrt(($xDiff * $xDiff) + ($yDiff * $yDiff));
    }
}

class Entity
{
    private $point;
    private $type;
    private $appearance;

    public function __construct(Point $point, $type, $appearance)
    {
        $this->point = $point;
        $this->type = $type;
        $this->appearance = $appearance;
    }

    public function getX()
    {
        return $this->point->getX();
    }

    public function getY()
    {
        return $this->point->getY();
    }

    public function getXAsInt()
    {
        return intval($this->getX());
    }

    public function getYAsInt()
    {
        return intval($this->getY());
    }

    public function getType()
    {
        return $this->type;
    }

    public function getAppearance()
    {
        return $this->appearance;
    }

    public function createRelocatedClone($x, $y)
    {
        return new self(
            new Point($x, $y),
            $this->type,
            $this->appearance
        );
    }
}

class World
{
    private $entities;
    private $logCount;

    public function __construct(array $entities, int $logCount)
    {
        foreach ($entities as $entity) {
            if (!$entity instanceof Entity) {
                throw new RuntimeException('Unexpected value found in entity array');
            }
        }

        $this->entities = $entities;
        $this->logCount = $logCount;
    }

    public function getLogCount()
    {
        return $this->logCount;
    }

    public function getEntityCount()
    {
        return count($this->entities);
    }

    public function getEntities()
    {
        return $this->entities;
    }
}

class Segment
{
    private $startDistance;
    private $point1;
    private $point2;
    private $endDistance;

    public function Segment($startDistance, $point1, $point2, $endDistance)
    {
        $this->startDistance = $startDistance;
        $this->point1 = $point1;
        $this->point2 = $point2;
        $this->endDistance = $endDistance;
    }

    public function containsDistance($distance)
    {
        return ($this->startDistance <= $distance && $this->endDistance >= $distance);
    }

    public function getPointAtDistance($distance)
    {
        if (!$this->containsDistance($distance)) {
            throw new RuntimeException('Segment does not contain distance');
        }

        $distanceIntoSegment = $distance - $this->startDistance;
        $segmentLength = $this->endDistance - $this->startDistance;

        return $this->point1->interpolateWith($this->point2, $segmentLength, $distanceIntoSegment);
    }

    public function getStartDistance()
    {
        return $this->startDistance;
    }

    public function getLength()
    {
        return $this->endDistance - $this->startDistance;
    }
}

class SegmentSequence
{
    private $segments = [];
    private $distanceAtLastPoint = 0;
    private $lastPointAdded = null;

    public function addPoint(Point $point)
    {
        if (is_null($this->lastPointAdded)) {
            $this->lastPointAdded = $point;
            $this->distanceAtLastPoint = 0;
        } else {
            $distanceFromLastPointToThisPoint = $this->lastPointAdded->getDistanceTo($point);

            $newDistanceAtLastPoint = $this->distanceAtLastPoint + $distanceFromLastPointToThisPoint;

            $this->segments[] = new Segment(
                $this->distanceAtLastPoint,
                $this->lastPointAdded,
                $point,
                $newDistanceAtLastPoint
            );

            $this->distanceAtLastPoint = $newDistanceAtLastPoint;
            $this->lastPointAdded = $point;
        }
    }

    public function deriveEvenlySpacedPoints($distanceBetweenPoints)
    {
        $currentPointDistance = 0;
        $totalDistance = $this->getTotalDistance();
        $points = [];

        while ($currentPointDistance < $totalDistance) {
            $points[] = $this->getPointAtDistance($currentPointDistance);
            $currentPointDistance += $distanceBetweenPoints;
        }

        return $points;
    }

    public function getPointAtDistance($distance)
    {
        foreach ($this->segments as $segment) {
            if ($segment->containsDistance($distance)) {
                return $segment->getPointAtDistance($distance);
            }
        }

        throw new RuntimeException('getPointAtDistance failed');
    }

    public function getTotalDistance()
    {
        $totalDistance = 0;

        foreach ($this->segments as $segment) {
            $totalDistance += $segment->getLength();
        }

        return $totalDistance;
    }
}

class PointCollection
{
    private $points;

    public function __construct(array $points)
    {
        foreach ($points as $point) {
            if (!$point instanceof Entity) {
                throw new RuntimeException('Unexpected type in array');
            }
        }

        $this->points = $points;
    }

    public function getRealignedToBoundingBox($desiredMinX, $desiredMinY, $desiredMaxX, $desiredMaxY)
    {
        $actualMinX = null;
        $actualMaxX = null;
        $actualMinY = null;
        $actualMaxY = null;

        foreach ($this->points as $point) {
            $pointX = $point->getX();
            $pointY = $point->getY();

            if (is_null($actualMinX) || $pointX < $actualMinX) {
                $actualMinX = $pointX;
            }

            if (is_null($actualMaxX) || $pointX > $actualMaxX) {
                $actualMaxX = $pointX;
            }

            if (is_null($actualMinY) || $pointY < $actualMinY) {
                $actualMinY = $pointY;
            }

            if (is_null($actualMaxY) || $pointY > $actualMaxY) {
                $actualMaxY = $pointY;
            }
        }

        $desiredWidth = $desiredMaxX - $desiredMinX;
        $desiredHeight = $desiredMaxY - $desiredMinY;

        $actualWidth = $actualMaxX - $actualMinX;
        $actualHeight = $actualMaxY - $actualMinY;

        $newPoints = [];
        foreach ($this->points as $point) {
            $fractionX = ($point->getX() - $actualMinX) / $actualWidth;
            $fractionY = ($point->getY() - $actualMinY) / $actualHeight;

            $newX = $desiredMinX + ($desiredWidth * $fractionX);
            $newY = $desiredMinY + ($desiredHeight * $fractionY);

            $newPoints[] = $point->createRelocatedClone($newX, $newY);
        }

        return $newPoints;
    }

    public function getPoints()
    {
        return $this->points;
    }
}

class CubicBezier
{
    private $point1;
    private $controlPoint1;
    private $controlPoint2;
    private $point2;

    public function __construct(Point $point1, Point $controlPoint1, Point $controlPoint2, Point $point2)
    {
        $this->point1 = $point1;
        $this->controlPoint1 = $controlPoint1;
        $this->controlPoint2 = $controlPoint2;
        $this->point2 = $point2;
    }

    public function getPointOnCurve($t)
    {
        $x1 = $this->point1->getX();
        $y1 = $this->point1->getY();

        $x2 = $this->controlPoint1->getX();
        $y2 = $this->controlPoint1->getY();

        $x3 = $this->controlPoint2->getX();
        $y3 = $this->controlPoint2->getY();

        $x4 = $this->point2->getX();
        $y4 = $this->point2->getY();

        $t2 = $t * $t;
        $t3 = $t * $t * $t;
        $x = $t3*$x4 + (3*$t2 - 3*$t3)*$x3 + (3*$t3 - 6*$t2 + 3*$t)*$x2 + (3*$t2 - $t3 - 3*$t + 1)*$x1;
        $y = $t3*$y4 + (3*$t2 - 3*$t3)*$y3 + (3*$t3 - 6*$t2 + 3*$t)*$y2 + (3*$t2 - $t3 - 3*$t + 1)*$y1;

        return new Point($x, $y);
    }
}

class CubicBezierSequence
{
    private $cubicBeziers = [];

    public function addCubicBezier(CubicBezier $cubicBezier)
    {
        $this->cubicBeziers[] = $cubicBezier;
    }

    public function getPoints()
    {
        $points = [];

        foreach ($this->cubicBeziers as $cubicBezier) {
            $t = 0;
            for ($index = 0; $index < 99; $index++) {
                $points[] = $cubicBezier->getPointOnCurve($t);
                $t += 0.01;
            }
        }

        return $points;
    }
}

class CurveStringParser
{
    public function deriveCubicBezierSequence(string $curveString)
    {
        $explodedCurveStringElements = explode(' ', $curveString);

        $coordinates = [];
        foreach ($explodedCurveStringElements as $potentialCoordinatePair) {
            $potentialCoordinatePairElements = explode(',', $potentialCoordinatePair);
            if (count($potentialCoordinatePairElements) == 2) {
                $potentialXCoordinate = $potentialCoordinatePairElements[0];
                $potentialYCoordinate = $potentialCoordinatePairElements[1];

                if (is_numeric($potentialXCoordinate) && is_numeric($potentialYCoordinate)) {
                    $coordinates[] = new Point(
                        floatval($potentialXCoordinate),
                        floatval($potentialYCoordinate)
                    );
                }
            }
        }

        $coordinatesCount = count($coordinates);

        if (count($coordinates) == 0) {
            throw new RuntimeException('No coordinates detected');
        }

        if (!($coordinates[0]->equals($coordinates[$coordinatesCount-1]))) {
            throw new RuntimeException('coordinates do not form a loop');
        }

        if ($coordinatesCount % 3 != 1) {
            throw new RuntimeException('number of coordinates is not as expected');
        }

        $cubicBezierSequence = new CubicBezierSequence();

        $offset = 0;
        while ($offset < ($coordinatesCount - 2)) {
            $cubicBezierSequence->addCubicBezier(
                new CubicBezier(
                    $coordinates[$offset],
                    $coordinates[$offset+1],
                    $coordinates[$offset+2],
                    $coordinates[$offset+3]
                )
            );
            $offset += 3;
        }

        return $cubicBezierSequence;
    }
}

class WorldGenerator
{
    public function deriveWorldFromDomDocument(string $filename)
    {
        $document = DomDocument::load($filename);
        if ($document === false) {
            throw new RuntimeException('Unable to load svg file');
        }

        $pathElements = $document->getElementsByTagName('path');
        if (count($pathElements) != 1) {
            throw new RuntimeException('Zero or more than one path elements found');
        }

        $curveElement = $pathElements[0];
        $curveString = $curveElement->getAttribute('d');
        if ($curveString == '') {
            throw new RuntimeException('Unable to find track curvature attribute');
        }

        $curveStringParser = new CurveStringParser();
        $cubicBezierSequence = $curveStringParser->deriveCubicBezierSequence($curveString);

        $cubicBezierSequencePoints = $cubicBezierSequence->getPoints();

        $segmentSequence = new SegmentSequence();
        foreach ($cubicBezierSequencePoints as $point) {
            $segmentSequence->addPoint($point);
        }

        $logPoints = $segmentSequence->deriveEvenlySpacedPoints(8);
        $logCount = count($logPoints);

        $entities = [];
        foreach ($logPoints as $point) {
            $entities[] = new Entity($point, ENTITY_TYPE_LOG, 0);
        }

        $sceneryElements = $document->getElementsByTagName('ellipse');
        foreach ($sceneryElements as $sceneryElement) {
            $entities[] = $this->generateSceneryEntity($sceneryElement);
        }

        $entityCount = count($entities);

        $pointCollection = new PointCollection($entities);
        $realignedPoints = $pointCollection->getRealignedToBoundingBox(-16380, -16380, 16380, 16380);

        $world = new World($realignedPoints, $logCount);

        return $world;

        /*foreach ($realignedPoints as $point) {
            echo($point->getx() . " " . $point->getY() . "\n");
        }*/

        //var_dump(count($realignedPoints));
    }

    private function generateSceneryEntity($element)
    {
        $x = $element->getAttribute('cx');
        $y = $element->getAttribute('cy');

        $appearance = $this->extractAppearanceFromStyle(
            $element->getAttribute('style')
        );

        return new Entity(
            new Point($x, $y),
            ENTITY_TYPE_SCENERY,
            $appearance
        );
    }

    private function extractAppearanceFromStyle($style)
    {
        $rgbToAppearanceMappings = [
            '#800000' => 0,
            '#ff0000' => 1,
            '#808000' => 2,
            '#ffff00' => 3,
            '#008000' => 4,
            '#00ff00' => 5,
            '#008080' => 6,
            '#00ffff' => 7,
            '#000080' => 8,
        ];

        $styleElements = explode(';', $style);
        foreach ($styleElements as $styleElement) {
            $styleElementElements = explode(':', $styleElement);
            if (count($styleElementElements) != 2) {
                throw new RuntimeException('Malformed element within style tag');
            }

            if ($styleElementElements[0] == 'fill') {
                $rgb = $styleElementElements[1];
                if (!isset($rgbToAppearanceMappings[$rgb])) {
                    throw new RuntimeException('No appearance mapping for ' . $rgb);
                }

                return $rgbToAppearanceMappings[$rgb];
            }
        }
    }
}

if ($argc < 3) {
    echo("usage: generate_world [inputFile] [outputFile]");
    exit(1);
}

$inputFilename = $argv[1];
$outputFilename = $argv[2];
$worldGenerator = new WorldGenerator();

$world = $worldGenerator->deriveWorldFromDomDocument($inputFilename);

ob_start();
require('world_data_template.php');
$output = ob_get_clean();

$result = file_put_contents($outputFilename, $output);
if ($result === false) {
    echo("Unable to write world data");
    exit(1);
}
