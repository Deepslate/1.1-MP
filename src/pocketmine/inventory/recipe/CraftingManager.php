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

namespace pocketmine\inventory\recipe;

use pocketmine\event\Timings;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\MainLogger;
use pocketmine\utils\UUID;

class CraftingManager{
	/** @var Recipe[] */
	public $recipes = [];

	/** @var ShapedRecipe[][] */
	protected $shapedRecipes = [];

	/** @var ShapelessRecipe[][] */
	protected $shapelessRecipes = [];

	/** @var FurnaceRecipe[] */
	protected $furnaceRecipes = [];

	private static $RECIPE_COUNT = 0;


	/** @var CraftingDataPacket */
	private $craftingDataCache;

	/**
	 * @throws \Exception
	 */
	public function __construct(){
		// load recipes from src/pocketmine/resources/recipes.json
		$recipes = new Config(Server::getInstance()->getFilePath() . "src/pocketmine/resources/recipes.json", Config::JSON, []);

		MainLogger::getLogger()->info("Loading recipes...");
		foreach($recipes->getAll() as $recipe){
			switch($recipe["type"]){
				case 0:
					$this->registerRecipe(new ShapelessRecipe(
						array_map(function(array $data) : Item{ return Item::jsonDeserialize($data); }, $recipe["input"]),
						array_map(function(array $data) : Item{ return Item::jsonDeserialize($data); }, $recipe["output"])
					));
					break;
				case 1:
					$this->registerRecipe(new ShapedRecipe(
						array_chunk(array_map(function(array $data) : Item{ return Item::jsonDeserialize($data); }, $recipe["input"]), $recipe["width"]),
						array_map(function(array $data) : Item{ return Item::jsonDeserialize($data); }, $recipe["output"])
					));
					break;
				case 2:
				case 3:
					$this->registerRecipe(new FurnaceRecipe(
						Item::jsonDeserialize($recipe["output"]),
						ItemFactory::get($recipe["inputId"], $recipe["inputDamage"] ?? -1, 1)
					));
					break;
				default:
					break;
			}
		}

		$this->buildCraftingDataCache();
	}

	/**
	 * Rebuilds the cached CraftingDataPacket.
	 */
	public function buildCraftingDataCache(){
		Timings::$craftingDataCacheRebuildTimer->startTiming();
		$pk = new CraftingDataPacket();
		$pk->cleanRecipes = true;

		foreach($this->recipes as $recipe){
			if($recipe instanceof ShapedRecipe){
				$pk->addShapedRecipe($recipe);
			}elseif($recipe instanceof ShapelessRecipe){
				$pk->addShapelessRecipe($recipe);
			}
		}

		foreach($this->furnaceRecipes as $recipe){
			$pk->addFurnaceRecipe($recipe);
		}

		$pk->encode();

		$this->craftingDataCache = $pk;
		Timings::$craftingDataCacheRebuildTimer->stopTiming();
	}

	/**
	 * Returns a CraftingDataPacket for sending to players. Rebuilds the cache if it is outdated.
	 *
	 * @return CraftingDataPacket
	 */
	public function getCraftingDataPacket() : CraftingDataPacket{
		if($this->craftingDataCache === null){
			$this->buildCraftingDataCache();
		}

		return $this->craftingDataCache;
	}

	/**
	 * @param Item[] $items
	 *
	 * @return Item[]
	 */
	private static function pack(array $items) : array{
		/** @var Item[] $result */
		$result = [];

		foreach($items as $i => $item){
			foreach($result as $otherItem){
				if($item->equals($otherItem)){
					$otherItem->setCount($otherItem->getCount() + $item->getCount());
					continue 2;
				}
			}

			$result[] = clone $item;
		}

		return $result;
	}

	private static function itemHash(Item $item) : string{
		return $item->getId() . ":" . ($item->hasAnyDamageValue() ? "?" : $item->getDamage());
	}

	/**
	 * @param UUID $id
	 * @return Recipe|null
	 */
	public function getRecipe(UUID $id){
		$index = $id->toBinary();
		return $this->recipes[$index] ?? null;
	}

	/**
	 * @return Recipe[]
	 */
	public function getRecipes() : array{
		return $this->recipes;
	}

	/**
	 * @return ShapedRecipe[]
	 */
	public function getShapedRecipes() : array{
		return $this->shapedRecipes;
	}

	/**
	 * @return ShapelessRecipe[]
	 */
	public function getShapelessRecipes() : array{
		return $this->shapelessRecipes;
	}

	/**
	 * @return FurnaceRecipe[]
	 */
	public function getFurnaceRecipes() : array{
		return $this->furnaceRecipes;
	}

	/**
	 * Finds a CraftingRecipe by its primary (first) result.
	 * @param Item $result
	 * @return null|CraftingRecipe
	 */
	public function matchCraftingRecipe(Item $result) : ?CraftingRecipe{
		$hash = self::itemHash($result);

		if(isset($this->shapedRecipes[$hash])){
			foreach($this->shapedRecipes[$hash] as $recipe){
				if($recipe->getResults()[0]->equalsExact($result)){
					return $recipe;
				}
			}
		}

		if(isset($this->shapelessRecipes[$hash])){
			foreach($this->shapelessRecipes[$hash] as $recipe){
				if($recipe->getResults()[0]->equalsExact($result)){
					return $recipe;
				}
			}
		}

		return null;
	}

	/**
	 * @param Item $input
	 *
	 * @return FurnaceRecipe|null
	 */
	public function matchFurnaceRecipe(Item $input){
		if(isset($this->furnaceRecipes[$hash = self::itemHash($input)])){
			return $this->furnaceRecipes[$hash];
		}

		return null;
	}

	/**
	 * @param ShapedRecipe $recipe
	 */
	public function registerShapedRecipe(ShapedRecipe $recipe){
		$recipe->setId($uuid = UUID::fromData((string) ++self::$RECIPE_COUNT, json_encode(self::pack($recipe->getResults()))));
		$this->recipes[$uuid->toBinary()] = $recipe;
		$this->shapedRecipes[self::itemHash($recipe->getResults()[0])][] = $recipe;

		$this->craftingDataCache = null;
	}

	/**
	 * @param ShapelessRecipe $recipe
	 */
	public function registerShapelessRecipe(ShapelessRecipe $recipe){
		$recipe->setId($uuid = UUID::fromData((string) ++self::$RECIPE_COUNT, json_encode(self::pack($recipe->getResults()))));
		$this->recipes[$uuid->toBinary()] = $recipe;
		$this->shapelessRecipes[self::itemHash($recipe->getResults()[0])][] = $recipe;

		$this->craftingDataCache = null;
	}

	/**
	 * @param FurnaceRecipe $recipe
	 */
	public function registerFurnaceRecipe(FurnaceRecipe $recipe){
		$input = $recipe->getInput();
		$this->furnaceRecipes[self::itemHash($input)] = $recipe;
		$this->craftingDataCache = null;
	}

	/**
	 * @param Recipe $recipe
	 */
	public function registerRecipe(Recipe $recipe){
		$recipe->registerToCraftingManager($this);
	}
}
