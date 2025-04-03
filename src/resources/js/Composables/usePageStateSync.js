export const STATE_VERSION = 2

export function usePageStateSync(key, formRef, options = {}) {
  const version = options.version ?? STATE_VERSION

  const loadState = () => {
    return loadStoredPageState(key, version)
  }

  const saveState = () => {
    const data = {
      ...formRef,
      _version: version,
    }
    localStorage.setItem(key, JSON.stringify(data))
  }

  return { loadState, saveState }
}

/**
 * Load raw state from localStorage with version validation.
 */
export function loadStoredPageState(key, version = STATE_VERSION) {
  const raw = localStorage.getItem(key)
  if (!raw) return null

  try {
    const parsed = JSON.parse(raw)
    if (!parsed._version || parsed._version !== version) {
      localStorage.removeItem(key)
      return null
    }
    return parsed
  } catch (e) {
    console.warn(`loadStoredPageState: failed to parse "${key}"`, e)
    return null
  }
}
