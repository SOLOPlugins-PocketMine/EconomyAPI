<?php

namespace onebone\economyapi\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\Player;

use onebone\economyapi\EconomyAPI;
use onebone\economyapi\event\money\PayMoneyEvent;

use solo\standardapi\message\Notify;
use solo\standardapi\message\Alert;
use solo\standardapi\message\Usage;

class PayCommand extends Command{
	private $plugin;

	public function __construct(EconomyAPI $plugin){
		parent::__construct("지불", "다른 플레이어에게 돈을 지불합니다.", "/지불 [플레이어] [금액]", ["돈보내기", "입금", "pay"]);
		$this->setPermission("economyapi.command.pay");

		$this->plugin = $plugin;
	}

	public function execute(CommandSender $sender, $label, array $params){
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(new Alert("이 명령을 사용할 권한이 없습니다."));
			return true;
		}
		
		if(!$sender instanceof Player){
			$sender->sendMessage(new Alert("인게임에서만 사용 가능합니다."));
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

		if(!$p instanceof Player and $this->plugin->getConfig()->get("allow-pay-offline", true) === false){
			$sender->sendMessage($this->plugin->getMessage("player-not-connected", [$player], $sender->getName()));
			return true;
		}

		if(!$this->plugin->accountExists($player)){
			$sender->sendMessage(new Alert($player . " 님은 서버에 접속한 적이 없습니다."));
			return true;
		}

		$this->plugin->getServer()->getPluginManager()->callEvent($ev = new PayMoneyEvent($this->plugin, $sender->getName(), $player, $amount));

		$result = EconomyAPI::RET_CANCELLED;
		if(!$ev->isCancelled()){
			$result = $this->plugin->reduceMoney($sender, $amount);
		}

		if($result === EconomyAPI::RET_SUCCESS){
			$this->plugin->addMoney($player, $amount, true);

			$sender->sendMessage(new Notify($player . " 님에게 " . $amount . "원을 지불하였습니다."));
			if($p instanceof Player){
				$p->sendMessage(new Notify($sender->getName() . " 님으로부터 " . $amount . "원을 지불받았습니다."));
			}
		}else{
			$sender->sendMessage($this->plugin->getMessage(new Alert("지불에 실패하였습니다.")));
		}
		return true;
	}
}
