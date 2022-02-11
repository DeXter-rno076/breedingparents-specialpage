function touchZoom (event) {
	const movedTouchCoordinatesDifference = touchPair.getMovedTouchCoordinatesDifference(event);

	//todo this name is sh*t, the entire zoom multiplication stuff feels bad af, but it makes zooming suitable for mouse and touch
	const factor = Math.abs(movedTouchCoordinatesDifference) / 10;

	if (movedTouchCoordinatesDifference > 0) {
		changeZoom(ZOOM_IN, factor);
	} else if (movedTouchCoordinatesDifference < 0) {
		changeZoom(ZOOM_OUT, factor);
	}
}


function zoomMouse (event) {
    event.preventDefault();
    const wheelUp = event.deltaY < 0;

    changeZoom(wheelUp);

    //todo
    // const currentXOffset = parseInt(svgTag.style.left);
    // const currentYOffset = parseInt(svgTag.style.top);

    // const plainMx = svgTag.width.baseVal.value / 2;
    // const plainMy = svgTag.height.baseVal.value / 2;

    // const realMx = plainMx + currentXOffset;
    // const realMy = plainMy + currentYOffset;


    //c means cursor
    // let cx = event.offsetX;
    // let cy = event.offsetY;

    // let xDiff = cx / prevX;
    // prevX = cx;
    // let zoomDiff = currentZoom / prevZoom;
    // prevZoom = currentZoom;

    // if (event.target.id === 'breedingParentsSVG') {
    //     cx = cx * currentZoom + currentXOffset;
    //     cy = cy * currentZoom + currentYOffset;
    // }

    // const cmx = realMx - cx;
    // const cmy = realMy - cy;

    // const xPush = cmx * (newZoom - 1);
    // const yPush = cmy * (newZoom - 1);

    //svgTag.style.left = currentXOffset + xPush + 'px';
    //svgTag.style.top = currentYOffset + yPush + 'px';
}

function changeZoom (zoomIn, factor = 1) {
	const currentTransform = svgTag.style.transform;
    if (!/scale\(.+?\)/.test(currentTransform)) {
        //user played around with the transform value
        svgTag.style.transform = 'scale(1)';
        return;
    }

	let newZoom = 1;
    const zoomChange = global_currentZoom * 0.05 * factor;
    if (zoomIn) {
        newZoom = global_currentZoom + zoomChange;
    } else {
        newZoom = global_currentZoom - zoomChange;
    }

    if (isNaN(newZoom)) {
        newZoom = 1;
    } else if (newZoom <= 0.1) {
        newZoom = 0.1;
    } else if (newZoom > 5) {
        newZoom = 5;
    }

	global_currentZoom = newZoom;
	svgTag.style.transform = 'scale(' + global_currentZoom + ')';
}