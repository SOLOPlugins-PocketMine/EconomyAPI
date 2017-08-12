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

class TakeMoneyCommand extends EconomyAPICommand{
	private $plugin;

	public function __construct(EconomyAPI $plugin){
		parent::__construct("돈뺏기", "플레이어의 돈을 뺏습니다.", "/돈뺏기 [플레이어] [금액]", ["돈가져오기", "takemoney"]);
		$this->setPermission("economyapi.command.takemoney");

		$this->plugin = $plugin;
	}

	public function _execute(CommandSender $sender, string $label, array $params) : bool{
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(EconomyAPI::$prefix . "이 명령을 사용할 권한이 없습니다.");
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

		if($amount < 0){
			$sender->sendMessage(EconomyAPI::$prefix . "잘못된 숫자를 입력하셨습니다.");
			return true;
		}

		$result = $this->plugin->reduceMoney($player, $amount);
		switch($result){
			case EconomyAPI::RET_INVALID:
				$sender->sendMessage(EconomyAPI::$prefix . $player . " 님의 돈이 모자랍니다.");
				break;

			case EconomyAPI::RET_SUCCESS:
				$sender->sendMessage(EconomyAPI::$prefix . $player . " 님의 돈을 " . $amount . " 만큼 빼앗았습니다.");

				if($p instanceof Player){
					$p->sendMessage(EconomyAPI::$prefix . "돈을 " . $amount . " 만큼 빼앗겼습니다.");
				}
				break;

			case EconomyAPI::RET_CANCELLED:
				$sender->sendMessage(EconomyAPI::$prefix . "요청이 취소되었습니다.");
				break;

			case EconomyAPI::RET_NO_ACCOUNT:
				$sender->sendMessage(EconomyAPI::$prefix . $player . " 님은 서버에 접속한 적이 없습니다.");
				break;
		}
		return true;
	}
}
