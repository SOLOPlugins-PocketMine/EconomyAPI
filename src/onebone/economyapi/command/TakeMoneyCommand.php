<?php

namespace onebone\economyapi\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\Player;

use onebone\economyapi\EconomyAPI;

use solo\standardapi\message\Notify;
use solo\standardapi\message\Alert;
use solo\standardapi\message\Usage;

class TakeMoneyCommand extends Command{
	private $plugin;

	public function __construct(EconomyAPI $plugin){
		parent::__construct("돈뺏기", "플레이어의 돈을 뺏습니다.", "/돈뺏기 [플레이어] [금액]", ["돈가져오기", "takemoney"]);
		$this->setPermission("economyapi.command.takemoney");

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

		if($amount < 0){
			$sender->sendMessage(new Alert("잘못된 숫자를 입력하셨습니다."));
			return true;
		}

		$result = $this->plugin->reduceMoney($player, $amount);
		switch($result){
			case EconomyAPI::RET_INVALID:
				$sender->sendMessage(new Alert($player . " 님의 돈이 모자랍니다."));
				break;

			case EconomyAPI::RET_SUCCESS:
				$sender->sendMessage(new Notify($player . " 님의 돈을 " . $amount . " 만큼 빼앗았습니다."));

				if($p instanceof Player){
					$p->sendMessage(new Notify("돈을 " . $amount . " 만큼 빼앗겼습니다."));
				}
				break;

			case EconomyAPI::RET_CANCELLED:
				$sender->sendMessage(new Alert("요청이 취소되었습니다."));
				break;

			case EconomyAPI::RET_NO_ACCOUNT:
				$sender->sendMessage(new Alert($player . " 님은 서버에 접속한 적이 없습니다."));
				break;
		}
	}
}
