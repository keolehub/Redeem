<?php

declare(strict_types=1);

namespace keo\redeem;

use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use keo\redeem\command\RedeemCommand;
use keo\redeem\util\RedeemManager;
use pocketmine\item\Item;

class Main extends PluginBase {

    private RedeemManager $redeemManager;

    public function onEnable(): void {
        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        $this->redeemManager = new RedeemManager($this);

        $this->getServer()->getCommandMap()->registerAll("redeem", [
            new RedeemCommand($this->redeemManager),
        ]);
    }

    public function getRedeemManager(): RedeemManager {
        return $this->redeemManager;
    }

    public function encodeItem(Item $item): string {
        return $item->jsonSerialize();
    }

    public function decodeItem(string $data): Item {
        return Item::jsonDeserialize($data);
    }
}