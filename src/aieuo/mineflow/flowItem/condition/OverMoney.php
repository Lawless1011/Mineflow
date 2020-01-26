<?php

namespace aieuo\mineflow\flowItem\condition;

use pocketmine\utils\TextFormat;
use pocketmine\entity\Entity;
use pocketmine\Player;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\economy\Economy;

class OverMoney extends TypeMoney {

    protected $id = self::OVER_MONEY;

    protected $name = "condition.overMoney.name";
    protected $detail = "condition.overMoney.detail";

    protected $targetRequired = Recipe::TARGET_REQUIRED_PLAYER;

    public function execute(?Entity $target, Recipe $origin): ?bool {
        if (!$this->checkValidNumberDataAndAlert($target)) return null;

        if (!Economy::isPluginLoaded()) {
            $target->sendMessage(TextFormat::RED.Language::get("economy.notfound"));
            return null;
        }

        $amount = $origin->replaceVariables($this->getAmount());

        if (!$this->checkValidNumberDataAndAlert($amount, null, null, $target)) return null;

        $myMoney = Economy::getPlugin()->getMoney($target->getName());
        return $myMoney >= (int)$amount;
    }
}