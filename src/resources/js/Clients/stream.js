import _ from 'lodash'

const endpoint = '/stream'
const headers = {
    'Accept': 'text/plain',
}

// This is done with XMLHttpRequest
// Axios apparently does not support client streaming
async function streamMessage(network, channel, offset = 0, parse) {
    let url = `${endpoint}/network/${network}/channel/${channel}/message?offset=${offset}`

    var xhr = new XMLHttpRequest()
    xhr.open('GET', url, true)
    xhr.timeout = 10000

    xhr.ontimeout = function() {
      console.error('Request timed out!')
    };

    xhr.onprogress = function() {
        var responseText = xhr.responseText
        var chunk = responseText.slice(xhr.prevLen)
        xhr.prevLen = responseText.length
        parse(chunk)
    }

    xhr.send()
}

async function fetchMessage(network, channel, offset = 0) {
    let data = null
    let error = null
    let url = `${endpoint}/network/${network}/channel/${channel}/message?offset=${offset}`

    try {
        const response = await axios.get(url, { headers: headers})
        if (_.has(response, 'data')) {
            data =  response.data
        }
    } catch (e) {
        error = e
    }

    return {data, error}
}

async function fetchConsole(network, offset = 0) {
    let data = null
    let error = null
    let url = `${endpoint}/network/${network}/console?offset=${offset}`

    try {
        const response = await axios.get(url, headers)
        if (_.has(response, 'data')) {
            data =  response.data
        }
    } catch (e) {
        error = e
    }

    return {data, error}
}

export {
    fetchConsole, fetchMessage, streamMessage
};
