<?php

declare(strict_types=1);

namespace keo\redeem\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use keo\redeem\util\RedeemManager;

class RedeemCommand extends Command {

    private RedeemManager $manager;

    public function __construct(RedeemManager $manager) {
        parent::__construct("redeem", "Made by keole", "/redeem <create|edit|claim> <nombre>", []);
        $this->manager = $manager;
        $this->setPermission("redeem.command");
    }

    public function execute(CommandSender $sender, string $label, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage("Este comando solo puede ser usado en el juego.");
            return;
        }

        if (!$this->testPermission($sender)) {
            $sender->sendMessage("No tienes permiso para usar este comando.");
            return;
        }

        if (count($args) < 2) {
            $sender->sendMessage("Uso: /redeem <create|edit|claim> <nombre>");
            return;
        }

        $subCommand = strtolower($args[0]);
        $name = $args[1];

        switch ($subCommand) {
            case "create":
                if (!$sender->hasPermission("redeem.create")) {
                    $sender->sendMessage("No tienes permiso para crear un redeem.");
                    return;
                }
                $this->manager->openRedeemEditor($sender, $name, true);
                $sender->sendMessage("Has creado un nuevo redeem llamado '{$name}'.");
                break;

            case "edit":
                if (!$sender->hasPermission("redeem.edit")) {
                    $sender->sendMessage("No tienes permiso para editar un redeem.");
                    return;
                }
                $this->manager->openRedeemEditor($sender, $name, false);
                $sender->sendMessage("Estás editando el redeem llamado '{$name}'.");
                break;

            case "claim":
                $this->manager->claimRedeem($sender, $name);
                break;

            default:
                $sender->sendMessage("Subcomando inválido. Uso: /redeem <create|edit|claim> <nombre>");
        }
    }
}
