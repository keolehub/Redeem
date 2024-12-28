<?php

declare(strict_types=1);

namespace keo\redeem\util;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use pocketmine\nbt\tag\CompoundTag;
use keo\redeem\Main;

class RedeemManager {

    private Config $redeemData;
    private Config $redeemClaims;
    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $plugin->saveResource("redeems.yml");
        $plugin->saveResource("claims.yml");
        $this->redeemData = new Config($plugin->getDataFolder() . "redeems.yml", Config::YAML);
        $this->redeemClaims = new Config($plugin->getDataFolder() . "claims.yml", Config::YAML);
    }

    public function redeemExists(string $name): bool {
        return $this->redeemData->exists($name);
    }

    public function hasClaimed(Player $player, string $name): bool {
        $claimed = $this->redeemClaims->getNested($player->getName(), []);
        return in_array($name, $claimed, true);
    }

    public function openRedeemEditor(Player $player, string $name, bool $isNew): void {
        if ($isNew && $this->redeemExists($name)) {
            $player->sendMessage(TextFormat::RED . "El redeem \"$name\" ya existe. Usa otro nombre.");
            return;
        }

        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->setName($isNew ? "Crear Redeem: $name" : "Editar Redeem: $name");
        if (!$isNew && $this->redeemExists($name)) {
            $items = $this->redeemData->getNested("$name", []);
            $menu->getInventory()->setContents($this->deserializeItems($items));
        }

        $menu->setListener(function (InvMenuTransaction $transaction) use ($name, $menu): InvMenuTransactionResult {
            $items = $menu->getInventory()->getContents();

            if (empty($items)) {
                $transaction->getPlayer()->sendMessage(TextFormat::RED . "¡El redeem no puede estar vacío!");
                return $transaction->continue();
            }

            $this->redeemData->setNested("$name", $this->serializeItems($items));
            $this->redeemData->save();
            $transaction->getPlayer()->sendMessage(TextFormat::GREEN . "Redeem \"$name\" guardado correctamente.");
            return $transaction->continue();
        });

        $menu->send($player);
    }

    public function claimRedeem(Player $player, string $name): void {
        if (!$this->redeemExists($name)) {
            $player->sendMessage(TextFormat::RED . "El redeem \"$name\" no existe.");
            return;
        }

        if ($this->hasClaimed($player, $name)) {
            $player->sendMessage(TextFormat::YELLOW . "Ya has reclamado el redeem \"$name\".");
            return;
        }

        $items = $this->redeemData->getNested("$name", []);
        foreach ($items as $itemData) {
            $item = $this->deserializeItem($itemData);
            $player->getInventory()->addItem($item);
        }
        $claimed = $this->redeemClaims->getNested($player->getName(), []);
        $claimed[] = $name;
        $this->redeemClaims->setNested($player->getName(), $claimed);
        $this->redeemClaims->save();

        $player->sendMessage(TextFormat::AQUA . "¡Has reclamado las recompensas del redeem \"$name\"!");
    }

    public function serializeItems(array $items): array {
        $serialized = [];
        foreach ($items as $item) {
            if ($item instanceof Item) {
                $serialized[] = $this->serializeItem($item);
            }
        }
        return $serialized;
    }

    public function serializeItem(Item $item): string {
        return Serialize::serialize($item);
    }

    public function deserializeItems(array $items): array {
        $deserialized = [];
        foreach ($items as $itemData) {
            $item = $this->deserializeItem($itemData);
            $deserialized[] = $item;
        }
        return $deserialized;
    }

    public function deserializeItem(string $itemData): Item {
        return Serialize::deserialize($itemData);
    }
}

