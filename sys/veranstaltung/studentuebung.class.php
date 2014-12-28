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
    require_once(dirname(__DIR__) . "/student.class.php");


    /** Exception, um zu erkennen, dass ein user nicht Teilnehmer ist **/
    class UserNotSeminarMember extends Exception {}
    

    /** Klasse f�r die �bung-Student-Beziehung
     * @note Die Klasse legt bei �nderungen automatisiert ein Log an
     **/
    class StudentUebung implements VeranstaltungsInterface
    {

        /** �bungsobjekt **/
        private $moUebung      = null;

        /** Studentenobjekt **/
        private $moStudent     = null;

        /** Prepare Statement f�r das Log zu erezugen **/
        private $moLogPrepare  = null;
    
        /** istTeilnehmer Flag **/
        private $mlVeranstaltungsTeilnehmer = false;

    

        /** l�scht den Eintrag von einem Studenten zur �bung mit einem ggf vorhandenen Log
         * @note der Ctor �berpr�ft nicht, ob der Student Teilnehmer der Veranstaltung
         * ist, diese Pr�fung geschieht erst beim Versuch des Eintragens von Daten
         * @param $pxUebung �bung
         * @param $pxAuth Authentifizierungsschl�ssel des Users oder Studentenobjekt
         **/
        static function delete( $pxUebung, $pxAuth )
        {
            $loUebung  = new Uebung( $pxUebung );
            $loStudent = new Student( $pxAuth );

            if ($loUebung->veranstaltung()->isClosed())
                throw new Exception(_("Die Veranstaltung wurde geschlossen, es k�nnen keine �nderungen mehr durchgef�hrt werden"));

            $laSQL = array(
                "delete from ppv_uebungstudentlog where uebung = :uebungid and student = :auth",
                "delete from ppv_uebungstudent where uebung = :uebungid and student = :auth"
            );
            
            foreach( $laSQL as $lcSQL )
            {
                $loPrepare = DBManager::get()->prepare( $lcSQL );
                $loPrepare->execute( array("uebungid" => $loUebung->id(), "auth" => $loStudent->id()) );
            }
        }



        /** Ctor f�r den Zugriff auf auf die Studenten-�bungsbeziehung
         * @param $pxUebung �bung
         * @param $pcAuth Authentifizierung
         **/
        function __construct( $pxUebung, $pxAuth )
        {
            if ( (is_string($pxAuth)) || (is_numeric($pxAuth)) )
                $this->moStudent = new Student($pxAuth);
            elseif ($pxAuth instanceof Student)
                $this->moStudent = $pxAuth;
            else
                throw new Exception(_("Keine korrekten Authentifizierungsdaten �bergeben"));

            $this->moUebung     = new Uebung( $pxUebung );
            $this->moLogPrepare = DBManager::get()->prepare( "insert into ppv_uebungstudentlog select NULL as id, d.* from ppv_uebungstudent as d where uebung = :uebungid and student = :auth" );
        
            // pr�fe ob der Student Veranstaltungsteilnehmer ist
            $loPrepare = DBManager::get()->prepare( "select user_id from seminar_user where status = :status and Seminar_id = :semid and user_id = :auth" );
            $loPrepare->execute( array("semid" => $this->moUebung->veranstaltung()->id(), "auth" => $this->moStudent->id(), "status" => "autor") );
            $this->mlVeranstaltungsTeilnehmer = $loPrepare->rowCount() == 1;
        }


        /** liefert die Authentifizierung
         * @return AuthString
         **/
        function student()
        {
            return $this->moStudent;
        }


        /** liefert die �bung
         * @return liefert das �bungsobjekt
         **/
        function uebung()
        {
            return $this->moUebung;
        }

        
        /** liefert die IDs des Datensatzes
         * @return ID als Array
         **/
        function id()
        {
            return array( "uebung" => $this->moUebung->id(), "uid" => $this->moStudent->id() );
        }
    
    
        /** pr�ft, ob der Student aktuell Teilnehmer der Veranstaltung ist
         * @note diese Pr�fung mus vor jedem Eintrag erfolgen, da sonst User
         * in die Veranstaltung eingef�gt werden k�nnen, die nicht angemeldet
         * sind
         @ return boolean Wert �ber die Existenz
         **/
        function istVeranstaltungsTeilnehmer()
        {
            return $this->mlVeranstaltungsTeilnehmer;
        }


        /** liefert / setzt die erreichten Punkte f�r den Stundent / �bung
         * und schreibt ggf einen vorhanden Datensatz ins Log
         * @param $pn Punke
         * @return Punkte
         **/
        function erreichtePunkte( $pn = false )
        {
            $ln = 0;
            if (is_numeric($pn))
            {
                if ($this->moUebung->veranstaltung()->isClosed())
                    throw new Exception(_("Die Veranstaltung wurde geschlossen, es k�nnen keine �nderungen mehr durchgef�hrt werden"));
                if (!$this->istVeranstaltungsTeilnehmer())
                    throw new UserNotSeminarMember( sprintf(_("Der Benutzer [%s / %s] ist nicht als Teilnehmer der Veranstaltung eingetragen"), $this->moStudent->name(), $this->moStudent->email()) );

                if ($pn > $this->moUebung->maxPunkte())
                    throw new Exception(_("Erreichte Punkte sind sind gr��er als die m�glichen Punkte, die bei der �bung vergeben werden k�nnen. Bitte Zusatzpunkte verwenden"));
                if ($pn < 0)
                    throw new Exception(_("Erreichte Punkte m�ssen gr��er gleich Null sein"));

                $this->moLogPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );
                
                $loPrepare = DBManager::get()->prepare( "insert into ppv_uebungstudent (uebung, student, erreichtepunkte, korrektor) values (:uebungid, :auth, :punkte, :korrektor) on duplicate key update erreichtepunkte = :punkte, korrektor = :korrektor" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id(), "punkte" => floatval($pn), "korrektor" => $GLOBALS["user"]->id) );

                $ln = $pn;

            } else {
                $loPrepare = DBManager::get()->prepare( "select erreichtepunkte from ppv_uebungstudent where uebung = :uebungid and student = :auth" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $ln     = $result["erreichtepunkte"];
                }
            }

            return floatval($ln);
        }


        /** liefert / setzt die Zusatzpunkt zu einer �bung f�r einen Studenten
         * und schreibt die Daten ins Log sofern vorhanden
         * @param $pn Punkte
         * @return Punkte
         **/
        function zusatzPunkte( $pn = false )
        {
            $ln = 0;
            if (is_numeric($pn))
            {
                if ($this->moUebung->veranstaltung()->isClosed())
                    throw new Exception(_("Die Veranstaltung wurde geschlossen, es k�nnen keine �nderungen mehr durchgef�hrt werden"));
                if (!$this->istVeranstaltungsTeilnehmer())
                    throw new UserNotSeminarMember( sprintf(_("Der Benutzer [%s / %s] ist nicht als Teilnehmer der Veranstaltung eingetragen"), $this->moStudent->name(), $this->moStudent->email()) );
            
                if ($pn < 0)
                    throw new Exception(_("Zusatzpunkte m�ssen gr��er gleich Null sein"));

                $this->moLogPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );
                
                $loPrepare = DBManager::get()->prepare( "insert into ppv_uebungstudent (uebung, student, zusatzpunkte, korrektor) values (:uebungid, :auth, :punkte, :korrektor) on duplicate key update zusatzpunkte = :punkte, korrektor = :korrektor" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id(), "punkte" => floatval($pn), "korrektor" => $GLOBALS["user"]->id) );

                $ln = $pn;

            } else {
                $loPrepare = DBManager::get()->prepare( "select zusatzpunkte from ppv_uebungstudent where uebung = :uebungid and student = :auth" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $ln     = $result["zusatzpunkte"];
                }
            }
            
            return floatval($ln);
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
                if ($this->moUebung->veranstaltung()->isClosed())
                    throw new Exception(_("Die Veranstaltung wurde geschlossen, es k�nnen keine �nderungen mehr durchgef�hrt werden"));
                if (!$this->istVeranstaltungsTeilnehmer())
                    throw new UserNotSeminarMember( sprintf(_("Der Benutzer [%s / %s] ist nicht als Teilnehmer der Veranstaltung eingetragen"), $this->moStudent->name(), $this->moStudent->email()) );

                $this->moLogPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

                $loPrepare = DBManager::get()->prepare( "insert into ppv_uebungstudent (uebung, student, bemerkung, korrektor) values (:uebungid, :auth, :bemerkung, :korrektor) on duplicate key update bemerkung = :bemerkung, korrektor = :korrektor" );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id(), "bemerkung" => (empty($pc) ? null : $pc), "korrektor" => $GLOBALS["user"]->id) );

                $lc = $pc;
            } else {
                $loPrepare = DBManager::get()->prepare("select bemerkung from ppv_uebungstudent where uebung = :uebungid and student = :auth", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
                $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

                if ($loPrepare->rowCount() == 1)
                {
                    $result = $loPrepare->fetch(PDO::FETCH_ASSOC);
                    $lc     = $result["bemerkung"];
                }

            }
            
            return $lc;
        }


        /** liefert den Korrektor des Eintrages
         * @return Korrektorname & EMail oder null, wenn nicht vorhanden
         **/
        function korrektor()
        {
            $lc = null;

            $loPrepare = DBManager::get()->prepare("select korrektor from ppv_uebungstudent where uebung = :uebungid and student = :auth", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

            if ($loPrepare->rowCount() == 1)
            {
                $result = $loPrepare->fetch(PDO::FETCH_ASSOC);

                $lo     = new User($result["korrektor"]);
                $lc     = $lo->getFullName("full_rev") ." (".User::find($result["korrektor"])->email.")";
            }


            return $lc;
        }


        /** updated den Datensatz f�r alle drei Felder
         * @param $pnErreichtePunkte numerischer Wert der erreichten Punkte
         * @param $pnZusatzPunkte numerischer Wert der zus�tzlichen Punkte
         * @param $pcBemerkung Stringwert f�r die Bemerkung
         **/
        function update( $pnErreichtePunkte, $pnZusatzPunkte, $pcBemerkung )
        {
            if ($this->moUebung->veranstaltung()->isClosed())
                throw new Exception(_("Die Veranstaltung wurde geschlossen, es k�nnen keine �nderungen mehr durchgef�hrt werden"));
            if (!$this->istVeranstaltungsTeilnehmer())
                throw new UserNotSeminarMember( sprintf(_("Der Benutzer [%s / %s] ist nicht als Teilnehmer der Veranstaltung eingetragen"), $this->moStudent->name(), $this->moStudent->email()) );
        
            if (!is_numeric($pnErreichtePunkte))
                throw new Exception(_("Erreichte Punkte sind nicht numerisch"));
            if (!is_numeric($pnZusatzPunkte))
                throw new Exception(_("zus�tzliche Punkte sind nicht numerisch"));
            if ( (!empty($pcBemerkung)) && (!is_string($pcBemerkung)) )
                throw new Exception(_("Bemerkung ist nicht leer oder ist kein Text"));

            if ($pnErreichtePunkte > $this->moUebung->maxPunkte())
                throw new Exception(_("Erreichte Punkte sind sind gr��er als die m�glichen Punkte, die bei der �bung vergeben werden k�nnen. Bitte Zusatzpunkte verwenden"));
            if ($pnZusatzPunkte < 0)
                throw new Exception(_("Zusatzpunkte m�ssen gr��er gleich Null sein"));
            if ($pnErreichtePunkte < 0)
                throw new Exception(_("Erreichte Punkte m�ssen gr��er gleich Null sein"));


            $this->moLogPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

            $loPrepare = DBManager::get()->prepare( "insert into ppv_uebungstudent (uebung, student, bemerkung, korrektor, zusatzpunkte, erreichtepunkte) values (:uebungid, :auth, :bemerkung, :korrektor, :zusatzpunkte, :erreichtepunkte) on duplicate key update bemerkung = :bemerkung, zusatzpunkte = :zusatzpunkte, erreichtepunkte = :erreichtepunkte, korrektor = :korrektor" );
            $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id(), "bemerkung" => (empty($pcBemerkung) ? null : $pcBemerkung), "korrektor" => $GLOBALS["user"]->id, "zusatzpunkte" => $pnZusatzPunkte, "erreichtepunkte" => $pnErreichtePunkte) );
        }
        

        /** liefert alle Logeintr�ge f�r diese Student-�bungsbeziehung
         * als assoziatives Array
         * @return assoziatives Array
         **/
        function log()
        {
            $la = array();

            $loPrepare = DBManager::get()->prepare("select erreichtepunkte, zusatzpunkte, bemerkung, korrektor from ppv_uebungstudentlog where uebung = :uebungid and student = :auth order by id desc", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY) );
            $loPrepare->execute( array("uebungid" => $this->moUebung->id(), "auth" => $this->moStudent->id()) );

            foreach( $loPrepare->fetchAll(PDO::FETCH_ASSOC) as $row )
            {
                $lo                     = new User($row["korrektor"]);
                $row["korrektor"]       = $lo->getFullName("full_rev") ." (".User::find($row["korrektor"])->email.")";
                $row["zusatzpunkte"]    = floatval($row["zusatzpunkte"]);
                $row["erreichtepunkte"] = floatval($row["erreichtepunkte"]);
                
                array_push($la, $row );
            }

            return $la;
        }

    }

?>
