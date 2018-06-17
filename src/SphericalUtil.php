<?php

namespace Javidalpe\Maps;

class SphericalUtil
{

	/**
	 * Returns the heading from one LatLng to another LatLng. Headings are
	 * expressed in degrees clockwise from North within the range [-180,180).
	 *
	 * @param LatLng $from
	 * @param LatLng $to
	 *
	 * @return int The heading in degrees clockwise from north.
	 */
	public static function computeHeading(LatLng $from, LatLng $to)
	{
		// http://williams.best.vwh.net/avform.htm#Crs
		$fromLat = deg2rad($from->latitude);
		$fromLng = deg2rad($from->longitude);
		$toLat = deg2rad($to->latitude);
		$toLng = deg2rad($to->longitude);
		$dLng = $toLng - $fromLng;
		$heading = atan2(
			sin($dLng) * cos($toLat),
			cos($fromLat) * sin($toLat) - sin($fromLat) * cos($toLat) * cos($dLng));

		return MathUtil::wrap(rad2deg($heading), -180, 180);
	}

	/**
	 * Returns the LatLng resulting from moving a distance from an origin
	 * in the specified heading (expressed in degrees clockwise from north).
	 *
	 * @param $from     LatLng    The LatLng $from which to start.
	 * @param $distance int The distance to travel.
	 * @param $heading  int  The heading in degrees clockwise from north.
	 *
	 * @return LatLng
	 */
	public static function computeOffset(LatLng $from, $distance, $heading)
	{
		$distance /= MathUtil::EARTH_RADIUS;
		$heading = deg2rad($heading);
		// http://williams.best.vwh.net/avform.htm#LL
		$fromLat = deg2rad($from->latitude);
		$fromLng = deg2rad($from->longitude);
		$cosDistance = cos($distance);
		$sinDistance = sin($distance);
		$sinFromLat = sin($fromLat);
		$cosFromLat = cos($fromLat);
		$sinLat = $cosDistance * $sinFromLat + $sinDistance * $cosFromLat * cos($heading);
		$dLng = atan2(
			$sinDistance * $cosFromLat * sin($heading),
			$cosDistance - $sinFromLat * $sinLat);

		return new LatLng(rad2deg(asin($sinLat)), rad2deg($fromLng + $dLng));
	}

	/**
	 * Returns the location of origin when provided with a LatLng destination,
	 * meters travelled and original heading. Headings are expressed in degrees
	 * clockwise from North. This function returns null when no solution is
	 * available.
	 *
	 * @param $to LatLng       The destination LatLng.
	 * @param $distance int The distance travelled, in meters.
	 * @param $heading int  The heading in degrees clockwise from north.
	 *
	 * @return LatLng|null
	 */
	public static function computeOffsetOrigin(LatLng $to, $distance, $heading)
	{
		$heading = deg2rad($heading);
		$distance /= MathUtil::EARTH_RADIUS;
		// http://lists.maptools.org/pipermail/proj/2008-October/003939.html
		$n1 = cos($distance);
		$n2 = sin($distance) * cos($heading);
		$n3 = sin($distance) * sin($heading);
		$n4 = sin(deg2rad($to->latitude));
		// There are two solutions for b. b = n2 * $n4 +/- sqrt(), one solution results
		// in the latitude outside the [-90, 90] range. We first try one solution and
		// back off to the other if we are outside that range.
		$n12 = $n1 * $n1;
		$discriminant = $n2 * $n2 * $n12 + $n12 * $n12 - $n12 * $n4 * $n4;
		if ($discriminant < 0) {
			// No real solution which would make sense in LatLng-space.
			return null;
		}
		$b = $n2 * $n4 + sqrt($discriminant);
		$b /= $n1 * $n1 + $n2 * $n2;
		$a = ($n4 - $n2 * $b) / $n1;
		$fromLatRadians = atan2($a, $b);
		if ($fromLatRadians < -M_PI / 2 || $fromLatRadians > M_PI / 2) {
			$b = $n2 * $n4 - sqrt($discriminant);
			$b /= $n1 * $n1 + $n2 * $n2;
			$fromLatRadians = atan2($a, $b);
		}
		if ($fromLatRadians < -M_PI / 2 || $fromLatRadians > M_PI / 2) {
			// No solution which would make sense in LatLng-space.
			return null;
		}
		$fromLngRadians = deg2rad($to->longitude) -
			atan2($n3, $n1 * cos($fromLatRadians) - $n2 * sin($fromLatRadians));

		return new LatLng(rad2deg($fromLatRadians), rad2deg($fromLngRadians));
	}

	/**
	 * Returns the LatLng which lies the given fraction of the way between the
	 * origin LatLng and the destination LatLng.
	 *
	 * @param LatLng $from
	 * @param LatLng $to
	 * @param        $fraction
	 *
	 * @return LatLng interpolated LatLng.
	 */
	public static function interpolate(LatLng $from, LatLng $to, $fraction)
	{
		// http://en.wikipedia.org/wiki/Slerp
		$fromLat = deg2rad($from->latitude);
		$fromLng = deg2rad($from->longitude);
		$toLat = deg2rad($to->latitude);
		$toLng = deg2rad($to->longitude);
		$cosFromLat = cos($fromLat);
		$cosToLat = cos($toLat);

		// Computes Spherical interpolation coefficients.
		$angle = self::computeAngleBetween($from, $to);
		$sinAngle = sin($angle);
		if ($sinAngle < 1E-6) {
			return $from;
		}
		$a = sin((1 - $fraction) * $angle) / $sinAngle;
		$b = sin($fraction * $angle) / $sinAngle;

		// Converts from polar $to vector and interpolate.
		$x = $a * $cosFromLat * cos($fromLng) + $b * $cosToLat * cos($toLng);
		$y = $a * $cosFromLat * sin($fromLng) + $b * $cosToLat * sin($toLng);
		$z = $a * sin($fromLat) + $b * sin($toLat);

		// Converts interpolated vector back $to polar.
		$lat = atan2($z, sqrt($x * $x + $y * $y));
		$lng = atan2($y, $x);

		return new LatLng(rad2deg($lat), rad2deg($lng));
	}

	/**
	 * Returns distance on the unit sphere; the arguments are in radians.
	 *
	 * @param $lat1
	 * @param $lng1
	 * @param $lat2
	 * @param $lng2
	 *
	 * @return float|int
	 */
	private static function distanceRadians($lat1, $lng1, $lat2, $lng2)
	{
		return MathUtil::arcHav(MathUtil::havDistance($lat1, $lat2, $lng1 - $lng2));
	}

	/**
	 * Returns the angle between two LatLngs, in radians. This is the same as the distance
	 * on the unit sphere.
	 *
	 * @param LatLng $from
	 * @param LatLng $to
	 *
	 * @return float|int
	 */
	static function computeAngleBetween(LatLng $from, LatLng $to)
	{
		return self::distanceRadians(deg2rad($from->latitude), deg2rad($from->longitude),
			deg2rad($to->latitude), deg2rad($to->longitude));
	}

	/**
	 * Returns the distance between two LatLngs, in meters.
	 *
	 * @param LatLng $from
	 * @param LatLng $to
	 *
	 * @return float|int
	 */
	public static function computeDistanceBetween(LatLng $from, LatLng $to)
	{
		return self::computeAngleBetween($from, $to) * MathUtil::EARTH_RADIUS;
	}

	/**
	 * Returns the length of the given $path, in meters, on Earth.
	 *
	 * @param LatLng[] $path
	 *
	 * @return float|int
	 */
	public static function computeLength(array $path)
	{
		if (count($path) < 2) {
			return 0;
		}

		$length = 0;
		$prev = $path[0];
		$prevLat = deg2rad($prev->latitude);
		$prevLng = deg2rad($prev->longitude);
		foreach ($path as $point) {
			$lat = deg2rad($point->latitude);
			$lng = deg2rad($point->longitude);
			$length += self::distanceRadians($prevLat, $prevLng, $lat, $lng);
			$prevLat = $lat;
			$prevLng = $lng;
		}

		return $length * MathUtil::EARTH_RADIUS;
	}

	/**
	 * Returns the area of a closed $path on Earth.
	 *
	 * @param $path LatLng[] A closed path.
	 *
	 * @return int The path's area in square meters.
	 */
	public static function computeArea(array $path)
	{
		return abs(self::computeSignedArea($path));
	}

	/**
	 * Returns the signed area of a closed $path on Earth. The sign of the area may be used $to
	 * determine the orientation of the $path.
	 * "inside" is the surface that does not contain the South Pole.
	 *
	 * @param $path LatLng[] A closed $path.
	 *
	 * @param $radius int|null Earth radius
	 *
	 * @return int loop's area in square meters.
	 */
	static function computeSignedArea(array $path, $radius = MathUtil::EARTH_RADIUS)
	{
		$size = count($path);
		if ($size < 3) {
			return 0;
		}
		$total = 0;
		$prev = $path[$size - 1];
		$prevTanLat = tan((M_PI / 2 - deg2rad($prev->latitude)) / 2);
		$prevLng = deg2rad($prev->longitude);
		// For each edge, accumulate the signed area of the triangle formed by the North Pole
		// and that edge ("polar triangle").
		foreach ($path as $point) {
			$tanLat = tan((M_PI / 2 - deg2rad($point->latitude)) / 2);
			$lng = deg2rad($point->longitude);
			$total += self::polarTriangleArea($tanLat, $lng, $prevTanLat, $prevLng);
			$prevTanLat = $tanLat;
			$prevLng = $lng;
		}

		return $total * ($radius * $radius);
	}

	/**
	 * Returns the signed area of a triangle which has North Pole as a vertex.
	 * Formula derived from "Area of a spherical triangle given two edges and the included angle"
	 * as per "Spherical Trigonometry" by Todhunter, page 71, section 103, point 2.
	 * See http://books.google.com/books?id=3uBHAAAAIAAJ&pg=PA71
	 * The arguments named "tan" are tan((pi/2 - latitude)/2).
	 *
	 * @param $tan1
	 * @param $lng1
	 * @param $tan2
	 * @param $lng2
	 *
	 * @return float|int
	 */
	private static function polarTriangleArea($tan1, $lng1, $tan2, $lng2)
	{
		$deltaLng = $lng1 - $lng2;
		$t = $tan1 * $tan2;

		return 2 * atan2($t * sin($deltaLng), 1 + $t * cos($deltaLng));
	}
}