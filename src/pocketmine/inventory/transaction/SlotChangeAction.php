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

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class SlotChangeAction extends Action implements InventoryAction{
	/** @var Inventory */
	protected $inventory;
	/** @var int */
	protected $slot;

	public function __construct(Inventory $inventory, int $slot, Item $targetItem){
		parent::__construct([], $targetItem);
		$this->inventory = $inventory;
		$this->slot = $slot;
	}

	public function getSourceItem() : Item{
		return empty($this->sourceItems) ? ItemFactory::get(Item::AIR, 0, 0) : clone $this->sourceItems[0];
	}

	public function getTargetItem() : Item{
		return clone $this->targetItems[0];
	}

	/**
	 * Get the {@see Inventory} involved in this {@see Action}
	 * @return Inventory
	 */
	public function getInventory() : Inventory{
		return $this->inventory;
	}

	public function getChangedSlot() : int{
		return $this->slot;
	}

	/**
	 * @param Transaction $transaction
	 */
	public function onAddToTransaction(Transaction $transaction) : void{
		$changes = $transaction->getSlotChanges($this->inventory, $this->slot, $this->creationTime);
		if(!empty($changes)){
			$this->sourceItems = end($changes)->targetItems;
		}else{
			$this->sourceItems[] = $this->inventory->getItem($this->slot);
		}
	}

	/**
	 * @param Transaction $transaction
	 * @return bool
	 */
	public function isValid(Transaction $transaction) : bool{
		$changes = $transaction->getSlotChanges($this->inventory, $this->slot, $this->creationTime);
		if(!empty($changes)){
			return end($changes)->targetItems[0]->equalsExact($this->sourceItems[0]);
		}else{
			return $this->inventory->slotExists($this->slot) and $this->inventory->getItem($this->slot)->equalsExact($this->sourceItems[0]);
		}
	}

	/**
	 * @param Player $source
	 * @return bool
	 */
	public function execute(Player $source) : bool{
		return $this->inventory->setItem($this->slot, $this->targetItems[0], false);
	}

	public function onExecuteSuccess(Player $source): void {
		$viewers = $this->inventory->getViewers();
		unset($viewers[spl_object_hash($source)]);
		$this->inventory->sendSlot($this->slot, $viewers);
	}

	public function onExecuteFail(Player $source): void {
		$this->inventory->sendSlot($this->slot, $source);
	}
}
