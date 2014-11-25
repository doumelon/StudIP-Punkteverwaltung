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
    // http://docs.studip.de/develop/Entwickler/Migrations
    


    /** Basisklasse f�r das Plugin nach dem Trails-Framework
     * @see http://docs.studip.de/develop/Entwickler/Trails
     * @see http://docs.studip.de/develop/Entwickler/Navigation
     **/
    class punkteverwaltung extends StudIPPlugin implements StandardPlugin
    {

        /** Ctor des Plugins zur Erzeugung der Navigation **/
        function __construct()
        {
            parent::__construct();

            // Navigation wird in Abh�ngigkeit der Berechtigungen und des Kontextes gesetzt,
            // nur wenn Plugin aktiviert ist und es es sich um eine Veranstaltung handelt wird
            // es aktiviert
            if ( ($this->isActivated()) && (Navigation::hasItem("/course")) )
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
            if (!Veranstaltung::get())
                return;

            Navigation::addItem( "/course/punkteverwaltung", new Navigation(_("Punkte"), PluginEngine::GetURL($this, array(), "show")) );
        }


        /** Administratoren (Dozenten) sehen die Verwaltung generell **/
        private function setAdminNavigation()
        {
            Navigation::addItem( "/course/punkteverwaltung", new Navigation(_("Punkteverwaltung"), PluginEngine::GetURL($this, array(), "admin")) );

            $loVeranstaltung = Veranstaltung::get();
            if (!is_object($loVeranstaltung))
                return;

            Navigation::addItem( "/course/punkteverwaltung/editsettings", new AutoNavigation(_("globale Einstellungen"), PluginEngine::GetURL($this, array(), "admin")) );
            Navigation::addItem( "/course/punkteverwaltung/bonuspunkte", new AutoNavigation(_("Bonuspunkte"), PluginEngine::GetURL($this, array(), "bonuspunkte")) );
            Navigation::addItem( "/course/punkteverwaltung/statistik", new AutoNavigation(_("Auswertungen"), PluginEngine::GetURL($this, array(), "auswertung")) );
            Navigation::addItem( "/course/punkteverwaltung/zulassung", new AutoNavigation(_("manuelle Zulassung"), PluginEngine::GetURL($this, array(), "zulassung")) );

            if (!$loVeranstaltung->isClosed())
            {
                Navigation::addItem( "/course/punkteverwaltung/updateteilnehmer", new AutoNavigation(_("Teilnehmer in �bung(en) aktualisieren"), PluginEngine::GetURL($this, array(), "admin/updateteilnehmer")) );
                Navigation::addItem( "/course/punkteverwaltung/createuebung", new AutoNavigation(_("neue �bung erzeugen"), PluginEngine::GetURL($this, array(), "admin/createuebung")) );
            }
                
            $laUebungen = $loVeranstaltung->uebungen();
            $this->addUebungEditList( $laUebungen );
        }


        /** Tutoren sehen nur die einzelnen �bungen **/
        private function setTutorNavigation()
        {
            $loVeranstaltung = Veranstaltung::get();
            if (!is_object($loVeranstaltung))
                return;
        
            Navigation::addItem( "/course/punkteverwaltung", new Navigation(_("Punkteverwaltung"), PluginEngine::GetURL($this, array(), "uebung")) );
        
            $laUebungen = $loVeranstaltung->uebungen();
            $this->addUebungEditList( $laUebungen );
        }
    
    
        /** setzt die Liste der �bungen mit korrekten Aktivierungsflag
         * @note man muss manuell feststellen, ob ein Item selektiert wurde, bei Tutoren existiert kein Item au�er den �bungen,
         * es muss manuell gepr�ft werden, ob ein Item gesetzt wurde und wenn nicht, dann manuell setzen
         * @param paUebungen Array mit �bungsobjekten
         **/
        private function addUebungEditList( $paUebung )
        {
            if ( (!is_array($paUebung)) || (empty($paUebung)) )
                return;
        
        
            $loFirst = null;
            $llSet   = false;
            foreach($paUebung as $loUebung)
            {
                $loNavigation = new AutoNavigation($loUebung->name(), PluginEngine::GetURL($this, array("ueid" => $loUebung->id()), "uebung"));
                Navigation::addItem( "/course/punkteverwaltung/edituebung".$loUebung->id(), $loNavigation );
            
                if (empty($loFirst))
                    $loFirst = $loNavigation;
            
                $llSet = $llSet || Request::quoted("ueid") == $loUebung->id();
                $loNavigation->setActive( Request::quoted("ueid") == $loUebung->id() );
            }
        
            if ((VeranstaltungPermission::hasTutorRecht()) && (!$llSet) && ($loFirst))
                $loFirst->setActive(true);
                
        }
    



        function initialize () { }


        /** @note dritten Parameter durch StudIP 2.5 eingef�gt, aber mit Defaultvalue versehen,
         * damit Abw�rtskompatibilit�t erhalten bleibt
        **/
        function getIconNavigation($course_id, $last_visit, $user_id = null) { }

        /** @note Methode wurde in StudIP 2.5 eingef�gt **/
        function getTabNavigation ($course_id) {}

        /** @note Methode wurde in StudIP 2.5 eingef�gt **/
        function getNotificationObjects ($course_id, $since, $user_id) {}

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
                spl_autoload_register(function ($class) { @include_once __DIR__ . $class . ".php"; });
        }
    }
