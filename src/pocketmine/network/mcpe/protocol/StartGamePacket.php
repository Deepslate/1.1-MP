<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\utils\Binary;


use pocketmine\network\mcpe\NetworkSession;

class StartGamePacket extends DataPacket{
	const NETWORK_ID = ProtocolInfo::START_GAME_PACKET;

	public $entityUniqueId;
	public $entityRuntimeId;
	public $playerGamemode;
	public $x;
	public $y;
	public $z;
	public $pitch;
	public $yaw;
	public $seed;
	public $dimension;
	public $generator = 1; //default infinite - 0 old, 1 infinite, 2 flat
	public $worldGamemode;
	public $difficulty;
	public $spawnX;
	public $spawnY;
	public $spawnZ;
	public $hasAchievementsDisabled = \true;
	public $dayCycleStopTime = -1; //-1 = not stopped, any positive value = stopped at that time
	public $eduMode = \false;
	public $rainLevel;
	public $lightningLevel;
	public $commandsEnabled;
	public $isTexturePacksRequired = \true;
	public $gameRules = []; //TODO: implement this
	public $levelId = ""; //base64 string, usually the same as world folder name in vanilla
	public $worldName;
	public $premiumWorldTemplateId = "";
	public $unknownBool = \false;
	public $currentTick = 0;

	public function decodePayload(){
		$this->entityUniqueId = $this->getEntityUniqueId();
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->playerGamemode = $this->getVarInt();
		$this->getVector3f($this->x, $this->y, $this->z);
		$this->pitch = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
		$this->yaw = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
		$this->seed = $this->getVarInt();
		$this->dimension = $this->getVarInt();
		$this->generator = $this->getVarInt();
		$this->worldGamemode = $this->getVarInt();
		$this->difficulty = $this->getVarInt();
		$this->getBlockPosition($this->spawnX, $this->spawnY, $this->spawnZ);
		$this->hasAchievementsDisabled = (($this->get(1) !== "\x00"));
		$this->dayCycleStopTime = $this->getVarInt();
		$this->eduMode = (($this->get(1) !== "\x00"));
		$this->rainLevel = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
		$this->lightningLevel = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
		$this->commandsEnabled = (($this->get(1) !== "\x00"));
		$this->isTexturePacksRequired = (($this->get(1) !== "\x00"));
		$this->gameRules = $this->getGameRules();
		$this->levelId = $this->getString();
		$this->worldName = $this->getString();
		$this->premiumWorldTemplateId = $this->getString();
		$this->unknownBool = (($this->get(1) !== "\x00"));
		$this->currentTick = (Binary::readLLong($this->get(8)));

	}

	public function encodePayload(){
		$this->putEntityUniqueId($this->entityUniqueId);
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putVarInt($this->playerGamemode);
		$this->putVector3f($this->x, $this->y, $this->z);
		($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->pitch)) : \pack("f", $this->pitch)));
		($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->yaw)) : \pack("f", $this->yaw)));
		$this->putVarInt($this->seed);
		$this->putVarInt($this->dimension);
		$this->putVarInt($this->generator);
		$this->putVarInt($this->worldGamemode);
		$this->putVarInt($this->difficulty);
		$this->putBlockPosition($this->spawnX, $this->spawnY, $this->spawnZ);
		($this->buffer .= ($this->hasAchievementsDisabled ? "\x01" : "\x00"));
		$this->putVarInt($this->dayCycleStopTime);
		($this->buffer .= ($this->eduMode ? "\x01" : "\x00"));
		($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->rainLevel)) : \pack("f", $this->rainLevel)));
		($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->lightningLevel)) : \pack("f", $this->lightningLevel)));
		($this->buffer .= ($this->commandsEnabled ? "\x01" : "\x00"));
		($this->buffer .= ($this->isTexturePacksRequired ? "\x01" : "\x00"));
		$this->putGameRules($this->gameRules);
		$this->putString($this->levelId);
		$this->putString($this->worldName);
		$this->putString($this->premiumWorldTemplateId);
		($this->buffer .= ($this->unknownBool ? "\x01" : "\x00"));
		($this->buffer .= (\pack("VV", $this->currentTick & 0xFFFFFFFF, $this->currentTick >> 32)));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleStartGame($this);
	}

}
