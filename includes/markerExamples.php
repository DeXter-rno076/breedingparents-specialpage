<?php
$markerExamplesTable = new HTMLElement('table', [
    'id' => 'breedingParentsExplanationTable'
], [
    new HTMLElement('th',['colspan' => 2], [$this->msg('breedingparents-markerexplanation-head')]),
    new HTMLElement('tr', [], [
        new HTMLElement('td', [], [
            new HTMLElement('svg', [
                'id' => 'breedingParentsEventMarkerExample',
                'class' => 'breedingParentsSVGExample',
                'xmlns' => 'http://www.w3.org/2000/svg',
                'width' => 50,
                'height' => 50
            ], [
                new HTMLElement('a',[
                    'href' => 'Mewtu/Attacken#8. Generation'
                ], [
                    new HTMLElement('circle', [
                        'cx' => 25,
                        'cy' => 25,
                        'r' => 24
                    ]),
                    new HTMLElement('image', [
                        'x' => 10,
                        'y' => 5,
                        'width' => 32,
                        'height' => 42,
                        'xlink:href' => '/localwiki/images/9/92/Pok%C3%A9mon-Icon_150.png'
                    ])
                ])
            ]),
        ]),
        new HTMLElement('td', [], [
            $this->msg('breedingparents-markerexplanation-oldgen')
        ])
    ]),
    new HTMLElement('tr', [], [
        new HTMLElement('td', [], [
            new HTMLElement('svg', [
                'id' => 'breedingParentsEventMarkerExample',
                'class' => 'breedingParentsSVGExample',
                'xmlns' => 'http://www.w3.org/2000/svg',
                'width' => 50,
                'height' => 56
            ], [
                new HTMLElement('a', [
                    'href' => 'Mewtu/Attacken#8. Generation'
                ], [
                    new HTMLElement('rect', [
                        'x' => 2,
                        'y' => 2,
                        'width' => 46,
                        'height' => 52,
                        'rx' => 6,
                        'ry' => 6
                    ]),
                    new HTMLElement('image', [
                        'x' => 7,
                        'y' => 7,
                        'width' => 32,
                        'height' => 42,
                        'xlink:href' => '/localwiki/images/9/92/Pok%C3%A9mon-Icon_150.png'
                    ])
                ])
            ])
        ]),
        new HTMLElement('td', [], [
            $this->msg('breedingparents-markerexplanation-event')
        ])
    ])
]);