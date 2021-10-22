<?php

define('ENTITY_TYPE_LOG', 0);
define('ENTITY_TYPE_SCENERY', 1);

class Point
{
    private $x;
    private $y;

    public function __construct(float $x, float $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function importFromData($x, $y)
    {
        return new Point($x, 16384 - $y);
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
    private $yaw;
    private $yawRadians;
    private $visibleEntities = [];

    public function __construct(Point $point, $type, $appearance, $yaw, $yawRadians, array $visibleEntities)
    {
        $this->point = $point;
        $this->type = $type;
        $this->appearance = $appearance;
        $this->yaw = $yaw;
        $this->yawRadians = $yawRadians;
        $this->visibleEntities = $visibleEntities;
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
        return intval($this->getX() - 16384);
    }

    public function getYAsInt()
    {
        return intval($this->getY() - 16384);
    }

    public function getType()
    {
        return $this->type;
    }

    public function getAppearance()
    {
        return $this->appearance;
    }

    public function getYaw()
    {
        return $this->yaw;
    }

    public function getYawRadians()
    {
        return $this->yawRadians;
    }

    public function getVisibleEntities()
    {
        return $this->visibleEntities;
    }

    public function setVisibleEntities(array $visibleEntities)
    {
        $this->visibleEntities = $visibleEntities;
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
                    $coordinates[] = Point::importFromData(
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

        $logPoints = $segmentSequence->deriveEvenlySpacedPoints(600);
        $logCount = count($logPoints);

        $entities = [];

        for ($pointIndex = 0; $pointIndex < count($logPoints); $pointIndex++) {
            $currentPointIndex = $pointIndex;
            if ($currentPointIndex > count($logPoints) - 1) {
                $currentPointIndex -= count($logPoints);
            }
            $point = $logPoints[$currentPointIndex];

            $nextPointIndex = $pointIndex + 1;
            if ($nextPointIndex > count($logPoints) - 1) {
                $nextPointIndex -= count($logPoints);
            }
            $nextPoint = $logPoints[$nextPointIndex];

            $yaw = atan2(
                $nextPoint->getY() - $point->getY(),
                $nextPoint->getX() - $point->getX()
            );

            $yaw += M_PI; // bring yaw into a 0 - M_PI * 2 range
            $yaw = (M_PI * 2) - $yaw; // reverse yaw
            $yaw -= M_PI * 0.5; // rotate by 270 degrees
            if ($yaw < 0) {
                $yaw += M_PI * 2;
            }

            $yawInteger = intval($yaw * 1024 / (2*M_PI));
            if ($yawInteger > 1023) {
                $yawInteger -= 1024;
            }

            $entities[] = new Entity($point, ENTITY_TYPE_LOG, 10, $yawInteger, $yaw, []);
        }

        $sceneryElements = $document->getElementsByTagName('circle');
        foreach ($sceneryElements as $sceneryElement) {
            $entities[] = $this->generateSceneryEntity($sceneryElement);
        }

        $entityCount = count($entities);

        for ($entityIndex = 0; $entityIndex < count($logPoints); $entityIndex++) {
            $entity = $entities[$entityIndex];
            $entity->setVisibleEntities(
                $this->deriveVisibleEntities($entities, $entities[$entityIndex])
            );
        }

        $world = new World($entities, $logCount);

        echo("world entity count: ".$entityCount."\n");
        echo("world log count: ".$logCount."\n");

        return $world;
    }

    private function generateSceneryEntity($element)
    {
        $x = $element->getAttribute('cx');
        $y = $element->getAttribute('cy');

        $appearance = $this->extractAppearanceFromStyle(
            $element->getAttribute('style')
        );

        return new Entity(
            Point::importFromData($x, $y),
            ENTITY_TYPE_SCENERY,
            $appearance,
            0,
            0,
            []
        );
    }

    private function deriveVisibleEntities(array $entities, Entity $cameraEntity)
    {
        $cameraX = $cameraEntity->getX();
        $cameraY = $cameraEntity->getY();
        $cameraYaw = $cameraEntity->getYawRadians();

        $v1 = new Point(- 8000, 16384);
        $v2 = new Point(8000, 16384);
        $v3 = new Point(0, - 500);

        $visibleEntities = [];
        for ($entityIndex = 0; $entityIndex < count($entities); $entityIndex++) {
            $visEntity = $entities[$entityIndex];

            $transformedX = $visEntity->getX() - $cameraX;
            $transformedY = $visEntity->getY() - $cameraY;

            $transformedAndRotatedX = $transformedX * cos($cameraYaw) - $transformedY * sin($cameraYaw);
            $transformedAndRotatedY = $transformedY * cos($cameraYaw) + $transformedX * sin($cameraYaw);

            $point = new Point(
                $transformedAndRotatedX,
                $transformedAndRotatedY
            );

            if ($this->pointInTriangle($point, $v1, $v2, $v3)) {
                $visibleEntities[] = $entityIndex;
            }
        }

        return $visibleEntities;
    }

    // https://stackoverflow.com/questions/2049582/how-to-determine-if-a-point-is-in-a-2d-triangle
    private function sign(Point $p1, Point $p2, Point $p3)
    {
        return ($p1->getX() - $p3->getX()) * ($p2->getY() - $p3->getY()) - ($p2->getX() - $p3->getX()) * ($p1->getY() - $p3->getY());
    }

    private function pointInTriangle(Point $pt, Point $v1, Point $v2, Point $v3)
    {
        $d1 = $this->sign($pt, $v1, $v2);
        $d2 = $this->sign($pt, $v2, $v3);
        $d3 = $this->sign($pt, $v3, $v1);

        $has_neg = ($d1 < 0) || ($d2 < 0) || ($d3 < 0);
        $has_pos = ($d1 > 0) || ($d2 > 0) || ($d3 > 0);

        return !($has_neg && $has_pos);
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
