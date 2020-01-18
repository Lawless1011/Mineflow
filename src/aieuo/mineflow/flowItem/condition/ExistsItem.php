<?php

namespace aieuo\mineflow\flowItem\condition;

use pocketmine\entity\Entity;
use pocketmine\Player;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\recipe\Recipe;

class ExistsItem extends TypeItem {

    protected $id = self::EXISTS_ITEM;

    protected $name = "condition.existsItem.name";
    protected $detail = "condition.existsItem.detail";

    protected $targetRequired = Recipe::TARGET_REQUIRED_PLAYER;

    public function execute(?Entity $target, Recipe $origin): ?bool {
        if (!$this->canExecute($target)) return null;

        $item = $this->getItem();
        $id = $origin->replaceVariables($item[0]);
        $count = $origin->replaceVariables($item[1]);
        $name = $origin->replaceVariables($item[2]);

        if (!$this->checkValidNumberDataAndAlert($count, 1, null, $target)) return null;

        $item = $this->parseItem($id, (int)$count, $name);
        if ($item === null) {
            $target->sendMessage(Language::get("flowItem.error", [$this->getName(), Language::get("condition.item.notFound")]));
            return null;
        }

        /** @var Player $target */
        return $target->getInventory()->contains($item);
    }
}