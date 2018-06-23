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

class SetMoneyCommand extends Command{
	private $plugin;

	public function __construct(EconomyAPI $plugin){
		parent::__construct("돈설정", "플레이어의 돈을 설정합니다.", "/돈설정 [플레이어] [금액]", ["setmoney"]);
		$this->setPermission("economyapi.command.setmoney");

		$this->plugin = $plugin;
	}

	public function execute(CommandSender $sender, string $label, array $params) : bool{
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

		$result = $this->plugin->setMoney($player, $amount);
		switch($result){
			case EconomyAPI::RET_INVALID:
				$sender->sendMessage(EconomyAPI::$prefix . "잘못된 숫자를 입력하셨습니다.");
				break;

			case EconomyAPI::RET_NO_ACCOUNT:
				$sender->sendMessage(EconomyAPI::$prefix . $player . " 님은 서버에 접속한 적이 없습니다.");
				break;

			case EconomyAPI::RET_CANCELLED:
				$sender->sendMessage(EconomyAPI::$prefix . "요청이 취소되었습니다.");
				break;

			case EconomyAPI::RET_SUCCESS:
				$sender->sendMessage(EconomyAPI::$prefix . $player . " 님의 돈을 " . $amount . "원으로 설정하였습니다.");

				if($p instanceof Player){
					$p->sendMessage(EconomyAPI::$prefix . "돈이 " . $amount . " 원으로 설정되었습니다.");
				}
				break;

			default:
				$sender->sendMessage(EconomyAPI::$prefix . "알 수 없는 에러가 발생하였습니다.");
		}
		return true;
	}
}
