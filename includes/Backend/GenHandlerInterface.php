<?php
interface GenHandlerInterface {
	public function createBreedingChainNode (
		$pkmn,
		&$pkmnBlacklist,
		$eggGroupBlacklist
	);

	public function handleDirectLearnability ($chainNode);

	public function handleInheritance ($params, &$pkmnBlacklist);

	public function handleSpecialLearnability ($chainNode);
}
?>