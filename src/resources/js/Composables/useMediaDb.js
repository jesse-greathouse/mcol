import { openDB } from 'idb'; // If you're using `idb` for easier IndexedDB handling

const DB_NAME = 'media-db';
const STORE_NAME = 'media';
const DB_VERSION = 1;

let dbPromise = null;

async function getDb() {
  if (!dbPromise) {
    dbPromise = openDB(DB_NAME, DB_VERSION, {
      upgrade(db) {
        if (!db.objectStoreNames.contains(STORE_NAME)) {
          db.createObjectStore(STORE_NAME);
        }
      },
    });
  }
  return dbPromise;
}

export function useMediaDb() {
  async function saveMedia(key, html) {
    try {
      const db = await getDb();
      console.log(`[IndexedDB] Saving buffer: ${key}, size=${html.length}`);
      await db.put(STORE_NAME, html, key);
    } catch (e) {
      console.warn(`Failed to save buffer [${key}]:`, e);
    }
  }

  async function loadMedia(key) {
    try {
      const db = await getDb();
      return await db.get(STORE_NAME, key);
    } catch (e) {
      console.warn(`Failed to load buffer [${key}]:`, e);
      return null;
    }
  }

  async function deleteMedia(key) {
    try {
      const db = await getDb();
      await db.delete(STORE_NAME, key);
    } catch (e) {
      console.warn(`Failed to delete buffer [${key}]:`, e);
    }
  }

  return {
    saveMedia,
    loadMedia,
    deleteMedia,
  };
}
