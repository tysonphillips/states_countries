<?php
/**
 * States/Countries Countries model
 * 
 * @package states_countries
 * @copyright Copyright (c) 2015, tysonphillips
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 */
class StatesCountriesCountries extends StatesCountriesModel {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Retrieves a country by it's alpha2 character code
     *
     * @param string $alpha2 The alpha2 character code
     * @return mixed An stdClass object representing the country, or false otherwise
     */
    public function get($alpha2) {
        return $this->getCountries()->where("countries.alpha2", "=", $alpha2)->fetch();
    }
    
    /**
     * Retrieves a list of all countries in the system
     */
    public function getAll() {
        return $this->getCountries()->fetchAll();
    }
    
    /**
     * Partially builds a query for fetching countries
     *
     * @return Record An instance of the partially constructed query
     */
    private function getCountries() {
        $select_fields = array("countries.*");
        $extra_fields = array("COUNT(contacts.id)" => "num_contacts");
        
        $this->Record->select($select_fields)
            ->select($extra_fields, false)
            ->from("countries")
            ->leftJoin("contacts", "contacts.country", "=", "countries.alpha2", false)
            ->group(array("countries.alpha2", "countries.name", "countries.alpha3", "countries.alt_name"));
            
        return $this->Record;
    }
}
