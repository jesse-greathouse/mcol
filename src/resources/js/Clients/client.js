import _ from 'lodash'
import axios from 'axios';

async function get(url, headers) {
    let data = null
    let error = null
    try {
        const response = await axios.get(url, headers)
        if (_.has(response, 'data')) {
            data =  response.data
        }
    } catch (e) {
        error = e
        console.error(error)
    }

    return {data, error}
}

async function post(body, url, headers) {
    let data = null
    let error = null

    try {
        const response = await axios.post(url, body, headers)
        if (_.has(response, 'data')) {
            data =  response.data
        }
    } catch (e) {
        error = e
        console.error(error)
    }

    return {data, error}
}

async function put(body, url, headers) {
    let data = null
    let error = null

    try {
        const response = await axios.put(url, body, headers)
        if (_.has(response, 'data')) {
            data =  response.data
        }
    } catch (e) {
        error = e
        console.error(error)
    }

    return {data, error}
}

async function save(body, url, headers, id = null) {
    // If the object has an Id property, treat it as a put.
    if (_.has(body, 'id')) {
        id = body.id
    }

    if (null !== id ) {
        url = `${url}/${id}`
        return put(body, url, headers)
    }

    return post(body, url, headers)
}

// This is done with XMLHttpRequest
// Axios apparently does not support client streaming
async function streamGet(url, headers, parse) {
    var xhr = new XMLHttpRequest()
    xhr.open('GET', url, true)
    xhr.timeout = 10000
    setHeaders(xhr, headers)

    xhr.ontimeout = function() {
      console.error(`Request to ${url} timed out!`)
    };

    xhr.onprogress = function() {
        var responseText = xhr.responseText
        var chunk = responseText.slice(xhr.prevLen)
        xhr.prevLen = responseText.length
        parse(chunk)
    }

    xhr.send()
}

const setHeaders = (xhr, headers) => {
    Object.keys(headers).forEach(key => {
        xhr.setRequestHeader(key, headers[key]);
    })
}

export { get, post, put, save, streamGet };
