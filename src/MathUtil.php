<?php


namespace Javidalpe\Maps;


class MathUtil
{
	/**
	 * The earth's radius, in meters.
	 * Mean radius as defined by IUGG.
	 */
	const EARTH_RADIUS = 6371009;

	/**
	 * Restrict x to the range [low, high].
	 *
	 * @param $x
	 * @param $low
	 * @param $high
	 *
	 * @return mixed
	 */
	public static function clamp($x, $low, $high)
	{
		return $x < $low ? $low : ($x > $high ? $high : $x);
	}

	/**
	 * Wraps the given value into the inclusive-exclusive interval between min and max.
	 *
	 * @param $n   float The value to wrap.
	 * @param $min float The minimum.
	 * @param $max float The maximum.
	 *
	 * @return int
	 */
	public static function wrap($n, $min, $max)
	{
		return ($n >= $min && $n < $max) ? $n : (self::mod($n - $min, $max - $min) + $min);
	}

	/**
	 * Returns the non-negative remainder of x / m.
	 *
	 * @param $x float The operand.
	 * @param $m float The modulus.
	 *
	 * @return int
	 */
	public static function mod($x, $m)
	{
		return (($x % $m) + $m) % $m;
	}

	/**
	 * Returns mercator Y corresponding to latitude.
	 * See http://en.wikipedia.org/wiki/Mercator_projection .
	 */
	public static function mercator($lat)
	{
		return log(tan($lat * 0.5 + M_PI / 4));
	}

	/**
	 * Returns latitude from mercator Y.
	 *
	 * @param $y
	 *
	 * @return float|int
	 */
	public static function inverseMercator($y)
	{
		return 2 * atan(exp($y)) - M_PI / 2;
	}

	/**
	 * Returns haversine(angle-in-radians).
	 * hav($x) == (1 - cos($x)) / 2 == sin($x / 2)^2.
	 *
	 * @param $x
	 *
	 * @return float|int
	 */
	public static function hav($x)
	{
		$sinHalf = sin($x * 0.5);

		return $sinHalf * $sinHalf;
	}

	/**
	 * Computes inverse haversine. Has good numerical stability around 0.
	 * arcHav($x) == acos(1 - 2 * $x) == 2 * asin(sqrt($x)).
	 * The argument must be in [0, 1], and the result is positive.
	 *
	 * @param $x
	 *
	 * @return float|int
	 */
	public static function arcHav($x)
	{
		return 2 * asin(sqrt($x));
	}

	// Given h==hav($x), returns sin(abs($x)).
	public static function sinFromHav($h)
	{
		return 2 * sqrt($h * (1 - $h));
	}

	// Returns hav(asin($x)).
	public static function havFromSin($x)
	{
		$x2 = $x * $x;

		return $x2 / (1 + sqrt(1 - $x2)) * .5;
	}

	// Returns sin(arcHav($x) + arcHav(y)).
	public static function sinSumFromHav($x, $y)
	{
		$a = sqrt($x * (1 - $x));
		$b = sqrt($y * (1 - $y));

		return 2 * ($a + $b - 2 * ($a * $y + $b * $x));
	}

	/**
	 * Returns hav() of distance from ($lat1, lng1) to ($lat2, lng2) on the unit sphere.
	 *
	 * @param $lat1
	 * @param $lat2
	 * @param $dLng
	 *
	 * @return float|int
	 */
	public static function havDistance($lat1, $lat2, $dLng)
	{
		return self::hav($lat1 - $lat2) + self::hav($dLng) * cos($lat1) * cos($lat2);
	}
}