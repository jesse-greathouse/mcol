import { has } from '@/funcs';
import { get, save } from '@/Clients/client';

const endpoint = '/api/download-destination';
const headers = {
  'Content-Type': 'application/json',
  Accept: 'application/json',
};

async function saveDownloadDestination(downloadDestination) {
  let { data, error } = await save(downloadDestination, `${endpoint}`, headers);
  if (has(data, 'data')) {
    data = data.data;
  }

  return { data, error };
}

export { saveDownloadDestination };
