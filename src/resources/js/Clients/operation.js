import { has } from '@/funcs';
import { save } from '@/Clients/client';

const endpoint = '/api/operation';
const headers = {
  'Content-Type': 'application/json',
  Accept: 'application/json',
};

async function saveOperation(operation) {
  let { data, error } = await save(operation, `${endpoint}`, headers);
  if (has(data, 'data')) {
    data = data.data;
  }
  return { data, error };
}

export { saveOperation };
