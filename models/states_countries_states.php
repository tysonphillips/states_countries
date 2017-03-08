<?php
/**
 * States/Countries States model
 *
 * @package states_countries
 * @copyright Copyright (c) 2015, tysonphillips
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 */
class StatesCountriesStates extends StatesCountriesModel
{
    /**;
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        Language::loadLang("states_countries_states", null, PLUGINDIR . "states_countries" . DS . "language" . DS);

        $this->table_references = ["taxes", "contacts", "accounts_ach", "accounts_cc"];
    }

    /**
     * Adds a state
     *
     * @param array An array of input data, including:
     *  - country_alpha2 The 2-character country code to add the state to
     *  - code The 3-character state code
     *  - name The state's name
     * @return mixed An stdClass object representing the new state, or void otherwise
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ["country_alpha2", "code", "name"];
            $this->Record->insert("states", $vars, $fields);
            return $this->get($vars['country_alpha2'], $vars['code']);
        }
    }

    /**
     * Updates a state
     *
     * @param string $code The current state code
     * @param array An array of input data, including:
     *  - country_alpha2 The 2-character country code to add the state to
     *  - code The 3-character state code
     *  - name The state's name
     * @return mixed An stdClass object representing the updated state, or void otherwise
     */
    public function edit($code, array $vars)
    {
        $vars['current_code'] = $code;
        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            $this->begin();

            $fields = ["country_alpha2", "code", "name"];
            if (strtolower($code) == strtolower($vars['code'])) {
                $this->Record->duplicate("name", "=", $vars['name'])
                    ->insert("states", $vars, $fields);
            } else {
                $this->Record->where("code", "=", $code)
                    ->where("country_alpha2", "=", $vars['country_alpha2'])
                    ->update("states", $vars, $fields);
            }
            $this->updateStateReferences($vars['country_alpha2'], $code, $vars['code']);

            $this->commit();

            return $this->get($vars['country_alpha2'], $vars['code']);
        }
    }

    /**
     * Deletes the given state
     *
     * @param string $country_alpha2 The 2-character country code
     * @param string $code The state code
     */
    public function delete($country_alpha2, $code)
    {
        $rules = [
            'country_alpha2' => [
                'delete' => [
                    'rule' => [[$this, "inUse"], $code],
                    'negate' => true,
                    'message' => $this->_("StatesCountriesStates.!error.country_alpha2.delete")
                ]
            ]
        ];

        $vars = ['country_alpha2' => $country_alpha2, 'code' => $code];
        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            $this->Record->from("states")
                ->where("country_alpha2", "=", $country_alpha2)
                ->where("code", "=", $code)
                ->delete();
        }
    }

    /**
     * Determines whether the given state is being used in the system
     *
     * @param string $country_alpha2 The 2-character country code
     * @param string $code The state code
     * @return boolean True if the state is in use in the system, or false otherwise
     */
    public function inUse($country_alpha2, $code)
    {
        foreach ($this->table_references as $table) {
            $results = $this->Record->select()->from($table)
                ->where("country", "=", $country_alpha2)
                ->where("state", "=", $code)
                ->numResults();

            if ($results > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Updates all references of state codes
     *
     * @param string $country_alpha2 The 2-character country code
     * @param string $from_code The current state code
     * @param string $to_code The new state code
     */
    private function updateStateReferences($country_alpha2, $from_code, $to_code)
    {
        foreach ($this->table_references as $table) {
            $this->Record->where("state", "=", $from_code)
                ->where("country", "=", $country_alpha2)
                ->update($table, ['state' => $to_code]);
        }
    }

    /**
     * Retrieves a specific state for a country
     *
     * @param string $country_alpha2 The 2-character country code
     * @param string $code The 3-character state code
     * @return mixed An stdClass object representing the state, or false if none exist
     */
    public function get($country_alpha2, $code)
    {
        return $this->getStates($country_alpha2)
            ->where("states.code", "=", $code)
            ->fetch();
    }

    /**
     * Retriees all states for a given country
     */
    public function getAll($country_alpha2)
    {
        return $this->getStates($country_alpha2)->fetchAll();
    }

    /**
     * Partially builds a query for fetching states
     *
     * @return Record An instance of the partially constructed query
     */
    private function getStates($country_alpha2)
    {
        $select_fields = ["states.*"];
        $extra_fields = [
            "COUNT(contacts.id)" => "num_contacts",
            "COUNT(taxes.id)" => "num_taxes",
            "COUNT(accounts_cc.id)" => "num_cc_accounts",
            "COUNT(accounts_ach.id)" => "num_ach_accounts"
        ];

        $this->Record->select($select_fields)
            ->select($extra_fields, false)
            ->from("states")
                ->on("contacts.country", "=", $country_alpha2)
            ->leftJoin("contacts", "contacts.state", "=", "states.code", false)
                ->on("taxes.country", "=", $country_alpha2)
            ->leftJoin("taxes", "taxes.state", "=", "states.code", false)
                ->on("accounts_cc.country", "=", $country_alpha2)
            ->leftJoin("accounts_cc", "accounts_cc.state", "=", "states.code", false)
                ->on("accounts_ach.country", "=", $country_alpha2)
            ->leftJoin("accounts_ach", "accounts_ach.state", "=", "states.code", false)
            ->where("states.country_alpha2", "=", $country_alpha2)
            ->group(["states.code", "states.country_alpha2", "states.name"]);

        return $this->Record;
    }

    /**
     * Returns the rule set for adding/editing states
     *
     * @param array $vars An array of state info including:
     *  - current_code The current state code for edit
     * 	- code The state code
     * 	- country_alpha2 The 2-character country code ISO 3166-2
     * 	- name The name of the state
     * @param boolean $edit True to get the edit rules, or false for the add rules
     * @return array State rules
     */
    private function getRules(array $vars, $edit = false)
    {
        // Fetch the state
        $state = null;
        $check_in_use = false;
        if (isset($vars['code']) && isset($vars['country_alpha2'])) {
            $state = $this->get($vars['country_alpha2'], $vars['code']);

            // Check that the state code is not already taken
            if ($edit && isset($vars['current_code']) && $vars['current_code'] != $vars['code']) {
                $check_in_use = true;
            }
        }

        $rules = [
            'code' => [
                'format' => [
                    'rule' => ["matches", "/^[0-9a-z]{1,3}$/i"],
                    'message' => $this->_("StatesCountriesStates.!error.code.format")
                ]
            ],
            'country_alpha2' => [
                'format' => [
                    'rule' => ["matches", "/^[a-z]{2}$/i"],
                    'message' => $this->_("StatesCountriesStates.!error.country_alpha2.format")
                ],
                'in_use' => [
                    'rule' => (bool)$state,
                    'negate' => true,
                    'message' => $this->_(
                        "StatesCountriesStates.!error.country_alpha2.in_use",
                        $this->ifSet($state->code),
                        $this->ifSet($state->name)
                    )
                ]
            ],
            'name' => [
                'format' => [
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => $this->_("StatesCountriesStates.!error.name.format")
                ],
                'length' => [
                    'rule' => ["maxLength", 255],
                    'message' => $this->_("StatesCountriesStates.!error.name.length")
                ]
            ]
        ];

        if ($edit) {
            if (!$check_in_use) {
                unset($rules['country_alpha2']['in_use']);
            }
        }
        return $rules;
    }
}
