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



    /** Klasse um Zugriffsrechte zu einer Veranstaltung f�r einen User zu pr�fen **/
    class CoursePermission
    {

        /** pr�ft, ob der aktuelle Benuter auf der aktuellen Veranstaltung Dozentenrechte hat
         * @return Boolean f�r die Rechte
         **/
        static function hasDozentRecht()
        {
            return isset($GLOBALS["SessionSeminar"]) ? $GLOBALS["perm"]->have_studip_perm("dozent", $GLOBALS["SessionSeminar"]) : false ;
        }


        /** pr�ft, ob der aktuelle Benuter auf der aktuellen Veranstaltung Tutorenrechte hat
         * @return Boolean f�r die Rechte
         **/
        static function hasTutorRecht()
        {
            return isset($GLOBALS["SessionSeminar"]) ? $GLOBALS["perm"]->have_studip_perm("tutor", $GLOBALS["SessionSeminar"]) : false ;
        }


        /* pr�ft, ob der aktuelle User Autorenrechte in der Veranstaltung hat
         * @return Boolean f�r die Rechte
         **/
        static function hasAutorRecht()
        {
            return isset($GLOBALS["SessionSeminar"]) ? $GLOBALS["perm"]->have_studip_perm("autor", $GLOBALS["SessionSeminar"]) : false ;
        }

    }

?>
