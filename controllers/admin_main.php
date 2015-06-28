<?php
/**
 * States/Countries main controller
 * 
 * @package states_countries
 * @copyright Copyright (c) 2015, tysonphillips
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 */
class AdminMain extends StatesCountriesController {
	
	/**
	 * Pre-action
	 */
	public function preAction() {
		parent::preAction();
		
		$this->requireLogin();
		
		$this->uses(array("StatesCountries.StatesCountriesCountries", "StatesCountries.StatesCountriesStates"));
        
        // Restore structure view location of the admin portal
		$this->structure->setDefaultView(APPDIR);
		$this->structure->setView(null, $this->orig_structure_view);
		
		Language::loadLang("admin_main", null, PLUGINDIR . "states_countries" . DS . "language" . DS);
	}
    
    /**
     * State/Country management
     */
    public function index() {
        $countries = $this->StatesCountriesCountries->getAll();
        
        $this->set("countries", $countries);
        $this->set("countries_authorized", $this->authorized("states_countries.admin_countries", "*"));
    }
    
    /**
     * AJAX Fetches states for a given country
     */
    public function getStates() {
        // Require a valid country alpha2 code be given
        if (!$this->isAjax() || !isset($this->get[0])
            || !($country = $this->StatesCountriesCountries->get($this->get[0]))) {
            header($this->server_protocol . " 401 Unauthorized");
			exit();
        }
        
        $states = $this->StatesCountriesStates->getAll($country->alpha2);
        $states_authorized = $this->authorized("states_countries.admin_states", "*");
        
        echo $this->outputAsJson($this->partial("admin_main_getstates", compact("country", "states", "states_authorized")));
        return false;
    }
}
