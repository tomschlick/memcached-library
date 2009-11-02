<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Memcached_library
{
	
	private var $config;
	private var $m;
	private var $ci;
	protected var $errors = array();
	
	
	public function __construct()
	{
		$this->ci =& get_instance();
		
		$this->ci->load->config('memcached');
		$this->config = $this->ci->config->item('memcached');
		
		$this->m = new Memcached();
		$this->auto_connect();
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
			$expiration = $this->config['expiration'];
		}
		if(is_array($key))
		{
			foreach($key as $multi)
			{
				if(!isset($multi['expiration']) || $multi['expiration'] = '')
				{
					$multi['expiration'] = $this->config['expiration'];
				}
				$this->m->add($multi['key'], $multi['value'], $multi['expiration']);
			}
		}
		else
		{
			return $this->m->add($key, $value, $expiration);
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
		if(is_null($key))
		{
			$this->errors[] = 'The key value cannot be NULL';
			return FALSE;
		}
		
		if(is_array($key))
		{
			return $this->m->getMulti($key);
		}
		else
		{
			return $this->m->get($key);
		}
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
			$expiration = $this->config['delete_expiration'];
		}
		
		if(is_array($key))
		{
			foreach($key as $multi)
			{
				$this->m->delete($multi, $expiration);
			}
		}
		else
		{
			return $this->m->delete($key, $expiration);
		}
	}
	
	public function replace($key = NULL, $value = NULL, $expiration = NULL)
	{
		if(is_null($expiration))
		{
			$expiration = $this->config['expiration'];
		}
		if(is_array($key))
		{
			foreach($key as $multi)
			{
				if(!isset($multi['expiration']) || $multi['expiration'] = '')
				{
					$multi['expiration'] = $this->config['expiration'];
				}
				$this->m->replace($multi['key'], $multi['value'], $multi['expiration']);
			}
		}
		else
		{
			return $this->m->replace($key, $value, $expiration);
		}
	}
	
	
	
	
/* End of file memcached_library.php */
/* Location: ./application/libraries/memcached_library.php */