import { get } from '@/Clients/client'

const endpoint = '/download.svg'
const headers = {
    'Content-Type': 'image/svg+xml',
    'Accept': 'image/svg+xml',
}

async function fetchDownloadCard(fileName = '') {
    const encoded = encodeURIComponent(fileName);
    const url = `${endpoint}?fileName=${encoded}`
    const res = await get(url, headers);
    return res.data;
}

export {
    fetchDownloadCard
};
