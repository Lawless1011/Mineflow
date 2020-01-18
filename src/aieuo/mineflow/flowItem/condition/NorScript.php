<?php

namespace aieuo\mineflow\flowItem\condition;

use pocketmine\entity\Entity;
use aieuo\mineflow\recipe\Recipe;

class NorScript extends ORScript {

    protected $id = self::CONDITION_NOR;

    protected $name = "condition.nor.name";
    protected $detail = "condition.nor.description";

    public function getDetail(): string {
        $details = ["-----------nor-----------"];
        foreach ($this->getConditions() as $condition) {
            $details[] = $condition->getDetail();
        }
        $details[] = "------------------------";
        return implode("\n", $details);
    }

    public function execute(?Entity $target, Recipe $origin): ?bool {
        $result = parent::execute($target, $origin);
        if ($result === null) return null;
        return !$result;
    }
}