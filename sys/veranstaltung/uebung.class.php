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
    require_once("studentuebung.class.php");
    require_once("interface.class.php");


    /** Klasse f�r die �bungsdaten **/
    class Uebung implements VeranstaltungsInterface
    {

        /** Veranstaltungsobjekt auf das sich die �bung bezieht */
        private $moVeranstaltung = null;

        /** �bungsID **/
        private $mcID = null;

        /** maximale Punktanzahl der �bung (f�r schnelles Caching) **/
        private $mnMaxPunkte = 0;



        /** erzeugt eine neue �bung
         * @warn der PK der Tabelle wird, wie es in StudIP �blich ist, application-side erzeugt, hier wird aber ein MD5 Hash verwendet inkl als Prefix die ID der Veranstaltung
         * @param $pxVeranstaltung Veranstaltungsobjekt oder -ID
         * @param $pcName name der �bung
         **/
        static function create( $pxVeranstaltung, $pcName )
        {
            if ((!is_string($pcName)) || (empty($pcName)))
                throw new Exception(_("F�r die Erzeugung der �bung muss ein Name vergeben werden"));

            $lo = Veranstaltung::get( $pxVeranstaltung );
            if ($lo->isClosed())
                throw new Exception(_("Die Veranstaltung wurde geschlossen, es k�nnen keine �nderungen mehr durchgef�hrt werden"));


            $lcID = md5( uniqid($lo->id(), true) );

            $loPrepare = DBManager::get()->prepare( "insert into ppv_uebung (seminar, id, uebungsname, bestandenprozent, maxpunkte) values (:semid, :id, :name, :prozent, :maxpunkte)" );
            $loPrepare->execute( array("semid" => $lo->id(), "id" => $lcID, "name" => $pcName, "prozent" => 50, "maxpunkte" => 1) );

            
            // erzeuge die Default Liste der Studenten aus der Liste der angemeldeten
            $loPrepare = DBManager::get()->prepare("insert into ppv_uebungstudent select :uebung as uebung, user_id as student, :korrektor as korrektor, 0 as erreichtepunkte, 0 as zusatzpunkte, null as bemerkung from seminar_user where status = :status and Seminar_id = :semid" );
            $loPrepare->execute( array("semid" => $lo->id(), "status" => "autor", "uebung" => $lcID, "korrektor" => $GLOBALS["user"]->id) );
            

            $lcClassName = __CLASS__;
            return new $lcClassName( $pxVeranstaltung, $lcID );
        }


        /** l�scht eine �bung mit allen dazugeh�rigen Daten
         * @param $pxVeranstaltung Veranstaltungsobjekt oder -ID oder �bungsobjekt
         * @param $pxID �bungsobjekt oder -ID (oder null, falls im ersten Parameter ein �bungsobjekt �bergeben wurde)
         **/
        static function delete( $pxVeranstaltung, $pxID = null )
        {
            $lcClassName = __CLASS__;
            $loUebung = new $lcClassName( $pxVeranstaltung, $pxID );

            if ($loUebung->veranstaltung()->isClosed())
                throw new Exception(_("Die Veranstaltung wurde geschlossen, es k�nnen keine �nderungen mehr durchgef�hrt werden"));

            foreach( $loUebung->studentenuebung() as $item )
                StudentUebung::delete( $item->uebung(), $item->student() );

            $loPrepare = DBManager::get()->prepare( "delete from ppv_uebung where seminar = :semid and id = :id" );
            $loPrepare->execute( array("semid" => $loUebung->veranstaltung()->id(), "id" => $loUebung->id()) );
        }


        /** Ctor f�r die �bungen
         * @param $pxVeranstaltung VeranstaltungsID oder Veranstaltungsobjekt oder �bungsobjekt um Copy-Ctor abzubilden
         * @param $pxUebung �bungsobjekt oder �bungsID
         **/
        function __construct( $pxVeranstaltungUebung, $pxUebung = null )
        {
            if ($pxVeranstaltungUebung instanceof $this)
            {
                $this->moVeranstaltung = $pxVeranstaltungUebung->moVeranstaltung;
                $this->mcID            = $pxVeranstaltungUebung->mcID;
                $this->mnMaxPunkte     = $pxVeranstaltungUebung->mnMaxPunkte;
            } else {
                $this->moVeranstaltung = Veranstaltung::get( $pxVeranstaltungUebung );

                if (is_string($pxUebung))
                {
                    $loPrepare = DBManager::get()->prepare("select id, maxpunkte from ppv_uebung where seminar = :semid and id = :id", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                    $loPrepare->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $pxUebung) );
                    if ($loPrepare->rowCount() != 1)
                        throw new Exception(_("�bung nicht gefunden"));

                    $result            = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $this->mnMaxPunkte = floatval($result["maxpunkte"]);
                    $this->mcID        = $result["id"];
                }
                elseif ($pxUebung instanceof $this)
                {
                    $this->mcID            = $pxUebung->mcID;
                    $this->mnMaxPunkte     = floatval($pxUebung->mnMaxPunkte);
                }
            }

            if ( (!$this->mcID) || (!$this->moVeranstaltung) )
                throw new Exception(_("�bungsparameter nicht definiert"));
        }


        /** liefert die Veranstaltung der �bung 
         * @return Veranstaltungsobjekt
         **/
        function veranstaltung()
        {
            return $this->moVeranstaltung;
        }


        /** liefert die ID der Veranstaltung
         * @return ID
         **/
        function id()
        {
            return $this->mcID;
        }


        /** liefert den Namen der �bung zur�ck bzw. setzt ihn neu
         * @param $pc neuer Name
         * @return Name
         **/
        function name( $pc = null )
        {
            $lc = null;
            
            if ((!empty($pc)) && (is_string($pc)) )
            {
                if ($this->moVeranstaltung->isClosed())
                    throw new Exception(_("Die Veranstaltung wurde geschlossen, es k�nnen keine �nderungen mehr durchgef�hrt werden"));


                DBManager::get()->prepare( "update ppv_uebung set uebungsname = :name where seminar = :semid and id = :id" )->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $this->mcID, "name" => $pc) );
                $lc = $pc;

            } else {

                $loPrepare = DBManager::get()->prepare("select uebungsname from ppv_uebung where seminar = :semid and id = :id", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $this->mcID) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $lc     = $result["uebungsname"];
                }

            }

            return $lc;
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
                if ($this->moVeranstaltung->isClosed())
                    throw new Exception(_("Die Veranstaltung wurde geschlossen, es k�nnen keine �nderungen mehr durchgef�hrt werden"));
                
                if (($pn < 0) || ($pn > 100))
                    throw new Exception(_("Parameter Prozentzahl f�r das Bestehen liegt nicht im Interval [0,100]"));

                DBManager::get()->prepare( "update ppv_uebung set bestandenprozent = :prozent where seminar = :semid and id = :id" )->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $this->mcID, "prozent" => floatval($pn)) );

                $ln = $pn;

            } else {

                $loPrepare = DBManager::get()->prepare("select bestandenprozent from ppv_uebung where seminar = :semid and id = :id", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $this->mcID) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $ln     = $result["bestandenprozent"];
                }
                
            }
            
            return floatval($ln);
        }


        /** liefert Anzahl an Punkten f�r die �bung
         * @param $pn Wert zum setzen der Punkte
         * @return Punkte
         **/
        function maxPunkte( $pn = null )
        {
            if (is_numeric($pn))
            {
                if ($this->moVeranstaltung->isClosed())
                    throw new Exception(_("Die Veranstaltung wurde geschlossen, es k�nnen keine �nderungen mehr durchgef�hrt werden"));
                
                if ($pn < 0)
                    throw new Exception(_("Parameter f�r die Punkte muss gr��er gleich Null sein"));

                $this->mnMaxPunkte = floatval($pn);
                DBManager::get()->prepare( "update ppv_uebung set maxpunkte = :pt where seminar = :semid and id = :id" )->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $this->mcID, "pt" => $this->mnMaxPunkte) );
            }

            return $this->mnMaxPunkte;
        }


        /** liefert / setzt die Bemerkung
         * @param $pc Bemerkung
         * @return Bemerkungstext
         **/
        function bemerkung( $pc = false )
        {
            $lc = null;

            if ( (!is_bool($pc)) && ((empty($pc)) || (is_string($pc))) )
            {
                if ($this->moVeranstaltung->isClosed())
                    throw new Exception(_("Die Veranstaltung wurde geschlossen, es k�nnen keine �nderungen mehr durchgef�hrt werden"));
                
                DBManager::get()->prepare( "update ppv_uebung set bemerkung = :bem where seminar = :semid and id = :id" )->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $this->mcID, "bem" => (empty($pc) ? null : $pc)) );

                $lc = $pc;
            } else {
                $loPrepare = DBManager::get()->prepare("select bemerkung from ppv_uebung where seminar = :semid and id = :id", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $this->mcID) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $lc     = $result["bemerkung"];
                }

            }
            
            return $lc;
        }


        /** liefert / setzt das Abgabedatum
         * @param $pc Abgabedatum order null
         * @return Datum
         **/
        function abgabeDatum( $pc = false )
        {
            $lc = null;

            if ( (!is_bool($pc)) && ((empty($pc)) || (is_string($pc))) )
            {
                if ($pc)
                {
                    $lxDate = DateTime::createFromFormat("d.m.Y H:i", $pc);
                    if (!$lxDate)
                    {
                        $lxDate = DateTime::createFromFormat("d.m.Y", $pc);
                        if (!$lxDate)
                            throw new Exception(_("Datum entspricht nicht dem geforderten Format"));
                    }

                    $lc = $lxDate->format("Y-m-d H:i:s");
                }

                if ($this->moVeranstaltung->isClosed())
                    throw new Exception(_("Die Veranstaltung wurde geschlossen, es k�nnen keine �nderungen mehr durchgef�hrt werden"));

                DBManager::get()->prepare( "update ppv_uebung set abgabe = :datum where seminar = :semid and id = :id" )->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $this->mcID, "datum" => $lc) );
                
            } else {
                $loPrepare = DBManager::get()->prepare("select abgabe from ppv_uebung where seminar = :semid and id = :id", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("semid" => $this->moVeranstaltung->id(), "id" => $this->mcID) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    if ($result["abgabe"])
                        $lc = DateTime::createFromFormat("Y-m-d H:i:s", $result["abgabe"])->format("d.m.Y H:i");
                }

            }

            return $lc;
        }


        /** liefert eine Liste mit allen Studenten
         * f�r diese �bung zur�ck
         * @param $resultarray liefert nur die Auth-Hashes der Studenten als Array
         * @param $pcAuth liefert nur den Datensatz f�r einen Studenten zur�ck
         * @return Array mit Objekten von Student-�bung
         **/
        function studentenuebung( $resultarray = false, $pcAuth = null )
        {
            $la = array();

            $loPrepare = null;
            if (is_string($pcAuth))
            {
                $loPrepare = DBManager::get()->prepare("select student from ppv_uebungstudent where uebung = :id and student = :student", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("id" => $this->mcID, "student" => $pcAuth) );
            } else {
                $loPrepare = DBManager::get()->prepare("select student from ppv_uebungstudent where uebung = :id", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("id" => $this->mcID) );
            }
            
            foreach( $loPrepare->fetchAll(PDO::FETCH_ASSOC) as $row )
                if ($resultarray)
                    array_push($la, $row["student"]);
                else
                    array_push($la, new StudentUebung( $this, $row["student"] ) );

            return $la;
        }



    }

?>