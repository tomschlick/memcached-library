<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Memcached_library
{
	
	private $config;
	private $local_cache = array();
	private $m;
	private $ci;
	protected $errors = array();
	
	
	public function __construct()
	{
		$this->ci =& get_instance();
		
		$this->m = FALSE;
		if(class_exists('Memcached'))
		{
			$this->ci->load->config('memcached');
			$this->config = $this->ci->config->item('memcached');
			
			$this->m = new Memcached();
			log_message('debug', "Memcached Library: Memcached Class Loaded");
			$this->auto_connect();
		}
	}
	
	/*
	+-------------------------------------+
		Name: auto_connect
		Purpose: runs through all of the servers defined in
		the configuration and attempts to connect to each
		@param return : none
	+-------------------------------------+
	*/
	private function auto_connect()
	{
		foreach($this->config['servers'] as $key=>$server)
		{
			if(!$this->add_server($server))
			{
				$this->errors[] = "Memcached Library: Could not connect to the server named $key";
				log_message('error', 'Memcached Library: Could not connect to the server named "'.$key.'"');
			}
			else
			{
				log_message('debug', 'Memcached Library: Successfully connected to the server named "'.$key.'"');
			}
		}
	}
	
	/*
	+-------------------------------------+
		Name: add_server
		Purpose: 
		@param return : TRUE or FALSE
	+-------------------------------------+
	*/
	public function add_server($server)
	{
		extract($server);
		return $this->m->addServer($host, $port, $weight);
	}
	
	/*
	+-------------------------------------+
		Name: add
		Purpose: add an item to the memcache server(s)
		@param return : TRUE or FALSE
	+-------------------------------------+
	*/
	public function add($key = NULL, $value = NULL, $expiration = NULL)
	{
		if(is_null($expiration))
		{
			$expiration = $this->config['config']['expiration'];
		}
		if(is_array($key))
		{
			foreach($key as $multi)
			{
				if(!isset($multi['expiration']) || $multi['expiration'] == '')
				{
					$multi['expiration'] = $this->config['config']['expiration'];
				}
				$this->add($this->key_name($multi['key']), $multi['value'], $multi['expiration']);
			}
		}
		else
		{
			$this->local_cache[$this->key_name($key)] = $value;
			return $this->m->add($this->key_name($key), $value, $this->config['config']['compression'], $expiration);
		}
	}
	
	/*
	+-------------------------------------+
		Name: get
		Purpose: gets the data for a single key or an array of keys
		@param return : array of data or multi-dimensional array of data
	+-------------------------------------+
	*/
	public function get($key = NULL)
	{
		if($this->m)
		{
			if(isset($this->local_cache[$this->key_name($key)]))
			{
				return $this->local_cache[$this->key_name($key)];
			}
			if(is_null($key))
			{
				$this->errors[] = 'The key value cannot be NULL';
				return FALSE;
			}
			
			if(is_array($key))
			{
				foreach($key as $n=>$k)
				{
					$key[$n] = $this->key_name($k);
				}
				return $this->m->getMulti($key);
			}
			else
			{
				return $this->m->get($this->key_name($key));
			}
		}
		return FALSE;		
	}
	
	
	/*
	+-------------------------------------+
		Name: delete
		Purpose: deletes a single or multiple data elements from the memached servers
		@param return : none
	+-------------------------------------+
	*/
	public function delete($key, $expiration = NULL)
	{
		if(is_null($key))
		{
			$this->errors[] = 'The key value cannot be NULL';
			return FALSE;
		}
		
		if(is_null($expiration))
		{
			$expiration = $this->config['config']['delete_expiration'];
		}
		
		if(is_array($key))
		{
			foreach($key as $multi)
			{
				$this->delete($multi, $expiration);
			}
		}
		else
		{
			unset($this->local_cache[$this->key_name($key)]);
			return $this->m->delete($this->key_name($key), $expiration);
		}
	}
	/*
	+-------------------------------------+
		Name: replace
		Purpose: replaces the value of a key that already exists
		@param return : none
	+-------------------------------------+
	*/
	public function replace($key = NULL, $value = NULL, $expiration = NULL)
	{
		if(is_null($expiration))
		{
			$expiration = $this->config['config']['expiration'];
		}
		if(is_array($key))
		{
			foreach($key as $multi)
			{
				if(!isset($multi['expiration']) || $multi['expiration'] == '')
				{
					$multi['expiration'] = $this->config['config']['expiration'];
				}
				$this->replace($multi['key'], $multi['value'], $multi['expiration']);
			}
		}
		else
		{
			$this->local_cache[$this->key_name($key)] = $value;
			return $this->m->replace($this->key_name($key), $value, $this->config['config']['compression'], $expiration);
		}
	}
	/*
	+-------------------------------------+
		Name: key_name
		Purpose: standardizes the key names for memcache instances
		@param return : md5 key name
	+-------------------------------------+
	*/
	private function key_name($key)
	{
		return md5(strtolower($this->config['config']['prefix'].$key));
	}
	
	
	
}	
/* End of file memcached_library.php */
/* Location: ./application/libraries/memcached_library.php */