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



    require_once(dirname(dirname(__DIR__)) . "/sys/tools.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/veranstaltung/veranstaltung.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/auswertung.class.php");
    require_once(dirname(dirname(__DIR__)) . "/sys/student.class.php");

    

    Tools::showMessage($flash["message"]);

    try {

        $loVeranstaltung = isset($flash["veranstaltung"]) ? $flash["veranstaltung"] : null;
        if (!$loVeranstaltung)
            throw new Exception(_("keine Veranstaltung gefunden"));

        $loStudent    = new Student($GLOBALS["user"]->id);

        $loAuswertung = new Auswertung($loVeranstaltung);
        $laAuswertung = $x->studentdaten($loStudent);


        echo "<table width=\"100%\">";
        echo "<tr><th>"._("�bung")."</th><th>"._("erreichte Punkte")."</th><th>"._("erreichte Prozent")."</th></tr>";

        foreach( $laAuswertung as $laUebung )
            echo "<tr><td>".$laUebung["name"]."</td><td> ".$laUebung["punktesumme"]."</td><td>".$laUebung["erreichteprozent"]."%</td></tr>";


        if ($loStudent)
        {
            echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
            echo "<tr><td>"._("Anerkennung f�r den Studiengang:")."</td><td colspan=\"2\">";

            $laStudiengang = reset($loStudent->studiengang($loVeranstaltung));
            if ($loVeranstaltung->isClosed())
                echo $laStudiengang["abschluss"]." ".$laStudiengang["fach"];

            else {
                $laStudiengaenge = $loStudent->studiengang();

                if (count($laStudiengaenge) > 1)
                {
                    $laStudiengang = reset($loStudent->studiengang($loVeranstaltung));


                    echo "<form method=\"post\" action=\"".$controller->url_for("show/studiengang")."\">\n";
                    CSRFProtection::tokenTag();

                    echo "<select name=\"studiengang\" size=\"1\">";
                    foreach ($laStudiengaenge as $item)
                        if ( ($item["abschluss_id"]) && ($item["fach_id"]) ) {
                            $lcSelect = null;
                            if ( (!empty($laStudiengang)) && ($laStudiengang["abschluss_id"] == $item["abschluss_id"]) && ($laStudiengang["fach_id"] == $item["fach_id"]) )
                                $lcSelect = "selected=\"selected\"";

                            echo "<option value=\"".$item["abschluss_id"]."#".$item["fach_id"]."\" ".$lcSelect.">".trim($item["abschluss"]." ".$item["fach"])."</option>";
                        }
                    echo "</select>";

                    echo "<input type=\"submit\" name=\"submitted\" value=\""._("�bernehmen")."\"/>";
                    echo "</form>";

                } else {
                    $laStudiengaenge = reset($laStudiengaenge);
                    echo $laStudiengaenge["abschluss"]." ".$laStudiengaenge["fach"];
                }

            }
            echo "</td></tr>";

        }

        echo "</table>";


    } catch (Exception $e) {
        Tools::showMessage( Tools::createMessage("error", $e->getMessage()) );
    }

?>