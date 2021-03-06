<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\flowItem\base\PlayerFlowItem;
use aieuo\mineflow\flowItem\base\PlayerFlowItemTrait;
use aieuo\mineflow\formAPI\Form;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\formAPI\element\Label;
use aieuo\mineflow\formAPI\element\Input;
use aieuo\mineflow\formAPI\CustomForm;
use aieuo\mineflow\Main;
use aieuo\mineflow\formAPI\element\Toggle;

class SetFood extends Action implements PlayerFlowItem {
    use PlayerFlowItemTrait;

    protected $id = self::SET_FOOD;

    protected $name = "action.setFood.name";
    protected $detail = "action.setFood.detail";
    protected $detailDefaultReplace = ["player", "food"];

    protected $category = Category::PLAYER;

    protected $targetRequired = Recipe::TARGET_REQUIRED_PLAYER;

    /** @var string */
    private $food;

    public function __construct(string $name = "target", string $health = "") {
        $this->playerVariableName = $name;
        $this->food = $health;
    }

    public function setFood(string $health) {
        $this->food = $health;
    }

    public function getFood(): string {
        return $this->food;
    }

    public function isDataValid(): bool {
        return $this->getPlayerVariableName() !== "" and $this->food !== "";
    }

    public function getDetail(): string {
        if (!$this->isDataValid()) return $this->getName();
        return Language::get($this->detail, [$this->getPlayerVariableName(), $this->getFood()]);
    }

    public function execute(Recipe $origin): bool {
        $this->throwIfCannotExecute();

        $health = $origin->replaceVariables($this->getFood());

        $this->throwIfInvalidNumber($health, 0, 20);

        $entity = $this->getPlayer($origin);
        $this->throwIfInvalidPlayer($entity);

        $entity->setFood((float)$health);
        return true;
    }

    public function getEditForm(array $default = [], array $errors = []): Form {
        return (new CustomForm($this->getName()))
            ->setContents([
                new Label($this->getDescription()),
                new Input("@flowItem.form.target.player", Language::get("form.example", ["target"]), $default[1] ?? $this->getPlayerVariableName()),
                new Input("@action.setFood.form.food", Language::get("form.example", ["20"]), $default[2] ?? $this->getFood()),
                new Toggle("@form.cancelAndBack")
            ])->addErrors($errors);
    }

    public function parseFromFormData(array $data): array {
        $errors = [];
        $containsVariable = Main::getVariableHelper()->containsVariable($data[2]);
        if ($data[2] === "") {
            $errors[] = ["@form.insufficient", 2];
        } elseif (!$containsVariable and !is_numeric($data[2])) {
            $errors[] = ["@flowItem.error.notNumber", 2];
        } elseif (!$containsVariable and (float)$data[2] < 1) {
            $errors[] = [Language::get("flowItem.error.lessValue", [1]), 2];
        } elseif (!$containsVariable and (float)$data[2] > 20) {
            $errors[] = [Language::get("flowItem.error.overValue", [20]), 2];
        }
        return ["status" => empty($errors), "contents" => [$data[1], $data[2]], "cancel" => $data[3], "errors" => $errors];
    }

    public function loadSaveData(array $content): Action {
        if (!isset($content[1])) throw new \OutOfBoundsException();
        $this->setPlayerVariableName($content[0]);
        $this->setFood($content[1]);
        return $this;
    }

    public function serializeContents(): array {
        return [$this->getPlayerVariableName(), $this->getFood()];
    }
}