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


export { COMMAND, COMMAND_MASK, getCmdMask}
