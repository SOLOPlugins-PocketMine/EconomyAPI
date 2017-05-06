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

class SeeMoneyCommand extends Command{
	private $plugin;

	public function __construct(EconomyAPI $plugin){
		parent::__construct("돈보기", "다른 플레이어의 돈을 확인합니다.", "/돈보기 [플레이어]", ["돈확인", "seemoney"]);
		$this->setPermission("economyapi.command.seemoney");

		$this->plugin = $plugin;
	}

	public function execute(CommandSender $sender, $label, array $params){
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(new Alert("이 명령을 사용할 권한이 없습니다."));
			return true;
		}
		
		$player = array_shift($params);

		if(trim($player) === ""){
			$sender->sendMessage(new Usage($this->getUsage()));
			return true;
		}

		if(($p = $this->plugin->getServer()->getPlayer($player)) instanceof Player){
			$player = $p->getName();
		}

		$money = $this->plugin->myMoney($player);
		if($money !== false){
			$sender->sendMessage(new Notify($player . " 님의 돈 : " . $money));
		}else{
			$sender->sendMessage(new Alert($player . " 님은 서버에 접속한 적이 없습니다."));
		}
		return true;
	}
}
