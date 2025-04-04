import { reactive, toRaw } from 'vue'

export const STATE_VERSION = 2

export function usePageStateSync(key, initialState = {}, options = {}) {
  const version = options.version ?? STATE_VERSION

  const saved = loadStoredPageState(key, version)
  const state = reactive(saved ?? { ...initialState })

  const saveState = () => {
    const raw = toRaw(state)
    raw._version = version
    localStorage.setItem(key, JSON.stringify(raw))
  }

  return { state, saveState }
}

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
