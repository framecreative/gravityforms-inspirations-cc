<?php
/*
Plugin Name: Gravity Forms Inspirations CC Processing
Plugin URI: http://www.gravityforms.com
Description: A plugin to change how the credit card processing / storing works on the Inspirations Studios site
Version: 1.3
Author: F / R / A / M / E Creative
Author URI: https://framecreative.com.au

Bitbucket Plugin URI: https://bitbucket.org/framecreative/gravity-forms-inspirations-cc

Bitbucket Branch: master

------------------------------------------------------------------------
Copyright 2012-2016 Rocketgenius Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

define( 'GF_INSPIRATIONS_CC_VERSION', '1.3' );

add_action( 'gform_loaded', array( 'GF_Inspirations_Cc_Bootstrap', 'load' ), 5 );

class GF_Inspirations_Cc_Bootstrap {

    public static function load() {

        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }

        require_once( 'class-gfinspirations-cc.php' );

        GFAddOn::register( 'GFInspirationsCc' );
    }

}

function gf_simple_addon() {
    return GFInspirationsCc::get_instance();
}
