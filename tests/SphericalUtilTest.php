<?php

use Javidalpe\Maps\LatLng;
use Javidalpe\Maps\MathUtil;
use Javidalpe\Maps\SphericalUtil;
use PHPUnit\Framework\TestCase;

class SphericalUtilTest extends TestCase
{

	// The vertices of an octahedron, for testing
	private $up;
	private $down;
	private $front;
	private $right;
	private $back;
	private $left;

	/**
	 * SphericalUtilTest constructor.
	 */
	function setUp()
	{
		$this->up = new LatLng(90, 0);
		$this->down = new LatLng(-90, 0);
		$this->front = new LatLng(0, 0);
		$this->right = new LatLng(0, 90);
		$this->back = new LatLng(0, -180);
		$this->left = new LatLng(0, -90);
	}

	/**
	 * Tests for approximate equality.
	 *
	 * @param LatLng $actual
	 * @param LatLng $expected
	 */
	private function expectLatLngApproxEquals(LatLng $actual, LatLng $expected)
	{
		self::expectNearNumber($actual->latitude, $expected->latitude, 1e-6);
		// Account for the convergence of longitude lines at the poles
		$cosLat = cos(deg2rad($actual->latitude));
		self::expectNearNumber($cosLat * $actual->longitude, $cosLat * $expected->longitude, 1e-6);
	}

	/**
	 * @param $actual
	 * @param $expected
	 * @param $epsilon
	 */
	private function expectNearNumber($actual, $expected, $epsilon)
	{
		if (abs($actual) == 180 && abs($expected) == 180) { //Strange fix to resolve -180 equal to 180
			$this->assertTrue(true);
		} else {
			$this->assertTrue(
				abs($expected - $actual) <= $epsilon, sprintf("Expected %g to be near %g", $actual, $expected));
		}
	}

	public function testAngles(): void
	{
		// Same vertex
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->up, $this->up), 0, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->down, $this->down), 0, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->left, $this->left), 0, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->right, $this->right), 0, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->front, $this->front), 0, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->back, $this->back), 0, 1e-6);

		// Adjacent vertices
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->up, $this->front), M_PI / 2, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->up, $this->right), M_PI / 2, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->up, $this->back), M_PI / 2, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->up, $this->left), M_PI / 2, 1e-6);

		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->down, $this->front), M_PI / 2, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->down, $this->right), M_PI / 2, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->down, $this->back), M_PI / 2, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->down, $this->left), M_PI / 2, 1e-6);

		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->back, $this->up), M_PI / 2, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->back, $this->right), M_PI / 2, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->back, $this->down), M_PI / 2, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->back, $this->left), M_PI / 2, 1e-6);

		// Opposite vertices
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->up, $this->down), M_PI, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->front, $this->back), M_PI, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeAngleBetween($this->left, $this->right), M_PI, 1e-6);
	}

	public function testDistances()
	{
		$this->expectNearNumber(SphericalUtil::computeDistanceBetween($this->up, $this->down),
			M_PI * MathUtil::EARTH_RADIUS, 1e-6);
	}

	public function testHeadings()
	{
		// Opposing vertices for which there is a result
		$this->expectNearNumber(SphericalUtil::computeHeading($this->up, $this->down), -180, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeHeading($this->down, $this->up), 0, 1e-6);

		// Adjacent vertices for which there is a result
		$this->expectNearNumber(SphericalUtil::computeHeading($this->front, $this->up), 0, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeHeading($this->right, $this->up), 0, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeHeading($this->back, $this->up), 0, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeHeading($this->down, $this->up), 0, 1e-6);

		$this->expectNearNumber(SphericalUtil::computeHeading($this->front, $this->down), -180, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeHeading($this->right, $this->down), -180, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeHeading($this->back, $this->down), -180, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeHeading($this->left, $this->down), -180, 1e-6);

		$this->expectNearNumber(SphericalUtil::computeHeading($this->right, $this->front), -90, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeHeading($this->left, $this->front), 90, 1e-6);

		$this->expectNearNumber(SphericalUtil::computeHeading($this->front, $this->right), 90, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeHeading($this->back, $this->right), -90, 1e-6);
	}

	public function testComputeOffset()
	{
		// From $this->front
		$this->expectLatLngApproxEquals(
			$this->front, SphericalUtil::computeOffset($this->front, 0, 0));
		$this->expectLatLngApproxEquals(
			$this->up, SphericalUtil::computeOffset($this->front, M_PI * MathUtil::EARTH_RADIUS / 2, 0));
		$this->expectLatLngApproxEquals(
			$this->down, SphericalUtil::computeOffset($this->front, M_PI * MathUtil::EARTH_RADIUS / 2, 180));
		$this->expectLatLngApproxEquals(
			$this->left, SphericalUtil::computeOffset($this->front, M_PI * MathUtil::EARTH_RADIUS / 2, -90));
		$this->expectLatLngApproxEquals(
			$this->right, SphericalUtil::computeOffset($this->front, M_PI * MathUtil::EARTH_RADIUS / 2, 90));
		$this->expectLatLngApproxEquals(
			$this->back, SphericalUtil::computeOffset($this->front, M_PI * MathUtil::EARTH_RADIUS, 0));
		$this->expectLatLngApproxEquals(
			$this->back, SphericalUtil::computeOffset($this->front, M_PI * MathUtil::EARTH_RADIUS, 90));

		// From $this->left
		$this->expectLatLngApproxEquals(
			$this->left, SphericalUtil::computeOffset($this->left, 0, 0));
		$this->expectLatLngApproxEquals(
			$this->up, SphericalUtil::computeOffset($this->left, M_PI * MathUtil::EARTH_RADIUS / 2, 0));
		$this->expectLatLngApproxEquals(
			$this->down, SphericalUtil::computeOffset($this->left, M_PI * MathUtil::EARTH_RADIUS / 2, 180));
		$this->expectLatLngApproxEquals(
			$this->front, SphericalUtil::computeOffset($this->left, M_PI * MathUtil::EARTH_RADIUS / 2, 90));
		$this->expectLatLngApproxEquals(
			$this->back, SphericalUtil::computeOffset($this->left, M_PI * MathUtil::EARTH_RADIUS / 2, -90));
		$this->expectLatLngApproxEquals(
			$this->right, SphericalUtil::computeOffset($this->left, M_PI * MathUtil::EARTH_RADIUS, 0));
		$this->expectLatLngApproxEquals(
			$this->right, SphericalUtil::computeOffset($this->left, M_PI * MathUtil::EARTH_RADIUS, 90));

		// NOTE(appleton): Heading is undefined at the poles, so we do not test
		// from $this->up/$this->down.
	}

	public function testComputeOffsetOrigin()
	{
		$this->expectLatLngApproxEquals(
			$this->front, SphericalUtil::computeOffsetOrigin($this->front, 0, 0));

		$this->expectLatLngApproxEquals(
			$this->front, SphericalUtil::computeOffsetOrigin(new LatLng(0, 45),
			M_PI * MathUtil::EARTH_RADIUS / 4, 90));
		$this->expectLatLngApproxEquals(
			$this->front, SphericalUtil::computeOffsetOrigin(new LatLng(0, -45),
			M_PI * MathUtil::EARTH_RADIUS / 4, -90));
		$this->expectLatLngApproxEquals(
			$this->front, SphericalUtil::computeOffsetOrigin(new LatLng(45, 0),
			M_PI * MathUtil::EARTH_RADIUS / 4, 0));
		$this->expectLatLngApproxEquals(
			$this->front, SphericalUtil::computeOffsetOrigin(new LatLng(-45, 0),
			M_PI * MathUtil::EARTH_RADIUS / 4, 180));

		// Situations with no solution, should return null.
		//
		// First 'over' the pole.
		$this->assertEquals(null, SphericalUtil::computeOffsetOrigin(new LatLng(80, 0),
			M_PI * MathUtil::EARTH_RADIUS / 4, 180));
		// Second a distance that doesn't fit on the earth.
		$this->assertEquals(null, SphericalUtil::computeOffsetOrigin(new LatLng(80, 0),
			M_PI * MathUtil::EARTH_RADIUS / 4, 90));
	}

	public function testComputeOffsetAndBackToOrigin()
	{
		$start = new LatLng(40, 40);
		$distance = 1e5;
		$heading = 15;

		// Some semi-random values to demonstrate going forward and $this->backward yields
		// the same location.
		$end = SphericalUtil::computeOffset($start, $distance, $heading);
		$this->expectLatLngApproxEquals(
			$start, SphericalUtil::computeOffsetOrigin($end, $distance, $heading));

		$heading = -37;
		$end = SphericalUtil::computeOffset($start, $distance, $heading);
		$this->expectLatLngApproxEquals(
			$start, SphericalUtil::computeOffsetOrigin($end, $distance, $heading));

		$distance = 3.8e+7;
		$end = SphericalUtil::computeOffset($start, $distance, $heading);
		$this->expectLatLngApproxEquals(
			$start, SphericalUtil::computeOffsetOrigin($end, $distance, $heading));

		$start = new LatLng(-21, -73);
		$end = SphericalUtil::computeOffset($start, $distance, $heading);
		$this->expectLatLngApproxEquals(
			$start, SphericalUtil::computeOffsetOrigin($end, $distance, $heading));

		// computeOffsetOrigin with multiple solutions, all we care about is that
		// going from there yields the requested result.
		//
		// First, for this particular situation the latitude is completely arbitrary.
		$start = SphericalUtil::computeOffsetOrigin(new LatLng(0, 90),
			M_PI * MathUtil::EARTH_RADIUS / 2, 90);
		$this->expectLatLngApproxEquals(
			new LatLng(0, 90),
			SphericalUtil::computeOffset($start, M_PI * MathUtil::EARTH_RADIUS / 2, 90));

		// Second, for this particular situation the longitude is completely
		// arbitrary.
		$start = SphericalUtil::computeOffsetOrigin(new LatLng(90, 0),
			M_PI * MathUtil::EARTH_RADIUS / 4, 0);
		$this->expectLatLngApproxEquals(
			new LatLng(90, 0),
			SphericalUtil::computeOffset($start, M_PI * MathUtil::EARTH_RADIUS / 4, 0));
	}

	public function testInterpolate()
	{
		// Same point
		$this->expectLatLngApproxEquals(
			$this->up, SphericalUtil::interpolate($this->up, $this->up, 1 / 2.0));
		$this->expectLatLngApproxEquals(
			$this->down, SphericalUtil::interpolate($this->down, $this->down, 1 / 2.0));
		$this->expectLatLngApproxEquals(
			$this->left, SphericalUtil::interpolate($this->left, $this->left, 1 / 2.0));

		// Between $this->front and $this->up
		$this->expectLatLngApproxEquals(
			new LatLng(1, 0), SphericalUtil::interpolate($this->front, $this->up, 1 / 90.0));
		$this->expectLatLngApproxEquals(
			new LatLng(1, 0), SphericalUtil::interpolate($this->up, $this->front, 89 / 90.0));
		$this->expectLatLngApproxEquals(
			new LatLng(89, 0), SphericalUtil::interpolate($this->front, $this->up, 89 / 90.0));
		$this->expectLatLngApproxEquals(
			new LatLng(89, 0), SphericalUtil::interpolate($this->up, $this->front, 1 / 90.0));

		// Between $this->front and $this->down
		$this->expectLatLngApproxEquals(
			new LatLng(-1, 0), SphericalUtil::interpolate($this->front, $this->down, 1 / 90.0));
		$this->expectLatLngApproxEquals(
			new LatLng(-1, 0), SphericalUtil::interpolate($this->down, $this->front, 89 / 90.0));
		$this->expectLatLngApproxEquals(
			new LatLng(-89, 0), SphericalUtil::interpolate($this->front, $this->down, 89 / 90.0));
		$this->expectLatLngApproxEquals(
			new LatLng(-89, 0), SphericalUtil::interpolate($this->down, $this->front, 1 / 90.0));

		// Between $this->left and $this->back
		$this->expectLatLngApproxEquals(
			new LatLng(0, -91), SphericalUtil::interpolate($this->left, $this->back, 1 / 90.0));
		$this->expectLatLngApproxEquals(
			new LatLng(0, -91), SphericalUtil::interpolate($this->back, $this->left, 89 / 90.0));
		$this->expectLatLngApproxEquals(
			new LatLng(0, -179), SphericalUtil::interpolate($this->left, $this->back, 89 / 90.0));
		$this->expectLatLngApproxEquals(
			new LatLng(0, -179), SphericalUtil::interpolate($this->back, $this->left, 1 / 90.0));

		// geodesic crosses pole
		$this->expectLatLngApproxEquals(
			$this->up, SphericalUtil::interpolate(new LatLng(45, 0), new LatLng(45, 180), 1 / 2.0));
		$this->expectLatLngApproxEquals(
			$this->down,
			SphericalUtil::interpolate(new LatLng(-45, 0), new LatLng(-45, 180), 1 / 2.0));
	}

	public function testComputeLength()
	{

		$this->expectNearNumber(SphericalUtil::computeLength([]), 0, 1e-6);
		$this->expectNearNumber(SphericalUtil::computeLength([new LatLng(0, 0)]), 0, 1e-6);

		$latLngs = [new LatLng(0, 0), new LatLng(0.1, 0.1)];
		$this->expectNearNumber(SphericalUtil::computeLength($latLngs),
			deg2rad(0.1) * sqrt(2) * MathUtil::EARTH_RADIUS, 1);

		$latLngs = [new LatLng(0, 0), new LatLng(90, 0), new LatLng(0, 90)];
		$this->expectNearNumber(SphericalUtil::computeLength($latLngs), M_PI * MathUtil::EARTH_RADIUS, 1e-6);
	}

	private function computeSignedTriangleArea(LatLng $a, LatLng $b, LatLng $c)
	{
		return SphericalUtil::computeSignedArea([$a, $b, $c], 1);
	}

	private function computeTriangleArea(LatLng $a, LatLng $b, LatLng $c)
	{
		return abs($this->computeSignedTriangleArea($a, $b, $c));
	}

	private function isCCW(LatLng $a, LatLng $b, LatLng $c)
	{
		return $this->computeSignedTriangleArea($a, $b, $c) > 0 ? 1 : -1;
	}

	public function testIsCCW()
	{
		// One face of the octahedron
		$this->assertEquals(1, $this->isCCW($this->right, $this->up, $this->front));
		$this->assertEquals(1, $this->isCCW($this->up, $this->front, $this->right));
		$this->assertEquals(1, $this->isCCW($this->front, $this->right, $this->up));
		$this->assertEquals(-1, $this->isCCW($this->front, $this->up, $this->right));
		$this->assertEquals(-1, $this->isCCW($this->up, $this->right, $this->front));
		$this->assertEquals(-1, $this->isCCW($this->right, $this->front, $this->up));
	}

	public function testComputeTriangleArea()
	{
		$this->expectNearNumber($this->computeTriangleArea($this->right, $this->up, $this->front), M_PI / 2, 1e-6);
		$this->expectNearNumber($this->computeTriangleArea($this->front, $this->up, $this->right), M_PI / 2, 1e-6);

		// computeArea returns area of zero on small polys
		$area = $this->computeTriangleArea(
			new LatLng(0, 0),
			new LatLng(0, rad2deg(1E-6)),
			new LatLng(rad2deg(1E-6), 0));
		$expectedArea = 1E-12 / 2;

		$this->assertTrue(abs($expectedArea - $area) < 1e-20);
	}

	public function testComputeSignedTriangleArea()
	{
		$this->expectNearNumber(
			$this->computeSignedTriangleArea(
				new LatLng(0, 0), new LatLng(0, 0.1), new LatLng(0.1, 0.1)),
			deg2rad(0.1) * deg2rad(0.1) / 2, 1e-6);

		$this->expectNearNumber($this->computeSignedTriangleArea($this->right, $this->up, $this->front),
			M_PI / 2, 1e-6);

		$this->expectNearNumber($this->computeSignedTriangleArea($this->front, $this->up, $this->right),
			-M_PI / 2, 1e-6);
	}

	public function testComputeArea()
	{
		$this->expectNearNumber(SphericalUtil::computeArea([$this->right, $this->up, $this->front, $this->down, $this->right]),
			M_PI * MathUtil::EARTH_RADIUS * MathUtil::EARTH_RADIUS, .4);

		$this->expectNearNumber(SphericalUtil::computeArea([$this->right, $this->down, $this->front, $this->up, $this->right]),
			M_PI * MathUtil::EARTH_RADIUS * MathUtil::EARTH_RADIUS, .4);
	}

	public function testComputeSignedArea()
	{
		$path = [$this->right, $this->up, $this->front, $this->down, $this->right];
		$pathReversed = [$this->right, $this->down, $this->front, $this->up, $this->right];
		$this->assertEquals(-SphericalUtil::computeSignedArea($path), SphericalUtil::computeSignedArea($pathReversed));
	}
}