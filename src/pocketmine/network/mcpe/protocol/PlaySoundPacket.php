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

class PlaySoundPacket extends DataPacket{
	const NETWORK_ID = ProtocolInfo::PLAY_SOUND_PACKET;

	/** @var string */
	public $soundName;
	/** @var float */
	public $x;
	/** @var float */
	public $y;
	/** @var float */
	public $z;
	/** @var float */
	public $volume;
	/** @var float */
	public $pitch;

	public function decodePayload(){
		$this->soundName = $this->getString();
		$this->getBlockPosition($this->x, $this->y, $this->z);
		$this->x /= 8;
		$this->y /= 8;
		$this->z /= 8;
		$this->volume = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
		$this->pitch = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
	}

	public function encodePayload(){
		$this->putString($this->soundName);
		$this->putBlockPosition((int) ($this->x * 8), (int) ($this->y * 8), (int) ($this->z * 8));
		($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->volume)) : \pack("f", $this->volume)));
		($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->pitch)) : \pack("f", $this->pitch)));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlaySound($this);
	}
}
