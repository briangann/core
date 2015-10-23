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

if (!class_exists('exchange_raids')) {
    class exchange_raids extends gen_class {
        public static $shortcuts = array('user', 'config', 'pex'=>'plus_exchange', 'pdh', 'time', 'env' => 'environment');
        public $options		= array();

	public function get_raids($params, $body) {
	    $isAPITokenRequest = $this->pex->getIsApiTokenRequest();
            if ($this->user->check_auth('a_raid_add', false) || $isAPITokenRequest){
                $raidlist = $this->pdh->get('raid', 'id_list');
                $raidlist = $this->pdh->sort($raidlist, 'raid', 'date', 'desc');
	        $out = array();
                foreach ($raidlist as $key => $row){
                    $raid_id = $row;
                    //$date_info = $this->time->user_date($this->pdh->get('raid', 'date', array($row)));
                    $date_raw = $this->pdh->get('raid', 'date', array($row));
                    $date_info = $this->pdh->get('raid', 'date', array($row));
                    $date_info = date("Y-m-d H:i:s", $date_raw);
                    $added_by = $this->pdh->get('raid', 'added_by', array($row));
                    $event_name = stripslashes($this->pdh->get('raid', 'event_name', array($row)));
                    $event_id = stripslashes($this->pdh->get('raid', 'event', array($row)));
                    $raid_note = stripslashes($this->pdh->get('raid', 'note', array($row)));
                    $out['raid:'.$raid_id] = array(
				'id'	=> $raid_id,
                                'date' => $date_info,
                                'date_raw' => $date_raw,
                                'added_by' => $added_by,
				'note'	=> $raid_note,
				'event_name' => $event_name,
				'event_id' => $event_id,
		    );
                }
                return $out;
	    } else {
	        return $this->pex->error('access denied');
	    }
        }
    }
}
?>
