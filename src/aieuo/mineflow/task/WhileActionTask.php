<?php

namespace aieuo\mineflow\task;

use aieuo\mineflow\flowItem\action\WhileAction;
use aieuo\mineflow\recipe\Recipe;
use pocketmine\entity\Entity;
use pocketmine\scheduler\Task;

class WhileActionTask extends Task {

    /** @var WhileAction */
    private $script;
    /** @var Recipe|null */
    private $recipe;
    /** @var int */
    private $count = 0;

    public function __construct(WhileAction $script, ?Recipe $recipe) {
        $this->script = $script;
        $this->recipe = $recipe;
    }

    public function onRun(int $currentTick) {
        $this->count ++;
        $this->script->check($this->recipe);
    }
}