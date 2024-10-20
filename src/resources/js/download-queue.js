import _ from 'lodash'

// maps a media type to a store
const mediaTypeToStoreMap = {
    movie: 'movies',
    'tv episode': 'tv',
    'tv season': 'tv',
    book: 'books',
    music: 'music',
    game: 'games',
    application: 'applications',
}

function splitDestinationDir(destination, roots) {
    const split = {
        root: null,
        uri: null,
    }

    if (!_.has(destination, 'destination_dir')) return split

    if (destination.destination_dir && null !== roots) {
        roots.forEach((root) => {
            if (0 <= destination.destination_dir.indexOf(root)) {
                split.uri = destination.destination_dir.split(root).pop()
                split.root = root
                return
            }
        })
    }

    return split
}

// This complicated algorithm ...
// If any conditions are not met, don't enable file save.
function shouldDisableFileSave(download, settings) {
    // To have the ability to save a file:
    // Must have the "media_store" section of settings
    if (!_.has(settings, 'media_store')) return true

    // Must have media_type in mediaTypeToStoreMap
    if (!_.has(mediaTypeToStoreMap, download.media_type)) return true

    // Must have the mapped media store in settings.media_store
    const mediaStore = mediaTypeToStoreMap[download.media_type]
    if (!_.has(settings.media_store, mediaStore)) return true

    // settings.media_store[mediaStore][] cannot be emnpty
    if (0 >= settings.media_store[mediaStore].length) return true

    // None of the conditions failed, so file can be saved.
    return false
}

// Does not include the root directory.
function suggestDownloadDestination(download) {
    const DS = '/'
    // If metadata doesn't exist, bail.
    if (null === download.meta) {
        return ''
    }

    const { title, season = null} = download.meta

    switch(download.media_type) {
        case 'movie':
            return '';
        case 'tv season':
            if (null !== title && null !== season) {
                return DS + title + DS + season;
            }
        case 'tv episode':
            if (null !== title && null !== season) {
                return DS + title + DS + season;
            }
        default:
            if (null !== title && '' !== title) {
                return DS + title;
            } else {
                return '';
            }
    }
}

function getDownloadDestinationRoots(download, settings) {
    const mediaStore = mediaTypeToStoreMap[download.media_type]
    return settings.media_store[mediaStore]
}

export {
    mediaTypeToStoreMap,
    shouldDisableFileSave,
    suggestDownloadDestination,
    getDownloadDestinationRoots,
    splitDestinationDir
};
