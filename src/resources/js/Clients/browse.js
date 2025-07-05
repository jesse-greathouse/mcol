import { get } from '@/Clients/client';
import { mergeDataIntoQueryString, hrefToUrl } from '@inertiajs/core';

const endpoint = '/api/browse';
const headers = {
  'Content-Type': 'application/json',
  Accept: 'application/json',
};

async function fetchLocks(packetList = null) {
  let url = `${endpoint}/locks`;
  if (null !== packetList) {
    const [_href] = mergeDataIntoQueryString('get', url, { packet_list: packetList }, 'brackets');
    url = hrefToUrl(_href);
  }
  const res = await get(url, headers);
  return res;
}

export { fetchLocks };
