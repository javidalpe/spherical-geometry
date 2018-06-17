<?php


namespace Javidalpe\Maps;


class LatLng
{
	public $latitude;
	public $longitude;

	/**
	 * LatLng constructor.
	 *
	 * @param $latitude
	 * @param $longitude
	 */
	public function __construct($latitude, $longitude)
	{
		$this->latitude = $latitude;
		$this->longitude = $longitude;
	}

}