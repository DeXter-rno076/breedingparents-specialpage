//offsets of the svgTag when the user started to move it
let yOffset = 0;
let xOffset = 0;

//mouse/touch coordinates when the user started to move the svg
let cursorStartingX = 0;
let cursorStartingY = 0;

function initTouchMoving (eventTouch) {
	startMoving('touch', eventTouch.clientX, eventTouch.clientY);
}

function initMouseMoving (event) {
    startMoving('mouse', event.clientX, event.clientY);
}

function startMoving (type, x, y) {
	initMovementVariables(x, y);
    attachMovementListeners(type);
}

function initMovementVariables (x, y) {
	cursorStartingX = x;
    cursorStartingY = y;
    xOffset = parseInt(svgTag.style.left);
    yOffset = parseInt(svgTag.style.top);
}

function attachMovementListeners (type) {
	if (type === 'mouse') {
        svgContainer.addEventListener('mousemove', moveSVG);
    } else if (type === 'touch') {
		svgContainer.addEventListener('touchmove', touchMove);
	}
}

function touchMove (event) {
    event.preventDefault();

	if (touchPair.getCurrentMovementMode() === TOUCH_MODE_MOVING) {
		moveSVG(event.touches[0]);
	} else {
		touchZoom(event);
	}

	touchPair.updateTouches(event);
}

function moveSVG (event) {
    let x = event.clientX;
    let y = event.clientY;

    let dx = cursorStartingX - x;
    let dy = cursorStartingY - y;

    let newX = xOffset - dx;
    let newY = yOffset - dy;

    setOffset(newX, newY);
}

function setOffset (x, y) {
    svgTag.style.left = x + 'px';
    svgTag.style.top = y + 'px';
}

function getOffset () {
	return {
		x: parseInt(svgTag.style.left),
		y: parseInt(svgTag.style.top)
	};
}

function centerSVG () {
	console.log('centering');
    const xOffset = (containerWidth - svgWidth) / 2;
    const yOffset = (containerHeight - svgHeight) / 2;

    setOffset(xOffset, yOffset);
}