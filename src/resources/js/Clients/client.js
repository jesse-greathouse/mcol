import _ from 'lodash'

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
    });
}

export { get, streamGet };
