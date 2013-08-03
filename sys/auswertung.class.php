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



    require_once("veranstaltung/veranstaltung.class.php");
    require_once("veranstaltung/studentuebung.class.php");


    /** Klasse um die Auswertung zentral zu behandeln **/
    class Auswertung
    {

        /** Veranstaltung **/
        private $moVeranstaltung = null;


        /** Ctor, um f�r eine Veranstaltung alle Auswertungen erzeugen
         * @param $po Veranstaltung
         **/
        function __construct( $po )
        {
            if (!($po instanceof Veranstaltung))
                throw new Exception(_("Es wurde kein Veranstaltungsobjekt �bergeben"));

            $this->moVeranstaltung = $po;
        }


        /** liefert die Veranstaltung zur�ck
         * @return Veranstaltung
         **/
        function veranstaltung()
        {
            return $this->moVeranstaltung;
        }


        /** erzeugt ein Array mit den informationen eines Studenten
         * @param $poStudent Studentenobjekt
         * @return Array
         **/
        private function createStudentenArray( $poStudent )
        {
            return array(
                "name"            => $poStudent->name(),
                "matrikelnummer"  => $poStudent->matrikelnummer(),
                "email"           => $poStudent->email(),
                // Studiengang f�r die Anerkennung fehlt noch
            );
        }


        /** erzeugt aus einem Student�bungsobjekt das passende
         * Array mit den Informationen 
         * @param $poUebungStudent �bungStudent Objekt
         * @param $pnBestandenPunkte Punkteanzahl, die f�r das Bestehen notwendig sind
         * @param $pnUebungMaxPunkte maximal zu erreichende Punkte der �bung
         * @return Array mit Daten
         **/
        private function createStudentenPunkteArray( $poUebungStudent, $pnBestandenPunkte, $pnUebungMaxPunkte )
        {
            $data = array(
                 "erreichtepunkte" => $poUebungStudent->erreichtePunkte(),
                 "zusatzpunkte"    => $poUebungStudent->zusatzPunkte()
            );

            $data["punktesumme"]      = $data["erreichtepunkte"] + $data["zusatzpunkte"];
            $data["bestanden"]        = $data["punktesumme"] >= $pnBestandenPunkte;
            $data["erreichteprozent"] = round($data["punktesumme"] / $pnUebungMaxPunkte * 100, 2);

            return $data;
        }


        /** erzeugt aus einem �bungsobjekt den passenden Eintrag f�r das Array
         * @param $poUebung �bungsobjekt
         * @return Array
         **/
        private function createUebungsArray( $poUebung )
        {
            $data = array(
                "id"               => $poUebung->id(),
                "maxPunkte"        => $poUebung->maxPunkte(),
                "bestandenProzent" => $poUebung->bestandenProzent(),
                "studenten"        => array(),
            );
            $data["bestandenpunkte"] = round($data["maxPunkte"] / 100 * $data["bestandenProzent"], 2);

            return $data;
        }


        /** liefert eine assoc. Array das f�r jeden Studenten die Anzahl der Punkt
         * erzeugt und gleichzeitig min / max / median / arithm. Mittel bestimmt
         * @return assoc. Array
         **/
        function studenttabelle()
        {
            // das globale Array enth�lt einmal die Liste aller Studenten und eine Liste der �bungen
            $main = array( "studenten" => array(), "uebungen" => array() );


            // Iteration �ber jede �bung und �ber jeden Teilnehmer
            foreach ( $this->moVeranstaltung->uebungen() as $uebung)
            {
                $uebungarray = $this->createUebungsArray( $uebung );

                foreach ($uebung->studentenuebung() as $studentuebung )
                {
                    // Student der globalen Namensliste hinzuf�gen bzw. �berschreiben und Punktedaten erzeugen
                    $main["studenten"][$studentuebung->student()->id()]       = $this->createStudentenArray( $studentuebung->student() );
                    $uebungarray["studenten"][$studentuebung->student()->id()] = $this->createStudentenPunkteArray( $studentuebung, $uebungarray["bestandenpunkte"], $uebungarray["maxPunkte"] );
                }

                $main["uebungen"][$uebung->name()] = $uebungarray;
            }



            // nun existiert ein Array mit den Basis Informationen zu jedem Studenten & jeder �bung
            // da ein Student sich w�hrend des Semesters aus der Veranstaltung austragen kann, in
            // der globalen Liste aber alle Teilnehmer vorhanden sind, m�ssen nun die �bungen so angepasst
            // werden, dass sie gleich viele Elemente erhalten, d.h. falls Studenten nicht in allen �bungen
            // enthalten sind, werden sie Default mit Null-Werten eingef�gt
            foreach ($main["uebungen"] as $item)
            {
                $uebung       = new Uebung( $this->moVeranstaltung, $item["id"] );
                $lcUebungName = $uebung->name();
                $uebungarray  = $this->createUebungsArray( $uebung );

                foreach( array_diff_key($main["studenten"], array_fill_keys($uebung->studentenuebung(true), null)) as $key )
                {
                    $loStudentUebung = new StudentUebung( $uebung, $key );
                    $main["uebungen"][$lcUebungName]["studenten"][$studentuebung->student()->id()] = $this->createStudentenPunkteArray( $loStudentUebung, $uebungarray["bestandenpunkte"], $uebungarray["maxPunkte"] );
                    var_dump($loStudentUebung);
                    die(" ");
                }
                    
            }



            /*
             $min                        = min($min, $studentdata["punktesumme"]);
             $max                        = max($max, $studentdata["punktesumme"]);
             $sum                        = $sum + $studentdata["punktesumme"];

             if ($studentdata["bestanden"])
             $countbestanden++;
             else
             $countnichtbestanden++;
             */


            /*
             $min                 = INF;
             $max                 = 0;
             $sum                 = 0;
             $countbestanden      = 0;
             $countnichtbestanden = 0;
             */

            /*
             $uebungdata["punktemittel"]          = round($sum / count($uebungdata["studenten"], 2));
             $uebungdata["punkteminimum"]         = $min;
             $uebungdata["punktemaximum"]         = $max;

             $uebungdata["anzahlbestanden"]       = $countbestanden;
             $uebungdata["anzahlnichtbestanden"]  = $countnichtbestanden;
             $uebungdata["prozentbestanden"]      = round($uebungdata["anzahlbestanden"] / ($uebungdata["anzahlbestanden"]+$uebungdata["anzahlnichtbestanden"]) * 100, 2);
             $uebungdata["prozentnichtbestanden"] = round($uebungdata["anzahlnichtbestanden"] / ($uebungdata["anzahlbestanden"]+$uebungdata["anzahlnichtbestanden"]) * 100, 2);
             */


            return $main;
        }


    }


?>
