import { reactive, toRaw } from 'vue'

export const STATE_VERSION = 2

function deepMerge(target, source) {
  for (const key in source) {
    if (
      source[key] &&
      typeof source[key] === 'object' &&
      !Array.isArray(source[key])
    ) {
      target[key] = deepMerge({ ...(target[key] || {}) }, source[key])
    } else {
      target[key] = source[key]
    }
  }
  return target
}

export function usePageStateSync(key, initialState = {}, options = {}) {
  const version = options.version ?? STATE_VERSION

  const saved = loadStoredPageState(key, version)

  // Merge initialState with saved version to avoid key loss
  const merged = deepMerge({ ...initialState }, saved ?? {})

  const state = reactive(merged)

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
