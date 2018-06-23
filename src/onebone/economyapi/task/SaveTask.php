<?php

namespace onebone\economyapi\task;

use pocketmine\scheduler\Task;
use onebone\economyapi\EconomyAPI;

class SaveTask extends Task {

	protected $owner;

	public function __construct(EconomyAPI $owner) {
		$this->owner = $owner;
	}

	public function onRun(int $currentTick) {
		$this->owner->saveAll();
	}
}
