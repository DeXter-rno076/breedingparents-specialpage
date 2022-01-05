# breedingparents-specialpage
MediaWiki special page extension for displaying the possible breeding chains of a given Pokémon and move.

# next steps
* visual stuff
  * zoom for mobile users (almost forgot it)
  * remove the draft while zooming by recalculating the offset for every zoom
* adjust needed php version

# bugs
* for evo connections the lowest evo appears twice in the tree

# other stuff
* find an idea for an easter egg (maybe when:
    * targetPkmn = Greenchu:
    * targetPkmn = DeXter:
)

# after 'release'
* solve redundancies (e. g. check each tree section for equality => do this bottom up => much more efficient, Glumanda Konter Gen8 is one of the extreme cases)
* set single successors to same y coord as the predecessor
* move single successors closer to their neighbours => less whitespace
* maybe put tutor learnsets into a separate list and handle it like event or old gen

# notes
* learns by old gen purposely only works with direct learnsets. Otherwise you would have to *start* to create a completely independet breeding tree for each Pokémon in order to know whether it can successfully inherit the move. But this is not impossible. You'd just probably have to cache every breeding tree for performance reasons, but the benefit additional effort ratio is by far too bad for v1.0 (right now there is no caching because caching itself probably is slower than creating the breeding trees)