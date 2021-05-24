<?php

/**
 * lib/Cisco/IOS/Parser.php.
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

namespace ohtarr\Cisco\IOS;

class Parser extends \ohtarr\Cisco\Parser
{
	public function update()
	{
		$this->output['system'] = $this->render_system();
		$this->output['neighbors'] = $this->render_neighbors();
		$this->output['interfaces'] = $this->render_interfaces();
		$this->output['ips'] = $this->render_ips();
		$this->output['macs'] = $this->render_macs();
		$this->output['dnsnames'] = $this->generate_dns_names();
	}
	
	public function generate_dns_names()
	{
		$dnsnames = [];
		if(!isset($this->output['system']['domain']))
		{
			return $dnsnames;
		}
		foreach($this->output['interfaces'] as $intname => $intcfg)
		{
			if(isset($intcfg['ips']))
			{
				foreach($intcfg['ips'] as $ip => $ipcfg)
				{
					unset($tmparray);
					if(!isset($ipcfg['secondary']))
					{
						$tmparray['name'] = strtolower(Utility::dns_name_converter($intname) . "." . $this->output['system']['hostname'] . "." . $this->output['system']['domain']);
						$tmparray['type'] = "a";
						$tmparray['value'] = $ip;
						$dnsnames[] = $tmparray;
						break;
					}
					//$tmparray = 
				}
			}
		}

		if(isset($this->output['system']['hostname']))
		{
			$tmparray['name'] = strtolower($this->output['system']['hostname']) . "." . $this->output['system']['domain'];
			$tmparray['type'] = "cname";
			$tmparray['value'] = strtolower(Utility::dns_name_converter($this->output['system']['mgmt']['interface']) . "." . $this->output['system']['hostname'] . "." . $this->output['system']['domain']);
			$dnsnames[] = $tmparray;
		}
		return $dnsnames;
	}

/* 	public function addNeighborsToInterfaces($shmac)
	{
		$intmacs = self::parse_mac_to_interface_macs($shmac);

		foreach($intmacs as $int => $macs)
		{
			$this->output['interfaces'][$int]['macs'] = $macs;
		}
	}

	public function merge_neighbors()
	{
		if(isset($this->output['neighbors']['lldp']))
		{
			foreach($this->output['neighbors']['lldp'] as $lldpneighbor)
			{
				$this->output['neighbors']['all'][$lldpneighbor['name']]['name'] = $lldpneighbor['name'];				
				$this->output['neighbors']['all'][$lldpneighbor['name']]['chassisid'] = $lldpneighbor['chassisid'];
				$this->output['neighbors']['all'][$lldpneighbor['name']]['localint'] = $lldpneighbor['localint'];
				$this->output['neighbors']['all'][$lldpneighbor['name']]['remoteint'] = $lldpneighbor['portid'];
				$this->output['neighbors']['all'][$lldpneighbor['name']]['portdesc'] = $lldpneighbor['portdesc'];
				$this->output['neighbors']['all'][$lldpneighbor['name']]['ip'] = $lldpneighbor['ip'];
				$this->output['neighbors']['all'][$lldpneighbor['name']]['version'] = $lldpneighbor['version'];
			}
		}
		if(isset($this->output['neighbors']['cdp']))
		{
			foreach($this->output['neighbors']['cdp'] as $cdpneighbor)
			{
				$this->output['neighbors']['all'][$cdpneighbor['name']]['name'] = $cdpneighbor['name'];				
				$this->output['neighbors']['all'][$cdpneighbor['name']]['model'] = $cdpneighbor['model'];
				$this->output['neighbors']['all'][$cdpneighbor['name']]['localint'] = $cdpneighbor['localint'];
				$this->output['neighbors']['all'][$cdpneighbor['name']]['remoteint'] = $cdpneighbor['remoteint'];
				$this->output['neighbors']['all'][$cdpneighbor['name']]['ip'] = $cdpneighbor['ip'];
				$this->output['neighbors']['all'][$cdpneighbor['name']]['version'] = $cdpneighbor['version'];
				if(isset($cdpneighbor['nativevlan']))
				{
					$this->output['neighbors']['all'][$cdpneighbor['name']]['nativevlan'] = $cdpneighbor['nativevlan'];
				}
				$this->output['neighbors']['all'][$cdpneighbor['name']]['duplex'] = $cdpneighbor['duplex'];
			}
		}
	} */
	
/* 	public function addMacsToInterfaces()
	{
		if(!$this->input['mac'])
		{
			return;
		}
		$intmacs = self::parse_mac_to_interface_macs($this->input['mac']);

		foreach($intmacs as $int => $macs)
		{
			$this->output['interfaces'][$int]['macs'] = $macs;
		}

		foreach($this->output['interfaces'] as $intname => $int)
		{
			$this->output['interfaces'][$intname]['mac_count'] = count($int['macs']);
		}
	} */

	public function render_system()
	{
		$return = [];
		if($this->input['run'])
		{
			$run['hostname'] = self::parse_run_to_hostname($this->input['run']);
			$run['usernames'] = self::parse_run_to_usernames($this->input['run']);
			$run['enable_secret'] = self::parse_run_to_enable_secret($this->input['run']);
			$run['domain'] = self::parse_run_to_domain($this->input['run']);
			$run['nameservers'] = self::parse_run_to_name_servers($this->input['run']);
			$run['mgmt'] = self::parse_run_to_mgmt_interface($this->input['run']);
			$run['vrfs'] = self::parse_run_to_vrfs($this->input['run']);
			$run['ntp'] = self::parse_run_to_ntp($this->input['run']);
			$run['snmp']['location'] = self::parse_run_to_snmp_location($this->input['run']);
			$return = array_replace_recursive($return,$run);
		}

		if($this->input['version'])
		{
			$version['hostname'] = self::parse_version_to_hostname($this->input['version']);
			$version['uptime'] = self::parse_version_to_uptime($this->input['version']);
			$version['model'] = self::parse_version_to_model($this->input['version']);
			$version['os'] = self::parse_version_to_ios($this->input['version']);
			$version['ram'] = self::parse_version_to_ram($this->input['version']);
			$version['serial'] = self::parse_version_to_serial($this->input['version']);
			$version['mac'] = self::parse_version_to_mac($this->input['version']);
			$version['license'] = self::parse_version_to_license($this->input['version']);
			$version['confreg'] = self::parse_version_to_confreg($this->input['version']);
			$version['stack'] = 	self::parse_version_to_stack_switches($this->input['version']);
			$return = array_replace_recursive($return,$version);
		}

		if($this->input['inventory'])
		{
			$inventory['inventory'] = self::parse_inventory($this->input['inventory']);
			$inventory['serial'] = self::parse_inventory_to_serial($this->input['inventory']);
			$return = array_replace_recursive($return,$inventory);
		}
		return $return;
	}

	public function render_interfaces()
	{
		$return = [];
		if($this->input['run'])
		{
			$run_int = self::parse_run_to_interfaces($this->input['run']);
			$return = array_replace_recursive($return,$run_int);
		}
		if($this->input['interfaces'])
		{
			$interfaces = self::parse_interfaces_to_interfaces($this->input['interfaces']);
			$return = array_replace_recursive($return,$interfaces);
		}
		if($this->input['switchport'])
		{
			$switchports = self::parse_switchport_to_interfaces($this->input['switchport']);
			$return = array_replace_recursive($return,$switchports);
		}
		if($this->input['mac'])
		{
			$macs = self::parse_mac_to_interface_macs($this->input['mac']);
			$return = array_replace_recursive($return,$macs);
		}
		if($this->output['neighbors'])
		{
			foreach($this->output['neighbors'] as $name => $neighbor)
			{
				if(isset($neighbor['localint']))
				{
					$interfaces[$neighbor['localint']]['neighbors'][] = $neighbor; 
				}
			}
			$return = array_replace_recursive($return,$interfaces);	
		}
		return $return;

	}

	public function render_ips()
	{
		$return = [];
		if(isset($this->output['interfaces']))
		{
			//print_r($this->output['interfaces']);
			foreach($this->output['interfaces'] as $intname => $interface)
			{
				//print_r($interface);
				if(isset($interface['ips']))
				{
					foreach($interface['ips'] as $ipaddress => $ipinfo)
					{
						if(isset($ipinfo['ip']))
						{
							$return[$ipaddress] = $ipinfo;
							$return[$ipaddress]['interface'] = $interface['name'];
						}
					}
				}
			}
		}



/* 		if($this->input['run'])
		{
			$ints = self::parse_interfaces_to_interfaces();
			//$ips = self::parse_run_to_ips($this->input['run']);

			//$return = array_replace_recursive($return,$ips);
		}
		if($this->input['interfaces'])
		{
			$intips = self::parse_interfaces_to_ips($this->input['interfaces']);
			//$intips = 
			$return = array_replace_recursive($return,$intips);
		} */
		return $return;
	}

	public function render_neighbors()
	{
		$return = [];
		if($this->input['lldp'])
		{
			$lldp = self::parse_lldp_to_neighbors($this->input['lldp']);
			$return = array_replace_recursive($return,$lldp);
		}
		if($this->input['cdp'])
		{
			$cdp = self::parse_cdp_to_neighbors($this->input['cdp']);
			$return = array_replace_recursive($return,$cdp);
		}
		return $return;
	}

	public function render_macs()
	{
		$return = [];
		if($this->input['mac'])
		{
			$macs = self::parse_mac_to_macs($this->input['mac']);
			$return = array_replace_recursive($return,$macs);
		}
		return $return;
	}

}
