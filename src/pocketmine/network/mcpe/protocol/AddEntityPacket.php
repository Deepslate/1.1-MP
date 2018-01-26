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

use pocketmine\entity\Attribute;
use pocketmine\network\mcpe\NetworkSession;

class AddEntityPacket extends DataPacket{
	const NETWORK_ID = ProtocolInfo::ADD_ENTITY_PACKET;

	/** @var int|null */
	public $entityUniqueId = \null; //TODO
	/** @var int */
	public $entityRuntimeId;
	public $type;
	public $x;
	public $y;
	public $z;
	public $speedX = 0.0;
	public $speedY = 0.0;
	public $speedZ = 0.0;
	public $yaw = 0.0;
	public $pitch = 0.0;
	/** @var Attribute[] */
	public $attributes = [];
	public $metadata = [];
	public $links = [];

	public function decodePayload(){
		$this->entityUniqueId = $this->getEntityUniqueId();
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->type = $this->getUnsignedVarInt();
		$this->getVector3f($this->x, $this->y, $this->z);
		$this->getVector3f($this->speedX, $this->speedY, $this->speedZ);
		$this->pitch = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
		$this->yaw = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));

		$attrCount = $this->getUnsignedVarInt();
		for($i = 0; $i < $attrCount; ++$i){
			$name = $this->getString();
			$min = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
			$current = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
			$max = ((\ENDIANNESS === 0 ? \unpack("f", \strrev($this->get(4)))[1] : \unpack("f", $this->get(4))[1]));
			$attr = Attribute::getAttributeByName($name);

			if($attr !== \null){
				$attr->setMinValue($min);
				$attr->setMaxValue($max);
				$attr->setValue($current);
				$this->attributes[] = $attr;
			}else{
				throw new \UnexpectedValueException("Unknown attribute type \"$name\"");
			}
		}

		$this->metadata = $this->getEntityMetadata();
		$linkCount = $this->getUnsignedVarInt();
		for($i = 0; $i < $linkCount; ++$i){
			$this->links[$i][0] = $this->getEntityUniqueId();
			$this->links[$i][1] = $this->getEntityUniqueId();
			$this->links[$i][2] = (\ord($this->get(1)));
		}
	}

	public function encodePayload(){
		$this->putEntityUniqueId($this->entityUniqueId ?? $this->entityRuntimeId);
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putUnsignedVarInt($this->type);
		$this->putVector3f($this->x, $this->y, $this->z);
		$this->putVector3f($this->speedX, $this->speedY, $this->speedZ);
		($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->pitch)) : \pack("f", $this->pitch)));
		($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $this->yaw)) : \pack("f", $this->yaw)));

		$this->putUnsignedVarInt(\count($this->attributes));
		foreach($this->attributes as $attribute){
			$this->putString($attribute->getName());
			($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $attribute->getMinValue())) : \pack("f", $attribute->getMinValue())));
			($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $attribute->getValue())) : \pack("f", $attribute->getValue())));
			($this->buffer .= (\ENDIANNESS === 0 ? \strrev(\pack("f", $attribute->getMaxValue())) : \pack("f", $attribute->getMaxValue())));
		}

		$this->putEntityMetadata($this->metadata);
		$this->putUnsignedVarInt(\count($this->links));
		foreach($this->links as $link){
			$this->putEntityUniqueId($link[0]);
			$this->putEntityUniqueId($link[1]);
			($this->buffer .= \chr($link[2]));
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAddEntity($this);
	}

}
