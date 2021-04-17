<?php
interface GenHandlerInterface {
	public function createBreedingChainNode (
		StdClass $pkmnData,
		Array &$pkmnBlacklist,
		Array $eggGroupBlacklist
	) : BreedingChainNode|null;

	public function handleDirectLearnability (
		BreedingChainNode $pkmnObj
	): BreedingChainNode;

	public function handleInheritance (
		BreedingChainNode $pkmnObj,
		StdClass $pkmnData,
		Array $eggGroupBlacklist,
		Array &$pkmnBlacklist
	) : BreedingChainNode|null;

	public function handleSpecialLearnability (
		BreedingChainNode $pkmnObj
	) : BreedingChainNode;
}