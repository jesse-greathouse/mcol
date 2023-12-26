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
AND b.nick NOT LIKE 'Beast-X%'
AND p.file_name NOT LIKE '%german%'
AND p.file_name NOT LIKE '%.DoVi%'
AND p.file_name LIKE '%2160%'
-- AND p.file_name LIKE '%Peaky.Blinders%'
-- AND p.file_name LIKE '%MeGusta%'
-- AND p.file_name LIKE '%Family.Guy.S21E%'
-- AND b.nick = '[MG]-4k-Movies|Archive'
AND (b.nick = '[MG]-4k-Movies|POS' OR b.nick = '[MG]-4k-Movies|Archive')
-- AND p.created_at > '2023-11-30 23:59:59'
-- ORDER BY p.created_at DESC;
ORDER BY p.file_name;