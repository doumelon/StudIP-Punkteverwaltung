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



    require_once("uebung.class.php");
    require_once("interface.class.php");


    /** Klasse f�r die Veranstaltungsdaten **/
    class Veranstaltung implements VeranstaltungsInterface
    {
        
        /** ID der Veranstaltung **/
        private $mcID = null;



        /* statische Methode f�r die �berpr�fung, ob �bungsdaten zu einer Veranstaltung existieren
         * @param $px VeranstaltungsID (SeminarID) oder Veranstaltungsobjekt [leer f�r aktuelle ID, sofern vorhanden]
         * @return liefert null (false) bei Nicht-Existenz, andernfalls das Veranstaltungsobject
         **/
        static function get( $pcID = null )
        {
            try
            {
                return new Veranstaltung($px);
            } catch (Exception $e) {}

            return null;
        }


        /** erzeugt einen neuen Eintrag f�r die Veranstaltung
         * @param $pcID VeranstaltungsID (SeminarID) [leer f�r aktuelle ID, sofern vorhanden]
         **/
        static function create( $pcID = null )
        {
            if ( (empty($pcID)) && (isset($GLOBALS["SessionSeminar"])) )
                $pcID = $GLOBALS["SessionSeminar"];

            $loPrepare = DBManager::get()->prepare( "insert into ppv_seminar (id, bestandenprozent, allow_nichtbestanden) values (:semid, :prozent, :nichtbestanden)" );
            $loPrepare->execute( array("semid" => $pcID, "prozent" => 100, "nichtbestanden" => 0) );
        }


        /** l�scht die Veranstaltung mit allen abh�ngigen Daten
         * @param $px Veranstaltungsobjekt / -ID
         * @param $pDummy Dummy Element, um die Interface Methode korrekt zu implementieren
         **/
        static function delete( $px, $pDummy = null )
        {
            $lo = Veranstaltung::get($px);

            foreach ($lo->uebungen() as $uebung)
                Uebung::delete( $uebung );

            $loPrepare = DBManager::get()->prepare( "delete from ppv_seminar where id = :semid" );
            $loPrepare->execute( array("semid" => $lo->id()) );
        }


        

        /** privater Ctor, um das Objekt nur durch den statischen Factory (get) erzeugen zu k�nnen
         * @param $px VeranstaltungsID (SeminarID) oder Veranstaltungsobjekt
         **/
        private function __construct($px)
        {
            if ( (empty($px)) && (isset($GLOBALS["SessionSeminar"])) )
                $px = $GLOBALS["SessionSeminar"];

            if ($px instanceof $this)
                $this->mcID = $px->id();
            elseif (is_string($px))
            {
                $loPrepare = DBManager::get()->prepare("select id from ppv_seminar where id = :semid", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("semid" => $px) );
                if ($loPrepare->rowCount() != 1)
                    throw new Exception("Veranstaltung nicht gefunden");

                $this->mcID       = $px;
            }
            else
                throw new Exception("Veranstaltungparameter inkrorrekt");
        }


        /** liefert die ID der Veranstaltung
         * @return ID
         **/
        function id()
        {
            return $this->mcID;
        }


        /** liefert die Prozentzahl (�ber alle �bungen) ab wann eine Veranstaltung als bestanden gilt
         * @param $pn Wert zum setzen der Prozentzahl
         * @return Prozentwert
         **/
        function bestandenProzent( $pn = null )
        {
            $ln = 0;

            if (is_numeric($pn))
            {
                
                if (($pn < 0) || ($pn > 100))
                    throw new Exception("Parameter Prozentzahl f�r das Bestehen liegt nicht im Interval [0,100]");

                DBManager::get()->prepare( "update ppv_seminar set bestandenprozent = :prozent where id = :semid" )->execute( array("semid" => $this->mcID, "prozent" => floatval($pn)) );

                $ln = $pn;

            } else {

                $loPrepare = DBManager::get()->prepare("select bestandenprozent from ppv_seminar where id = :semid", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("semid" => $this->mcID) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $ln     = $result["bestandenprozent"];
                }

            }
                
            return floatval($ln);
        }


        /** liefert die Anzahl an �bungen, die als nicht-bestanden
         * gewertet werden d�rfen, um die Veranstaltung trotzdem zu bestehen
         * @param $pn Anzahl der �bungen, die als nicht-bestanden akzeptiert werden
         * @return Anzahl
         **/
        function allowNichtBestanden( $pn = null )
        {
            $ln = 0;

            if (is_numeric($pn))
            {

                if ($pn < 0)
                    throw new Exception("Parameter muss gr��er gleich null sein");

                DBManager::get()->prepare( "update ppv_seminar set allow_nichtbestanden = :anzahl where id = :semid" )->execute( array("semid" => $this->mcID, "anzahl" => intval($pn)) );

                $ln = $pn;

            } else {

                $loPrepare = DBManager::get()->prepare("select allow_nichtbestanden from ppv_seminar where id = :semid", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("semid" => $this->mcID) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $ln     = $result["allow_nichtbestanden"];
                }
                
            }
            
            return intval($ln);

        }


        /** liefert / setzt die Bemerkung 
         * @param $pc Bemerkung 
         * @return Bemerkungstext
         **/
        function bemerkung( $pc = false )
        {
            $lc = null;

            if ( (empty($pc)) || (is_string($pc)) )
            {
                DBManager::get()->prepare( "update ppv_seminar set bemerkung = :bem where id = :semid" )->execute( array("semid" => $this->mcID, "bem" => $pc) );

                $lc = $pc;
            } else {
                $loPrepare = DBManager::get()->prepare("select bemerkung from ppv_seminar where id = :semid", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("semid" => $this->mcID) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $lc     = $result["bemerkung"];
                }

            }

            return $lc;
        }


        /** liefert ein Array mit allen �bungsobjekten
         * @return Array mit �bungsobjekten
         **/
        function uebungen()
        {
            $la = array();

            $loPrepare = DBManager::get()->prepare("select id from ppv_uebung where veranstaltung = :semid", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $loPrepare->execute( array("semid" => $this->mcID) );

            foreach( $loPrepare->fetchAll(PDO::FETCH_ASSOC) as $row )
                array_push($la, new Uebung($this, $orw["id"]) );

            return $la;
        }
        
    }
    
    
?>
