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
	$result = $Controller->choice_attack();
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
		$this->TRules['troopSent'] = 1;
		$this->TRules['multiplier_defprod'] = 10;
		$this->TRules['multiplier_defcyborgs'] = 5;
		$this->TRules['multiplier_distance'] = 5;
		$this->TRules['min_cyborgs'] = 5;
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
			if($entityType == 'FACTORY') {
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

	function choice_attack() {
		$this->mapping();
		$ret = '';
		for($i=1;$i<=1;$i++){
			if($this->TData['nbcyborgs']['total'] <= 1) {
				$res = 'WAIT';
			}else{
				$source     = $this->getSourceFactory();
				$nbcyborgs  = $this->TData['FACTORY'][1][$source][2] - 1;
				if(isset($source) && $nbcyborgs >= $this->TRules['min_cyborgs']) {
					$TInstruction   = $this->getAtkInstruction($source,$nbcyborgs);
					if(isset($source) && isset($TInstruction['dest']) && isset($TInstruction['cyborgs'])) {
						$destination    = $TInstruction['dest'];
						$nbcyborgs      = $TInstruction['cyborgs'];
						$this->TData['nbcyborgs']['total'] -= $nbcyborgs;
						$this->TData['nbcyborgs'][$source] -= $nbcyborgs;
						$this->TData['FACTORY'][1][$source][2] -= $nbcyborgs;
						$res = 'MOVE '.$source.' '.$destination.' '.$nbcyborgs;
					}else{
						$res = 'WAIT';
					}
				} else {
					$res = 'WAIT';
				}
			}
			$ret.= $res.';';
		}
		$ret = substr($ret,0,-1);
		return $ret;
	}

	function getAtkInstruction($source, $nbcyborgs) {
		$TFactory           = $this->TData['FACTORY'][1];
		$TFactoryMap        = $this->TMap[$source];
		$TRes               = array();

		foreach($TFactoryMap as $factory => $distance) {
			if($factory != $source) {
				if($this->nbCH > 0) {
					$TTemp = $this->getDestFor(0, $source, $nbcyborgs, $factory);
					//error_log(var_export('Round : '.$this->round.' || SUISSE dispo '.$this->nbCH,true));
				}else{
					$TTemp = $this->getDestFor(-1, $source, $nbcyborgs, $factory);
					//error_log(var_export('Round : '.$this->round.' || ATK dispo '.$this->nbVS,true));
					//error_log(var_export($source,true));
					//error_log(var_export($TTemp,true));
				}

				if(empty($TTemp)) {
					$TRes = $TRes;
				}else if(empty($TRes)) {
					$TRes = $TTemp;
				}else if($TTemp['priority'] > $TRes['priority']) {
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
		$troups_on  = $this->calcTroopsSent(1, $factory);
		if(!empty($this->TData['FACTORY'][$typeEntity][$factory])) {
			$def_cyborgs        = $this->TData['FACTORY'][$typeEntity][$factory][2];
			$def_prod           = $this->TData['FACTORY'][$typeEntity][$factory][3];
			$def_length         = $this->TMap[$source][$factory];
			$priority_test      = 0;
			if($typeEntity == 0) {
				// Cas SUISSE
				$cyborgs_needed = $def_cyborgs + $def_prod * $def_length;
				$priority_test -= ($def_cyborgs * $this->TRules['multiplier_defcyborgs']);
				$priority_test += ($def_prod * $this->TRules['multiplier_defprod']);
			}else{
				// Cas VS
				$cyborgs_needed = $def_cyborgs + $def_prod * ($def_length + 2);
				$priority_test -= $def_cyborgs * $this->TRules['multiplier_defcyborgs'];
				$priority_test += $def_prod * $this->TRules['multiplier_defprod'];
				$priority_test -= $def_length * $this->TRules['multiplier_distance'];
			}
			$cyborgs_needed += 1;

			if($nbcyborgs >= $cyborgs_needed && $troups_on < $cyborgs_needed ) {
				$TRes['dest']        = $factory;
				$TRes['cyborgs']     = $cyborgs_needed;
				$TRes['priority']    = $priority_test;
				//error_log(var_export($TRes,true));
			}
		}
		return $TRes;
	}


	function calcTroopsSent($typeEntity, $dest) {
		$troups_on = 0;
		if(!empty($this->TData['TROOP'][$typeEntity])) {
			$TTroops = $this->TData['TROOP'][$typeEntity];
			foreach($TTroops as $troup){
				if($troup[3] == $dest) {
					$troups_on += $troup[4];
				}
			}
		}
		// Règle d'envoi
		if($typeEntity == 1)
			$troups_on *= $this->TRules['troopSent'];
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