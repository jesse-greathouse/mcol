import { trim } from '@/funcs'

function parseSystemMessage(line, queue = null) {
    let [ts, routingKey, msg, error] = [null, null, null, null]

    // Separate the message from the metadata at the last ":" colon.
    const meta = line.split(":::");

    if (meta.length !== 3) {
        error = `Unable to parse SystemChat line: ${line}`
        return {ts, routingKey, msg, error}
    }

    ([ ts, routingKey, msg ] = meta)

    if (ts) {
        // Transform ts to Date object.
        const date = new Date(ts)
        if (isNaN(date.getTime())) {
            error = `Invalid date: ${ts}`
            return { ts, routingKey, msg, error }
        }

        // transform ts into Date object.
        ts = date
    }

    if (routingKey) {
        if (queue) {
            // split routing key at queue
            let halves = routingKey.split(queue + '.')
            if (halves.length > 1) {
                routingKey = trim(halves[1])
            }
        }
    }

    // trim msg
    if (msg) {
        msg = trim(msg)
    }

    return {ts, routingKey, msg, error}
}

export {
    parseSystemMessage,
}
