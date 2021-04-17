<?php
interface GenHandlerInterface {
	public function createBreedingChainNode (
		StdClass $pkmnData,
		Array &$pkmnBlacklist,
		Array $eggGroupBlacklist
	) : ?BreedingChainNode;

	public function handleDirectLearnability (
		BreedingChainNode $pkmnObj
	): BreedingChainNode;

	public function handleInheritance (
		BreedingChainNode $pkmnObj,
		StdClass $pkmnData,
		Array $eggGroupBlacklist,
		Array &$pkmnBlacklist
	) : ?BreedingChainNode;

	public function handleSpecialLearnability (
		BreedingChainNode $pkmnObj
	) : BreedingChainNode;
}