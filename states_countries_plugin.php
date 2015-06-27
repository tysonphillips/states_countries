<?php
/**
 * States/Countries plugin handler
 * 
 * @package states_countries
 * @copyright Copyright (c) 2015, tysonphillips
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 */
class StatesCountriesPlugin extends Plugin {

	/**
	 * Construct
	 */
	public function __construct() {
		Language::loadLang("states_countries_plugin", null, dirname(__FILE__) . DS . "language" . DS);
		
		// Load components required by this plugin
		Loader::loadComponents($this, array("Input", "Record"));
		
		$this->loadConfig(dirname(__FILE__) . DS . "config.json");
	}

	/**
	 * Performs any necessary bootstraping actions
	 *
	 * @param int $plugin_id The ID of the plugin being installed
	 */
	public function install($plugin_id) {
		Loader::loadModels($this, array("Permissions"));
        
		// Add a new permission to [Tools]
		$group = $this->Permissions->getGroupByAlias("admin_tools");
		$perm = array(
            'plugin_id' => $plugin_id,
            'group_id' => $group->id,
            'name' => Language::_("StatesCountriesPlugin.admin_main.name", true),
            'alias' => "states_countries.admin_main",
            'action' => "*"
        );
		
		// Add permission to view
		$this->Permissions->add($perm);
		$errors = $this->Permissions->errors();
		
		// Manage countries
		if (empty($errors)) {
			$perm = array_merge($perm, array(
				'name' => Language::_("StatesCountriesPlugin.admin_countries.name", true),
				'alias' => "states_countries.admin_countries"
			));
			$this->Permissions->add($perm);
			
			$errors = $this->Permissions->errors();
		}
		
		// Manage states
		if (empty($errors)) {
			$perm = array_merge($perm, array(
				'name' => Language::_("StatesCountriesPlugin.admin_states.name", true),
				'alias' => "states_countries.admin_states"
			));
			$this->Permissions->add($perm);
			
			$errors = $this->Permissions->errors();
		}
		
		if ($errors) {
			$this->Input->setErrors($errors);
			return;
		}
	}
	
	/**
	 * Performs any necessary cleanup actions
	 *
	 * @param int $plugin_id The ID of the plugin being uninstalled
	 * @param boolean $last_instance True if $plugin_id is the last instance across all companies for this plugin, false otherwise
	 */
	public function uninstall($plugin_id, $last_instance) {
		Loader::loadModels($this, array("Permissions"));
		
		$permissions = array(
			$this->Permissions->getByAlias("states_countries.admin_main", $plugin_id),
			$this->Permissions->getByAlias("states_countries.admin_countries", $plugin_id),
			$this->Permissions->getByAlias("states_countries.admin_states", $plugin_id)
		);
		
		foreach ($permissions as $permission) {
			$this->Permissions->delete($permission->id);
		}
	}
	
	/**
	 * Returns all actions to be configured for this widget (invoked after install() or upgrade(), overwrites all existing actions)
	 *
	 * @return array A numerically indexed array containing:
	 * 	- action The action to register for
	 * 	- uri The URI to be invoked for the given action
	 * 	- name The name to represent the action (can be language definition)
	 */
	public function getActions() {
		return array(
			array(
				'action' => "nav_secondary_staff",
				'uri' => "plugin/states_countries/admin_main/",
				'name' => Language::_("StatesCountriesPlugin.admin_main.name", true),
				'options' => array('parent' => "tools/")
			)
		);
	}
}
