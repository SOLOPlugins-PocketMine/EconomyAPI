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

namespace onebone\economyapi\provider;


use onebone\economyapi\EconomyAPI;
use pocketmine\Player;
use pocketmine\utils\Config;

class YamlProvider implements Provider{

	private $config;

	private $plugin;

	private $money = [];

	private $rankCache_NameToRank = [];
	private $rankCache_RankToName = [];

	public function __construct(EconomyAPI $plugin){
		$this->plugin = $plugin;

		$this->open();
		$this->recalculateRank();
	}

	protected function recalculateRank($player = null){
		if($player === null){
			arsort($this->money["money"]);
			$rank = 1;
			foreach($this->money["money"] as $name => $money){
				$this->rankCache_RankToName[$rank] = $name;
				$this->rankCache_NameToRank[$name] = $rank;
				++$rank;
			}
			return;
		}

		if($player instanceof CommandSender){
			$player = $player->getName();
		}
		$player = strtolower($player);

		$end = count($this->money["money"]);
		if(!isset($this->rankCache_NameToRank[$player])){
			$this->rankCache_NameToRank[$player] = $end;
			$this->rankCache_RankToName[$end] = $player;
		}

		$currentRank = $this->rankCache_NameToRank[$player];
		$currentMoney = $this->money["money"][$player];

		$changed = false;

		while($currentRank > 1 && $currentMoney > $this->money["money"][$this->rankCache_RankToName[$currentRank - 1]]){
			$abovePlayer = $this->rankCache_RankToName[$currentRank - 1];
			$this->rankCache_RankToName[$currentRank] = $abovePlayer;
			$this->rankCache_NameToRank[$abovePlayer] = $currentRank;

			$currentRank--; // rank up!
			$this->rankCache_RankToName[$currentRank] = $player;
			$this->rankCache_NameToRank[$player] = $currentRank;

			$changed = true;
		}

		if($changed){
			return;
		}

		while($currentRank < $end && $currentMoney < $this->money["money"][$this->rankCache_RankToName[$currentRank + 1]]){ // if target player's money is less than below player's money
			$belowPlayer = $this->rankCache_RankToName[$currentRank + 1];
			$this->rankCache_RankToName[$currentRank] = $belowPlayer;
			$this->rankCache_NameToRank[$belowPlayer] = $currentRank;

			$currentRank++; // rank down...
			$this->rankCache_RankToName[$currentRank] = $player;
			$this->rankCache_NameToRank[$player] = $currentRank;

			$changed = true;
		}
	}

	public function open(){
		$this->config = new Config($this->plugin->getDataFolder() . "Money.yml", Config::YAML, ["version" => 2, "money" => []]);
		$this->money = $this->config->getAll();
	}

	public function accountExists($player){
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = strtolower($player);

		return isset($this->money["money"][$player]);
	}

	public function createAccount($player, $defaultMoney = 1000){
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = strtolower($player);

		if(!isset($this->money["money"][$player])){
			$this->money["money"][$player] = $defaultMoney;
			$this->recalculateRank($player); // calculate rank
			return true;
		}
		return false;
	}

	public function removeAccount($player){
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = strtolower($player);

		if(isset($this->money["money"][$player])){
			unset($this->money["money"][$player]);
			$this->recalculateRank(); //TODO: Optimize
			return true;
		}
		return false;
	}

	public function getMoney($player){
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = strtolower($player);

		if(isset($this->money["money"][$player])){
			return $this->money["money"][$player];
		}
		return false;
	}

	public function setMoney($player, $amount){
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = strtolower($player);

		if(isset($this->money["money"][$player])){
			$this->money["money"][$player] = $amount;
			$this->money["money"][$player] = round($this->money["money"][$player], 2);
			$this->recalculateRank($player); // calculate rank
			return true;
		}
		return false;
	}

	public function addMoney($player, $amount){
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = strtolower($player);

		if(isset($this->money["money"][$player])){
			$this->money["money"][$player] += $amount;
			$this->money["money"][$player] = round($this->money["money"][$player], 2);
			$this->recalculateRank($player); // calculate rank
			return true;
		}
		return false;
	}

	public function reduceMoney($player, $amount){
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = strtolower($player);

		if(isset($this->money["money"][$player])){
			$this->money["money"][$player] -= $amount;
			$this->money["money"][$player] = round($this->money["money"][$player], 2);
			$this->recalculateRank($player); // calculate rank
			return true;
		}
		return false;
	}

	public function getRank($player){
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = strtolower($player);

		return $this->rankCache_NameToRank[$player] ?? -1;
	}

	public function getPlayerByRank($rank){
		return $this->rankCache_RankToName[$rank] ?? null;
	}

	public function getAll(){
		return $this->money["money"] ?? [];
	}

	public function save(){
		$this->config->setAll($this->money);
		$this->config->save();
	}

	public function close(){
		$this->save();
	}

	public function getName(){
		return "Yaml";
	}
}
