import { get, streamGet } from '@/Clients/client'

const endpoint = '/stream'
const headers = {
    'Accept': 'text/plain',
}

async function fetchMessage(network, channel, offset = 0) {
    const res = await get(`${endpoint}/network/${network}/channel/${channel}/message?offset=${offset}`, headers)
    return res
}

async function fetchConsole(network, offset = 0) {
    return await get(`${endpoint}/network/${network}/console?offset=${offset}`, headers)
}

async function streamConsole(network, offset = 0, parse) {
    const url = `${endpoint}/network/${network}/console?offset=${offset}`
    await streamGet(url, headers, parse)
}

async function streamEvent(network, channel, offset = 0, parse) {
    const url = `${endpoint}/network/${network}/channel/${channel}/event?offset=${offset}`
    await streamGet(url, headers, parse)
}

async function streamMessage(network, channel, offset = 0, parse) {
    const url = `${endpoint}/network/${network}/channel/${channel}/message?offset=${offset}`
    await streamGet(url, headers, parse)
}

async function streamNotice(network, offset = 0, parse) {
    const url = `${endpoint}/network/${network}/notice?offset=${offset}`
    await streamGet(url, headers, parse)
}

async function streamPrivmsg(network, offset = 0, parse) {
    const url = `${endpoint}/network/${network}/privmsg?offset=${offset}`
    await streamGet(url, headers, parse)
}

async function streamSystemMessage(queue, parse) {
    const url = `${endpoint}/system-message?queue=${queue}`
    await streamGet(url, headers, parse)
}

export {
    fetchConsole,
    fetchMessage,
    streamConsole,
    streamEvent,
    streamMessage,
    streamNotice,
    streamPrivmsg,
    streamSystemMessage
};
