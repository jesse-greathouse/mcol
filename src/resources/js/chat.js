import { has, trim, isUndefined } from '@/funcs';

const SERVER = 'server';
const USER = 'user';
const CHANNEL = 'channel';

const COMMAND = {
  ADMIN: 'ADMIN',
  INFO: 'INFO',
  JOIN: 'JOIN',
  KICK: 'KICK',
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
  TIME: 'TIME',
  TRACE: 'TRACE',
  VERSION: 'VERSION',
  WHO: 'WHO',
  WHOIS: 'WHOIS',
  //KILL: 'KILL',
  //STATS: 'STATS',
};

const COMMAND_MASK = {
  '/admin': COMMAND.ADMIN,
  '/info': COMMAND.INFO,
  '/join': COMMAND.JOIN,
  '/kick': COMMAND.KICK,
  '/links': COMMAND.LINKS,
  '/list': COMMAND.LIST,
  '/mode': COMMAND.MODE,
  '/msg': COMMAND.PRIVMSG,
  '/names': COMMAND.NAMES,
  '/nick': COMMAND.NICK,
  '/notice': COMMAND.NOTICE,
  '/op': COMMAND.OPER,
  '/part': COMMAND.PART,
  '/ping': COMMAND.PING,
  '/quit': COMMAND.QUIT,
  '/time': COMMAND.TIME,
  '/trace': COMMAND.TRACE,
  '/version': COMMAND.VERSION,
  '/who': COMMAND.WHO,
  '/whois': COMMAND.WHOIS,
  //'/kill': COMMAND.KILL,
  //'/stats': COMMAND.STATS,
};

const TARGET_CONTEXT = {
  ADMIN: [[SERVER]],
  INFO: [[SERVER]],
  JOIN: [[CHANNEL]],
  KICK: [[CHANNEL], [USER]],
  LINKS: [[SERVER]],
  LIST: [],
  MODE: [[USER, CHANNEL]],
  NAMES: [[CHANNEL]],
  NICK: [],
  NOTICE: [[USER]],
  OPER: [],
  PART: [[CHANNEL]],
  PING: [[SERVER]],
  PRIVMSG: [[CHANNEL, USER]],
  QUIT: [],
  TIME: [],
  TRACE: [[SERVER]],
  VERSION: [[SERVER]],
  WHO: [[USER]],
  WHOIS: [[SERVER]],
  //KILL: [['users']],
  // STATS: [],
};

function getCmdMask(command) {
  for (const key in COMMAND_MASK) {
    if (COMMAND_MASK[key] === command) {
      return key;
    }
  }

  return null;
}

function makeIrcCommand(message, ircTarget = null, ircCommand = null) {
  let [command, error] = [null, null];
  const target = null !== ircTarget ? ircTarget : '';

  // If command is null, parse the message for a command.
  if (null === ircCommand) {
    ({ command, message, error } = parseMessage(message));
  } else {
    ({ command, error } = validateCommand(ircCommand));
  }

  if (null !== error) return null;

  return `${command} ${target} ${message}`;
}

function parseMessage(parseMessage) {
  const [mask, ...parts] = parseMessage.split(' ');
  const { command, error } = validateCommand(mask);
  const message = parts.join(' ');

  return { command, message, error };
}

function validateCommand(ircCommand) {
  let [command, error] = [null, null];

  // Make uppercase
  let upperCommand = ircCommand.toUpperCase();

  if (isIrcCommand(upperCommand)) {
    command = upperCommand;
  } else {
    command = getCommandFromMask(upperCommand);
    if (!command) {
      error = new Error(`Illegal command: "${command}" is not a recognized IRC command.`);
      console.error(error);
    }
  }

  return { command, error };
}

function getCommandFromMask(mask) {
  let command = false;
  if (has(COMMAND_MASK, mask)) {
    command = COMMAND_MASK[mask];
  }

  return command;
}

function isIrcCommand(ircCommand) {
  const keys = Object.keys(COMMAND);
  if (-1 < keys.indexOf(ircCommand)) return true;

  return false;
}

function parseChatLine(line) {
  const re = /^\[(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2})]\s(.*)$/s;
  let [date, message, error] = [null, null, null];

  const match = re.exec(line);
  if (match) {
    [, date, message] = match;
  } else {
    // bad formatting
    error = `Invalid chat line format: ${line}`;
    console.warn(error);
  }

  return { date, message, error };
}

async function parseChatLog(data) {
  let meta = {};
  let parseError = null;
  const lines = data.split(/\r?\n|\r|\n/g);
  const lastIndex = lines.length - 1;

  if (0 <= lastIndex) {
    const lastLine = lines[lastIndex];
    // Remove the last line
    lines.splice(lastIndex, 1);

    // process metadata
    try {
      meta = await parseMeta(lastLine);
    } catch (parseError) {
      console.log(parseError);
    }
  }

  return new Promise((resolve) => {
    resolve({ lines, meta, parseError });
  });
}

function parseChatMessage(message) {
  let [nick, content, error] = ['', '', null];

  // https://regexr.com/890rs
  const re = /(^([\S]+)\:\s)?(.*)/gs;

  try {
    [, , nick, content] = re.exec(message);
  } catch (error) {
    //TODO: Fix any parsing errors if possible.
    console.log(`couldn't parse message: ${message}`);
    console.error(error);
  }

  return { nick, content, error };
}

function parsePacket(message) {
  let [num, gets, size, fileName, error] = [null, null, null, null, null];

  // https://regexr.com/89508
  const re = /^\#(\d{1,4})\s+(\d+)x\s+\[(.*)\]\s+(.*)$/gs;

  try {
    const parts = re.exec(message);

    if (null !== parts && 5 === parts.length) {
      [, num, gets, size, fileName] = parts;
    }
  } catch (error) {
    //TODO: Fix any parsing errors if possible.
    console.log(`couldn't parse message: ${message}`);
    console.error(error);
  }

  return { num, gets, size, fileName, error };
}

function parseDownload(message) {
  let [fileName, ip, port, size, error] = [null, null, null, null, null];

  // https://regexr.com/89d2e
  const re = /^DCC\s+SEND\s+(.*)\s+(.*)\s+(.*)\s+(.*)\s+$/gs;

  try {
    const parts = re.exec(message);

    if (null !== parts && 5 === parts.length) {
      [, fileName, ip, port, size] = parts;
    }
  } catch (error) {
    console.log(`couldn't parse message: ${message}`);
    console.error(error);
  }

  return { fileName, ip, port, size, error };
}

async function parseMeta(line) {
  const data = line.split('[meta]: ')[1];
  return new Promise((resolve) => {
    if (!isUndefined(data)) {
      resolve(JSON.parse(data));
    }
  });
}

function parseInput(line, commands, selectedCommand, selectedParameters = [], lists = {}) {
  let [command, message, parameters, parameterLists, error] = [
    selectedCommand,
    line,
    selectedParameters,
    [],
    null,
  ];
  let numParams = 0;
  let words = message.split(' ');

  // If its an empty string return the defaults
  if (1 > words.length) {
    return { command, parameters, message, parameterLists, error };
  }

  const firstChar = words[0].charAt(0);

  if ('/' === firstChar) {
    if (has(commands, words[0])) {
      const cmdMask = words.shift();
      command = commands[cmdMask];
    }
  }

  if (command && 1 >= words.length && has(TARGET_CONTEXT, command)) {
    words = parameters.concat(words);
    numParams =
      words.length >= TARGET_CONTEXT[command].length
        ? TARGET_CONTEXT[command].length
        : words.length;
    parameters = words.slice(0, numParams);
    parameterLists = makeParameterLists(command, lists);
    words = words.slice(numParams - 1);
  }

  if (1 > words.length) {
    message = trim(words.join(' '));
  }

  return { command, parameters, message, parameterLists, error };
}

function makeParameterLists(command, lists) {
  const parameters = [];
  TARGET_CONTEXT[command].forEach((param) => {
    parameters.push(makeCombinedList(param, lists));
  });
  return parameters;
}

function makeCombinedList(param, lists) {
  let combinedList = [];
  param.forEach((list) => {
    if (has(lists, list)) {
      combinedList = combinedList.concat(lists[list]);
    }
  });

  return combinedList;
}

export {
  COMMAND,
  COMMAND_MASK,
  getCmdMask,
  makeIrcCommand,
  makeParameterLists,
  parseChatLine,
  parseChatLog,
  parseChatMessage,
  parseDownload,
  parseInput,
  parsePacket,
};
