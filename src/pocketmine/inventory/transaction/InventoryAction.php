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

/**
 * All the {@see Action Actions} that involve {@see Inventory Inventories}
 * must implement this interface.
 */
interface InventoryAction{
	/**
	 * Get the {@see Inventory} involved in this {@see Action}
	 * @return Inventory
	 */
	public function getInventory() : Inventory;
}
