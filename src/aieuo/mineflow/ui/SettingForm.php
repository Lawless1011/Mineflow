<?php

namespace aieuo\mineflow\ui;

use aieuo\mineflow\EventListener;
use aieuo\mineflow\formAPI\CustomForm;
use aieuo\mineflow\formAPI\element\Button;
use aieuo\mineflow\formAPI\element\Dropdown;
use aieuo\mineflow\formAPI\element\Toggle;
use aieuo\mineflow\formAPI\ListForm;
use aieuo\mineflow\trigger\EventTriggers;
use aieuo\mineflow\utils\Language;
use pocketmine\Player;
use pocketmine\Server;

class SettingForm {
    public function sendMenu(Player $player, array $messages = []) {
        (new ListForm("@mineflow.settings"))
            ->setContent("@form.selectButton")
            ->addButtons([
                new Button("@form.back"),
                new Button("@setting.language"),
                new Button("@setting.event"),
            ])->onReceive(function (Player $player, ?int $data) {
                if ($data === null) return;

                switch ($data) {
                    case 0:
                        (new HomeForm)->sendMenu($player);
                        break;
                    case 1:
                        $this->selectLanguageForm($player);
                        break;
                    case 2:
                        $this->sendEventListForm($player);
                        break;
                }
            })->addMessages($messages)->show($player);
    }

    public function selectLanguageForm(Player $player) {
        $languages = Language::getAvailableLanguages();
        (new CustomForm("@setting.language"))
            ->setContents([
                new Dropdown("@setting.language", $languages, array_search(Language::getLanguage(), $languages)),
            ])->onReceive(function (Player $player, ?array $data) use ($languages) {
                if ($data === null) return;

                $language = $languages[$data[0]];
                Server::getInstance()->dispatchCommand($player, "mineflow language ".$language);
            })->show($player);
    }

    public function sendEventListForm(Player $player) {
        $events = EventTriggers::getEvents();
        $enables = EventListener::getEnabledEvents();
        $contents = [];
        foreach ($events as $name => $event) {
            $contents[] = new Toggle("@".$event[1], isset($enables[$name]));
        }
        (new CustomForm("@setting.event"))
            ->setContents($contents)
            ->onReceive(function (Player $player, ?array $data) use ($events, $enables) {
                if ($data === null) return;

                $count = 0;
                foreach ($events as $name => $event) {
                    if ($data[$count] and !isset($enables[$name])) {
                        EventListener::setEventEnabled($name, true);
                    } elseif (!$data[$count] and isset($enables[$name])) {
                        EventListener::setEventEnabled($name, false);
                    }
                    $count ++;
                }
                $this->sendMenu($player, ["@setting.event.changed"]);
            })->show($player);
    }
}