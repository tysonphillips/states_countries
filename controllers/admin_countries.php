<?php
/**
 * Countries controller
 *
 * @package states_countries
 * @copyright Copyright (c) 2015, tysonphillips
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 */
class AdminCountries extends StatesCountriesController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        $this->requireLogin();

        $this->uses(["StatesCountries.StatesCountriesCountries"]);

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);

        Language::loadLang("admin_countries", null, PLUGINDIR . "states_countries" . DS . "language" . DS);
    }

    /**
     * Index
     */
    public function index()
    {
        $this->redirect($this->base_uri . "plugin/states_countries/admin_main/");
    }

    /**
     * Add a country
     */
    public function add()
    {
        $states_authorized = $this->authorized("states_countries.admin_states", "*");

        // Add the country
        if (!empty($this->post)) {
            $country = $this->StatesCountriesCountries->add($this->post);

            if (($errors = $this->StatesCountriesCountries->errors())) {
                $this->setMessage("error", $errors, false, null, false);
                $vars = (object)$this->post;
            } else {
                $this->flashMessage(
                    "message",
                    Language::_("AdminCountries.!success.added", true, $this->Html->_($country->name, true)),
                    null,
                    false
                );

                $redirect_to = $this->base_uri . "plugin/states_countries/admin_countries/add/";
                if ($states_authorized && isset($this->post['add_states']) && $this->post['add_states'] == "true") {
                    $redirect_to = $this->base_uri . "plugin/states_countries/admin_states/add/" . $country->alpha2;
                }
                $this->redirect($redirect_to);
            }
        }

        $vars = (isset($vars) ? $vars : new stdClass());
        $form = $this->partial("admin_countries_form", compact("vars", "states_authorized"));
        $this->set(compact("vars", "form", "states_authorized"));
    }

    /**
     * Edit a country
     */
    public function edit()
    {
        // Require a country be given
        if (!isset($this->get[0]) || !($country = $this->StatesCountriesCountries->get($this->get[0]))) {
            $this->redirect($this->base_uri . "plugin/states_countries/admin_main/");
        }

        // Update the country
        if (!empty($this->post)) {
            $new_country = $this->StatesCountriesCountries->edit($country->alpha2, $this->post);

            if (($errors = $this->StatesCountriesCountries->errors())) {
                $this->setMessage("error", $errors, false, null, false);
                $vars = (object)$this->post;
            } else {
                $this->flashMessage("message", Language::_("AdminCountries.!success.updated", true), null, false);
                $this->redirect(
                    $this->base_uri . "plugin/states_countries/admin_countries/edit/"
                    . $new_country->alpha2
                );
            }
        }

        $vars = (isset($vars) ? $vars : $country);
        $form = $this->partial("admin_countries_form", compact("country", "vars"));
        $this->set(compact("country", "vars", "form"));
    }

    /**
     * Delete a country
     */
    public function delete()
    {
        // Require a country be given
        if (!isset($this->post['alpha2'])
            || !($country = $this->StatesCountriesCountries->get($this->post['alpha2']))
        ) {
            $this->redirect($this->base_uri . "plugin/states_countries/admin_main/");
        }

        $this->StatesCountriesCountries->delete($country->alpha2);

        if ($this->StatesCountriesCountries->errors()) {
            $this->flashMessage("error", $errors, null, false);
        } else {
            $this->flashMessage(
                "message",
                Language::_("AdminCountries.!success.deleted", true, $this->Html->_($country->name, true)),
                null,
                false
            );
        }

        $this->redirect($this->base_uri . "plugin/states_countries/admin_main/");
    }
}
