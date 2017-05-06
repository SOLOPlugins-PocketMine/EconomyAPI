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
use onebone\economyapi\task\SortTask;

use solo\standardapi\message\Page;

class TopMoneyCommand extends Command{

	private $plugin;

	public function __construct(EconomyAPI $plugin){
		parent::__construct("돈순위", "돈 순위를 표시합니다.", "/돈순위 [페이지]", ["topmoney"]);
		$this->setPermission("economyapi.command.topmoney");

		$this->plugin = $plugin;
	}

	public function execute(CommandSender $sender, $label, array $params){
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(new Alert("이 명령을 사용할 권한이 없습니다."));
			return true;
		}
		
		$page = (int)array_shift($params);

		$max = count($this->plugin->getAllMoney());
		$maxPage = ceil($max / 5);
		$page = min($maxPage, $page);
		$page = max(1, $page);

		$server = $this->plugin->getServer();

		$except = [];
		foreach($server->getNameBans()->getEntries() as $entry){
			if($this->plugin->accountExists($entry->getName())){
				$except[$entry->getName()] = true;
			}
		}

		if(!$this->plugin->getConfig()->get("add-op-at-rank")){
			foreach($server->getOps()->getAll() as $op){
				if($this->plugin->accountExists($op)){
					$except[$op] = true;
				}
			}
		}

		$texts = [];
		for($i = 1; $i <= 5; $i++){
			$rank = (5 * ($page - 1)) + $i;
			if($rank > $max){
				break;
			}
			$player = $this->plugin->getPlayerByRank($rank);
			if($player === null){
				continue;
			}
			$texts[] = ((isset($except[$player])) ? "§7" : "") . "[" . $rank . "위] " . $player . " : " . $this->plugin->myMoney($player);
		}
		$sender->sendMessage(new Page("돈 순위", $texts, $page, $maxPage));
		return true;
	}
}
