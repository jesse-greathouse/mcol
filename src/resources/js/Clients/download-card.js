import { get } from '@/Clients/client'

const endpoint = '/download.svg'
const headers = {
    'Content-Type': 'image/svg+xml',
    'Accept': 'image/svg+xml',
}

async function fetchDownloadCard(fileName = '', label = null) {
    const encoded = encodeURIComponent(fileName);
    let url = `${endpoint}?fileName=${encoded}`

    if (label) {
        url = url + `&label=${label}`
    }

    const res = await get(url, headers);
    return res.data;
}

export {
    fetchDownloadCard
};
