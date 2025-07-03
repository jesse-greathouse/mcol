const scaleToViewportHeight = (scale) => {
  const height = Math.min(window.innerHeight * scale);
  return `${height}px`;
};

export { scaleToViewportHeight };
