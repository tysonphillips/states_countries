<?php
/**
 * States/Countries States model
 * 
 * @package states_countries
 * @copyright Copyright (c) 2015, tysonphillips
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 */
class StatesCountriesStates extends StatesCountriesModel {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Retriees all states for a given country
     */
    public function getAll($country_alpha2) {
        return $this->getStates($country_alpha2)->fetchAll();
    }
    
    /**
     * Partially builds a query for fetching states
     *
     * @return Record An instance of the partially constructed query
     */
    private function getStates($country_alpha2 = null) {
        $select_fields = array("states.*");
        $extra_fields = array("COUNT(contacts.id)" => "num_contacts");
        
        $this->Record->select($select_fields)
            ->select($extra_fields, false)
            ->from("states")
            ->leftJoin("contacts", "contacts.state", "=", "states.code", false);
        
        // Filter by country
        if ($country_alpha2) {
            $this->Record->where("states.country_alpha2", "=", $country_alpha2);
        }
        
        $this->Record->group(array("states.code", "states.country_alpha2", "states.name"));
        
        return $this->Record;
    }
}
