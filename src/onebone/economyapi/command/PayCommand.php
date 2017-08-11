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
use onebone\economyapi\event\money\PayMoneyEvent;

class PayCommand extends EconomyAPICommand{
	private $plugin;

	public function __construct(EconomyAPI $plugin){
		parent::__construct("지불", "다른 플레이어에게 돈을 지불합니다.", "/지불 [플레이어] [금액]", ["돈보내기", "입금", "pay"]);
		$this->setPermission("economyapi.command.pay");

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

		$player = array_shift($params);
		$amount = array_shift($params);

		if(!is_numeric($amount)){
			$sender->sendMessage(EconomyAPI::$prefix . "사용법 : " . $this->getUsage());
			return true;
		}

		if(($p = $this->plugin->getServer()->getPlayer($player)) instanceof Player){
			$player = $p->getName();
		}

		if(!$p instanceof Player and $this->plugin->getConfig()->get("allow-pay-offline", true) === false){
			$sender->sendMessage(EconomyAPI::$prefix . $player . "님은 현재 오프라인입니다.");
			return true;
		}

		if(!$this->plugin->accountExists($player)){
			$sender->sendMessage(EconomyAPI::$prefix . $player . " 님은 서버에 접속한 적이 없습니다.");
			return true;
		}

		$this->plugin->getServer()->getPluginManager()->callEvent($ev = new PayMoneyEvent($this->plugin, $sender->getName(), $player, $amount));

		$result = EconomyAPI::RET_CANCELLED;
		if(!$ev->isCancelled()){
			$result = $this->plugin->reduceMoney($sender, $amount);
		}

		if($result === EconomyAPI::RET_SUCCESS){
			$this->plugin->addMoney($player, $amount, true);

			$sender->sendMessage(EconomyAPI::$prefix . $player . " 님에게 " . $amount . "원을 지불하였습니다.");
			if($p instanceof Player){
				$p->sendMessage(EconomyAPI::$prefix . $sender->getName() . " 님으로부터 " . $amount . "원을 지불받았습니다.");
			}
		}else{
			$sender->sendMessage(EconomyAPI::$prefix . "지불에 실패하였습니다.");
		}
		return true;
	}
}
