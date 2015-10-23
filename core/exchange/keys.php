<?php
/*	Project:	EQdkp-Plus
 *	Package:	EQdkp-plus
 *	Link:		http://eqdkp-plus.eu
 *
 *	Copyright (C) 2006-2015 EQdkp-Plus Developer Team
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU Affero General Public License as published
 *	by the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU Affero General Public License for more details.
 *
 *	You should have received a copy of the GNU Affero General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('EQDKP_INC')){
	die('Do not access this file directly.');
}

if (!class_exists('exchange_keys')) {
    class exchange_keys extends gen_class {
        public static $shortcuts = array('user', 'config', 'pex'=>'plus_exchange', 'pdh', 'time', 'env' => 'environment');
        public $options		= array();

	public function get_keys($params, $body) {
	    $isAPITokenRequest = $this->pex->getIsApiTokenRequest();
            if ($this->user->check_auth('a_raid_add', false) || $isAPITokenRequest){
                /* if we get here, the API key supplied is working - adding raids is allowed */
                return array('access'   => 'granted');
	    } else {
	        return $this->pex->error('access denied');
	    }
        }
    }
}
?>
