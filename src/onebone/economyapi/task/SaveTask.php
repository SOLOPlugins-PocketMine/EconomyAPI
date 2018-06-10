<?php

namespace onebone\economyapi\task;

use onebone\economyapi\EconomyAPI;
use onebone\economyapi\EconomyAPITask;

class SaveTask extends EconomyAPITask {
	/**
	 * 
	 * @var EconomyAPI
	 */
	protected $owner;
	
	public function __construct(EconomyAPI $owner) {
		// parent::__construct($owner);
		$this->owner = $owner;
	}
	
	public function _onRun(int $currentTick) {
    	$this->owner->saveAll();
  	}
}
