import { format, formatRFC3339 } from "date-fns";

const cleanChannelName = (channelName) => {
    return channelName.slice(1)
}

const formatDate = (date, time = false) => {
    if (null === date) return ''

    // regexr.com/890rg
    const dateMask = /(\d{4}-\d{2}-\d{2})\s*(\d{2}\:\d{2}\:\d{2})*/

    const matches = date.date.match(dateMask)
    const dateStr = matches[1]

    if (time) {
        const timeStr = matches[2]
        return `${dateStr} ${timeStr}`
    } else {
        return `${dateStr}`
    }
}

const formatISODate = (date, formatStr = 'MM/dd/yyyy') => {
    return format(new Date(date), formatStr); // '01/24/2024'
}

const makeChatLogDate = () => {
    const date = new Date()
    return formatRFC3339(date, {})
}

const formatTruncate = (str, total, offset = null, display = '[...]') => {
    const length = str.length

    // length might not need to be truncated.
    if (length <= total) return str

    const end = length - (total + display.length)

    // if the math returns a negative, just forget it and return the str
    if (end < 1) return str

    if (null === offset) {
        return str.substring(0, end) + display
    } else {
        const removeLength = total - end
        const chunk = str.substring(offset, removeLength)

        // if the substring grabs empty, just return the whole string.
        if (chunk == '') return str
        return str.replace(chunk, display)
    }
}



export {
    cleanChannelName,
    formatDate,
    formatISODate,
    formatTruncate,
    makeChatLogDate,
}
