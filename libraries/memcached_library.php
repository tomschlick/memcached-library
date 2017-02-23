<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Memcached_library
{
    private $config;
    private $m;
    private $client_type;
    private $ci;
    protected $errors = [];

    public function __construct()
    {
        $this->ci = &get_instance();

        // Load the memcached library config
        $this->ci->load->config('memcached');
        $this->config = $this->ci->config->item('memcached');

        // Lets try to load Memcache or Memcached Class
        $this->client_type = class_exists($this->config['config']['engine']) ? $this->config['config']['engine'] : false;

        if ($this->client_type) {
            // Which one should be loaded
            switch ($this->client_type) {
                case 'Memcached':
                    $this->m = new Memcached();
                    break;
                case 'Memcache':
                    $this->m = new Memcache();
                    // Set Automatic Compression Settings
                    if ($this->config['config']['auto_compress_tresh']) {
                        $this->setcompressthreshold($this->config['config']['auto_compress_tresh'], $this->config['config']['auto_compress_savings']);
                    }
                    break;
            }
            log_message('debug', 'Memcached Library: '.$this->client_type.' Class Loaded');

            $this->auto_connect();
        } else {
            log_message('error', 'Memcached Library: Failed to load Memcached or Memcache Class');
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
        foreach ($this->config['servers'] as $key => $server) {
            if (!$this->add_server($server)) {
                $this->errors[] = "Memcached Library: Could not connect to the server named $key";
                log_message('error', 'Memcached Library: Could not connect to the server named "'.$key.'"');
            } else {
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

    public function add($key = null, $value = null, $expiration = null)
    {
        if (is_null($expiration)) {
            $expiration = $this->config['config']['expiration'];
        }
        if (is_array($key)) {
            foreach ($key as $multi) {
                if (!isset($multi['expiration']) || $multi['expiration'] == '') {
                    $multi['expiration'] = $this->config['config']['expiration'];
                }
                $this->add($multi['key'], $multi['value'], $multi['expiration']);
            }
        } else {
            switch ($this->client_type) {
                case 'Memcache':
                    $add_status = $this->m->add($this->key_name($key), $value, $this->config['config']['compression'], $expiration);
                    break;

                default:
                case 'Memcached':
                    $add_status = $this->m->add($this->key_name($key), $value, $expiration);
                    break;
            }

            return $add_status;
        }
    }

    /*
    +-------------------------------------+
        Name: set
        Purpose: similar to the add() method but uses set
        @param return : TRUE or FALSE
    +-------------------------------------+
    */

    public function set($key = null, $value = null, $expiration = null)
    {
        if (is_null($expiration)) {
            $expiration = $this->config['config']['expiration'];
        }
        if (is_array($key)) {
            foreach ($key as $multi) {
                if (!isset($multi['expiration']) || $multi['expiration'] == '') {
                    $multi['expiration'] = $this->config['config']['expiration'];
                }
                $this->set($multi['key'], $multi['value'], $multi['expiration']);
            }
        } else {
            switch ($this->client_type) {
                case 'Memcache':
                    $add_status = $this->m->set($this->key_name($key), $value, $this->config['config']['compression'], $expiration);
                    break;

                default:
                case 'Memcached':
                    $add_status = $this->m->set($this->key_name($key), $value, $expiration);
                    break;
            }

            return $add_status;
        }
    }

    /*
    +-------------------------------------+
        Name: get
        Purpose: gets the data for a single key or an array of keys
        @param return : array of data or multi-dimensional array of data
    +-------------------------------------+
    */

    public function get($key = null)
    {
        if ($this->m) {
            if (is_null($key)) {
                $this->errors[] = 'The key value cannot be NULL';

                return false;
            }

            if (is_array($key)) {
                foreach ($key as $n => $k) {
                    $key[$n] = $this->key_name($k);
                }

                return $this->m->getMulti($key);
            } else {
                return $this->m->get($this->key_name($key));
            }
        }

        return false;
    }

    /*
    +-------------------------------------+
        Name: delete
        Purpose: deletes a single or multiple data elements from the memached servers
        @param return : none
    +-------------------------------------+
    */

    public function delete($key, $expiration = null)
    {
        if (is_null($key)) {
            $this->errors[] = 'The key value cannot be NULL';

            return false;
        }

        if (is_null($expiration)) {
            $expiration = $this->config['config']['delete_expiration'];
        }

        if (is_array($key)) {
            foreach ($key as $multi) {
                $this->delete($multi, $expiration);
            }
        } else {
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

    public function replace($key = null, $value = null, $expiration = null)
    {
        if (is_null($expiration)) {
            $expiration = $this->config['config']['expiration'];
        }
        if (is_array($key)) {
            foreach ($key as $multi) {
                if (!isset($multi['expiration']) || $multi['expiration'] == '') {
                    $multi['expiration'] = $this->config['config']['expiration'];
                }
                $this->replace($multi['key'], $multi['value'], $multi['expiration']);
            }
        } else {
            switch ($this->client_type) {
                case 'Memcache':
                    $replace_status = $this->m->replace($this->key_name($key), $value, $this->config['config']['compression'], $expiration);
                    break;

                default:
                case 'Memcached':
                    $replace_status = $this->m->replace($this->key_name($key), $value, $expiration);
                    break;
            }

            return $replace_status;
        }
    }

    /*
    +-------------------------------------+
        Name: increment
        Purpose: increments a value
        @param return : none
    +-------------------------------------+
    */

    public function increment($key = null, $by = 1)
    {
        return $this->m->increment($this->key_name($key), $by);
    }

    /*
    +-------------------------------------+
        Name: decrement
        Purpose: decrements a value
        @param return : none
    +-------------------------------------+
    */

    public function decrement($key = null, $by = 1)
    {
        return $this->m->decrement($this->key_name($key), $by);
    }

    /*
    +-------------------------------------+
        Name: flush
        Purpose: flushes all items from cache
        @param return : none
    +-------------------------------------+
    */

    public function flush()
    {
        return $this->m->flush();
    }

    /*
    +-------------------------------------+
        Name: getversion
        Purpose: Get Server Vesion Number
        @param Returns a string of server version number or FALSE on failure.
    +-------------------------------------+
    */

    public function getversion()
    {
        return $this->m->getVersion();
    }

    /*
    +-------------------------------------+
        Name: getstats
        Purpose: Get Server Stats
        Possible: "reset, malloc, maps, cachedump, slabs, items, sizes"
        @param returns an associative array with server's statistics. Array keys correspond to stats parameters and values to parameter's values.
    +-------------------------------------+
    */

    public function getstats($type = 'items')
    {
        switch ($this->client_type) {
            case 'Memcache':
                $stats = $this->m->getStats($type);
                break;

            default:
            case 'Memcached':
                $stats = $this->m->getStats();
                break;
        }

        return $stats;
    }

    /*
    +-------------------------------------+
        Name: setcompresstreshold
        Purpose: Set When Automatic compression should kick-in
        @param return TRUE/FALSE
    +-------------------------------------+
    */

    public function setcompressthreshold($tresh, $savings = 0.2)
    {
        switch ($this->client_type) {
            case 'Memcache':
                $setcompressthreshold_status = $this->m->setCompressThreshold($tresh, $savings = 0.2);
                break;

            default:
                $setcompressthreshold_status = true;
                break;
        }

        return $setcompressthreshold_status;
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

    /*
    +--------------------------------------+
        Name: isConected
        Purpose: Check if the memcache server is connected.
    +--------------------------------------+
    */

    public function isConnected()
    {
        foreach ($this->getstats() as $key => $server) {
            if ($server['pid'] == -1) {
                return false;
            }

            return true;
        }
    }
}
