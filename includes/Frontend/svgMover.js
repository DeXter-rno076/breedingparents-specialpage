'use strict';
//todo x y input with button (move around more quickly on big svgs)
//todo (idea) if the user moves the svg out of the visible area, highlight the reset button

const svgContainer = document.getElementById('breedingParentsSVGContainer');
const svgTag = document.getElementById('breedingParentsSVG');
const resetButton = document.getElementById('breedingParentsSVGResetButton');

let yOffset = 0;
let xOffset = 0;
let cursorStartingX = 0;
let cursorStartingY = 0;

resetButton.addEventListener('click', () => {
	svgTag.style.top = '0px';
	svgTag.style.left = '0px';
});
svgContainer.addEventListener('mousedown', startScrolling);
svgContainer.addEventListener('mouseup', stopScrolling);

function startScrolling (event) {
	cursorStartingX = event.clientX;
	cursorStartingY = event.clientY;
	xOffset = parseInt(svgTag.style.left) || 0;
	yOffset = parseInt(svgTag.style.top) || 0;

	svgContainer.addEventListener('mousemove', scrollSVG);
}

function stopScrolling () {
	svgContainer.removeEventListener('mousemove', scrollSVG);
}

function scrollSVG (event) {
	let x = event.clientX;
	let y = event.clientY;

	let dx = cursorStartingX - x;
	let dy = cursorStartingY - y;

	let newX = xOffset - dx;
	let newY = yOffset - dy;

	svgTag.style.left = newX + 'px';
	svgTag.style.top = newY + 'px';
}