<?php
    
    /**
    @cond
    ############################################################################
    # GPL License                                                              #
    #                                                                          #
    # This file is part of the StudIP-Punkteverwaltung.                        #
    # Copyright (c) 2013, Philipp Kraus, <philipp.kraus@tu-clausthal.de>       #
    # This program is free software: you can redistribute it and/or modify     #
    # it under the terms of the GNU General Public License as                  #
    # published by the Free Software Foundation, either version 3 of the       #
    # License, or (at your option) any later version.                          #
    #                                                                          #
    # This program is distributed in the hope that it will be useful,          #
    # but WITHOUT ANY WARRANTY; without even the implied warranty of           #
    # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            #
    # GNU General Public License for more details.                             #
    #                                                                          #
    # You should have received a copy of the GNU General Public License        #
    # along with this program. If not, see <http://www.gnu.org/licenses/>.     #
    ############################################################################
    @endcond
    **/



    require_once("bootstrap.php");
    require_once("sys/veranstaltungpermission.class.php");
    require_once("sys/veranstaltung/veranstaltung.class.php");


    //ini_set("display_errors", TRUE);
    //error_reporting(E_ALL);
    //error_reporting(E_ALL ^ E_NOTICE);

    // http://docs.studip.de/develop/Entwickler/HowToFormulars
    // http://docs.studip.de/develop/Entwickler/HowToHTML
    // http://studip.tleilax.de/plugins/generator/
    // http://docs.studip.de/api


    /** Basisklasse f�r das Plugin nach dem Trails-Framework
     * @see http://docs.studip.de/develop/Entwickler/Trails
     * @see http://docs.studip.de/develop/Entwickler/Navigation
     **/
    class punkteverwaltung extends StudIPPlugin implements StandardPlugin
    {

        function __construct()
        {
            parent::__construct();

            // Navigation wird in Abh�ngigkeit der Berechtigungen gesetzt
            if (VeranstaltungPermission::hasDozentRecht())
                $this->setAdminNavigation();
            elseif (VeranstaltungPermission::hasTutorRecht())
                $this->setTutorNavigation();
            elseif (VeranstaltungPermission::hasAutorRecht())
                $this->setAutorNavigation();

        }


        /** Navigation f�r Autoren, sie sehen nur die Navigation, wenn 
         * f�r die Veranstaltung �bungen vorhanden sind
         **/
        private function setAutorNavigation()
        {
            if ( (!Veranstaltung::get()) || (!Navigation::hasItem("/course")) )
                return;

            Navigation::addItem( "/course/punkteverwaltung", new Navigation(_("Punkte"), PluginEngine::GetURL($this, array(), "show")) );
        }


        /** Administratoren (Dozenten) sehen die Verwaltung generell **/
        private function setAdminNavigation()
        {
            if (!Navigation::hasItem("/course"))
                return;

            Navigation::addItem( "/course/punkteverwaltung", new Navigation(_("Punkteverwaltung"), PluginEngine::GetURL($this, array(), "admin")) );
            Navigation::addItem( "/course/punkteverwaltung/editsettings", new AutoNavigation(_("globale Einstellungen"), PluginEngine::GetURL($this, array(), "admin")) );
            Navigation::addItem( "/course/punkteverwaltung/statistik", new AutoNavigation(_("Auswertungen"), PluginEngine::GetURL($this, array(), "admin")) );

            $loVeranstaltung = Veranstaltung::get();
            if ($loVeranstaltung)
                foreach($loVeranstaltung->uebungen() as $ueb)
                    Navigation::addItem( "/course/punkteverwaltung/edituebung", new AutoNavigation($ueb->name(), PluginEngine::GetURL($this, array("id" => $ueb->id()), "uebung")) );

        }


        /** Tutoren sehen nur die einzelnen �bungen **/
        private function setTutorNavigation()
        {
            $loVeranstaltung = Veranstaltung::get();
            if ( (!Navigation::hasItem("/course")) || (!$loVeranstaltung) || (!$loVeranstaltung->uebungen()) )
                return;

            Navigation::addItem( "/course/punkteverwaltung", new Navigation(_("Punkteverwaltung"), PluginEngine::GetURL($this, array(), "uebungen")) );
            foreach($loVeranstaltung->uebungen() as $ueb)
                Navigation::addItem( "/course/punkteverwaltung/edituebung", new AutoNavigation($ueb->name(), PluginEngine::GetURL($this, array("id" => $ueb->id()), "uebung")) );

        }



        function initialize () { }


        function getIconNavigation($course_id, $last_visit) { }


        function getInfoTemplate($course_id) { }


        function perform($unconsumed_path)
        {
            $this->setupAutoload();
            $dispatcher = new Trails_Dispatcher(
                                                $this->getPluginPath(),
                                                rtrim(PluginEngine::getLink($this, array(), null), "/"),
                                                "show"
                                                );
            $dispatcher->plugin = $this;
            $dispatcher->dispatch($unconsumed_path);
        }

        
        private function setupAutoload()
        {
            if (class_exists("StudipAutoloader"))
                StudipAutoloader::addAutoloadPath(__DIR__ . "/models");
            else
                spl_autoload_register(function ($class) { include_once __DIR__ . $class . ".php"; });
        }
    }
