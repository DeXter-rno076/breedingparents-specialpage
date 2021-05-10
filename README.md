# breedingparents-specialpage
MediaWiki special page extension for displaying the possible breeding chains of a given Pokémon and move.

# next steps
* support normal forms for every gen
* first testing round of backend
* debug (bug list is below)
* add looser blacklist handling
* support special forms and special cases
* add the unbreedable functionality
  * legendaries
  * pkmn without breeding learnsets
  * pkmn with special gender stuff
  * Ditto and other special cases
* adjust the look for small screen widths
* document this
  * especially everything needed for gen handlers
* testing
  * test every kind of special case
  * every possible problem
  * bring it into the testwiki and let multiple people test it
  * maybe even do this arcane unit + integration testing like a professional beast
* localisation files (aren't properly loaded right now)
* adjust needed php version
* find some way to prevent the tab from having to load several eternities when having BIIIIG breeding trees

# bugs
* handle wrong input (e. g. 'a' as pkmn name etc.)
* line endings of event pkmn have too low y coords (this is so little though that you can ignore it)
* the page freezes sometimes when moving the svg of extreme cases with looser blacklist handling (more in svgMover.js)

# other stuff
* handling of icon stuff in frontend preparation feels **very** unclean :(
  * maybe create a parent class for BreedingChainNode and FrontendPkmnObj
  * Refactoring frontend stuff in general would be nice
* set initial y offset of svg so that the target pkmn is in the middle