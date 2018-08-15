<?php

/*
 *
 * PM-1.1: The PMMP fork to support MCPE 1.1
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Tee7even
 *
*/

declare(strict_types=1);

namespace pocketmine\inventory\transaction;

use pocketmine\item\Item;
use pocketmine\Player;

class EquipItemAction extends Action{
	/** @var int */
	protected $hotbarSlot;
	/** @var int */
	protected $inventorySlot;
	/** @var Item */
	protected $equippedItem;

	public function __construct(int $hotbarSlot, int $inventorySlot, Item $equippedItem){
		parent::__construct([], []);
		$this->hotbarSlot = $hotbarSlot;
		$this->inventorySlot = $inventorySlot;
		$this->equippedItem = $equippedItem;
	}

	/**
	 * @param Transaction $transaction
	 * @return bool
	 */
	public function isValid(Transaction $transaction) : bool{
		return (empty($changes = $transaction->getSlotChanges($transaction->getSource()->getInventory(), $this->inventorySlot)) ?
			$transaction->getSource()->getInventory()->getItem($this->inventorySlot) :
			end($changes)->getTargetItem())->equals($this->equippedItem);
	}

	/**
	 * @param Player $source
	 * @return bool
	 */
	public function execute(Player $source) : bool{
		$source->getInventory()->equipItem($this->hotbarSlot, $this->inventorySlot);
		$source->setGenericFlag(Player::DATA_FLAG_ACTION, false);
		return true;
	}
}
