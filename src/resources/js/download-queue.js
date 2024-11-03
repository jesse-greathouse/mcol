import _ from 'lodash'

// maps a media type to a store
const mediaTypeToStoreMap = {
    movie: 'movies',
    'tv episode': 'tv',
    'tv season': 'tv',
    book: 'books',
    music: 'music',
    porn: 'porn',
    game: 'games',
    application: 'applications',
}

// Holds an index of root uris to store names.
// Useful if you have a destination uri but you don't have the media store name.
// call getRootToStoreMap() function to populate it.
const rootToStoreMap = {}

// Separates a Uri string between the destination root and the rest of the uri.
function splitDestinationDir(uri, roots) {
    const split = {
        root: null,
        uri: null,
    }

    if (null === uri || null === roots) return split

    roots.forEach((root) => {
        if (0 <= uri.indexOf(root)) {
            split.uri = uri.split(root).pop()
            split.root = root
            return
        }
    })

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
function suggestDownloadDestination(download, settings) {
    const DS = settings.system.DS // DIRECTORY_SEPARATOR
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

// Reverse index root uris to store names.
// This function is so it doesn't have to be mapped multiple times.
// const rootToStoreMap is in the higher scope.
function getRootToStoreMapMap(settings) {
    const mapped = Object.keys(rootToStoreMap).length

    if (0 >= mapped) {
        Object.entries(settings.media_store).forEach(([storeName, store]) => {
            store.forEach((root) => {
                rootToStoreMap[root] = storeName
            })
        });
    }

    return rootToStoreMap
}

// Determines a media store from a uri.
function getMediaStoreFromUri(uri, settings) {
    let mediaStore;
    const map = getRootToStoreMapMap(settings)

    // Compare each mapped index to the uri
    // If the mapped index is found in the uri, return the store.
    Object.entries(map).forEach(([root, store]) => {
        if (0 === uri.indexOf(root)) {
            mediaStore = store
            return
        }
    })

    return mediaStore;
}

function getDownloadDestinationRoots(download, settings, mediaStore = null) {
    if (null === mediaStore) {
        mediaStore = mediaTypeToStoreMap[download.media_type]
    }

    return settings.media_store[mediaStore]
}

export {
    mediaTypeToStoreMap,
    getMediaStoreFromUri,
    shouldDisableFileSave,
    suggestDownloadDestination,
    getDownloadDestinationRoots,
    splitDestinationDir
};
