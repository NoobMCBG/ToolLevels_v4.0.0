<?php

namespace NoobMCBG\ToolLevels\task;

use pocketmine\scheduler\Task;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use NoobMCBG\ToolLevels\Main as ToolLevels;
class CooldownSkill extends Task{

    public function __construct(ToolLevels $plugin){
        $this->plugin = $plugin;
    }

    public function getPlugin(){
        return $this->plugin;
    }

    public function onRun($currenttick){
        if(count($this->getPlugin()->time->getAll()) >= 1){
            foreach($this->getPlugin()->time->getAll() as $player => $time){
                if($time == 0){
                    $all = $this->getPlugin()->time->getAll();
                    unset($all[$player]);
                    $this->getPlugin()->time->setAll($all);
                    $this->getPlugin()->pickaxeleveling->set($player, false);
                    if($this->getPlugin()->getServer()->getPlayer($player) !== null){
                        $this->getPlugin()->getServer()->getPlayer($player)->sendMessage("§l§c•§e Kĩ Năng§a PickaxeLeveling§e Của Bạn Đã Hết Thời Gian Sử Dụng !");
                    }
                    continue;
                }
                if($this->getPlugin()->getServer()->getPlayer($player) !== null){
                }
                $this->getPlugin()->time->set($player, $time -1);
            }
            $this->getPlugin()->time->save();
        }
    }
}