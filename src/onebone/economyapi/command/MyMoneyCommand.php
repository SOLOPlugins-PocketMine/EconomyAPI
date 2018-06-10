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
use pocketmine\utils\TextFormat;
use pocketmine\Player;

use onebone\economyapi\EconomyAPI;
use onebone\economyapi\EconomyAPICommand;

class MyMoneyCommand extends EconomyAPICommand{

	private $plugin;

	public function __construct(EconomyAPI $plugin){
		parent::__construct("내돈", "내 돈을 확인합니다.", "/내돈", ["mymoney", "돈"]);
		$this->setPermission("economyapi.command.mymoney");

		$this->plugin = $plugin;
	}

	public function _execute(CommandSender $sender, string $label, array $params) : bool{
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(EconomyAPI::$prefix . "이 명령을 사용할 권한이 없습니다.");
			return true;
		}

		if(!$sender instanceof Player){
			$sender->sendMessage(EconomyAPI::$prefix . "인게임에서만 사용 가능합니다.");
			return true;
		}

		$total = count($this->plugin->getAllMoney());
		$sender->sendMessage(EconomyAPI::$prefix . "돈 : " . $this->plugin->koreanWonFormat($this->plugin->myMoney($sender)));
		$sender->sendMessage(EconomyAPI::$prefix . "순위 : 전체 " . number_format($total) . "명중 " . number_format($this->plugin->getRank($sender)) . "위");
		return true;
	}
}
