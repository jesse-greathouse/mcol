const scaleToViewportHeight = (scale) => {
    const viewportHeight = window.innerHeight;
    const height = Math.min(viewportHeight * scale);
    console.log( `${height}px`)
    return `${height}px`
}

export { scaleToViewportHeight };
