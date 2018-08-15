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

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Transaction{
	/** @var bool */
	protected $hasExecuted = false;
	/** @var Player */
	protected $source;
	/** @var Inventory[] */
	protected $inventories = [];
	/** @var Action[] */
	protected $actions = [];

	/**
	 * @param Player $source
	 * @param Action[] $actions
	 */
	public function __construct(Player $source, array $actions = []){
		$this->source = $source;
		foreach($actions as $action){
			$this->addAction($action);
		}
	}

	/**
	 * @return Player
	 */
	public function getSource() : Player{
		return $this->source;
	}

	/**
	 * @return Inventory[]
	 */
	public function getInventories() : array{
		return $this->inventories;
	}

	/**
	 * @return Action[]
	 */
	public function getActions() : array{
		return $this->actions;
	}

	/**
	 * @param Action $action
	 */
	public function addAction(Action $action) : void{
		if(!isset($this->actions[$hash = spl_object_hash($action)])){
			$action->onAddToTransaction($this);

			$this->actions[$hash] = $action;
			if($action instanceof InventoryAction){
				$this->inventories[spl_object_hash($action->getInventory())] = $action->getInventory();
			}
		}else{
			throw new \InvalidArgumentException("Tried to add the same action to a transaction twice");
		}
	}

	/**
	 * @param Inventory $inventory
	 * @param int $slot
	 * @param float|null $before
	 * @return SlotChangeAction[]
	 */
	public function getSlotChanges(Inventory $inventory, int $slot, ?float $before = null) : array{
		$changes = [];

		foreach($this->actions as $action){
			if($before!== null and $action->getCreationTime() >= $before){
				continue;
			}

			if($action instanceof SlotChangeAction and $action->getInventory() === $inventory and $action->getChangedSlot() === $slot){
				$changes[] = $action;
			}
		}

		return $changes;
	}

	/**
	 * Sends the contents of the involved in this transaction {@see Inventory inventories} to the source player.
	 */
	protected function sendInventories() : void{
		foreach($this->inventories as $inventory){
			$inventory->sendContents($this->source);
		}
	}

	/**
	 * Checks if all the inputs have the corresponding outputs.
	 * @param Item[] $needItems
	 * @param Item[] $haveItems
	 * @return bool
	 */
	protected function matchItems(array &$haveItems, array &$needItems) : bool{
		foreach($this->actions as $action){
			foreach($action->getSourceItems() as $sourceItem){
				if(!$sourceItem->isNull()){
					$haveItems[] = $sourceItem;
				}
			}

			foreach($action->getTargetItems() as $targetItem){
				if(!$targetItem->isNull()){
					$needItems[] = $targetItem;
				}
			}
		}

		foreach($haveItems as $i => $haveItem){
			foreach($needItems as $j => $needItem){
				if($needItem->equals($haveItem, $needItem->hasAnyDamageValue() and $haveItem->hasAnyDamageValue())){
					$amount = min($haveItem->getCount(), $needItem->getCount());

					$haveItem->pop($amount);
					$needItem->pop($amount);

					if($haveItem->getCount() === 0){
						unset($haveItems[$i]);
					}

					if($needItem->getCount() === 0){
						unset($needItems[$j]);
					}
				}
			}
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function canExecute() : bool{
		if(count($this->actions) === 0){
			return false;
		}

		$haveItems = [];
		$needItems = [];

		return $this->matchItems($haveItems, $needItems) and empty($haveItems) and empty($needItems);
	}

	/**
	 * @return bool
	 */
	public function execute() : bool{
		if($this->hasExecuted or !$this->canExecute()){
			return false;
		}

		foreach($this->actions as $action){
			if(!$action->isValid($this)){
				$this->sendInventories();
				return false;
			}
		}

		$this->source->getServer()->getPluginManager()->callEvent($event = new InventoryTransactionEvent($this));
		if($event->isCancelled()){
			$this->sendInventories();
			return false;
		}

		foreach($this->actions as $action){
			if(!$action->onPreExecute($this->source)){
				$this->sendInventories();
				return false;
			}
		}

		foreach($this->actions as $action){
			if($action->execute($this->source)){
				$action->onExecuteSuccess($this->source);
			}else{
				$action->onExecuteFail($this->source);
			}
		}

		$this->hasExecuted = true;
		return true;
	}

	/**
	 * @return bool
	 */
	public function hasExecuted() : bool{
		return $this->hasExecuted;
	}
}
