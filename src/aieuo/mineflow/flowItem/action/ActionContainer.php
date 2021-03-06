<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\flowItem\FlowItemContainer;

/**
 * Interface ActionContainer
 * @package aieuo\mineflow\flowItem\action
 *
 * @property bool $wait
 * @property bool $waiting
 * @property array|null $next
 * @property bool $exit
 * @property bool $break
 *
 * @property bool $lastResult
 */
interface ActionContainer extends FlowItemContainer {

    /**
     * @param Action $action
     */
    public function addAction(Action $action): void;

    /**
     * @param int $index
     * @return Action|null
     */
    public function getAction(int $index): ?Action;

    /**
     * @param int $index
     */
    public function removeAction(int $index): void;

    /**
     * @param array $actions
     */
    public function setActions(array $actions): void;

    /**
     * @return Action[]
     */
    public function getActions(): array;

    /**
     * @return bool|null
     */
    public function getLastActionResult(): ?bool;

    public function wait();

    public function resume();

    public function exitRecipe();

    public function break();
}