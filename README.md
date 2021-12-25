# breedingparents-specialpage
MediaWiki special page extension for displaying the possible breeding chains of a given Pokémon and move.

# next steps
* Babys: use egg groups of evolutions (egg groups is unknown => doesnt work right now)
* add evolution connection for evos => evos use breeding tree of stage 1 pkmn and have something like `pkmn <= evolution = stage 1 <=...`
* add and handle oldGenLearnsets
* special cases
  * stuff like one gender only or Farbeagle
* visual stuff
  * improve button look for small widths
  * add touch move functionality to JS
  * add button toolbar (with position:fixed) for mobile users
* remove Wasserzeichen
* center breeding tree
* only activate moving functionality when the breeding tree exceeds the screen
* localisation files (aren't properly loaded right now)
* adjust needed php version
* replace hardcoded text outputs with i18n messages

# bugs
* handle wrong input (e. g. 'a' as pkmn name etc.)

# other stuff
* dont remove time logs in the end, instead add a debug param to qs (may be useful at some point)
  * add a check box for debug param for esb users
* find an idea for an easter egg (maybe when:
    * targetPkmn = Greenchu:
    * targetPkmn = DeXter:
)
* look for throwables and catch them

# after 'release'
* solve redundancies (e. g. check each tree section for equality => do this bottom up => much more efficient, Glumanda Konter Gen8 is one of the extreme cases)
* set single successors to same y coord as the predecessor
* move single successors closer to their neighbours => less whitespace

# notes
* learns by old gen purposely only works with direct learnsets. Otherwise you would have to *start* to create a completely independet breeding tree for each Pokémon in order to know whether it can successfully inherit the move. But this is not impossible. You'd just probably have to cache every breeding tree for performance reasons, but the benefit additional effort ratio is by far too bad for v1.0 (right now there is no caching because caching itself probably is slower than creating the breeding trees)