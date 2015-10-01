<?php
//= module:cmd-selector
//: Implements "@" command prefixes
//:
//: Please refer to the CommandSelector section
//


namespace aliuly\grabbag;

use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\command\CommandSender;

use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\server\RemoteServerCommandEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\Timings;

use aliuly\common\BasicCli;
use aliuly\common\mc;
use aliuly\common\CmdSelector;
use aliuly\common\PermUtils;

class PlayerCommandPreprocessEvent_sub extends PlayerCommandPreprocessEvent{
}
class RemoteServerCommandEvent_sub extends RemoteServerCommandEvent{
}
class ServerCommandEvent_sub extends ServerCommandEvent{
}


class CmdSelMgr extends BasicCli implements Listener {
  protected $max;
	static public function defaults() {
		//= cfg:cmd-selector
		return [
			"# max-commands" => "Limit the ammount of commands generated by @ prefixes",
			"max-commands" => 100,
		];
	}
	public function __construct($owner, $cfg) {
		parent::__construct($owner);
		$this->max = $cfg["max-commands"];
    PermUtils::add($this->owner, "gb.module.cmdsel", "use command selectors", "true");
		$this->owner->getServer()->getPluginManager()->registerEvents($this, $this->owner);
	}
	/**
	 * @priority HIGHEST
	 */
	public function onPlayerCmd(PlayerCommandPreprocessEvent $ev) {
		if ($ev instanceof PlayerCommandPreprocessEvent_sub) return;
		$line = $ev->getMessage();
		if(substr($line, 0, 1) !== "/") return;
		if (!$ev->getPlayer()->hasPermission("gb.module.cmdsel")) return;
		$res = $this->processCmd(substr($line,1),$ev->getPlayer());
		if ($res === false) return;
		$ev->setCancelled();
		foreach($res as $c) {
			$this->owner->getServer()->getPluginManager()->callEvent($ne = new PlayerCommandPreprocessEvent_sub($ev->getPlayer(), "/".$c));
			if($ne->isCancelled()) continue;
			if (substr($ne->getMessage(),0,1) !== "/") continue;
			$this->owner->getServer()->dispatchCommand($ne->getPlayer(), substr($ne->getMessage(),1));
		}
	}
	/**
	 * @priority HIGHEST
	 */
	public function onRconCmd(RemoteServerCommandEvent $ev) {
		if ($ev instanceof RemoteServerCommandEvent_sub) return;
		$res = $this->processCmd($ev->getCommand(),$ev->getSender());
		if ($res === false) return;
		$ev->setCancelled();
		foreach($res as $c) {
			$this->owner->getServer()->getPluginManager()->callEvent($ne = new RemoteServerCommandEvent_sub($ev->getSender(), $c));
			if($ne->isCancelled()) continue;
			$this->owner->getServer()->dispatchCommand($ne->getSender(), $ne->getCommand());
		}
	}
	/**
	 * @priority HIGHEST
	 */
	public function onConsoleCmd(ServerCommandEvent $ev) {
		if ($ev instanceof ServerCommandEvent_sub) return;
		$res = $this->processCmd($ev->getCommand(),$ev->getSender());
		if ($res === false) return;
		$ev->setCancelled();
		foreach($res as $c) {
			$this->owner->getServer()->getPluginManager()->callEvent($ne = new ServerCommandEvent_sub($ev->getSender(), $c));
			if($ne->isCancelled()) continue;
			$this->owner->getServer()->dispatchCommand($ne->getSender(), $ne->getCommand());
		}
	}

	protected function processCmd($cmd,CommandSender $sender) {
		return CmdSelector::expandSelectors($this->owner->getServer(),$sender, $cmd, $this->max);
	}
}
