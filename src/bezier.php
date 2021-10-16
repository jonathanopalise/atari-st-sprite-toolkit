<?php

class Point2d
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

    public function equals(Point2d $point2d)
    {
        return $this->x == $point2d->getX() &&
            $this->y == $point2d->getY();
    }

    public function interpolateWith(Point2d $point2d, $divisor, $multiplier)
    {
        $x1 = $this->x;
        $y1 = $this->y;

        $x2 = $point2d->getX();
        $y2 = $point2d->getY();

        $x = $x1 + (($x2 - $x1) * $multiplier / $divisor);
        $y = $y1 + (($y2 - $y1) * $multiplier / $divisor);

        return new Point2d($x, $y);
    }

    public function getDistanceTo(Point2d $point2d)
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

    public function addPoint(Point2d $point)
    {
        if (is_null($this->lastPointAdded)) {
            $this->lastPointAdded = $point;
            $this->distanceAtLastPoint = 0;
        } else {
            $distanceFromLastPointToThisPoint = $this->lastPointAdded->getDistanceTo($point);

            //var_dump($this->lastPointAdded);
            //var_dump($point);
            //echo("distance between points: " . $distanceFromLastPointToThisPoint . "\n");
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
        //echo("total distance = " . $totalDistance);
        $points = [];

        while ($currentPointDistance < $totalDistance) {
            //echo("get point at distance for ".$currentPointDistance."\n");
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

    public function __construct(Point2d $point1, Point2d $controlPoint1, Point2d $controlPoint2, Point2d $point2)
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

        return new Point2d($x, $y);
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

$curveString = "M 148.77622,29.514983 C 204.17256,29.873562 255.69519,30.375218 315.74697,30.532383 344.89726,30.608673 367.05072,61.673071 353.02148,85.939157 328.06032,129.11399 297.50343,185.55801 267.32408,239.04748 258.49945,254.68813 218.04447,254.43144 204.02282,239.82166 191.79173,227.07753 185.741,208.79634 197.36226,188.54232 202.202,180.10741 210.9043,172.53939 219.88298,168.80387 230.90106,164.21987 240.92849,166.31332 249.84883,162.58527 259.33582,158.6204 268.22701,144.02522 268.17685,133.59827 268.10685,119.04062 249.74406,104.05326 234.87703,103.92459 204.1181,103.65838 180.05052,104.93165 145.51193,104.64835 128.62736,104.50986 109.54457,82.289178 109.59593,65.095804 109.64503,48.644448 129.06092,29.387366 148.77622,29.514983 Z";

/*$curveString = "
148.77622,29.514983 <-
204.17256,29.873562
255.69519,30.375218
315.74697,30.532383 <-
344.89726,30.608673
367.05072,61.673071
353.02148,85.939157 <-
328.06032,129.11399
297.50343,185.55801
267.32408,239.04748 <-
258.49945,254.68813
218.04447,254.43144
204.02282,239.82166 <-
191.79173,227.07753
185.741,208.79634
197.36226,188.54232 <-
202.202,180.10741
210.9043,172.53939
219.88298,168.80387 <-
230.90106,164.21987
240.92849,166.31332
249.84883,162.58527 <-
259.33582,158.6204
268.22701,144.02522
268.17685,133.59827 <-
268.10685,119.04062
249.74406,104.05326
234.87703,103.92459 <-
204.1181,103.65838
180.05052,104.93165
145.51193,104.64835 <-
128.62736,104.50986
109.54457,82.289178
109.59593,65.095804 <-
109.64503,48.644448
129.06092,29.387366
148.77622,29.514983 <- ";*/


$explodedCurveStringElements = explode(' ', $curveString);

$coordinates = [];
foreach ($explodedCurveStringElements as $potentialCoordinatePair) {
    $potentialCoordinatePairElements = explode(',', $potentialCoordinatePair);
    if (count($potentialCoordinatePairElements) == 2) {
        $potentialXCoordinate = $potentialCoordinatePairElements[0];
        $potentialYCoordinate = $potentialCoordinatePairElements[1];

        if (is_numeric($potentialXCoordinate) && is_numeric($potentialYCoordinate)) {
            $coordinates[] = new Point2d(
                floatval($potentialXCoordinate),
                floatval($potentialYCoordinate)
            );
        }
    }
}

$coordinatesCount = count($coordinates);

if (count($coordinates) == 0) {
    echo("no coordinates detected, exiting\n");
    exit(1);
}

if (!($coordinates[0]->equals($coordinates[$coordinatesCount-1]))) {
    echo("coordinates do not form a loop, exiting\n");
    exit();
}

//array_pop($coordinates);
//var_dump($coordinates);
$coordinatesCount = count($coordinates);

if ($coordinatesCount % 3 != 1) {
    echo("number of coordinates is not as expected, exiting\n");
    exit();
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

$cubicBezierSequencePoints = $cubicBezierSequence->getPoints();

$segmentSequence = new SegmentSequence();
foreach ($cubicBezierSequencePoints as $point) {
    $segmentSequence->addPoint($point);
}

//var_dump($segmentSequence);

$evenlySpacedPoints = $segmentSequence->deriveEvenlySpacedPoints(6);

//var_dump($evenlySpacedPoints);

foreach ($evenlySpacedPoints as $point) {
    echo($point->getx() . " " . $point->getY() . "\n");
}

