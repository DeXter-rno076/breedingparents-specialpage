'use strict';
//todo x y input with button (move around more quickly on big svgs)
//todo (idea) if the user moves the svg out of the visible area, highlight the reset button
//todo maybe it's needed to load only the visible svg elements when using looser blacklist handling (Pikachu and Ausdauer killed the tab multiple times)

//todo sometimes this isn't loaded

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
svgContainer.addEventListener('mousedown', startMoving);
svgContainer.addEventListener('mouseup', stopMoving);

function startMoving (event) {
	cursorStartingX = event.clientX;
	cursorStartingY = event.clientY;
	xOffset = parseInt(svgTag.style.left) || 0;
	yOffset = parseInt(svgTag.style.top) || 0;

	svgContainer.addEventListener('mousemove', moveSVG);
}

function stopMoving () {
	svgContainer.removeEventListener('mousemove', moveSVG);
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