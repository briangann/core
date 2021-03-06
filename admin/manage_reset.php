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

define('EQDKP_INC', true);
define('IN_ADMIN', true);
$eqdkp_root_path = './../';
include_once($eqdkp_root_path . 'common.php');

class reset_eqdkp extends page_generic {
	private $resets		= array('raids', 'events', 'items', 'itempools', 'adjustments', 'multipools', 'chars', 'plugins', 'user', 'logs', 'calendar');
	private $dependency	= array(
		'events'	=> array('raids', 'items', 'multipools', 'calendar'),
		'raids'		=> array('items'),
		'chars'		=> array('raids', 'items', 'adjustments'),
		'itempools'	=> array('multipools')
	);
	private $toreset	= array();
	private $resetted	= array();
	private $modules	= array(
		'raids'			=> 'raid',
		'events'		=> 'event',
		'items'			=> 'item',
		'itempools'		=> 'itempool',
		'adjustments'	=> 'adjustment',
		'multipools'	=> 'multidkp',
		'chars'			=> 'member',
		'user'			=> 'user'
	);

	public function __construct(){
		$this->user->check_auth('a_reset');
		parent::__construct(false, false, 'plain', null, '_class_');
		$this->process();
	}
	
	public function delete(){
		//Make a backup
		$this->backup->createDatabaseBackup('zip',true,false,true);	
			
		$this->toreset = $this->in->getArray('selected', 'string');
		$log_action = array('{L_entries_reset}' => '');
		foreach ($this->toreset as $value){
			$this->reset_val($value);
			$log_action['{L_'.$value.'}'] = '';
		}
		$this->pdh->process_hook_queue();
		$this->pdc->flush();
		$this->core->message($this->user->lang('reset_success'), $this->user->lang('success'), 'green');
		$this->logs->add('action_reset', $log_action);
		$this->display();
	}
	
	public function reset_val($value) {
		if(!in_array($value, $this->resets) || in_array($value, $this->resetted)) return false;
		if(isset($this->dependency[$value])) {
			foreach($this->dependency[$value] as $dep) {
				if (!in_array($dep, $this->toreset)){
					$this->core->message($this->user->lang('reset_dependency_info'), 'Error', 'red');
					return false;
				}
				$this->reset_val($dep);
			}
		}
		$function = 'reset__'.$value;
		if(method_exists($this, $function)) {
			$this->$function();
		} else {
			if(isset($this->modules[$value])) $this->pdh->put($this->modules[$value], 'reset');
		}
		$this->resetted[] = $value;
		return true;
	}

	public function reset__plugins(){
		foreach($this->pm->get_plugins(PLUGIN_INSTALLED) as $value){
			$this->pm->uninstall($value);
		}
	}
	
	public function reset__calendar() {
		$this->pdh->put('calendar_events', 'reset');
		$this->pdh->put('calendar_raids_attendees', 'reset');
		$this->pdh->put('calendar_raids_guests', 'reset');
		$this->pdh->put('calendar_raids_templates', 'reset');
		$this->pdh->put('calendars', 'reset');
	}
	
	public function reset__logs() {
		$this->pdh->put('logs', 'truncate_log');
	}

	public function display(){
		foreach($this->resets as $type) {
			$this->tpl->assign_block_vars('reset_row', array(
				'TYPE'			=> $this->user->lang($type),
				'DISC'			=> $this->user->lang('reset_'.$type.'_disc'),
				'VAL_NAME'		=> $type,
			));
		}

		$js = "
			$('.cb_select_class').change(function(){
				if(!$(this).prop('checked')) return;
				var id = $(this).attr('id').substr(3);
				var dependency = new Array();
				";
		foreach($this->dependency as $key => $deps) {
			$js .= "dependency['".$key."'] = new Object();
				";
			foreach($deps as $dk => $dep) {
				$js .= "dependency['".$key."']['".$dk."'] = '".$dep."';
				";
			}
		}
		$js .= "if(typeof dependency[id] != undefined) {
					for(var current in dependency[id]) {
						current = dependency[id][current];
						if(!$('#cb_'+current).attr('checked')) {
							$('#cb_'+id).removeAttr('checked');
							eval(id+'_warning()');
							break;
						}
					}
				}
			});
		";
		$this->tpl->add_js($js, 'docready');
		foreach(array_keys($this->dependency) as $key) {
			$custom_js = "$('#cb_".$key."').attr('checked', 'checked');$('#cb_".implode("').attr('checked', 'checked');$('#cb_", $this->dependency[$key])."').attr('checked', 'checked');";
			$this->jquery->Dialog($key.'_warning', $this->user->lang('attention'), array('message'=> $this->user->lang('reset_'.$key.'_warning'), 'custom_js'	=> $custom_js, 'height' => 260), 'confirm');
		}
		$this->confirm_delete($this->user->lang('reset_confirm'));
		
		$this->tpl->add_js('$("#reset_selectall").click(function(){
					var checked_status = this.checked;
					$(".cb_select_class").each(function(){
						this.checked = checked_status;
					});
				});', 'docready');
		
		$this->core->set_vars(array(
			'page_title'		=> $this->user->lang('title_resetdkp'),
			'template_file'		=> 'admin/manage_reset.html',
			'display'			=> true
		));
	}
}
registry::register('reset_eqdkp');
?>