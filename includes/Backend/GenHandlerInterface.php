<?php
interface GenHandlerInterface {
	public function createBreedingChainNode (
		StdClass $pkmnData,
		Array $eggGroupBlacklist,
		Array &$pkmnBlacklist
	) : ?BreedingChainNode;

	public function handleDirectLearnability (
		BreedingChainNode $node
	): BreedingChainNode;

	public function handleInheritance (
		BreedingChainNode $node,
		StdClass $pkmnObj,
		Array $eggGroupBlacklist,
		Array &$pkmnBlacklist
	) : ?BreedingChainNode;

	public function handleSpecialLearnability (
		BreedingChainNode $node
	) : BreedingChainNode;
}