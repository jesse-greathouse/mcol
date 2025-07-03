import { get } from '@/Clients/client';

const endpoint = '/api/download-queue';
const headers = {
  'Content-Type': 'application/json',
  Accept: 'application/json',
};

async function fetchDownloadQueue() {
  const res = await get(`${endpoint}/queue`, headers);
  return res;
}

export { fetchDownloadQueue };
