# Spherical Geometry

This library provides classes and functions for the computation of geometric data on the surface of the Earth. Code ported from the Google Maps API.

### Install

Require this package with composer using the following command:

```bash
composer require javidalpe/spherical-geometry
```

### API

**SphericalUtil::computeHeading**

Returns the heading from one LatLng to another LatLng. Headings are expressed in degrees clockwise from North within the range [-180,180).

**SphericalUtil::computeOffset**

Returns the LatLng resulting from moving a distance from an origin in the specified heading (expressed in degrees clockwise from north).

**SphericalUtil::computeOffsetOrigin**

Returns the location of origin when provided with a LatLng destination, meters travelled and original heading. Headings are expressed in degrees clockwise from North. This function returns null when no solution is available.

**SphericalUtil::interpolate**

Returns the LatLng which lies the given fraction of the way between the origin LatLng and the destination LatLng.

**SphericalUtil::computeAngleBetween**

Returns the angle between two LatLngs, in radians. This is the same as the distance on the unit sphere.

**SphericalUtil::computeDistanceBetween**

Returns the distance between two LatLngs, in meters.

**SphericalUtil::computeLength**

Returns the length of the given $path, in meters, on Earth.

**SphericalUtil::computeArea**

Returns the area of a closed $path on Earth.

**SphericalUtil::computeSignedArea**

Returns the signed area of a closed path on Earth. The sign of the area may be used to determine the orientation of the path. "inside" is the surface that does not contain the South Pole.  

### License

The Spherical Geometry is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)