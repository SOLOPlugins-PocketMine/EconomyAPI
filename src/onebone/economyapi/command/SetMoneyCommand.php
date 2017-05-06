<?php

namespace onebone\economyapi\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\event\TranslationContainer;
use pocketmine\Player;

use onebone\economyapi\EconomyAPI;

use solo\standardapi\message\Notify;
use solo\standardapi\message\Alert;
use solo\standardapi\message\Usage;

class SetMoneyCommand extends Command{
	private $plugin;

	public function __construct(EconomyAPI $plugin){
		parent::__construct("돈설정", "플레이어의 돈을 설정합니다.", "/돈설정 [금액]", ["setmoney"]);
		$this->setPermission("economyapi.command.setmoney");

		$this->plugin = $plugin;
	}

	public function execute(CommandSender $sender, $label, array $params){
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(new Alert("이 명령을 사용할 권한이 없습니다."));
			return true;
		}
		
		$player = array_shift($params);
		$amount = array_shift($params);

		if(!is_numeric($amount)){
			$sender->sendMessage(new Usage($this->getUsage()));
			return true;
		}

		if(($p = $this->plugin->getServer()->getPlayer($player)) instanceof Player){
			$player = $p->getName();
		}

		$result = $this->plugin->setMoney($player, $amount);
		switch($result){
			case EconomyAPI::RET_INVALID:
				$sender->sendMessage(new Alert("잘못된 숫자를 입력하셨습니다."));
				break;

			case EconomyAPI::RET_NO_ACCOUNT:
				$sender->sendMessage(new Alert($player . " 님은 서버에 접속한 적이 없습니다."));
				break;

			case EconomyAPI::RET_CANCELLED:
				$sender->sendMessage(new Alert("요청이 취소되었습니다."));
				break;

			case EconomyAPI::RET_SUCCESS:
				$sender->sendMessage(new Notify($player . " 님의 돈을 " . $amount . "원으로 설정하였습니다."));

				if($p instanceof Player){
					$p->sendMessage(new Notify("돈이 " . $amount . " 원으로 설정되었습니다."));
				}
				break;

			default:
				$sender->sendMessage(new Alert("알 수 없는 에러가 발생하였습니다."));
		}
		return true;
	}
}
