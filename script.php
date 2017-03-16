<?php
/**
 * Auto-generated code below aims at helping you parse
* the standard input according to the problem statement.
**/

// Instanciations
$TMap = array();
$TData = array();
$TData["nbcyborgs"]['total'] = 0;

// Définition des règles
$TData['rules']['troopSent'] = 1.2;
$TData['rules']['multiplier_defprod'] = 4;
$TData['rules']['multiplier_defcyborgs'] = 1.2;


fscanf(STDIN, "%d",
		$factoryCount // the number of factories
		);
fscanf(STDIN, "%d",
		$linkCount // the number of links between factories
		);
for ($i = 0; $i < $linkCount; $i++)
{
	fscanf(STDIN, "%d %d %d",
			$factory1,
			$factory2,
			$distance
			);
	$TMap[$factory1][$factory2]=$distance;
	$TMap[$factory2][$factory1]=$distance;
}

// game loop
while (TRUE)
{
	// Write an action using echo(). DON'T FORGET THE TRAILING \n
	// To debug (equivalent to var_dump): error_log(var_export($var, true));
	$result = '';
	$i=1;
	$max=1;
	while($i<=$max) {
		$res = choice_attack($TMap, $TData);
		$result .= $res;
		if($i!=$max) $result.= ';';
		$i++;
	}
	// Any valid action, such as "WAIT" or "MOVE source destination cyborgs"
	echo $result."\n";
}


/**
 * Functions
 */

function choice_attack(&$TMap, &$TData) {
	mapping($TMap, $TData);

	if($TData['nbcyborgs']['total'] <= 1) {
		$res = 'WAIT';
	}else{
		$source     = getSourceFactory($TMap,$TData);
		$nbcyborgs  = $TData['FACTORY'][1][$source][2];
		if(isset($source)) {
			$TInstruction   = getAtkInstruction($TMap,$TData,$source,$nbcyborgs);
			$dest           = $TInstruction['dest'];
			$nbcyborgs      = $TInstruction['cyborgs'];
			if(isset($source) && isset($destination) && isset($nbcyborgs)) {
				$res = 'MOVE '.$source.' '.$destination.' '.$nbcyborgs;
			}else{
				$res = 'WAIT';
			}
		} else {
			$res = 'WAIT';
		}
	}
	return $res;
}

function mapping(&$TMap, &$TData) {
	fscanf(STDIN, "%d",
			$entityCount // the number of entities (e.g. factories and troops)
			);
	for ($i = 0; $i < $entityCount; $i++)
	{
		fscanf(STDIN, "%d %s %d %d %d %d %d",
				$entityId,
				$entityType,
				$arg1,
				$arg2,
				$arg3,
				$arg4,
				$arg5
				);
		$TData[$entityType][$arg1][$entityId] = array(2=>$arg2,$arg3,$arg4,$arg5);
		if($entityType == 'FACTORY') {
			$TData['nbcyborgs']['total'] += $arg2;
		}
	}
}

function getSourceFactory($TMap, $TData) {
	$TFactory   = $TData['FACTORY'][1];
	$source     = null;
	$max        = 0;

	foreach($TFactory as $idfactory => $factory) {
		$nbcyborgs = $factory[2];
		if($nbcyborgs > $max) {
			$source = $idfactory;
			$max = $nbcyborgs;
		}
	}
	return $source;
}

function getAtkInstruction($TMap, $TData, $source, $nbcyborgs) {
	$TFactory           = $TData['FACTORY'][1];
	$TFactoryMap        = $TMap[$source];
	$TRes               = array();
	$nb_ch              = getNbCHFactory($TData);

	foreach($TFactoryMap as $factory => $distance) {
		if($factory != $source) {
			if($nb_ch > 0) {
				$TTemp = getDestFor(0, $TData, $TMap, $source, $nbcyborgs, $factory);
				//error_log(var_export($TTemp,true));
			}else{
				$TTemp = getDestFor(-1, $TData, $TMap, $source, $nbcyborgs, $factory);
			}

			if(empty($TRes['priority'])) {
				$TRes = $TTemp;
			}
			else if($TTemp['priority'] > $TRes['priority']) {
				$TRes = $TTemp;
			}
		}
	}
	return $TRes;
}

function getDestFor($typeEntity, $TData, $TMap, $source, $nbcyborgs, $factory) {
	$TRes = array();
	$troups_on  = calcTroopsSent($TData, 1, $factory);
	if(!empty($TData['FACTORY'][$typeEntity][$factory])) {
		$def_cyborgs        = $TData['FACTORY'][$typeEntity][$factory][2];
		$def_prod           = $TData['FACTORY'][$typeEntity][$factory][3];
		$def_length         = $TMap[$source][$factory];
		$priority_test      = 0;
		if($typeEntity == 0) {
			// Cas SUISSE
			$cyborgs_needed = $def_cyborgs + $def_prod * $def_length;
			$priority_test -= ($def_cyborgs * $TData['rules']['multiplier_defcyborgs']);
			$priority_test += ($def_prod * $TData['rules']['multiplier_defprod']);
		}else{
			// Cas VS
			$cyborgs_needed = $def_cyborgs + $def_prod * ($def_length + 2);
			$priority_test -= ($def_cyborgs * $TData['rules']['multiplier_defcyborgs']);
			$priority_test += ($def_prod * $TData['rules']['multiplier_defprod']);
		}
		$cyborgs_needed += 1;

		if($nbcyborgs >= $cyborgs_needed && $troups_on < $cyborgs_needed ) {
			$TRes['dest']        = $factory;
			$TRes['cyborgs']     = $cyborgs_needed;
			$TRes['priority']    = $priority_test;
		}
	}
	return $TRes;
}


function calcTroopsSent($TData, $typeEntity, $dest) {
	$troups_on = 0;
	if(!empty($TData['TROOP'][$typeEntity])) {
		$TTroops = $TData['TROOP'][$typeEntity];
		foreach($TTroops as $troup){
			if($troup[3] == $dest) {
				$troups_on += $troup[4];
			}
		}
	}
	// Règle d'envoi
	if($typeEntity == 1)
		$troups_on *= $TData['rules']['troopSent'];
		return $troups_on;
}


function getNbCHFactory($TData) {
	if(!empty($TData['FACTORY'][0])) {
		return sizeof($TData['FACTORY'][0]);
	}else{
		return 0;
	}
}
?>