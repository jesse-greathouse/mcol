const COMMAND = {
    ADMIN: 'ADMIN',
    INFO: 'INFO',
    JOIN: 'JOIN',
    KICK: 'KICK',
    KILL: 'KILL',
    LINKS: 'LINKS',
    LIST: 'LIST',
    MODE: 'MODE',
    NAMES: 'NAMES',
    NICK: 'NICK',
    NOTICE: 'NOTICE',
    OPER: 'OPER',
    PART: 'PART',
    PING: 'PING',
    PRIVMSG: 'PRIVMSG',
    QUIT: 'QUIT',
    STATS: 'STATS',
    TIME: 'TIME',
    TRACE: 'TRACE',
    VERSION: 'VERSION',
    WHO: 'WHO',
    WHOIS: 'WHOIS',
}

const COMMAND_MASK = {
    '/admin': COMMAND.ADMIN,
    '/info': COMMAND.INFO,
    '/join': COMMAND.JOIN,
    '/kick': COMMAND.KICK,
    '/KILL': COMMAND.KILL,
    '/LINKS': COMMAND.LINKS,
    '/list': COMMAND.LIST,
    '/mode': COMMAND.MODE,
    '/msg': COMMAND.PRIVMSG,
    '/names': COMMAND.NAMES,
    '/nick': COMMAND.NICK,
    '/notice': COMMAND.NOTICE,
    '/op': COMMAND.OPER,
    '/part': COMMAND.PART,
    '/ping': COMMAND.ping,
    '/quit': COMMAND.QUIT,
    '/stats': COMMAND.STATS,
    '/TIME': COMMAND.TIME,
    '/trace': COMMAND.TRACE,
    '/version': COMMAND.VERSION,
    '/who': COMMAND.WHO,
    '/whois': COMMAND.WHOIS,
}

function getCmdMask(command) {
    for (const key in COMMAND_MASK) {
        if (COMMAND_MASK[key] === command) {
          return key
        }
    }

    return null
}

function makeIrcCommand(message, ircTarget = null, ircCommand = null) {
    let [command, error] = [null, null]
    const target = (null !== ircTarget) ? ircTarget : ''

    // If command is null, parse the message for a command.
    if (null === ircCommand) {
        ({command, message, error} = parseMessage(message))
    } else {
        ({command, error} = validateCommand(ircCommand))
    }

    if (null !== error) return null

    return `${command} ${target} ${message}`
}

function parseMessage(parseMessage) {
    const [mask, ...parts] = parseMessage.split(' ')
    const {command, error} = validateCommand(mask)
    const message = parts.join(' ')

    return {command, message, error}
}

function validateCommand(ircCommand) {
    let [command, error] = [null, null]

    // Make uppercase
    let upperCommand = ircCommand.toUpperCase()

    if (isIrcCommand(upperCommand)) {
        command = upperCommand
    } else {
        command = getCommandFromMask(upperCommand)
        if (!command) {
            error = new Error(`Illegal command: "${command}" is not a recognized IRC command.`)
            console.error(error)
        }
    }

    return {command, error}
}

function getCommandFromMask(mask) {
    let command = false
    if (_.has(COMMAND_MASK, mask)) {
        command = COMMAND_MASK[mask]
    }

    return command
}

function isIrcCommand(ircCommand) {
    const keys = Object.keys(COMMAND)
    if (-1 < keys.indexOf(ircCommand)) return true

    return false
}

function parseChatLine(line) {
    // https://regexr.com/890ra
    const re = /^\[(\d{4}\-\d{2}\-\d{2}T\d{2}\:\d{2}\:\d{2}[\+|\-]\d{2}\:\d{2})]\s(.*)$/s;
    let [date, message, error] = [null, null, null]

    try {
        ([, date, message] = re.exec(line));
    } catch(error) {
        // Sometimes the server chunks a line in a way thats impossible to parse.
        console.log(`couldn't parse line: ${line}`)
        console.error(error)
    }

    return {date, message, error}
}

function parseChatMessage(message) {
    let [nick, content, error] = ['', '', null]

    // https://regexr.com/890rs
    const re = /(^([\S]+)\:\s)?(.*)/gs;

    try {
        ([, , nick, content] = re.exec(message));
    } catch(error) {
        //TODO: Fix any parsing errors if possible.
        console.log(`couldn't parse message: ${message}`)
        console.error(error)
    }

    return {nick, content, error}
}

export {
    COMMAND,
    COMMAND_MASK,
    getCmdMask,
    makeIrcCommand,
    parseChatLine,
    parseChatMessage,
}
