<?php

namespace NoobMCBG\ToolLevels;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\Listener as L;
use pocketmine\plugin\PluginBase as PB;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\level\Position;
use pocketmine\level\particle\FlameParticle;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\math\VoxelRayTrace;
use pocketmine\math\Vector3;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\entity\Entity;
use pocketmine\entity\object\ItemEntity;
use pocketmine\level\Explosion;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Tool;
use pocketmine\item\Armor;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\item\ItemBlock;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\scheduler\ClosureTask;
use jojoe77777\FormAPI\SimpleForm;
use NoobMCBG\ToolLevels\task\PopupTask;
use NoobMCBG\ToolLevels\task\CooldownSkill;

class Main extends PB implements L {

	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->money = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
		$this->token = $this->getServer()->getPluginManager()->getPlugin("TokenAPI");
		$this->credits = $this->getServer()->getPluginManager()->getPlugin("CreditsAPI");
        $this->level = new Config($this->getDataFolder() . "level.yml", Config::YAML);
        $this->exp = new Config($this->getDataFolder() . "exp.yml", Config::YAML);
        $this->nextexp = new Config($this->getDataFolder() . "nextexp.yml", Config::YAML);
        $this->pickaxeleveling = new Config($this->getDataFolder() . "pickaxeleveling.yml", Config::YAML);
        $this->thor = new Config($this->getDataFolder() . "thor.yml", Config::YAML);
        $this->hactram = new Config($this->getDataFolder() . "hactram.yml", Config::YAML);
        $this->bungno = new Config($this->getDataFolder() . "bungno.yml", Config::YAML);
        $this->time = new Config($this->getDataFolder() . "time.yml", Config::YAML);
        $this->getScheduler()->scheduleRepeatingTask(new CooldownSkill($this), 20 * 60);
        $this->getLogger()->info("
???????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????\n\n
???????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????");
	}

    public function getPickaxeLeveling(){
        return $this->pickaxeleveling;
    }

    public function getCooldown(){
        return $this->time;
    }

	public function onJoin(PlayerJoinEvent $ev){
		$player = $ev->getPlayer();
		$inv = $player->getInventory();
		if(!$this->level->get(strtolower($player->getName()))){
			$this->level->set(strtolower($player->getName()), 1);
			$this->level->save();
			$item = Item::get(278, 0, 1);
			$name = $player->getName();
			$level = $this->getLevel($player);
			$item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
		}
		if(!$this->exp->get(strtolower($player->getName()))){
			$this->exp->set(strtolower($player->getName()), 0);
			$this->exp->save();
		}
        if(!$this->nextexp->get(strtolower($player->getName()))){
            $this->nextexp->set(strtolower($player->getName()), 100);
            $this->nextexp->save();
        }
        $this->thor->set(strtolower($player->getName()), false);
        $this->thor->save();
        $this->hactram->set(strtolower($player->getName()), false);
        $this->hactram->save();
        $this->bungno->set(strtolower($player->getName()), false);
        $this->bungno->save();
	}

	public function onQuit(PlayerQuitEvent $ev){
		$this->level->save();
		$this->exp->save();
        $this->nextexp->save();
        $player = $ev->getPlayer();
        $name = $player->getName();
        $this->getLogger()->notice("\n??l??b--------------------\n??l??e ???? L??u File Ng?????i Ch??i??a $name \n??l??b--------------------");
	}

	public function onDisable(){
		$this->level->save();
		$this->exp->save();
        $this->nextexp->save();
        $this->getLogger()->info("
???????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????\n\n
???????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????");
	}

	public function onBreak(BlockBreakEvent $ev){
        $block = $ev->getBlock();
        $player = $ev->getPlayer();
        $inv = $player->getInventory();
        $level = $this->level->get(strtolower($player->getName()));
        $exp = $this->exp->get(strtolower($player->getName()));
        $nextexp = $this->nextexp->get(strtolower($player->getName()));
        $name = $player->getName();
        if($player->getInventory()->getItemInHand()->getCustomName() == "??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???"){
            if($this->getPickaxeLeveling()->get(strtolower($player->getName())) == true){
                $pl = 3;
            }else{
                $pl = 1;
            }
            switch($block->getId()){
            	case 56:// Kim C????ng Ore
                    $this->exp->set(strtolower($player->getName()), $exp+5*$pl);
                    $this->exp->save();
                break;
                case 14:// V??ng Ore
                    $this->exp->set(strtolower($player->getName()), $exp+4*$pl);
                    $this->exp->save();
                break;
                case 15:// S???t Ore
                    $this->exp->set(strtolower($player->getName()), $exp+4*$pl);
                    $this->exp->save();
                break;
                case 16:// Than Ore
                    $this->exp->set(strtolower($player->getName()), $exp+4*$pl);
                    $this->exp->save();
                break;
                case 129:// Emerald Ore
                    $this->exp->set(strtolower($player->getName()), $exp+5*$pl);
                    $this->exp->save();
                break;
                case 21:// Lapis Lazuli Ore
                    $this->exp->set(strtolower($player->getName()), $exp+3*$pl);
                    $this->exp->save();
                break;
                case 22:// Lapis Lazuli Block
                    $this->exp->set(strtolower($player->getName()), $exp+6*$pl);
                    $this->exp->save();
                break;
                case 133:// Emerald Block
                    $this->exp->set(strtolower($player->getName()), $exp+8*$pl);
                    $this->exp->save();
                    break;
                case 57:// Kim C????ng Block
                    $this->exp->set(strtolower($player->getName()), $exp+8*$pl);
                    $this->exp->save();
                break;
                case 42:// S???t Block
                    $this->exp->set(strtolower($player->getName()), $exp+7*$pl);
                    $this->exp->save();
                break;
                case 41:// V??ng Block
                    $this->exp->set(strtolower($player->getName()), $exp+7*$pl);
                    $this->exp->save();
                        break;
                default:// All Kh???i
                    $this->exp->set(strtolower($player->getName()), $exp+2*$pl);
                    $this->exp->save();
                break;
            }
            if($exp >= $nextexp){
			    $this->exp->set(strtolower($player->getName()), 0);
                $this->exp->save();
                $this->nextexp->set(strtolower($player->getName()), $nextexp+100);
                $this->level->set(strtolower($player->getName()), $level+1);
                $this->level->save();
			    $money = $level * 1000;
			    $this->money->addMoney($player, $money);
                $player->sendMessage("??l??c?????e B???n ???? Nh???n ???????c??a $money Xu ??eT??? Ph???n Th?????ng L??n C???p !");
			    $token = 1;
			    $this->token->addToken($player, $token);
                $player->sendMessage("??l??c?????e B???n ???? Nh???n ???????c??2 $token Tokens??eT??? Ph???n Th?????ng L??n C???p !");
			    if(in_array($level, array(100, 200, 300, 400, 500, 600, 700, 800, 900, 1000, 1100, 1200, 1300, 1400, 1500, 1600, 1700, 1800, 1900, 2000, 2100, 2200, 2300, 2400, 2500, 2600, 2700, 2800, 2900, 3000, 3100, 3200, 3300, 3400, 3500, 3600, 3700, 3800, 3900, 4000, 4100, 4200, 4300, 4400, 45000, 4600, 4700, 4800, 4900, 5000, 5100, 5200, 5300, 5400, 5500, 5600, 5700, 5800, 5900, 6000, 6100, 6200, 6300, 6400, 6500, 6600, 6700, 6800, 6900, 7000, 7100, 7200, 7300, 7400, 7500, 7600, 7700, 7800, 7900, 8000, 8100, 8200, 8300, 8400, 8500, 8600, 8700, 8800, 8900, 9000, 9100, 9200, 9300, 9400, 9500, 9600, 9700, 9800, 9900, 10000))){
                    $credits = 1;
                    $this->credits->addCredits($player, $credits);
                    $player->sendMessage("??l??c?????e B???n ???? Nh???n ???????c??f $credits Credits??e T??? Ph???n Th?????ng L??n C???p !");
			    }
			    $this->getServer()->broadcastMessage("??l??c?????e D???ng C??? C???a Ng?????i Ch??i??b $name ??eV???a L??n C???p??a $level");
                $packet = new PlaySoundPacket();
                $packet->soundName = "random.levelup";
                $packet->x = $player->getPosition()->getX();
                $packet->y = $player->getPosition()->getY();
                $packet->z = $player->getPosition()->getZ();
                $packet->volume = 1;
                $packet->pitch = 1;
                $player->sendDataPacket($packet);
			    $player->sendMessage("??l??c?????e Ch??c M???ng D???ng C??? C???a B???n ???? ?????t C???p??a $level");
			    $player->addTitle("??l??c?????e D???ng C??? C???p:??b $level ??c???", "??l??9?????a Ch??c M???ng B???n ???? L??n Level ??9???");
                switch(mt_rand(1, 4)){
                    case 1:
                        if($level >= 50){
                            $item = Item::get(745, 0, 1);
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                            $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $packet = new PlaySoundPacket();
                            $packet->soundName = "random.levelup";
                            $packet->x = $player->getX();
                            $packet->y = $player->getY();
                            $packet->z = $player->getZ();
                            $packet->volume = 1;
                            $packet->pitch = 1;
                            $player->sendDataPacket($packet);
                        }else{
                            $item = Item::get(278, 0, 1);
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                            $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $packet = new PlaySoundPacket();
                            $packet->soundName = "random.levelup";
                            $packet->x = $player->getX();
                            $packet->y = $player->getY();
                            $packet->z = $player->getZ();
                            $packet->volume = 1;
                            $packet->pitch = 1;
                            $player->sendDataPacket($packet);
                        }
                    break;
                    case 2:
                        if($level >= 50){
                            $item = Item::get(746, 0, 1);
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                            $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $packet = new PlaySoundPacket();
                            $packet->soundName = "random.levelup";
                            $packet->x = $player->getX();
                            $packet->y = $player->getY();
                            $packet->z = $player->getZ();
                            $packet->volume = 1;
                            $packet->pitch = 1;
                            $player->sendDataPacket($packet);
                        }else{
                            $item = Item::get(279, 0, 1);
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                            $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $packet = new PlaySoundPacket();
                            $packet->soundName = "random.levelup";
                            $packet->x = $player->getX();
                            $packet->y = $player->getY();
                            $packet->z = $player->getZ();
                            $packet->volume = 1;
                            $packet->pitch = 1;
                            $player->sendDataPacket($packet);
                        }
                    break;
                    case 3:
                       if($level >= 50){
                            $item = Item::get(744, 0, 1);
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                            $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $packet = new PlaySoundPacket();
                            $packet->soundName = "random.levelup";
                            $packet->x = $player->getX();
                            $packet->y = $player->getY();
                            $packet->z = $player->getZ();
                            $packet->volume = 1;
                            $packet->pitch = 1;
                            $player->sendDataPacket($packet);
                        }else{
                            $item = Item::get(277, 0, 1);
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                            $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $packet = new PlaySoundPacket();
                            $packet->soundName = "random.levelup";
                            $packet->x = $player->getX();
                            $packet->y = $player->getY();
                            $packet->z = $player->getZ();
                            $packet->volume = 1;
                            $packet->pitch = 1;
                            $player->sendDataPacket($packet);
                        }
                    break;
                    case 4:
                        if($level >= 50){
                            $item = Item::get(743, 0, 1);
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), $lv));
                            $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $packet = new PlaySoundPacket();
                            $packet->soundName = "random.levelup";
                            $packet->x = $player->getX();
                            $packet->y = $player->getY();
                            $packet->z = $player->getZ();
                            $packet->volume = 1;
                            $packet->pitch = 1;
                            $player->sendDataPacket($packet);
                        }else{
                            $item = Item::get(276, 0, 1);
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), $lv));
                            $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $inv->setItemInHand($item);
                            $packet = new PlaySoundPacket();
                            $packet->soundName = "random.levelup";
                            $packet->x = $player->getX();
                            $packet->y = $player->getY();
                            $packet->z = $player->getZ();
                            $packet->volume = 1;
                            $packet->pitch = 1;
                            $player->sendDataPacket($packet);
                        }
                    break;
			    }
            }
        }
	}

	public function onUse(PlayerInteractEvent $ev){
		$player = $ev->getPlayer();
        $player1 = $ev->getPlayer();
		$inv = $player->getInventory();
		$block = $ev->getBlock();
		$level = $this->level->get(strtolower($player->getName()));
		$name = $player->getName();
		if($player->getInventory()->getItemInHand()->getCustomName() == "??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???"){
		    switch($block->getId()){
                case 17:
                    if($level >= 50){
                    	$item = Item::get(746, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
		    	        $player->getInventory()->setItemInHand($item);
                    }else{
                        $item = Item::get(279, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
		    	        $player->getInventory()->setItemInHand($item);
                    }
		    	break;
		    	case 3:
		    	    if($level >= 50){
                    	$item = Item::get(744, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
		    	        $player->getInventory()->setItemInHand($item);
                    }else{
                        $item = Item::get(277, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
		    	        $player->getInventory()->setItemInHand($item);
                    }
                break;
                case 2:
		    	    if($level >= 50){
                    	$item = Item::get(744, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
		    	        $player->getInventory()->setItemInHand($item);
                    }else{
                        $item = Item::get(277, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
		    	        $player->getInventory()->setItemInHand($item);
                    }
                break;
                case 3:
		    	    if($level >= 50){
                    	$item = Item::get(744, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
		    	        $player->getInventory()->setItemInHand($item);
                    }else{
                        $item = Item::get(277, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
		    	        $player->getInventory()->setItemInHand($item);
                    }
                break;
                case 243:
                    if($level >= 50){
                        $item = Item::get(744, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
                        $player->getInventory()->setItemInHand($item);
                    }else{
                        $item = Item::get(277, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
                        $player->getInventory()->setItemInHand($item);
                    }
                break;
                case 198:
                    if($level >= 50){
                        $item = Item::get(744, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
                        $player->getInventory()->setItemInHand($item);
                    }else{
                        $item = Item::get(277, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
                        $player->getInventory()->setItemInHand($item);
                    }
                break;
                case 110:
                    if($level >= 50){
                        $item = Item::get(744, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
                        $player->getInventory()->setItemInHand($item);
                    }else{
                        $item = Item::get(277, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
                        $player->getInventory()->setItemInHand($item);
                    }
                break;
                case 18:
                    if($level >= 50){
                        $item = Item::get(359, 0, 1);
                        $lv = $this->getLevel($player)/1;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/1;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/1;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
                        $player->getInventory()->setItemInHand($item);
                    }else{
                        $item = Item::get(359, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
                        $player->getInventory()->setItemInHand($item);
                    }
                break;
                default:
		    	    if($level >= 50){
                    	$item = Item::get(745, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
		    	        $player->getInventory()->setItemInHand($item);
                    }else{
                        $item = Item::get(278, 0, 1);
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                        $lv = $this->getLevel($player)/2.5;
                        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                        $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                        $item->setDamage(0);
		    	        $player->getInventory()->setItemInHand($item);
                    }
                break;
		    }
		}
        //Skill Thor (by S2TwKen)
        if($this->thor->get(strtolower($player->getName())) == true){
            if($inv->getItemInHand()->getId() == 746 and $inv->getItemInHand()->getCustomName() == "??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???" or $level >= 50){
                if(!isset($this->cooldown[$player->getName()])){
                    $this->cooldown[$player->getName()] = time() + 5;
                    $pk = new PlaySoundPacket();
                    $pk->soundName = "ambient.weather.thunder";
                    $pk->volume = 150;
                    $pk->pitch = 3;
                    $pk->x = $player->getX();
                    $pk->y = $player->getY();
                    $pk->z = $player->getZ();
                    Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
                    $pk = new PlaySoundPacket();
                    $pk->soundName = "ambient.weather.thunder";
                    $pk->volume = 150;
                    $pk->pitch = 3;
                    $pk->x = $player->getX();
                    $pk->y = $player->getY();
                    $pk->z = $player->getZ();
                    Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
                    $light = new AddActorPacket();
                        $light->type = "minecraft:lightning_bolt";
                    $light->entityRuntimeId = Entity::$entityCount++;
                    $light->metadata = array();
                    $light->motion = null; 
                    $light->yaw = $player->getYaw();
                    $light->pitch = $player->getPitch();
                    $light->position = new Vector3($block->getX(), $block->getY(), $block->getZ());
                    Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $light);
                    foreach($player->getLevel()->getNearbyEntities(new AxisAlignedBB($block->getFloorX() - ($radius = 5), $block->getFloorY() - $radius, $block->getFloorZ() - $radius, $block->getFloorX() + $radius, $block->getFloorY() + $radius, $block->getFloorZ() + $radius), $player) as $e){
                        $e->attack(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_MAGIC, 9));
                    }
                    if(!isset($this->cooldown[$player1->getName()])){
                      $this->cooldown[$player1->getName()] = time() + 5;
                    }else{
                        if(time() < $this->cooldown[$player1->getName()]){
                          $conlai2 = $this->cooldown[$player1->getName()] - time();
                          $player->sendMessage("??l??c?????e Th???i Gian H???i K?? N??ng C??n ??a" . $conlai2 . " Gi??y ! ??c???");
                        }else{
                          unset($this->cooldown[$player1->getName()]);
                        }
                    }
                }else{
                    if(time() < $this->cooldown[$player->getName()]){
                      $conlai1 = $this->cooldown[$player->getName()] - time();
                      $player->sendMessage("??l??c?????e Th???i Gian H???i K?? N??ng C??n ??a" . $conlai1 . " Gi??y ! ??c???");
                    }else{
                      unset($this->cooldown[$player->getName()]);
                    }
                }
            }
        }
        //Skill H???c Tr???m (by S2TwKen)
        if($this->hactram->get(strtolower($player->getName())) == true){
            if($inv->getItemInHand()->getId() == 743 and $inv->getItemInHand()->getCustomName() == "??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???" or $level >= 100){
                if(!isset($this->cooldown[$player->getName()])){
                    $this->cooldown[$player->getName()] = time() + 10;
                    $pos = $player->getTargetBlock(10, $transparent = []);
                    $player->teleport($pos);
                    $center = new Vector3($player->x, $player->y, $player->z);
                    $player->setHealth(9999);
                    $player->getLevel()->broadcastLevelEvent($player, LevelEventPacket::EVENT_SOUND_TOTEM, mt_rand());
                    $player->setHealth(9999);
                    $explosion = new Explosion(new Position($player->getX(), $player->getY(), $player->getZ(), $player->getLevel()), 1, null); 
                    $player->setHealth(9999); 
                    $explosion->explodeB();
                    $player->setHealth(9999);
                    $player->getLevel()->broadcastLevelSoundEvent($player->asVector3(), LevelSoundEventPacket::SOUND_EXPLODE);
                    $player->setHealth(9999);
                }else{
                    if(time() < $this->cooldown[$player->getName()]){
                      $conlai = $this->cooldown[$player->getName()] - time();
                      $player->sendMessage("??l??c?????e Th???i Gian H???i K?? N??ng C??n ??a" . $conlai . " Gi??y ! ??c???");
                    }else{
                      unset($this->cooldown[$player->getName()]);
                    }
                }
            }
        }
        //Skill B??ng N??? (by NoobMCBG)
        if($this->bungno->get(strtolower($player->getName())) == true){
            if($inv->getItemInHand()->getId() == 743 and $inv->getItemInHand()->getCustomName() == "??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???" or $level >= 150){
                if(!isset($this->cooldown[$player->getName()])){
                    $this->cooldown[$player->getName()] = time() + 10;
                    $x = $player->getX();
                    $y = $player->getY();
                    $z = $player->getZ();
                    $world = $player->getLevel();
                    $width = 7;
                    $height = 2;
                    $pos = new Position($x+$width, $y+$height, $z, $world);
                    $explosive = new Explosion($pos, 4, null);
                    $explosive->explodeB();
                    $pos = new Position($x, $y+$height, $z+$width, $world);
                    $explosive = new Explosion($pos, 4, null);
                    $explosive->explodeB();
                    $pos = new Position($x+$width, $y+$height, $z+$width, $world);
                    $explosive = new Explosion($pos, 4, null);
                    $explosive->explodeB();
                    $pos = new Position($x-$width, $y+$height, $z, $world);
                    $explosive = new Explosion($pos, 4, null);
                    $explosive->explodeB();
                    $pos = new Position($x, $y+$height, $z-$width, $world);
                    $explosive = new Explosion($pos, 4, null);
                    $explosive->explodeB();
                    $pos = new Position($x-$width, $y+$height, $z-$width, $world);
                    $explosive = new Explosion($pos, 4, null);
                    $explosive->explodeB();
                }else{
                    if(time() < $this->cooldown[$player->getName()]){
                        $cd = $this->cooldown[$player->getName()] - time();
                        $player->sendMessage("??l??c?????e Th???i Gian H???i K?? N??ng C??n ??a" . $cd . " Gi??y ! ??c???");
                    }else{
                        unset($this->cooldown[$player->getName()]);
                    }
                }
            }
        }
	}

    public function onHit(EntityDamageByEntityEvent $ev){
        $attack = $ev->getDamager();
        $entity = $ev->getEntity();
        if(!$attack instanceof Player){
            return true;
        }
        $inv = $attack->getPlayer()->getInventory();
        if($inv->getItemInHand()->getCustomName() == "??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???"){
            if($level >= 50){
                $item = Item::get(743, 0, 1);
                $lv = $this->getLevel($player)/2.5;
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                $lv = $this->getLevel($player)/2.5;
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                $lv = $this->getLevel($player)/2.5;
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                $lv = $this->getLevel($player)/2.5;
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), $lv));
                $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                $inv->setItemInHand($item);
                $packet = new PlaySoundPacket();
                $packet->soundName = "random.levelup";
                $packet->x = $player->getX();
                $packet->y = $player->getY();
                $packet->z = $player->getZ();
                $packet->volume = 1;
                $packet->pitch = 1;
                $player->sendDataPacket($packet);
            }else{
                $item = Item::get(276, 0, 1);
                $lv = $this->getLevel($player)/2.5;
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                $lv = $this->getLevel($player)/2.5;
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                $lv = $this->getLevel($player)/2.5;
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                $lv = $this->getLevel($player)/2.5;
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), $lv));
                $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                $inv->setItemInHand($item);
                $packet = new PlaySoundPacket();
                $packet->soundName = "random.levelup";
                $packet->x = $player->getX();
                $packet->y = $player->getY();
                $packet->z = $player->getZ();
                $packet->volume = 1;
                $packet->pitch = 1;
                $player->sendDataPacket($packet);
            }
        }
    }

    public function onDeath(PlayerDeathEvent $ev){
		$player = $ev->getPlayer();
		$cause = $player->getLastDamageCause();
		$level = $this->level->get(strtolower($player->getName()));
        $exp = $this->exp->get(strtolower($player->getName()));
        $nextexp = $this->nextexp->get(strtolower($player->getName()));
        $item = $player->getInventory()->getItemInHand();
        $name = $player->getName();
		if($cause instanceof EntityDamageByEntityEvent){
			$damager = $cause->getDamager();
			if($damager instanceof Player){
                if($item->getCustomName() === "??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???"){
				    $this->exp->set(strtolower($player->getName()), $exp+10);
                    $this->exp->save();
                }
			}
			if($exp >= $nextexp){
                $this->exp->set(strtolower($player->getName()), 0);
                $this->exp->save();
                $this->nextexp->set(strtolower($player->getName()), $nextexp+100);
                $this->level->set(strtolower($player->getName()), $level+1);
                $this->level->save();
                $money = $level * 1000;
                $this->money->addMoney($player, $money);
                $player->sendMessage("??l??c?????e B???n ???? Nh???n ???????c??a $money Xu ??eT??? Ph???n Th?????ng L??n C???p !");
                $token = 1;
                $this->token->addToken($player, $token);
                $player->sendMessage("??l??c?????e B???n ???? Nh???n ???????c??2 $token Tokens??eT??? Ph???n Th?????ng L??n C???p !");
                if(in_array($level, array(100, 200, 300, 400, 500, 600, 700, 800, 900, 1000, 1100, 1200, 1300, 1400, 1500, 1600, 1700, 1800, 1900, 2000, 2100, 2200, 2300, 2400, 2500, 2600, 2700, 2800, 2900, 3000, 3100, 3200, 3300, 3400, 3500, 3600, 3700, 3800, 3900, 4000, 4100, 4200, 4300, 4400, 45000, 4600, 4700, 4800, 4900, 5000, 5100, 5200, 5300, 5400, 5500, 5600, 5700, 5800, 5900, 6000, 6100, 6200, 6300, 6400, 6500, 6600, 6700, 6800, 6900, 7000, 7100, 7200, 7300, 7400, 7500, 7600, 7700, 7800, 7900, 8000, 8100, 8200, 8300, 8400, 8500, 8600, 8700, 8800, 8900, 9000, 9100, 9200, 9300, 9400, 9500, 9600, 9700, 9800, 9900, 10000))){
                    $credits = 1;
                    $this->credits->addCredits($player, $credits);
                    $player->sendMessage("??l??c?????e B???n ???? Nh???n ???????c??f $credits Credits??e T??? Ph???n Th?????ng L??n C???p !");
                }
                $this->getServer()->broadcastMessage("??l??c?????e D???ng C??? C???a Ng?????i Ch??i??b $name ??eV???a L??n C???p??a $level");
                $packet = new PlaySoundPacket();
                $packet->soundName = "random.levelup";
                $packet->x = $player->getPosition()->getX();
                $packet->y = $player->getPosition()->getY();
                $packet->z = $player->getPosition()->getZ();
                $packet->volume = 1;
                $packet->pitch = 1;
                $player->sendDataPacket($packet);
                $player->sendMessage("??l??c?????e Ch??c M???ng D???ng C??? C???a B???n ???? ?????t C???p??a $level");
                $player->addTitle("??l??c?????e D???ng C??? C???p:??b $level ??c???", "??l??9?????a Ch??c M???ng B???n ???? L??n Level ??9???");
                switch(mt_rand(1, 4)){
                    case 1:
                        if($level >= 50){
                            $item = Item::get(745, 0, 1);
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                            $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                            $inv->addItem($item);
                            $packet = new PlaySoundPacket();
                            $packet->soundName = "random.levelup";
                            $packet->x = $player->getX();
                            $packet->y = $player->getY();
                            $packet->z = $player->getZ();
                            $packet->volume = 1;
                            $packet->pitch = 1;
                            $player->sendDataPacket($packet);
                        }else{
                            $item = Item::get(278, 0, 1);
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                            $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                            $inv->addItem($item);
                            $packet = new PlaySoundPacket();
                            $packet->soundName = "random.levelup";
                            $packet->x = $player->getX();
                            $packet->y = $player->getY();
                            $packet->z = $player->getZ();
                            $packet->volume = 1;
                            $packet->pitch = 1;
                            $player->sendDataPacket($packet);
                        }
                    break;
                    case 2:
                        if($level >= 50){
                            $item = Item::get(746, 0, 1);
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                            $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                            $inv->addItem($item);
                            $packet = new PlaySoundPacket();
                            $packet->soundName = "random.levelup";
                            $packet->x = $player->getX();
                            $packet->y = $player->getY();
                            $packet->z = $player->getZ();
                            $packet->volume = 1;
                            $packet->pitch = 1;
                            $player->sendDataPacket($packet);
                        }else{
                            $item = Item::get(279, 0, 1);
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                            $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                            $inv->addItem($item);
                            $packet = new PlaySoundPacket();
                            $packet->soundName = "random.levelup";
                            $packet->x = $player->getX();
                            $packet->y = $player->getY();
                            $packet->z = $player->getZ();
                            $packet->volume = 1;
                            $packet->pitch = 1;
                            $player->sendDataPacket($packet);
                        }
                    break;
                    case 3:
                       if($level >= 50){
                            $item = Item::get(744, 0, 1);
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                            $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                            $inv->addItem($item);
                            $packet = new PlaySoundPacket();
                            $packet->soundName = "random.levelup";
                            $packet->x = $player->getX();
                            $packet->y = $player->getY();
                            $packet->z = $player->getZ();
                            $packet->volume = 1;
                            $packet->pitch = 1;
                            $player->sendDataPacket($packet);
                        }else{
                            $item = Item::get(277, 0, 1);
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                            $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                            $inv->addItem($item);
                            $packet = new PlaySoundPacket();
                            $packet->soundName = "random.levelup";
                            $packet->x = $player->getX();
                            $packet->y = $player->getY();
                            $packet->z = $player->getZ();
                            $packet->volume = 1;
                            $packet->pitch = 1;
                            $player->sendDataPacket($packet);
                        }
                    break;
                    case 4:
                        if($level >= 50){
                            $item = Item::get(743, 0, 1);
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), $lv));
                            $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                            $inv->addItem($item);
                            $packet = new PlaySoundPacket();
                            $packet->soundName = "random.levelup";
                            $packet->x = $player->getX();
                            $packet->y = $player->getY();
                            $packet->z = $player->getZ();
                            $packet->volume = 1;
                            $packet->pitch = 1;
                            $player->sendDataPacket($packet);
                        }else{
                            $item = Item::get(276, 0, 1);
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                            $lv = $this->getLevel($player)/2.5;
                            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), $lv));
                            $item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
                            $inv->addItem($item);
                            $packet = new PlaySoundPacket();
                            $packet->soundName = "random.levelup";
                            $packet->x = $player->getX();
                            $packet->y = $player->getY();
                            $packet->z = $player->getZ();
                            $packet->volume = 1;
                            $packet->pitch = 1;
                            $player->sendDataPacket($packet);
                        }
                    break;
                }
            }
		}
	}

	public function onItemHeld(PlayerItemHeldEvent $ev){
        $task = new PopupTask($this, $ev->getPlayer());
        $player = $ev->getPlayer();
        $this->tasks[$ev->getPlayer()->getId()] = $task;
        $this->getScheduler()->scheduleRepeatingTask($task, 20);
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool {
    	switch($cmd->getName()){
    		case "toollevel":
    		    if(!$sender instanceof Player){
    		    	$sender->sendMessage("??l??c?????e H??y S??? D???ng L???nh Trong Tr?? Ch??i !");
    		    	return true;
    		    }else{
    		    	$this->MenuToolLevel($sender);
    		    }
    		break;
            case "toptool":
                if(!$sender instanceof Player){
                    $sender->sendMessage("??l??c?????e H??y S??? D???ng L???nh Trong Tr?? Ch??i !");
                    return true;
                }else{
                    $this->TopDungCu($sender);
                }
            break;
            case "settool":
                if(!$sender->hasPermission("toollevel.settool")){
                    $sender->sendMessage("??l??c?????e C???p ????? D???ng C??? C???a B???n Ch??a ?????, B???n C???n ?????t ??aLevel 50 ??e????? S??? D???ng K?? N??ng N??y !");
                }else{
                    if(isset($args[0])){
                        if(isset($args[1])){
                            $player = $this->getServer()->getPlayer($args[0]);
                            if(!is_numeric($args[1])){
                              $sender->sendMessage("??l??c?????e S??? Level Set B???t Bu???c Ph???i L?? S??? !");
                              return true;
                            }
                            if(!$player instanceof Player){
                              $sender->sendMessage("??l??c?????e Ng?????i Ch??i??a " . $args[0] . "??e Kh??ng Online !");
                              return true;
                            }
                            $this->level->set(strtolower($player->getName()), $args[1]);
                            $this->level->save();
                            $this->exp->set(strtolower($player->getName()), 0);
                            $this->exp->save();
                            $this->nextexp->set(strtolower($player->getName()) , $args[1]*100);
                            $this->nextexp->save();
                            $sender->sendMessage("??l??c?????e ???? Ch???nh C???p D???ng C??? C???a??b " . $args[0] . "??e Th??nh C???p??a " . $args[1]);
                            $player->sendMessage("??l??c?????e C???p ????? D???ng C??? C???a B???n ???? ???????c Ch???nh Th??nh??a " . $args[1]);
                        }else{
                            $sender->sendMessage("??l??c?????e S??? D???ng:??b /settool <player> <level>");
                        }
                    }else{
                        $sender->sendMessage("??l??c?????e S??? D???ng:??b /settool <player> <level>");
                    }
                }
            break;
            case "pickaxeleveling":
                if(!$sender instanceof Player){
                    $sender->sendMessage("??l??c?????e H??y S??? D???ng L???nh Trong Tr?? Ch??i !");
                    return true;
                }
                if(!$sender->hasPermission("toollevel.pickaxeleveling")){
                    $sender->sendMessage("??l??c?????e B???n Kh??ng C?? Quy???n S??? D???ng L???nh N??y !");
                }else{
                    $this->MenuPickaxeLeveling($sender);
                }
            break;
            case "thor":
                $level = $this->level->get(strtolower($player->getName()));
                if($level >= 50){
                    $sender->sendMessage("??l??c?????e B???n C???n ?????t ??aLevel 50??e ????? C??i ?????t K?? N??ng N??y !");
                }else{
                    $this->SettingSkillThor($sender);
                }
            break;
            case "hactram":
                $level = $this->level->get(strtolower($player->getName()));
                if($level >= 100){
                    $sender->sendMessage("??l??c?????e B???n C???n ?????t ??aLevel 100??e ????? C??i ?????t K?? N??ng N??y !");
                }else{
                    $this->SettingSkillHacTram($sender);
                }
            break;
            case "bungno":
                $level = $this->level->get(strtolower($player->getName()));
                if($level >= 100){
                    $sender->sendMessage("??l??c?????e B???n C???n ?????t ??aLevel 100??e ????? C??i ?????t K?? N??ng N??y !");
                }else{
                    $this->SettingSkillBungNo($sender);
                }
            break;
    	}
    	return true;
    }

    public function MenuPickaxeLeveling($player){
        $form = new SimpleForm(function(Player $player, $data){
            if($data == null){
                return true;
            }
            switch($data){
                case 0:
                break;
                case 1:
                    $this->getPickaxeLeveling()->set(strtolower($player->getName()), true);
                    $this->getPickaxeLeveling()->save();
                    $this->getCooldown()->set(strtolower($player->getName()), 60);
                    $this->getCooldown()->save();
                    $player->sendMessage("??l??c?????e K?? N??ng??a PickaxeLeveling??e C???a B???n ???? ???????c??a B???t !");
                break;
                case 2:
                    $this->getPickaxeLeveling()->set(strtolower($player->getName()), false);
                    $this->getPickaxeLeveling()->save();
                    $this->getCooldown()->set(strtolower($player->getName()), 60);
                    $this->getCooldown()->save();
                    $player->sendMessage("??l??c?????e K?? N??ng??a PickaxeLeveling??e C???a B???n ???? ???????c??c T???t !");
                break;
            }
        });
        $form->setTitle("??l??c?????9 Menu PickaxeLeveling ??c???");
        $form->addButton("??l??c?????9 Tho??t K?? N??ng ??c???", 0, "textures/other/exit");
        $form->addButton("??l??c?????2 B???t K?? N??ng ??c???", 0, "textures/other/on");
        $form->addButton("??l??c?????4 T???t K?? N??ng ??c???", 0, "textures/other/off");
        $form->sendToPlayer($player);
    }

    public function MenuToolLevel($player){
    	$form = new SimpleForm(function(Player $player, $data){
            if($data == null){
                return true;
            }
            switch($data){
                case 0:
                break;
                case 1:
                    $this->NhanDungCu($player);
                break;
                case 2:
                    $this->MenuSkillToolLevel($player);
                break;
                case 3:
                    $this->SettingSkill($player);
                break;
                case 4:
                    $this->TopDungCu($player);
                break;
                case 5:
                    $this->MenuCachSuDung($player);
                break;
            }
        });
        $form->setTitle("??l??c?????9 Menu D???ng C??? ??c???");
        $form->addButton("??l??c?????9 Tho??t Menu ??c???", 0, "textures/other/exit");
        $form->addButton("??l??c?????9 Nh???n D???ng C??? ??c???", 0, "textures/other/pickaxe");
        $form->addButton("??l??c?????9 K?? N??ng ??c???", 0, "textures/other/skill");
        $form->addButton("??l??c?????9 C??i ?????t Skill ??c???", 0, "textures/other/file");
        $form->addButton("??l??c?????9 TOP D???ng C??? ??c???", 0, "textures/other/eletepass");
        $form->addButton("??l??c?????9 C??ch S??? D???ng ??c???", 0, "textures/other/help");
        $form->sendToPlayer($player);
    }

    public function SettingSkill($player){
        $form = new SimpleForm(function(Player $player, $data){
            if($data == null){
                $this->MenuToolLevel($player);
                return true;
            }
            switch($data){
                case 0:
                    $this->MenuToolLevel($player);
                break;
                case 1:
                    $level = $this->level->get(strtolower($player->getName()));
                    if($level >= 50){
                        $this->SettingSkillThor($player);
                    }else{
                        $player->sendMessage("??l??c?????e B???n Kh??ng ????? Level ????? C??i ?????t K?? N??ng N??y !");
                    }
                break;
                case 2:
                    $level = $this->level->get(strtolower($player->getName()));
                    if($level >= 100){
                        $this->SettingSkillHacTram($player);
                    }else{
                        $player->sendMessage("??l??c?????e B???n Kh??ng ????? Level ????? C??i ?????t K?? N??ng N??y !");
                    }
                break;
                case 3:
                    $level = $this->level->get(strtolower($player->getName()));
                    if($level >= 150){
                        $this->SettingSkillBungNo($player);
                    }else{
                        $player->sendMessage("??l??c?????e B???n Kh??ng ????? Level ????? C??i ?????t K?? N??ng N??y !");
                    }
                break;
            }
        });
        $form->setTitle("??l??c?????9 Menu ToolLevels ??c???");
        $form->addButton("??l??c?????9 Quay L???i ??c???");
        $form->addButton("??l??c?????9 R??u Thor ??c???");
        $form->addButton("??l??c?????9 H???c Tr???m ??c???");
        $form->addButton("??l??c?????9 B??ng N??? ??c???");
        $form->sendToPlayer($player);
    }

    public function getThor(){
        return $this->thor;
    }

    public function getHacTram(){
        return $this->hactram;
    }

    public function getBungNo(){
        return $this->bungno;
    }

    public function SettingSkillThor($player){
        $form = new SimpleForm(function(Player $player, $data){
            if($data == null){
                $this->SettingSkill($player);
                return true;
            }
            switch($data){
                case 0:
                    $this->SettingSkill($player);
                break;
                case 1:
                    $this->getThor()->set(strtolower($player->getName()), true);
                    $this->getThor()->save();
                    $player->sendMessage("??l??c?????e K?? N??ng ??bThor??e C???a B???n ???? ???????c??a B???t.");
                break;
                case 2:
                    $this->getThor()->set(strtolower($player->getName()), false);
                    $this->getThor()->save();
                    $player->sendMessage("??l??c?????e K?? N??ng ??bThor??e C???a B???n ???? ???????c??c T???t.");
                break;
            }
        });
        $form->setTitle("??l??c?????9 Menu ToolLevels ??c???");
        $form->addButton("??l??c?????9 Quay L???i ??c???", 0, "textures/other/exit");
        $form->addButton("??l??c?????2 B???t K?? N??ng ??c???", 0, "textures/other/on");
        $form->addButton("??l??c?????4 T???t K?? N??ng ??c???", 0, "textures/other/off");
        $form->sendToPlayer($player);
    }

    public function SettingSkillHacTram($player){
        $form = new SimpleForm(function(Player $player, $data){
            if($data == null){
                $this->SettingSkill($player);
                return true;
            }
            switch($data){
                case 0:
                    $this->SettingSkill($player);
                break;
                case 1:
                    $this->getHacTram()->set(strtolower($player->getName()), true);
                    $this->getHacTram()->save();
                    $player->sendMessage("??l??c?????e K?? N??ng ??bH???c Tr???m??e C???a B???n ???? ???????c??a B???t.");
                break;
                case 2:
                    $this->getHacTram()->set(strtolower($player->getName()), false);
                    $this->getHacTram()->save();
                    $player->sendMessage("??l??c?????e K?? N??ng ??bH???c Tr???m??e C???a B???n ???? ???????c??c T???t.");
                break;
            }
        });
        $form->setTitle("??l??c?????9 Menu ToolLevels ??c???");
        $form->addButton("??l??c?????9 Quay L???i ??c???", 0, "textures/other/exit");
        $form->addButton("??l??c?????2 B???t K?? N??ng ??c???", 0, "textures/other/on");
        $form->addButton("??l??c?????4 T???t K?? N??ng ??c???", 0, "textures/other/off");
        $form->sendToPlayer($player);
    }

    public function SettingSkillBungNo($player){
        $form = new SimpleForm(function(Player $player, $data){
            if($data == null){
                $this->SettingSkill($player);
                return true;
            }
            switch($data){
                case 0:
                    $this->SettingSkill($player);
                break;
                case 1:
                    $this->getBungNo()->set(strtolower($player->getName()), true);
                    $this->getBungNo()->save();
                    $player->sendMessage("??l??c?????e K?? N??ng ??bB??ng N?????e C???a B???n ???? ???????c??a B???t.");
                break;
                case 2:
                    $this->getBungNo()->set(strtolower($player->getName()), false);
                    $this->getBungNo()->save();
                    $player->sendMessage("??l??c?????e K?? N??ng ??bB??ng N?????e C???a B???n ???? ???????c??c T???t.");
                break;
            }
        });
        $form->setTitle("??l??c?????9 Menu ToolLevels ??c???");
        $form->addButton("??l??c?????9 Quay L???i ??c???", 0, "textures/other/exit");
        $form->addButton("??l??c?????2 B???t K?? N??ng ??c???", 0, "textures/other/on");
        $form->addButton("??l??c?????4 T???t K?? N??ng ??c???", 0, "textures/other/off");
        $form->sendToPlayer($player);
    }

    public function MenuSkillToolLevel($player){
        $level = $this->level->get(strtolower($player->getName()));
        $form = new SimpleForm(function(Player $player, $data){
            if($data == null){
                $this->MenuToolLevel($player);
                return true;
            }   
            switch($data){
                case 0:
                    $this->MenuToolLevel($player);
                break;
                case 1:
                    if(!$player->hasPermission("toollevel.pickaxeleveling")){
                        $player->sendMessage("??l??c?????e C???p ????? D???ng C??? C???a B???n Ch??a ?????, B???n C???n ?????t ??aLevel 50 ??e????? S??? D???ng K?? N??ng N??y !");
                            $level = $this->level->get(strtolower($player->getName()));
                    }else{
                        $this->MenuPickaxeLeveling($player);
                    }
                break;
                case 2:
                    $level = $this->level->get(strtolower($player->getName()));
                    if($level >= 50){
                        $this->HowToUseSkillThor($player);
                    }else{
                        $player->sendMessage("??l??c?????e B???n C???n ?????t ??aLevel 50 ??e????? S??? D???ng K?? N??ng ??bR??u Thor.");
                        $packet = new PlaySoundPacket();
                        $packet->soundName = "random.explode";
                        $packet->x = $player->getX();
                        $packet->y = $player->getY();
                        $packet->z = $player->getZ();
                        $packet->volume = 1;
                        $packet->pitch = 1;
                        $player->sendDataPacket($packet);
                    }
                break;
                case 3:
                    $level = $this->level->get(strtolower($player->getName()));
                    if($level >= 100){
                        $this->HowtoUseSkillHacTram($player);
                    }else{
                        $player->sendMessage("??l??c?????e B???n C???n ?????t ??aLevel 100??e ????? S??? D???ng K?? N??ng??b H???c Tr???m.");
                        $packet = new PlaySoundPacket();
                        $packet->soundName = "random.explode";
                        $packet->x = $player->getX();
                        $packet->y = $player->getY();
                        $packet->z = $player->getZ();
                        $packet->volume = 1;
                        $packet->pitch = 1;
                        $player->sendDataPacket($packet);
                    }
               break;
               case 4:
                    $level = $this->level->get(strtolower($player->getName()));
                    if($level >= 150){
                        $this->HowToUseSkillBungNo($player);
                    }else{
                        $player->sendMessage("??l??c?????e B???n C???n ?????t ??aLevel 150 ??e????? S??? D???ng K?? N??ng ??bR??u Thor.");
                        $packet = new PlaySoundPacket();
                        $packet->soundName = "random.explode";
                        $packet->x = $player->getX();
                        $packet->y = $player->getY();
                        $packet->z = $player->getZ();
                        $packet->volume = 1;
                        $packet->pitch = 1;
                        $player->sendDataPacket($packet);
                    }
               break;
            }
        });
        $form->setTitle("??l??c?????9 Menu ToolLevels ??c???");
        $form->addButton("??l??c?????9 Quay L???i ??c???");
        $pickaxeleveling = ($player->hasPermission("toollevel.pickaxeleveling") ? "??l??2???? M??? Kh??a" : "??l??4Ch??a M??? Kh??a");
        $form->addButton("??l??c?????9 K?? N??ng PickaxeLeveling ??c???\n??l??0Tr???ng Th??i: $pickaxeleveling");
        $thor = ($level >= 50 ? "??l??2???? M??? Kh??a" : "??l??4Ch??a M??? Kh??a");
        $form->addButton("??l??c?????9 K?? N??ng R??u Thor ??c???\n??l??0Tr???ng Th??i: $thor");
        $hactram = ($level >= 100 ? "??l??2???? M??? Kh??a" : "??l??4Ch??a M??? Kh??a");
        $form->addButton("??l??c?????9 K?? N??ng H???c Tr???m ??c???\n??l??0Tr???ng Th??i: $hactram");
        $hactram = ($level >= 150 ? "??l??2???? M??? Kh??a" : "??l??4Ch??a M??? Kh??a");
        $form->addButton("??l??c?????9 K?? N??ng B??ng N??? ??c???\n??l??0Tr???ng Th??i: $bungno");
        $form->sendToPlayer($player);
    }

    public function HowToUseSkillThor($player){
        $form = new SimpleForm(function(Player $player, $data){
            if($data == null){
                $this->MenuSkillToolLevel($player);
                return true;
            }
            switch($data){
                case 0:
                    $this->MenuSkillToolLevel($player);
                break;
            }
        });
        $form->setTitle("??l??c?????9 Menu D???ng C??? ??c???");
        $form->setContent("??l??c?????e ????? S??? D???ng Skill Thor, H??y C???m D???ng C??? V?? Nh???n V??o N??i B???n Mu???n Cho S??t ????nh !");
        $form->addButton("??l??c?????9 Quay L???i ??c???");
        $form->sendToPlayer($player);
    }

    public function HowtoUseSkillHacTram($player){
        $form = new SimpleForm(function(Player $player, $data){
            if($data == null){
                $this->MenuSkillToolLevel($player);
                return true;
            }
            switch($data){
                case 0:
                    $this->MenuSkillToolLevel($player);
                break;
            }
        });
        $form->setTitle("??l??c?????9 Menu D???ng C??? ??c???");
        $form->setContent("??l??c?????e ????? S??? D???ng Skill H???c Tr???m, H??y C???m D???ng C??? V?? Gi??? V??o N??i B???n Mu???n Tr???m V??o !");
        $form->addButton("??l??c?????9 Quay L???i ??c???");
        $form->sendToPlayer($player);
    }

    public function HowToUseSkillBungNo($player){
        $form = new SimpleForm(function(Player $player, $data){
            if($data == null){
                $this->MenuSkillToolLevel($player);
                return true;
            }
            switch($data){
                case 0:
                    $this->MenuSkillToolLevel($player);
                break;
            }
        });
        $form->setTitle("??l??c?????9 Menu D???ng C??? ??c???");
        $form->setContent("??l??c?????e ????? S??? D???ng Skill B??ng N???, H??y C???m D???ng C??? V?? Gi???, Xung Quanh B???n S??? N??? Tung");
        $form->addButton("??l??c?????9 Quay L???i ??c???");
        $form->sendToPlayer($player);
    }

    public function NhanDungCu($player){
    	$inv = $player->getInventory();
    	$name = $player->getName();
    	$level = $this->level->get(strtolower($player->getName()));
    	switch(mt_rand(1, 4)){
    		case 1:
    		    if($level >= 50){
    		    	$item = Item::get(745, 0, 1);
    		    	$lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
    		    	$item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
    		    	$inv->addItem($item);
    		    	$packet = new PlaySoundPacket();
		            $packet->soundName = "random.levelup";
		            $packet->x = $player->getX();
		            $packet->y = $player->getY();
		            $packet->z = $player->getZ();
		            $packet->volume = 1;
		            $packet->pitch = 1;
		            $player->sendDataPacket($packet);
    		    }else{
    		    	$item = Item::get(278, 0, 1);
    		    	$lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
    		    	$item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
    		    	$inv->addItem($item);
    		    	$packet = new PlaySoundPacket();
		            $packet->soundName = "random.levelup";
		            $packet->x = $player->getX();
		            $packet->y = $player->getY();
		            $packet->z = $player->getZ();
		            $packet->volume = 1;
		            $packet->pitch = 1;
		            $player->sendDataPacket($packet);
    		    }
    		break;
    		case 2:
    		    if($level >= 50){
    		    	$item = Item::get(746, 0, 1);
    		    	$lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
    		    	$item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
    		    	$inv->addItem($item);
    		    	$packet = new PlaySoundPacket();
		            $packet->soundName = "random.levelup";
		            $packet->x = $player->getX();
		            $packet->y = $player->getY();
		            $packet->z = $player->getZ();
		            $packet->volume = 1;
		            $packet->pitch = 1;
		            $player->sendDataPacket($packet);
    		    }else{
    		    	$item = Item::get(279, 0, 1);
    		    	$lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
    		    	$item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
    		    	$inv->addItem($item);
    		    	$packet = new PlaySoundPacket();
		            $packet->soundName = "random.levelup";
		            $packet->x = $player->getX();
		            $packet->y = $player->getY();
		            $packet->z = $player->getZ();
		            $packet->volume = 1;
		            $packet->pitch = 1;
		            $player->sendDataPacket($packet);
    		    }
    		break;
    		case 3:
    		   if($level >= 50){
    		    	$item = Item::get(744, 0, 1);
    		    	$lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
    		    	$item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
    		    	$inv->addItem($item);
    		    	$packet = new PlaySoundPacket();
		            $packet->soundName = "random.levelup";
		            $packet->x = $player->getX();
		            $packet->y = $player->getY();
		            $packet->z = $player->getZ();
		            $packet->volume = 1;
		            $packet->pitch = 1;
		            $player->sendDataPacket($packet);
    		    }else{
    		    	$item = Item::get(277, 0, 1);
    		    	$lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
    		    	$item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
    		    	$inv->addItem($item);
    		    	$packet = new PlaySoundPacket();
		            $packet->soundName = "random.levelup";
		            $packet->x = $player->getX();
		            $packet->y = $player->getY();
		            $packet->z = $player->getZ();
		            $packet->volume = 1;
		            $packet->pitch = 1;
		            $player->sendDataPacket($packet);
    		    }
    		break;
    		case 4:
    		    if($level >= 50){
    		    	$item = Item::get(743, 0, 1);
    		    	$lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), $lv));
    		    	$item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
    		    	$inv->addItem($item);
    		    	$packet = new PlaySoundPacket();
		            $packet->soundName = "random.levelup";
		            $packet->x = $player->getX();
		            $packet->y = $player->getY();
		            $packet->z = $player->getZ();
		            $packet->volume = 1;
		            $packet->pitch = 1;
		            $player->sendDataPacket($packet);
    		    }else{
    		    	$item = Item::get(276, 0, 1);
    		    	$lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), $lv));
                    $lv = $this->getLevel($player)/2.5;
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), $lv));
    		    	$item->setCustomName("??l??c?????e D???ng C??? C???a??b $name ??c|??e C???p ?????:??a $level ??c???");
    		    	$inv->addItem($item);
    		    	$packet = new PlaySoundPacket();
		            $packet->soundName = "random.levelup";
		            $packet->x = $player->getX();
		            $packet->y = $player->getY();
		            $packet->z = $player->getZ();
		            $packet->volume = 1;
		            $packet->pitch = 1;
		            $player->sendDataPacket($packet);
    		    }
    		break;
    	}
    }

    public function TopDungCu($player){
		$lv = $this->level->getAll();
		$message = "";
		$message1 = "";
		if(count($lv) > 0){
			arsort($lv);
			$i = 1;
			foreach($lv as $name => $level){
				$message .= "??l??c?????e TOP??d " . $i . " ??b" . $name . " ??c?????f C???p??a " . $level . "\n";
				if($name == $player->getName())$xh=$i;
				if($i == 1000)break;
				++$i;
			}
		}
		$form = new SimpleForm(function(Player $player, $data){
			if($data == null){
				return true;
		    }
		    switch($data){
		    	case 0:
		    	    $packet = new PlaySoundPacket();
		            $packet->soundName = "random.click";
		            $packet->x = $player->getX();
		            $packet->y = $player->getY();
		            $packet->z = $player->getZ();
		            $packet->volume = 1;
		            $packet->pitch = 1;
		            $player->sendDataPacket($packet);
		    	break;
		    }
		});
		$form->setTitle("??l??c?????9 Menu D???ng C??? ??c???");
		$form->setContent("??l??c?????e TOP D???ng C??? Trong Server:\n$message ");
		$form->addButton("??l??c?????9 Tho??t Menu ??c???", 0, "textures/other/exit");
		$form->sendToPlayer($player);
		return true;
    }

    public function MenuCachSuDung($player){
    	$form = new SimpleForm(function(Player $player, $data){
    		if($data == null){
    			return true;
    		}
    		switch($data){
    			case 0:
    			    $packet = new PlaySoundPacket();
		            $packet->soundName = "random.click";
		            $packet->x = $player->getX();
		            $packet->y = $player->getY();
		            $packet->z = $player->getZ();
		            $packet->volume = 1;
		            $packet->pitch = 1;
		            $player->sendDataPacket($packet);
    			break;
    		}
    	});
    	$form->setTitle("??l??c?????9 Menu D???ng C??? ??c???");
    	$form->setContent("??l??c?????e C??ch S??? D???ng ??aD???ng C???:\n??l??c?????e ????o Ho???c Gh???t Ng?????i ????? L??n Level D???ng C???\n??l??c?????e Khi D???ng C??? L??n Level 50, Ch??ng S??? C?????ng h??a Th??nh ????? ??bNetherite\n??l??c?????e Khi ????? Level Theo D???ng C???, D???ng C??? S??? M??? Kh??a C??c Skill !\n??l??c?????e D???ng C??? C?? Kh??? N??ng Bi???n ?????i C???c Ch???t\n??l??c?????e Khi L??n Level B???n S??? Nh???n ???????c Nhi???u Ph???n Th?????ng L???n !\n??l??c?????e C??? M???i 100 Level B???n S??? Nh???n ???????c??f 1 Credits\n??l??c?????e Khi X??i Skill, B???n Ch??? Vi???c ????o, Gi???, Ho???c Nh???n Xu???ng ?????t !");
    	$form->addButton("??l??c?????9 Tho??t Menu ??c???");
    }

    public function getLevel($player){
        if($player instanceof Player){
           $name = $player->getName();
        }
        $level = $this->level->get(strtolower($player->getName()));
        return $level;
    }

    public function getExp($player){
        if($player instanceof Player){
            $name = $player->getName();
        }

        $exp = $this->exp->get(strtolower($player->getName()));
        return $exp;
    }

    public function getNextExp($player){
        if($player instanceof Player){
            $name = $player->getName();
        }

        $nextexp = $this->nextexp->get(strtolower($player->getName()));
        return $nextexp;
    }

    public function getCap($player){
        $lv = $this->level->get(strtolower($player->getName()));
        $cap = "Th?????ng";
        if($lv >= 50) $cap = "Ma L??i";
        if($lv >= 100) $cap = "Ma V????ng";
        if($lv >= 150) $cap = "Thi??n V????ng";
        if($lv >= 200) $cap = "Thi??n L??i";
        if($lv >= 250) $cap = "Th???y V????ng";
        if($lv >= 300) $cap = "H???a V????ng";
        if($lv >= 350) $cap = "Th???n V????ng";
        if($lv >= 450) $cap = "Th??nh V????ng";
        if($lv >= 500) $cap = "Di??m V????ng";
        if($lv >= 550) $cap = "V????ng Th???y";
        if($lv >= 600) $cap = "V????ng H???a";
        if($lv >= 650) $cap = "Th???y L??i";
        if($lv >= 700) $cap = "H???a L??i";
        if($lv >= 750) $cap = "Th???y Long";
        if($lv >= 800) $cap = "H???a Long";
        if($lv >= 850) $cap = "Kh??c Long";
        if($lv >= 900) $cap = "B???o Long";
        if($lv >= 950) $cap = "B???ch Long";
        if($lv >= 1000) $cap = "H???c Long";
        return $cap;
    }
}