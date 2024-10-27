import _ from 'lodash'
import { mergeDataIntoQueryString, hrefToUrl } from '@inertiajs/core'

const endpoint = '/api/media-store'
const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
}

const mediaStores = [
    'applications',
    'books',
    'games',
    'movies',
    'music',
    'tv'
]

async function fetchStoreRoot(store, index = null) {
    let data = null
    let error = null
    let url = `${endpoint}/${store}`

    if (null !== index) {
        const [_href,] = mergeDataIntoQueryString('get', url, {index})
        url = hrefToUrl(_href)
    }

    try {
        const response = await axios.get(url, headers)
        if (_.has(response.data, 'data')) {
            data =  response.data.data
        }
    } catch (e) {
        error = e
    }

    return {data, error}
}

async function fetchUri(uri) {
    let data = null
    let error = null
    const [_href,] = mergeDataIntoQueryString('get', endpoint, {uri})
    const url = hrefToUrl(_href)

    try {
        const response = await axios.get(url, headers)
        if (_.has(response.data, 'data')) {
            data =  response.data.data
        }
    } catch (e) {
        error = e
    }

    return {data, error}
}

async function mkDir(uri) {
    let data = null
    let error = null
    const url = `${endpoint}/${store}`
    const body = {uri}

    try {
        const response = await axios.post(url, body, headers)
        if (_.has(response.data, 'data')) {
            data =  response.data.data
        }
    } catch (e) {
        error = e
    }

    return {data, error}
}

async function rmDir(uri) {
    let data = null
    let error = null
    const [_href,] = mergeDataIntoQueryString('get', endpoint, {uri})
    const url = hrefToUrl(_href)

    try {
        const response = await axios.delete(url, headers)
        if (_.has(response.data, 'data')) {
            data =  response.data.data
        }
    } catch (e) {
        error = e
    }

    return {data, error}
}

export {
    fetchStoreRoot,
    fetchUri,
    mkDir,
    rmDir,
    mediaStores
};
