<?php

/*
 * EconomyS, the massive economy plugin with many features for PocketMine-MP
 * Copyright (C) 2013-2016  onebone <jyc00410@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace onebone\economyapi\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use onebone\economyapi\EconomyAPI;
use onebone\economyapi\EconomyAPICommand;

class TopBalanceCommand extends EconomyAPICommand{
  private $plugin;

  public function __construct(EconomyAPI $plugin){
		parent::__construct("돈상위", "상위 플레이어의 돈 평균을 봅니다.", "/돈상위 [퍼센트(0~100)]", ["topbalance"]);
		$this->setPermission("economyapi.command.topbalance");

		$this->plugin = $plugin;
  }

  public function _execute(CommandSender $sender, string $label, array $args) : bool{
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(EconomyAPI::$prefix . "이 명령을 사용할 권한이 없습니다.");
			return true;
		}

    if(!isset($args[0]) || !is_numeric($args[0]) || $args[0] <= 0 || $args[0] > 100){
      $sender->sendMessage(EconomyAPI::$prefix . "사용법 : " . $this->getUsage());
      return true;
    }

    $count = count($this->plugin->getAllMoney());
    $check = max(0, min($count, ceil($count / 100 * $args[0])));

    $total = 0;
    for($i = 1; $i <= $check; $i++){
      $total += $this->plugin->myMoney($this->plugin->getPlayerByRank($i));
    }
    $sender->sendMessage("§l==========[ 상위 " . $args[0] . "퍼센트 (" . $check . "명) ]==========");
    $sender->sendMessage("§a§l* §r§f평균 : " . $this->plugin->koreanWonFormat(round($total / $check, 2)));
    $sender->sendMessage("§a§l* §r§f합계 : " . $this->plugin->koreanWonFormat($total));
    return true;
  }
}
