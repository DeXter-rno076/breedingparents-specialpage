'use strict';
//todo support touch events

//todo (idea) if the user moves the svg out of the visible area, highlight the reset button

const svgContainer = document.getElementById('breedingParentsSVGContainer');
const svgTag = document.getElementById('breedingParentsSVG');
const resetButton = document.getElementById('breedingParentsSVGResetButton');

//offsets of the svgTag when the user started to move it
let yOffset = 0;
let xOffset = 0;

//mouse coordinates when the user started to move the svg
let cursorStartingX = 0;
let cursorStartingY = 0;

resetButton.addEventListener('click', () => {
	svgTag.style.top = '0px';
	svgTag.style.left = '0px';
});
svgContainer.addEventListener('mousedown', mouseStart);
svgContainer.addEventListener('mouseup', mouseStop);
svgContainer.addEventListener('touchstart', touchStart);
svgContainer.addEventListener('touchmove', touchMove);

function mouseStart (event) {
    startMoving('mouse', event.clientX, event.clientY);
}

function touchStart (event) {
    const firstTouch = event.targetTouches[0];
    startMoving('touch', firstTouch.clientX, firstTouch.clientY);
}

function startMoving (type, x, y) {
	cursorStartingX = x;
	cursorStartingY = y;
	xOffset = parseInt(svgTag.style.left) || 0;
	yOffset = parseInt(svgTag.style.top) || 0;

    if (type === 'mouse') {
	    svgContainer.addEventListener('mousemove', moveSVG);
    }
}

function mouseStop () {
	svgContainer.removeEventListener('mousemove', moveSVG);
}

function touchMove (event) {
    event.preventDefault();
    const firstTouch = event.targetTouches[0];
    moveSVG(firstTouch);
}

function moveSVG (event) {
	let x = event.clientX;
	let y = event.clientY;

	//coordinate differences
	let dx = cursorStartingX - x;
	let dy = cursorStartingY - y;

	let newX = xOffset - dx;
	let newY = yOffset - dy;

	svgTag.style.left = newX + 'px';
	svgTag.style.top = newY + 'px';
}