<?php

/**
 * lib/Cisco/Utility.php.
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

namespace ohtarr\Cisco;

class Utility extends \ohtarr\Utility
{

    public static function get_interface_name_conversions()
	{
		return [
			"fa" 	=>	"fastethernet",
			"gi" 	=>	"gigabitethernet",
			"te" 	=>	"tengigabitethernet",
			"lo" 	=>	"loopback",
			"mu" 	=>	"multilink",
			"ge"	=>	"gigabitethernet",
			"fe"	=>	"fastethernet",
			"eth"	=>	"ethernet",
			"fo"	=>	"fortygigabitethernet",
		];

	}

	public static function parse_interface_name($name)
	{
		$conversions = self::get_interface_name_conversions();
		$return['raw'] = $name;
		$return['rawlower'] = strtolower($name);
		$return['rawupper'] = strtoupper($name);
		foreach($conversions as $abbrev => $full)
		{
			$regs = [
				'long'	=>	"/^" . $full . "(.*)/",
				'short'	=>	"/^" . $abbrev . "(.*)/",
			];
			foreach($regs as $key => $reg)
			{
				//print "key: {$key} Reg: {$reg}\n";
				if(preg_match($reg, $return['rawlower'], $hits))
				{
					//print "Match found using Key: {$key} and Reg: {$reg}! \n";
					foreach($conversions as $short => $long)
					{
						//print "short: {$short} long: {$long}\n";
						if($long == $full)
						{
							$return['short'] = $short . $hits[1];
							$return['long'] = $long . $hits[1];
							break;
						}
					}
					break;
				}
			}
		}
		return $return;
	}

	public static function name_unabbreviate($name)
	{
		$parsed = self::parse_interface_name($name);
		if(isset($parsed['long']))
		{
			return $parsed['long'];
		}
		return $parsed['rawlower'];
	}
	
	public static function name_abbreviate($name)
	{
		$parsed = self::parse_interface_name($name);
		if(isset($parsed['short']))
		{
			return $parsed['short'];
		}
		return $parsed['rawlower'];
    }
    
    public static function dns_name_converter($name)
	{
		$newname = self::name_abbreviate($name);
		$newname = str_replace("/","-",$newname);
		$newname = str_replace(".","-",$newname);
		return $newname;	
    }
    
}