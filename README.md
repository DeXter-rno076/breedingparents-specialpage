# breedingparents-specialpage
MediaWiki special page extension for displaying the possible breeding chains of a given Pokémon and move.

# next steps
* finish backend for normal forms
* add the unbreedable functionality
  * legendaries
  * pkmn without breeding learnsets
  * pkmn with special gender stuff
  * Ditto and other special cases
* first testing round
  * finish data set programs and testing help program for this
* add support for special forms and special cases
* handle that pkmn can be transeferred from older gens (will be probably mainly data set stuff)
* document this
  * especially everything needed for backend handler (probably some crazy af pdf document with sick grafics)
* visual stuff
  * improve button look for small widths
  * add touch move functionality to JS
  * add button toolbar (with position:fixed) for mobile users
* localisation files (aren't properly loaded right now)
* adjust needed php version

# bugs
* fix that lines sometimes touch pkmn icons in very long breeding trees
* handle wrong input (e. g. 'a' as pkmn name etc.)
* line endings of event pkmn have too low y coords (this is so little though that you can ignore it)

# other stuff
* set initial y offset of svg so that the target pkmn is in the middle
* change icon links to move subpages and corresponding gen section
* dont remove time logs in the end, instead add a debug param to qs (may be useful at some point)