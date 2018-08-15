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

use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\item\Item;
use pocketmine\Player;

class DropItemAction extends Action{
	/**
	 * @param Item|Item[] $droppedItems
	 */
	public function __construct($droppedItems){
		parent::__construct([], $droppedItems);
	}

	/**
	 * @param Transaction $transaction
	 * @return bool
	 */
	public function isValid(Transaction $transaction) : bool{
		return true;
	}

	/**
	 * @param Player $source
	 * @return bool
	 */
	public function onPreExecute(Player $source) : bool{
		foreach($this->targetItems as $item){
			$source->getServer()->getPluginManager()->callEvent($event = new PlayerDropItemEvent($source, $item));
			if($event->isCancelled()){
				return false;
			}
		}

		return true;
	}

	/**
	 * @param Player $source
	 * @return bool
	 */
	public function execute(Player $source) : bool{
		foreach($this->targetItems as $item){
			$source->dropItem($item);
		}

		return true;
	}
}
