<?php

/**
 * lib/Cisco/Parser.php.
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

class Parser extends \ohtarr\Parser
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
		"mac"			=>  "",
	];
	
	public static function parse_cdp_to_raw_neighbors($cdp)
	{
		$entry_reg = "/^-----------------------/";
		$LINES = explode("\n", $cdp); 
		$NEIGHBORCFG = "";
		foreach($LINES as $LINE)
		{
			if(preg_match($entry_reg,$LINE))
			{
				//print "TRUE!\n";
				if($NEIGHBORCFG == "")
				{
					//print "NEIGHBORCFG is BLANK!\n";
					continue;
				}
				//print $NEIGHBORCFG . "\n";
				$NEIGHBORS[] = $NEIGHBORCFG;
				$NEIGHBORCFG = "";
				continue;
			} else {
				//print "FALSE!\n";
				$NEIGHBORCFG .= $LINE;
				$NEIGHBORCFG .= "\n";
			}
		}
		if($NEIGHBORCFG != "")
		{
			$NEIGHBORS[] = $NEIGHBORCFG;
		}
		//return $INTARRAY;
		return $NEIGHBORS;
	}

	public static function parse_cdp_neighbor($cdpdevice)
	{
		$regex = [
			'name'			=>	'/Device\s+ID:\s*(\S+)/',
			'ip'			=>	'/Entry address\(es\):\s+IP\s+address:\s+(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/',
			'model'			=>	'/\s*Platform:\s+(.+),\s+Capabilities:/',
			'localint'		=>	'/Interface:\s*(\S+),\s*Port ID\s*\(outgoing port\):\s*.+/',
			'remoteint'		=>	'/Interface:\s*\S+,\s*Port ID\s*\(outgoing port\):\s*(.+)/',
			'version'		=>	'/Version\s*:\s*\n(.*)advertisement\s+version:/s',
			'nativevlan'	=>	'/Native\s+VLAN:\s*(\d+)/',
			'duplex'		=>	'/Duplex:\s*(\S+)/',
		];

		$tmp = [];
		foreach($regex as $key => $reg)
		{
			if(preg_match($reg,$cdpdevice,$hits))
			{
				$tmp[$key] = $hits[1];
			}
		}
		if(isset($tmp['name']))
		{
			$namearray = explode(".",$tmp['name']);
			$tmp['name'] = strtoupper($namearray[0]);
		
			if(preg_match("/(.*)\(.*\)/",$tmp['name'],$hits2))
			{
				$tmp['name'] = $hits2[1];
			}
		}
		if(isset($tmp['localint']))
		{
			$tmp['localint'] = Utility::name_abbreviate($tmp['localint']);
		}
		if(isset($tmp['remoteint']))
		{
			$tmp['remoteint'] = Utility::name_abbreviate($tmp['remoteint']);
		}
		if(isset($tmp['version']))
		{
			$tmp['version'] = trim($tmp['version']);
		}
		$tmp['cdp'] = 1;
		return $tmp;
	}

	public static function parse_cdp_to_neighbors($cdp)
	{
		$neighbors = self::parse_cdp_to_raw_neighbors($cdp);
		foreach($neighbors as $neighbor)
		{
			$parsed_neighbor = self::parse_cdp_neighbor($neighbor);
			if(isset($parsed_neighbor['name']))
			{
				$return[$parsed_neighbor['name']] = $parsed_neighbor;
			} elseif(isset($parsed_neighbor['ip']))
			{
				$return[$parsed_neighbor['ip']] = $parsed_neighbor;
			} elseif(isset($parsed_neighbor['chassisid']))
			{
				$return[$parsed_neighbor['chassisid']] = $parsed_neighbor;
			}
		}
		return $return;
	}

/* 	public static function parse_cdp_to_neighbors($cdp)
	{
		$cdplines = explode("\n", $cdp); 
		//$cdplines = preg_split('/\r\n|\r|\n/', $cdp);
		//print_r($cdplines);

		$current = [];
		foreach($cdplines as $line)
		{
			if(preg_match('/Device ID:/',$line))
			{
				if(empty($current))
				{
					$current[] = $line;
				} else {
					$neighbors[] = implode("\n",$current);
					$current = [];
					$current[] = $line;
				}
			} else {
				//var_dump($current);
				if(!empty($current))
				{
					$current[] = $line;
				}
			}
		}

		$regex = [
			'name'			=>	'/Device\s+ID:\s*(\S+)/',
			'ip'			=>	'/Entry address\(es\):\s+IP\s+address:\s+(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/',
			'model'			=>	'/\s*Platform:\s+(.+),\s+Capabilities:/',
			'localint'		=>	'/Interface:\s*(\S+),\s*Port ID\s*\(outgoing port\):\s*.+/',
			'remoteint'		=>	'/Interface:\s*\S+,\s*Port ID\s*\(outgoing port\):\s*(.+)/',
			'version'		=>	'/Version\s*:\s*\n(.*)advertisement\s+version:/s',
			'nativevlan'	=>	'/Native\s+VLAN:\s*(\d+)/',
			'duplex'		=>	'/Duplex:\s*(\S+)/',
		];

		$final = [];

		foreach($neighbors as $cdp)
		{
			//print_r($cdp);
			$tmp = [];
			foreach($regex as $key => $reg)
			{
				if(preg_match($reg,$cdp,$hits))
				{
					$tmp[$key] = $hits[1];
				}
			}
			$namearray = explode(".",$tmp['name']);
			$tmp['name'] = strtoupper($namearray[0]);
			if(preg_match("/(.*)\(.*\)/",$tmp['name'],$hits2))
			{
				$tmp['name'] = $hits2[1];
			}
			$tmp['localint'] = Utility::name_abbreviate($tmp['localint']);
			$tmp['remoteint'] = Utility::name_abbreviate($tmp['remoteint']);
			$tmp['version'] = trim($tmp['version']);
			$final[] = $tmp;
		}
		return $final;
	} */
	
	public static function parse_interface_config($INTCFG)
	{
		if(preg_match("/interface (\S+)/", $INTCFG, $HITS1))
		{
			$INTNAME = Utility::name_abbreviate($HITS1[1]);
			//$INTNAME = $HITS1[1];
			$INTARRAY['name']= $INTNAME;
		}
//		$INTLINES = explode("\n",$INTCFG);
//		foreach($INTLINES as $INTLINE)
//		{
			if(preg_match("/^\s*shutdown$/m", $INTCFG, $HITS1))
			{
				$INTARRAY['shutdown'] = 1;
			}
			if(preg_match("/description (.*)/", $INTCFG, $HITS1))
			{
				$INTARRAY['description']['raw'] = $HITS1[1];
				$descarray = json_decode($HITS1[1],true);
				if(is_array($descarray))
				{
					foreach($descarray as $key => $value)
					{
						$INTARRAY['description']['json'][$key] = $value;
					}
				}
			}
			if(preg_match("/[Ee]thernet/",$INTNAME) || preg_match("/[Pp]ort-channel/",$INTNAME))
			{
				if(preg_match("/switchport mode (.*)/", $INTCFG, $HITS1))
				{
					//print "match!\n";
					$INTARRAY['switchport']['mode'] = $HITS1[1];
				}
				if(preg_match("/switchport trunk encapsulation (.*)/", $INTCFG, $HITS1))
				{
					//print "match!\n";
					$INTARRAY['switchport']['encapsulation'] = $HITS1[1];
				}
				if(preg_match("/switchport trunk native vlan (\d+)/", $INTCFG, $HITS1))
				{
					$INTARRAY['switchport']['native_vlan'] = $HITS1[1];
				}
				if(preg_match("/switchport access vlan (\d+)/", $INTCFG, $HITS1))
				{
					$INTARRAY['switchport']['access_vlan'] = $HITS1[1];
				}
				if(preg_match("/switchport voice vlan (\d+)/", $INTCFG, $HITS1))
				{
					$INTARRAY['switchport']['voice_vlan'] = $HITS1[1];
				}
				//print "$INTCFG";
				if(preg_match("/speed (\d+)/", $INTCFG, $HITS1))
				{
					$INTARRAY['speed'] = $HITS1[1];
				}
				if(preg_match("/^\s*duplex (\S+)$/m", $INTCFG, $HITS1))
				{
					print "DUPLEX: " . $HITS1[1];
					$INTARRAY['duplex'] = $HITS1[1];
				}
			}
			if(preg_match("/bandwidth (\d+)/", $INTCFG, $HITS1))
			{
				$INTARRAY['bandwidth'] = $HITS1[1];
			}
			if(preg_match("/vrf forwarding (\S+)/", $INTCFG, $HITS1))
			{
				$INTARRAY['vrf'] = $HITS1[1];
			}
			if(preg_match_all("/ip address (\d+.\d+.\d+.\d+) (\d+.\d+.\d+.\d+)( secondary|)/", $INTCFG, $HITS1,PREG_SET_ORDER))
			{
				foreach($HITS1 as $HIT)
				{
					unset($tmp);
					$tmp['ip'] = $HIT[1];				
					$tmp['mask'] = $HIT[2];
					$tmp['cidr'] = Utility::netmask2cidr($HIT[2]);
					$tmp['network'] = Utility::cidr2network($HIT[1],Utility::netmask2cidr($HIT[2]));
					if($HIT[3])
					{
						$tmp['secondary'] = 1;
					}
					$INTARRAY['ips'][$HIT[1]] = $tmp;
				}
			}
			if(preg_match("/ip address (dhcp|negotiated)/", $INTCFG, $HITS1))
			{
				$INTARRAY['ip'][$HITS1[1]] = 1;
			}
			if(preg_match_all("/ip helper-address (\S+)/", $INTCFG, $HITS1))
			{
				foreach($HITS1[1] as $helper)
				{
					$INTARRAY['helper'][] = $helper;
				}
			}
			if(preg_match("/standby version (\d)/", $INTCFG, $HITS1))
			{
				$INTARRAY['hsrp']['version'] = $HITS1[1];
			}
			if(preg_match_all("/standby (\d+) ip (\S+)/", $INTCFG, $HITS1))
			{
				foreach($HITS1[1] as $key => $group)
				{
					$INTARRAY['hsrp']['group'][$group]['ip'] = $HITS1[2][$key];
				}
			}
			if(preg_match_all("/standby (\d+) priority (\d+)/", $INTCFG, $HITS1))
			{
				foreach($HITS1[1] as $key => $group)
				{
					$INTARRAY['hsrp']['group'][$group]['priority'] = $HITS1[2][$key];
				}
			}
			if(preg_match("/ip mtu (\S+)/", $INTCFG, $HITS1))
			{
				$INTARRAY['ipmtu'] = $HITS1[1];
			}
			if(preg_match("/ip tcp adjust-mss (\S+)/", $INTCFG, $HITS1))
			{
				$INTARRAY['adjustmss'] = $HITS1[1];
			}
			if(preg_match("/service-policy output (\S+)/", $INTCFG, $HITS1))
			{
				$INTARRAY['service-policy'] = $HITS1[1];
			}
	//	}
		return $INTARRAY;
    }
    
    public static function parse_interfaces_to_raw_interfaces($interfaces)
	{
		$LINES = explode("\n", $interfaces); 
		$INT = null;
		$INTCFG = "";
		foreach($LINES as $LINE)
		{
			if ($LINE == "")
			{
				continue;
			}
			$DEPTH  = strlen($LINE) - strlen(ltrim($LINE));

			if($DEPTH == 0)
			{
				if($INT)
				{
					$tmparray[] = $INTCFG;
					//$INTARRAY[$INT] = self::parse_interface_config($INT,$INTCFG);
					$INTCFG = "";
				}
				if (preg_match("/(\S+) is .+,\s+line protocol is/", $LINE, $HITS))
				{
					$INT = strtolower($HITS[1]);
					$INTCFG .= $LINE . "\n";
				} else {
					$INT = null;
				}
				continue;
			}
			if($DEPTH > 0)
			{
				if($INT)
				{
					$INTCFG .= $LINE . "\n";
				}
			}
		}
		//return $INTARRAY;
		return $tmparray;
    }
    
    public static function parse_interfaces_to_interfaces($interfaces)
	{
		$interfaces = self::parse_interfaces_to_raw_interfaces($interfaces);
		foreach($interfaces as $interface)
		{
			$tmp = self::parse_interface($interface);
			$intname = $tmp['name'];
			$array[$intname] = $tmp;
			$array[$intname]['raw_interface']= $interface;
		}
		return $array;
    }
 
    public static function parse_interface($interface)
	{
		if(preg_match("/(\S+) is (.+),\s+line protocol is (\S+)/", $interface, $HITS1))
		{
			$INTNAME = $HITS1[1];
			$INTARRAY['rawname'] = $HITS1[1];
			$INTARRAY['name']= Utility::name_abbreviate($HITS1[1]);
			$INTARRAY['physical'] = $HITS1[2];
			$INTARRAY['lineprotocol'] = $HITS1[3];
			if($HITS1[2] == "administratively down")
			{
				$INTARRAY['shutdown'] = 1;
			} else {
				$INTARRAY['shutdown'] = 0;
			}
		}
		if (preg_match("/Hardware is (.*),/",$interface,$HITS1))
		{
			$INTARRAY['hardware'] = $HITS1[1];
		} elseif (preg_match("/Hardware is (.+)$/m",$interface,$HITS2)){
			$INTARRAY['hardware'] = $HITS2[1];
		}
		if(preg_match("/, address is (\S{4}\.\S{4}\.\S{4})/", $interface, $HITS1))
		{
			$INTARRAY['mac'] = $HITS1[1];
		}
		if(preg_match("/Description: (.*)/", $interface, $HITS1))
		{
			$INTARRAY['description']['raw'] = $HITS1[1];
			$descarray = json_decode($HITS1[1],true);
			if(is_array($descarray))
			{
				foreach($descarray as $key => $value)
				{
					$INTARRAY['description']['json'][$key] = $value;
				}
			}
		}
		if(preg_match("/Internet address is (\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/(\d{1,2})/", $interface, $HITS1))
		{
			$INTARRAY['ips'][$HITS1[1]]['ip'] = $HITS1[1];			
			$INTARRAY['ips'][$HITS1[1]]['mask'] = Utility::cidr2netmask($HITS1[2]);
			$INTARRAY['ips'][$HITS1[1]]['cidr'] = $HITS1[2];
			$INTARRAY['ips'][$HITS1[1]]['network'] = Utility::cidr2network($HITS1[1],$HITS1[2]);
			//if($HITS1[3])
			//{
			//	$INTARRAY['ip'][$HITS1[1]]['secondary'] = 1;
			//}
		}
		if(preg_match("/MTU (\S+) bytes, BW (\d+) Kbit\/sec, DLY (\d+) usec/", $interface, $HITS1))
		{
			$INTARRAY['ipmtu'] = $HITS1[1];
			$INTARRAY['bandwidth'] = $HITS1[2];
			$INTARRAY['dly'] = $HITS1[3];
		}
		if(preg_match("/reliability (\d+)\/255, txload (\d+)\/255, rxload (\d+)\/255/", $interface, $HITS1))
		{
			$INTARRAY['reliability'] = $HITS1[1];
			$INTARRAY['txload'] = $HITS1[2];
			$INTARRAY['rxload'] = $HITS1[3];
		}
		if(preg_match("/Encapsulation (\S+),/", $interface, $HITS1))
		{
			$INTARRAY['encapsulation'] = $HITS1[1];
		}
		if(preg_match("/^\s*(.*), (.*), media type is (.*)/m", $interface, $HITS1))
		{
			$INTARRAY['duplex'] = $HITS1[1];
			$INTARRAY['speed'] = $HITS1[2];
			$INTARRAY['mediatype'] = $HITS1[3];
		}
		if(preg_match("/output flow-control is (\S+), input flow-control is (\S+)/", $interface, $HITS1))
		{
			$INTARRAY['output_flow_control'] = $HITS1[1];
			$INTARRAY['input_flow_control'] = $HITS1[2];
		}
		if(preg_match("/ARP type: (\S+), ARP Timeout (\S+)/", $interface, $HITS1))
		{
			$INTARRAY['arp_type'] = $HITS1[1];
			$INTARRAY['arp_timeout'] = $HITS1[2];
		}
		if(preg_match("/Last clearing of \"show interface\" counters (\S+)/", $interface, $HITS1))
		{
			$INTARRAY['counters_cleared'] = $HITS1[1];
		}
		if(preg_match("/Input queue: (\d+)\/(\d+)\/(\d+)\/(\d+) \(size\/max\/drops\/flushes\); Total output drops: (\d+)/", $interface, $HITS1))
		{
			$INTARRAY['input_queue_size'] = $HITS1[1];
			$INTARRAY['input_queue_max'] = $HITS1[2];
			$INTARRAY['input_queue_drops'] = $HITS1[3];
			$INTARRAY['input_queue_flushes'] = $HITS1[4];
			$INTARRAY['total_output_drops'] = $HITS1[5];
		}
		if(preg_match("/Queueing strategy: (\S+)/", $interface, $HITS1))
		{
			$INTARRAY['queueing_strategy'] = $HITS1[1];
		}
		if(preg_match("/Output queue: (\d+)\/(\d+) \(size\/max\)/", $interface, $HITS1))
		{
			$INTARRAY['output_queue_size'] = $HITS1[1];
			$INTARRAY['output_queue_max'] = $HITS1[2];
		}
		if(preg_match("/5 minute input rate (\d+) bits\/sec, (\d+) packets\/sec/", $interface, $HITS1))
		{
			$INTARRAY['5min_input_rate_bps'] = $HITS1[1];
			$INTARRAY['5min_input_rate_pps'] = $HITS1[2];
		}
		if(preg_match("/5 minute output rate (\d+) bits\/sec, (\d+) packets\/sec/", $interface, $HITS1))
		{
			$INTARRAY['5min_output_rate_bps'] = $HITS1[1];
			$INTARRAY['5min_output_rate_pps'] = $HITS1[2];
		}
		if(preg_match("/(\d+) packets input, (\d+) bytes, (\d+) no buffer/", $interface, $HITS1))
		{
			$INTARRAY['packets_input'] = $HITS1[1];
			$INTARRAY['bytes_input'] = $HITS1[2];
			$INTARRAY['no_buffer'] = $HITS1[2];
		}
		if(preg_match("/Received (\d+) broadcasts \((\d+) IP multicasts\)/", $interface, $HITS1))
		{
			$INTARRAY['broadcasts_received'] = $HITS1[1];
			$INTARRAY['multicasts_received'] = $HITS1[2];
		}
		if(preg_match("/(\d+) runts, (\d+) giants, (\d+) throttles\)/", $interface, $HITS1))
		{
			$INTARRAY['runts_received'] = $HITS1[1];
			$INTARRAY['giants_received'] = $HITS1[2];
			$INTARRAY['throttles_received'] = $HITS1[2];
		}
		if(preg_match("/(\d+) input errors, (\d+) CRC, (\d+) frame, (\d+) overrun, (\d+) ignored/", $interface, $HITS1))
		{
			$INTARRAY['input_errors'] = $HITS1[1];
			$INTARRAY['crc'] = $HITS1[2];
			$INTARRAY['frame'] = $HITS1[3];
			$INTARRAY['overrun'] = $HITS1[4];
			$INTARRAY['ignored'] = $HITS1[5];
		}
		if(preg_match("/(\d+) watchdog, (\d+) multicast, (\d+) pause input/", $interface, $HITS1))
		{
			$INTARRAY['watchdog'] = $HITS1[1];
			$INTARRAY['multicast'] = $HITS1[2];
			$INTARRAY['pause_input'] = $HITS1[3];
		}
		if(preg_match("/(\d+) packets output, (\d+) bytes, (\d+) underruns/", $interface, $HITS1))
		{
			$INTARRAY['packets_output'] = $HITS1[1];
			$INTARRAY['bytes_output'] = $HITS1[2];
			$INTARRAY['underruns'] = $HITS1[3];
		}
		if(preg_match("/(\d+) output errors, (\d+) collisions, (\d+) interface resets/", $interface, $HITS1))
		{
			$INTARRAY['output_errors'] = $HITS1[1];
			$INTARRAY['collisions'] = $HITS1[2];
			$INTARRAY['interface_resets'] = $HITS1[3];
		}
		if(preg_match("/(\d+) unknown protocol drops/", $interface, $HITS1))
		{
			$INTARRAY['unknown_protocol_drops'] = $HITS1[1];
		}
		if(preg_match("/(\d+) babbles, (\d+) late collision, (\d+) deferred/", $interface, $HITS1))
		{
			$INTARRAY['babbles'] = $HITS1[1];
			$INTARRAY['late_collision'] = $HITS1[2];
			$INTARRAY['deferred'] = $HITS1[3];
		}
		if(preg_match("/(\d+) lost carrier, (\d+) no carrier, (\d+) pause output/", $interface, $HITS1))
		{
			$INTARRAY['lost_carrier'] = $HITS1[1];
			$INTARRAY['no_carrier'] = $HITS1[2];
			$INTARRAY['pause_output'] = $HITS1[3];
		}
		if(preg_match("/(\d+) output buffer failures, (\d+) output buffers swapped out/", $interface, $HITS1))
		{
			$INTARRAY['output_buffer_failures'] = $HITS1[1];
			$INTARRAY['output_buffers_swapped_out'] = $HITS1[2];
		}
		if(preg_match("/Tunnel source (\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}) \((\S+)\), destination (\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/", $interface, $HITS1))
		{
			$INTARRAY['tunnel_source_ip'] = $HITS1[1];
			$INTARRAY['tunnel_source_interface'] = $HITS1[2];
			$INTARRAY['tunnel_destination_ip'] = $HITS1[3];
		}
		if(preg_match("/Tunnel protocol\/transport (\S+)/", $interface, $HITS1))
		{
			$INTARRAY['tunnel_transport'] = $HITS1[1];
		}
		if(preg_match("/Tunnel transport MTU (\d+) bytes/", $interface, $HITS1))
		{
			$INTARRAY['tunnel_mtu'] = $HITS1[1];
		}
		if(preg_match("/Tunnel transmit bandwidth (\d+) \(kbps\)/", $interface, $HITS1))
		{
			$INTARRAY['tunnel_tx_bandwidth'] = $HITS1[1];
		}
		if(preg_match("/Tunnel receive bandwidth (\d+) \(kbps\)/", $interface, $HITS1))
		{
			$INTARRAY['tunnel_rx_bandwidth'] = $HITS1[1];
		}
		if(preg_match("/Tunnel protection via (\S+) \(profile \"(\S+)\"\)/", $interface, $HITS1))
		{
			$INTARRAY['tunnel_protection'] = $HITS1[1];
			$INTARRAY['tunnel_profile'] = $HITS1[2];
		}
		if(preg_match("/Encapsulation (.+), Vlan ID  (\d+)\./", $interface, $HITS1))
		{
			$INTARRAY['trunk_encapsulation'] = $HITS1[1];
			$INTARRAY['vlan_id'] = $HITS1[2];
		}

		return $INTARRAY;
	}

    public static function parse_interfaces_to_ips($interfaces)
	{
		$reg1 = "/Internet address is (\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/(\d{1,2})/";

		foreach(explode("\n", $interfaces) as $line)
		{
			if (preg_match($reg1, $line, $HITS1))
			{
				$ips[$HITS1[1]]['ip'] = $HITS1[1];				
				$ips[$HITS1[1]]['network'] = Utility::cidr2network($HITS1[1],$HITS1[2]);
				$ips[$HITS1[1]]['mask'] = Utility::cidr2netmask($HITS1[2]);
				$ips[$HITS1[1]]['cidr'] = $HITS1[2];
			}
		}
		return $ips;
	}

	public static function parse_switchport_to_interfaces($intswitchport)
	{
		$array=[];
		$LINES = explode("\n", $intswitchport); 
		$INT = null;
		$NEWINT = null;
		foreach($LINES as $LINE)
		{
			if ($LINE == "")
			{
				continue;
			}
			if(preg_match("/^Name: (\S+)/", $LINE, $HITS1))
			{
				$NEWINT = $HITS1[1];
				if(!$INT)
				{
					$INT = $NEWINT;
				}
				continue;
			}
			if($INT != $NEWINT)
			{
				$array[Utility::name_abbreviate($INT)] = $TMPARRAY;
				$TMPARRAY = null;
				$INT = $NEWINT;
			} elseif($INT) {
				$TMPARRAY[] = $LINE;
			}
		}
		if(isset($TMPARRAY))
		{
			$array[Utility::name_abbreviate($INT)] = $TMPARRAY;
		}
		//print_r($array);

		foreach ($array as $interface => $ifconfig)
		{
			$TMPARRAY=null;
			foreach($ifconfig as $line)
			{
				if(preg_match("/Administrative Mode: (.+)/", $line, $HITS1))
				{
					$TMPARRAY['mode'] = $HITS1[1];
				}
				if(preg_match("/Operational Mode: (.+)/", $line, $HITS1))
				{
					$TMPARRAY['op_mode'] = $HITS1[1];
				}
				if(preg_match("/Administrative Trunking Encapsulation: (.+)/", $line, $HITS1))
				{
					$TMPARRAY['encapsulation'] = $HITS1[1];
				}
				if(preg_match("/Negotiation of Trunking: (.+)/", $line, $HITS1))
				{
					if($HITS1[1] == "On")
					{
						$TMPARRAY['negotiation'] = 1;
					}
				}
				if(preg_match("/Access Mode VLAN: (\d+)/", $line, $HITS1))
				{
					$TMPARRAY['access_vlan'] = $HITS1[1];
				}
				if(preg_match("/Trunking Native Mode VLAN: (\d+)/", $line, $HITS1))
				{
					$TMPARRAY['native_vlan'] = $HITS1[1];
				}
				if(preg_match("/Voice VLAN: (\d+)/", $line, $HITS1))
				{
					$TMPARRAY['voice_vlan'] = $HITS1[1];
				}
				if(preg_match("/Trunking VLANs Enabled: ALL/", $line, $HITS1))
				{
					$TMPARRAY['all_vlans'] = 1;
				}
			}
			$newarray[$interface]['switchport'] = $TMPARRAY;
		}
		if(isset($newarray))
		{
			return $newarray;
		}
	}

	public static function parse_inventory($inventory)
	{
		$reg = '/NAME:\s*(\S.*\S),\s*DESCR:\s*(.*)\nPID:\s*(\S.*\S)\s*,\s*VID:\s*(\S.*\S)\s*,\s*SN:\s*(\S.*\S)/';
		if (preg_match_all($reg, $inventory, $HITS, PREG_SET_ORDER))
		{
			foreach($HITS as $key => $entity)
			{
				$item = [
					"name"	=>	$HITS[$key][1],
					"descr"	=>	$HITS[$key][2],
					"pid"	=>	$HITS[$key][3],
					"vid"	=>	$HITS[$key][4],
					"sn"	=>	$HITS[$key][5],
				];
				$inv[] = $item;
			}
			return $inv;
		}
	}

	public static function parse_inventory_to_serial($inventory)
	{
		$reg = '/NAME:\s*(\S.*\S),\s*DESCR:\s*(.*)\nPID:\s*(\S.*\S)\s*,\s*VID:\s*(\S.*\S)\s*,\s*SN:\s*(\S.*\S)/';
		if (preg_match_all($reg, $inventory, $HITS, PREG_SET_ORDER))
		{
			return reset($HITS)[5];
		}
	}

	public static function parse_lldp_to_raw_neighbors($lldp)
	{
		$entry_reg = "/^----------------------------------/";
		$LINES = explode("\n", $lldp); 
		$NEIGHBORCFG = "";
		foreach($LINES as $LINE)
		{
			if(preg_match($entry_reg,$LINE))
			{
				//print "TRUE!\n";
				if($NEIGHBORCFG == "")
				{
					//print "NEIGHBORCFG is BLANK!\n";
					continue;
				}
				//print $NEIGHBORCFG . "\n";
				$NEIGHBORS[] = $NEIGHBORCFG;
				$NEIGHBORCFG = "";
				continue;
			} else {
				//print "FALSE!\n";
				$NEIGHBORCFG .= $LINE;
				$NEIGHBORCFG .= "\n";
			}
		}
		if($NEIGHBORCFG != "")
		{
			$NEIGHBORS[] = $NEIGHBORCFG;
		}
		return $NEIGHBORS;
	}

	public static function parse_lldp_neighbor($lldpdevice)
	{
		$tmparray = [];
		$reg = "/System\s+Name:\s+(\S+)/";
		if(preg_match($reg,$lldpdevice,$hits))
		{
			$namearray = explode(".",$hits[1]);
			$devicename = strtoupper($namearray[0]);
			$tmparray['name'] = $devicename;
		}
		$reg = "/Chassis id:\s*(\S+)/";
		if(preg_match($reg,$lldpdevice,$hits))
		{
			$tmparray['chassisid'] = $hits[1];
		}
		$reg = "/Local\s+Intf:\s+(\S+)/";
		if(preg_match($reg,$lldpdevice,$hits))
		{
			//print_r($hits);
			$tmparray['localint'] = Utility::name_abbreviate($hits[1]);
		}
		$reg = "/Port\s+id:\s+(\S+)/";
		if(preg_match($reg,$lldpdevice,$hits))
		{
			//print_r($hits);
			$tmparray['portid'] = Utility::name_abbreviate($hits[1]);
		}
		$reg = "/Port\s+Description:\s+(\S+)/";
		if(preg_match($reg,$lldpdevice,$hits))
		{
			$tmparray['portdesc'] = $hits[1];
		}
		$reg = "/\s+IP:\s+(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/";
		if(preg_match($reg,$lldpdevice,$hits))
		{
			$tmparray['ip'] = $hits[1];
		}
		$reg = "/System Description:\s+\n(.*)\s+Time remaining/s";
		if(preg_match($reg,$lldpdevice,$hits))
		{
			$tmparray['version'] = $hits[1];
		}
		$tmparray['lldp'] = 1;
		return $tmparray;
	}

	public static function parse_lldp_to_neighbors($lldp)
	{
		$return = [];
		$neighbors = self::parse_lldp_to_raw_neighbors($lldp);
		foreach($neighbors as $neighbor)
		{
			$parsed_neighbor = self::parse_lldp_neighbor($neighbor);
			if(isset($parsed_neighbor['name']))
			{
				$return[$parsed_neighbor['name']] = $parsed_neighbor;
			} elseif(isset($parsed_neighbor['ip']))
			{
				$return[$parsed_neighbor['ip']] = $parsed_neighbor;
			} elseif(isset($parsed_neighbor['chassisid']))
			{
				$return[$parsed_neighbor['chassisid']] = $parsed_neighbor;
			}
		}
		return $return;
	}

/* 	public static function parse_lldp_to_neighbors($lldp)
	{
		//$lldpreg = "/Chassis id:.*Management Addresses:\s+IP:\s+\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\s/sU";
		$lldpreg = "/Local Intf:.*?Vlan ID:.*?\n/s";
		if(preg_match_all($lldpreg,$lldp,$hits,PREG_SET_ORDER))
		{
			foreach($hits as $hit)
			{
				$lldpdevice = $hit[0];
			
				$reg = "/System\s+Name:\s+(\S+)/";
				if(preg_match($reg,$lldpdevice,$hits))
				{
					$namearray = explode(".",$hits[1]);
					$devicename = strtoupper($namearray[0]);
					$tmparray['name'] = $devicename;
				}
				$reg = "/Chassis id:\s*(\S+)/";
				if(preg_match($reg,$lldpdevice,$hits))
				{
					$tmparray['chassisid'] = $hits[1];
				}
				$reg = "/Local\s+Intf:\s+(\S+)/";
				if(preg_match($reg,$lldpdevice,$hits))
				{
					//print_r($hits);
					$tmparray['localint'] = Utility::name_abbreviate($hits[1]);
				}
				$reg = "/Port\s+id:\s+(\S+)/";
				if(preg_match($reg,$lldpdevice,$hits))
				{
					//print_r($hits);
					$tmparray['portid'] = Utility::name_abbreviate($hits[1]);
				}
				$reg = "/Port\s+Description:\s+(\S+)/";
				if(preg_match($reg,$lldpdevice,$hits))
				{
					$tmparray['portdesc'] = $hits[1];
				}
				$reg = "/\s+IP:\s+(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/";
				if(preg_match($reg,$lldpdevice,$hits))
				{
					$tmparray['ip'] = $hits[1];
				}
				$reg = "/System Description:\s+\n(.*)\s+Time remaining/s";
				if(preg_match($reg,$lldpdevice,$hits))
				{
					$tmparray['version'] = $hits[1];
				}
				$neighbors[] = $tmparray;
				unset($tmparray);
			}
		}
		if(isset($neighbors))
		{
			return $neighbors;
		}
	} */

	public static function parse_mac_to_macs($shmac)
	{
		$reg = "/\s*(\S*)\s*(\S{1,4}\.\S{1,4}\.\S{1,4})\s*(\S*)\s*(\S*)/";
		preg_match_all($reg,$shmac,$hits,PREG_SET_ORDER);
		foreach($hits as $hit)
		{
			unset($tmp);
			//$tmp['vlan'] = $hit[1];
			$formattedmac = Utility::macToRaw($hit[2]);
			$tmp['mac'] = Utility::macToAll($hit[2]);
			$tmp['type'] = $hit[3];
			$tmp['port'] = Utility::name_abbreviate($hit[4]);
			$macs[$formattedmac] = $tmp;
		}
		return $macs;
	}

	public static function parse_mac_to_interface_macs($shmac)
	{
        $reg = "/\s*(\S*)\s*(\S{1,4}\.\S{1,4}\.\S{1,4})\s*(\S*)\s*(\S*)/";
		preg_match_all($reg,$shmac,$hits,PREG_SET_ORDER);
		foreach($hits as $hit)
		{
			$macs[Utility::name_abbreviate($hit[4])]['macs'][] = Utility::macToRaw($hit[2]);
		}
        return $macs;
	}

	public static function parse_run_to_hostname($run)
	{
		$reg1 = "/^hostname (\S+)/m";

		//find hostname line
		if(preg_match_all($reg1, $run, $HITS))
		{
			//print_r($HITS);
			return $HITS[1][0];
		}
	}

	public static function parse_run_to_domain($run)
	{
		$reg1 = "/^ip domain-name (\S+)/m";
		$reg2 = "/^ip domain name (\S+)/m";
		if(preg_match_all($reg1, $run, $HITS))
		{
			$domain = $HITS[1][0];
		}
		if(preg_match_all($reg2, $run, $HITS))
		{
			$domain = $HITS[1][0];
		}
		return $domain;
	}

	public static function parse_run_to_name_servers($run)
	{
		$reg1 = "/^ip name-server (\S+)/m";
		if(preg_match_all($reg1, $run, $HITS))
		{
			foreach($HITS[1] as $key => $server)
			{
				//print_r($HITS);
				$servers[] = $server;
			}
		}
		return $servers;
	}

	public static function parse_run_to_vrfs($run)
	{
		$vrfs = [];
		$reg = "/vrf definition (\S+)/";
		if(preg_match_all($reg, $run, $HITS, PREG_SET_ORDER))
		{
			foreach($HITS as $vrf)
			{
				$vrfs[] = $vrf[1];
			}
		}
		return $vrfs;
	}

	public static function parse_run_to_aaa($run)
	{
	
	}
	
	public static function parse_run_to_snmp_location($run)
	{
		if(preg_match("/snmp-server location (.*)/", $run, $HITS1))
		{
			$return['raw'] = $HITS1[1];
			$array = json_decode($HITS1[1],true);
			if(is_array($array))
			{
				foreach($array as $key => $value)
				{
					$return['json'][$key] = $value;
				}
			}
		}
		return $return;
	}
	
	public static function parse_run_to_ntp($run)
	{
		$reg = "/ntp server (\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/";
		$reg2 = "/ntp server vrf (\S+) (\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/";
		$reg3 = "/ntp source (\S+)/";
		if(preg_match_all($reg, $run, $HITS, PREG_SET_ORDER))
		{
			//print_r($HITS);
			foreach($HITS as $ntp1)
			{
				$ntp['servers'][] = $ntp1[1];
			}
		} 
		if (preg_match_all($reg2, $run, $HITS2, PREG_SET_ORDER)) {
			//print_r($HITS2);
			foreach($HITS2 as $ntp2)
			{
				//$ntp[] = $ntp2[2];
				$ntp['servers'][$ntp2[2]]['vrf'] = $ntp2[1];
			}
		}
		if (preg_match($reg3, $run, $HITS3)) {
			//print_r($HITS3);
			$ntp['sourceint'] = $HITS3[1];
		}
		
		//print_r($ntp);
		return $ntp;
	}
	
	public static function parse_run_to_tacacs($run)
	{
		$LINES = explode("\n", $run); 
		$INT = null;
		$INTCFG = "";
		foreach($LINES as $LINE)
		{
			if ($LINE == "")
			{
				continue;
			}
			$DEPTH  = strlen($LINE) - strlen(ltrim($LINE));

			if($DEPTH == 0)
			{
				if($INT)
				{
					$tmparray[] = $INTCFG;
					//$INTARRAY[$INT] = self::parse_interface_config($INT,$INTCFG);
					$INTCFG = "";
				}
				if (preg_match("/^aaa group server tacacs\+ (\S+)/", $LINE, $HITS))
				{
					$INT = strtolower($HITS[1]);
					$INTCFG .= $LINE . "\n";
				} else {
					$INT = null;
				}
				continue;
			}
			if($DEPTH > 0)
			{
				if($INT)
				{
					$INTCFG .= $LINE . "\n";
				}
			}
		}
		//return $INTARRAY;
		return $tmparray;
	}

	public static function parse_run_to_policymap($run)
	{

	}

	public static function parse_run_to_ips($run)
	{
		$reg1 = "/ip address (\d+.\d+.\d+.\d+) (\d+.\d+.\d+.\d+)/";
		
		foreach(explode("\n", $run) as $line)
		{
			if (preg_match($reg1, $line, $HITS1))
			{
				$ips[$HITS1[1]]['ip'] = $HITS1[1];				
				$ips[$HITS1[1]]['network'] = Utility::cidr2network($HITS1[1],Utility::netmask2cidr($HITS1[2]));
				$ips[$HITS1[1]]['mask'] = $HITS1[2];
				$ips[$HITS1[1]]['cidr'] = Utility::netmask2cidr($HITS1[2]);
			}
		}
		return $ips;
	}

	public static function parse_run_to_raw_interfaces($run)
	{
		$LINES = explode("\n", $run); 
		$INT = null;
		$INTCFG = "";
		foreach($LINES as $LINE)
		{
			if ($LINE == "")
			{
				continue;
			}
			$DEPTH  = strlen($LINE) - strlen(ltrim($LINE));

			if($DEPTH == 0)
			{
				if($INT)
				{
					$tmparray[] = $INTCFG;
					//$INTARRAY[$INT] = self::parse_interface_config($INT,$INTCFG);
					$INTCFG = "";
				}
				if (preg_match("/^interface (\S+)/", $LINE, $HITS))
				{
					$INT = strtolower($HITS[1]);
					$INTCFG .= $LINE . "\n";
				} else {
					$INT = null;
				}
				continue;
			}
			if($DEPTH > 0)
			{
				if($INT)
				{
					$INTCFG .= $LINE . "\n";
				}
			}
		}
		//return $INTARRAY;
		return $tmparray;
	}
	
	public static function parse_run_to_interfaces($run)
	{
		$interfaces = self::parse_run_to_raw_interfaces($run);
		foreach($interfaces as $interface)
		{
			$tmp = self::parse_interface_config($interface);
			//$intname = self::name_unabbreviate($tmp['name']);
			//$intname = strtolower($tmp['name']);
			$array[$tmp['name']] = $tmp;
			$array[$tmp['name']]['raw_config']= $interface;
		}
		return $array;
	}

	public static function parse_run_to_mgmt_interface($run)
	{
		$regs = [
			'/.*source.* (\S+)/',
			'/ip tacacs source-interface (\S+)/',
			'/ip ftp source-interface (\S+)/',
			'/ip tftp source-interface (\S+)/',
			'/logging source-interface (\S+)/',
			'/ntp source (\S+)/',
			'/snmp-server source-interface informs (\S+)/',
			'/snmp-server trap-source (\S+)/',
			'/ip flow-export source (\S+)/',	
		];
		$SOURCES = [];
		foreach($regs as $reg){
			if (preg_match($reg, $run, $HITS))
			{
				if(!isset($SOURCES[$HITS[1]]))
				{
					$SOURCES[$HITS[1]] = 0;
				}
				$SOURCES[$HITS[1]]++;
			}
		}
		array_multisort($SOURCES,SORT_DESC);
		//print_r($SOURCES);
		$value = reset($SOURCES);
		$key = key($SOURCES);
		$return['interface'] = $key;

		$interfaces = self::parse_run_to_interfaces($run);
		if(isset($interfaces[strtolower($key)]))
		{
			foreach($interfaces[strtolower($key)]['ips'] as $ip => $mask)
			{
				$return['ip'] = $ip;
				break;
			}
		}		
		return $return;
	}

	public static function parse_run_to_boot($run)
	{
		$boots = null;
		$reg1 = "/boot system switch (\S+) (\S+):(\S+\.bin|\S+\.conf)$/m";

		if(preg_match_all($reg1, $run, $HITS1, PREG_SET_ORDER))
		{
			print "MATCH 1! \n";
			foreach($HITS1 as $HIT)
			{
				//print_r($HIT);
				$tmp = [];
				$tmp['switch'] = $HIT[1];
				$tmp['filesystem'] = $HIT[2];
				$tmp['file'] = $HIT[3];
				$boots[] = $tmp;
			}
		}

		$reg2 = "/boot system (\S+):(\S+\.bin|\S+\.conf)$/m";

		if(preg_match_all($reg2, $run, $HITS2, PREG_SET_ORDER))
		{
			print "MATCH 2! \n";
			foreach($HITS2 as $HIT)
			{
				//print_r($HIT);
				$tmp = [];
				$tmp['filesystem'] = $HIT[1];
				$tmp['file'] = $HIT[2];
				$boots[] = $tmp;
			}
		}

		return $boots;
	}

	public static function parse_run_to_usernames($run)
	{
		$reg1 = "/^username (\S+).*/m";
		$reg2 = "/privilege (\d+)/m";
		$reg3 = "/secret (\d+) (\S+)/m";

		//find all usernames lines
		if(preg_match_all($reg1, $run, $HITS))
		{
			//print_r($HITS);
			foreach($HITS[1] as $HKEY => $HIT)
			{
				//find privilege level of each
				if(preg_match_all($reg2, $HITS[0][$HKEY], $HITS2))
				{
					$usernames[$HITS[1][$HKEY]]['privilege'] = $HITS2[1][0];
				}
				if(preg_match_all($reg3, $HITS[0][$HKEY], $HITS3))
				{
					//print_r($HITS3);
					$usernames[$HITS[1][$HKEY]]['encryption'] = $HITS3[1][0];
					$usernames[$HITS[1][$HKEY]]['secret'] = $HITS3[2][0];					
				}
			}
		}
		//print_r($usernames);
		return $usernames;
	}

	public static function parse_run_to_enable_secret($run)
	{
		$reg1 = "/enable secret \d (\S+)/";

		if(preg_match_all($reg1, $run, $HITS))
		{
			//print_r($HITS);
			return $HITS[1][0];
		}
	}

	public static function parse_version_to_uptime($version)
	{
		$reg1 = "/uptime is (.+)/m";
		$reg2 = "/(\d+) year/m";
		$reg3 = "/(\d+) week/m";
		$reg4 = "/(\d+) day/m";
		$reg5 = "/(\d+) hour/m";
		$reg6 = "/(\d+) minute/m";

		if(preg_match_all($reg1, $version, $HITS))
		{
			if(preg_match_all($reg2, $HITS[1][0], $HITS2))
			{
				$uptime['years'] = $HITS2[1][0];
			}
			if(preg_match_all($reg3, $HITS[1][0], $HITS3))
			{
				$uptime['weeks'] = $HITS3[1][0];
			}
			if(preg_match_all($reg4, $HITS[1][0], $HITS4))
			{
				$uptime['days'] = $HITS4[1][0];
			}
			if(preg_match_all($reg5, $HITS[1][0], $HITS5))
			{
				$uptime['hours'] = $HITS5[1][0];
			}
			if(preg_match_all($reg6, $HITS[1][0], $HITS6))
			{
				$uptime['minutes'] = $HITS6[1][0];
			}
		}

		return $uptime;
	}

	public static function parse_version_to_model($version)
	{
			if (preg_match('/.*isco\s+(WS-\S+)\s.*/', $version, $reg))
			{
			$model = $reg[1];

			return $model;
		}
		if (preg_match('/.*isco\s+(OS-\S+)\s.*/', $version, $reg))
		{
			$model = $reg[1];

			return $model;
		}
		if (preg_match('/.*ardware:\s+(\S+),.*/', $version, $reg))
		{
			$model = $reg[1];

			return $model;
		}
		if (preg_match('/.*ardware:\s+(\S+).*/', $version, $reg))
		{
			$model = $reg[1];

			return $model;
		}
		if (preg_match('/^[c,C]isco\s(\S+)\s\(.*/m', $version, $reg))
		{
			$model = $reg[1];

			return $model;
		}
	}
	
	
	public static function parse_version_to_ios($version)
	{
		$os = null;
		$reg1 = "/Cisco (IOS) Software/m";
		$reg2 = "/Cisco (IOS XE) Software/m";
		if (preg_match($reg1, $version, $HITS1))
		{
			$os['type'] = $HITS1[1];
		}
		if (preg_match($reg2, $version, $HITS2))
		{
			$os['type'] = $HITS2[1];
		}
		$reg3 = '/System image file is "\S+:\/{0,1}(\S+)"/m';
		if (preg_match($reg3, $version, $HITS3))
		{
			$os['version'] = $HITS3[1];
		}
		$reg4 = '/Compiled \S+ (\S+)/';
		if (preg_match($reg4, $version, $HITS4))
		{
			$os['date'] = $HITS4[1];
		}		
		return $os;
	}
	
	public static function parse_version_to_license($version)
	{
		$license = null;
		$reg1 = "/License Level: (\S+)/";
		if (preg_match($reg1, $version, $HITS1))
		{
			$license[$HITS1[1]]['current'] = $HITS1[1];
			$reg2 = "/License Type: (\S+)/";
			if (preg_match($reg2, $version, $HITS2))
			{
				$license[$HITS1[1]]['type'] = $HITS2[1];
			}
			$reg3 = "/Next reload license Level: (\S+)/";
			if (preg_match($reg3, $version, $HITS3))
			{
				$license[$HITS1[1]]['reboot'] = $HITS3[1];
			}
		}

		$reg = [
			"ipbase"	=>	"/ipbase\s+(ipbasek9|None)\s+(Permanent|None)\s+(ipbasek9|none)/",
			"security"	=>	"/security\s+(securityk9|None)\s+(Permanent|None)\s+(securityk9|None)/",
			"uc"		=>	"/uc\s+(uck9|None)\s+(Permanent|None)\s+(uck9|None)/",
			"data"		=>	"/data\s+(datak9|None)\s+(Permanent|None)\s+(datak9|None)/",
		];
		foreach($reg as $package => $reg)
		{
			if (preg_match($reg, $version, $HITS))
			{
				if($HITS[1] != "None")
				{
					$license[$package]['current'] = $HITS[1];
					$license[$package]['type'] = $HITS[2];
					$license[$package]['reboot'] = $HITS[3];
				}
			}
		}
		return $license;		
	}

	public static function parse_version_to_confreg($version)
	{
		$reg = "/Configuration register is (\S+)/";
		if (preg_match($reg, $version, $HITS1))
		{
			$confreg = $HITS1[1];
		}
		return $confreg;
	}
	
	public static function parse_version_to_ram($version)
	{
		$reg1 = "/with (\d+\S|\d+\S\/\d+\S) bytes of memory/m";
		if (preg_match($reg1, $version, $HITS1))
		{
			$ram = $HITS1[1];
		}
		return $ram;
	}

	public static function parse_version_to_hostname($version)
	{
		$reg1 = "/(\S+) uptime/";
		if (preg_match($reg1, $version, $HITS1))
		{
			$hostname = $HITS1[1];
		}
		return $hostname;
	}


	public static function parse_version_to_serial($version)
	{
		$reg1 = "/Processor board ID (.*)/";
		if (preg_match($reg1, $version, $HITS1))
		{
			$serial = $HITS1[1];
		}
		return $serial;
	}

	public static function parse_version_to_mac($version)
	{
		$reg1 = "/Base ethernet MAC Address\s+:\s+(\S\S:\S\S:\S\S:\S\S:\S\S:\S\S)/";
		if (preg_match($reg1, $version, $HITS1))
		{
			return Utility::macToAll($HITS1[1]);
		}
	}
	
	public static function parse_version_to_stack_switches($version)
	{
		$tmp = [];
		$LINES = explode("\n", $version);
		//print $LINES[0];
		//print_r($LINES);
		$reg1 = '/Switch (\d+)/';
		$reg2 = '/Base ethernet MAC Address\s+:\s+(\S\S:\S\S:\S\S:\S\S:\S\S:\S\S)/';
		$reg3 = '/Model number\s+:\s+(\S+)/';
		$reg4 = '/System serial number\s+:\s+(\S+)/';

		$switch = 1;
		$tmp[$switch]['switch'] = $switch;
		foreach($LINES as $LINE)
		{
			//print $reg1 ."\n";
			//print $LINE . "\n";
			if(preg_match($reg1,$LINE,$hits1))
			{
				$switch = str_replace('0','',$hits1[1]);
				$tmp[$switch]['switch'] = $switch;
			}
			if(preg_match($reg2,$LINE,$hits2))
			{
				$tmp[$switch]['mac'] = Utility::macToAll($hits2[1]);

			}
			if(preg_match($reg3,$LINE,$hits3))
			{
				$tmp[$switch]['model'] = $hits3[1];
			}
			if(preg_match($reg4,$LINE,$hits4))
			{
				$tmp[$switch]['serial'] = $hits4[1];
			}
		}
		return $tmp;
	}
	//
	public static function parse_version_to_current_switch_details($version)
	{
		$versionlines = explode("\n", $version);
		$uptimereg = "/^\S+ uptime is (.*)$/";
		$flashreg = "/^.* bytes of flash-simulated non-volatile configuration memory/";
		$hardwarerevisionreg = "/^Hardware Board Revision Number/";
		$details = [];
		$save = 0;
		foreach($versionlines as $line)
		{
			if(preg_match($uptimereg,$line,$hits))
			{
				$uptimestring = "Switch Uptime                   : " . $hits[1];
			}
			if(preg_match($hardwarerevisionreg,$line,$hits))
			{
				$save = 0;
				break;
			}
			if($save == 1)
			{
				$details[] = $line;
			}
			if(preg_match($flashreg,$line,$hits))
			{
				$save = 1;
				$details[] = $uptimestring;
			}
		}
		return $details;
	}

	public static function parse_version_to_stack_switches_summary($version)
	{
		$versionlines = explode("\n", $version); 
		$reg = "/^(?:\*\s*|\s*)(\d)\s+(\d+)\s+(\S+)\s+(\S+)\s+(\S+)\s*$/";
		$return = null;
		foreach($versionlines as $line)
		{
			if(preg_match($reg,$line,$hits))
			{
				print_r($hits);
				unset($tmp);
				$tmp['switch'] = $hits[1];
				$tmp['ports'] = $hits[2];
				$tmp['model'] = $hits[3];
				$tmp['version'] = $hits[4];
				$tmp['image'] = $hits[5];
				$return[$hits[1]] = $tmp;
			}			
		}

		$current = [];
		foreach($versionlines as $line)
		{
			if(preg_match('/Switch \d\d/',$line))
			{
				if(empty($current))
				{
					$current[] = $line;
				} else {
					$neighbors[] = implode("\n",$current);
					$current = [];
					$current[] = $line;
				}
			} else {
				//var_dump($current);
				if(!empty($current))
				{
					$current[] = $line;
				}
			}
		}
	}


}
