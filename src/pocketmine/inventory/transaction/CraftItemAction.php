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

use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\inventory\recipe\CraftingRecipe;
use pocketmine\item\Item;
use pocketmine\Player;

class CraftItemAction extends Action{
	/** @var CraftingRecipe */
	protected $recipe;

	/**
	 * @param CraftingRecipe $recipe
	 */
	public function __construct($recipe){
		if(!$recipe instanceof CraftingRecipe){
			throw new \InvalidArgumentException("Expected " . CraftingRecipe::class . ", got " .
				(is_object($recipe) ? get_class($recipe) : gettype($recipe)));
		}

		$this->recipe = $recipe;
		parent::__construct($recipe->getResults(), $recipe->getIngredientList());
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
		$source->getServer()->getPluginManager()->callEvent($event = new CraftItemEvent($source, $this->targetItems, $this->recipe));
		return !$event->isCancelled();
	}

	/**
	 * @param Player $source
	 * @return bool
	 */
	public function execute(Player $source) : bool{
		return true;
	}

	/**
	 * @param Player $source
	 */
	public function onExecuteSuccess(Player $source) : void{
		foreach($this->recipe->getResults() as $result){
			switch($result->getId()){
				case Item::CRAFTING_TABLE:
					$source->awardAchievement("buildWorkBench");
					break;
				case Item::WOODEN_PICKAXE:
					$source->awardAchievement("buildPickaxe");
					break;
				case Item::FURNACE:
					$source->awardAchievement("buildFurnace");
					break;
				case Item::WOODEN_HOE:
					$source->awardAchievement("buildHoe");
					break;
				case Item::BREAD:
					$source->awardAchievement("makeBread");
					break;
				case Item::CAKE:
					$source->awardAchievement("bakeCake");
					break;
				case Item::STONE_PICKAXE:
				case Item::GOLDEN_PICKAXE:
				case Item::IRON_PICKAXE:
				case Item::DIAMOND_PICKAXE:
					$source->awardAchievement("buildBetterPickaxe");
					break;
				case Item::WOODEN_SWORD:
					$source->awardAchievement("buildSword");
					break;
				case Item::DIAMOND:
					$source->awardAchievement("diamond");
					break;
			}
		}
	}
}
