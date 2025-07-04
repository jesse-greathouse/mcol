import { has } from '@/funcs';

const DOWNLOAD_STATE_COMPLETED = 'completed';
const DOWNLOAD_STATE_INCOMPLETE = 'incomplete';
const DOWNLOAD_STATE_QUEUED = 'queued';

const downloadStates = [DOWNLOAD_STATE_COMPLETED, DOWNLOAD_STATE_INCOMPLETE, DOWNLOAD_STATE_QUEUED];

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
};

// Holds an index of root uris to store names.
// Useful if you have a destination uri but you don't have the media store name.
// call getRootToStoreMap() function to populate it.
const rootToStoreMap = {};

// Separates a Uri string between the destination root and the rest of the uri.
function splitDestinationDir(uri, roots) {
  const split = {
    root: null,
    uri: null,
  };

  if (null === uri || null === roots) return split;

  roots.forEach((root) => {
    if (0 <= uri.indexOf(root)) {
      split.uri = uri.split(root).pop();
      split.root = root;
      return;
    }
  });

  return split;
}

// This complicated algorithm ...
// If any conditions are not met, don't enable file save.
function shouldDisableFileSave(download, settings) {
  // To have the ability to save a file:
  // Must have the "media_store" section of settings
  if (!has(settings, 'media_store')) return true;

  // Must have media_type in mediaTypeToStoreMap
  if (!has(mediaTypeToStoreMap, download.media_type)) return true;

  // Must have the mapped media store in settings.media_store
  const mediaStore = mediaTypeToStoreMap[download.media_type];
  if (!has(settings.media_store, mediaStore)) return true;

  // settings.media_store[mediaStore][] cannot be emnpty
  if (0 >= settings.media_store[mediaStore].length) return true;

  // None of the conditions failed, so file can be saved.
  return false;
}

// Does not include the root directory.
function suggestDownloadDestination(download, settings) {
  const DS = settings.system.DS; // DIRECTORY_SEPARATOR
  // If metadata doesn't exist, bail.
  if (null === download.meta) {
    return '';
  }

  const { title, season = null } = download.meta;

  switch (download.media_type) {
    case 'movie':
      return '';

    case 'tv season':
      if (title !== null && season !== null) {
        return DS + title + DS + season;
      }
      return '';

    case 'tv episode':
      if (title !== null && season !== null) {
        return DS + title + DS + season;
      }
      return '';

    default:
      if (title !== null && title !== '') {
        return DS + title;
      }
      return '';
  }
}

// Reverse index root uris to store names.
// This function is so it doesn't have to be mapped multiple times.
// const rootToStoreMap is in the higher scope.
function getRootToStoreMapMap(settings) {
  const mapped = Object.keys(rootToStoreMap).length;

  if (0 >= mapped) {
    Object.entries(settings.media_store).forEach(([storeName, store]) => {
      store.forEach((root) => {
        rootToStoreMap[root] = storeName;
      });
    });
  }

  return rootToStoreMap;
}

// Determines a media store from a uri.
function getMediaStoreFromUri(uri, settings) {
  let mediaStore;
  const map = getRootToStoreMapMap(settings);

  // Compare each mapped index to the uri
  // If the mapped index is found in the uri, return the store.
  Object.entries(map).forEach(([root, store]) => {
    if (0 === uri.indexOf(root)) {
      mediaStore = store;
      return;
    }
  });

  return mediaStore;
}

function getDownloadDestinationRoots(download, settings, mediaStore = null) {
  if (null === mediaStore) {
    mediaStore = mediaTypeToStoreMap[download.media_type];
  }

  return settings.media_store[mediaStore];
}

// Creates an index that keys the file name for each file object.
function indexQueue(files, index) {
  files.forEach((file) => {
    if (!has(index, file.file_name)) {
      index[file.file_name] = file;
    }
  });
}

function makeDownloadIndexFromQueue(downloadQueue) {
  const index = {};

  downloadStates.forEach((state) => {
    if (has(downloadQueue, state)) {
      indexQueue(downloadQueue[state], index);
    }
  });

  return index;
}

export {
  DOWNLOAD_STATE_COMPLETED,
  DOWNLOAD_STATE_INCOMPLETE,
  DOWNLOAD_STATE_QUEUED,
  mediaTypeToStoreMap,
  getMediaStoreFromUri,
  makeDownloadIndexFromQueue,
  shouldDisableFileSave,
  suggestDownloadDestination,
  getDownloadDestinationRoots,
  splitDestinationDir,
};
