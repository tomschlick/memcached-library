<?php

class Example_memcached extends Controller
{
    public function Example_memcached()
    {
        parent::Controller();
    }

    public function test()
    {
        // Load library
        $this->load->library('memcached_library');

        // Lets try to get the key
        $results = $this->memcached_library->get('test');

        // If the key does not exist it could mean the key was never set or expired
        if (!$results) {
            // Modify this Query to your liking!
            $query = $this->db->get('members', 7000);

            // Lets store the results
            $this->memcached_library->add('test', $query->result());

            // Output a basic msg
            echo 'Alright! Stored some results from the Query... Refresh Your Browser';
        } else {
            // Output
            var_dump($results);

            // Now let us delete the key for demonstration sake!
            $this->memcached_library->delete('test');
        }
    }

    public function stats()
    {
        $this->load->library('memcached_library');

        echo $this->memcached_library->getversion();
        echo '<br/>';

        // We can use any of the following "reset, malloc, maps, cachedump, slabs, items, sizes"
        $p = $this->memcached_library->getstats('sizes');

        var_dump($p);
    }
}
