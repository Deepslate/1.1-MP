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

class MovePlayerPacket extends DataPacket{
	const NETWORK_ID = ProtocolInfo::MOVE_PLAYER_PACKET;

	const MODE_NORMAL = 0;
	const MODE_RESET = 1;
	const MODE_TELEPORT = 2;
	const MODE_PITCH = 3; //facepalm Mojang

	public $entityRuntimeId;
	public $x;
	public $y;
	public $z;
	public $yaw;
	public $bodyYaw;
	public $pitch;
	public $mode = self::MODE_NORMAL;
	public $onGround = \false; //TODO
	public $ridingEid = 0;
	public $int1 = 0;
	public $int2 = 0;

	public function decodePayload(){
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->getVector3f($this->x, $this->y, $this->z);
		$this->pitch = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
		$this->yaw = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
		$this->bodyYaw = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
		$this->mode = (\ord($this->get(1)));
		$this->onGround = (($this->get(1) !== "\x00"));
		$this->ridingEid = $this->getEntityRuntimeId();
		if($this->mode === MovePlayerPacket::MODE_TELEPORT){
			$this->int1 = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
			$this->int2 = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
		}
	}

	public function encodePayload(){
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putVector3f($this->x, $this->y, $this->z);
		($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->pitch)) : \pack("f", $this->pitch)));
		($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->yaw)) : \pack("f", $this->yaw)));
		($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->bodyYaw)) : \pack("f", $this->bodyYaw))); //TODO
		($this->buffer .= \chr($this->mode));
		($this->buffer .= ($this->onGround ? "\x01" : "\x00"));
		$this->putEntityRuntimeId($this->ridingEid);
		if($this->mode === MovePlayerPacket::MODE_TELEPORT){
			($this->buffer .= (\pack("V", $this->int1)));
			($this->buffer .= (\pack("V", $this->int2)));
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleMovePlayer($this);
	}

}
