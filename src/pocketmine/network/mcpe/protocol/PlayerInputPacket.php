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

class PlayerInputPacket extends DataPacket{
	const NETWORK_ID = ProtocolInfo::PLAYER_INPUT_PACKET;

	public $motionX;
	public $motionY;
	public $unknownBool1;
	public $unknownBool2;

	public function decodePayload(){
		$this->motionX = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
		$this->motionY = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
		$this->unknownBool1 = (($this->get(1) !== "\x00"));
		$this->unknownBool2 = (($this->get(1) !== "\x00"));
	}

	public function encodePayload(){
		($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->motionX)) : \pack("f", $this->motionX)));
		($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->motionY)) : \pack("f", $this->motionY)));
		($this->buffer .= ($this->unknownBool1 ? "\x01" : "\x00"));
		($this->buffer .= ($this->unknownBool2 ? "\x01" : "\x00"));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerInput($this);
	}

}
