import { get } from '@/Clients/client';

const endpoint = '/api/network';
const headers = {
  'Content-Type': 'application/json',
  Accept: 'application/json',
};

async function fetchNetworkClients() {
  const res = await get(`${endpoint}/clients`, headers);
  return res;
}

export { fetchNetworkClients };
