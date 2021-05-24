<?php

/**
 * lib/Utility.php.
 *
 *
 *
 * PHP version 5
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category  default
 *
 * @author    Andrew Jones
 * @copyright 2016 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 3.0
 */

namespace ohtarr;

class Utility
{
	public static function netmask2cidr($netmask)
	{
		$bits = 0;
		$netmask = explode(".", $netmask);

		foreach($netmask as $octect)
			$bits += strlen(str_replace("0", "", decbin($octect)));
		return $bits;
	}

	public static function cidr2network($ip, $cidr)
	{
		$network = long2ip((ip2long($ip)) & ((-1 << (32 - (int)$cidr))));
		return $network;
	}

	public static function cidr2netmask($int) {
		return long2ip(-1 << (32 - (int)$int));
	}
	
	public static function macToRaw($mac)
	{
		$mac = str_replace(":", "", $mac);
		$mac = str_replace("-", "", $mac);
		$mac = str_replace(".", "", $mac);
		$mac = trim($mac);
		return strtolower($mac);
	}

	public static function macToNone($mac)
	{
		$mac = str_replace(":", "", $mac);
		$mac = str_replace("-", "", $mac);
		$mac = str_replace(".", "", $mac);
		$mac = trim($mac);
		return strtolower($mac);
	}

	public static function macToHyphen($mac)
	{
		$raw = self::macToRaw($mac);
		$trim = trim($raw);
		$split = str_split($trim, 2);
		$implode =  implode("-", $split);
		return strtolower($implode);
	}

	public static function macToColon($mac)
	{
		$raw = self::macToRaw($mac);
		$trim = trim($raw);
		$split = str_split($trim, 2);
		$implode =  implode(":", $split);
		return strtolower($implode);
	}

	public static function macToPeriod($mac)
	{
		$raw = self::macToRaw($mac);
		$trim = trim($raw);
		$split = str_split($trim, 4);
		$implode =  implode(".", $split);
		return strtolower($implode);
	}

	public static function macToAll($mac)
	{
		return [
			'raw'		=>	$mac,
			'none'		=>	self::macToNone($mac),
			'colon'		=>	self::macToColon($mac),
			'hyphen'	=>	self::macToHyphen($mac),
			'period'	=>	self::macToPeriod($mac),
		];
	}
}