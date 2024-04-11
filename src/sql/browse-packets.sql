SELECT b.nick, count(p.id) as packets
FROM mcol.packets p 
JOIN mcol.networks n on p.network_id = n.id
JOIN mcol.bots b on p.bot_id = b.id
JOIN mcol.`channels` c on p.channel_id = c.id
WHERE p.file_name LIKE '%2160%'
AND p.file_name NOT LIKE '%german%'
GROUP BY b.nick
ORDER BY packets DESC;

SELECT p.created_at, p.size, p.file_name, n.name, b.nick, p.number
FROM mcol.packets p 
JOIN mcol.networks n on p.network_id = n.id
JOIN mcol.bots b on p.bot_id = b.id
JOIN mcol.`channels` c on p.channel_id = c.id
WHERE 1
AND b.nick = '[mg]-LongStores'
AND p.file_name NOT LIKE '%german%'
AND p.file_name NOT LIKE '%.DoVi%'
AND p.file_name NOT LIKE '%720p%'
-- AND p.file_name LIKE '%2160%'
-- AND p.file_name LIKE '%Yellowstone.S0%'
-- AND p.file_name LIKE '%Attack.On.Titan%'
-- AND b.nick = '[MG]-4k-Movies|Archive'
-- AND (b.nick = '[MG]-4k-Movies|POS' OR b.nick = '[MG]-4k-Movies|Archive')
-- AND p.created_at > '2023-12-12 23:59:59'
-- ORDER BY p.created_at DESC;
ORDER BY p.file_name;


SELECT p.created_at, p.size, p.file_name, n.name, b.nick, p.number
FROM mcol.packets p 
JOIN mcol.networks n on p.network_id = n.id
JOIN mcol.bots b on p.bot_id = b.id
JOIN mcol.`channels` c on p.channel_id = c.id
WHERE 1
AND b.nick NOT LIKE 'Beast-%'
AND b.nick <> '561AARU6K'
AND b.nick <> ''
AND p.file_name NOT LIKE '%german%'
AND p.file_name NOT LIKE '%.DoVi%'
AND (p.file_name LIKE '%1080%' OR p.file_name LIKE '%2160%')
-- AND p.file_name LIKE '%2160%'
-- AND p.file_name LIKE '%Echo%'
AND p.created_at > '2024-01-18 23:59:59'
-- AND p.file_name RLIKE '[.]+S[0-9]+[.]+'
-- AND p.file_name LIKE '%Berlin.2023%'
-- AND b.nick = '[MG]-4k-Movies|Archive'
-- AND (b.nick = '[MG]-4k-Movies|POS' OR b.nick = '[MG]-4k-Movies|Archive')
-- AND b.nick = '[MG]-Request|Bot|Gelato'
AND p.file_name RLIKE '.*tar$|.*mkv$|.*avi$|.*mp4$|.*nsp$|.*dmg$|.*iso$|.*pdf$|.*pkg$|.*epub$|.*m4a$|.*m4v$|.*exe$|.*dll$|.*rar$|.*mobi$|.*m4b$|.*txt$|.*mp3$|.*wav$'
ORDER BY p.created_at DESC;
-- ORDER BY p.file_name;