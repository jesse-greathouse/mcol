// A collection of functions that get used a lot but don't belong in any certain module.

// replaces lodash _.has
const has = function (obj, key) {
    // first start with he easiest qualifier
    if (!obj) return false

    if (obj.hasOwnProperty(key)) {
        return true
    }

    const keyParts = key.split('.')
    return (keyParts.length > 1 && has(obj[key.split('.')[0]], keyParts.slice(1).join('.')))
}

// replaces lodash _.isUndefined
const isUndefined = function(a) {
    return typeof a === 'undefined'
}

// replaces lodash _.intersection
const intersection = function(...arrays) {
    return arrays.reduce((a, b) => a.filter(c => b.includes(c)))
}

// replaces lodash _.trim
const trim = function(str) {
    return str.trim()
}

// replaces lodash _.pickBy
// Creates an object composed of the object properties predicate returns truthy for.
function pickBy(object) {
    const obj = {};
    for (const key in object) {
        if (object[key]) {
            obj[key] = object[key];
        }
    }
    return obj;
}

// replaces lodash _.mapValues
// With the values of an object, map them to a new object with processing of a specific function.
function mapValues(obj, proc) {
    const result = {};

    for (const [key, value] of Object.entries(obj)) {
        result[key] = proc(value, key, obj);
    }

    return result;
}


// replaces lodash _.debounce
const nativeMax = Math.max;
const nativeMin = Math.min;
function debounce(func, wait, options) {
  let lastArgs,
    lastThis,
    maxWait,
    result,
    timerId,
    lastCallTime,
    lastInvokeTime = 0,
    leading = false,
    maxing = false,
    trailing = true;
  if (typeof func !== 'function') {
    throw new TypeError(FUNC_ERROR_TEXT);
  }
  wait = Number(wait) || 0;
  if (typeof options === 'object') {
    leading = !!options.leading;
    maxing = 'maxWait' in options;
    maxWait = maxing
      ? nativeMax(Number(options.maxWait) || 0, wait)
      : maxWait;
    trailing = 'trailing' in options
      ? !!options.trailing
      : trailing;
  }

  function invokeFunc(time) {
    let args = lastArgs,
      thisArg = lastThis;

    lastArgs = lastThis = undefined;
    lastInvokeTime = time;
    result = func.apply(thisArg, args);
    return result;
  }

  function leadingEdge(time) {
    // Reset any `maxWait` timer.
    lastInvokeTime = time;
    // Start the timer for the trailing edge.
    timerId = setTimeout(timerExpired, wait);
    // Invoke the leading edge.
    return leading
      ? invokeFunc(time)
      : result;
  }

  function remainingWait(time) {
    let timeSinceLastCall = time - lastCallTime,
      timeSinceLastInvoke = time - lastInvokeTime,
      result = wait - timeSinceLastCall;
    return maxing
      ? nativeMin(result, maxWait - timeSinceLastInvoke)
      : result;
  }

  function shouldInvoke(time) {
    let timeSinceLastCall = time - lastCallTime,
      timeSinceLastInvoke = time - lastInvokeTime;
    // Either this is the first call, activity has stopped and we're at the trailing
    // edge, the system time has gone backwards and we're treating it as the
    // trailing edge, or we've hit the `maxWait` limit.
    return (lastCallTime === undefined || (timeSinceLastCall >= wait) || (timeSinceLastCall < 0) || (maxing && timeSinceLastInvoke >= maxWait));
  }

  function timerExpired() {
    const time = Date.now();
    if (shouldInvoke(time)) {
      return trailingEdge(time);
    }
    // Restart the timer.
    timerId = setTimeout(timerExpired, remainingWait(time));
  }

  function trailingEdge(time) {
    timerId = undefined;

    // Only invoke if we have `lastArgs` which means `func` has been debounced at
    // least once.
    if (trailing && lastArgs) {
      return invokeFunc(time);
    }
    lastArgs = lastThis = undefined;
    return result;
  }

  function cancel() {
    if (timerId !== undefined) {
      clearTimeout(timerId);
    }
    lastInvokeTime = 0;
    lastArgs = lastCallTime = lastThis = timerId = undefined;
  }

  function flush() {
    return timerId === undefined
      ? result
      : trailingEdge(Date.now());
  }

  function debounced() {
    let time = Date.now(),
      isInvoking = shouldInvoke(time);
    lastArgs = arguments;
    lastThis = this;
    lastCallTime = time;

    if (isInvoking) {
      if (timerId === undefined) {
        return leadingEdge(lastCallTime);
      }
      if (maxing) {
        // Handle invocations in a tight loop.
        timerId = setTimeout(timerExpired, wait);
        return invokeFunc(lastCallTime);
      }
    }
    if (timerId === undefined) {
      timerId = setTimeout(timerExpired, wait);
    }
    return result;
  }
  debounced.cancel = cancel;
  debounced.flush = flush;
  return debounced;
}

// replaces lodash _.throttle
function throttle(func, wait, options) {
  let leading = true,
    trailing = true;

  if (typeof func !== 'function') {
    throw new TypeError(FUNC_ERROR_TEXT);
  }
  if (typeof options === 'object') {
    leading = 'leading' in options
      ? !!options.leading
      : leading;
    trailing = 'trailing' in options
      ? !!options.trailing
      : trailing;
  }
  return debounce(func, wait, {
    leading,
    maxWait: wait,
    trailing,
  });
}


export {
    debounce,
    has,
    intersection,
    isUndefined,
    mapValues,
    pickBy,
    throttle,
    trim,
}
