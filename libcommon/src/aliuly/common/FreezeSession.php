<?php

namespace aliuly\common;
use aliuly\common\Session;
use pocketmine\event\player\PlayerMoveEvent;

/**
 * Frozen Player sessions
 *
 * TODO:
 * Check if GrabBag exists, if so, this will call GrabBag...
 */
class FrozenSession extends Session {
  protected $hard;
  /**
   * @param PluginBase $owner - plugin that owns this session
   * @param bool $hard - hard freeze option
   */
  public function __construct(PluginBase $owner, $hard = true) {
    parent::__construct($owner);
    $this->hard = $hard;
  }
  /**
	 * Handle player move events.
   * @param PlayerMoveEvent $ev - Move event
	 */
  public function onMove(PlayerMoveEvent $ev) {
    //echo __METHOD__.",".__LINE__."\n";//##DEBUG
    if ($ev->isCancelled()) return;
    $p = $ev->getPlayer();
    if (!$this->getState("fz",$p,false)) return;
    if ($this->hard) {
      $ev->setCancelled();
    } else {
      // Lock position but still allow to turn around
      $to = clone $ev->getFrom();
      $to->yaw = $ev->getTo()->yaw;
      $to->pitch = $ev->getTo()->pitch;
      $ev->setTo($to);
    }
  }
  /**
   * Checks if hard or soft freezing
   * @return bool
   */
  public function isHard() {
    return $this->hard;
  }
  /**
   * Sets hard or soft freezing
   * @param bool $hard - if true (default) hard freeze is in effect.
   */
  public function setHard($hard = true) {
    $this->hard = $hard;
  }
  /**
   * Freeze given player
   * @param Player $player - player to freeze
   * @param bool $freeze - if true (default) freeze, if false, thaw.
   */
  public function freeze(Player $player, $freeze = true) {
    if ($freeze) {
      $this->setState("fz",$player,true);
    } else {
      $this->unsetState("fz",$player);
    }
  }
  /**
   * Return a list of frozen players
   * @return str[]
   */
  public function getFrosties() {
    $s = [];
    foreach ($this->state as $n=>$d) {
      if (isset($d["fz"])) $s[] = $n;
    }
    return $s;
  }
}