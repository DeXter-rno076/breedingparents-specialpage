console.time('creating form');
addCustomMethods();

const gameToSk = mw.config.get('breedingchains-game-to-sk');
const gameNames = Object.keys(gameToSk);
const moveSuggestions = mw.config.get('breedingchains-move-suggestions');
const pkmnNames = Object.keys(moveSuggestions);

const pkmnOptionsPromise = createPkmnOptions();

let currentMoveSuggestions = [];

const qsParams = new Proxy(new URLSearchParams(window.location.search), {
    get: (searchParams, prop) => searchParams.get(prop),
});


const gameInput = new OO.ui.ComboBoxInputWidget({
    placeholder: mw.config.get('breedingchains-game-input-placeholder'),
    value: qsParams.targetGame || undefined,
    options: arrayToOptionsArray(gameNames)
});
const gameInputField = new OO.ui.FieldLayout(gameInput);


const pkmnInput = new OO.ui.ComboBoxInputWidget({
    placeholder: mw.config.get('breedingchains-pkmn-input-placeholder'),
    value: qsParams.targetPkmn || undefined,
    options: []
});
const pkmnInputField = new OO.ui.FieldLayout(pkmnInput);


const moveInput = new OO.ui.ComboBoxInputWidget({
    placeholder: mw.config.get('breedingchains-move-input-placeholder'),
    value: qsParams.targetMove || undefined,
    options: []
});
const moveInputField = new OO.ui.FieldLayout(moveInput);


const displayDebugLogs = new OO.ui.CheckboxInputWidget({
    selected: qsParams.displayDebugLogs || false
});
const displayDebugLogsField = new OO.ui.FieldLayout(
    displayDebugLogs, {label: 'display debug logs', align: 'inline'});


const displayStatusLogs = new OO.ui.CheckboxInputWidget({
    selected: qsParams.displayStatusLogs || false
});
const displayStatusLogsField = new OO.ui.FieldLayout(
    displayStatusLogs, {label: 'display status logs', align: 'inline'});


const createDetailedSuccessorFilterLogs = new OO.ui.CheckboxInputWidget({
    selected: qsParams.createDetailedSuccessorFilterLogs || false
});
const createDetailedSuccessorFilterLogsField = new OO.ui.FieldLayout(
    createDetailedSuccessorFilterLogs, {label: 'print detailed logs', align: 'inline'});


const submitButton = new OO.ui.ButtonInputWidget({
    label: mw.config.get('breedingchains-submit-text'),
    icon: 'check',
    flags: 'progressive',
    type: 'submit'
});
const submitButtonField = new OO.ui.FieldLayout(submitButton);

addComponents();
changeMoveSuggestions();
changePkmnSuggestions();
checkAndSetGameInputWarnings();
checkAndSetPkmnInputWarnings();
checkAndSetMoveInputWarnings();

addEventListeners();

console.timeEnd('creating form');
$('#specialBreedingChainsLoadingBar').remove();
setInitialScrollState();

function clearErrorsAndWarnings (field) {
    field.setErrors([]).setWarnings([]);
}

function arrayToOptionsArray (array) {
    const result = [];
    for (const item of array) {
        result.push({data: item});
    }
    return result;
}

function addComponents () {
    const wrapperId = 'specialBreedingChainsFormContainer';

    const outerFieldset = new OO.ui.FieldsetLayout({
        id: 'specialBreedingChainsForm'
    });
    const innerFieldSets = [];

    const textInputFieldset = new OO.ui.FieldsetLayout();
    textInputFieldset.addItems([
        gameInputField,
        pkmnInputField,
        moveInputField
    ]);

    innerFieldSets.push(textInputFieldset);

    if (mw.config.get('breedingchains-display-debug-checkboxes')) {
        const buttonFieldSet = new OO.ui.FieldsetLayout();
        buttonFieldSet.addItems([
            displayDebugLogsField,
            displayStatusLogsField,
            createDetailedSuccessorFilterLogsField
        ]);

        innerFieldSets.push(buttonFieldSet);
    }

    const submitFieldSet = new OO.ui.FieldsetLayout();
    submitFieldSet.addItems([
        submitButtonField
    ]);
    innerFieldSets.push(submitFieldSet);


    outerFieldset.addItems(innerFieldSets);

    $(`#${wrapperId}`).append(
        outerFieldset.$element
    );
}

function addEventListeners () {
    submitButton.on('click', submitForm);

    gameInput.on('change', function () {
        changeMoveSuggestions();
        changePkmnSuggestions();

        clearErrorsAndWarnings(gameInputField);
        clearErrorsAndWarnings(pkmnInputField);
        clearErrorsAndWarnings(moveInputField);

        checkAndSetPkmnInputWarnings();
        checkAndSetMoveInputWarnings();
        checkAndSetGameInputWarnings();
    });

    pkmnInput.on('change', function () {
        changeMoveSuggestions();

        clearErrorsAndWarnings(pkmnInputField);
        clearErrorsAndWarnings(moveInputField);

        checkAndSetPkmnInputWarnings();
        checkAndSetMoveInputWarnings();
    });
    moveInput.on('change', function () {
        clearErrorsAndWarnings(moveInputField);

        checkAndSetMoveInputWarnings();
    });
}

function checkAndSetGameInputWarnings () {
    if (gameInput.getValue() !== '' && !gameNames.includes(gameInput.getValue())) {
        gameInputField.setWarnings([
            mw.config.get('breedingchains-unknown-game').replace('$1', gameInput.getValue())
        ]);
    }
}

function checkAndSetPkmnInputWarnings () {
    if (pkmnInput.getValue() === '') return;

    if (!pkmnNames.includes(pkmnInput.getValue())) {
        pkmnInputField.setWarnings([
            mw.config.get('breedingchains-unknown-pkmn').replace('$1', pkmnInput.getValue())
        ]);
    } else if (currentMoveSuggestions.length === 0) {
        pkmnInputField.setWarnings([
            mw.config.get('breedingchains-pkmn-has-no-breedingmoves').replace('$1', pkmnInput.getValue())
            .replace('$2', gameInput.getValue())
        ]);
    }
}

function checkAndSetMoveInputWarnings () {
    const moveInputStr = moveInput.getValue();

    if (moveInputStr !== '' && currentMoveSuggestions.length > 0 
            && !currentMoveSuggestions.includes(moveInputStr)) {
        moveInputField.setWarnings([
            mw.config.get('breedingchains-move-not-suggested')
                            .replace('$1', moveInputStr)
                            .replace('$2', pkmnInput.getValue())
        ]);
    }
}

function changeMoveSuggestions () {
    moveInput.setOptions([]);
    currentMoveSuggestions = [];

    const pkmnName = pkmnInput.getValue();
    const suggestions = moveSuggestions[pkmnName];
    if (suggestions === undefined) {
        //console.debug('unknown pkmn name ' + pkmnName);
        return;
    }
    const game = gameInput.getValue();
    const targetSuggestions = getMoveSuggestionsForGame(suggestions, game);
    //console.debug('new moves:' + targetSuggestions);
    const targetSuggestionsAsOptionsArray = arrayToOptionsArray(targetSuggestions);

    moveInput.setOptions(targetSuggestionsAsOptionsArray);
    currentMoveSuggestions = targetSuggestions;
}

function getMoveSuggestionsForGame (suggestions, game) {
    if (!Array.isArray(suggestions)) {
        console.error('invalid argument, suggestions must be an array');
        return [];
    }

    const gameSk = gameToSk[game];
    if (gameSk === undefined) {
        //console.debug('unknown game ' + game);
    }

    const suggestionBlock = suggestions.find(function (item) {
        return item.games.includes(gameSk);
    });

    if (suggestionBlock !== undefined) {
        return suggestionBlock.moves;
    } else {
        return [];
    }
}

function submitForm () {
    const textInputErrorStatus = checkForInputErrors();
    if (textInputErrorStatus) {
        return;
    }

    const baseUrl = window.location.origin + window.location.pathname;

    const qs = new URLSearchParams({
        'targetGame': gameInput.getValue(),
        'targetPkmn': pkmnInput.getValue(),
        'targetMove': moveInput.getValue(),
    });

    if (displayDebugLogs.isSelected !== undefined && displayDebugLogs.isSelected()) {
        qs.append('displayDebugLogs', '1');
    }
    if (displayStatusLogs.isSelected !== undefined && displayStatusLogs.isSelected()) {
        qs.append('displayStatusLogs', '1');
    }
    if (createDetailedSuccessorFilterLogs.isSelected !== undefined
            && createDetailedSuccessorFilterLogs.isSelected()) {
        qs.append('createDetailedSuccessorFilterLogs', '1');
    }

    const targetUrl = baseUrl + '?' + qs.toString();

    open(targetUrl, '_self');
}

function checkForInputErrors () {
    let errorOccured = false;

    if (gameInput.getValue() === '') {
        gameInputField.setErrors([mw.config.get('breedingchains-game-required')]);
        errorOccured = true;
    }
    if (pkmnInput.getValue() === '') {
        pkmnInputField.setErrors([mw.config.get('breedingchains-pkmn-required')]);
        errorOccured = true;
    }
    if (moveInput.getValue() === '') {
        moveInputField.setErrors([mw.config.get('breedingchains-move-required')]);
        errorOccured = true;
    }

    return errorOccured;
}

function setInitialScrollState () {
    const msgBoxes = $('.breedingChainsMessageBox');
    if (msgBoxes.length) {
        msgBoxes[0].scrollIntoView({
            behavior: 'smooth'
        });
        return;
    }

    const breedingChainsSVGMap = $('#breedingChainsSVGMap');
    if (breedingChainsSVGMap.length) {
        breedingChainsSVGMap[0].scrollIntoView({
            behavior: 'smooth'
        });
    }
}

function changePkmnSuggestions () {
    const gameSk = gameToSk[gameInput.getValue()];
    if (gameSk === undefined) {
        return;
    }

    pkmnOptionsPromise.then(function (options) {
        const targetedPkmn = options.filter(({option, suggestions}) => {
            const targetedSuggestions = suggestions.find(item => {
                return item.games.includes(gameSk)
            });
            return targetedSuggestions !== undefined && targetedSuggestions.moves.length > 0;
        });

        const targetedPkmnOptions = targetedPkmn.map(item => item.option);

        pkmnInput.setOptionsFast(targetedPkmnOptions);
    });
}

function createPkmnOptions () {
    return new Promise(resolve => {
        //Promise.resolve().then to prevent this from just executing synchronously
        //  resulting in no performance benefit
        Promise.resolve().then(() => {
            const result = Object.entries(moveSuggestions).map(function([pkmnName, suggestions]) {
                return {
                    option: new OO.ui.MenuOptionWidget({data: pkmnName, label: pkmnName}),
                    suggestions
                }
            });
            resolve(result);
        })
    });
    
}

function addCustomMethods () {
    addFastOptionsSetting();

    /**
     * automatic focus on dropdown button click is extremely annoying on mobile phones
     */
    OO.ui.ComboBoxInputWidget.prototype.onDropdownButtonClick = function () {
        this.menu.toggle();
        //this.focus();
    };
}

/**
 * For changePkmnSuggestions a way to set a LOT of options fast is needed
 * this custom setOpionsFast function does two things for that:
 *      - as input it directly takes MenuOptionWidget obects so that they only have to be created once
 *      - default OO.EmitterList.prototype.addItems has two event emits that are VERY performance heavy
 *          but they are appearently irrelevant for displaying the options -> are cut out
 */
function addFastOptionsSetting () {
    OO.ui.ComboBoxInputWidget.prototype.setOptionsFast = function (options) {
        this.getMenu()
            .clearItems()
            .addItemsFast(options);
        return this;
    }

    OO.ui.MenuSelectWidget.prototype.addItemsFast = function ( items, index ) {
        if ( !items || !items.length ) {
            return this;
        }
    
        // Parent method
        OO.ui.MenuSelectWidget.super.prototype.addItemsFast.call( this, items, index );
    
        this.updateItemVisibility();
    
        return this;
    };

    OO.ui.SelectWidget.prototype.addItemsFast = function ( items, index ) {
        if ( !items || !items.length ) {
            return this;
        }
    
        // Mixin method
        OO.ui.mixin.GroupWidget.prototype.addItemsFast.call( this, items, index );

        // Always provide an index, even if it was omitted
        this.emit( 'add', items, index === undefined ? this.items.length - items.length - 1 : index );
    
        return this;
    };

    OO.ui.mixin.GroupWidget.prototype.addItemsFast = function ( items, index ) {
        if ( !items || !items.length ) {
            return this;
        }
    
        // Mixin method
        OO.EmitterList.prototype.addItemsFast.call( this, items, index );

        this.emit( 'change', this.getItems() );
        return this;
    };
    
    OO.EmitterList.prototype.addItemsFast = function ( items, index ) {
        if ( !Array.isArray( items ) ) {
            items = [ items ];
        }

        if ( items.length === 0 ) {
            return this;
        }

        index = normalizeArrayIndex( this.items, index );

        for ( var i = 0; i < items.length; i++ ) {
            var oldIndex = this.items.indexOf( items[ i ] );

            if ( oldIndex !== -1 ) {
                // Move item to new index
                index = this.moveItem( items[ i ], index );
                //removed for performance reasons: this.emit( 'move', items[ i ], index, oldIndex );
            } else {
                // insert item at index
                index = this.insertItem( items[ i ], index );
                //removed for performance reasons: this.emit( 'add', items[ i ], index );
            }
            index++;
        }
        return this;
    };

    function normalizeArrayIndex( arr, index ) {
        return ( index === undefined || index < 0 || index >= arr.length ) ?
            arr.length :
            index;
    }
}