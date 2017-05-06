<?php

namespace onebone\economyapi\command;

use pocketmine\event\TranslationContainer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\Player;

use onebone\economyapi\EconomyAPI;

use solo\standardapi\message\Notify;
use solo\standardapi\message\Alert;

class MyMoneyCommand extends Command{

	private $plugin;

	public function __construct(EconomyAPI $plugin){
		parent::__construct("내돈", "내 돈을 확인합니다.", "/내돈", ["mymoney", "돈"]);
		$this->setPermission("economyapi.command.mymoney");

		$this->plugin = $plugin;
	}

	public function execute(CommandSender $sender, $label, array $params){
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(new Alert("이 명령을 사용할 권한이 없습니다."));
			return true;
		}
		
		$sender->sendMessage(($sender instanceof Player) ? new Notify("내 돈 : " . $this->plugin->myMoney($sender)) : new Alert("인게임에서만 사용 가능합니다."));
		return true;
	}
}
