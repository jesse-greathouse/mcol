/* global axios */
import { has } from '@/funcs';
import { mergeDataIntoQueryString, hrefToUrl } from '@inertiajs/core';

const endpoint = '/api/media-store';
const headers = {
  'Content-Type': 'application/json',
  Accept: 'application/json',
};

const mediaStores = ['applications', 'books', 'games', 'movies', 'music', 'porn', 'tv'];

async function fetchStoreRoot(store, index = null) {
  let data = null;
  let error = null;
  let url = `${endpoint}/${store}`;

  if (null !== index) {
    const [href] = mergeDataIntoQueryString('get', url, { index });
    url = hrefToUrl(href);
  }

  try {
    const response = await axios.get(url, { headers });
    data = response.data.data ?? null;
  } catch (e) {
    error = e;
  }

  return { data, error };
}

async function fetchUri(uri) {
  let data = null;
  let error = null;
  const [href] = mergeDataIntoQueryString('get', endpoint, { uri });
  const url = hrefToUrl(href);

  try {
    const response = await axios.get(url, {
      headers,
      validateStatus: (status) => status === 400 || (status >= 200 && status < 300),
    });

    if (response.status === 400) {
      // We reach this because we ACCEPTED 400 as a valid status.
      // So it's not an exception — just a business logic case.
      error = {
        code: 'ERR_BAD_REQUEST',
        message: `Directory (${uri}) not found`,
        status: 400,
        response: response.data,
      };
    } else {
      data = has(response.data, 'data') ? response.data.data : null;
    }
  } catch (error) {
    // Handle errors, including those with status codes outside the 2xx range
    console.error('Request failed with status:', error.response?.status);
  }

  return { data, error };
}

async function mkDir(uri) {
  let data = null;
  let error = null;
  const url = `${endpoint}`;
  const body = { uri };

  try {
    const response = await axios.post(url, body, headers);
    data = response.data.data ?? null;
  } catch (e) {
    error = e;
  }

  return { data, error };
}

async function rmDir(uri) {
  let data = null;
  let error = null;
  const [href] = mergeDataIntoQueryString('get', endpoint, { uri });
  const url = hrefToUrl(href);

  try {
    const response = await axios.delete(url, { headers });
    data = response.data.data ?? null;
  } catch (e) {
    error = e;
  }

  return { data, error };
}

export { fetchStoreRoot, fetchUri, mkDir, rmDir, mediaStores };
