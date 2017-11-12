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

	/** @var EconomyAPI */
	private $plugin;

	/** @var Config */
	private $config;

	/** @var array */
	private $money = [];

	private $rankCache_NameToRank = [];
	private $rankCache_RankToName = [];

	public function __construct(EconomyAPI $plugin){
		$this->plugin = $plugin;

		$this->open();
		$this->recalculateAllRank();
	}

	public function open(){
		$this->config = new Config($this->plugin->getDataFolder() . "Money.yml", Config::YAML, [
			"version" => 2,
			"money" => []
		]);
		$this->money = $this->config->getAll()["money"];
	}

	protected function recalculateRank(string $player){
		$player = strtolower($player);

		$end = count($this->money);
		if(!isset($this->rankCache_NameToRank[$player])){
			$this->rankCache_NameToRank[$player] = $end;
			$this->rankCache_RankToName[$end] = $player;
		}

		$currentRank = $this->rankCache_NameToRank[$player];
		$currentMoney = $this->money[$player];

		$changed = false;

		while($currentRank > 1 && $currentMoney > $this->money[$this->rankCache_RankToName[$currentRank - 1]]){
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

		while($currentRank < $end && $currentMoney < $this->money[$this->rankCache_RankToName[$currentRank + 1]]){ // if target player's money is less than below player's money
			$belowPlayer = $this->rankCache_RankToName[$currentRank + 1];
			$this->rankCache_RankToName[$currentRank] = $belowPlayer;
			$this->rankCache_NameToRank[$belowPlayer] = $currentRank;

			$currentRank++; // rank down...
			$this->rankCache_RankToName[$currentRank] = $player;
			$this->rankCache_NameToRank[$player] = $currentRank;

			$changed = true;
		}
	}

	protected function recalculateAllRank(){
		arsort($this->money);
		$rank = 1;
		foreach($this->money as $name => $money){
			$this->rankCache_RankToName[$rank] = $name;
			$this->rankCache_NameToRank[$name] = $rank;
			++$rank;
		}
		return;
	}

	public function accountExists(string $player) : bool{
		return isset($this->money[$player]);
	}

	public function createAccount(string $player, $defaultMoney = 1000) : bool{
		$player = strtolower($player);
		if(!isset($this->money[$player])){
			$this->money[$player] = $defaultMoney;
			$this->recalculateRank($player); // calculate rank
			return true;
		}
		return false;
	}

	public function removeAccount(string $player) : bool{
		$player = strtolower($player);
		if(isset($this->money[$player])){
			unset($this->money[$player]);
			$this->recalculateAllRank(); //TODO: Optimize
			return true;
		}
		return false;
	}

	public function getMoney(string $player){
		return $this->money[strtolower($player)] ?? false;
	}

	public function setMoney(string $player, $amount) : bool{
		$player = strtolower($player);
		if(isset($this->money[$player])){
			$this->money[$player] = $amount;
			$this->money[$player] = round($this->money[$player], 2);
			$this->recalculateRank($player); // calculate rank
			return true;
		}
		return false;
	}

	public function addMoney(string $player, $amount) : bool{
		$player = strtolower($player);
		if(isset($this->money[$player])){
			$this->money[$player] += $amount;
			$this->money[$player] = round($this->money[$player], 2);
			$this->recalculateRank($player); // 해당 플레이어의 순위를 다시 계산합니다.
			return true;
		}
		return false;
	}

	public function reduceMoney(string $player, $amount) : bool{
		$player = strtolower($player);
		if(isset($this->money[$player])){
			$this->money[$player] -= $amount;
			$this->money[$player] = round($this->money[$player], 2);
			$this->recalculateRank($player); // 해당 플레이어의 순위를 다시 계산합니다.
			return true;
		}
		return false;
	}

	public function getRank(string $player){
		return $this->rankCache_NameToRank[strtolower($player)] ?? false;
	}

	public function getPlayerByRank(int $rank){
		return $this->rankCache_RankToName[$rank] ?? false;
	}

	public function getAll() : array{
		return $this->money ?? [];
	}

	public function save(){
		$this->config->setAll([
			"version" => 2,
			"money" => $this->money
		]);
		$this->config->save();
	}

	public function getName() : string{
		return "Yaml";
	}
}
