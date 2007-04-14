<?php
/**
 * Class for printing the map in NagVis
 */
class NagVisMap extends GlobalMap {
	var $MAINCFG;
	var $MAPCFG;
	var $BACKEND;
	var $GRAPHIC;
	
	var $objects;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalMainCfg 	$MAINCFG
	 * @param 	GlobalMapCfg 	$MAPCFG
	 * @param 	GlobalBackend 	$BACKEND
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function NagVisMap(&$MAINCFG,&$MAPCFG,&$LANG,&$BACKEND) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::NagVisMap($MAINCFG,$MAPCFG,$LANG,$BACKEND)');
		$this->MAINCFG = &$MAINCFG;
		$this->MAPCFG = &$MAPCFG;
		$this->LANG = &$LANG;
		$this->BACKEND = &$BACKEND;
		
		$this->GRAPHIC = new GlobalGraphic();
		
		parent::GlobalMap($MAINCFG,$MAPCFG);
		
		$this->objects = $this->getMapObjects(1,1);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::NagVisMap()');
	}
	
	/**
	 * Parses the Map and the Objects
	 *
	 * @return	Array 	Array with Html Code
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function parseMap() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::parseMap()');
		$ret = Array();
		$ret[] = $this->getBackground();
		$ret = array_merge($ret,$this->parseObjects());
		
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::parseMap(): Array(...)');
		return $ret;
	}
	
	/**
	 * Parses the Objects
	 *
	 * @return	Array 	Array with Html Code
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function parseObjects() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::parseObjects()');
		$ret = Array();
		foreach($this->objects AS $obj) {
			switch($obj['type']) {
				case 'textbox':
					// css class of the textbox
					$obj['class'] = 'box';
					
					// default background color
					if(!isset($obj['background_color']) || $obj['background_color'] == '') {
						$obj['background_color'] = '#CCCCCC';
					} elseif($obj['background_color'] == 'transparent') {
						$obj['background_color'] = '';
					}
					
					// Check if set a hostname
					if(isset($obj['host_name'])) {
				  		// Output the Error-Message into the textbox.
						if ($obj['state'] == 'ERROR') {
				  			$obj['text'] = $obj['stateOutput'];
				  		}
				  		
				  		if(in_array($obj['state'],Array('PENDING','UP','DOWN','UNREACHABLE','ERROR'))) {
				  			$obj['class'] = 'box_'.$obj['state'];
				  		}
						$ret = array_merge($ret,$this->textBox($obj));
					} else {
						$ret = array_merge($ret,$this->textBox($obj));
					}
				break;
				case 'shape':
					if(isset($obj['url']) && $obj['url'] != '') {
						$link = 1;
					} else {
						$link = 0;
					}
					
					if(isset($obj['hover_url']) && $obj['hover_url'] != '') {
						$hoverMenu = 1;
					} else {
						$hoverMenu = 0;
					}
					
					$obj = $this->fixIconPosition($obj);
					$ret[] = $this->parseIcon($obj,$link,$hoverMenu);
				break;
				default:
					// replace macros in url and hover_url
					$obj = $this->replaceMacros($obj);
				
					if(isset($obj['line_type'])) {
						if($obj['line_type'] != '20') {
							// a line with one object...
							$ret = array_merge($ret,$this->createBoxLine($obj,$obj['state'],NULL,$obj[$name]));
						} else {
							// a line with two objects...
							$ret = array_merge($ret,$this->createBoxLine($obj,$obj['state1'],$obj['state2'],$obj[$name]));
						}
					} else {
						$obj = $this->fixIconPosition($obj);
						$icon = $this->parseIcon($obj);
						if (DEBUG&&DEBUGLEVEL&2) debug('Start array_merge(Array(...),Array(...))');
						$ret[] = $icon;
						if (DEBUG&&DEBUGLEVEL&2) debug('End array_merge(Array(...),Array(...))');
					}
				break;	
			}
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::parseObjects(): Array(...)');
		return $ret;
	}
	
	/**
	 * Adds paths to the icon
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	Array	Array with object informations
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function fixIconPosition(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::fixIconPosition(&$obj)');
		$return = parent::fixIconPosition($this->getIconPaths($obj));
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::fixIconPosition(): '.$return);
		return $return;
	}
	
	/**
	 * Create a Link for a Line
	 *
	 * @param	Array	$obj	Array with object informations
	 * @param	String	$name	Key for Objectname eg. host_name
	 * @return	Array	Array with HTML Code
	 * @fixme 	FIXME 1.1: optimize
	 */
	function createBoxLine(&$obj,$name) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::createBoxLine(&$obj,'.$name.')');
		$ret = Array();
	    if($obj['line_type'] == '10' || $obj['line_type'] == '11'){
			list($x_from,$x_to) = explode(',', $obj['x']);
			list($y_from,$y_to) = explode(',', $obj['y']);
			$obj['x'] = $this->GRAPHIC->middle($x_from,$x_to);
			$obj['y'] = $this->GRAPHIC->middle($y_from,$y_to);
			$obj['icon'] = '20x20.gif';
			
			$obj = $this->fixIconPosition($obj);
			$ret[] = $this->parseIcon($obj);
		} elseif($mapCfg['line_type'] == '20') {
			list($host_name_from,$host_name_to) = explode(',', $mapCfg[$name]);
			list($service_description_from,$service_description_to) = explode(',', $mapCfg['service_description']);
			list($x_from,$x_to) = explode(',', $mapCfg['x']);
			list($y_from,$y_to) = explode(',', $mapCfg['y']);
			
			// From
			$obj['x'] = $this->GRAPHIC->middle2($x_from,$x_to);
			$obj['y'] = $this->GRAPHIC->middle2($y_from,$y_to);
			$obj['icon'] = '20x20.gif';
			
			$obj = $this->fixIconPosition($obj);
			$ret[] = $this->parseIcon($obj);
			
			// To
			$obj['x'] = $this->GRAPHIC->middle2($x_to,$x_from);
			$obj['y'] = $this->GRAPHIC->middle2($y_to,$y_from);
			$obj = $this->fixIconPosition($obj);
			$ret[] = $this->parseIcon($obj);
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::createBoxLine(): Array(...)');
		return $ret;
	}
	
	/**
	 * Parses the HTML-Code of an icon
	 *
	 * @param	Array	$obj		Array with object informations
	 * @param	String	$base		Array with object informations
	 * @param	Boolean	$link		Add a link to the icon
	 * @param	Boolean	$hoverMenu	Add a hover menu to the icon
	 * @return	String	String with Html Code
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function parseIcon(&$obj,$link=1,$hoverMenu=1) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::parseIcon(&$obj,'.$link.','.$hoverMenu.')');
		
		if($obj['type'] == 'shape') {
			if(preg_match('/^\[(.*)\]$/',$obj['icon'],$match) > 0) {
				$imgPath = $match[1];
			} else {
				$imgPath = $this->MAINCFG->getValue('paths', 'htmlshape').$obj['icon'];
			}
		} else {
			$imgPath = $this->MAINCFG->getValue('paths', 'htmlicon').$obj['icon'];
		}
		
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		$ret = '<div class="icon" style="left:'.$obj['x'].'px;top:'.$obj['y'].'px;z-index:'.$obj['z'].';">';
		
		if($link) {
			$ret .= $this->createLink($obj);
		}
		
		if($hoverMenu) {
			$menu = $this->getHoverMenu($obj).';';
		} else {
			$menu = '';
		}
		
		$ret .= '<img src="'.$imgPath.'" '.$menu.'>';
		
		if($link) {
			$ret .= '</a>';
		}
		
		$ret .= '</div>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::parseIcon(): Array(...)');
		return $ret;
	}
	
	/**
	 * Replaces macros of urls and hover_urls
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	Array	$obj	Modified array
	 * @author 	Michael Luebben <michael_luebben@web.de>
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function replaceMacros(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::replaceMacros(&$obj)');
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		$obj['url'] = str_replace('['.$name.']',$obj[$name],$obj['url']);
		$obj['hover_url'] = str_replace('['.$name.']',$obj[$name],$obj['hover_url']);
		
		if($obj['type'] == 'service') {
			$obj['url'] = str_replace('[service_description]',$obj['service_description'],$obj['url']);
			$obj['hover_url'] = str_replace('[service_description]',$obj['service_description'],$obj['hover_url']);
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::replaceMacros(): Array(...)');
		return $obj;
	}
	
	/**
	 * Creates a link to Nagios, when this is not set in the Config-File
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String	The Link
	 * @author 	Michael Luebben <michael_luebben@web.de>
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function createLink(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::createLink(&$obj)');
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		if(isset($obj['url']) && $obj['url'] != '') {
			$link = '<A HREF='.$obj['url'].'>';
    	} else {
    		switch($obj['type']) {
    			case 'map':
    				$link = '<a href="'.$this->MAINCFG->getValue('paths', 'htmlbase').'/index.php?map='.$obj[$name].'">';
    			break;
    			case 'host':
    				$link = '<a href="'.$this->MAINCFG->getValue('paths', 'htmlcgi').'/status.cgi?host='.$obj[$name].'">';
    			break;
    			case 'service':
    				$link = '<a href="'.$this->MAINCFG->getValue('paths', 'htmlcgi').'/extinfo.cgi?type=2&host='.$obj[$name].'&service='.$obj['service_description'].'">';
    			break;
    			case 'hostgroup':
    				$link = '<a href="'.$this->MAINCFG->getValue('paths', 'htmlcgi').'/status.cgi?hostgroup='.$obj[$name].'&style=detail">';
    			break;
    			case 'servicegroup':
    				$link = '<a href="'.$this->MAINCFG->getValue('paths', 'htmlcgi').'/status.cgi?servicegroup='.$obj[$name].'&style=detail">';
    			break;
    		}
    	}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::createLink(): '.$link);
    	return $link;
	}
	
	/**
	 * Create a Comment-Textbox
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String	Array with HTML Code
	 * @author	Joerg Linge
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function textBox(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::textBox(&$obj)');
		$ret = Array();
		$ret[] = '<div class="'.$obj['class'].'" style="background:'.$obj['background_color'].';left:'.$obj['x'].'px;top:'.$obj['y'].'px;width:'.$obj['w'].'px;overflow:visible;">';	
		$ret[] = "\t".'<span>'.$obj['text'].'</span>';
		$ret[] = '</div>';
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::textBox(): Array(...)');
		return $ret;	
	}
	
	/**
	 * Creates a hover box for objects
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String	Code for the hover box
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getHoverMenu(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::getHoverMenu(&$obj)');
		$ret = '';
		// FIXME 1.1: check if this is an object, where a menu should be displayed
		if(1) {
			$ret .= 'onmouseover="return overlib(\'';
			if($obj['hover_url']) {
				$ret .= $this->readHoverUrl($obj);
			} else {
				$ret .= $this->createInfoBox($obj);
			}
			
			$ret .= '\', CAPTION, \''.$this->LANG->getLabel($obj['type']).'\', SHADOW, WRAP, VAUTO);" onmouseout="return nd();" ';
			
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::getHoverMenu(): Array(...)');
			return $ret;
		}
	}
	
	/**
	 * Reads the given hover url form an object and forms it to a readable format for the hover box
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String	Code for the hover box
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function readHoverUrl(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::readHoverUrl(&$obj)');
		/* FIXME: Context is supported in php >= 5.0
		* $http_opts = array(
		*      'http'=>array(
		*      'method'=>"GET",
		*      'header'=>"Accept-language: en\r\n" .
		*                "Authorization: Basic ".base64_encode("user:pw"),
		*      'request_fulluri'=>true  ,
		*      'proxy'=>"tcp://proxy.url.de"
		*   )
		* );
		* $context = stream_context_create($http_opts);
		* $content = file_get_contents($obj['hover_url'],FALSE,$context);
		*/
		if(!$content = @file_get_contents($obj['hover_url'])) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'nagvis:global'));
	        $FRONTEND->messageToUser('WARNING','couldNotGetHoverUrl','URL~'.$obj['hover_url']);
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::readHoverUrl(): HTML');
		return str_replace('"','\\\'',str_replace('\'','\\\'',str_replace("\t",'',str_replace("\n",'',str_replace("\r\n",'',$content)))));
	}
	
	/**
	 * Creates a JavaScript hover menu
	 *
	 * @param	Array	$obj	Array with object informations
	 * @return	String	Code for the Hover-Box
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function createInfoBox(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::createInfoBox(&$obj)');
		$ret = '';
		
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		if(!isset($obj['stateCount'])) {
			$obj['stateCount'] = 0;
		}
		
		if(!isset($obj['stateHost'])) {
			$obj['stateHost'] = '';
		}
		
		$Count = $obj['stateCount'];
		$obj['stateCount'] = str_replace('"','',$obj['stateCount']);
		$obj['stateCount'] = str_replace("'",'',$obj['stateCount']);
		$ServiceHostState = $obj['stateHost'];
		
		//FIXME 1.1: mehr Output (ackComment, mehr Zahlen etc.)
		switch($obj['type']) {
			case 'host':
				$ret .= '<b>'.$this->LANG->getLabel('hostname').':</b> '.$obj[$name].'<br>';
				$ret .= '<b>'.$this->LANG->getLabel('state').':</b> '.$obj['state'].'<br>';
				$ret .= '<b>'.$this->LANG->getLabel('output').':</b> '.strtr(addslashes($obj['stateOutput']), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
			break;
			case 'service':
				$ret .= '<b>'.$this->LANG->getLabel('hostname').':</b> '.$obj[$name].'<br>';
				$ret .= '<b>'.$this->LANG->getLabel('servicename').':</b> '.$obj['service_description'].'<br>';
				$ret .= '<b>'.$this->LANG->getLabel('state').':</b> '.$obj['state'].'<br>';
				$ret .= '<b>'.$this->LANG->getLabel('output').':</b> '.strtr(addslashes($obj['stateOutput']), array("\r" => '<br>', "\n" => '<br>')).'<br>';
			break;
			case 'hostgroup':
				$ret .= '<b>'.$this->LANG->getLabel('hostgroupname').':</b> '.$obj[$name].'<br>';
				$ret .= '<b>'.$this->LANG->getLabel('state').':</b> '.$obj['state'].'<br>';
				$ret .= '<b>'.$this->LANG->getLabel('output').':</b> '.strtr(addslashes($obj['stateOutput']), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
			break;
			case 'servicegroup':
				$ret .= '<b>'.$this->LANG->getLabel('servicegroupname').':</b> '.$obj[$name].'<br>';
				$ret .= '<b>'.$this->LANG->getLabel('state').':</b> '.$obj['state'].'<br>';
				$ret .= '<b>'.$this->LANG->getLabel('output').':</b> '.strtr(addslashes($obj['stateOutput']), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
			break;
			case 'map':
				$ret .= '<b>'.$this->LANG->getLabel('mapname').':</b> '.$obj[$name].'<br>';
				$ret .= '<b>'.$this->LANG->getLabel('state').':</b> '.strtr(addslashes($obj['state']), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
				$ret .= '<b>'.$this->LANG->getLabel('output').':</b> '.strtr(addslashes($obj['stateOutput']), array("\r" => '<br>', "\n" => '<br>')).'<br>'; 
			break;
			default:
				// Unknown type, don't display anything
			break;
		}
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::createInfoBox(): Array(...)');
		return $ret;
	}
	
	/**
	 * Gets the summary state of all objects on the map
	 *
	 * @param	Array	$arr	Array with states
	 * @return	String	Summary state of the map
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getMapState(&$arr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::getMapState(Array(...))');
		$ret = Array();
		foreach($arr AS $obj) {
			$ret[] = $obj['state'];
		}
		
		$sRet = $this->wrapState($ret);
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::getMapState(): '.$sRet);
		return $sRet;
	}
	
	/**
	 * Gets the state of an object
	 *
	 * @param	Array	$obj	Array with object properties
	 * @return	Array	Array with state of the object
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getState(&$obj) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::getState(&$obj)');
		$state = Array('State'=>'','Output'=>'');
		if($obj['type'] == 'service') {
			$name = 'host_name';
		} else {
			$name = $obj['type'] . '_name';
		}
		
		switch($obj['type']) {
			case 'map':
				// save mapName in linkedMaps array
				$this->linkedMaps[] = $this->MAPCFG->getName();
				
				$SUBMAPCFG = new NagVisMapCfg($this->MAINCFG,$obj[$name]);
				$SUBMAPCFG->readMapConfig();
				$SUBMAP = new NagVisMap($this->MAINCFG,$SUBMAPCFG,$this->LANG,$this->BACKEND);
				$SUBMAP->linkedMaps = $this->linkedMaps;
				
				if($this->checkPermissions($SUBMAPCFG->getValue('global',0, 'allowed_user'),FALSE)) {
					// prevent loops in recursion
					if(in_array($SUBMAPCFG->getName(),$this->linkedMaps)) {
		                $FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'global:global'));
			            $FRONTEND->messageToUser('WARNING','loopInMapRecursion');
						
						$state = Array('State' => 'UNKNOWN','Output' => $FRONTEND->LANG->getMessageText('loopInMapRecursion'));
					} else {
						$state = $SUBMAP->getMapState($SUBMAP->getMapObjects(1,1));
						$state = Array('State' => $state,'Output'=>'State of child map is '.$state);
					}
				} else {
					$state = Array('State' => 'UNKNOWN','Output'=>'Error: You\'re not permited to view the state of this map.');
				}
			break;
			case 'textbox':
				// Check if set a hostname
				if(isset($obj['host_name'])) {
					if($this->BACKEND->checkBackendInitialized($obj['backend_id'],TRUE)) {
						$state = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$obj['host_name'],$obj['recognize_services'],'',$obj['only_hard_states']);
					}
				}
			break;
			default:
				if(isset($obj['line_type']) && $obj['line_type'] == '20') {
					// line with 2 states...
					list($objNameFrom,$objNameTo) = explode(',', $obj[$name]);
					list($serviceDescriptionFrom,$serviceDescriptionTo) = explode(',', $obj['service_description']);
					
					if($this->BACKEND->checkBackendInitialized($obj['backend_id'],TRUE)) {
						$state1 = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$objNameFrom,$obj['recognize_services'],$serviceDescriptionFrom,$obj['only_hard_states']);
						$state2 = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$objNameTo,$obj['recognize_services'],$serviceDescriptionTo,$obj['only_hard_states']);
					}
					$state = Array('State' => $this->wrapState(Array($state1['State'],$state2['State'])),'Output' => 'State1: '.$state1['Output'].'<br />State2:'.$state2['Output']);
				} else {
					if(!isset($obj['service_description'])) {
						$obj['service_description'] = '';
					}
					if(!isset($obj['recognize_services'])) {
						$obj['recognize_services'] = '';	
					}
					
					if($this->BACKEND->checkBackendInitialized($obj['backend_id'],TRUE)) {
						$state = $this->BACKEND->BACKENDS[$obj['backend_id']]->checkStates($obj['type'],$obj[$name],$obj['recognize_services'],$obj['service_description'],$obj['only_hard_states']);
					}
				}
			break;	
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::getState(): Array()');
		return Array('state' => $state['State'],'stateOutput' => $state['Output']);
	}
	
	/**
	 * Gets all objects of the map
	 *
	 * @param	Boolean	$getState	With state?
	 * @return	Array	Array of Objects of this map
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getMapObjects($getState=1,$mergeWithGlobals=1) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getMapObjects('.$getState.','.$mergeWithGlobals.')');
		$objects = Array();
		
		$objects = array_merge($objects,$this->getObjectsOfType('map',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('host',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('service',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('hostgroup',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('servicegroup',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('textbox',$getState,$mergeWithGlobals));
		$objects = array_merge($objects,$this->getObjectsOfType('shape',0,$mergeWithGlobals));
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getMapObjects(): Array(...)');
		return $objects;
	}
	
	/**
	 * Gets all objects of the defined type from a map and return an array with states
	 *
	 * @param	String	$type		Type of objects
	 * @param	Boolean	$getState	With state?
	 * @return	Array	Array of Objects of this type on the map
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
     */
	function getObjectsOfType($type,$getState=1,$mergeWithGlobals=1) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::getObjectsOfType('.$type.','.$getState.','.$mergeWithGlobals.')');
		// object array
		$objects = Array();
		
		// Default object state
		if($type == 'host' || $type == 'hostgroup') {
			$objState = Array('state'=>'UP','stateOutput'=>'Default State');
		} else {
			$objState = Array('state'=>'OK','stateOutput'=>'Default State');
		}
		
		if(is_array($objs = $this->MAPCFG->getDefinitions($type))){
			foreach($objs AS $index => $obj) {
				if (DEBUG&&DEBUGLEVEL&2) debug('Start object of type: '.$type);
				// workaround
				$obj['id'] = $index;
				
				if($mergeWithGlobals) {
					// merge with "global" settings
					foreach($this->MAPCFG->validConfig[$type] AS $key => $values) {
						if((!isset($obj[$key]) || $obj[$key] == '') && isset($values['default'])) {
							$obj[$key] = $values['default'];
						}
					}
				}
				
				// add default state to the object
				$obj = array_merge($obj,$objState);
				
				if($getState) {
					$obj = array_merge($obj,$this->getState($obj));
				}
				
				if($obj['type'] != 'textbox' && $obj['type'] != 'shape') {
					$obj['icon'] = $this->getIcon($obj);
				}
				
				// add object to array of objects
				$objects[] = $obj;
				if (DEBUG&&DEBUGLEVEL&2) debug('End object of type: '.$type);
			}
			
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::getObjectsOfType(): Array(...)');
			return $objects;
		}
	}
	
	/**
	 * Wraps all states in an Array to a summary state
	 *
	 * @param	Array	Array with objects states
	 * @return	String	Object state (DOWN|CRITICAL|WARNING|UNKNOWN|ERROR)
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function wrapState(&$objStates) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method GlobalMap::wrapState(Array(...))');
		if(in_array('DOWN', $objStates) || in_array('CRITICAL', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): CRITICAL');
			return 'CRITICAL';
		} elseif(in_array('WARNING', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): WARNING');
			return 'WARNING';
		} elseif(in_array('UNKNOWN', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): UNKNOWN');
			return 'UNKNOWN';
		} elseif(in_array('ERROR', $objStates)) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): ERROR');
			return 'ERROR';
		} else {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method GlobalMap::wrapState(): OK');
			return 'OK';
		}
	}
}
