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

if(!defined('EQDKP_INC')){
	die('Do not access this file directly.');
}

if(!class_exists('page_generic')){
	class page_generic extends gen_class {
		private $cd_module = false;
		private $cd_tag = false;
		private $cd_params = array('%id%');
		private $cb_name = '';
		
		private $pre_check = '';
		private $params = array();
		
		protected $handler = array(
			'del'	=> array('process' => 'delete', 'csrf'=>true),
			'add'	=> array('process' => 'add', 'csrf'=>true),
			'upd'	=> array('process' => 'update', 'csrf'=>true),
			'edit'	=> array('process' => 'edit'),
		);
		
		protected $url_id = false;
		protected $simple_head = '';
		protected $action = '';
	
		public function __construct($pre_check, $handler=false, $pdh_call=array(), $params=null, $cb_name='', $url_id='') {
			$this->pre_check = $pre_check;
			$this->simple_head = ($this->in->exists('simple_head')) ? 'simple' : 'full';
			$this->simple_head_url = ($this->simple_head == 'simple') ? '&amp;simple_head=simple' : '';
			$this->url_id_ext = '';
			if($url_id) {
				$this->url_id = $this->in->get($url_id, 0);
				$this->url_id_ext = '&amp;'.$url_id.'='.$this->url_id;
			}
			if(is_array($pdh_call) AND count($pdh_call) > 1) {
				$this->cd_module	= @$pdh_call[0];
				$this->cd_tag		= @$pdh_call[1];
			} elseif($pdh_call == 'plain') {
				$this->cd_module	= 'plain';
			}
			if($params) $this->cd_params = $params;
			if($cb_name) $this->cb_name = $cb_name;
			if($this->in->get('get_cd')) {
				$this->output_deletion_text();
			}
			if(is_array($handler)) $this->handler = array_merge($this->handler, $handler);
			foreach($this->handler as $key => &$handle) {
				if(!isset($handle['check'])) $handle['check'] = $this->pre_check.$key;
			}
			$this->action = $this->env->phpself.$this->SID.$this->simple_head_url.$this->url_id_ext;
			$this->tpl->assign_vars(array(
				'ACTION'	=> $this->action,
			));
		}
		
		public function set_url_id($name, $param){
			$this->url_id = $param;
			$this->url_id_ext = '&amp;'.$name.'='.$param;
			$this->action = $this->env->phpself.$this->SID.$this->simple_head_url.$this->url_id_ext;
			$this->tpl->assign_vars(array(
				'ACTION'	=> $this->action,
			));
		}
		
		public function get_hptt($hptt_settings, $full_list, $filtered_list, $sub_array, $cache_suffix = '', $sort_suffix = 'sort') {
			return registry::register('html_pdh_tag_table', array($hptt_settings, $full_list, $filtered_list, $sub_array, $cache_suffix, $sort_suffix));
		}
	
		protected function confirm_delete($message='', $url='', $single=false, $options=array()) {
			$url = ($url) ? $url : $this->env->request;
			$url .= (strpos($url, '?') !== false) ? '&' : '?';
			if ($single){
				$param = (strpos($this->cb_name, '[]') !== false) ? substr($this->cb_name, 0, strpos($this->cb_name, '[]')) : 'selected_id';
				$url .= $param."='+selectedID+'&";
				$options['withid'] = 'selectedID';
			}
			$message = ($message) ? $message : $this->user->lang('confirm_delete');
			$handler = 'del';
			if(isset($options['handler'])) {
				$handler = $options['handler'];
				unset($options['handler']);
			}
			$options['url'] = $url.$handler.'=true'.'&link_hash='.$this->CSRFGetToken($handler).str_replace('&amp;', '&', $this->url_id_ext);
			$options['confirm_url'] = (($this->cd_module AND !$single) || (isset($options['force_ajax']) && $options['force_ajax'])) ? $url."get_cd=true" : false;
			$options['confirm_name'] = $this->cb_name;
			$options['message'] = sprintf($message, ((($this->cd_module AND !$single) || (isset($options['force_ajax']) && $options['force_ajax'])) ? '<div class="confirm_content"><span style="display:none;">#replacedata#</span></div>' : ''));
			if(($this->cb_name == '_class_' OR strpos($this->cb_name, '[]') !== false) AND !$single) {
				$options['custom_js'] = "$('#mass_del_submit').removeAttr('disabled'); $('form:has(#mass_del_submit)').submit();";
			}
			$funcname = 'delete_warning';
			if(isset($options['function'])) {
				$funcname = $options['function'];
				unset($options['function']);
			}
			$options['width'] = 200;
			$options['height'] = 280;
			$this->jquery->Dialog($funcname, $this->user->lang('confirm_deletion'), $options, 'confirm');
		}
		
		protected function process() {
			foreach($this->handler as $key => $process) {				
				if($this->in->exists($key) AND !is_array(current($process))) {
					if($this->pre_check && $process['check'] !== false) $this->user->check_auth($process['check']);
					
					if(isset($process['csrf']) && $process['csrf']) {
						$blnResult = $this->checkCSRF($key);
						if (!$blnResult) break;
					}

					if(method_exists($this, $process['process'])) $this->{$process['process']}();
					break;
				} elseif($this->in->get($key) AND is_array(current($process))) {
					foreach($process as $subprocess) {
						if($subprocess['value'] == $this->in->get($key)) {
							if($this->pre_check && $subprocess['check'] !== false) $this->user->check_auth($subprocess['check']);
							
							if(isset($subprocess['csrf']) && $subprocess['csrf']) {
								$blnResult = $this->checkCSRF($key);
								if (!$blnResult) break;
							}
							
							$this->{$subprocess['process']}();
							break 2;
						}
					}
				}
			}
			if($this->pre_check) $this->user->check_auth($this->pre_check);
			$this->display();
		}
		
		/*
		 * Echoes Additional text for delete_confirm window
		 * expects ids given via _GET or _POST with key $this->cb_name
		 */
		protected function output_deletion_text() {
			$text = array();
			$id_key = array_search('%id%', $this->cd_params);
			if(is_array($this->in->getArray('type', 'string'))) {
				$ids = $this->in->getArray('type', 'string');
			} else {
				$ids = array($this->in->get('type'), '');
			}

			foreach($ids as $id) {
				$this->params[$id_key] = $id;
				$text[] = ($this->cd_module == 'plain') ? $this->user->lang($id, true, false) : $this->pdh->get($this->cd_module, $this->cd_tag, $this->params);
			}
			header('content-type: text/html; charset=UTF-8');
			echo '<ul><li>'.implode('</li><li>', $text).'</li></ul>';
			exit;
		}
		
		protected function checkCSRF($strProcess){
			$strAction = get_class($this).$strProcess;
			$blnCheckGet = $this->user->checkCsrfGetToken($this->in->get('link_hash'), $strAction);
			$blnCheckPost = $this->user->checkCsrfPostToken($this->in->get($this->user->csrfPostToken()));
			$blnCheckPostOld = $this->user->checkCsrfPostToken($this->in->get($this->user->csrfPostToken(true)));
			
			if ($blnCheckGet || $blnCheckPost || $blnCheckPostOld) {
				return true;
			}
			$this->core->message($this->user->lang('error_invalid_session_key'), $this->user->lang('error'), 'red');
			return false;
		}
				
		protected function CSRFGetToken($strProcess){
			$strAction = get_class($this).$strProcess;
			return $this->user->csrfGetToken($strAction);
		}
	}
}
?>