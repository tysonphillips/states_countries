<?php
/**
 * States/Countries Countries model
 *
 * @package states_countries
 * @copyright Copyright (c) 2015, tysonphillips
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 */
class StatesCountriesCountries extends StatesCountriesModel
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        Language::loadLang("states_countries_countries", null, PLUGINDIR . "states_countries" . DS . "language" . DS);

        $this->table_references = ["taxes", "contacts", "accounts_ach", "accounts_cc"];
    }

    /**
     * Adds a country
     *
     * @param array An array of input data, including:
     *  - alpha2 The 2-character country code
     *  - alpha3 The 3-character country code
     *  - name The country's name
     *  - alt_name The country's alternative (native) name
     * @return mixed An stdClass object representing the new country, or void otherwise
     */
    public function add(array $vars)
    {
        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $fields = ["alpha2", "alpha3", "name", "alt_name"];
            $this->Record->insert("countries", $vars, $fields);
            return $this->get($vars['alpha2']);
        }
    }

    /**
     * Updates a country
     *
     * @param string $aplah2 The current alpha2 country code
     * @param array An array of input data, including:
     *  - alpha2 The 2-character country code
     *  - alpha3 The 3-character country code
     *  - name The country's name
     *  - alt_name The country's alternative (native) name
     * @return mixed An stdClass object representing the updated country, or void otherwise
     */
    public function edit($alpha2, array $vars)
    {
        $vars['current_alpha2'] = $alpha2;
        $this->Input->setRules($this->getRules($vars, true));

        if ($this->Input->validates($vars)) {
            $this->begin();

            $fields = ["alpha2", "alpha3", "name", "alt_name"];
            if (strtolower($alpha2) == strtolower($vars['alpha2'])) {
                $this->Record->duplicate("alpha3", "=", $vars['alpha3'])
                    ->duplicate("name", "=", $vars['name'])
                    ->duplicate("alt_name", "=", $vars['alt_name'])
                    ->insert("countries", $vars, $fields);
            } else {
                $this->Record->where("alpha2", "=", $alpha2)
                    ->update("countries", $vars, $fields);
            }
            $this->updateCountryReferences($alpha2, $vars['alpha2']);

            $this->commit();

            return $this->get($vars['alpha2']);
        }
    }

    /**
     * Deletes the given country
     *
     * @param string $alpha2 The 2-character country code
     */
    public function delete($alpha2)
    {
        $rules = [
            'alpha2' => [
                'delete' => [
                    'rule' => [[$this, "inUse"]],
                    'negate' => true,
                    'message' => $this->_("StatesCountriesCountries.!error.alpha2.delete")
                ]
            ]
        ];

        $vars = ['alpha2' => $alpha2];
        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            $this->begin();

            $this->Record->from("states")
                ->where("country_alpha2", "=", $alpha2)
                ->delete();

            $this->Record->from("countries")
                ->where("alpha2", "=", $alpha2)
                ->delete();

            $this->commit();
        }
    }

    /**
     * Determines whether the given country is being used in the system
     *
     * @param string $alpha2 The 2-character country code
     * @return boolean True if the country is in use in the system, or false otherwise
     */
    public function inUse($alpha2)
    {
        foreach ($this->table_references as $table) {
            $results = $this->Record->select()->from($table)
                ->where("country", "=", $alpha2)
                ->numResults();

            if ($results > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Updates all references of country codes
     *
     * @param string $from_alpha2 The current 2-character country code
     * @param string $to_alpha2 The new 2-character country code
     */
    private function updateCountryReferences($from_alpha2, $to_alpha2)
    {
        foreach ($this->table_references as $table) {
            $this->Record->where("country", "=", $from_alpha2)
                ->update($table, ['country' => $to_alpha2]);
        }

        $this->Record->where("country_alpha2", "=", $from_alpha2)
            ->update("states", ['country_alpha2' => $to_alpha2]);
    }

    /**
     * Retrieves a country by it's alpha2 character code
     *
     * @param string $alpha2 The alpha2 character code
     * @return mixed An stdClass object representing the country, or false otherwise
     */
    public function get($alpha2)
    {
        return $this->getCountries()->where("countries.alpha2", "=", $alpha2)->fetch();
    }

    /**
     * Retrieves a list of all countries in the system
     */
    public function getAll()
    {
        return $this->getCountries()->fetchAll();
    }

    /**
     * Partially builds a query for fetching countries
     *
     * @return Record An instance of the partially constructed query
     */
    private function getCountries()
    {
        $select_fields = ["countries.*"];
        $extra_fields = [
            "COUNT(contacts.id)" => "num_contacts",
            "COUNT(taxes.id)" => "num_taxes",
            "COUNT(accounts_cc.id)" => "num_cc_accounts",
            "COUNT(accounts_ach.id)" => "num_ach_accounts"
        ];

        $this->Record->select($select_fields)
            ->select($extra_fields, false)
            ->from("countries")
            ->leftJoin("contacts", "contacts.country", "=", "countries.alpha2", false)
            ->leftJoin("taxes", "taxes.country", "=", "countries.alpha2", false)
            ->leftJoin("accounts_cc", "accounts_cc.country", "=", "countries.alpha2", false)
            ->leftJoin("accounts_ach", "accounts_ach.country", "=", "countries.alpha2", false)
            ->group(["countries.alpha2", "countries.name", "countries.alpha3", "countries.alt_name"]);

        return $this->Record;
    }

    /**
     * Returns the rules for adding/editing countries
     *
     * @param array $vars An array of country fields, including:
     *  - current_alpha2 The current 2-character country code for edit
     *  - alpha2 The 2-character country code
     *  - alpha3 The 3-character country code
     *  - name The country name
     *  - alt_name The native country name
     * @param boolean $edit True to get the edit rules, or false for the edit rules
     * @return array The rules for adding/editing a country
     */
    private function getRules(array $vars, $edit = false)
    {
        // Fetch the country
        $country = null;
        $check_in_use = false;
        if (isset($vars['alpha2'])) {
            $country = $this->get($vars['alpha2']);

            // Check that the country code is not already taken
            if ($edit && isset($vars['current_alpha2']) && $vars['current_alpha2'] != $vars['alpha2']) {
                $check_in_use = true;
            }
        }

        $rules = [
            'alpha2' => [
                'format' => [
                    'rule' => ["matches", "/^[a-z]{2}$/i"],
                    'message' => $this->_("StatesCountriesCountries.!error.alpha2.format")
                ],
                'in_use' => [
                    'rule' => (bool)$country,
                    'negate' => true,
                    'message' => $this->_(
                        "StatesCountriesCountries.!error.alpha2.in_use",
                        $this->ifSet($country->alpha2),
                        $this->ifSet($country->name)
                    )
                ]
            ],
            'alpha3' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ["matches", "/^[a-z]{3}$/i"],
                    'message' => $this->_("StatesCountriesCountries.!error.alpha3.format")
                ]
            ],
            'name' => [
                'format' => [
                    'rule' => ["isEmpty"],
                    'negate' => true,
                    'message' => $this->_("StatesCountriesCountries.!error.name.format")
                ],
                'length' => [
                    'rule' => ["maxLength", 255],
                    'message' => $this->_("StatesCountriesCountries.!error.name.length")
                ]
            ]
        ];

        if ($edit) {
            if (!$check_in_use) {
                unset($rules['alpha2']['in_use']);
            }
        }
        return $rules;
    }
}
