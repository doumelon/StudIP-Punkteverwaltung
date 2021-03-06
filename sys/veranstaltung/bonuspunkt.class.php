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



    require_once("veranstaltung.class.php");


    /** Klasse f�r die Bonuspunkte zu einer Veranstaltung **/
    class Bonuspunkt
    {
        /** Veranstaltung **/
        private $moVeranstaltung = null;

        /** Array mit Punktedaten **/
        private $maPunkte = array();
        


        /** l�scht alle Bonuspunkte zu einer Veranstaltung
         * @param $po Veranstaltung
         **/
        static function delete( $po )
        {
            $lo = Veranstaltung::get( $pxVeranstaltung );
            if ($lo->isClosed())
                throw new Exception(_("Die Veranstaltung wurde geschlossen und somit k�nnen keine �nderungen durchgef�hrt werden"));

            $loPrepare = DBManager::get()->prepare( "delete from ppv_bonuspunkte where seminar = :semid" );
            $loPrepare->execute( array("semid" => $lo->id()) );
        }


        /** Ctor der Bonuspunkte **/
        function __construct( $pxVeranstaltung )
        {
            $this->moVeranstaltung = Veranstaltung::get( $pxVeranstaltung );
            $this->readData();
        }


        /** liest die Datenbanktabelle und setzt die Daten in das Cachearray **/
        private function readData()
        {
            $this->maPunkte = array();

            $loPrepare = DBManager::get()->prepare("select prozent, punkte from ppv_bonuspunkte where seminar = :semid", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $loPrepare->execute( array("semid" => $this->moVeranstaltung->id()) );

            foreach( $loPrepare->fetchAll(PDO::FETCH_ASSOC) as $row )
                $this->maPunkte[ floatval($row["prozent"]) ] = floatval($row["punkte"]);

            asort($this->maPunkte);
        }


        /** liefert die Veranstaltung f�r die die Bonuspunkte erzeugt wurden
         * @return Veranstaltung
         **/
        function veranstaltung()
        {
            return $this->moVeranstaltung;
        }


        /** l�scht einen Punktedatensatz
         * @param $pn Prozentzahl
         **/
        function remove( $pn )
        {
            if ($this->moVeranstaltung->isClosed())
                throw new Exception(_("Die Veranstaltung wurde geschlossen und somit k�nnen keine �nderungen durchgef�hrt werden"));

            if (!is_numeric($pn))
                throw new Exception(_("Der �bergebene Parameter muss numerisch sein"));

            DBManager::get()->prepare("delete from ppv_bonuspunkte where seminar = :semid and prozent = :prozent", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) )->execute( array("semid" => $this->moVeranstaltung->id(), "prozent" => $pn) );
            $this->readData();
        }


        /** setzt die Bonuspunkte f�r einen Range
         * @param Prozentzahl f�r die die Punkte gesetzt werden sollen
         * @param $pnPunkte Punkte die gesetzt werden
         **/
        function set( $pn, $pnPunkte )
        {
            if ($this->moVeranstaltung->isClosed())
                throw new Exception(_("Die Veranstaltung wurde geschlossen und somit k�nnen keine �nderungen durchgef�hrt werden"));

            if ( (!is_numeric($pn)) || (!is_numeric($pnPunkte)) )
                throw new Exception(_("Der �bergebenen Parameter m�ssen numerisch sein"));
            if ( ($pn < 0) || ($pn > 100) )
                throw new Exception(_("Der �bergebenen Prozentwert muss im Intervall [0,100] liegen"));

            
            DBManager::get()->prepare("insert into ppv_bonuspunkte values ( :semid, :prozent, :punkte) on duplicate key update punkte = :punkte", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) )->execute( array("semid" => $this->moVeranstaltung->id(), "prozent" => $pn, "punkte" => $pnPunkte) );
            $this->readData();
        }


        /** liefert zu einem Prozentwert die Punkte
         * @param $pn Prozentwert
         * @return Punkte
         **/
        function get( $pn )
        {
            if (!is_numeric($pn))
                throw new Exception(_("Der �bergebene Parameter muss numerisch sein"));

            $punkte = 0;
            foreach($this->maPunkte as $key => $value)
            {
                if ($key <= $pn)
                    $punkte = $value;
                else
                    break;
            }

            return $punkte;
        }


        /** liefert die Prozentliste zur�ck
         * @return Array mit Punkteverteilung
         **/
        function liste()
        {
            return $this->maPunkte;
        }

    }

?>
