<?php

namespace RpgEtEb;

// 플러그인
use pocketmine\plugin\PluginBase;
// 이벤트
use pocketmine\event\Listener;
// 이벤트 플레이어
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerQuitEvent;
// 이벤트 엔티티
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
// 이벤트 블럭
use pocketmine\event\block\BlockBreakEvent;
// 커맨드
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
// 엔티티
use pocketmine\entity\Entity;
use pocketmine\entity\Arrow;
use pocketmine\entity\Zombie;
use pocketmine\entity\Egg;
use pocketmine\entity\Snowball;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
// 객체
use pocketmine\Player;
// 유틸
use pocketmine\utils\Config;
use pocketmine\utils\Utils;
// 레벨
use pocketmine\level\Position;
use pocketmine\level\Explosion;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\SnowballPoofParticle;
// 백터
use pocketmine\math\Vector3;
// 연동
use onebone\economyapi\EconomyAPI;

class RpgEtEb extends PluginBase implements Listener {
   private $economy;
   public function onEnable() {
      @mkdir ( $this->getDataFolder () );
      $this->player = new Config ( $this->getDataFolder () . "players.yml", Config::YAML );
      $this->pldb = $this->player->getAll ();
      if ($this->getServer ()->getPluginManager ()->getPlugin ( "EconomyAPI" ) !== null) {
         $this->economy = EconomyAPI::getInstance ();
      } else {
         $this->getServer ()->getLogger ()->warning ( "EconomyAPI 플러그인이 없기에 비활성화 됩니다." );
         $this->getServer ()->getPluginManager ()->disablePlugin ( $this );
      }
      $this->getServer ()->getPluginManager ()->registerEvents ( $this, $this );
   }
   public function WriteInfo(PlayerJoinEvent $event) {
      $player = $event->getPlayer ();
      $name = $player->getName ();
      if (! isset ( $this->pldb [strtolower ( $name )] )) {
         $this->pldb [strtolower ( $name )] ["전직"] = "없음";
         $this->pldb [strtolower ( $name )] ["스킬템"] = "미지급";
         $this->pldb [strtolower ( $name )] ["레벨"] = 1;
         $this->pldb [strtolower ( $name )] ["점프횟수"] = 0;
         $this->pldb [strtolower ( $name )] ["일반스킬"] = "이용가능";
         $this->pldb [strtolower ( $name )] ["궁스킬"] = "이용가능";
         $this->pldb [strtolower ( $name )] ["체력"] = 0;
         $this->pldb [strtolower ( $name )] ["데미지"] = 0;
         $this->pldb [strtolower ( $name )] ["20렙"] = "미수령";
         $this->pldb [strtolower ( $name )] ["30렙"] = "미수령";
         $this->pldb [strtolower ( $name )] ["40렙"] = "미수령";
         $this->pldb [strtolower ( $name )] ["50렙"] = "미수령";
         $this->pldb [strtolower ( $name )] ["60렙"] = "미수령";
         $this->save ();
      }
      $max = $player->getMaxHealth ();
      $event->getPlayer ()->setMaxHealth ( $max + $this->pldb [strtolower ( $name )] ["체력"] );
   }
   public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
      $command = $command->getName ();
      $name = $sender->getName ();
      $player = $sender->getPlayer ();
      $tag = "§d§l[ §f시스템 §d]§f";
      if ($command == "레벨") {
         if (! isset ( $args [0] )) {
            $sender->sendMessage ( $tag . " /레벨 관리 [ 닉네임 ] [ 레벨 ]( 플레이어 레벨을 설정 합니다.) " );
            return true;
         }
         switch ($args [0]) {
            case "관리" :
               if (! isset ( $args [1] )) {
                  $sender->sendMessage ( $tag . " /레벨 관리 [ 닉네임 ] [ 레벨 ]( 플레이어 레벨을 설정 합니다.) " );
                  return true;
               }
               switch ($args [1]) {
                  case $args [1] :
                     if (! isset ( $args [2] )) {
                        $sender->sendMessage ( $tag . " /레벨 관리 [ 닉네임 ] [ 레벨 ]( 플레이어 레벨을 설정 합니다.) " );
                        return true;
                     }
                     switch ($args [2]) {
                        case $args [2] :
                           if ($this->pldb [strtolower ( $args [1] )]) {
                              $sender->sendMessage ( $tag . " 해당 플레이어의 레벨을 설정했습니다. " );
                              $sender->sendMessage ( $tag . " 해당 플레이어의 레벨은 §b§l" . $args [2] . " §f§l로 설정되었습니다." );
                              $this->pldb [strtolower ( $args [1] )] ["레벨"] = $args [2];
                              $this->save ();
                              return true;
                           } else {
                              $sender->sendMessage ( $tag . " 그런 플레이어는 없습니다." );
                           }
                     }
               }
         }
      } // 레벨 커맨드
      if ($command == "체력사과") {
         if (! isset ( $args [0] )) {
            $sender->sendMessage ( $tag . " /체력사과 지급 ( 체력을 증가시키는 템이 지급 됩니다. ) " );
            return true;
         }
         switch ($args [0]) {
            case "지급" :
               if (! $sender->isOp ()) {
                  $sender->sendMessage ( $tag . "권한이 없습니다." );
                  return true;
               }
               $newitem = new Item ( 260, 0, 1 );
               $newitem->setCustomName ( "§e[§f 체력+§e ]§r" );
               $newitem->setLore ( [ 
                     "§f체력을 1 증가시켜줍니다" 
               ] );
               $sender->getInventory ()->addItem ( $newitem );
               $sender->getInventory ()->removeItem ( Item::get ( 399, 0, 1 ) );
               $sender->sendMessage ( $tag . "인벤토리를 확인해주세요" );
               break;
         }
      }
      if ($command == "스킬템") {
         if (! isset ( $args [0] )) {
            $sender->sendMessage ( $tag . " /스킬템 지급 ( 스킬템이 지급 됩니다. ) " );
            return true;
         }
         switch ($args [0]) {
            case "지급" :
               if ($this->pldb [strtolower ( $name )] ["전직"] == "빛정령") {
                  if ($this->pldb [strtolower ( $name )] ["스킬템"] == "미지급") {
                     $a = Item::get ( 377, 0, 1 );
                     $a->setCustomName ( "§f§l빛정령 스킬템 ( 일반 )" );
                     $a->setLore ( [ 
                           "§c§l땅 터치시 상대방이 재생 2 구속1  4초간부여 대신 자신의 피 3칸이 깍김 \n쿨타임 : 50초" 
                     ] );
                     $sender->sendMessage ( $tag . " §f§l스킬템을 얻었습니다." );
                     $sender->getInventory ()->addItem ( $a );
                     $b = Item::get ( 369, 0, 1 );
                     $b->setCustomName ( "§f§l빛정령 스킬템 ( 궁 )" );
                     $b->setLore ( [ 
                           "§c§l땅 터치시 주변에 폭발이 일어나며 \n상대방의 피를 줄이고 구속 3초를 부여합니다.\n쿨타임 : 70초" 
                     ] );
                     $sender->sendMessage ( $tag . " §f§l스킬템을 얻었습니다." );
                     $sender->getInventory ()->addItem ( $b );
                     $this->pldb [strtolower ( $name )] ["스킬템"] = "지급";
                     $this->save ();
                     return true;
                  } else {
                     $sender->sendMessage ( $tag . " §f§l당신은 벌써 스킬템을 지급 받았습니다." );
                  }
               } else {
                  $sender->sendMessage ( $tag . " §f§l당신은 전직을 해야 스킬템을 지급 받을 수 있습니다." );
               }
               if ($this->pldb [strtolower ( $name )] ["전직"] == "흑정령") {
                  if ($this->pldb [strtolower ( $name )] ["스킬템"] == "미지급") {
                     $a = Item::get ( 377, 0, 1 );
                     $a->setCustomName ( "§f§l흑정령 스킬템 ( 일반 )" );
                     $a->setLore ( [ 
                           "§c§l땅 터치시 주변에 있는 플레이어들에게 구속과 독을 5초식 부여합니다.\n쿨타임 : 50초" 
                     ] );
                     $sender->sendMessage ( $tag . " §f§l스킬템을 얻었습니다." );
                     $sender->getInventory ()->addItem ( $a );
                     $b = Item::get ( 369, 0, 1 );
                     $b->setCustomName ( "§f§l흑정령 스킬템 ( 궁 )" );
                     $b->setLore ( [ 
                           "§c§l땅 터치시 자신에게 힘 신속 실명 을 부여합니다.\n쿨타임 : 20초" 
                     ] );
                     $sender->sendMessage ( $tag . " §f§l스킬템을 얻었습니다." );
                     $sender->getInventory ()->addItem ( $b );
                     $this->pldb [strtolower ( $name )] ["스킬템"] = "지급";
                     $this->save ();
                     return true;
                  } else {
                     $sender->sendMessage ( $tag . " §f§l당신은 벌써 스킬템을 지급 받았습니다." );
                  }
               } else {
                  $sender->sendMessage ( $tag . " §f§l당신은 전직을 해야 스킬템을 지급 받을 수 있습니다." );
               }
               break;
         }
      } // 전직권 커맨드
      if ($command == "전직권") {
         if (! isset ( $args [0] )) {
            $sender->sendMessage ( $tag . " /전직권 발급 ( 250만원이 사용 됩니다. ) " );
            return true;
         }
         switch ($args [0]) {
            case "발급" :
               if ($this->economy->myMoney ( $player ) < 2500000) {
                  $sender->sendMessage ( $tag . " §f§l돈이 부족합니다." );
               } else {
                  $ssg = Item::get ( 351, 0, 1 );
                  $ssg->setCustomName ( "§f§l전직권" );
                  $ssg->setLore ( [ 
                        "§c§l땅터치시 당신의 전직 정보가 초기화가 됩니다." 
                  ] );
                  $sender->sendMessage ( $tag . " §f§l전직권을 얻었습니다." );
                  $sender->getInventory ()->addItem ( $ssg );
                  $this->economy->reduceMoney ( $player, 2500000 );
                  return true;
               }
         }
      } // 전직권 커맨드
      if ($command == "레벨업권") {
         if (! isset ( $args [0] )) {
            $sender->sendMessage ( $tag . " /레벨업권 지급 " );
            return true;
         }
         switch ($args [0]) {
            case "지급" :
               if (! $sender->isOp ()) {
                  $sender->sendMessage ( $tag . "권한이 없습니다." );
                  return true;
               }
               $ssg = Item::get ( 351, 1, 1 );
               $ssg->setCustomName ( "§f§l레벨업권" );
               $ssg->setLore ( [ 
                     "§c§l땅터치시 당신의 레벨이 상승 됩니다." 
               ] );
               $sender->sendMessage ( $tag . " §f§l레벨업권을 얻었습니다." );
               $sender->getInventory ()->addItem ( $ssg );
               break;
         }
      } // 전직권 커맨드
      if ($command == "흑정령") {
         if (! isset ( $args [0] )) {
            $sender->sendMessage ( $tag . " /흑정령 전직 ( 변경 가능 ) " );
            return true;
         }
         switch ($args [0]) {
            case "전직" :
               if ($this->pldb [strtolower ( $name )] ["전직"] == "없음") {
                  $sender->sendMessage ( $tag . " 당신은 흑정령으로 전직 하셨습니다. " );
                  $this->pldb [strtolower ( $name )] ["전직"] = "흑정령";
                  $this->save ();
                  return true;
               } else {
                  $sender->sendMessage ( $tag . " 당신은 벌써 전직을 했습니다." );
               }
         }
      } // 흑정령 커맨드
      if ($command == "빛정령") {
         if (! isset ( $args [0] )) {
            $sender->sendMessage ( $tag . " /빛정령 전직 ( 변경 가능 ) " );
            return true;
         }
         switch ($args [0]) {
            case "전직" :
               if ($this->pldb [strtolower ( $name )] ["전직"] == "없음") {
                  $sender->sendMessage ( $tag . " 당신은 빛정령으로 전직 하셨습니다. " );
                  $this->pldb [strtolower ( $name )] ["전직"] = "빛정령";
                  $this->save ();
                  return true;
               } else {
                  $sender->sendMessage ( $tag . " 당신은 벌써 전직을 했습니다." );
               }
         }
      } // 빛정령 커맨드
      if ($command == "정보") {
         if (! isset ( $args [0] )) {
            $sender->sendMessage ( $tag . " 나의 정보 " . "\n{$tag} 직업 : " . $this->pldb [strtolower ( $name )] ["전직"] . "\n{$tag} 레벨 : " . $this->pldb [strtolower ( $name )] ["레벨"] . "\n{$tag} 점프횟수 : " . $this->pldb [strtolower ( $name )] ["점프횟수"] );
            return true;
         } // 정보
      }
      return true;
   }
   public function EntityDamage(EntityDamageEvent $event) {
      $entity = $event->getEntity ();
      if ($event instanceof EntityDamageByEntityEvent) {
         $tag = "§d§l[ §f시스템 §d]§f";
         $damager = $event->getDamager ();
         if ($damager instanceof Player) {
            if ($entity instanceof Player) {
               $damage = $this->pldb [strtolower ( $name )] ["데미지"];
               $event->setBaseDamage ( $damager + $damage );
            }
         }
      }
   }
   public function RegisterJobKind(PlayerMoveEvent $event) {
      $player = $event->getPlayer ();
      $name = $player->getName ();
      $tag = "§d§l[ §f시스템 §d]§f";
      if ($this->pldb [strtolower ( $name )] ["전직"] == "없음") {
         $player->sendPopup ( $tag . " 당신은 전직을 해야만 움직일 수 있습니다. /흑정령 or /빛정령" );
         $event->setCancelled ();
      }
   }
   public function jump(PlayerJumpEvent $event) {
      $player = $event->getPlayer ();
      $name = $player->getName ();
      $tag = "§d§l[ §f시스템 §d]§f";
      if ($this->pldb [strtolower ( $name )] ["점프횟수"] <= 2499) {
         if ($this->pldb [strtolower ( $name )] ["20렙"] == "미수령" && $this->pldb [strtolower ( $name )] ["레벨"] >= 20) {
            $player->sendMessage ( $tag . " 당신은 20렙이 되어 데미지가 1 상승 하셨습니다." );
            $this->pldb [strtolower ( $name )] ["20렙"] = "수령완료";
            $this->pldb [strtolower ( $name )] ["데미지"] += 1;
            $this->save ();
            return true;
         }
         if ($this->pldb [strtolower ( $name )] ["30렙"] == "미수령" && $this->pldb [strtolower ( $name )] ["레벨"] >= 30) {
            $player->sendMessage ( $tag . " 당신은 30렙이 되어 데미지가 2 상승 하셨습니다." );
            $this->pldb [strtolower ( $name )] ["30렙"] = "수령완료";
            $this->pldb [strtolower ( $name )] ["데미지"] += 2;
            $this->save ();
            return true;
         }
         if ($this->pldb [strtolower ( $name )] ["40렙"] == "미수령" && $this->pldb [strtolower ( $name )] ["레벨"] >= 49) {
            $player->sendMessage ( $tag . " 당신은 40렙이 되어 피가 2칸 증가 하셨습니다." );
            if ($player->getMaxHealth () >= 300) {
               $player->sendMessage ( $tag . " 체력은 300까지 밖에 올릴 수 없습니다" );
               return true;
            }
            $max = $player->getMaxHealth ();
            $event->getPlayer ()->setMaxHealth ( $max + 4 );
            $this->pldb [strtolower ( $name )] ["40렙"] = "수령완료";
            $this->pldb [strtolower ( $name )] ["체력"] += 4;
            $this->save ();
            return true;
         }
         if ($this->pldb [strtolower ( $name )] ["50렙"] == "미수령" && $this->pldb [strtolower ( $name )] ["레벨"] >= 50) {
            $player->sendMessage ( $tag . " 당신은 50렙이 되어 데미지가 4 상승 하셨습니다." );
            $this->pldb [strtolower ( $name )] ["50렙"] = "수령완료";
            $this->pldb [strtolower ( $name )] ["데미지"] += 4;
            $this->save ();
            return true;
         }
         if ($this->pldb [strtolower ( $name )] ["60렙"] == "미수령" && $this->pldb [strtolower ( $name )] ["레벨"] >= 60) {
            $player->sendMessage ( $tag . " 당신은 60렙이 되어 피가 5칸 증가 하셨습니다." );
            if ($player->getMaxHealth () >= 300) {
               $player->sendMessage ( $tag . " 체력은 300까지 밖에 올릴 수 없습니다" );
               return true;
            }
            $max = $player->getMaxHealth ();
            $event->getPlayer ()->setMaxHealth ( $max + 10 );
            $this->pldb [strtolower ( $name )] ["60렙"] = "수령완료";
            $this->pldb [strtolower ( $name )] ["체력"] += 10;
            $this->save ();
            return true;
         }
         $player->sendPopup ( $tag . " 점프횟수 +1" );
         $this->pldb [strtolower ( $name )] ["점프횟수"] += 1;
         return true;
      } else {
         $player->sendMessage ( $tag . " 2500번을 점프하여 레벨이 1 상승하셨습니다." );
         $this->pldb [strtolower ( $name )] ["레벨"] += 1;
      }
   }
   public function Move(PlayerInteractEvent $event) {
      $player = $event->getPlayer ();
      $name = $player->getName ();
      $block = $event->getBlock ();
      $inventory = $player->getInventory ();
      $item = $player->getInventory ()->getItemInHand ();
      $customName = $item->getCustomName ();
      $xA = ( int ) round ( $player->x - 0.5 );
      $yS = ( int ) round ( $player->y - 1 );
      $zD = ( int ) round ( $player->z - 0.5 );
      $x = $block->getFloorX ();
      $y = $block->getFloorY ();
      $z = $block->getFloorZ ();
      $tag = "§d§l[ §f시스템 §d]§f";
      switch ($customName) {
         case "§f§l레벨업권" :
            $player->getInventory ()->removeItem ( Item::get ( $item->getId (), $item->getDamage (), 1 ) );
            $player->sendMessage ( $tag . " 당신의 레벨이 1 상승 되었습니다." );
            $this->pldb [strtolower ( $name )] ["레벨"] += 1;
            $this->save ();
            break;
         case "§f§l전직권" :
            $player->getInventory ()->removeItem ( Item::get ( $item->getId (), $item->getDamage (), 1 ) );
            $player->sendMessage ( $tag . " 모든 전직정보가 초기화 되었습니다." );
            $this->pldb [strtolower ( $name )] ["전직"] = "없음";
            $this->pldb [strtolower ( $name )] ["스킬템"] = "미지급";
            $this->pldb [strtolower ( $name )] ["레벨"] = 1;
            $this->pldb [strtolower ( $name )] ["점프횟수"] = 0;
            $this->pldb [strtolower ( $name )] ["체력"] = 0;
            $this->save ();
            break;
         case "§f§l빛정령 스킬템 ( 궁 )" :
            if ($this->pldb [strtolower ( $name )] ["전직"] == "빛정령") {
               if ($this->pldb [strtolower ( $name )] ["궁스킬"] == "이용가능") {
                  $level = $player->getLevel ();
                  $location = $player->getLocation ();
                  $time = 0;
                  $pos = $player->getPosition ();
                  $pi = 3.14159;
                  $time += $pi / 2;
                  for($i = 0; $i <= 50; $i += $pi / 16) {
                     $radio = 8;
                     $x = $radio * cos ( $i ) * sin ( $time );
                     $y = $radio * cos ( $time ) + 1.5;
                     $z = $radio * sin ( $i ) * sin ( $time );
                     $level->addParticle ( new SnowballPoofParticle ( $location->add ( $x, $y, $z ) ) );
                  }
                  foreach ( $player->getLevel ()->getPlayers () as $players ) {
                     if ($players !== $player && $player->distance ( $players ) <= 5) {
                        $players->addEffect ( new EffectInstance ( Effect::getEffect ( 2 ), 60, 1 ) );
                        $player->setHealth ( $player->getHealth () - 5 );
                     }
                  }
                  $this->pldb [strtolower ( $name )] ["궁스킬"] = "이용불가";
                  $player->sendMessage ( $tag . " 궁스킬을 사용했습니다." );
                  $explode = new Explosion ( $block, $y, $y, $y, 1 );
                  $explode->explodeB ();
                  $this->time [$name . "4"] = $this->makeTimestamp ();
               } else {
                  $player->sendMessage ( $tag . " 궁스킬은 쿨타임이 남았습니다. 남은 시간 : " . ($this->makeTimestamp () - $this->time [$name . "4"] - 70) );
               }
            } else {
               $player->sendMessage ( $tag . " 당신은 빛정령이 아닙니다." );
               break;
            }
            if (isset ( $this->time [$name . "4"] )) {
               if ($this->makeTimestamp () - $this->time [$name . "4"] > 70) {
                  unset ( $this->time [$name . "4"] );
                  $this->pldb [strtolower ( $name )] ["궁스킬"] = "이용가능";
                  $player->sendMessage ( $tag . " 궁 이용 가능" );
               }
            }
            break;
         case "§f§l빛정령 스킬템 ( 일반 )" :
            if ($this->pldb [strtolower ( $name )] ["전직"] == "빛정령") {
               if ($this->pldb [strtolower ( $name )] ["일반스킬"] == "이용가능") {
                  foreach ( $player->getLevel ()->getPlayers () as $players ) {
                     if ($players !== $player && $player->distance ( $players ) <= 5) {
                        $players->addEffect ( new EffectInstance ( Effect::getEffect ( 10 ), 80, 2 ) );
                        $players->addEffect ( new EffectInstance ( Effect::getEffect ( 2 ), 80, 1 ) );
                     }
                  }
                  $level = $player->getLevel ();
                  $location = $player->getLocation ();
                  $time = 0;
                  $pos = $player->getPosition ();
                  $pi = 3.14159;
                  $time += $pi / 2;
                  $rand = mt_rand ( - 5, 5 );
                  for($i = 0; $i <= 50; $i += $pi / 16) {
                     $radio = 8;
                     $x = $radio * cos ( $i ) * sin ( $time );
                     $y = $radio * cos ( $time ) + 1.5;
                     $z = $radio * sin ( $i ) * sin ( $time );
                     $level->addParticle ( new SnowballPoofParticle ( $location->add ( $x, $y, $z ) ) );
                  }
                  $this->pldb [strtolower ( $name )] ["일반스킬"] = "이용불가";
                  $player->sendMessage ( $tag . " 일반스킬을 사용했습니다." );
                  $player->setHealth ( $player->getHealth () - 3 );
                  $this->time [$name . "3"] = $this->makeTimestamp ();
               } else {
                  $player->sendMessage ( $tag . " 일반스킬은 쿨타임이 남았습니다. 남은 시간 : " . ($this->makeTimestamp () - $this->time [$name . "3"] - 50) );
               }
            } else {
               $player->sendMessage ( $tag . " 당신은 빛정령이 아닙니다." );
               break;
            }
            if (isset ( $this->time [$name . "3"] )) {
               if ($this->makeTimestamp () - $this->time [$name . "3"] > 50) {
                  unset ( $this->time [$name . "3"] );
                  $this->pldb [strtolower ( $name )] ["일반스킬"] = "이용가능";
                  $player->sendMessage ( $tag . " 일반스킬 이용 가능" );
               }
            }
            break;
         case "§f§l흑정령 스킬템 ( 궁 )" :
            if ($this->pldb [strtolower ( $name )] ["전직"] == "흑정령") {
               if ($this->pldb [strtolower ( $name )] ["궁스킬"] == "이용가능") {
                  $level = $player->getLevel ();
                  $location = $player->getLocation ();
                  $time = 0;
                  $pos = $player->getPosition ();
                  $pi = 3.14159;
                  $time += $pi / 2;
                  for($i = 0; $i <= 50; $i += $pi / 16) {
                     $radio = 8;
                     $x = $radio * cos ( $i ) * sin ( $time );
                     $y = $radio * cos ( $time ) + 1.5;
                     $z = $radio * sin ( $i ) * sin ( $time );
                     $level->addParticle ( new FlameParticle ( $location->add ( $x, $y, $z ) ) );
                  }
                  $this->pldb [strtolower ( $name )] ["궁스킬"] = "이용불가";
                  $player->addEffect ( new EffectInstance ( Effect::getEffect ( 5 ), 160, 2 ) );
                  $player->addEffect ( new EffectInstance ( Effect::getEffect ( 15 ), 160, 1 ) );
                  $player->addEffect ( new EffectInstance ( Effect::getEffect ( 1 ), 160, 3 ) );
                  $player->sendMessage ( $tag . " 궁스킬을 사용했습니다." );
                  $this->time [$name . "2"] = $this->makeTimestamp ();
               } else {
                  $player->sendMessage ( $tag . " 궁스킬은 쿨타임이 남았습니다. 남은 시간 : " . ($this->makeTimestamp () - $this->time [$name . "2"] - 20) );
               }
            } else {
               $player->sendMessage ( $tag . " 당신은 흑정령이 아닙니다. " );
               break;
            }
            if (isset ( $this->time [$name . "2"] )) {
               if ($this->makeTimestamp () - $this->time [$name . "2"] > 20) {
                  unset ( $this->time [$name . "2"] );
                  $this->pldb [strtolower ( $name )] ["궁스킬"] = "이용가능";
                  $player->sendMessage ( $tag . " 궁 이용 가능" );
               }
            }
            break;
         case "§f§l흑정령 스킬템 ( 일반 )" :
            if ($this->pldb [strtolower ( $name )] ["전직"] == "흑정령") {
               if ($this->pldb [strtolower ( $name )] ["일반스킬"] == "이용가능") {
                  $level = $player->getLevel ();
                  $location = $player->getLocation ();
                  $time = 0;
                  $pos = $player->getPosition ();
                  $pi = 3.14159;
                  $time += $pi / 2;
                  for($i = 0; $i <= 50; $i += $pi / 16) {
                     $radio = 8;
                     $x = $radio * cos ( $i ) * sin ( $time );
                     $y = $radio * cos ( $time ) + 1.5;
                     $z = $radio * sin ( $i ) * sin ( $time );
                     $level->addParticle ( new FlameParticle ( $location->add ( $x, $y, $z ) ) );
                  }
                  $this->pldb [strtolower ( $name )] ["일반스킬"] = "이용불가";
                  foreach ( $player->getLevel ()->getPlayers () as $players ) {
                     if ($players !== $player && $player->distance ( $players ) <= 5) {
                        $player->setHealth ( $player->getHealth () - 5 );
                        $players->addEffect ( new EffectInstance ( Effect::getEffect ( 2 ), 140, 1 ) );
                        $players->addEffect ( new EffectInstance ( Effect::getEffect ( 19 ), 140, 2 ) );
                     }
                  }
                  $player->sendMessage ( $tag . " 일반스킬을 사용했습니다." );
                  $this->time [$name . "1"] = $this->makeTimestamp ();
               } else {
                  $player->sendMessage ( $tag . " 일반스킬은 쿨타임이 남았습니다. 남은 시간 : " . ($this->makeTimestamp () - $this->time [$name . "1"] - 50) );
               }
            } else {
               $player->sendMessage ( $tag . " 당신은 흑정령이 아닙니다." );
               break;
            }
            if (isset ( $this->time [$name . "1"] )) {
               if ($this->makeTimestamp () - $this->time [$name . "1"] > 50) {
                  unset ( $this->time [$name . "1"] );
                  $this->pldb [strtolower ( $name )] ["일반스킬"] = "이용가능";
                  $player->sendMessage ( $tag . " 일반스킬 이용 가능" );
               }
            }
            break;
         case "§e[§f 체력+§e ]§r" :
            if ($player->getMaxHealth () >= 300) {
               $player->sendMessage ( $tag . " 체력은 300까지 밖에 올릴 수 없습니다" );
               break;
            }
            $max = $player->getMaxHealth ();
            $event->getPlayer ()->setMaxHealth ( $max + 2 );
            $player->sendMessage ( $tag . "체력이 1 증가하였습니다" );
            $this->pldb [strtolower ( $name )] ["체력"] += 2;
            $player->getInventory ()->removeItem ( Item::get ( $item->getId (), $item->getDamage (), 1 ) );
            return true;
      }
   }
   public function makeTimestamp() {
      $date = date ( "Y-m-d H:i:s" );
      $yy = substr ( $date, 0, 4 );
      $mm = substr ( $date, 5, 2 );
      $dd = substr ( $date, 8, 2 );
      $hh = substr ( $date, 11, 2 );
      $ii = substr ( $date, 14, 2 );
      $ss = substr ( $date, 17, 2 );
      return mktime ( $hh, $ii, $ss, $mm, $dd, $yy );
   }
   public function onQuit(PlayerQuitEvent $event) {
      $player = $event->getPlayer ();
      $name = $player->getName ();
      $event->setQuitMessage ( null );
      $this->pldb [strtolower ( $name )] ["궁스킬"] = "이용가능";
      $this->pldb [strtolower ( $name )] ["일반스킬"] = "이용가능";
      $this->save ();
   }
   public function onExplosion(\pocketmine\level\Explosion $event) {
      $event->setCancelled ();
   }
   public function onDisable() {
      $this->save ();
   }
   public function save() {
      $this->player->setAll ( $this->pldb );
      $this->player->save ();
   }
}