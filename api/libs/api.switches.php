<?php

////////////////////// switch models managment

function web_SwitchModelAddForm() {
    $addinputs=wf_TextInput('newsm', 'Model', '', true);
    $addinputs.=wf_TextInput('newsmp', 'Ports', '', true,'5');
    $addinputs.=web_add_icon().' '.wf_Submit('Create');
    $addform=wf_Form('', 'POST', $addinputs, 'glamour');
    $result=$addform;
    return ($result);
}

function web_SwitchModelsShow() {
	$query='SELECT * from `switchmodels`';
	$allmodels=simple_queryall($query);
                
        $tablecells=wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('Model'));
        $tablecells.=wf_TableCell(__('Ports'));
        $tablecells.=wf_TableCell(__('Actions'));
        $tablerows=wf_TableRow($tablecells, 'row1');
        
        
	if (!empty($allmodels)) {
	foreach ($allmodels as $io=>$eachmodel) {
            
        $tablecells=wf_TableCell($eachmodel['id']);
        $tablecells.=wf_TableCell($eachmodel['modelname']);
        $tablecells.=wf_TableCell($eachmodel['ports']);
        $switchmodelcontrols=wf_JSAlert('?module=switchmodels&deletesm='.$eachmodel['id'], web_delete_icon(), 'Are you serious');
        $switchmodelcontrols.=wf_Link('?module=switchmodels&edit='.$eachmodel['id'], web_edit_icon());
        $tablecells.=wf_TableCell($switchmodelcontrols);
        $tablerows.=wf_TableRow($tablecells, 'row3');
 	   }
	}
        
    $result=wf_TableBody($tablerows, '100%', '0', 'sortable');




return ($result);
}

function zb_SwitchModelsGetAll() {
    $query="SELECT * from `switchmodels`";
    $result=simple_queryall($query);
    return ($result);
}

function zb_SwitchModelGetData($modelid) {
    $modelid=vf($modelid,3);
    $query="SELECT * from `switchmodels` where `id`='".$modelid."'";
    $result=simple_query($query);
    return ($result);
}

function web_SwitchSelector($name='switchid') {
    $allswitches=zb_SwitchesGetAll();
    $selector='<select name="'.$name.'">';
    if (!empty ($allswitches)) {
        foreach ($allswitches as $io=>$eachswitch) {
            $selector.='<option value="'.$eachswitch['id'].'">'.$eachswitch['location'].'</option>';
            }
    }
    $selector.='</select>';    
    return ($selector);
}


function web_SwitchModelSelector($selectname='switchmodelid') {
    $allmodels=zb_SwitchModelsGetAll();
    $selector='<select name="'.$selectname.'">';
            if (!empty ($allmodels)) {
                foreach ($allmodels as $io=>$eachmodel) {
                    $selector.='<option value="'.$eachmodel['id'].'">'.$eachmodel['modelname'].'</option>';
                }
            }        
    $selector.='</select>';
    return ($selector);
}

function ub_SwitchModelAdd($name,$ports) {
    $ports=vf($ports);
    $name=mysql_real_escape_string($name);
	$query='
	INSERT INTO `switchmodels` (
                `id` ,
                `modelname` ,
                `ports`
                )
                VALUES (
                NULL , "'.$name.'", "'.$ports.'");';
	nr_query($query);
	stg_putlogevent('SWITCHMODEL ADD '.$name);
}

function ub_SwitchModelDelete($modelid) {
        $modelid=vf($modelid);
        $query='DELETE FROM `switchmodels` WHERE `id` = "'.$modelid.'"';
	nr_query($query);
	stg_putlogevent('SWITCHMODEL DELETE  '.$modelid);
	}

function web_SwitchFormAdd() {
    $addinputs=wf_TextInput('newip', 'IP', '', true);
    $addinputs.=wf_TextInput('newlocation', 'Location', '', true);
    $addinputs.=wf_TextInput('newdesc', 'Description', '', true);
    $addinputs.=wf_TextInput('newsnmp', 'SNMP community', '', true);
    $addinputs.=wf_TextInput('newgeo', 'Geo location', '', true);
    $addinputs.=web_SwitchModelSelector('newswitchmodel').' '.__('Model');
    $addinputs.='<br>';
    $addinputs.=web_add_icon().' '.wf_Submit('Save');
    $addform=wf_Form("", 'POST', $addinputs, 'glamour');
    return($addform);
}

function web_SwitchFormDelete() {
$delform='
    <form action="" METHOD="POST" class="row3">
	'.web_delete_icon().' '.web_SwitchSelector('switchdelete').'
	 <input type="submit" value="'.__('Delete').'">
	</form>';
    return($delform);
}


function zb_SwitchesGetAll() {
    	$query='SELECT * FROM `switches` ORDER BY `id` DESC';
	$allswitches=simple_queryall($query);
        return ($allswitches);
}

function zb_SwitchGetData($switchid) {
        $switchid=vf($switchid,3);
    	$query="SELECT * FROM `switches` WHERE `id`='".$switchid."' ";
	$result=simple_query($query);
        return ($result);
}


function zb_SwitchModelsGetAllTag() {
    $allmodels=zb_SwitchModelsGetAll();
    $result=array();
    if (!empty ($allmodels)) {
        foreach ($allmodels as $io=>$eachmodel) {
            $result[$eachmodel['id']]=$eachmodel['modelname'];
        }
    }
    return ($result);
}

function zb_PingICMP($ip) {
    $globconf=parse_ini_file(CONFIG_PATH."billing.ini");
    $ping=$globconf['PING'];
    $sudo=$globconf['SUDO'];
    $ping_command=$sudo.' '.$ping.' -i 0.01 -c 1 '.$ip;
    $ping_result=shell_exec($ping_command);
    if (strpos($ping_result, 'ttl')) {
        return (true);
    } else {
        return(false);
    }
}

function zb_SwitchAlive($ip) {
    if (zb_PingICMP($ip)) {
        $result=web_green_led();
    } else {
        $result=web_red_led();
    }
    return ($result);
}


function zb_SwitchesRepingAll() {
    $allswitches=zb_SwitchesGetAll();
    $deadswitches=array();
    
    if (!empty($allswitches)) {
        foreach ($allswitches as $io=>$eachswitch) {
            
              if (!ispos($eachswitch['desc'], 'NP')) {
                    if (!zb_PingICMP($eachswitch['ip'])) {
                    $deadswitches[$eachswitch['ip']]=$eachswitch['location'];
                    }
                } 
        }
    }
    
    $newdata=serialize($deadswitches);
    zb_StorageSet('SWDEAD', $newdata);
    
}
    
        
function web_SwitchesShow() {
    $alterconf=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
        $allswitches=zb_SwitchesGetAll();
        $modelnames=zb_SwitchModelsGetAllTag();
        $currenttime=time();
        $reping_timeout=$alterconf['SW_PINGTIMEOUT'];
        
        //non realtime switches pinging
        $last_pingtime=zb_StorageGet('SWPINGTIME');
        
        if (!$last_pingtime) {
            zb_SwitchesRepingAll();
            zb_StorageSet('SWPINGTIME', $currenttime);
            $last_pingtime=$currenttime;
        } else {
            if ($currenttime>($last_pingtime+($reping_timeout*60))) {
            // normal timeout reping sub here
            zb_SwitchesRepingAll();
            zb_StorageSet('SWPINGTIME', $currenttime);
            }
        }
        
        //force total reping and update cache
        if (wf_CheckGet(array('forcereping'))) {
            zb_SwitchesRepingAll();
            zb_StorageSet('SWPINGTIME', $currenttime);
        }
        
        //load dead switches cache
        $dead_switches_raw=  zb_StorageGet('SWDEAD');
        if (!$dead_switches_raw) {
            $dead_switches=array();
        } else {
            $dead_switches=  unserialize($dead_switches_raw);
        }
        
			
        $tablecells=wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('IP'));
        $tablecells.=wf_TableCell(__('Location'));
        $tablecells.=wf_TableCell(__('Active'));
        $tablecells.=wf_TableCell(__('Model'));
        $tablecells.=wf_TableCell(__('SNMP community'));
        $tablecells.=wf_TableCell(__('Geo location'));
        $tablecells.=wf_TableCell(__('Description'));
        $tablecells.=wf_TableCell(__('Actions'));
        $tablerows=wf_TableRow($tablecells, 'row1');
        
	if (!empty($allswitches)) {
            foreach ($allswitches as $io=>$eachswitch) {
                //check switch alive state
// realtime switch check deprecated in release 0.3.0                
//                if (!ispos($eachswitch['desc'], 'NP')) {
//                if (zb_PingICMP($eachswitch['ip'])) {
//                $aliveled=web_green_led();
//                $aliveflag='1';
//                } else {
//                $aliveled=web_red_led();
//                $aliveflag='0';
//                }
//                } else {
//                // if switch have NP flag
//                $aliveled=web_green_led();
//                $aliveflag='1';
//                }
                if (isset($dead_switches[$eachswitch['ip']])) {
                  $aliveled=web_red_led();
                  $aliveflag='0';  
                } else {
                  $aliveled=  web_green_led();
                  $aliveflag='1';
                }
                
    
                $tablecells=wf_TableCell($eachswitch['id']);
                $tablecells.=wf_TableCell($eachswitch['ip'], '', '', 'sorttable_customkey="'.ip2int($eachswitch['ip']).'"');
                $tablecells.=wf_TableCell($eachswitch['location']);
                $tablecells.=wf_TableCell($aliveled, '', '', 'sorttable_customkey="'.$aliveflag.'"');
                $tablecells.=wf_TableCell(@$modelnames[$eachswitch['modelid']]);
                $tablecells.=wf_TableCell($eachswitch['snmp']);
                $tablecells.=wf_TableCell($eachswitch['geo']);
                $tablecells.=wf_TableCell($eachswitch['desc']);
                $switchcontrols=wf_JSAlert('?module=switches&switchdelete='.$eachswitch['id'], web_delete_icon(), 'Are you serious');
                $switchcontrols.=wf_Link('?module=switches&edit='.$eachswitch['id'], web_edit_icon());
                $tablecells.=wf_TableCell($switchcontrols);
                $tablerows.=wf_TableRow($tablecells, 'row3');
                
            }
	 
	}
	$result=wf_TableBody($tablerows, '100%', '0', 'sortable');
	return ($result);
}


function ub_SwitchAdd($modelid,$ip,$desc,$location,$snmp,$geo) {
    $modelid=vf($modelid);
    $ip=mysql_real_escape_string($ip);
    $desc=mysql_real_escape_string($desc);
    $location=mysql_real_escape_string($location);
    $snmp=mysql_real_escape_string($snmp);
	$query="
            INSERT INTO `switches` (
            `id` ,
            `modelid` ,
            `ip` ,
            `desc` ,
            `location` ,
             `snmp`,
             `geo`
            )
            VALUES (
                '', '".$modelid."', '".$ip."', '".$desc."', '".$location."', '".$snmp."','".$geo."'
                );
            ";
	nr_query($query);
        $lastid=  simple_get_lastid('switches');
	stg_putlogevent('SWITCH ADD ['.$lastid.'] IP '.$ip.' ON LOC '.$location);
	show_window(__('Add switch'),__('Was added new switch').' '.$ip.' '.$location);
}



function ub_SwitchDelete($switchid) {
    $switchid=vf($switchid);
    $switchdata=zb_SwitchGetData($switchid);
    $query="DELETE from `switches` WHERE `id`='".$switchid."'";
    nr_query($query);
    log_register('SWITCH DELETE ['.$switchid.'] IP '.$switchdata['ip'].' LOC '.$switchdata['location']);
}

?>
