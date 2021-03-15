'use strict';
/**
 * big todo
 * works meh :( (first draft to make it work)
 * if you get out of the container div while holding down the mouse butten, you got a problem
 * this crazy vibrating is still there but "behavior: 'smooth'" makes it appear smoother
 */
const svgContainer = document.getElementById('breedingParentsSVGContainer');

let active = false;
let originalX = 0;
let originalY = 0;
let origOffsetX = 0;
let origOffsetY = 0;

svgContainer.addEventListener('mousedown', startScrolling);

svgContainer.addEventListener('mousemove', scrollSVG);

svgContainer.addEventListener('mouseup', stopScrolling);

function startScrolling (event) {
	console.log('mouse down');
	originalX = event.offsetX;
	originalY = event.offsetY;
	origOffsetX = svgContainer.scrollLeft;
	origOffsetY = svgContainer.scrollTop;
	active = true;
}

function stopScrolling () {
	console.log('mouse up');
	active = false;
}

function scrollSVG (event) {
	if (!active) return;
	let x = event.offsetX;
	let y = event.offsetY;

	let dx = (x - originalX) * 2;
	let dy = (y - originalY) * 2;

	let resX = origOffsetX - dx;
	let resY = origOffsetY - dy;
	svgContainer.scrollTo({
		top: resY,
		left: resX,
		behavior: 'smooth'
	});
}