import { format } from "date-fns";

const formatDate = (date, time = false) => {
    if (null === date) return ''
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

const formatISODate = (date) => {
    return format(new Date(date), "MM/dd/yyyy"); // '01/24/2024'
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

export { formatDate, formatISODate, formatTruncate };
