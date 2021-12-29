<?php

namespace NoobMCBG\ToolLevels\task;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\Player;
use NoobMCBG\ToolLevels\Main as ToolLevels;

class PopupTask extends Task{


    public function __construct(ToolLevels $plugin, Player $player){
        $this->plugin = $plugin;
        $this->player = $player;
    }

    public function getPlugin(){
        return $this->plugin;
    }

    public function onRun($currentTick){
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $level = $this->getPlugin()->getLevel($player);
            $exp = $this->getPlugin()->getExp($player);
            $next = $this->getPlugin()->getNextExp($player);
            $name = $player->getName();
            $item = $player->getInventory()->getItemInHand();
            $damage = $item->getDamage();
            if($item->getCustomName() == "§l§c•§e Dụng Cụ Của§b $name §c|§e Cấp Độ:§a $level §c•"){
                if($damage > 50){ 
                    $item->setDamage(0);
                    $player->getInventory()->setItemInHand($item);
                    $player->sendMessage("§l§c•§e Dụng Cụ Của Bạn Đã Được Sửa Chữa Miễn Phí !");
                }
                $player->sendPopup("    §l§9☭§d Dụng Cụ§c | §eＭine§b Ｌight\n" . "§l§c•§b Kinh Nghiệm:§9 " . $exp ."§l§3 /§9 ".$next. " §l§c•\n§l§c•§e Cấp Độ:§a " . $level . " §c•");
            }else{
                $this->getPlugin()->getScheduler()->cancelTask($this->getTaskId());
            }
        }
    }
}