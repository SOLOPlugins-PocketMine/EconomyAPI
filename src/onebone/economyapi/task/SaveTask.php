<?php

namespace onebone\economyapi\task;

use onebone\economyapi\EconomyAPI;
use onebone\economyapi\EconomyAPITask;

class SaveTask extends EconomyAPITask{

  public function __construct(EconomyAPI $owner){
    parent::__construct($owner);
  }

  public function _onRun(int $currentTick){
    $this->owner->saveAll();
  }
}
