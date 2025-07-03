import { rpc } from '@/Clients/client';

const endpoint = '/api/rpc';
const headers = {
  'Content-Type': 'application/json',
  Accept: 'application/json',
};

async function removeCompleted(download) {
  const url = `${endpoint}/remove-completed`;
  return rpc(url, 'removeCompleted@request', { download: download.id }, headers);
}

async function requestDownload(packetId) {
  const url = `${endpoint}/download`;
  return rpc(url, 'download@request', { packet: packetId }, headers);
}

async function requestRemove(packetId) {
  const url = `${endpoint}/remove`;
  return rpc(url, 'remove@request', { packet: packetId }, headers);
}

async function requestCancel(download) {
  const url = `${endpoint}/cancel`;
  return rpc(url, 'cancel@request', { bot: download.packet.bot_id }, headers);
}

export { removeCompleted, requestDownload, requestRemove, requestCancel };
