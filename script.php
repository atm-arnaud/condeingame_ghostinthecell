<?php
/**
 * Auto-generated code below aims at helping you parse
* the standard input according to the problem statement.
**/

// Instanciations
$TMap = array();


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

$Controller = new CodeInGameController();
$Controller->setTMap($TMap);

$j = 1;
// game loop
while (TRUE)
{
	// Write an action using echo(). DON'T FORGET THE TRAILING \n
	// To debug (equivalent to var_dump): error_log(var_export($var, true));
	$result = '';
	$Controller->setRound($j);
	$result = $Controller->result_move();
	// Any valid action, such as "WAIT" or "MOVE source destination cyborgs"
	echo $result."\n";
	$j++;
}


class CodeInGameController
{
	private $TMap;
	private $TData;
	private $TRules;
	private $round;
	private $nbCH;
	private $nbVS;
	private $nbMY;

	public $output;

	function __construct() {
		$this->TData = array();
		$this->TMap = array();
		// Définition des règles
		$this->TRules['troopSent'] = 1.2;
		$this->TRules['multiplier_defprod'] = 5;
		$this->TRules['multiplier_defcyborgs'] = 1;
		$this->TRules['multiplier_distance'] = 3;
		$this->TRules['min_cyborgs'] = 1;
		$this->TRules['min_instructions'] = 4;
	}

	function setTData($TData) {
		$this->TData = $TData;
	}

	function setTMap($TMap) {
		$this->TMap = $TMap;
	}

	function setRound($round) {
		$this->round = $round;
	}

	function mapping() {
		unset($this->TData);
		$this->TData["nbcyborgs"]['total'] = 0;
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
			$this->TData[$entityType][$arg1][$entityId] = array(2=>$arg2,$arg3,$arg4,$arg5);
			if($entityType == 'FACTORY' && $arg1 == 1) {
				$this->TData['nbcyborgs']['total'] += $arg2;
				$this->TData['nbcyborgs'][$entityId] = $arg2;
			}
		}
		$this->nbCH = $this->getNbFactoryFor(0);
		$this->nbVS = $this->getNbFactoryFor(-1);
		$this->nbMY = $this->getNbFactoryFor(1);
	}

	function getSourceFactory() {
		$TFactory   = $this->TData['FACTORY'][1];
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

	function result_move() {
		$this->mapping();
		$TRes = array();
		if($this->TData['nbcyborgs']['total'] > 0) {
			if($this->nbCH == 0) {
				$TRes[] = $this->choice_attack();
				error_log(var_export('ATK', true));
			}else {
				$TRes[] = $this->choice_colon();
				error_log(var_export('COL', true));
			}
			if($this->nbMY >= 2) {
				$TRes[] = $this->choice_defense();
				error_log(var_export('DEF', true));
			}
			if($this->round == 1 || $this->round == 60) {
				$TRes[] = $this->choice_bomb();
				error_log(var_export('BOMB', true));
			}
			while(sizeof($TRes) < $this->TRules['min_instructions']) {
				$TRes[] = $this->choice_attack();
				error_log(var_export('WHILE ATK', true));
			}
			$res = implode(';',$TRes);
		}else{
			$res = 'WAIT';
		}
		return $res;
	}


	/*
	 * Attack Part
	 */

	function choice_attack() {
		$source     = $this->getSourceFactory();
		$nbcyborgs  = $this->TData['FACTORY'][1][$source][2] - 1;
		if(isset($source) && $nbcyborgs >= $this->TRules['min_cyborgs']) {
			$TInstruction   = $this->getMoveInstruction(-1,$source,$nbcyborgs);
			if(isset($source) && isset($TInstruction['dest']) && isset($TInstruction['cyborgs'])) {
				$destination    = $TInstruction['dest'];
				$typeEntity     = $TInstruction['dest_type'];
				$nbcyborgs      = $TInstruction['cyborgs'];
				$distance       = $TInstruction['distance'];
				$this->updateMove($source,$nbcyborgs,$destination,$typeEntity,$distance);
				$res = 'MOVE '.$source.' '.$destination.' '.$nbcyborgs;
			}else{
				$res = 'WAIT';
			}
		} else {
			$res = 'WAIT';
		}
		return $res;
	}


	/*
	 * Colonisation Part
	 */

	function choice_colon() {
		$source     = $this->getSourceFactory();
		$nbcyborgs  = $this->TData['FACTORY'][1][$source][2] - 1;
		if(isset($source) && $nbcyborgs >= $this->TRules['min_cyborgs']) {
			$TInstruction   = $this->getMoveInstruction(0,$source,$nbcyborgs);
			if(isset($source) && isset($TInstruction['dest']) && isset($TInstruction['cyborgs'])) {
				$destination    = $TInstruction['dest'];
				$typeEntity     = $TInstruction['dest_type'];
				$nbcyborgs      = $TInstruction['cyborgs'];
				$distance       = $TInstruction['distance'];
				$this->updateMove($source,$nbcyborgs,$destination,$typeEntity,$distance);
				$res = 'MOVE '.$source.' '.$destination.' '.$nbcyborgs;
			}else{
				$res = 'WAIT';
			}
		} else {
			$res = 'WAIT';
		}
		return $res;
	}

	/*
	 * Defense Part
	 */

	function choice_defense() {
		$source     = $this->getSourceFactory();
		$nbcyborgs  = $this->TData['FACTORY'][1][$source][2] - 1;
		if(isset($source) && $nbcyborgs >= $this->TRules['min_cyborgs']) {
			$TInstruction   = $this->getMoveInstruction(1,$source,$nbcyborgs);
			if(isset($source) && isset($TInstruction['dest']) && isset($TInstruction['cyborgs'])) {
				$destination    = $TInstruction['dest'];
				$typeEntity     = $TInstruction['dest_type'];
				$nbcyborgs      = $TInstruction['cyborgs'];
				$distance       = $TInstruction['distance'];
				$this->updateMove($source,$nbcyborgs,$destination,$typeEntity,$distance);
				$res = 'MOVE '.$source.' '.$destination.' '.$nbcyborgs;
			}else{
				$res = 'WAIT';
			}
		} else {
			$res = 'WAIT';
		}
		return $res;
	}


	/*
	 * Bomb Part
	 */

	function choice_bomb() {
		$res = 'WAIT';
		return $res;
	}


	/*
	 * General Part
	 */


	function getMoveInstruction($typeEntity, $source, $nbcyborgs) {
		$TFactory           = $this->TData['FACTORY'][1];
		$TFactoryMap        = $this->TMap[$source];
		$TRes               = array();

		foreach($TFactoryMap as $factory => $distance) {
			if(!empty($this->TData['FACTORY'][$typeEntity][$factory])) {
				$TTemp = $this->getDestFor($typeEntity, $source, $nbcyborgs, $factory); // Get Data

				// Choose priority
				if(empty($TTemp)) {
					// No choice found
				}else if(empty($TRes)) {
					// First case
					$TRes = $TTemp;
				}else if($TTemp['priority'] > $TRes['priority']) {
					// Priority > ex Priority
					$TRes = $TTemp;
				}
			}
		}
		//error_log(var_export('Result : ',true));
		//error_log(var_export($TTemp,true));
		return $TRes;
	}

	function getDestFor($typeEntity, $source, $nbcyborgs, $factory) {
		$TRes = array();
		if(!empty($this->TData['FACTORY'][$typeEntity][$factory])) {
			$def_cyborgs        = $this->TData['FACTORY'][$typeEntity][$factory][2];
			$inc_cyborgs		= $this->calcTroopsOnTheRoad($factory); // Add moves comming
			$def_prod           = $this->TData['FACTORY'][$typeEntity][$factory][3];
			$def_length         = $this->TMap[$source][$factory];
			$priority_test      = 0;
			if($typeEntity == 1){
				// Cas MY
				$cyborgs_needed = (-$def_cyborgs + $inc_cyborgs);
				$priority_test 	= (($def_prod * $this->TRules['multiplier_defprod'])/$cyborgs_needed);
			}else if($typeEntity == 0){
				// Cas SUISSE
				$cyborgs_needed = ($def_cyborgs + -$inc_cyborgs);
				$priority_test -= ($cyborgs_needed * $this->TRules['multiplier_defcyborgs']);
				$priority_test += ($def_prod * $this->TRules['multiplier_defprod']);
			}else{
				// Cas VS
				$cyborgs_needed = ($def_cyborgs + $inc_cyborgs + $def_prod * ($def_length + 1));
				$priority_test -= ($cyborgs_needed * $this->TRules['multiplier_defcyborgs']);
				$priority_test += ($def_prod * $this->TRules['multiplier_defprod']);
				$priority_test -= ($def_length * $this->TRules['multiplier_distance']);
			}
			$cyborgs_needed += 1;

			if($nbcyborgs >= $cyborgs_needed && $cyborgs_needed > 1) {
				$TRes['dest']       = $factory;
				$TRes['cyborgs']    = $cyborgs_needed;
				$TRes['dest_type']  = $typeEntity;
				$TRes['distance']   = $def_length;
				$TRes['priority']   = $priority_test;
				//error_log(var_export($TRes,true));
			}
		}
		return $TRes;
	}

	function updateMove($source,$nbcyborgs,$dest,$distance) {
		$this->TData['nbcyborgs']['total'] -= $nbcyborgs;
		$this->TData['nbcyborgs'][$source] -= $nbcyborgs;
		$this->TData['FACTORY'][1][$source][2] -= $nbcyborgs;
		$troop = array(
				1=>1,
				2=>$source,
				3=>$dest,
				4=>$nbcyborgs,
				5=>$distance
		);

		$this->TData['TROOP'][1][] = $troop;
	}

	function calcTroopsOnTheRoad($dest) {
		$troups_on = 0;
		if(!empty($this->TData['TROOP'][-1])) {
			// Calc nb VS troops comming
			$TTroops = $this->TData['TROOP'][-1];
			foreach($TTroops as $troup){
				if($troup[3] == $dest) {
					$troups_on -= $troup[4];
				}
			}
		}
		if(!empty($this->TData['TROOP'][1])) {
			// Calc nb MY troops comming
			$TTroops = $this->TData['TROOP'][1];
			foreach($TTroops as $troup){
				if($troup[3] == $dest) {
					$troups_on += $troup[4];
				}
			}
		}
		return $troups_on;
	}


	function getNbFactoryFor($entityId) {
		if(!empty($this->TData['FACTORY'][$entityId])) {
			return sizeof($this->TData['FACTORY'][$entityId]);
		}else{
			return 0;
		}
	}
}

?>