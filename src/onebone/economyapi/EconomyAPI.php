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

namespace onebone\economyapi;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;
use pocketmine\utils\Utils;
use pocketmine\utils\TextFormat;

use onebone\economyapi\provider\Provider;
use onebone\economyapi\provider\YamlProvider;
use onebone\economyapi\event\money\SetMoneyEvent;
use onebone\economyapi\event\money\ReduceMoneyEvent;
use onebone\economyapi\event\money\AddMoneyEvent;
use onebone\economyapi\event\money\MoneyChangedEvent;
use onebone\economyapi\event\account\CreateAccountEvent;
use onebone\economyapi\task\SaveTask;

class EconomyAPI extends PluginBase{

	public static $prefix = "§b§l[EconomyAPI] §r§7";

	const API_VERSION = 3;
	const PACKAGE_VERSION = "5.7";

	const RET_NO_ACCOUNT = -3;
	const RET_CANCELLED = -2;
	const RET_NOT_FOUND = -1;
	const RET_INVALID = 0;
	const RET_SUCCESS = 1;

	/** @var EconomyAPI */
	private static $instance = null;

	/** @var Provider */
	private $provider;

	public static function getInstance() : EconomyAPI{
		return self::$instance;
	}

	public function onLoad(){
		if(self::$instance !== null){
			throw new \InvalidStateException();
		}
		self::$instance = $this;
	}

	public function onEnable(){
		$this->saveDefaultConfig();

		// Provider set
		switch(strtolower($this->getConfig()->get("provider"))){
			case "yaml":
				$this->provider = new YamlProvider($this);
				break;

			default:
				throw new \UnexpectedValueException("Invalid database was given");
		}

		foreach([
			"GiveMoneyCommand", "MyMoneyCommand", "PayCommand",
			"SeeMoneyCommand", "SetMoneyCommand", "TakeMoneyCommand",
			"TopBalanceCommand", "TopMoneyCommand"
		] as $class){
			$class = "\\onebone\\economyapi\\command\\" . $class;
			$this->getServer()->getCommandMap()->register("economyapi", new $class($this));
		}

		$saveInterval = $this->getConfig()->get("auto-save-interval") * 1200;

		if($saveInterval > 0){
			$this->getScheduler()->scheduleDelayedRepeatingTask(new SaveTask($this), $saveInterval, $saveInterval);
		}

		$this->getServer()->getPluginManager()->registerEvents(new class($this) implements Listener{
			public function __construct(EconomyAPI $owner){
				$this->owner = $owner;
			}

			/**
			 * @ignoreCancelled true
			 *
			 * @priority MONITOR
			 */
			public function handlePlayerJoin(PlayerJoinEvent $event){
				$player = $event->getPlayer();

				if(!$this->owner->accountExists($player)){
					$this->owner->createAccount($player, false, true);
				}
			}
		}, $this);
	}

	public function onDisable(){
		$this->saveAll();

		self::$instance = null;
	}

	public function getMonetaryUnit() : string{
		return $this->getConfig()->get("monetary-unit", "￦");
	}

	public function thousandSeparatedFormat($money) : string{
		return number_format($money) . $this->getMonetaryUnit();
	}

	public function koreanWonFormat($money) : string{
		$str = '';
		$elements = [];
		if($money >= 1000000000000){
			$elements[] = floor($money / 1000000000000) . "조";
			$money %= 1000000000000;
		}
		if($money >= 100000000){
			$elements[] = floor($money / 100000000) . "억";
			$money %= 100000000;
		}
		if($money >= 10000){
			$elements[] = floor($money / 10000) . "만";
			$money %= 10000;
		}
		if(count($elements) == 0 || $money > 0){
			$elements[] = $money;
		}
		return implode(" ", $elements) . $this->getMonetaryUnit();
	}

	public function getAllMoney() : array{
		return $this->provider->getAll();
	}

	public function createAccount($player, $defaultMoney = false, bool $force = false) : bool{
		$player = strtolower($player instanceof Player ? $player->getName() : $player);

		if(!$this->provider->accountExists($player)){
			$defaultMoney = ($defaultMoney === false) ? $this->getConfig()->get("default-money") : $defaultMoney;

			$this->getServer()->getPluginManager()->callEvent($ev = new CreateAccountEvent($this, $player, $defaultMoney, "none"));
			if(!$ev->isCancelled() or $force === true){
				$this->provider->createAccount($player, $ev->getDefaultMoney());
			}
		}
		return false;
	}

	public function accountExists($player) : bool{
		$player = strtolower($player instanceof Player ? $player->getName() : $player);

		return $this->provider->accountExists($player);
	}

	public function myMoney($player){
		$player = strtolower($player instanceof Player ? $player->getName() : $player);

		return $this->provider->getMoney($player);
	}

	public function setMoney($player, $amount, bool $force = false, string $issuer = "none") : int{
		if($amount < 0){
			return self::RET_INVALID;
		}
		$player = strtolower($player instanceof Player ? $player->getName() : $player);

		if($this->provider->accountExists($player)){
			$amount = round($amount, 2);
			if($amount > $this->getConfig()->get("max-money")){
				return self::RET_INVALID;
			}

			$this->getServer()->getPluginManager()->callEvent($ev = new SetMoneyEvent($this, $player, $amount, $issuer));
			if(!$ev->isCancelled() or $force === true){
				$this->provider->setMoney($player, $amount);
				$this->getServer()->getPluginManager()->callEvent(new MoneyChangedEvent($this, $player, $amount, $issuer));
				return self::RET_SUCCESS;
			}
			return self::RET_CANCELLED;
		}
		return self::RET_NO_ACCOUNT;
	}

	public function addMoney($player, $amount, bool $force = false, $issuer = "none") : int{
		if($amount < 0){
			return self::RET_INVALID;
		}
		$player = strtolower($player instanceof Player ? $player->getName() : $player);

		if(($money = $this->provider->getMoney($player)) !== false){
			$amount = round($amount, 2);
			if($money + $amount > $this->getConfig()->get("max-money")){
				return self::RET_INVALID;
			}

			$this->getServer()->getPluginManager()->callEvent($ev = new AddMoneyEvent($this, $player, $amount, $issuer));
			if(!$ev->isCancelled() or $force === true){
				$this->provider->addMoney($player, $amount);
				$this->getServer()->getPluginManager()->callEvent(new MoneyChangedEvent($this, $player, $amount + $money, $issuer));
				return self::RET_SUCCESS;
			}
			return self::RET_CANCELLED;
		}
		return self::RET_NO_ACCOUNT;
	}

	public function reduceMoney($player, $amount, bool $force = false, $issuer = "none") : int{
		if($amount < 0){
			return self::RET_INVALID;
		}
		$player = strtolower($player instanceof Player ? $player->getName() : $player);

		if(($money = $this->provider->getMoney($player)) !== false){
			$amount = round($amount, 2);
			if($money - $amount < 0){
				return self::RET_INVALID;
			}

			$this->getServer()->getPluginManager()->callEvent($ev = new ReduceMoneyEvent($this, $player, $amount, $issuer));
			if(!$ev->isCancelled() or $force === true){
				$this->provider->reduceMoney($player, $amount);
				$this->getServer()->getPluginManager()->callEvent(new MoneyChangedEvent($this, $player, $money - $amount, $issuer));
				return self::RET_SUCCESS;
			}
			return self::RET_CANCELLED;
		}
		return self::RET_NO_ACCOUNT;
	}

	public function getRank($player){
		$player = strtolower($player instanceof Player ? $player->getName() : $player);

		return $this->provider->getRank($player);
	}

	public function getPlayerByRank(int $rank){
		return $this->provider->getPlayerByRank($rank);
	}

	public function saveAll(){
		if($this->provider instanceof Provider){
			$this->provider->save();
		}
	}
}
