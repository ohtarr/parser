<?php

/**
 * lib/Parser_cisco.php.
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

require_once "Parser.php";

class Parser_cisco extends Parser
{

	public $input = [
		"run" 			=>	"",
		"version" 		=>	"",
		"inventory"		=>	"",
		"cdp"			=>	"",
		"lldp"			=>	"",
		"interfaces"	=>	"",
		"stp"			=>	"",
		"switchport"	=>	"",
	];
	//public $interfaces = [];
	public $output = [
		'system' 		=>	[],
		'ips'			=>	[],
		'interfaces'	=>	[],
	];
	
}
