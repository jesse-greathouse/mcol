const bytesInGB = 1e+9
const bytesInMB = 1e+6
const bytesInKB = 1000

function shouldShowIn(bytes, unit) {
  return (unit <= Number(bytes)) ? true : false
}

function formatSizeBy(bytes, unit, label = null, precision = 1) {
  const size = Number(bytes) / unit
  const display = size.toFixed(precision)
  if (null !== label) {
    return `${display} ${label}`
  } else {
    return display
  }
}

function formatSize(bytes) {
  if (shouldShowIn(bytes, bytesInGB)) {
    return formatSizeBy(bytes, bytesInGB, 'G')
  } else if (shouldShowIn(bytes, bytesInMB)) {
    return formatSizeBy(bytes, bytesInMB, 'M')
  } else if (shouldShowIn(bytes, bytesInKB)) {
    return formatSizeBy(bytes, bytesInKB, 'K')
  } else {
    return `${bytes} B`
  }
}

export { shouldShowIn, formatSizeBy, formatSize};
