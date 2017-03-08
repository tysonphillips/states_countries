<?php
/**
 * States controller
 *
 * @package states_countries
 * @copyright Copyright (c) 2015, tysonphillips
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License v3
 */
class AdminStates extends StatesCountriesController
{
    /**
     * Pre-action
     */
    public function preAction()
    {
        parent::preAction();

        $this->requireLogin();

        $this->uses(["StatesCountries.StatesCountriesCountries", "StatesCountries.StatesCountriesStates"]);

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);

        Language::loadLang("admin_states", null, PLUGINDIR . "states_countries" . DS . "language" . DS);
    }

    /**
     * Index
     */
    public function index()
    {
        $this->redirect($this->base_uri . "plugin/states_countries/admin_main/");
    }

    /**
     * Add a state
     */
    public function add()
    {
        // Require a country be given
        if (!isset($this->get[0]) || !($country = $this->StatesCountriesCountries->get($this->get[0]))) {
            $this->redirect($this->base_uri . "plugin/states_countries/admin_main/");
        }

        // Add the state
        if (!empty($this->post)) {
            $data = array_merge($this->post, ['country_alpha2' => $country->alpha2]);
            $state = $this->StatesCountriesStates->add($data);

            if (($errors = $this->StatesCountriesStates->errors())) {
                $this->setMessage("error", $errors, false, null, false);
                $vars = (object)$this->post;
            } else {
                $this->flashMessage(
                    "message",
                    Language::_("AdminStates.!success.added", true, $this->Html->_($state->name, true)),
                    null,
                    false
                );
                $this->redirect($this->base_uri . "plugin/states_countries/admin_states/add/" . $country->alpha2);
            }
        }

        $vars = (isset($vars) ? $vars : new stdClass());
        $form = $this->partial("admin_states_form", compact("country", "vars"));
        $this->set(compact("country", "vars", "form"));
    }

    /**
     * Edit a state
     */
    public function edit()
    {
        // Require a state be given
        if (!isset($this->get[0]) || !isset($this->get[1])
            || !($country = $this->StatesCountriesCountries->get($this->get[0]))
            || !($state = $this->StatesCountriesStates->get($country->alpha2, $this->get[1]))
            ) {
            $this->redirect($this->base_uri . "plugin/states_countries/admin_main/");
        }

        // Update the state
        if (!empty($this->post)) {
            $data = array_merge($this->post, ['country_alpha2' => $country->alpha2]);
            $new_state = $this->StatesCountriesStates->edit($state->code, $data);

            if (($errors = $this->StatesCountriesStates->errors())) {
                $this->setMessage("error", $errors, false, null, false);
                $vars = (object)$this->post;
            } else {
                $this->flashMessage("message", Language::_("AdminStates.!success.updated", true), null, false);
                $this->redirect(
                    $this->base_uri . "plugin/states_countries/admin_states/edit/"
                    . $country->alpha2 . "/" . $new_state->code
                );
            }
        }

        $vars = (isset($vars) ? $vars : $state);
        $form = $this->partial("admin_states_form", compact("country", "state", "vars"));
        $this->set(compact("country", "state", "vars", "form"));
    }

    /**
     * Delete a state
     */
    public function delete()
    {
        // Require a state be given
        if (!isset($this->post['country_alpha2']) || !isset($this->post['code'])
            || !($country = $this->StatesCountriesCountries->get($this->post['country_alpha2']))
            || !($state = $this->StatesCountriesStates->get($country->alpha2, $this->post['code']))
            ) {
            $this->redirect($this->base_uri . "plugin/states_countries/admin_main/");
        }

        $this->StatesCountriesStates->delete($country->alpha2, $state->code);

        if ($this->StatesCountriesStates->errors()) {
            $this->flashMessage("error", $errors, null, false);
        } else {
            $this->flashMessage(
                "message",
                Language::_("AdminStates.!success.deleted", true, $this->Html->_($state->name, true)),
                null,
                false
            );;
        }

        $this->redirect($this->base_uri . "plugin/states_countries/admin_main/");
    }
}
