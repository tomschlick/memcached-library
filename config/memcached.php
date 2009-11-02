<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');


// --------------------------------------------------------------------------
// Servers
// --------------------------------------------------------------------------
$memcached['servers'] = array(

'server-1' 		=> array(
							'host'			=> 'localhost',
							'port'			=> '11211',
							'weight'		=> '1',
						),
);

// --------------------------------------------------------------------------
// Configuration
// --------------------------------------------------------------------------
$memached['config'] = array(

	'prefix' 			=> '',			// Prefixes every key value (useful for multi environment setups)
	'compression'		=> TRUE,		// Turns value compression on/off
	'expiration'		=> 3600,		// Default content expiration value (in seconds)
	
);


$config['memcached'] = $memcached;

/* End of file memcached.php */
/* Location: ./system/application/config/memcached.php */