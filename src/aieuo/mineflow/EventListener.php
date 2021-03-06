<?php /** @noinspection PhpUnused */

namespace aieuo\mineflow;

use aieuo\mineflow\flowItem\action\SetSitting;
use aieuo\mineflow\trigger\Trigger;
use aieuo\mineflow\trigger\TriggerHolder;
use pocketmine\command\Command;
use pocketmine\Server;
use pocketmine\plugin\MethodEventExecutor;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\event\player\PlayerToggleSprintEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerToggleFlightEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\inventory\FurnaceBurnEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\EventPriority;
use pocketmine\event\Event;
use pocketmine\Player;
use aieuo\mineflow\variable\DefaultVariables;
use aieuo\mineflow\utils\Session;
use aieuo\mineflow\ui\TriggerForm;
use aieuo\mineflow\event\ServerStartEvent;
use pocketmine\event\entity\EntityTeleportEvent;

class EventListener implements Listener {

    /** @var Main */
    private $owner;

    /** @var array */
    private $eventMethods = [
        "PlayerChatEvent" => "onChat",
        "PlayerCommandPreprocessEvent" => "onCommandPreprocess",
        "BlockBreakEvent" => "onBlockBreak",
        "BlockPlaceEvent" => "onBlockPlace",
        "ServerStartEvent" => "onServerStart",
        "SignChangeEvent" => "onSignChange",
        "EntityDamageEvent" => "onEntityDamage",
        "PlayerToggleFlightEvent" => "onToggleFlight",
        "CraftItemEvent" => "onCraftItem",
        "PlayerDropItemEvent" => "onDropItem",
        "FurnaceBurnEvent" => "onFurnaceBurn",
        "LevelLoadEvent" => "onLevelLoad",
        "PlayerBedEnterEvent" => "onBedEnter",
        "PlayerChangeSkinEvent" => "onChangeSkin",
        "PlayerExhaustEvent" => "onExhaust",
        "PlayerItemConsumeEvent" => "onItemConsume",
        "PlayerMoveEvent" => "onMove",
        "PlayerToggleSneakEvent" => "onToggleSneak",
        "PlayerToggleSprintEvent" => "onToggleSprint",
        "ProjectileHitEntityEvent" => "onProjectileHit",
    ];

    public function __construct(Main $owner) {
        $this->owner = $owner;
    }

    private function getOwner(): Main {
        return $this->owner;
    }

    public function registerEvents() {
        $this->registerEvent(PlayerJoinEvent::class, "onJoin");
        $this->registerEvent(PlayerQuitEvent::class, "onQuit");
        $this->registerEvent(PlayerInteractEvent::class, "onInteract");
        $this->registerEvent(PlayerDeathEvent::class, "onDeath");
        $this->registerEvent(CommandEvent::class, "command");
        $this->registerEvent(DataPacketReceiveEvent::class, "receive");
        $this->registerEvent(EntityTeleportEvent::class, "teleport");
        $this->registerEvent(EntityLevelChangeEvent::class, "onLevelChange");

        foreach (Main::getEventManager()->getEnabledEvents() as $event => $value) {
            if (!isset($this->eventMethods[$event])) continue;
            $this->registerEvent(Main::getEventManager()->getEventPath($event), $this->eventMethods[$event]);
        }
    }

    private function registerEvent(string $event, string $method) {
        $owner = $this->getOwner();
        $pluginManager = $owner->getServer()->getPluginManager();
        $pluginManager->registerEvent($event, $this, EventPriority::NORMAL, new MethodEventExecutor($method), $this->getOwner());
    }

    public function onJoin(PlayerJoinEvent $event) {
        Session::createSession($event->getPlayer());

        if (Main::getEventManager()->isEnabledEvent("PlayerJoinEvent")) $this->onEvent($event, "PlayerJoinEvent");
    }

    public function onQuit(PlayerQuitEvent $event) {
        Session::destroySession($event->getPlayer());

        if (Main::getEventManager()->isEnabledEvent("PlayerQuitEvent")) $this->onEvent($event, "PlayerQuitEvent");
    }

    public function onInteract(PlayerInteractEvent $event) {
        if ($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK and $event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_AIR) return;

        $player = $event->getPlayer();
        $block = $event->getBlock();
        $session = Session::getSession($player);
        $holder = TriggerHolder::getInstance();
        $position = $block->x.",".$block->y.",".$block->z.",".$block->level->getFolderName();

        if ($player->isOp() and $session->exists("blockTriggerAction")) {
            switch ($session->get("blockTriggerAction")) {
                case "add":
                    $recipe = $session->get("blockTriggerRecipe");
                    $trigger = new Trigger(Trigger::TYPE_BLOCK, $position);
                    if ($recipe->existsTrigger($trigger)) {
                        (new TriggerForm)->sendAddedTriggerMenu($player, $recipe, $trigger, ["@trigger.alreadyExists"]);
                        return;
                    }
                    $recipe->addTrigger($trigger);
                    (new TriggerForm)->sendAddedTriggerMenu($player, $recipe, $trigger, ["@trigger.add.success"]);
                    break;
            }
            $session->remove("blockTriggerAction");
            return;
        }
        if ($holder->existsRecipeByString(Trigger::TYPE_BLOCK, $position)) {
            $recipes = $holder->getRecipes(new Trigger(Trigger::TYPE_BLOCK, $position));
            $variables = DefaultVariables::getBlockVariables($block);
            $recipes->executeAll($player, $variables, $event);
        }

        if (Main::getEventManager()->isEnabledEvent("PlayerInteractEvent")) $this->onEvent($event, "PlayerInteractEvent");
    }

    public function command(CommandEvent $event) {
        $sender = $event->getSender();
        if (!($sender instanceof Player)) return;
        if ($event->isCancelled()) return;

        $cmd = $event->getCommand();
        $holder = TriggerHolder::getInstance();
        $commands = explode(" ", $cmd);

        $count = count($commands);
        $origin = $commands[0];
        $command = Server::getInstance()->getCommandMap()->getCommand($origin);
        if (!($command instanceof Command) or !$command->testPermissionSilent($sender)) return;

        for ($i=0; $i<$count; $i++) {
            $command = implode(" ", $commands);
            if ($holder->existsRecipeByString(Trigger::TYPE_COMMAND, $origin, $command)) {
                $recipes = $holder->getRecipes(new Trigger(Trigger::TYPE_COMMAND, $origin, $command));
                $variables = DefaultVariables::getCommandVariables($event->getCommand());
                $recipes->executeAll($sender, $variables, $event);
                break;
            }
            array_pop($commands);
        }
    }

    public function onEvent(Event $event, string $eventName): void {
        $holder = TriggerHolder::getInstance();
        if ($holder->existsRecipeByString(Trigger::TYPE_EVENT, $eventName)) {
            $recipes = $holder->getRecipes(new Trigger(Trigger::TYPE_EVENT, $eventName));
            $target = null;
            if ($event instanceof PlayerEvent or $event instanceof BlockEvent or $event instanceof CraftItemEvent) {
                $target = $event->getPlayer();
            } elseif ($event instanceof EntityDamageByEntityEvent) {
                $target = $event->getDamager();
            } elseif ($event instanceof EntityEvent) {
                $target = $event->getEntity();
            }
            $variables = DefaultVariables::getEventVariables($event, $eventName);
            $recipes->executeAll($target, $variables, $event);
        }
    }

    public function onDeath(PlayerDeathEvent $event) {
        if (Main::getEventManager()->isEnabledEvent("PlayerDeathEvent")) $this->onEvent($event, "PlayerDeathEvent");
        $player = $event->getPlayer();
        if ($player instanceof Player) SetSitting::leave($player);
    }

    public function onLevelChange(EntityLevelChangeEvent $event) {
        if (Main::getEventManager()->isEnabledEvent("EntityLevelChangeEvent")) $this->onEvent($event, "EntityLevelChangeEvent");
        $player = $event->getEntity();
        if ($player instanceof Player) SetSitting::leave($player);
    }

    public function receive(DataPacketReceiveEvent $event) {
        $pk = $event->getPacket();
        $player = $event->getPlayer();
        if ($pk instanceof InteractPacket) {
            if ($pk->action === InteractPacket::ACTION_LEAVE_VEHICLE) {
                SetSitting::leave($player);
            }
        }
    }

    public function teleport(EntityTeleportEvent $event) {
        $player = $event->getEntity();
        if ($player instanceof Player) SetSitting::leave($player);
    }

    public function onChat(PlayerChatEvent $event) {
        $this->onEvent($event, "PlayerChatEvent");
    }
    public function onCommandPreprocess(PlayerCommandPreprocessEvent $event) {
        $this->onEvent($event, "PlayerCommandPreprocessEvent");
    }
    public function onBlockBreak(BlockBreakEvent $event) {
        $this->onEvent($event, "BlockBreakEvent");
    }
    public function onBlockPlace(BlockPlaceEvent $event) {
        $this->onEvent($event, "BlockPlaceEvent");
    }
    public function onServerStart(ServerStartEvent $event) {
        $this->onEvent($event, "ServerStartEvent");
    }
    public function onSignChange(SignChangeEvent $event) {
        $this->onEvent($event, "SignChangeEvent");
    }
    public function onEntityDamage(EntityDamageEvent $event) {
        $this->onEvent($event, "EntityDamageEvent");
        if ($event instanceof EntityDamageByEntityEvent) $this->onEvent($event, "EntityAttackEvent");
    }
    public function onToggleFlight(PlayerToggleFlightEvent $event) {
        $this->onEvent($event, "PlayerToggleFlightEvent");
    }
    public function onCraftItem(CraftItemEvent $event) {
        $this->onEvent($event, "CraftItemEvent");
    }
    public function onDropItem(PlayerDropItemEvent $event) {
        $this->onEvent($event, "PlayerDropItemEvent");
    }
    public function onFurnaceBurn(FurnaceBurnEvent $event) {
        $this->onEvent($event, "FurnaceBurnEvent");
    }
    public function onLevelLoad(LevelLoadEvent $event) {
        $this->onEvent($event, "LevelLoadEvent");
    }
    public function onBedEnter(PlayerBedEnterEvent $event) {
        $this->onEvent($event, "PlayerBedEnterEvent");
    }
    public function onChangeSkin(PlayerChangeSkinEvent $event) {
        $this->onEvent($event, "PlayerChangeSkinEvent");
    }
    public function onExhaust(PlayerExhaustEvent $event) {
        $this->onEvent($event, "PlayerExhaustEvent");
    }
    public function onItemConsume(PlayerItemConsumeEvent $event) {
        $this->onEvent($event, "PlayerItemConsumeEvent");
    }
    public function onMove(PlayerMoveEvent $event) {
        $this->onEvent($event, "PlayerMoveEvent");
    }
    public function onToggleSneak(PlayerToggleSneakEvent $event) {
        $this->onEvent($event, "PlayerToggleSneakEvent");
    }
    public function onToggleSprint(PlayerToggleSprintEvent $event) {
        $this->onEvent($event, "PlayerToggleSprintEvent");
    }
    public function onProjectileHit(ProjectileHitEntityEvent $event) {
        $this->onEvent($event, "ProjectileHitEntityEvent");
    }
}