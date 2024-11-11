import _ from 'lodash'

const endpoint = '/api/network'
const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
}


async function fetchNetworkClients() {
    let data = null
    let error = null
    let url = `${endpoint}/clients`

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

export {
    fetchNetworkClients
};
